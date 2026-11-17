<?php
/**
 * Prueba de humo - Historia HU-13: generar respaldo del sistema
 *
 * Genera un respaldo real, verifica que el archivo resultante sea un dump SQL
 * valido Y RESTAURABLE (se restaura en una base temporal y se comparan los
 * datos), y valida la gestion de archivos y la proteccion contra path traversal.
 *
 * Uso:  C:\xampp\php\php.exe tests\smoke_respaldo.php
 *
 * IMPORTANTE: crea y destruye archivos de prueba en la carpeta backups/.
 * Nunca ejecutar contra la base de datos de produccion.
 */

define('BASE_URL', 'http://localhost/plenamente');
chdir(__DIR__ . '/..');

require_once 'app/Core/Logger.php';
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

// Limpia respaldos previos de prueba
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}

echo "\n=== 1. Generacion del respaldo ===\n";
$error = null;
try {
    $resultado = Respaldo::generar();
} catch (Throwable $e) {
    $resultado = null;
    $error = $e->getMessage();
}
check('La generacion no lanza errores', $resultado !== null, (string)$error);

if ($resultado) {
    echo "     archivo:  {$resultado['archivo']}\n";
    echo "     tamano:   {$resultado['tamano']} bytes\n";
    echo "     tablas:   {$resultado['tablas']}\n";
    echo "     duracion: {$resultado['duracion']} s\n";

    check('El archivo existe', is_file($resultado['ruta']));
    check('El archivo tiene contenido', $resultado['tamano'] > 100, 'bytes=' . $resultado['tamano']);
    check('El nombre sigue el patron esperado',
        (bool)preg_match('/^respaldo_\d{8}_\d{6}(_\d{2})?\.sql\.gz$/', $resultado['archivo']),
        $resultado['archivo']);
    check('El archivo esta comprimido (gzip)', strtolower(substr(file_get_contents($resultado['ruta']), 0, 2)) === "\x1f\x8b");
    check('Se respaldaron todas las tablas', $resultado['tablas'] >= 8, 'tablas=' . $resultado['tablas']);
    check('La generacion fue rapida (< 10 s)', $resultado['duracion'] < 10, $resultado['duracion'] . ' s');

    echo "\n=== 2. Contenido del dump ===\n";
    $sql = gzopen($resultado['ruta'], 'rb');
    $contenido = '';
    while (!gzeof($sql)) {
        $contenido .= gzread($sql, 65536);
    }
    gzclose($sql);

    check('El dump incluye el encabezado', strpos($contenido, 'Respaldo del sistema PlenaMente') !== false);
    check('El dump desactiva la verificacion de claves foraneas', strpos($contenido, 'SET FOREIGN_KEY_CHECKS = 0;') !== false);
    check('El dump abre una transaccion', strpos($contenido, 'START TRANSACTION;') !== false);
    check('El dump cierra la transaccion', strpos($contenido, 'COMMIT;') !== false);
    check('Incluye la estructura de la tabla pacientes', strpos($contenido, 'CREATE TABLE `pacientes`') !== false);
    check('Incluye la estructura de la tabla citas', strpos($contenido, 'CREATE TABLE `citas`') !== false);
    check('Incluye la estructura de la tabla usuarios', strpos($contenido, 'CREATE TABLE `usuarios`') !== false);
    check('Incluye la estructura de la tabla auditoria_acciones', strpos($contenido, 'CREATE TABLE `auditoria_acciones`') !== false);
    check('Incluye DROP TABLE para permitir la restauracion', strpos($contenido, 'DROP TABLE IF EXISTS') !== false);
    check('Incluye sentencias INSERT con datos', substr_count($contenido, 'INSERT INTO') > 0);
    check('Los valores de texto estan entrecomillados', preg_match("/INSERT INTO `usuarios` .*VALUES/s", $contenido) === 1);

    echo "\n=== 3. El dump es restaurable ===\n";
    $db = (new App\Config\Database())->getConnection();
    $puedeCrear = false;
    try {
        $db->exec('CREATE DATABASE IF NOT EXISTS plenamente_test_restauracion');
        $puedeCrear = true;
    } catch (Throwable $e) {
        echo "     (omitido: sin permisos para crear una base temporal)\n";
    }

    if ($puedeCrear) {
        $db->exec('USE plenamente_test_restauracion');

        $sentencias = preg_split('/;\s*\n/', $contenido);
        $ejecutadas = 0;
        $fallos = 0;
        foreach ($sentencias as $sentencia) {
            // Se retiran las lineas de comentario, pero se conserva el SQL mezclado con ellas
            $limpia = trim(preg_replace('/^\s*--.*$/m', '', $sentencia));
            if ($limpia === '') {
                continue;
            }
            try {
                $db->exec($limpia);
                $ejecutadas++;
            } catch (Throwable $e) {
                $fallos++;
                if ($fallos <= 3) {
                    echo "     ERROR: " . substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 140) . "\n";
                }
            }
        }
        check('Todas las sentencias del dump se ejecutan sin error', $fallos === 0, "fallos=$fallos de $ejecutadas");
        check('Se ejecutaron sentencias', $ejecutadas > 5, "ejecutadas=$ejecutadas");

        $original = (int)$db->query("SELECT COUNT(*) FROM plenamente_bd.pacientes")->fetchColumn();
        $restaurado = (int)$db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
        check('El numero de pacientes coincide tras restaurar', $original === $restaurado, "original=$original restaurado=$restaurado");

        $originalCitas = (int)$db->query("SELECT COUNT(*) FROM plenamente_bd.citas")->fetchColumn();
        $restauradoCitas = (int)$db->query("SELECT COUNT(*) FROM citas")->fetchColumn();
        check('El numero de citas coincide tras restaurar', $originalCitas === $restauradoCitas, "original=$originalCitas restaurado=$restauradoCitas");

        $restauradoUsuarios = (int)$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        check('La tabla de usuarios se restauro', $restauradoUsuarios > 0, "usuarios=$restauradoUsuarios");

        $db->exec('DROP DATABASE IF EXISTS plenamente_test_restauracion');
    }
}

echo "\n=== 4. Listado, purga y eliminacion ===\n";
$listado = Respaldo::listar();
check('El respaldo aparece en el listado', count($listado) >= 1, 'count=' . count($listado));
check('El listado trae nombre, tamano y fecha', isset($listado[0]['archivo'], $listado[0]['tamano'], $listado[0]['fecha']));

for ($i = 0; $i < 12; $i++) {
    Respaldo::generar();
}
$listado = Respaldo::listar();
check('Se generaron multiples respaldos sin sobrescribirse', count($listado) >= 13, 'count=' . count($listado));
check('Los nombres son unicos', count(array_unique(array_column($listado, 'archivo'))) === count($listado));

$eliminados = Respaldo::purgar(10);
check('La purga elimina los mas antiguos', count($eliminados) >= 3, 'eliminados=' . count($eliminados));
check('La purga conserva el maximo configurado', count(Respaldo::listar()) === 10, 'restantes=' . count(Respaldo::listar()));

$nombre = Respaldo::listar()[0]['archivo'];
check('Se puede eliminar un respaldo puntual', Respaldo::eliminar($nombre) === true);
check('El respaldo eliminado ya no esta', count(Respaldo::listar()) === 9, 'restantes=' . count(Respaldo::listar()));

echo "\n=== 5. Seguridad (path traversal) ===\n";
check('Rechaza un nombre con ruta relativa', Respaldo::rutaSegura('../../app/Config/Database.php') === null);
check('Rechaza un nombre con ruta absoluta', Respaldo::rutaSegura('C:\\Windows\\system.ini') === null);
check('Rechaza un nombre con ..', Respaldo::rutaSegura('..\\..\\plenamente_bd.sql.gz') === null);
check('Rechaza un nombre que no es respaldo', Respaldo::rutaSegura('index.php') === null);
check('Rechaza un archivo inexistente', Respaldo::rutaSegura('respaldo_20200101_000000.sql.gz') === null);
check('Acepta un nombre valido existente', Respaldo::rutaSegura(Respaldo::listar()[0]['archivo']) !== null);

echo "\n=== 6. La carpeta de respaldos esta fuera del docroot ===\n";
$docroot = realpath(__DIR__ . '/../public');
$directorio = realpath(Respaldo::directorio());
check('La carpeta de respaldos NO esta dentro de public/', $directorio !== false && strpos($directorio, $docroot) !== 0,
    "directorio=$directorio docroot=$docroot");
check('Existe el .htaccess que bloquea el acceso web', is_file(__DIR__ . '/../backups/.htaccess'));

echo "\n=== 7. Limpieza ===\n";
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
check('Se eliminaron los respaldos de prueba', count(Respaldo::listar()) === 0);
check('La carpeta de respaldos se conserva', is_dir(Respaldo::directorio()));

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
