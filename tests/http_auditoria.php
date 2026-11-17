<?php
/**
 * Prueba end-to-end de la historia HU-15: auditar acciones del sistema.
 *
 * Verifica que las acciones criticas quedan registradas, que el registro se
 * puede consultar por usuario y por fecha, y que cada entrada muestra sus
 * detalles de forma clara.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_auditoria.php
 *
 * IMPORTANTE: crea y destruye datos de prueba con prefijo aud-test-.
 * Nunca ejecutar contra la base de datos de produccion.
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

// Fecha de referencia tomada de la base de datos al inicio de la prueba.
// Se fija aqui para que un cambio de reloj durante la ejecucion no invalide el filtro por fecha.
$fechaReferencia = (string)$db->query("SELECT CURDATE()")->fetchColumn();

// ---------- Limpieza previa ----------
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%AUD-TEST-%' OR descripcion LIKE '%aud-test-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'aud-test-%@local.test'");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%'");

// ---------- Usuarios de prueba ----------
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'Auditoria Psicologo', 'aud-test-psi@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$idPsicologo = (int)$db->query("SELECT id_usuario FROM usuarios WHERE correo = 'aud-test-psi@local.test'")->fetchColumn();

$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (3, 'Auditoria Admin', 'aud-test-admin@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$idAdmin = (int)$db->query("SELECT id_usuario FROM usuarios WHERE correo = 'aud-test-admin@local.test'")->fetchColumn();

// ---------- Peticiones HTTP ----------
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

function iniciarSesion($correo, $jar) {
    return peticion(BASE_TEST . '?url=auth/procesar', $jar, ['correo' => $correo, 'password' => 'Prueba123!']);
}

$jarPsi = sys_get_temp_dir() . '/cookies_aud_psi.txt';
$jarAdmin = sys_get_temp_dir() . '/cookies_aud_admin.txt';
@unlink($jarPsi);
@unlink($jarAdmin);

echo "\n=== 1. Registro de acciones criticas ===\n";
$r = iniciarSesion('aud-test-psi@local.test', $jarPsi);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

// CREAR paciente
$r = peticion(BASE_TEST . '?url=paciente/guardar', $jarPsi, [
    'nie_dui' => 'AUD-TEST-001', 'nombres' => 'Auditoria', 'apellidos' => 'Prueba',
    'fecha_nacimiento' => '2005-06-06', 'genero' => 'Femenino', 'tipo_paciente' => 'Estudiante',
    'grado_seccion' => '7° A', 'telefono_contacto' => '7000-0000',
    'correo_paciente' => 'aud.test@local.test', 'nombre_responsable' => 'Responsable'
]);
check('El paciente de prueba se creo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$idPaciente = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = 'AUD-TEST-001'")->fetchColumn();

$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'paciente' AND accion = 'CREAR'
                    AND descripcion LIKE '%AUD-TEST-001%' ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la CREACION del paciente', !empty($reg));
check('El registro guarda el usuario responsable', !empty($reg) && (int)$reg['id_usuario'] === $idPsicologo);
check('El registro describe la accion', !empty($reg) && strpos($reg['descripcion'], 'AUD-TEST-001') !== false, $reg['descripcion'] ?? '');
check('El registro tiene fecha', !empty($reg) && !empty($reg['fecha']));

// ACTUALIZAR paciente
$r = peticion(BASE_TEST . '?url=paciente/actualizar/' . $idPaciente, $jarPsi, [
    'nie_dui' => 'AUD-TEST-001', 'nombres' => 'Auditoria', 'apellidos' => 'Modificada',
    'fecha_nacimiento' => '2005-06-06', 'genero' => 'Femenino', 'tipo_paciente' => 'Estudiante',
    'grado_seccion' => '7° A', 'telefono_contacto' => '7000-0000',
    'correo_paciente' => 'aud.test@local.test', 'nombre_responsable' => 'Responsable'
]);
check('El paciente se actualizo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'paciente' AND accion = 'ACTUALIZAR'
                    AND descripcion LIKE '%AUD-TEST-001%' ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la ACTUALIZACION del paciente', !empty($reg));
check('La descripcion detalla los campos modificados', !empty($reg) && strpos($reg['descripcion'], 'apellidos') !== false, $reg['descripcion'] ?? '');

// CREAR cita
$r = peticion(BASE_TEST . '?url=cita/guardar', $jarPsi, [
    'id_paciente' => $idPaciente, 'fecha_cita' => date('Y-m-d', strtotime('+5 days')),
    'hora_cita' => '09:30', 'motivo' => 'Evaluacion de prueba AUD-TEST-001'
]);
check('La cita se creo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$idCita = (int)$db->query("SELECT id_cita FROM citas WHERE id_paciente = $idPaciente LIMIT 1")->fetchColumn();
$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'cita' AND accion = 'CREAR'
                    ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la CREACION de la cita', !empty($reg));
check('La cita queda identificada en el registro', !empty($reg) && (int)$reg['id_entidad'] === $idCita);

// ACTUALIZAR cita (reprogramar)
$r = peticion(BASE_TEST . '?url=cita/editar/' . $idCita, $jarPsi, [
    'fecha_cita' => date('Y-m-d', strtotime('+6 days')), 'hora_cita' => '11:00'
]);
check('La cita se reprogramo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'cita' AND accion = 'ACTUALIZAR'
                    ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la REPROGRAMACION de la cita', !empty($reg));
check('La descripcion muestra el horario anterior y el nuevo',
    !empty($reg) && strpos($reg['descripcion'], 'de ') !== false && strpos($reg['descripcion'], 'a ') !== false, $reg['descripcion'] ?? '');

// ELIMINAR cita (cancelar)
$r = peticion(BASE_TEST . '?url=cita/cancelar/' . $idCita, $jarPsi, [
    'motivo_cancelacion' => 'Motivo de prueba para la auditoria AUD-TEST-001'
]);
check('La cita se cancelo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'cita' AND accion = 'ELIMINAR'
                    ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la CANCELACION de la cita', !empty($reg));
check('La descripcion incluye el motivo de cancelacion',
    !empty($reg) && strpos($reg['descripcion'], 'Motivo de prueba') !== false, $reg['descripcion'] ?? '');

// ELIMINAR paciente
$r = peticion(BASE_TEST . '?url=paciente/eliminar/' . $idPaciente, $jarPsi, [
    'confirmacion_nie' => 'AUD-TEST-001', 'aceptar' => '1'
]);
check('El paciente se elimino', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$reg = $db->query("SELECT * FROM auditoria_acciones WHERE entidad = 'paciente' AND accion = 'ELIMINAR'
                    AND descripcion LIKE '%AUD-TEST-001%' ORDER BY id_auditoria DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
check('Se registro la ELIMINACION del paciente', !empty($reg));
check('La descripcion indica que el historial se conserva',
    !empty($reg) && strpos($reg['descripcion'], 'conservan') !== false, $reg['descripcion'] ?? '');

echo "\n=== 2. Consulta del registro por usuario y fecha ===\n";
$r = iniciarSesion('aud-test-admin@local.test', $jarAdmin);
check('Login del administrador aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=auditoria', $jarAdmin);
$html = $r['cuerpo'];
check('El administrador accede al registro', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('La pagina muestra el titulo', strpos($html, 'Registro de Auditoría') !== false);
check('Muestra las columnas de la tabla', strpos($html, '<th>Usuario</th>') !== false && strpos($html, '<th>Acción</th>') !== false && strpos($html, '<th>Descripción</th>') !== false);
check('Muestra el filtro por usuario', strpos($html, 'name="usuario"') !== false);
check('Muestra el filtro por accion', strpos($html, 'name="accion"') !== false);
check('Muestra el filtro por entidad', strpos($html, 'name="entidad"') !== false);
check('Muestra el filtro por rango de fechas', strpos($html, 'name="desde"') !== false && strpos($html, 'name="hasta"') !== false);
check('Muestra el buscador de texto', strpos($html, 'name="buscar"') !== false);
check('Muestra las acciones registradas en la tabla', strpos($html, 'Auditoria Prueba') !== false || strpos($html, 'AUD-TEST-001') !== false);
check('Muestra el resumen estadistico', strpos($html, 'Acciones registradas') !== false);
check('El menu incluye Auditoria', strpos($html, '📋 Auditoría') !== false);

// Filtro por usuario
$r = peticion(BASE_TEST . '?url=auditoria&usuario=' . $idPsicologo, $jarAdmin);
check('El filtro por usuario responde 200', $r['codigo'] === 200);
check('El filtro por usuario muestra el nombre', strpos($r['cuerpo'], 'Auditoria Psicologo') !== false);
// Se revisa solo el cuerpo de la tabla (el nombre del usuario en sesion aparece en el encabezado)
preg_match('/<tbody>(.*?)<\/tbody>/s', $r['cuerpo'], $cuerpoTabla);
$filas = $cuerpoTabla[1] ?? '';
check('El filtro por usuario excluye a otros usuarios en la tabla', strpos($filas, 'Auditoria Admin') === false);
check('El filtro por usuario deja filas del usuario filtrado', strpos($filas, 'Auditoria Psicologo') !== false);

// Filtro por fecha
$hoy = $fechaReferencia;
$r = peticion(BASE_TEST . '?url=auditoria&desde=' . $hoy . '&hasta=' . $hoy, $jarAdmin);
check('El filtro por fecha de hoy responde 200', $r['codigo'] === 200);
check('El filtro por fecha de hoy devuelve registros', strpos($r['cuerpo'], 'Auditoria Prueba') !== false);

// Fecha claramente futura: no debe devolver registros
$futuro = date('Y-m-d', strtotime($fechaReferencia . ' +1 year'));
$r = peticion(BASE_TEST . '?url=auditoria&desde=' . $futuro . '&hasta=' . $futuro, $jarAdmin);
check('El filtro por fecha futura no devuelve registros', strpos($r['cuerpo'], 'No hay registros de auditoría') !== false);

// Filtro por acción
$r = peticion(BASE_TEST . '?url=auditoria&accion=ELIMINAR', $jarAdmin);
check('El filtro por accion responde 200', $r['codigo'] === 200);
check('El filtro por accion ELIMINAR devuelve registros', strpos($r['cuerpo'], 'ELIMINAR') !== false);

// Filtro por entidad
$r = peticion(BASE_TEST . '?url=auditoria&entidad=cita', $jarAdmin);
check('El filtro por entidad responde 200', $r['codigo'] === 200);
check('El filtro por entidad cita devuelve registros', strpos($r['cuerpo'], 'cita') !== false);

// Búsqueda por texto
$r = peticion(BASE_TEST . '?url=auditoria&buscar=' . urlencode('AUD-TEST-001'), $jarAdmin);
check('La busqueda por texto responde 200', $r['codigo'] === 200);
check('La busqueda por texto encuentra el registro', strpos($r['cuerpo'], 'AUD-TEST-001') !== false);

// Combinación de filtros
$r = peticion(BASE_TEST . '?url=auditoria&usuario=' . $idPsicologo . '&accion=CREAR&entidad=cita&desde=' . $hoy, $jarAdmin);
check('Los filtros se combinan', $r['codigo'] === 200);
check('La combinacion devuelve el registro de la cita', strpos($r['cuerpo'], 'cita') !== false);

echo "\n=== 3. Detalle de cada accion ===\n";
$idAuditoria = (int)$db->query("SELECT id_auditoria FROM auditoria_acciones WHERE entidad = 'cita' AND accion = 'ELIMINAR'
                                ORDER BY id_auditoria DESC LIMIT 1")->fetchColumn();
$r = peticion(BASE_TEST . '?url=auditoria/ver/' . $idAuditoria, $jarAdmin);
$html = $r['cuerpo'];
check('El detalle responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Muestra la fecha y hora del registro', strpos($html, 'Fecha y hora') !== false);
check('Muestra el usuario responsable', strpos($html, 'Auditoria Psicologo') !== false);
check('Muestra el tipo de accion', strpos($html, 'ELIMINAR') !== false);
check('Muestra la entidad afectada', strpos($html, 'cita') !== false);
check('Muestra la descripcion completa', strpos($html, 'Motivo de prueba') !== false);
check('Indica que es de solo lectura', strpos($html, 'solo lectura') !== false);

$r = peticion(BASE_TEST . '?url=auditoria/ver/99999999', $jarAdmin);
check('Un registro inexistente redirige', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

echo "\n=== 4. Permisos ===\n";
$r = peticion(BASE_TEST . '?url=auditoria', $jarPsi);
check('El psicologo NO accede al registro', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

$r = peticion(BASE_TEST . '?url=auditoria/ver/' . $idAuditoria, $jarPsi);
check('El psicologo NO accede al detalle', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=dashboard', $jarPsi);
check('El psicologo no ve el enlace de auditoria', strpos($r['cuerpo'], '📋 Auditoría') === false);

echo "\n=== 5. Limpieza ===\n";
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%AUD-TEST-%'");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'aud-test-%@local.test'");
check('Se eliminaron los registros de prueba', (int)$db->query("SELECT COUNT(*) FROM auditoria_acciones WHERE descripcion LIKE '%AUD-TEST-%'")->fetchColumn() === 0);
check('Se eliminaron los pacientes de prueba', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'AUD-TEST-%'")->fetchColumn() === 0);
check('Se eliminaron los usuarios de prueba', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'aud-test-%@local.test'")->fetchColumn() === 0);
@unlink($jarPsi);
@unlink($jarAdmin);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
