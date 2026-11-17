<?php
/**
 * Prueba end-to-end de la historia HU-14: restaurar datos desde un respaldo.
 *
 * Verifica el flujo por HTTP con el Administrador: seleccion del respaldo,
 * pantalla de confirmacion con el analisis previo, confirmacion explicita,
 * restauracion real, validacion de integridad y restricciones de acceso.
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_restauracion.php
 *
 * IMPORTANTE: ejecuta una restauracion REAL sobre la base local.
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

// ---------- Limpieza previa ----------
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'restauracion-test-%@local.test'");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REST-HTTP-%'");

// El administrador debe existir ANTES del respaldo para que sobreviva a la restauracion
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (3, 'Restauracion Admin', 'restauracion-test-admin@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);
$db->prepare("INSERT INTO usuarios (id_rol, nombre_completo, correo, password_hash, estado)
              VALUES (2, 'Restauracion Director', 'restauracion-test-dir@local.test', :p, 'Activo')")
   ->execute([':p' => password_hash('Prueba123!', PASSWORD_DEFAULT)]);

// ---------- Peticiones HTTP ----------
function peticion($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 40,
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

$jarAdmin = sys_get_temp_dir() . '/cookies_rest_admin.txt';
$jarDir = sys_get_temp_dir() . '/cookies_rest_dir.txt';
@unlink($jarAdmin);
@unlink($jarDir);

echo "\n=== 1. Seleccion del respaldo ===\n";
// Dato que quedara dentro del respaldo
$db->prepare("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, estado_paciente)
              VALUES ('REST-HTTP-001', 'Dentro', 'DelRespaldo', '2004-03-03', 'Femenino', 'Estudiante', 'Activo')")->execute();

$respaldo = Respaldo::generar();
$nombreArchivo = $respaldo['archivo'];
check('Se genero un respaldo disponible para restaurar', is_file($respaldo['ruta']), $nombreArchivo);

$r = iniciarSesion('restauracion-test-admin@local.test', $jarAdmin);
check('Login del administrador aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('El listado ofrece la accion de restaurar', strpos($r['cuerpo'], '♻️ Restaurar') !== false);
check('El listado enlaza a la ruta de restauracion', strpos($r['cuerpo'], '/respaldo/restaurar?archivo=') !== false);

echo "\n=== 2. Pantalla de confirmacion ===\n";
$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode($nombreArchivo), $jarAdmin);
$html = $r['cuerpo'];
check('La pantalla de confirmacion responde 200', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Advierte que se reemplazan todos los datos', strpos($html, 'reemplaza TODOS los datos actuales') !== false);
check('Explica el respaldo de seguridad automatico', strpos($html, 'respaldo de seguridad') !== false);
check('Muestra el archivo seleccionado', strpos($html, htmlspecialchars($nombreArchivo)) !== false);
check('Muestra el contenido que se restaurara', strpos($html, 'Contenido que se restaurará') !== false);
check('Muestra las tablas con su numero de registros', strpos($html, 'pacientes') !== false);
check('Muestra las verificaciones de integridad', strpos($html, 'Verificaciones que se ejecutarán') !== false);
check('Exige escribir el nombre del archivo', strpos($html, 'name="confirmacion"') !== false);
check('Exige marcar la casilla de aceptacion', strpos($html, 'name="aceptar"') !== false);
check('El boton es POST', strpos($html, 'method="POST"') !== false);

echo "\n=== 3. Confirmaciones obligatorias ===\n";
$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode($nombreArchivo), $jarAdmin, [
    'confirmacion' => 'nombre-equivocado.sql.gz',
    'aceptar' => '1'
]);
check('Un nombre incorrecto se rechaza', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('Se informa del nombre incorrecto', strpos($r['cuerpo'], 'exactamente el nombre del archivo') !== false);

$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode($nombreArchivo), $jarAdmin, [
    'confirmacion' => $nombreArchivo
]);
check('Sin marcar la casilla se rechaza', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('Se informa que falta la casilla', strpos($r['cuerpo'], 'marcar la casilla') !== false);

echo "\n=== 4. Restauracion correcta ===\n";
// Dato registrado DESPUES del respaldo: debe desaparecer al restaurar
$db->exec("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, estado_paciente)
           VALUES ('REST-HTTP-002', 'Desaparecera', 'AlRestaurar', '2007-07-07', 'Masculino', 'Estudiante', 'Activo')");
check('El paciente posterior al respaldo existe antes de restaurar',
    (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-HTTP-002'")->fetchColumn() === 1);

$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode($nombreArchivo), $jarAdmin, [
    'confirmacion' => $nombreArchivo,
    'aceptar' => '1'
]);
check('La restauracion responde con redireccion', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('Se confirma la restauracion', strpos($r['cuerpo'], 'Restauración completada correctamente') !== false);
check('La confirmacion menciona la validacion de integridad', strpos($r['cuerpo'], 'integridad de los datos fue verificada') !== false);

check('El paciente posterior al respaldo desaparecio',
    (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-HTTP-002'")->fetchColumn() === 0);
check('El paciente del respaldo sigue presente',
    (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-HTTP-001'")->fetchColumn() === 1);
check('El administrador sigue existiendo tras restaurar',
    (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo = 'restauracion-test-admin@local.test'")->fetchColumn() === 1);
check('No hay citas huerfanas tras restaurar',
    (int)$db->query("SELECT COUNT(*) FROM citas c LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente WHERE p.id_paciente IS NULL")->fetchColumn() === 0);

// Se genero un respaldo de seguridad automatico
$listado = Respaldo::listar();
check('Se genero un respaldo de seguridad previo', count($listado) >= 2, 'respaldos=' . count($listado));

echo "\n=== 5. Restauracion de un archivo no existente ===\n";
$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=respaldo_20200101_000000.sql.gz', $jarAdmin);
check('Un archivo inexistente redirige', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
$r = peticion(BASE_TEST . '?url=respaldo', $jarAdmin);
check('Se informa que el respaldo no existe', strpos($r['cuerpo'], 'no existe o el nombre no es válido') !== false);

$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode('../../app/Config/Database.php'), $jarAdmin);
check('El path traversal se rechaza', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

echo "\n=== 6. Permisos por rol ===\n";
$r = iniciarSesion('restauracion-test-dir@local.test', $jarDir);
check('Login del director aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=respaldo/restaurar&archivo=' . urlencode($nombreArchivo), $jarDir);
check('El director NO puede restaurar', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

$r = peticion(BASE_TEST . '?url=respaldo', $jarDir);
check('El director NO accede a la pagina de respaldos', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

echo "\n=== 7. Limpieza ===\n";
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'restauracion-test-%@local.test'");
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REST-HTTP-%'");
check('Se eliminaron los respaldos de prueba', count(Respaldo::listar()) === 0);
check('Se eliminaron los usuarios de prueba', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'restauracion-test-%@local.test'")->fetchColumn() === 0);
check('Se eliminaron los pacientes de prueba', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'REST-HTTP-%'")->fetchColumn() === 0);
@unlink($jarAdmin);
@unlink($jarDir);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
