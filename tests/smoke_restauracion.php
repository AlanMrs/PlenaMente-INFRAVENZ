<?php
/**
 * Prueba de humo - Historia HU-14: restaurar datos desde un respaldo
 *
 * Verifica el escenario real: se genera un respaldo, se modifican los datos,
 * se restaura el respaldo SOBRE la base viva y se comprueba que:
 *   - los datos previos al respaldo desaparecieron,
 *   - los datos del respaldo volvieron,
 *   - la validacion de integridad no reporta errores,
 *   - la validacion detecta una corrupcion,
 *   - un archivo invalido o corrupto se rechaza sin tocar la base.
 *
 * Uso:  C:\xampp\php\php.exe tests\smoke_restauracion.php
 *
 * IMPORTANTE: modifica la base de datos local (restauracion real) y crea
 * datos de prueba con prefijo REST-TEST-. Nunca ejecutar en produccion.
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

$db = (new App\Config\Database())->getConnection();

// ---------- Limpieza ----------
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REST-TEST-%'");

echo "\n=== 1. Analisis previo del archivo (sin modificar nada) ===\n";
$db->prepare("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, estado_paciente)
              VALUES ('REST-TEST-001', 'Datos', 'Actuales', '2005-01-01', 'Femenino', 'Estudiante', 'Activo')")->execute();

$respaldo = Respaldo::generar();
check('Se genero el respaldo de referencia', is_file($respaldo['ruta']));

$analisis = Respaldo::analizar($respaldo['ruta']);
check('El analisis declara el archivo valido', $analisis['valido'] === true, implode(' ', $analisis['errores']));
check('El analisis lista las tablas', count($analisis['tablas']) >= 8, 'tablas=' . count($analisis['tablas']));
check('El analisis cuenta los registros', $analisis['total_filas'] > 0, 'filas=' . $analisis['total_filas']);
check('El analisis detecta la tabla pacientes', isset($analisis['tablas']['pacientes']));
check('El analisis no modifico la base', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-TEST-001'")->fetchColumn() === 1);

echo "\n=== 2. Modificacion de datos posteriores al respaldo ===\n";
$db->exec("INSERT INTO pacientes (nie_dui, nombres, apellidos, fecha_nacimiento, genero, tipo_paciente, estado_paciente)
           VALUES ('REST-TEST-002', 'Posterior', 'AlRespaldo', '2006-02-02', 'Masculino', 'Estudiante', 'Activo')");
$totalAntes = (int)$db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
check('Se registro un paciente posterior al respaldo', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-TEST-002'")->fetchColumn() === 1);
echo "     pacientes antes de restaurar: $totalAntes\n";

echo "\n=== 3. Restauracion sobre la base viva ===\n";
$informe = Respaldo::restaurar($respaldo['ruta']);
check('La restauracion se ejecuto', $informe['sentencias'] > 0, 'sentencias=' . $informe['sentencias']);
check('La restauracion reporto las tablas afectadas', count($informe['tablas']) >= 8, 'tablas=' . count($informe['tablas']));
check('La restauracion fue rapida (< 30 s)', $informe['duracion'] < 30, $informe['duracion'] . ' s');

$totalDespues = (int)$db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
check('El numero de pacientes volvio al del respaldo', $totalDespues === $totalAntes - 1,
    "antes=$totalAntes despues=$totalDespues");
check('El paciente posterior al respaldo desaparecio',
    (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-TEST-002'")->fetchColumn() === 0);
check('El paciente contenido en el respaldo sigue presente',
    (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui = 'REST-TEST-001'")->fetchColumn() === 1);

echo "\n=== 4. Validacion de integridad ===\n";
$validacion = Respaldo::validarIntegridad($analisis);
check('La validacion declara la integridad correcta', $validacion['valido'] === true, implode(' | ', $validacion['errores']));
check('La validacion no reporta errores', empty($validacion['errores']), implode(' | ', $validacion['errores']));
check('La validacion reviso el detalle por tabla', count($validacion['detalle']) >= 8, 'detalle=' . count($validacion['detalle']));

$coinciden = true;
foreach ($validacion['detalle'] as $fila) {
    if (!$fila['coincide']) {
        $coinciden = false;
    }
}
check('Todas las tablas coinciden con el numero de filas del respaldo', $coinciden);

$huerfanos = (int)$db->query("SELECT COUNT(*) FROM citas c LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente
                               WHERE p.id_paciente IS NULL")->fetchColumn();
check('No hay citas huerfanas tras restaurar', $huerfanos === 0, "huerfanos=$huerfanos");
$huerfanos2 = (int)$db->query("SELECT COUNT(*) FROM sesiones_clinicas s LEFT JOIN expedientes e ON s.id_expediente = e.id_expediente
                                WHERE e.id_expediente IS NULL")->fetchColumn();
check('No hay sesiones huerfanas tras restaurar', $huerfanos2 === 0, "huerfanos=$huerfanos2");
$roles = (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
check('El catalogo de roles quedo completo', $roles === 3, "roles=$roles");

echo "\n=== 5. La validacion detecta una corrupcion ===\n";
$db->exec("DELETE FROM citas LIMIT 1");
$validacionMala = Respaldo::validarIntegridad($analisis);
check('La validacion detecta la perdida de datos', $validacionMala['valido'] === false);
check('La validacion describe el problema', !empty($validacionMala['errores']), implode(' | ', $validacionMala['errores']));
check('La validacion marca la tabla afectada como discrepante', (function () use ($validacionMala) {
    foreach ($validacionMala['detalle'] as $fila) {
        if (!$fila['coincide']) {
            return true;
        }
    }
    return false;
})());

Respaldo::restaurar($respaldo['ruta']);
check('La segunda restauracion deja la base integra', (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn() === 3);

echo "\n=== 6. Restauracion de un archivo invalido ===\n";
$rutaInvalida = sys_get_temp_dir() . '/respaldo_invalido.sql.gz';
$gz = gzopen($rutaInvalida, 'wb9');
gzwrite($gz, "-- archivo que no es un respaldo valido\nSELECT 1;\n");
gzclose($gz);

$analisisInvalido = Respaldo::analizar($rutaInvalida);
check('El analisis rechaza un archivo sin tablas', $analisisInvalido['valido'] === false);
check('El analisis explica el motivo', !empty($analisisInvalido['errores']), implode(' ', $analisisInvalido['errores']));

$rutaCorrupta = sys_get_temp_dir() . '/respaldo_corrupto.sql.gz';
file_put_contents($rutaCorrupta, 'esto no es un gzip valido');
$analisisCorrupto = Respaldo::analizar($rutaCorrupta);
check('El analisis rechaza un archivo corrupto', $analisisCorrupto['valido'] === false);
check('El analisis detecta la corrupcion', !empty($analisisCorrupto['errores']));

$threw = false;
try {
    Respaldo::restaurar($rutaCorrupta);
} catch (Throwable $e) {
    $threw = true;
}
check('Restaurar un archivo corrupto lanza un error controlado', $threw);
check('La base no se modifico tras el rechazo', (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn() === 3);
@unlink($rutaInvalida);
@unlink($rutaCorrupta);

echo "\n=== 7. Seguridad (path traversal) ===\n";
check('Rechaza ruta relativa', Respaldo::rutaSegura('../../app/Config/Database.php') === null);
check('Rechaza ruta absoluta', Respaldo::rutaSegura('C:\\Windows\\system.ini') === null);
check('Rechaza un nombre que no es respaldo', Respaldo::rutaSegura('index.php') === null);

echo "\n=== 8. Limpieza ===\n";
foreach (Respaldo::listar() as $respaldo) {
    @unlink($respaldo['ruta']);
}
$db->exec("DELETE FROM pacientes WHERE nie_dui LIKE 'REST-TEST-%'");
check('Se eliminaron los datos de prueba', (int)$db->query("SELECT COUNT(*) FROM pacientes WHERE nie_dui LIKE 'REST-TEST-%'")->fetchColumn() === 0);
check('Se eliminaron los respaldos de prueba', count(Respaldo::listar()) === 0);

echo "\n----------------------------------------\n";
echo "RESULTADO: $ok correctas, $fail fallidas\n";
exit($fail > 0 ? 1 : 0);
