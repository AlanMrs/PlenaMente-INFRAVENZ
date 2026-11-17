<?php
namespace App\Libraries;

use App\Core\Logger;

require_once __DIR__ . '/Smtp.php';

/**
 * Correo: envio de correos del sistema (recordatorios, recuperacion de contrasena).
 *
 * Prioriza SMTP porque mail() solo indica que el mensaje fue entregado al proceso
 * local de envio: devuelve true aunque el servidor despues lo descarte. Con SMTP
 * se conoce la respuesta real del servidor (250 aceptado, 535 credenciales,
 * 550 bloqueado...) y se puede registrar el estado honestamente.
 */
class Correo {

    /**
     * Carga la configuracion de correo.
     * Prioridad: Correo.local.php > variable de entorno > Correo.php (versionado).
     */
    public static function config() {
        $base = require __DIR__ . '/../Config/Correo.php';

        $local = __DIR__ . '/../Config/Correo.local.php';
        if (is_file($local)) {
            $override = require $local;
            if (is_array($override)) {
                $base = array_replace_recursive($base, $override);
            }
        }

        // La clave puede venir por variable de entorno (no se versiona)
        $claveEntorno = $_ENV['PLENAMENTE_SMTP_CLAVE'] ?? getenv('PLENAMENTE_SMTP_CLAVE');
        if (!empty($claveEntorno)) {
            $base['smtp']['clave'] = $claveEntorno;
        }

        // Si no hay remitente configurado se usa el dominio del servidor
        if (empty($base['remitente'])) {
            $dominio = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $base['remitente'] = 'no-reply@' . $dominio;
        }

        return $base;
    }

    /**
     * Envia un correo en formato HTML usando la configuracion vigente.
     *
     * @param string      $destinatario
     * @param string      $asunto
     * @param string      $mensajeHtml
     * @param array|null  $diagnostico  Recibe el detalle del envio (metodo, remitente, dialogo, errores)
     * @return bool       true solo si el transporte acepto el mensaje
     */
    public static function enviar(string $destinatario, string $asunto, string $mensajeHtml, &$diagnostico = null): bool {
        $diagnostico = self::diagnosticoInicial($destinatario);

        if (!self::validarDestinatario($destinatario, $diagnostico)) {
            return false;
        }

        $config = self::config();
        $remitente = $config['remitente'];
        $diagnostico['remitente'] = $remitente;

        $smtp = $config['smtp'] ?? [];
        $smtpListo = !empty($config['usar_smtp'])
            && !empty($smtp['host'])
            && !empty($smtp['usuario'])
            && !empty($smtp['clave']);

        if ($smtpListo) {
            if (self::enviarPorSmtp($smtp, $remitente, $destinatario, $asunto, $mensajeHtml, $diagnostico)) {
                return true;
            }

            Logger::error('Fallo al enviar por SMTP', [
                'destinatario' => $destinatario,
                'remitente' => $remitente,
                'servidor' => $smtp['host'] ?? '',
                'errores' => $diagnostico['errores']
            ]);

            // Se continua con mail() solo si esta habilitado
            if (empty($config['usar_mail'])) {
                return false;
            }
            $diagnostico['advertencia'] = 'El envio por SMTP fallo; se intento con mail() como alternativa.';
        }

        if (empty($config['usar_mail'])) {
            $diagnostico['errores'][] = 'No hay ningun metodo de envio habilitado (ni SMTP ni mail()).';
            return false;
        }

        return self::enviarPorMail($config, $remitente, $destinatario, $asunto, $mensajeHtml, $diagnostico);
    }

    /**
     * Envia un correo con parametros SMTP explicitos (usado por las pruebas y
     * por el diagnostico). No depende de la configuracion global.
     */
    public static function enviarParaPrueba(
        $host, $puerto, $cifrado, $usuario, $clave,
        $remitente, $destinatario, $asunto, $cuerpo,
        &$diagnostico = null
    ): bool {
        $diagnostico = self::diagnosticoInicial($destinatario);

        if (!self::validarDestinatario($destinatario, $diagnostico)) {
            return false;
        }

        $diagnostico['remitente'] = $remitente;

        $smtp = [
            'host'    => $host,
            'puerto'  => $puerto,
            'cifrado' => $cifrado,
            'usuario' => $usuario,
            'clave'   => $clave,
            'timeout' => 20
        ];

        return self::enviarPorSmtp($smtp, $remitente, $destinatario, $asunto, $cuerpo, $diagnostico);
    }

    // ---------------------------------------------------------------------
    // Metodos internos
    // ---------------------------------------------------------------------

    private static function diagnosticoInicial($destinatario) {
        return [
            'metodo'       => null,
            'remitente'    => null,
            'destinatario' => $destinatario,
            'dialogo'      => [],
            'errores'      => [],
            'advertencia'  => null
        ];
    }

    private static function validarDestinatario($destinatario, &$diagnostico) {
        $destinatario = trim((string)$destinatario);
        if ($destinatario === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            $diagnostico['errores'][] = 'La direccion de destino no es un correo valido.';
            return false;
        }
        $diagnostico['destinatario'] = $destinatario;
        return true;
    }

    private static function enviarPorSmtp(array $smtp, $remitente, $destinatario, $asunto, $mensajeHtml, &$diagnostico) {
        $cliente = new Smtp(
            $smtp['host'],
            $smtp['puerto'] ?? 587,
            $smtp['cifrado'] ?? 'tls',
            $smtp['usuario'],
            $smtp['clave'],
            $smtp['timeout'] ?? 20
        );

        $enviado = $cliente->enviar($remitente, $destinatario, $asunto, $mensajeHtml);

        $diagnostico['metodo'] = 'smtp';
        $diagnostico['dialogo'] = $cliente->obtenerDialogo();
        $diagnostico['errores'] = $cliente->obtenerErrores();

        if ($enviado) {
            Logger::info('Correo enviado por SMTP', [
                'destinatario' => $destinatario,
                'remitente' => $remitente,
                'servidor' => $smtp['host'] ?? ''
            ]);
        }

        return $enviado;
    }

    private static function enviarPorMail(array $config, $remitente, $destinatario, $asunto, $mensajeHtml, &$diagnostico) {
        $diagnostico['metodo'] = 'mail';

        $nombreRemitente = $config['nombre_remitente'] ?? 'PlenaMente';
        $cabeceras = [];
        $cabeceras[] = 'MIME-Version: 1.0';
        $cabeceras[] = 'Content-Type: text/html; charset=UTF-8';
        $cabeceras[] = 'Content-Transfer-Encoding: base64';
        $cabeceras[] = 'From: "' . str_replace(['"', "\r", "\n"], '', $nombreRemitente) . '" <' . $remitente . '>';
        $cabeceras[] = 'Reply-To: ' . $remitente;
        $cabeceras[] = 'Date: ' . date('r');
        $dominioRemitente = (strpos($remitente, '@') !== false)
            ? substr($remitente, strrpos($remitente, '@') + 1)
            : ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $cabeceras[] = 'Message-ID: <' . uniqid('plena', true) . '@' . $dominioRemitente . '>';
        $cabeceras[] = 'X-Mailer: PlenaMente INFRAVENZ';

        $cuerpo = chunk_split(base64_encode($mensajeHtml), 76, "\r\n");

        // Se capturan los avisos de PHP que mail() puede emitir (si no, quedan ocultos)
        $avisos = [];
        set_error_handler(function ($nivel, $mensaje) use (&$avisos) {
            $avisos[] = $mensaje;
            return true;
        });

        $enviado = @mail($destinatario, $asunto, $cuerpo, implode("\r\n", $cabeceras));

        restore_error_handler();

        if (!empty($avisos)) {
            $diagnostico['advertencia'] = implode(' | ', $avisos);
        }

        if (!$enviado) {
            $diagnostico['errores'][] = 'mail() devolvio false (el servidor local de correo no acepto el mensaje).';
            Logger::error('Fallo al enviar correo con mail()', [
                'destinatario' => $destinatario,
                'remitente' => $remitente,
                'avisos' => $avisos
            ]);
            return false;
        }

        Logger::info('Correo enviado con mail()', [
            'destinatario' => $destinatario,
            'remitente' => $remitente,
            'advertencia' => $diagnostico['advertencia']
        ]);

        return true;
    }
}
