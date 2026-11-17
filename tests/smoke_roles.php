<?php
/**
 * Prueba de humo (smoke test) - Historia HU-10: Asignar roles y permisos
 *
 * Valida la matriz de permisos, el catalogo de roles, la aplicacion inmediata
 * de cambios de rol y las protecciones del administrador.
 *
 * Uso:  C:\xampp\php\php.exe tests\smoke_roles.php
 *
 * IMPORTANTE: crea y destruye usuarios de prueba con prefijo SMOKE-TEST-.
 * Nunca ejecutar contra la base de datos de produccion.
 */

define('BASE_URL', 'http://localhost/plenamente');
chdir(__DIR__ . '/..');

session_id('smokeroles');
session_start();

require_once 'app/Core/Logger.php';
require_once 'app/Core/Flash.php';
require_once 'app/Core/Permisos.php';
require_once 'app/Core/Controller.php';
require_once 'app/Config/Database.php';
require_once 'app/Models/Rol.php';
require_once 'app/Models/Usuario.php';

use App\Core\Permisos;
use App\Models\Rol;
use App\Models\Usuario;

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
$rolModel = new Rol();
$usuarioModel = new Usuario();

// ---------- Limpieza previa ----------
$db->exec("DELETE FROM usuarios WHERE correo LIKE 'smoke-test-%@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%smoke-test-%@local.test%'");

echo "\n=== 1. Catalogo de roles predefinidos ===\n";
$roles = $rolModel->obtenerTodos();
check('La tabla roles expone los 3 roles predefinidos', count($roles) === 3, 'count=' . count($roles));
$nombres = array_column($roles, 'nombre_rol');
check('Rol Psicologo presente', in_array('Psicologo', $nombres, true), implode(',', $nombres));
check('Rol Director presente', in_array('Director', $nombres, true), implode(',', $nombres));
check('Rol Administrador presente', in_array('Administrador', $nombres, true), implode(',', $nombres));
check('Rol 1 existe (Psicologo)', $rolModel->existe(1));
check('Rol 2 existe (Director)', $rolModel->existe(2));
check('Rol 3 existe (Administrador)', $rolModel->existe(3));
check('Rol 99 NO existe', !$rolModel->existe(99));
check('Rol 0 NO existe', !$rolModel->existe(0));

echo "\n=== 2. Matriz de permisos por rol ===\n";
check('Psicologo: pacientes', Permisos::rolTiene(1, 'pacientes'));
check('Psicologo: citas', Permisos::rolTiene(1, 'citas'));
check('Psicologo: expedientes', Permisos::rolTiene(1, 'expedientes'));
check('Psicologo: reportes', Permisos::rolTiene(1, 'reportes'));
check('Psicologo: NO usuarios', !Permisos::rolTiene(1, 'usuarios'));
check('Psicologo: NO roles', !Permisos::rolTiene(1, 'roles'));
check('Director: reportes', Permisos::rolTiene(2, 'reportes'));
check('Director: asistencias', Permisos::rolTiene(2, 'asistencias'));
check('Director: NO pacientes', !Permisos::rolTiene(2, 'pacientes'));
check('Director: NO citas', !Permisos::rolTiene(2, 'citas'));
check('Director: NO expedientes', !Permisos::rolTiene(2, 'expedientes'));
check('Director: NO usuarios', !Permisos::rolTiene(2, 'usuarios'));
check('Director: NO puede registrar asistencias', !Permisos::rolTiene(2, 'asistencias.registrar'));
check('Director: NO puede generar informes', !Permisos::rolTiene(2, 'reportes.crear'));
check('Administrador: usuarios', Permisos::rolTiene(3, 'usuarios'));
check('Administrador: roles', Permisos::rolTiene(3, 'roles'));
check('Administrador: dashboard', Permisos::rolTiene(3, 'dashboard'));
check('Administrador: NO pacientes', !Permisos::rolTiene(3, 'pacientes'));
check('Administrador: NO reportes', !Permisos::rolTiene(3, 'reportes'));
check('Modulo inexistente queda denegado', !Permisos::rolTiene(1, 'modulo_inexistente'));

echo "\n=== 3. Permisos en funcion de la sesion ===\n";
$_SESSION['id_rol'] = 1;
check('Sesion Psicologo puede entrar a pacientes', Permisos::puede('pacientes'));
check('Sesion Psicologo NO puede entrar a usuarios', !Permisos::puede('usuarios'));
$_SESSION['id_rol'] = 2;
check('Sesion Director puede ver reportes', Permisos::puede('reportes'));
check('Sesion Director NO puede ver pacientes', !Permisos::puede('pacientes'));
$_SESSION['id_rol'] = 3;
check('Sesion Administrador puede ver usuarios', Permisos::puede('usuarios'));
$_SESSION['id_rol'] = 99;
check('Rol desconocido no tiene permisos', !Permisos::puede('dashboard'));
unset($_SESSION['id_rol']);
check('Sin sesion no hay permisos', !Permisos::puede('dashboard'));

echo "\n=== 4. Conteo de administradores activos (proteccion) ===\n";
$adminsActivos = $usuarioModel->contarActivosPorRol(3);
check('El sistema tiene al menos un administrador activo', $adminsActivos >= 1, "count=$adminsActivos");

$idAdminReal = (int)$db->query("SELECT id_usuario FROM usuarios WHERE id_rol = 3 AND estado = 'Activo' LIMIT 1")->fetchColumn();
$otrosAdmins = $usuarioModel->contarActivosPorRol(3, $idAdminReal);
check('Excluyendo un admin, el conteo es consistente', $otrosAdmins === $adminsActivos - 1, "otros=$otrosAdmins total=$adminsActivos");

echo "\n=== 5. Cambio de rol y aplicacion inmediata ===\n";
$usuarioModel->crearUsuario(2, 'Smoke Test', 'smoke-test-rol@local.test', password_hash('Prueba123!', PASSWORD_DEFAULT));
$idUsuario = (int)$db->query("SELECT id_usuario FROM usuarios WHERE correo = 'smoke-test-rol@local.test'")->fetchColumn();
check('Usuario de prueba creado', $idUsuario > 0, "id=$idUsuario");

// Simula sesion iniciada con el rol anterior
$_SESSION['usuario_id'] = $idUsuario;
$_SESSION['id_rol'] = 2;
check('Sesion arranca como Director', Permisos::puede('reportes') && !Permisos::puede('pacientes'));

// El administrador cambia el rol en la BD
$usuarioModel->actualizarUsuario($idUsuario, 1, 'Smoke Test', 'smoke-test-rol@local.test', 'Activo', null);
$rolEnBd = (int)$db->query("SELECT id_rol FROM usuarios WHERE id_usuario = $idUsuario")->fetchColumn();
check('Rol persistido en BD = Psicologo', $rolEnBd === 1, "rol=$rolEnBd");

// Sesion::sincronizar() relee el rol -> aplicacion inmediata
require_once 'app/Core/Sesion.php';
App\Core\Sesion::sincronizar();
check('La sesion se actualizo al instante', (int)$_SESSION['id_rol'] === 1, 'rol_sesion=' . $_SESSION['id_rol']);
check('Ahora SI puede entrar a pacientes', Permisos::puede('pacientes'));
check('Ahora NO puede entrar a usuarios', !Permisos::puede('usuarios'));

echo "\n=== 6. Cambio de estado: cuenta desactivada pierde el acceso ===\n";
$usuarioModel->eliminarLogico($idUsuario);
$estadoEnBd = (string)$db->query("SELECT estado FROM usuarios WHERE id_usuario = $idUsuario")->fetchColumn();
check('Cuenta marcada como Inactiva', $estadoEnBd === 'Inactivo', $estadoEnBd);
$resultado = App\Core\Sesion::sincronizar();
check('La sincronizacion ordena cerrar la sesion', $resultado['accion'] === 'logout', $resultado['accion']);
check('La sesion fue cerrada al desactivar la cuenta', empty($_SESSION['usuario_id']));
check('Se informa al usuario del cierre', !empty($resultado['mensaje']), $resultado['mensaje']);

echo "\n=== 7. Usuario con rol invalido (manipulacion de POST) ===\n";
try {
    $db->prepare("UPDATE usuarios SET id_rol = 99 WHERE id_usuario = :id")->execute([':id' => $idUsuario]);
    // La FK id_usuario -> roles.id_rol debe impedir un rol inexistente
    check('La FK impide asignar un rol inexistente (99)', false, 'la BD acepto un rol inexistente');
} catch (Throwable $e) {
    check('La FK impide asignar un rol inexistente (99)', true);
}
$rolInvalido = (int)$db->query("SELECT id_rol FROM usuarios WHERE id_usuario = $idUsuario")->fetchColumn();
check('El usuario conserva un rol valido', in_array($rolInvalido, [1, 2, 3], true), "rol=$rolInvalido");
check('La validacion del modelo detecta el rol invalido', !$rolModel->existe(99));

echo "\n=== 8. Limpieza ===\n";
$db->exec("DELETE FROM usuarios WHERE correo = 'smoke-test-rol@local.test'");
$db->exec("DELETE FROM auditoria_acciones WHERE descripcion LIKE '%smoke-test-rol@local.test%'");
check('Usuario de prueba eliminado', (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE correo = 'smoke-test-rol@local.test'")->fetchColumn() === 0);
unset($_SESSION['usuario_id'], $_SESSION['id_rol']);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
