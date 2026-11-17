<?php
namespace App\Libraries;

use App\Config\Database;
use PDO;

/**
 * Respaldo: generacion y gestion de copias de seguridad de la base de datos.
 *
 * El volcado se realiza en PHP puro (sin mysqldump ni shell), por lo que funciona
 * en cualquier hosting sin depender de que exec() este habilitado ni de exponer
 * credenciales en la linea de comandos. El resultado se guarda comprimido con gzip.
 *
 * Los archivos se almacenan FUERA del docroot (carpeta `backups/` en la raiz del
 * proyecto) y se protegen con .htaccess, ya que contienen informacion clinica
 * sensible.
 */
class Respaldo {

    // Cantidad maxima de respaldos que se conservan (los mas antiguos se purgan)
    const MAXIMO_RESPALDOS = 10;

    // Filas por lote al volcar los datos
    const FILAS_POR_LOTE = 300;

    /**
     * Ruta absoluta de la carpeta donde se almacenan los respaldos.
     */
    public static function directorio() {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backups';
    }

    /**
     * Genera un respaldo comprimido de la base de datos.
     *
     * @return array ['archivo','ruta','tamano','duracion','tablas']
     * @throws \RuntimeException si no se puede crear el archivo
     */
    public static function generar() {
        $directorio = self::directorio();

        if (!is_dir($directorio)) {
            if (!@mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                throw new \RuntimeException('No se pudo crear la carpeta de respaldos: ' . $directorio);
            }
        }

        if (!is_writable($directorio)) {
            throw new \RuntimeException('La carpeta de respaldos no tiene permisos de escritura: ' . $directorio);
        }

        $db = (new Database())->getConnection();

        $nombre = 'respaldo_' . date('Ymd_His') . '.sql.gz';
        $ruta = $directorio . DIRECTORY_SEPARATOR . $nombre;

        // Evita sobrescribir un respaldo generado en el mismo segundo
        $contador = 1;
        while (file_exists($ruta)) {
            $nombre = 'respaldo_' . date('Ymd_His') . '_' . str_pad($contador, 2, '0', STR_PAD_LEFT) . '.sql.gz';
            $ruta = $directorio . DIRECTORY_SEPARATOR . $nombre;
            $contador++;
        }

        $archivo = gzopen($ruta, 'wb9');
        if (!$archivo) {
            throw new \RuntimeException('No se pudo abrir el archivo de respaldo para escritura.');
        }

        $inicio = microtime(true);
        $tablas = [];

        try {
            gzwrite($archivo, self::encabezado($db));

            // Transaccion para obtener una fotografia consistente de los datos
            $db->beginTransaction();

            $tablas = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            // Las tablas se vuelcan ordenadas por dependencias: primero las que
            // no son referenciadas por ninguna otra, para que el respaldo sea
            // restaurable incluso sin desactivar la verificacion de claves foraneas.
            $tablas = self::ordenarPorDependencias($db, $tablas);

            foreach ($tablas as $tabla) {
                gzwrite($archivo, self::volcarTabla($db, $tabla));
            }

            $db->commit();
            gzwrite($archivo, "\n-- Fin del respaldo\nCOMMIT;\n");
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            gzclose($archivo);
            @unlink($ruta);
            throw $e;
        }

        gzclose($archivo);

        if (!is_file($ruta)) {
            throw new \RuntimeException('El archivo de respaldo no se generó correctamente.');
        }

        $fin = microtime(true);

        return [
            'archivo'  => $nombre,
            'ruta'     => $ruta,
            'tamano'   => (int)filesize($ruta),
            'duracion' => round($fin - $inicio, 2),
            'tablas'   => count($tablas)
        ];
    }

    /**
     * Ordena las tablas de forma que las referenciadas por otras se creen antes.
     * Usa las claves foraneas registradas en information_schema.
     */
    private static function ordenarPorDependencias(PDO $db, array $tablas) {
        if (count($tablas) < 2) {
            return $tablas;
        }

        $stmt = $db->prepare("SELECT TABLE_NAME, REFERENCED_TABLE_NAME
                               FROM information_schema.KEY_COLUMN_USAGE
                               WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
        $stmt->execute();

        $padres = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $hijo = $fila['TABLE_NAME'];
            $padre = $fila['REFERENCED_TABLE_NAME'];
            $padres[$hijo][$padre] = true;
        }

        $ordenadas = [];
        $pendientes = array_values($tablas);

        while (!empty($pendientes)) {
            $progreso = false;

            foreach ($pendientes as $indice => $tabla) {
                $listo = true;
                foreach (($padres[$tabla] ?? []) as $padre => $_) {
                    if (in_array($padre, $pendientes, true)) {
                        $listo = false;
                        break;
                    }
                }
                if ($listo) {
                    $ordenadas[] = $tabla;
                    unset($pendientes[$indice]);
                    $progreso = true;
                }
            }

            // Ciclo de dependencias: se agregan las restantes para no perder datos
            if (!$progreso) {
                foreach ($pendientes as $tabla) {
                    $ordenadas[] = $tabla;
                }
                break;
            }
        }

        return $ordenadas;
    }

    /**
     * Encabezado del volcado: metadatos y ajustes de sesion.
     */
    private static function encabezado(PDO $db) {
        $nombreBd = $db->query("SELECT DATABASE()")->fetchColumn();

        $sql = "-- ============================================================\n";
        $sql .= "-- Respaldo del sistema PlenaMente - INFRAVENZ\n";
        $sql .= "-- Generado: " . date('d/m/Y H:i:s') . "\n";
        $sql .= '-- Base de datos: ' . $nombreBd . "\n";
        $sql .= "-- Motor: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        $sql .= "-- ============================================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";

        return $sql;
    }

    /**
     * Vuelca la estructura y los datos de una tabla.
     */
    private static function volcarTabla(PDO $db, $tabla) {
        $sql = "\n-- --------------------------------------------------------\n";
        $sql .= "-- Estructura de la tabla `$tabla`\n";
        $sql .= "-- --------------------------------------------------------\n";

        $fila = $db->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
        $sql .= $fila[1] . ";\n\n";

        // Columnas binarias: sus valores se escriben en hexadecimal
        $binarias = self::columnasBinarias($db, $tabla);

        $columnas = null;
        $offset = 0;
        $filasInsertadas = 0;

        while (true) {
            $filas = $db->query("SELECT * FROM `$tabla` LIMIT " . self::FILAS_POR_LOTE . " OFFSET " . $offset)
                ->fetchAll(PDO::FETCH_ASSOC);

            if (empty($filas)) {
                break;
            }

            if ($columnas === null) {
                $columnas = array_keys($filas[0]);
                $sql .= "-- Datos de la tabla `$tabla`\n";
                $sql .= "INSERT INTO `$tabla` (`" . implode('`, `', $columnas) . "`) VALUES\n";
            }

            $valores = [];
            foreach ($filas as $fila) {
                $valores[] = self::valoresFila($db, $fila, $binarias);
            }
            $sql .= implode(",\n", $valores) . ";\n";

            $filasInsertadas += count($filas);
            $offset += self::FILAS_POR_LOTE;

            if (count($filas) < self::FILAS_POR_LOTE) {
                break;
            }
        }

        if ($filasInsertadas === 0) {
            $sql .= "-- (la tabla no contiene registros)\n";
        }

        return $sql;
    }

    /**
     * Construye la lista de valores de una fila.
     */
    private static function valoresFila(PDO $db, array $fila, array $binarias) {
        $valores = [];

        foreach ($fila as $columna => $valor) {
            if ($valor === null) {
                $valores[] = 'NULL';
                continue;
            }
            if (in_array($columna, $binarias, true)) {
                $valores[] = '0x' . bin2hex((string)$valor);
                continue;
            }
            $valores[] = $db->quote((string)$valor);
        }

        return '(' . implode(', ', $valores) . ')';
    }

    /**
     * Nombres de las columnas de tipo binario de una tabla.
     */
    private static function columnasBinarias(PDO $db, $tabla) {
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = :tabla
                                 AND DATA_TYPE IN ('binary','varbinary','blob','tinyblob','mediumblob','longblob')");
        $stmt->bindValue(':tabla', $tabla);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Analiza un respaldo sin modificar nada: comprueba que el archivo sea
     * legible y devuelve las tablas que contiene con su numero de filas.
     *
     * @return array ['valido','archivo','tablas','total_filas','errores','advertencias']
     */
    public static function analizar($ruta) {
        $analisis = [
            'valido'       => false,
            'archivo'      => basename((string)$ruta),
            'tablas'       => [],
            'total_filas'  => 0,
            'errores'      => [],
            'advertencias' => []
        ];

        if (!is_file($ruta)) {
            $analisis['errores'][] = 'El archivo de respaldo no existe.';
            return $analisis;
        }

        $archivo = @gzopen($ruta, 'rb');
        if (!$archivo) {
            $analisis['errores'][] = 'El archivo no es un respaldo gzip válido o está corrupto.';
            return $analisis;
        }

        $tablas = [];
        $tablaActual = null;

        while (!gzeof($archivo)) {
            $linea = gzgets($archivo);
            if ($linea === false) {
                break;
            }

            if (preg_match('/^CREATE TABLE `([^`]+)`/', $linea, $coincidencia)) {
                $tablaActual = $coincidencia[1];
                if (!isset($tablas[$tablaActual])) {
                    $tablas[$tablaActual] = 0;
                }
                continue;
            }

            if (preg_match('/^INSERT INTO `([^`]+)`/', $linea, $coincidencia)) {
                $tablaActual = $coincidencia[1];
                if (!isset($tablas[$tablaActual])) {
                    $tablas[$tablaActual] = 0;
                }
                continue;
            }

            // Dentro de un bloque INSERT cada tupla ocupa una linea
            if ($tablaActual !== null && strpos(ltrim($linea), '(') === 0) {
                $tablas[$tablaActual]++;
            }
        }

        gzclose($archivo);

        if (empty($tablas)) {
            $analisis['errores'][] = 'El respaldo no contiene ninguna tabla reconocible.';
            return $analisis;
        }

        $analisis['tablas'] = $tablas;
        $analisis['total_filas'] = array_sum($tablas);

        // Tablas indispensable del sistema
        $esenciales = ['usuarios', 'roles', 'pacientes', 'expedientes', 'citas'];
        $faltantes = array_diff($esenciales, array_keys($tablas));
        if (!empty($faltantes)) {
            $analisis['errores'][] = 'El respaldo no contiene las tablas esenciales: ' . implode(', ', $faltantes) . '.';
            return $analisis;
        }

        $analisis['valido'] = true;

        return $analisis;
    }

    /**
     * Restaura un respaldo sobre la base de datos actual.
     *
     * @return array ['sentencias','tablas','duracion','errores']
     * @throws \RuntimeException si el respaldo no se puede aplicar
     */
    public static function restaurar($ruta) {
        $analisis = self::analizar($ruta);
        if (!$analisis['valido']) {
            throw new \RuntimeException('El respaldo no es válido: ' . implode(' ', $analisis['errores']));
        }

        $db = (new Database())->getConnection();
        $informe = [
            'sentencias' => 0,
            'tablas'     => [],
            'duracion'   => 0,
            'errores'    => []
        ];

        $inicio = microtime(true);
        $archivo = @gzopen($ruta, 'rb');
        if (!$archivo) {
            throw new \RuntimeException('No se pudo abrir el archivo de respaldo.');
        }

        try {
            // La verificacion de claves foraneas se desactiva durante la restauracion
            // (el respaldo ya lo hace) y se restablece al finalizar.
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');

            $buffer = '';
            while (!gzeof($archivo)) {
                $bloque = gzread($archivo, 262144);
                if ($bloque === '') {
                    break;
                }
                $buffer .= $bloque;

                while (($posicion = strpos($buffer, ";\n")) !== false) {
                    $sentencia = substr($buffer, 0, $posicion + 1);
                    $buffer = substr($buffer, $posicion + 2);
                    self::ejecutarSentencia($db, $sentencia, $informe);
                }
            }

            if (trim($buffer) !== '') {
                self::ejecutarSentencia($db, $buffer, $informe);
            }

            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Throwable $e) {
            gzclose($archivo);
            try {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Throwable $ex) {
                // ignored
            }
            throw $e;
        }

        gzclose($archivo);

        $informe['duracion'] = round(microtime(true) - $inicio, 2);

        return $informe;
    }

    /**
     * Ejecuta una sentencia del respaldo. Ignora los bloques que solo son comentarios.
     */
    private static function ejecutarSentencia(PDO $db, $sentencia, array &$informe) {
        // Se retiran las lineas de comentario conservando el SQL mezclado con ellas
        $limpia = trim(preg_replace('/^\s*--.*$/m', '', $sentencia));
        if ($limpia === '') {
            return;
        }

        if (preg_match('/^CREATE TABLE `([^`]+)`/i', $limpia, $coincidencia)) {
            $informe['tablas'][$coincidencia[1]] = 0;
        }

        $db->exec($limpia);
        $informe['sentencias']++;

        if (preg_match('/^INSERT INTO `([^`]+)`/i', $limpia, $coincidencia)) {
            if (!isset($informe['tablas'][$coincidencia[1]])) {
                $informe['tablas'][$coincidencia[1]] = 0;
            }
            $informe['tablas'][$coincidencia[1]] += $db->query('SELECT ROW_COUNT()')->fetchColumn();
        }
    }

    /**
     * Valida la integridad de la base de datos tras una restauracion.
     *
     * Comprueba que existan las tablas del respaldo, que los conteos coincidan
     * con los del archivo y que no haya registros huerfanos.
     *
     * @param array $analisis Resultado de analizar() con las tablas esperadas
     * @return array ['valido','errores','advertencias','detalle']
     */
    public static function validarIntegridad($analisis) {
        $db = (new Database())->getConnection();
        $informe = [
            'valido'      => true,
            'errores'     => [],
            'advertencias'=> [],
            'detalle'     => []
        ];

        // 1. Las tablas del respaldo deben existir y tener el mismo numero de filas
        foreach ($analisis['tablas'] as $tabla => $filasEsperadas) {
            $existe = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES
                                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $db->quote($tabla))->fetchColumn();

            if ($existe === 0) {
                $informe['errores'][] = 'La tabla "' . $tabla . '" del respaldo no existe en la base de datos.';
                $informe['valido'] = false;
                continue;
            }

            $filasReales = (int)$db->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();

            $informe['detalle'][] = [
                'tabla'    => $tabla,
                'esperado' => (int)$filasEsperadas,
                'real'     => $filasReales,
                'coincide' => $filasReales === (int)$filasEsperadas
            ];

            if ($filasReales !== (int)$filasEsperadas) {
                $informe['errores'][] = 'La tabla "' . $tabla . '" quedó con ' . $filasReales
                    . ' registro(s) pero el respaldo contenía ' . $filasEsperadas . '.';
                $informe['valido'] = false;
            }
        }

        // 2. Integridad referencial: ningun registro debe quedar sin su padre
        $relaciones = [
            ['citas', 'id_paciente', 'pacientes', 'id_paciente', 'Citas sin paciente'],
            ['citas', 'id_usuario_psicologo', 'usuarios', 'id_usuario', 'Citas sin psicólogo asignado'],
            ['expedientes', 'id_paciente', 'pacientes', 'id_paciente', 'Expedientes sin paciente'],
            ['sesiones_clinicas', 'id_expediente', 'expedientes', 'id_expediente', 'Sesiones sin expediente'],
            ['reportes', 'id_expediente', 'expedientes', 'id_expediente', 'Informes sin expediente'],
            ['asistencias', 'id_expediente', 'expedientes', 'id_expediente', 'Asistencias sin expediente'],
            ['auditoria_acciones', 'id_usuario', 'usuarios', 'id_usuario', 'Acciones de auditoría sin usuario']
        ];

        foreach ($relaciones as $relacion) {
            list($hijo, $columnaHijo, $padre, $columnaPadre, $descripcion) = $relacion;

            $existeHijo = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES
                                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $db->quote($hijo))->fetchColumn();
            $existePadre = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES
                                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $db->quote($padre))->fetchColumn();
            if ($existeHijo === 0 || $existePadre === 0) {
                continue;
            }

            $huerfanos = (int)$db->query(
                "SELECT COUNT(*) FROM `$hijo` h
                 LEFT JOIN `$padre` p ON h.`$columnaHijo` = p.`$columnaPadre`
                 WHERE h.`$columnaHijo` IS NOT NULL AND p.`$columnaPadre` IS NULL"
            )->fetchColumn();

            if ($huerfanos > 0) {
                $informe['errores'][] = $descripcion . ': ' . $huerfanos . ' registro(s) huérfano(s).';
                $informe['valido'] = false;
            }
        }

        // 3. Catalogos minimos del sistema
        $roles = (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($roles < 3) {
            $informe['advertencias'][] = 'El respaldo restauró solo ' . $roles . ' rol(es); el sistema requiere 3.';
        }

        $admins = 0;
        try {
            $admins = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 3 AND estado = 'Activo'")->fetchColumn();
            if ($admins === 0) {
                $informe['advertencias'][] = 'No hay ningún administrador activo: usted podría quedar fuera del sistema.';
            }
        } catch (\Throwable $e) {
            // ignored
        }

        // 4. Tablas actuales que no estan en el respaldo (conservan datos previos)
        $existentes = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES
                                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $extra = array_diff($existentes, array_keys($analisis['tablas']));
        if (!empty($extra)) {
            $informe['advertencias'][] = 'Estas tablas no están en el respaldo y conservan sus datos actuales: ' . implode(', ', $extra) . '.';
        }

        return $informe;
    }

    /**
     * Lista los respaldos existentes en la carpeta (del mas reciente al mas antiguo).
     */
    public static function listar() {
        $directorio = self::directorio();

        if (!is_dir($directorio)) {
            return [];
        }

        $archivos = glob($directorio . DIRECTORY_SEPARATOR . 'respaldo_*.sql.gz');
        if ($archivos === false || empty($archivos)) {
            return [];
        }

        rsort($archivos);

        $listado = [];
        foreach ($archivos as $ruta) {
            $nombre = basename($ruta);
            $listado[] = [
                'archivo' => $nombre,
                'ruta'    => $ruta,
                'tamano'  => (int)filesize($ruta),
                'fecha'   => (int)filemtime($ruta),
                'etiqueta'=> date('d/m/Y H:i:s', (int)filemtime($ruta))
            ];
        }

        return $listado;
    }

    /**
     * Elimina los respaldos mas antiguos conservando el numero indicado.
     * Devuelve los nombres de los archivos eliminados.
     */
    public static function purgar($maximo = self::MAXIMO_RESPALDOS) {
        $listado = self::listar();
        $eliminados = [];

        if (count($listado) > $maximo) {
            foreach (array_slice($listado, $maximo) as $respaldo) {
                if (@unlink($respaldo['ruta'])) {
                    $eliminados[] = $respaldo['archivo'];
                }
            }
        }

        return $eliminados;
    }

    /**
     * Elimina un respaldo indicado.
     */
    public static function eliminar($nombreArchivo) {
        $ruta = self::rutaSegura($nombreArchivo);
        if ($ruta === null) {
            return false;
        }
        return @unlink($ruta);
    }

    /**
     * Construye la ruta de un respaldo validando el nombre (evita path traversal).
     * Devuelve null si el nombre no es valido o el archivo no existe.
     */
    public static function rutaSegura($nombreArchivo) {
        $nombreArchivo = basename((string)$nombreArchivo);

        if (!preg_match('/^respaldo_[A-Za-z0-9_\-]+\.sql\.gz$/', $nombreArchivo)) {
            return null;
        }

        $ruta = self::directorio() . DIRECTORY_SEPARATOR . $nombreArchivo;

        return is_file($ruta) ? $ruta : null;
    }

    /**
     * Formatea un tamano en bytes de forma legible.
     */
    public static function formatearTamano($bytes) {
        $bytes = (float)$bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / 1048576, 2) . ' MB';
    }
}
