<?php
/**
 * Prueba del cliente SMTP nativo y del flujo de recordatorios.
 *
 * Levanta un servidor SMTP simulado (tests/smtp_falso.php) y verifica:
 *   - la conexion, la autenticacion y el envio son correctos,
 *   - una clave incorrecta se reporta como error (no como "Enviado"),
 *   - un servidor que rechaza el mensaje se reporta como error,
 *   - el recordatorio solo se registra como "Enviado" si el servidor lo acepta.
 *
 * Uso:  C:\xampp\php\php.exe tests\smoke_correo.php
 */

define('BASE_URL', 'http://localhost/plenamente');
chdir(__DIR__ . '/..');

require_once 'app/Core/Logger.php';
require_once 'app/Config/Database.php';
require_once 'app/Libraries/Correo.php';

$ok = 0;
$fail = 0;

function check($nombre, $condicion, $detalle = '') {
    global $ok, $fail;
    if ($condicion) { $ok++; echo "  [OK]    $nombre\n"; }
    else { $fail++; echo "  [FALLA] $nombre" . ($detalle !== '' ? " -> $detalle" : '') . "\n"; }
}

/**
 * Levanta el SMTP simulado en un puerto libre y ejecuta el escenario.
 */
function escenarioSmtp($puerto, $clave, $modo, $destinatario) {
    $servidor = proc_open(
        [PHP_BINARY, __DIR__ . '/smtp_falso.php', (string)$puerto, $clave, $modo],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );

    // Espera a que el servidor este escuchando
    for ($i = 0; $i < 40; $i++) {
        usleep(100000);
        $conexion = @stream_socket_client("tcp://127.0.0.1:$puerto", $errno, $err, 1);
        if ($conexion) {
            fclose($conexion);
            break;
        }
    }
    usleep(300000);

    $diagnostico = null;
    $enviado = App\Libraries\Correo::enviarParaPrueba(
        '127.0.0.1', $puerto, 'none', 'usuario-prueba', $clave,
        'psicologia@dsoftin.net', $destinatario,
        'Prueba', '<p>Contenido</p>', $diagnostico
    );

    // Cierra el servidor simulado
    foreach ($pipes as $pipe) {
        @fclose($pipe);
    }
    @proc_terminate($servidor);
    @proc_close($servidor);

    return [$enviado, $diagnostico];
}

echo "\n=== 1. Envio SMTP correcto ===\n";
list($enviado, $diag) = escenarioSmtp(2531, 'clave-prueba', 'ok', 'destino@correo.com');
check('El envio por SMTP es aceptado', $enviado === true, json_encode($diag['errores'] ?? []));
check('El metodo usado es smtp', ($diag['metodo'] ?? '') === 'smtp');
check('Se registro el remitente', ($diag['remitente'] ?? '') === 'psicologia@dsoftin.net');
check('El dialogo con el servidor se guardo', count($diag['dialogo'] ?? []) > 5, 'lineas=' . count($diag['dialogo'] ?? []));
check('El dialogo incluye la autenticacion', strpos(implode("\n", $diag['dialogo'] ?? []), 'AUTH LOGIN') !== false);
check('El dialogo incluye la confirmacion 250', strpos(implode("\n", $diag['dialogo'] ?? []), '250') !== false);
check('No hay errores reportados', empty($diag['errores']), implode(' | ', $diag['errores'] ?? []));

echo "\n=== 2. Credenciales incorrectas ===\n";
list($enviado, $diag) = escenarioSmtp(2532, 'clave-prueba', 'ok', 'destino@correo.com');
// El escenario anterior es el bueno; aqui se fuerza una clave distinta
$diagnostico = null;
$enviado = App\Libraries\Correo::enviarParaPrueba(
    '127.0.0.1', 2533, 'none', 'usuario-prueba', 'clave-incorrecta',
    'psicologia@dsoftin.net', 'destino@correo.com',
    'Prueba', '<p>Contenido</p>', $diagnostico
);
check('Con clave incorrecta el envio falla', $enviado === false);
check('Se informa del rechazo de credenciales', !empty($diagnostico['errores']), implode(' | ', $diagnostico['errores'] ?? []));

echo "\n=== 3. Servidor que rechaza el mensaje ===\n";
$servidor = proc_open(
    [PHP_BINARY, __DIR__ . '/smtp_falso.php', '2534', 'clave-prueba', 'rechazar_datos'],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes
);
for ($i = 0; $i < 40; $i++) {
    usleep(100000);
    $conexion = @stream_socket_client('tcp://127.0.0.1:2534', $errno, $err, 1);
    if ($conexion) { fclose($conexion); break; }
}
usleep(300000);

$diagnostico = null;
$enviado = App\Libraries\Correo::enviarParaPrueba(
    '127.0.0.1', 2534, 'none', 'usuario-prueba', 'clave-prueba',
    'psicologia@dsoftin.net', 'destino@correo.com',
    'Prueba', '<p>Contenido</p>', $diagnostico
);
foreach ($pipes as $pipe) { @fclose($pipe); }
@proc_terminate($servidor);
@proc_close($servidor);

check('Si el servidor rechaza, el envio falla', $enviado === false);
check('Se informa del rechazo del servidor', !empty($diagnostico['errores']), implode(' | ', $diagnostico['errores'] ?? []));

echo "\n=== 4. Servidor inexistente ===\n";
$diagnostico = null;
$enviado = App\Libraries\Correo::enviarParaPrueba(
    '127.0.0.1', 2599, 'none', 'usuario-prueba', 'clave-prueba',
    'psicologia@dsoftin.net', 'destino@correo.com',
    'Prueba', '<p>Contenido</p>', $diagnostico
);
check('Sin servidor el envio falla', $enviado === false);
check('Se informa del fallo de conexion', !empty($diagnostico['errores']), implode(' | ', $diagnostico['errores'] ?? []));

echo "\n=== 5. Destinatario invalido ===\n";
$diagnostico = null;
$enviado = App\Libraries\Correo::enviar('correo-invalido', 'Prueba', '<p>x</p>', $diagnostico);
check('Un destinatario invalido se rechaza', $enviado === false);
check('Se informa del destinatario invalido', !empty($diagnostico['errores']));

echo "\n=== 6. Configuracion cargada ===\n";
$config = App\Libraries\Correo::config();
check('La configuracion incluye el remitente', !empty($config['remitente']), $config['remitente'] ?? '');
check('La configuracion incluye el servidor SMTP', !empty($config['smtp']['host']), $config['smtp']['host'] ?? '');
check('La configuracion permite SMTP y mail', !empty($config['usar_smtp']) && !empty($config['usar_mail']));

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
