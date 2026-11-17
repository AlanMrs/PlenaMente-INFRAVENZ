<?php
/**
 * Prueba end-to-end por HTTP de la historia HU-10 (roles y permisos).
 *
 * Levanta usuarios temporales, inicia sesion real contra el servidor local
 * y verifica: matriz de permisos visible para el admin, restricciones por rol
 * y aplicacion inmediata de un cambio de rol (sin volver a iniciar sesion).
 *
 * Requisito: el servidor de desarrollo debe estar levantado en el puerto 8099
 *   C:\xampp\php\php.exe -S 127.0.0.1:8099 -t public
 *
 * Uso:  C:\xampp\php\php.exe tests\http_roles.php
 *
 * IMPORTANTE: crea y destruye usuarios de prueba con prefijo smoke-http-.
 * Nunca ejecutar contra la base de datos de produccion.
 */

$BASE_URL_TEST = 'http://127.0.0.1:8099/index.php';
if (isset($argv[1])) {
    $BASE_URL_TEST = rtrim($argv[1], '/') . '/index.php';
}
define('BASE_TEST', $BASE_URL_TEST);

chdir(__DIR__ . '/..');
require_once 'app/Config/Database.php';
require_once 'app/Models/Usuario.php';

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
$usuarioModel = new App\Models\Usuario();

function limpiar($db) {
    $db->exec("DELETE FROM usuarios WHERE correo LIKE 'smoke-http-%@local.test'");
    $db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%smoke-http-%@local.test%'");
}
limpiar($db);

$usuarioModel->crearUsuario(3, 'Admin HTTP', 'smoke-http-admin@local.test', password_hash('Prueba123!', PASSWORD_DEFAULT));
$usuarioModel->crearUsuario(1, 'Psi HTTP', 'smoke-http-psi@local.test', password_hash('Prueba123!', PASSWORD_DEFAULT));

/**
 * Peticion HTTP con manejo de cookies y sin seguir redirecciones.
 */
function peticion($url, $jar, $post = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 20,
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

$jarAdmin = sys_get_temp_dir() . '/cookies_admin.txt';
$jarPsi = sys_get_temp_dir() . '/cookies_psi.txt';
@unlink($jarAdmin);
@unlink($jarPsi);

echo "\n=== 1. Sesion del Administrador ===\n";
$r = iniciarSesion('smoke-http-admin@local.test', $jarAdmin);
check('Login del administrador aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=rol', $jarAdmin);
check('Accede a /rol (Roles y Permisos)', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('La pantalla muestra la matriz de permisos', strpos($r['cuerpo'], 'Matriz de Permisos') !== false);
check('La pantalla lista los 3 roles', strpos($r['cuerpo'], 'Psicologo') !== false && strpos($r['cuerpo'], 'Director') !== false && strpos($r['cuerpo'], 'Administrador') !== false);
check('El menu muestra "Roles y Permisos"', strpos($r['cuerpo'], 'Roles y Permisos') !== false);

$r = peticion(BASE_TEST . '?url=usuario', $jarAdmin);
check('Accede a /usuario', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('La tabla de usuarios muestra la columna Rol', strpos($r['cuerpo'], '>Rol<') !== false || strpos($r['cuerpo'], '<th>Rol</th>') !== false);

$r = peticion(BASE_TEST . '?url=paciente', $jarAdmin);
check('El admin NO accede a /paciente', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

$r = peticion(BASE_TEST . '?url=expediente', $jarAdmin);
check('El admin NO accede a /expediente', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

echo "\n=== 2. Sesion del Psicologo ===\n";
$r = iniciarSesion('smoke-http-psi@local.test', $jarPsi);
check('Login del psicologo aceptado', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=paciente', $jarPsi);
check('El psicologo accede a /paciente', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('Se muestra el directorio de pacientes', strpos($r['cuerpo'], 'Directorio de Pacientes') !== false);

$r = peticion(BASE_TEST . '?url=cita', $jarPsi);
check('El psicologo accede a /cita', $r['codigo'] === 200, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=usuario', $jarPsi);
check('El psicologo NO accede a /usuario', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=rol', $jarPsi);
check('El psicologo NO accede a /rol', $r['codigo'] === 302, 'codigo=' . $r['codigo']);

$r = peticion(BASE_TEST . '?url=reportes', $jarPsi);
check('El psicologo accede a /reportes', $r['codigo'] === 200, 'codigo=' . $r['codigo']);

echo "\n=== 3. Aplicacion inmediata del cambio de rol (sin re-login) ===\n";
$r = peticion(BASE_TEST . '?url=paciente', $jarPsi);
check('Antes del cambio: el psicologo SI accede a /paciente', $r['codigo'] === 200, 'codigo=' . $r['codigo']);

// El administrador reasigna el rol en la BD (simula el guardado desde /usuario)
$idPsi = (int)$db->query("SELECT id_usuario FROM usuarios WHERE correo = 'smoke-http-psi@local.test'")->fetchColumn();
$usuarioModel->actualizarUsuario($idPsi, 2, 'Psi HTTP', 'smoke-http-psi@local.test', 'Activo', null);

$r = peticion(BASE_TEST . '?url=paciente', $jarPsi);
check('Despues del cambio: ya NO accede a /paciente (aplicado al instante)', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al dashboard', strpos($r['ubicacion'], '/dashboard') !== false, $r['ubicacion']);

// El mensaje de acceso denegado se muestra en el dashboard de destino
$r = peticion(BASE_TEST . '?url=dashboard', $jarPsi);
check('El dashboard sigue accesible', $r['codigo'] === 200, 'codigo=' . $r['codigo']);
check('El mensaje explica el acceso denegado', strpos($r['cuerpo'], 'No tienes permisos') !== false);

$r = peticion(BASE_TEST . '?url=reportes', $jarPsi);
check('Con el rol Director ahora accede a /reportes', $r['codigo'] === 200, 'codigo=' . $r['codigo']);

echo "\n=== 4. Cuenta desactivada pierde el acceso de inmediato ===\n";
$usuarioModel->actualizarUsuario($idPsi, 2, 'Psi HTTP', 'smoke-http-psi@local.test', 'Inactivo', null);
$r = peticion(BASE_TEST . '?url=dashboard', $jarPsi);
check('La sesion se cierra y redirige al login', $r['codigo'] === 302, 'codigo=' . $r['codigo']);
check('El redirect va al login', strpos($r['ubicacion'], 'auth/login') !== false, $r['ubicacion']);

echo "\n=== 5. Limpieza ===\n";
limpiar($db);
check('Usuarios temporales eliminados', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE 'smoke-http-%@local.test'")->fetchColumn() === 0);
@unlink($jarAdmin);
@unlink($jarPsi);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
