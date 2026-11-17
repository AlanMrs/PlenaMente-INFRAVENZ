<?php
/**
 * Prueba end-to-end de la historia HU-11: exportar reportes a PDF.
 *
 * Verifica el flujo completo por HTTP: generacion del informe tecnico, descarga
 * del documento imprimible, guardado del informe y exportacion del reporte
 * guardado, ademas de los permisos por rol y de la presencia de todo el
 * contenido clinico relevante.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_reportes_pdf.php
 *
 * IMPORTANTE: crea y destruye datos de prueba con prefijo pdf-test-.
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

// ---------- Limpieza previa ----------
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%pdf-test-%'");
$db->exec("DELETE FROM reportes WHERE id_expediente IN (SELECT id_expediente FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%'))");
$db->exec("DELETE FROM sesiones_clinicas WHERE id_expediente IN (SELECT id_expediente FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%'))");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'pdf-test-%@local.test'");

// ---------- Datos de prueba ----------
$nie = 'PDF-TEST-001';
$db->prepare("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, grado_seccion, telefono_contacto, correo_paciente, nombre_responsable, estado_paciente)
              VALUES (:nie, 'María', 'Prueba PDF', '2010-03-15', 'Femenino', 'Estudiante', '8° A', '7000-1111', 'maria.pdf@test.local', 'Responsable de Prueba', 'Activo')")
   ->execute([':nie' => $nie]);
$idPaciente = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = '$nie'")->fetchColumn();

$db->prepare("INSERT INTO expedientes (id_paciente, fecha_apertura, antecedentes_familiares, antecedentes_medicos, estado_caso)
              VALUES (:p, CURDATE(), 'Antecedentes familiares de prueba', 'Antecedentes medicos de prueba', 'Seguimiento')")
   ->execute([':p' => $idPaciente]);
$idExpediente = (int)$db->query("SELECT id_expediente FROM expedientes WHERE id_paciente = $idPaciente")->fetchColumn();

$db->prepare("INSERT INTO sesiones_clinicas (id_expediente, tipo_consulta, observaciones_generales, intervencion_realizada, notas_evolucion, fecha_registro)
              VALUES (:e, 'Evaluación inicial', 'Observaciones clinicas de prueba con acentos: áéíóúñ', 'Intervencion aplicada de prueba', 'Notas de evolucion de prueba', NOW())")
   ->execute([':e' => $idExpediente]);
$db->prepare("INSERT INTO sesiones_clinicas (id_expediente, tipo_consulta, observaciones_generales, intervencion_realizada, notas_evolucion, fecha_registro)
              VALUES (:e, 'Seguimiento', 'Segunda sesion registrada', 'Reforzo de estrategias', 'Continuar seguimiento', NOW())")
   ->execute([':e' => $idExpediente]);

$idPsicologo = (int)$db->query("SELECT id_usuario FROM usuarios WHERE id_rol = 1 AND estado = 'Activo' LIMIT 1")->fetchColumn();
$db->prepare("INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita)
              VALUES (:p, :u, DATE_ADD(NOW(), INTERVAL 2 DAY), 'Cita de prueba PDF', 'Programada')")
   ->execute([':p' => $idPaciente, ':u' => $idPsicologo]);

$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'PDF Psicologo', 'pdf-test-psi@local.test', :pass, 'Activo')")
   ->execute([':pass' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (2, 'PDF Director', 'pdf-test-dir@local.test', :pass, 'Activo')")
   ->execute([':pass' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);

// ---------- Peticiones HTTP ----------
function peticion($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 25,
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

$jarPsi = sys_get_temp_dir() . '/cookies_pdf_psi.txt';
$jarDir = sys_get_temp_dir() . '/cookies_pdf_dir.txt';
@unlink($jarPsi);
@unlink($jarDir);

echo "\n=== 1. Hoja de estilos de impresion ===\n";
$r = peticion(str_replace('/index.php', '', BASE_TEST) . '/css/reporte-impresion.css', $jarPsi);
$css = $r['cuerpo'];
check('La hoja de estilos se sirve correctamente', $r['codigo'] === 200 && stripos($css, '@media print') !== false, 'codigo=' . $r['codigo']);
check('Define el tamano de pagina (@page)', strpos($css, '@page') !== false);
check('Oculta la barra de herramientas al imprimir', strpos($css, '.doc-toolbar') !== false);
check('Evita cortes de pagina en tablas y firmas', strpos($css, 'break-inside') !== false);

echo "\n=== 2. Informe tecnico en pantalla ===\n";
$r = iniciarSesion('pdf-test-psi@local.test', $jarPsi);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=reportes/tecnico/' . $idExpediente, $jarPsi);
check('Accede al informe tecnico', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Muestra el boton "Descargar PDF"', strpos($r['cuerpo'], 'Descargar PDF') !== false);
check('El boton apunta a la ruta de impresion', strpos($r['cuerpo'], 'reportes/imprimir/' . $idExpediente) !== false);
check('La pantalla incluye la hoja de estilos de impresion', strpos($r['cuerpo'], 'reporte-impresion.css') !== false);

echo "\n=== 3. Documento imprimible del informe tecnico ===\n";
$r = peticion(BASE_TEST . '?url=reportes/imprimir/' . $idExpediente, $jarPsi);
$cuerpo = $r['cuerpo'];
check('La ruta de impresion responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Es un documento autonomo (sin menu lateral)', strpos($cuerpo, 'menu-item') === false);
check('Incluye la hoja de estilos de impresion', strpos($cuerpo, 'reporte-impresion.css') !== false);
check('Ofrece la accion de imprimir/guardar PDF', strpos($cuerpo, 'window.print()') !== false);
check('Incluye la identidad del paciente', strpos($cuerpo, 'María') !== false && strpos($cuerpo, 'Prueba PDF') !== false);
check('Incluye el NIE/DUI', strpos($cuerpo, 'PDF-TEST-001') !== false);
check('Incluye la edad calculada', strpos($cuerpo, '15 años') !== false || strpos($cuerpo, '16 años') !== false);
check('Incluye el correo del paciente', strpos($cuerpo, 'maria.pdf@test.local') !== false);
check('Incluye el responsable', strpos($cuerpo, 'Responsable de Prueba') !== false);
check('Incluye los antecedentes medicos', strpos($cuerpo, 'Antecedentes medicos de prueba') !== false);
check('Incluye los antecedentes familiares', strpos($cuerpo, 'Antecedentes familiares de prueba') !== false);
check('Incluye el estado del caso', strpos($cuerpo, 'Seguimiento') !== false);
check('Incluye el resumen de atencion', strpos($cuerpo, 'Total de sesiones realizadas') !== false);
check('Incluye el total de citas', strpos($cuerpo, 'Total de citas registradas') !== false);
check('Incluye la proxima cita', strpos($cuerpo, 'Próxima cita programada') !== false);
check('Incluye las observaciones clinicas', strpos($cuerpo, 'Segunda sesion registrada') !== false);
check('Incluye la intervencion realizada', strpos($cuerpo, 'Reforzo de estrategias') !== false);
check('Incluye las notas de evolucion', strpos($cuerpo, 'Continuar seguimiento') !== false);
check('Incluye el historial de sesiones', strpos($cuerpo, 'Historial de sesiones') !== false && strpos($cuerpo, 'Evaluación inicial') !== false);
check('Incluye las 7 secciones del documento', substr_count($cuerpo, 'class="doc-seccion"') === 7, 'secciones=' . substr_count($cuerpo, 'class="doc-seccion"'));
check('Incluye el nombre del autor del informe', strpos($cuerpo, 'PDF Psicologo') !== false);
check('Incluye el aviso de confidencialidad', strpos($cuerpo, 'confidencial') !== false);

echo "\n=== 4. Guardar el informe y exportarlo ===\n";
$r = peticion(BASE_TEST . '?url=reportes/guardar', $jarPsi, [
    'id_expediente' => $idExpediente,
    'nivel_urgencia' => 'Medio',
    'observaciones_generales' => 'Observaciones clinicas de prueba con acentos: áéíóúñ',
    'intervencion_realizada' => 'Intervencion aplicada de prueba',
    'notas_evolucion' => 'Notas de evolucion de prueba'
]);
check('El informe se guarda correctamente', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$idReporte = (int)$db->query("SELECT id_reporte FROM reportes WHERE id_expediente = $idExpediente ORDER BY id_reporte DESC LIMIT 1")->fetchColumn();
check('El reporte quedo persistido', $idReporte > 0, "id=$idReporte");

$r = peticion(BASE_TEST . '?url=reportes/ver/' . $idReporte, $jarPsi);
check('Accede al reporte guardado', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El reporte guardado muestra el boton "Descargar PDF"', strpos($r['cuerpo'], 'Descargar PDF') !== false);
check('El boton apunta a la ruta del reporte guardado', strpos($r['cuerpo'], 'reportes/imprimir_reporte/' . $idReporte) !== false);
check('Muestra el nivel de urgencia', strpos($r['cuerpo'], 'Medio') !== false);

$r = peticion(BASE_TEST . '?url=reportes/imprimir_reporte/' . $idReporte, $jarPsi);
$cuerpo = $r['cuerpo'];
check('La ruta del reporte guardado responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Muestra el numero de reporte', strpos($cuerpo, '#' . str_pad($idReporte, 4, '0', STR_PAD_LEFT)) !== false);
check('Muestra el nivel de urgencia', strpos($cuerpo, 'Nivel de urgencia') !== false);
check('Descompone las observaciones', strpos($cuerpo, 'Observaciones clinicas de prueba') !== false);
check('Descompone la intervencion', strpos($cuerpo, 'Intervencion aplicada de prueba') !== false);
check('Descompone las notas de evolucion', strpos($cuerpo, 'Notas de evolucion de prueba') !== false);
check('Muestra el autor del reporte', strpos($cuerpo, 'PDF Psicologo') !== false);

echo "\n=== 5. Permisos por rol ===\n";
$r = iniciarSesion('pdf-test-dir@local.test', $jarDir);
check('Login del director aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=reportes/tecnico/' . $idExpediente, $jarDir);
check('El director NO puede generar el informe tecnico', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=reportes/imprimir/' . $idExpediente, $jarDir);
check('El director NO puede imprimir el informe tecnico (mismo permiso)', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=reportes/imprimir_reporte/' . $idReporte, $jarDir);
check('El director SI puede exportar un reporte ya guardado', $r['codigo'] === 200, 'codigo=' . $r['codigo']);

echo "\n=== 6. Limpieza ===\n";
$db->exec("DELETE FROM reportes WHERE id_expediente = $idExpediente");
$db->exec("DELETE FROM sesiones_clinicas WHERE id_expediente = $idExpediente");
$db->exec("DELETE FROM citas WHERE id_paciente = $idPaciente");
$db->exec("DELETE FROM expedientes WHERE id_expediente = $idExpediente");
$db->exec("DELETE FROM pacientes WHERE id_paciente = $idPaciente");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'pdf-test-%@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%pdf-test-%'");
check('Datos de prueba eliminados', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'PDF-TEST-%'")->fetchColumn() === 0);
check('Usuarios de prueba eliminados', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'pdf-test-%@local.test'")->fetchColumn() === 0);
@unlink($jarPsi);
@unlink($jarDir);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
