<?php
/**
 * Servidor SMTP simulado para probar el cliente nativo (app/Libraries/Smtp.php).
 *
 * Escucha en 127.0.0.1 y responde como un servidor SMTP real:
 *   - EHLO -> 250
 *   - AUTH LOGIN -> 334 / 235 (o 535 si la clave no es la esperada)
 *   - MAIL FROM / RCPT TO -> 250
 *   - DATA -> 354 -> 250
 *   - QUIT -> 221
 *
 * Modo "rechazar_datos": responde 550 al DATA (simula un servidor que
 * descarta el mensaje).
 *
 * Acepta varias conexiones seguidas (la primera suele ser una sonda de
 * disponibilidad).
 *
 * Uso: php tests/smtp_falso.php [puerto] [clave_esperada] [modo]
 */

$host = '127.0.0.1';
$puerto = (int)($argv[1] ?? 2525);
$claveEsperada = $argv[2] ?? 'clave-prueba';
$modo = $argv[3] ?? 'ok';

$servidor = stream_socket_server("tcp://$host:$puerto", $errno, $errorInfo);
if (!$servidor) {
    fwrite(STDERR, "No se pudo abrir el puerto $puerto: $errorInfo\n");
    exit(1);
}

echo "SMTP falso escuchando en $host:$puerto (modo=$modo)\n";

function responder($conexion, $texto) {
    fwrite($conexion, $texto . "\r\n");
    echo "S: " . trim($texto) . "\n";
}

function manejarConexion($conexion, $claveEsperada, $modo) {
    responder($conexion, '220 localhost ESMTP PlenaMente Test');

    while (($linea = fgets($conexion, 1024)) !== false) {
        $linea = trim($linea);
        echo "C: $linea\n";

        if ($linea === '') {
            break;
        }

        if (stripos($linea, 'EHLO') === 0) {
            responder($conexion, '250-localhost');
            responder($conexion, '250-AUTH LOGIN PLAIN');
            responder($conexion, '250 HELP');
        } elseif (stripos($linea, 'AUTH LOGIN') === 0) {
            responder($conexion, '334 VXNlcm5hbWU6');
            $usuario = trim((string)fgets($conexion, 1024));
            echo "C: " . base64_decode($usuario) . "\n";
            responder($conexion, '334 UGFzc3dvcmQ6');
            $clave = trim((string)fgets($conexion, 1024));
            $clavePlana = base64_decode($clave);
            echo "C: (clave de " . strlen($clavePlana) . " caracteres)\n";
            if ($clavePlana === $claveEsperada) {
                responder($conexion, '235 2.7.0 Authentication successful');
            } else {
                responder($conexion, '535 5.7.8 Authentication credentials invalid');
            }
        } elseif (stripos($linea, 'MAIL FROM') === 0) {
            responder($conexion, '250 2.1.0 Ok');
        } elseif (stripos($linea, 'RCPT TO') === 0) {
            responder($conexion, '250 2.1.5 Ok');
        } elseif (stripos($linea, 'DATA') === 0) {
            responder($conexion, '354 End data with <CR><LF>.<CR><LF>');
            $cuerpo = '';
            while (($linea = fgets($conexion, 1024)) !== false) {
                if (trim($linea) === '.') { break; }
                $cuerpo .= $linea;
            }
            echo "C: (cuerpo recibido: " . strlen($cuerpo) . " bytes)\n";
            if ($modo === 'rechazar_datos') {
                responder($conexion, '550 5.7.1 Message rejected by policy');
            } else {
                responder($conexion, '250 2.0.0 Ok: queued as PLAIN12345');
            }
        } elseif (stripos($linea, 'QUIT') === 0) {
            responder($conexion, '221 2.0.0 Bye');
            break;
        } elseif (stripos($linea, 'STARTTLS') === 0) {
            responder($conexion, '220 2.0.0 Ready to start TLS');
        } else {
            responder($conexion, '250 2.0.0 Ok');
        }
    }
}

while (true) {
    $conexion = stream_socket_accept($servidor, 120);
    if (!$conexion) {
        break;
    }
    manejarConexion($conexion, $claveEsperada, $modo);
    fclose($conexion);
    echo "-- conexion cerrada --\n";
}

fclose($servidor);
echo "SMTP falso terminado\n";
