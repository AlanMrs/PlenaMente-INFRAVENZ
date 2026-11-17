<?php
/**
 * Prueba end-to-end de las sugerencias de interfaz:
 *   1) Terminologia "eliminar paciente" (antes "desactivar").
 *   2) Aviso de citas con fecha vencida en la consulta principal.
 *   3) El formulario de inicio de sesion no expone datos de usuarios.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_sugerencias.php
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
    if ($condicion) {
        $ok++;
        echo "  [OK]    $nombre\n";
    } else {
        $fail++;
        echo "  [FALLA] $nombre" . ($detalle !== '' ? " -> $detalle" : '') . "\n";
    }
}

$db = (new App\Config\Database())->getConnection();

$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%SUG-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'sug-test-%@local.test'");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%'");

$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'Sugerencias Psicologo', 'sug-test-psi@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$idPsicologo = (int)$db->query("SELECT id_usuario FROM usuarios WHERE correo = 'sug-test-psi@local.test'")->fetchColumn();

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
    $ubicacion = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['codigo' => $codigo, 'cuerpo' => (string)$cuerpo, 'ubicacion' => $ubicacion];
}

$jarPsi = sys_get_temp_dir() . '/cookies_sug_psi.txt';
@unlink($jarPsi);

echo "\n=== 1. Inicio de sesion sin datos de usuarios ===\n";
$r = peticion(BASE_TEST . '?url=auth/login', $jarPsi);
$html = $r['cuerpo'];
check('El formulario de login se muestra', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('NO expone un correo de administrador como ejemplo', stripos($html, 'admin@') === false);
check('NO expone ningun correo real de usuario', stripos($html, 'awmoris@gmail.com') === false && stripos($html, 'bmcruz@') === false);
check('El hint del correo es generico', strpos($html, 'placeholder="nombre.usuario@correo.com"') !== false);
check('El hint indica que se use el correo institucional', strpos($html, 'correo institucional con el que fue registrado') !== false);
check('El hint de la contrasena explica el criterio', strpos($html, 'mayúscula, minúscula, número y símbolo') !== false);
check('Incluye un aviso de acceso institucional', strpos($html, 'Acceso institucional') !== false);
check('Conserva el enlace de recuperacion de contrasena', strpos($html, '/auth/recuperar') !== false);

echo "\n=== 2. Proceso de eliminar paciente (terminologia) ===\n";
$r = peticion(BASE_TEST . '?url=auth/procesar', $jarPsi, ['correo' => 'sug-test-psi@local.test', 'password' => 'Prueba123!']);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=paciente/guardar', $jarPsi, [
    'nie_dui' => 'SUG-TEST-001', 'nombres' => 'Sugerencia', 'apellidos' => 'Paciente',
    'fecha_nacimiento' => '2005-06-06', 'genero' => 'Femenino', 'tipo_paciente' => 'Estudiante',
    'grado_seccion' => '7° A'
]);
check('El paciente de prueba se creo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$idPaciente = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = 'SUG-TEST-001'")->fetchColumn();

// Se le da un expediente para que la pantalla muestre la variante con validacion reforzada
$db->prepare("INSERT INTO expedientes (id_paciente, fecha_apertura, antecedentes_familiares, antecedentes_medicos, estado_caso)
              VALUES (:p, CURDATE(), 'Antecedentes de prueba', 'Antecedentes de prueba', 'Activo')")
   ->execute([':p' => $idPaciente]);

$r = peticion(BASE_TEST . '?url=paciente/eliminar/' . $idPaciente, $jarPsi);
$html = $r['cuerpo'];
check('La pantalla de eliminacion se muestra', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El titulo es "Eliminar Paciente"', strpos($html, 'Eliminar Paciente') !== false);
check('El proceso se denomina eliminacion, NO desactivacion', stripos($html, 'desactivaci') === false);
check('El texto menciona la eliminacion del paciente', stripos($html, 'eliminaci') !== false);
check('La casilla de confirmacion dice que sera eliminado', strpos($html, 'será <strong>eliminado</strong>') !== false);
check('Se aclara que el historial se conserva', strpos($html, 'historial clínico se conservará') !== false);

// Ejecucion de la eliminacion
$r = peticion(BASE_TEST . '?url=paciente/eliminar/' . $idPaciente, $jarPsi, [
    'confirmacion_nie' => 'SUG-TEST-001', 'aceptar' => '1'
]);
check('El paciente se elimino', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=paciente', $jarPsi);
check('El mensaje confirma la eliminacion (no la desactivacion)', strpos($r['cuerpo'], 'fue eliminado del directorio') !== false);
check('El mensaje NO usa la palabra desactivacion', stripos($r['cuerpo'], 'desactivad') === false);

$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'paciente' AND accion = 'ELIMINAR'
                    AND descripcion LIKE '%SUG-TEST-001%' ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('La auditoria registra la accion ELIMINAR', !empty($reg));
check('La descripcion auditada usa la palabra eliminacion', !empty($reg) && stripos($reg['descripcion'], 'desactiv') === false, $reg['descripcion'] ?? '');
check('La descripcion indica que el historial se conserva', !empty($reg) && strpos($reg['descripcion'], 'conservan') !== false);

// Listado: badge y conmutador
$r = peticion(BASE_TEST . '?url=paciente&inactivos=1', $jarPsi);
check('El conmutador dice "Mostrar eliminados"', strpos($r['cuerpo'], 'Mostrar eliminados') !== false || strpos($r['cuerpo'], 'Ocultar eliminados') !== false);
check('El badge del paciente eliminado dice "Eliminado"', strpos($r['cuerpo'], '>Eliminado<') !== false);
check('El listado NO usa el badge "Inactivo"', strpos($r['cuerpo'], '>Inactivo<') === false);

echo "\n=== 3. Aviso de citas con fecha vencida ===\n";
// Cita vencida: Programada con fecha y hora en el pasado
$db->prepare("INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita)
              VALUES (:p, :u, :f, 'Cita vencida de prueba', 'Programada')")
   ->execute([':p' => $idPaciente, ':u' => $idPsicologo, ':f' => date('Y-m-d H:i:s', strtotime('-2 days'))]);
$idCitaVencida = (int)$db->query("SELECT id_cita FROM citas WHERE id_paciente = $idPaciente LIMIT 1")->fetchColumn();

// Cita vigente: Programada con fecha futura
$db->prepare("INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita)
              VALUES (:p, :u, :f, 'Cita futura de prueba', 'Programada')")
   ->execute([':p' => $idPaciente, ':u' => $idPsicologo, ':f' => date('Y-m-d H:i:s', strtotime('+5 days'))]);

$r = peticion(BASE_TEST . '?url=cita&vista=todas', $jarPsi);
$html = $r['cuerpo'];
check('La consulta principal responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Muestra el aviso de citas vencidas', strpos($html, 'cita(s) con fecha vencida') !== false);
check('El aviso explica que se actualice el estado', strpos($html, 'Actualice su estado') !== false);
check('Marca la cita vencida con la etiqueta "Vencida"', strpos($html, 'Vencida') !== false);
check('Ofrece la accion de marcar inasistencia', strpos($html, '/cita/inasistencia/' . $idCitaVencida) !== false);
check('La cita vigente NO aparece como vencida', substr_count($html, 'Vencida') === 1, 'apariciones=' . substr_count($html, 'Vencida'));

// Detalle de la cita vencida
$r = peticion(BASE_TEST . '?url=cita/ver/' . $idCitaVencida, $jarPsi);
$html = $r['cuerpo'];
check('El detalle muestra el aviso de fecha vencida', strpos($html, 'Esta cita tiene la fecha vencida') !== false);
check('El detalle ofrece cancelar la cita', strpos($html, '/cita/cancelar/' . $idCitaVencida) !== false);
check('El detalle ofrece marcar inasistencia', strpos($html, '/cita/inasistencia/' . $idCitaVencida) !== false);

// Registrar la inasistencia
$r = peticion(BASE_TEST . '?url=cita/inasistencia/' . $idCitaVencida, $jarPsi, ['confirmar' => '1']);
check('La inasistencia se registra', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$estado = (string)$db->query("SELECT estado_cita FROM citas WHERE id_cita = $idCitaVencida")->fetchColumn();
check('La cita queda en estado Inasistencia', $estado === 'Inasistencia', $estado);

$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'cita' AND accion = 'ACTUALIZAR'
                    AND descripcion LIKE '%inasistencia%' ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('El cambio de estado queda auditado', !empty($reg));

// No se puede marcar dos veces
$r = peticion(BASE_TEST . '?url=cita/inasistencia/' . $idCitaVencida, $jarPsi, ['confirmar' => '1']);
check('Una cita ya actualizada no se puede marcar de nuevo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$estado = (string)$db->query("SELECT estado_cita FROM citas WHERE id_cita = $idCitaVencida")->fetchColumn();
check('El estado se mantiene Inasistencia', $estado === 'Inasistencia', $estado);

echo "\n=== 4. Limpieza ===\n";
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%SUG-TEST-%'");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'sug-test-%@local.test'");
check('Datos de prueba eliminados', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'SUG-TEST-%'")->fetchColumn() === 0);
@unlink($jarPsi);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
