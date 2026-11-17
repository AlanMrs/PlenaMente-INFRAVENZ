<?php
/**
 * Prueba end-to-end del envio de recordatorios (HU-16: entrega real del correo).
 *
 * Configura el SMTP del sistema contra un servidor simulado y comprueba que:
 *   - un envio aceptado por el servidor se registra como "Enviado",
 *   - un envio rechazado por el servidor se registra como "Fallido" y el mensaje
 *     explica el motivo (no se reporta como enviado lo que no se envio),
 *   - la pantalla de diagnostico muestra la configuracion y el dialogo SMTP.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_recordatorios.php
 */

$BASE_URL_TEST = 'http://127.0.0.1:8099/index.php';
if (isset($argv[1])) {
    $BASE_URL_TEST = rtrim($argv[1], '/') . '/index.php';
}
define('BASE_TEST', $BASE_URL_TEST);

chdir(__DIR__ . '/..');
require_once 'app/Config/Database.php';

$ok = 0;
$fail = 0;

function check($nombre, $condicion, $detalle = '') {
    global $ok, $fail;
    if ($condicion) { $ok++; echo "  [OK]    $nombre\n"; }
    else { $fail++; echo "  [FALLA] $nombre" . ($detalle !== '' ? " -> $detalle" : '') . "\n"; }
}

$db = (new App\Config\Database())->getConnection();
$configLocal = __DIR__ . '/../app/Config/Correo.local.php';
$puerto = 2525;

// ---------- Limpieza ----------
$db->exec("DELETE FROM recordatorios_citas WHERE id_cita IN (SELECT id_cita FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%'))");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'rec-test-%@local.test'");

$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'Recordatorios Psicologo', 'rec-test-psi@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);

/**
 * Escribe la configuracion local de correo apuntando al servidor SMTP simulado.
 */
function configurarSmtp($puerto, $clave = 'clave-prueba') {
    global $configLocal;
    $contenido = "<?php\nreturn [\n"
        . "    'remitente' => 'psicologia@dsoftin.net',\n"
        . "    'smtp' => [\n"
        . "        'host' => '127.0.0.1',\n"
        . "        'puerto' => " . (int)$puerto . ",\n"
        . "        'cifrado' => 'none',\n"
        . "        'usuario' => 'usuario-prueba',\n"
        . "        'clave' => '" . $clave . "'\n"
        . "    ]\n];\n";
    file_put_contents($configLocal, $contenido);
}

/**
 * Levanta el servidor SMTP simulado y devuelve el proceso.
 */
function levantarSmtp($puerto, $modo = 'ok') {
    $proceso = proc_open(
        [PHP_BINARY, __DIR__ . '/smtp_falso.php', (string)$puerto, 'clave-prueba', $modo],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    for ($i = 0; $i < 40; $i++) {
        usleep(100000);
        $conexion = @stream_socket_client("tcp://127.0.0.1:$puerto", $errno, $err, 1);
        if ($conexion) { fclose($conexion); break; }
    }
    usleep(300000);
    return [$proceso, $pipes];
}

function peticion($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['codigo' => $codigo, 'cuerpo' => (string)$cuerpo];
}

$jarPsi = sys_get_temp_dir() . '/cookies_rec_psi.txt';
@unlink($jarPsi);

try {
    echo "\n=== 1. Envio aceptado por el servidor ===\n";
    configurarSmtp($puerto);
    list($procesoSmtp, $pipes) = levantarSmtp($puerto);

    peticion(BASE_TEST . '?url=auth/procesar', $jarPsi, ['correo' => 'rec-test-psi@local.test', 'password' => 'Prueba123!']);

    $r = peticion(BASE_TEST . '?url=paciente/guardar', $jarPsi, [
        'nie_dui' => 'REC-TEST-001', 'nombres' => 'Recordatorio', 'apellidos' => 'Prueba',
        'fecha_nacimiento' => '2005-06-06', 'genero' => 'Femenino', 'tipo_paciente' => 'Estudiante',
        'grado_seccion' => '7° A', 'correo_paciente' => 'destino@correo.com'
    ]);
    check('El paciente de prueba se creo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
    $idPaciente = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = 'REC-TEST-001'")->fetchColumn();

    $r = peticion(BASE_TEST . '?url=cita/guardar', $jarPsi, [
        'id_paciente' => $idPaciente, 'fecha_cita' => date('Y-m-d', strtotime('+2 days')),
        'hora_cita' => '09:00', 'motivo' => 'Consulta de prueba REC-TEST'
    ]);
    check('La cita de prueba se creo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
    $idCita = (int)$db->query("SELECT id_cita FROM citas WHERE id_paciente = $idPaciente LIMIT 1")->fetchColumn();

    $r = peticion(BASE_TEST . '?url=recordatorio/enviar/' . $idCita, $jarPsi, ['confirmar' => '1']);
    check('El recordatorio se procesa', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

    $registro = $db->query("SELECT * FROM recordatorios_citas WHERE id_cita = $idCita ORDER BY id_recordatorio DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    check('El envio quedo registrado', !empty($registro));
    check('Se registra como Enviado (el servidor lo acepto)', ($registro['estado'] ?? '') === 'Enviado', $registro['estado'] ?? 'sin registro');
    check('Se guarda el destinatario', ($registro['destinatario'] ?? '') === 'destino@correo.com');

    $r = peticion(BASE_TEST . '?url=recordatorio', $jarPsi);
    check('El mensaje confirma el envio', strpos($r['cuerpo'], 'Recordatorio enviado a destino@correo.com') !== false);
    check('El mensaje indica el metodo usado', strpos($r['cuerpo'], 'método: smtp') !== false);

    // Cierra el SMTP simulado
    foreach ($pipes as $pipe) { @fclose($pipe); }
    @proc_terminate($procesoSmtp);
    @proc_close($procesoSmtp);

    echo "\n=== 2. Envio rechazado por el servidor ===\n";
    $puerto2 = 2526;
    configurarSmtp($puerto2);
    list($procesoSmtp2, $pipes2) = levantarSmtp($puerto2, 'rechazar_datos');

    $r = peticion(BASE_TEST . '?url=recordatorio/enviar/' . $idCita, $jarPsi, ['confirmar' => '1']);
    check('El recordatorio se procesa', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

    $registro = $db->query("SELECT * FROM recordatorios_citas WHERE id_cita = $idCita ORDER BY id_recordatorio DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    check('El envio rechazado se registra como Fallido (no como Enviado)', ($registro['estado'] ?? '') === 'Fallido', $registro['estado'] ?? 'sin registro');

    $r = peticion(BASE_TEST . '?url=recordatorio', $jarPsi);
    check('El mensaje explica el motivo del fallo', strpos($r['cuerpo'], 'No se pudo enviar el recordatorio') !== false);
    check('El mensaje remite al diagnostico', strpos($r['cuerpo'], 'Diagnóstico') !== false);

    foreach ($pipes2 as $pipe) { @fclose($pipe); }
    @proc_terminate($procesoSmtp2);
    @proc_close($procesoSmtp2);

    echo "\n=== 3. Pantalla de diagnostico ===\n";
    $r = peticion(BASE_TEST . '?url=recordatorio/diagnostico', $jarPsi);
    check('La pantalla de diagnostico responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
    check('Muestra la configuracion vigente', strpos($r['cuerpo'], 'Configuración vigente') !== false);
    check('Muestra el remitente', strpos($r['cuerpo'], 'psicologia@dsoftin.net') !== false);
    check('Muestra el servidor SMTP', strpos($r['cuerpo'], '127.0.0.1') !== false);
    check('Permite enviar un correo de prueba', strpos($r['cuerpo'], 'name="destinatario"') !== false);
    check('Enlaza desde la pantalla de recordatorios', strpos(peticion(BASE_TEST . '?url=recordatorio', $jarPsi)['cuerpo'], '/recordatorio/diagnostico') !== false);

    // Envio de prueba con diagnostico
    $puerto3 = 2527;
    configurarSmtp($puerto3);
    list($procesoSmtp3, $pipes3) = levantarSmtp($puerto3);
    $r = peticion(BASE_TEST . '?url=recordatorio/diagnostico', $jarPsi, ['destinatario' => 'prueba@correo.com']);
    check('La prueba de correo se ejecuta', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
    check('Muestra que el servidor acepto el mensaje', strpos($r['cuerpo'], 'El servidor aceptó el mensaje') !== false);
    check('Muestra el dialogo con el servidor', strpos($r['cuerpo'], 'Diálogo con el servidor de correo') !== false);
    foreach ($pipes3 as $pipe) { @fclose($pipe); }
    @proc_terminate($procesoSmtp3);
    @proc_close($procesoSmtp3);

    echo "\n=== 4. Paciente sin correo o con correo invalido ===\n";
    $db->prepare("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, correo_paciente, estado_paciente)
                  VALUES ('REC-TEST-002', 'Sin', 'Correo', '2004-01-01', 'Femenino', 'Estudiante', 'correo-invalido', 'Activo')")->execute();
    $idPaciente2 = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = 'REC-TEST-002'")->fetchColumn();
    peticion(BASE_TEST . '?url=cita/guardar', $jarPsi, [
        'id_paciente' => $idPaciente2, 'fecha_cita' => date('Y-m-d', strtotime('+3 days')),
        'hora_cita' => '10:00', 'motivo' => 'Cita con correo invalido'
    ]);
    $idCita2 = (int)$db->query("SELECT id_cita FROM citas WHERE id_paciente = $idPaciente2 LIMIT 1")->fetchColumn();

    $r = peticion(BASE_TEST . '?url=recordatorio/enviar/' . $idCita2, $jarPsi, ['confirmar' => '1']);
    check('Una cita con correo invalido no se envia', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
    $registro = $db->query("SELECT COUNT(*) AS total FROM recordatorios_citas WHERE id_cita = $idCita2")->fetch(PDO::FETCH_ASSOC);
    check('No se registra ningun envio para un destinatario invalido', (int)$registro['total'] === 0);

    $r = peticion(BASE_TEST . '?url=recordatorio', $jarPsi);
    check('El mensaje explica que el correo no es valido', strpos($r['cuerpo'], 'no es una dirección válida') !== false);

} finally {
    // Se restaura la configuracion de correo
    if (is_file($configLocal)) {
        unlink($configLocal);
    }

    $db->exec("DELETE FROM recordatorios_citas WHERE id_cita IN (SELECT id_cita FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%'))");
    $db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%')");
    $db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%')");
    $db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REC-TEST-%'");
    $db->exec("DELETE FROM usuarios WHERE correo LIKE 'rec-test-%@local.test'");
    @unlink($jarPsi);
}

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
