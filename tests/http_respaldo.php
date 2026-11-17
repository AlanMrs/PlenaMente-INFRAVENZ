<?php
/**
 * Prueba end-to-end de la historia HU-13: generar respaldo del sistema.
 *
 * Verifica el flujo completo por HTTP con el Administrador: pagina de
 * respaldos, generacion manual, confirmacion, descarga, eliminacion y
 * restricciones de acceso por rol.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_respaldo.php
 *
 * IMPORTANTE: genera y destruye archivos de prueba en la carpeta backups/.
 * Nunca ejecutar contra la base de datos de produccion.
 */

$BASE_URL_TEST = 'http://127.0.0.1:8099/index.php';
if (isset($argv[1])) {
    $BASE_URL_TEST = rtrim($argv[1], '/') . '/index.php';
}
define('BASE_TEST', $BASE_URL_TEST);

chdir(__DIR__ . '/..');
require_once 'app/Config/Database.php';
require_once 'app/Libraries/Respaldo.php';

use App\Libraries\Respaldo;

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

// Limpieza previa
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'respaldo-test-%@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%respaldo_%'");

$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (3, 'Respaldo Admin', 'respaldo-test-admin@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (1, 'Respaldo Psicologo', 'respaldo-test-psi@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);

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
    $tipo = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return ['codigo' => $codigo, 'cuerpo' => (string)$cuerpo, 'ubicacion' => $ubicacion, 'tipo' => $tipo];
}

function iniciarSesion($correo, $jar) {
    return peticion(BASE_TEST . '?url=auth/procesar', $jar, ['correo' => $correo, 'password' => 'Prueba123!']);
}

$jarAdmin = sys_get_temp_dir() . '/cookies_respaldo_admin.txt';
$jarPsi = sys_get_temp_dir() . '/cookies_respaldo_psi.txt';
@unlink($jarAdmin);
@unlink($jarPsi);

echo "\n=== 1. Pagina de respaldos (Administrador) ===\n";
$r = iniciarSesion('respaldo-test-admin@local.test', $jarAdmin);
check('Login del administrador aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
$html = $r['cuerpo'];
check('Accede a la pagina de respaldos', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Muestra el boton de generar respaldo', strpos($html, 'Generar respaldo') !== false);
check('El formulario de generacion es POST', strpos($html, 'method="POST"') !== false && strpos($html, '/respaldo/generar"') !== false);
check('Muestra la cantidad de respaldos almacenados', strpos($html, 'Respaldos almacenados') !== false);
check('Muestra la ubicacion segura', strpos($html, 'Fuera del directorio público') !== false);
check('Avisa sobre la sensibilidad de la informacion', strpos($html, 'información clínica sensible') !== false);
check('El menu incluye Respaldos', strpos($html, '💾 Respaldos') !== false);
check('Informa que no hay respaldos aun', strpos($html, 'Todavía no hay respaldos generados') !== false);

echo "\n=== 2. Generacion manual y confirmacion ===\n";
$r = peticion(BASE_TEST . '?url=respaldo/generar', $jarAdmin, ['confirmar' => '1']);
check('La generacion responde con redireccion', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va a la pagina de respaldos', strpos($r['ubicacion'], '/respaldo') !== false, $r['ubicacion']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
$html = $r['cuerpo'];
check('Se confirma la generacion exitosa', strpos($html, 'Respaldo generado correctamente') !== false);
check('La confirmacion indica el nombre del archivo', preg_match('/respaldo_\d{8}_\d{6}/', $html) === 1);
check('La confirmacion indica el tamano', strpos($html, 'KB') !== false || strpos($html, 'MB') !== false || strpos($html, 'B<') !== false);
check('La pagina lista el respaldo generado', strpos($html, '⬇ Descargar') !== false);
check('Muestra el ultimo respaldo generado', strpos($html, 'Último respaldo') !== false);

// El archivo existe realmente y es valido
$listado = Respaldo::listar();
check('El archivo del respaldo existe en disco', count($listado) === 1, 'count=' . count($listado));
if (!empty($listado)) {
    check('El archivo esta comprimido (gzip)', strtolower(substr(file_get_contents($listado[0]['ruta']), 0, 2)) === "\x1f\x8b");
    $sql = gzopen($listado[0]['ruta'], 'rb');
    $contenido = '';
    while (!gzeof($sql)) {
        $contenido .= gzread($sql, 65536);
    }
    gzclose($sql);
    check('El dump contiene los datos de los pacientes', strpos($contenido, 'INSERT INTO `pacientes`') !== false);
}

// Registro en auditoria y log
$aud = (int)$db->query("SELECT COUNT(*) FROM auditoria_acciones WHERE entidad = 'respaldo' AND accion = 'CREAR'")->fetchColumn();
check('El respaldo quedo registrado en la auditoria', $aud >= 1, "registros=$aud");

echo "\n=== 3. Descarga del respaldo ===\n";
$nombreArchivo = $listado[0]['archivo'];
$r = peticion(BASE_TEST . '?url=respaldo/descargar&archivo=' . urlencode($nombreArchivo), $jarAdmin);
check('La descarga responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('La descarga es un archivo gzip', strpos((string)$r['tipo'], 'gzip') !== false, (string)$r['tipo']);
check('El contenido descargado es el respaldo', strlen($r['cuerpo']) === $listado[0]['tamano'],
    'descargado=' . strlen($r['cuerpo']) . ' esperado=' . $listado[0]['tamano']);

// Path traversal
$r = peticion(BASE_TEST . '?url=respaldo/descargar&archivo=' . urlencode('../../app/Config/Database.php'), $jarAdmin);
check('La descarga rechaza path traversal', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$r = peticion(BASE_TEST . '?url=respaldo/descargar&archivo=' . urlencode('index.php'), $jarAdmin);
check('La descarga rechaza un nombre no valido', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

echo "\n=== 4. Eliminacion de un respaldo ===\n";
$r = peticion(BASE_TEST . '?url=respaldo/eliminar&archivo=' . urlencode($nombreArchivo), $jarAdmin, ['confirmar' => '1']);
check('La eliminacion responde con redireccion', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El respaldo fue borrado del disco', count(Respaldo::listar()) === 0, 'restantes=' . count(Respaldo::listar()));

$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('Se confirma la eliminacion', strpos($r['cuerpo'], 'Respaldo eliminado correctamente') !== false);

echo "\n=== 5. Permisos por rol ===\n";
$r = iniciarSesion('respaldo-test-psi@local.test', $jarPsi);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarPsi);
check('El psicologo NO accede a /respaldo', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

$r = peticion(BASE_TEST . '?url=respaldo/generar', $jarPsi, ['confirmar' => '1']);
check('El psicologo NO puede generar respaldos', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('No se genero ningun respaldo', count(Respaldo::listar()) === 0, 'restantes=' . count(Respaldo::listar()));

$r = peticion(BASE_TEST . '?url=dashboard', $jarPsi);
check('El psicologo no ve el enlace de respaldos', strpos($r['cuerpo'], '💾 Respaldos') === false);

// Sin sesion
$jarAnon = sys_get_temp_dir() . '/cookies_respaldo_anon.txt';
@unlink($jarAnon);
$r = peticion(BASE_TEST . '?url=respaldo', $jarAnon);
check('Sin sesion se redirige al login', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al login', strpos($r['ubicacion'], 'auth/login') !== false, $r['ubicacion']);
@unlink($jarAnon);

echo "\n=== 6. Limpieza ===\n";
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'respaldo-test-%@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%respaldo_%'");
check('Se eliminaron los respaldos de prueba', count(Respaldo::listar()) === 0);
check('Se eliminaron los usuarios de prueba', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'respaldo-test-%@local.test'")->fetchColumn() === 0);
@unlink($jarAdmin);
@unlink($jarPsi);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
