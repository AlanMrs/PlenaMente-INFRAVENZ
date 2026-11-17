<?php
namespace App\Libraries;

/**
 * Smtp: cliente SMTP minimo en PHP nativo (sin librerias externas).
 *
 * A diferencia de mail(), que solo indica que el mensaje fue entregado al
 * proceso de envio local, este cliente habla directamente con el servidor SMTP
 * y conoce su respuesta real (250 accepted, 535 auth failed, 550 blocked...).
 * Esa informacion es la que permite registrar honestamente el estado del envio.
 *
 * Soporta: EHLO, STARTTLS, AUTH LOGIN, MAIL FROM, RCPT TO, DATA y QUIT.
 */
class Smtp {

    private $host;
    private $puerto;
    private $cifrado;
    private $usuario;
    private $clave;
    private $timeout;

    private $socket = null;
    private $dialogo = [];
    private $errores = [];

    public function __construct($host, $puerto = 587, $cifrado = 'tls', $usuario = '', $clave = '', $timeout = 20) {
        $this->host = $host;
        $this->puerto = (int)$puerto;
        $this->cifrado = strtolower((string)$cifrado);
        $this->usuario = (string)$usuario;
        $this->clave = (string)$clave;
        $this->timeout = (int)$timeout;
    }

    /**
     * Envia un mensaje. Devuelve true solo si el servidor SMTP responde
     * 250 (mensaje aceptado para entrega).
     */
    public function enviar($remitente, $destinatario, $asunto, $cuerpoHtml) {
        $this->dialogo = [];
        $this->errores = [];

        if (!$this->conectar()) {
            return false;
        }

        try {
            if (!$this->esperar([220])) {
                return false;
            }

            $nombreHost = $this->host;
            if (!$this->comando('EHLO ' . $nombreHost, [250])) {
                return false;
            }

            // Cifrado STARTTLS (puerto 587)
            if ($this->cifrado === 'tls') {
                if (!$this->comando('STARTTLS', [220])) {
                    return false;
                }
                if (!$this->activarCifrado()) {
                    return false;
                }
                if (!$this->comando('EHLO ' . $nombreHost, [250])) {
                    return false;
                }
            }

            // Autenticacion
            if ($this->usuario !== '') {
                if (!$this->autenticar()) {
                    return false;
                }
            }

            if (!$this->comando('MAIL FROM:<' . $this->sanear($remitente) . '>', [250])) {
                return false;
            }

            if (!$this->comando('RCPT TO:<' . $this->sanear($destinatario) . '>', [250, 251])) {
                return false;
            }

            if (!$this->comando('DATA', [354])) {
                return false;
            }

            $this->escribir($this->construirMensaje($remitente, $destinatario, $asunto, $cuerpoHtml) . "\r\n.\r\n");
            $this->dialogo[] = $this->leer();

            $codigo = (int)substr(end($this->dialogo), 0, 3);
            if ($codigo !== 250) {
                $this->errores[] = 'El servidor SMTP no acepto el mensaje: ' . end($this->dialogo);
                $this->cerrar();
                return false;
            }

            $this->comando('QUIT', [221]);
            $this->cerrar();
            return true;
        } catch (\Throwable $e) {
            $this->errores[] = 'Error durante el envio SMTP: ' . $e->getMessage();
            $this->cerrar();
            return false;
        }
    }

    // Dialogo completo con el servidor (util para diagnostico)
    public function obtenerDialogo() {
        return $this->dialogo;
    }

    // Errores detectados
    public function obtenerErrores() {
        return $this->errores;
    }

    private function conectar() {
        $transporte = ($this->cifrado === 'ssl') ? 'ssl://' : 'tcp://';
        $destino = $transporte . $this->host . ':' . $this->puerto;

        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $errorInfo = '';
        $socket = @stream_socket_client($destino, $errno, $errorInfo, $this->timeout, STREAM_CLIENT_CONNECT, $contexto);

        if (!$socket) {
            $this->errores[] = 'No se pudo conectar al servidor SMTP ' . $this->host . ':' . $this->puerto
                . ($errorInfo !== '' ? ' (' . $errorInfo . ')' : '');
            return false;
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, $this->timeout);
        return true;
    }

    private function activarCifrado() {
        $ok = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($ok !== true) {
            $this->errores[] = 'No se pudo activar el cifrado TLS con el servidor SMTP.';
            $this->cerrar();
            return false;
        }
        return true;
    }

    private function autenticar() {
        if (!$this->comando('AUTH LOGIN', [334])) {
            return false;
        }
        if (!$this->comando(base64_encode($this->usuario), [334])) {
            return false;
        }
        if (!$this->comando(base64_encode($this->clave), [235])) {
            $this->errores[] = 'El servidor SMTP rechazo las credenciales del buzon de salida.';
            return false;
        }
        return true;
    }

    /**
     * Lee la respuesta del servidor y comprueba el codigo esperado
     * (se usa para el saludo inicial, que no es un comando).
     */
    private function esperar(array $codigosValidos) {
        $respuesta = $this->leer();
        $this->dialogo[] = '<servidor> ' . $respuesta;

        $codigo = (int)substr($respuesta, 0, 3);
        if (!in_array($codigo, $codigosValidos, true)) {
            $this->errores[] = 'El servidor SMTP respondio "' . $respuesta . '" en la conexion inicial.';
            return false;
        }
        return true;
    }

    private function comando($comando, array $codigosValidos) {
        $this->escribir($comando . "\r\n");
        $respuesta = $this->leer();
        $this->dialogo[] = $comando . ' -> ' . $respuesta;

        $codigo = (int)substr($respuesta, 0, 3);
        if (!in_array($codigo, $codigosValidos, true)) {
            $this->errores[] = 'El servidor SMTP respondio "' . $respuesta . '" al comando ' . explode(' ', $comando)[0] . '.';
            return false;
        }
        return true;
    }

    // Escribe varios comandos seguidos (para AUTH LOGIN)
    private function escribir($datos) {
        $longitud = strlen($datos);
        $enviado = 0;
        while ($enviado < $longitud) {
            $resultado = @fwrite($this->socket, substr($datos, $enviado));
            if ($resultado === false || $resultado === 0) {
                throw new \RuntimeException('No se pudo escribir en la conexion SMTP.');
            }
            $enviado += $resultado;
        }
    }

    // Lee una respuesta completa (soporta respuestas multilinea)
    private function leer() {
        $respuesta = '';
        while (($linea = @fgets($this->socket, 515)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] !== '-') {
                break;
            }
        }
        return trim($respuesta);
    }

    private function construirMensaje($remitente, $destinatario, $asunto, $cuerpoHtml) {
        $nombreRemitente = $this->nombreRemitente();
        $asunto = $this->sanear($asunto);
        $cuerpo = base64_encode($cuerpoHtml);

        $cabeceras = [];
        $cabeceras[] = 'Date: ' . date('r');
        $cabeceras[] = 'From: ' . $nombreRemitente . ' <' . $remitente . '>';
        $cabeceras[] = 'To: <' . $destinatario . '>';
        $cabeceras[] = 'Subject: ' . $asunto;
        $cabeceras[] = 'Reply-To: ' . $remitente;
        $cabeceras[] = 'Message-ID: <' . uniqid('plena', true) . '@' . $this->dominioDe($remitente) . '>';
        $cabeceras[] = 'MIME-Version: 1.0';
        $cabeceras[] = 'Content-Type: text/html; charset=UTF-8';
        $cabeceras[] = 'Content-Transfer-Encoding: base64';
        $cabeceras[] = 'X-Mailer: PlenaMente INFRAVENZ';

        $mensaje = implode("\r\n", $cabeceras) . "\r\n\r\n" . chunk_split($cuerpo, 76, "\r\n");
        return $mensaje;
    }

    // Dominio de una direccion de correo (para el Message-ID)
    private function dominioDe($correo) {
        $posicion = strrpos((string)$correo, '@');
        return $posicion !== false ? substr((string)$correo, $posicion + 1) : $this->host;
    }

    private function nombreRemitente() {
        $config = Correo::config();
        $nombre = $config['nombre_remitente'] ?? 'PlenaMente';
        return '"' . str_replace(['"', "\r", "\n"], '', $nombre) . '"';
    }

    // Elimina saltos de linea (evita inyeccion de cabeceras)
    private function sanear($valor) {
        return trim(str_replace(["\r", "\n", "%0a", "%0d"], '', (string)$valor));
    }

    private function cerrar() {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }
}
