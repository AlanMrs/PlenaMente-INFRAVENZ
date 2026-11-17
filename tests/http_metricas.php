<?php
/**
 * Prueba end-to-end de la historia HU-12: visualizar metricas (analisis general).
 *
 * Verifica el panel por HTTP con un Director y un Psicologo, y contrasta las
 * cifras mostradas contra los valores calculados directamente en la base de
 * datos (KPIs, series, distribuciones y graficos SVG).
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_metricas.php
 *
 * IMPORTANTE: crea y destruye datos de prueba con prefijo met-test-.
 * Nunca ejecutar contra la base de datos de produccion.
 */

$BASE_URL_TEST = 'http://127.0.0.1:8099/index.php';
if (isset($argv[1])) {
    $BASE_URL_TEST = rtrim($argv[1], '/') . '/index.php';
}
define('BASE_TEST', $BASE_URL_TEST);

chdir(__DIR__ . '/..');
require_once 'app/Config/Database.php';
require_once 'app/Models/Dashboard.php';
require_once 'app/Libraries/Graficos.php';

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
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%met-test-%'");
$db->exec("DELETE FROM reportes WHERE id_expediente IN (SELECT id_expediente FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%'))");
$db->exec("DELETE FROM sesiones_clinicas WHERE id_expediente IN (SELECT id_expediente FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%'))");
$db->exec("DELETE FROM citas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%')");
$db->exec("DELETE FROM expedientes WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%')");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%'");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'met-test-%@local.test'");

// ---------- Datos de prueba ----------
$idPsicologo = (int)$db->query("SELECT id_usuario FROM usuarios WHERE id_rol = 1 AND estado = 'Activo' LIMIT 1")->fetchColumn();

// 3 pacientes: 2 estudiantes + 1 empleado, con expedientes y citas/sesiones
$pacientes = [
    ['MET-TEST-001', 'Ana', 'Metrica', 'Estudiante', '8° A'],
    ['MET-TEST-002', 'Luis', 'Metrica', 'Estudiante', '9° B'],
    ['MET-TEST-003', 'Elena', 'Metrica', 'Empleado', null]
];
$idsPacientes = [];
foreach ($pacientes as $p) {
    $db->prepare("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, grado_seccion, estado_paciente)
                  VALUES (:nie, :n, :a, '2005-04-10', 'Femenino', :t, :g, 'Activo')")
       ->execute([':nie' => $p[0], ':n' => $p[1], ':a' => $p[2], ':t' => $p[3], ':g' => $p[4]]);
    $idsPacientes[] = (int)$db->query("SELECT id_paciente FROM pacientes WHERE nie_dui = '" . $p[0] . "'")->fetchColumn();
}

$estadosCaso = ['Activo', 'Seguimiento', 'Cerrado'];
$idsExpedientes = [];
foreach ($idsPacientes as $idx => $idPaciente) {
    $db->prepare("INSERT INTO expedientes (id_paciente, fecha_apertura, estado_caso) VALUES (:p, :f, :e)")
       ->execute([':p' => $idPaciente, ':f' => date('Y-m-d', strtotime('-' . ($idx + 1) . ' months')), ':e' => $estadosCaso[$idx]]);
    $idsExpedientes[] = (int)$db->query("SELECT id_expediente FROM expedientes WHERE id_paciente = $idPaciente")->fetchColumn();
}

// Citas: 4 en distintos estados + 1 inasistencia
$estadosCita = ['Completada', 'Completada', 'Programada', 'Cancelada', 'Inasistencia'];
foreach ($idsPacientes as $i => $idPaciente) {
    foreach (['Completada', 'Cancelada'] as $extra) {
        $db->prepare("INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita)
                      VALUES (:p, :u, :f, 'Cita metricas', :e)")
           ->execute([':p' => $idPaciente, ':u' => $idPsicologo, ':f' => date('Y-m-d H:i:s', strtotime('-' . ($i + 1) . ' days')), ':e' => $extra]);
    }
}
$db->prepare("INSERT INTO citas (id_paciente, id_usuario_psicologo, fecha_hora, motivo_cita, estado_cita)
              VALUES (:p, :u, :f, 'Cita metricas', :e)")
   ->execute([':p' => $idsPacientes[0], ':u' => $idPsicologo, ':f' => date('Y-m-d H:i:s', strtotime('-2 days')), ':e' => 'Completada']);

// Sesiones clinicas
for ($i = 0; $i < 3; $i++) {
    $db->prepare("INSERT INTO sesiones_clinicas (id_expediente, tipo_consulta, observaciones_generales, intervencion_realizada, notas_evolucion, fecha_registro)
                  VALUES (:e, :t, 'Obs metricas', 'Intervencion metricas', 'Notas metricas', :f)")
       ->execute([':e' => $idsExpedientes[0], ':t' => 'Seguimiento', ':f' => date('Y-m-d H:i:s', strtotime('-' . ($i + 1) . ' days'))]);
}
$db->prepare("INSERT INTO sesiones_clinicas (id_expediente, tipo_consulta, observaciones_generales, intervencion_realizada, notas_evolucion, fecha_registro)
              VALUES (:e, :t, 'Obs metricas', 'Intervencion metricas', 'Notas metricas', :f)")
   ->execute([':e' => $idsExpedientes[1], ':t' => 'Evaluación inicial', ':f' => date('Y-m-d H:i:s', strtotime('-5 days'))]);

// Informes con distintos niveles de urgencia
$db->prepare("INSERT INTO reportes (id_expediente, id_usuario_autor, fecha_generacion, contenido_tecnico, nivel_urgencia)
              VALUES (:e, :u, CURDATE(), 'Contenido', 'Alto')")
   ->execute([':e' => $idsExpedientes[0], ':u' => $idPsicologo]);
$db->prepare("INSERT INTO reportes (id_expediente, id_usuario_autor, fecha_generacion, contenido_tecnico, nivel_urgencia)
              VALUES (:e, :u, CURDATE(), 'Contenido', 'Bajo')")
   ->execute([':e' => $idsExpedientes[1], ':u' => $idPsicologo]);

// Usuarios de prueba
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (2, 'Metricas Director', 'met-test-dir@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'Metricas Psicologo', 'met-test-psi@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);

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

$jarDir = sys_get_temp_dir() . '/cookies_met_dir.txt';
$jarPsi = sys_get_temp_dir() . '/cookies_met_psi.txt';
@unlink($jarDir);
@unlink($jarPsi);

echo "\n=== 1. Valores calculados por el modelo (contraste con la BD) ===\n";
$modelo = new App\Models\Dashboard();
$metricas = $modelo->obtenerMetricas(date('Y-m-d', strtotime('-6 months')), date('Y-m-d'));
check('El modelo devuelve todos los bloques de metricas',
    isset($metricas['pacientes'], $metricas['expedientes'], $metricas['citas'], $metricas['sesiones'],
          $metricas['informes'], $metricas['series_mensual'], $metricas['por_dia_semana'],
          $metricas['por_tipo'], $metricas['por_estado_caso'], $metricas['por_urgencia']));
check('La serie mensual tiene 12 periodos', count($metricas['series_mensual']) === 12, 'count=' . count($metricas['series_mensual']));
check('La serie mensual trae citas y sesiones',
    array_key_exists('citas', $metricas['series_mensual'][0]) && array_key_exists('sesiones', $metricas['series_mensual'][0]));
check('La distribucion por dia de semana tiene 7 dias', count($metricas['por_dia_semana']) === 7, 'count=' . count($metricas['por_dia_semana']));
check('La distribucion por tipo esta presente', count($metricas['por_tipo']) >= 1);
check('La distribucion por estado de caso esta presente', count($metricas['por_estado_caso']) >= 1);
check('La distribucion por urgencia tiene 3 niveles', count($metricas['por_urgencia']) === 3, 'count=' . count($metricas['por_urgencia']));

// Contraste directo contra la BD
$citasReales = (int)$db->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)")->fetchColumn();
check('El total de citas coincide con la consulta directa a la BD',
    $metricas['citas']['total'] === $citasReales, 'modelo=' . $metricas['citas']['total'] . ' bd=' . $citasReales);

$pacientesReales = (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE estado_paciente = 'Activo'")->fetchColumn();
check('El total de pacientes activos coincide con la BD',
    $metricas['pacientes']['activos'] === $pacientesReales, 'modelo=' . $metricas['pacientes']['activos'] . ' bd=' . $pacientesReales);

$completadasReales = (int)$db->query("SELECT COUNT(*) FROM citas WHERE estado_cita = 'Completada' AND DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)")->fetchColumn();
check('Las citas completadas coinciden con la BD',
    $metricas['citas']['completadas'] === $completadasReales, 'modelo=' . $metricas['citas']['completadas'] . ' bd=' . $completadasReales);

$altoReal = (int)$db->query("SELECT COUNT(*) FROM reportes WHERE nivel_urgencia = 'Alto' AND fecha_generacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)")->fetchColumn();
check('Los informes de urgencia alta coinciden con la BD',
    $metricas['informes']['alto'] === $altoReal, 'modelo=' . $metricas['informes']['alto'] . ' bd=' . $altoReal);

$tasaEsperada = $citasReales > 0 ? round(($completadasReales / $citasReales) * 100, 1) : 0;
check('La tasa de cumplimiento se calcula correctamente',
    $metricas['citas']['cumplimiento'] === $tasaEsperada, 'modelo=' . $metricas['citas']['cumplimiento'] . ' esperado=' . $tasaEsperada);

echo "\n=== 2. Graficos SVG generados por la libreria ===\n";
$svg = App\Libraries\Graficos::barrasVerticales(['A', 'B', 'C'], [['nombre' => 'Citas', 'color' => '#7E57C2', 'valores' => [3, 0, 5]]]);
check('El grafico de barras verticales genera un SVG valido', strpos($svg, '<svg') === 0 && strpos($svg, '</svg>') !== false);
check('Incluye el fondo y las 2 barras con datos (la de valor 0 no se dibuja)', substr_count($svg, '<rect') === 3, 'rects=' . substr_count($svg, '<rect'));
check('Incluye las etiquetas del eje X', strpos($svg, '>A<') !== false && strpos($svg, '>C<') !== false);
check('Incluye los valores en el title (accesibilidad)', strpos($svg, '<title>') !== false);
check('Escapa los caracteres especiales', strpos(App\Libraries\Graficos::barrasVerticales(['<script>'], [['nombre' => 'x', 'valores' => [1]]]), '&lt;script&gt;') !== false);

$svgH = App\Libraries\Graficos::barrasHorizontales([['etiqueta' => 'Activo', 'total' => 4, 'color' => '#7E57C2']]);
check('El grafico horizontal genera un SVG valido', strpos($svgH, '<svg') === 0 && strpos($svgH, '</svg>') !== false);
check('El grafico horizontal muestra el valor', strpos($svgH, '>4<') !== false);

$svgVacio = App\Libraries\Graficos::barrasVerticales(['A'], [['nombre' => 'Citas', 'valores' => [0]]]);
check('El grafico no falla sin datos (division por cero)', strpos($svgVacio, '<svg') === 0);

echo "\n=== 3. Dashboard de metricas por HTTP (Director) ===\n";
$r = iniciarSesion('met-test-dir@local.test', $jarDir);
check('Login del director aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=dashboard', $jarDir);
$html = $r['cuerpo'];
check('El dashboard del director responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El dashboard del director ES el panel de metricas', strpos($html, 'Dashboard de Métricas') !== false);
check('Muestra el filtro de periodo', substr_count($html, 'name="periodo"') === 5, 'botones=' . substr_count($html, 'name="periodo"'));
check('Muestra las 8 tarjetas de indicadores', substr_count($html, 'class="metrica-card') === 8, 'tarjetas=' . substr_count($html, 'class="metrica-card'));
check('Muestra el indicador de pacientes activos', strpos($html, 'Pacientes activos') !== false);
check('Muestra el indicador de cumplimiento de citas', strpos($html, 'Cumplimiento de citas') !== false);
check('Muestra el indicador de tasa de inasistencia', strpos($html, 'Tasa de inasistencia') !== false);
check('Muestra el indicador de sesiones clinicas', strpos($html, 'Sesiones clínicas') !== false);
check('Muestra el indicador de informes emitidos', strpos($html, 'Informes emitidos') !== false);
check('Muestra la evolucion mensual', strpos($html, 'Evolución mensual') !== false);
check('Muestra las citas por dia de la semana', strpos($html, 'Citas por día de la semana') !== false);
check('Muestra el estado de los casos', strpos($html, 'Estado de los casos') !== false);
check('Muestra el nivel de urgencia', strpos($html, 'Nivel de urgencia de los informes') !== false);
check('Muestra el tipo de paciente', strpos($html, 'Pacientes activos por tipo') !== false);
check('Muestra el resumen de la agenda', strpos($html, 'Resumen de la agenda en el período') !== false);
check('Incluye graficos SVG', substr_count($html, '<svg') === 5, 'svg=' . substr_count($html, '<svg'));
check('Incluye la leyenda de series', strpos($html, 'metricas-leyenda') !== false);
check('Muestra el control de auto-actualizacion', strpos($html, 'id="metricas-auto"') !== false);
check('Muestra la marca de tiempo de actualizacion', strpos($html, 'id="metricas-timestamp"') !== false);
check('Muestra el boton de actualizacion manual', strpos($html, 'id="metricas-refrescar"') !== false);
check('Muestra el cronometro de cuenta regresiva', strpos($html, 'id="metricas-cronometro"') !== false);
check('NO incluye la agenda operativa del psicologo', strpos($html, 'Próximas Citas (Hoy)') === false);
check('Declara que los datos son agregados', strpos($html, 'no exponen información clínica individual') !== false);

// Valores numericos visibles
check('Muestra el total de pacientes activos en pantalla', strpos($html, '>' . number_format($pacientesReales) . '<') !== false);

// La ruta /metricas debe redirigir a la URL canonica
$r = peticion(BASE_TEST . '?url=metricas', $jarDir);
check('La ruta /metricas redirige al dashboard', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect apunta a /dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

echo "\n=== 4. Filtro de periodo ===\n";
$r = peticion(BASE_TEST . '?url=dashboard&periodo=mes', $jarDir);
check('El periodo "mes" responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El periodo "mes" muestra su etiqueta', strpos($r['cuerpo'], 'Este mes') !== false);
check('El periodo "mes" muestra el rango de fechas', strpos($r['cuerpo'], date('d/m/Y', strtotime(date('Y-m-01')))) !== false);

$r = peticion(BASE_TEST . '?url=dashboard&periodo=todo', $jarDir);
check('El periodo "todo" responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El periodo "todo" no muestra rango de fechas', strpos($r['cuerpo'], 'Todo el historial') !== false);

$r = peticion(BASE_TEST . '?url=dashboard&periodo=invalido', $jarDir);
check('Un periodo invalido cae en el predeterminado', $r['codigo'] === 200 && strpos($r['cuerpo'], 'Ultimos 6 meses') !== false);

$r = peticion(BASE_TEST . '?url=metricas&periodo=mes', $jarDir);
check('El alias /metricas conserva el periodo', strpos($r['ubicacion'], 'periodo=mes') !== false, $r['ubicacion']);

echo "\n=== 5. Permisos por rol ===\n";
$r = iniciarSesion('met-test-psi@local.test', $jarPsi);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=dashboard', $jarPsi);
check('El psicologo accede a SU dashboard operativo', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El psicologo ve la agenda de citas del dia', strpos($r['cuerpo'], 'Próximas Citas (Hoy)') !== false);
check('El psicologo NO ve el panel de metricas', strpos($r['cuerpo'], 'Dashboard de Métricas') === false);
check('El psicologo NO ve las tarjetas KPI', strpos($r['cuerpo'], 'class="metrica-card') === false);

$r = peticion(BASE_TEST . '?url=metricas', $jarPsi);
check('El psicologo NO accede a /metricas', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

// El menu no debe duplicar la entrada
$r = peticion(BASE_TEST . '?url=dashboard', $jarDir);
check('El menu no duplica la entrada de metricas', strpos($r['cuerpo'], '>📈 Métricas<') === false);
check('El menu mantiene la entrada Dashboard', strpos($r['cuerpo'], '📊 Dashboard') !== false);

echo "\n=== 6. Limpieza ===\n";
$db->exec("DELETE FROM reportes WHERE id_expediente IN (" . implode(',', $idsExpedientes) . ")");
$db->exec("DELETE FROM sesiones_clinicas WHERE id_expediente IN (" . implode(',', $idsExpedientes) . ")");
$db->exec("DELETE FROM citas WHERE id_paciente IN (" . implode(',', $idsPacientes) . ")");
$db->exec("DELETE FROM expedientes WHERE id_expediente IN (" . implode(',', $idsExpedientes) . ")");
$db->exec("DELETE FROM pacientes WHERE id_paciente IN (" . implode(',', $idsPacientes) . ")");
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'met-test-%@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%met-test-%'");
check('Datos de prueba eliminados', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'MET-TEST-%'")->fetchColumn() === 0);
check('Usuarios de prueba eliminados', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'met-test-%@local.test'")->fetchColumn() === 0);
@unlink($jarDir);
@unlink($jarPsi);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
