<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Logger;
use App\Libraries\Respaldo;

// No hay autoloader: la libreria de respaldos se carga de forma explicita
require_once __DIR__ . '/../Libraries/Respaldo.php';

/**
 * RespaldoController: gestion de las copias de seguridad de la base de datos.
 *
 * Solo el Administrador (rol 3) tiene acceso. Los archivos se almacenan fuera
 * del docroot y se descargan a traves de esta ruta (nunca por URL directa).
 */
class RespaldoController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requiere('backups');
    }

    /**
     * Genera el token con el que viaja el nombre del archivo en la URL.
     *
     * Los puntos se codifican (%2E) para que la URL no contenga de forma literal
     * la extension .sql: algunos servidores rechazan con "Forbidden" las
     * peticiones cuyo URL incluye un volcado de base de datos.
     */
    public static function tokenArchivo($nombreArchivo) {
        return str_replace('.', '%2E', rawurlencode((string)$nombreArchivo));
    }

    /**
     * Resuelve el nombre del archivo de respaldo.
     *
     * Se recibe por query string (?archivo=...) porque el nombre contiene la
     * extension .sql y algunos servidores (y la regla del .htaccess que bloquea
     * archivos sensibles) rechazan las URLs que la incluyen en su ruta.
     * Se mantiene el parametro de ruta por compatibilidad con enlaces anteriores.
     */
    private function resolverArchivo($archivo = null) {
        if (!empty($_GET['archivo'])) {
            return trim((string)$_GET['archivo']);
        }
        if (!empty($_POST['archivo'])) {
            return trim((string)$_POST['archivo']);
        }
        return trim((string)$archivo);
    }

    // Listado de respaldos y boton de generacion
    public function index() {
        $this->vista('respaldo/index', [
            'respaldos'   => Respaldo::listar(),
            'directorio'  => Respaldo::directorio(),
            'maximo'      => Respaldo::MAXIMO_RESPALDOS,
            'flash'       => Flash::all()
        ]);
    }

    // Genera un respaldo manual
    public function generar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/respaldo');
        }

        try {
            $resultado = Respaldo::generar();
        } catch (\Throwable $e) {
            Logger::error('Error al generar el respaldo', ['error' => $e->getMessage()]);
            Flash::error('No se pudo generar el respaldo: ' . $e->getMessage());
            $this->redirect(BASE_URL . '/respaldo');
        }

        // Se conservan solo los respaldos mas recientes
        $purgados = Respaldo::purgar();

        $descripcion = 'Respaldo generado: ' . $resultado['archivo']
            . ' (' . Respaldo::formatearTamano($resultado['tamano']) . ', '
            . $resultado['tablas'] . ' tablas, ' . $resultado['duracion'] . ' s).';
        $this->auditar('CREAR', 'respaldo', 0, $descripcion);

        Logger::info('Respaldo generado', [
            'archivo' => $resultado['archivo'],
            'tamano' => $resultado['tamano'],
            'duracion' => $resultado['duracion'],
            'usuario' => $_SESSION['usuario_id']
        ]);

        Flash::success('Respaldo generado correctamente: ' . $resultado['archivo']
            . ' (' . Respaldo::formatearTamano($resultado['tamano']) . ' en ' . $resultado['duracion'] . ' s).');

        if (!empty($purgados)) {
            Flash::info('Se eliminaron ' . count($purgados) . ' respaldo(s) antiguo(s) para conservar el límite de '
                . Respaldo::MAXIMO_RESPALDOS . '.');
        }

        $this->redirect(BASE_URL . '/respaldo');
    }

    // Descarga un respaldo (solo Administrador, y solo desde la carpeta de respaldos)
    public function descargar($archivo = null) {
        $ruta = Respaldo::rutaSegura($this->resolverArchivo($archivo));

        if ($ruta === null) {
            Flash::error('El respaldo solicitado no existe o el nombre no es válido.');
            $this->redirect(BASE_URL . '/respaldo');
        }

        Logger::info('Respaldo descargado', ['archivo' => basename($ruta), 'usuario' => $_SESSION['usuario_id']]);

        $nombre = basename($ruta);
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, must-revalidate');
        readfile($ruta);
        exit;
    }

    // Elimina un respaldo
    public function eliminar($archivo = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/respaldo');
        }

        $nombre = basename($this->resolverArchivo($archivo));

        if (!Respaldo::eliminar($nombre)) {
            Flash::error('No se pudo eliminar el respaldo (no existe o el nombre no es válido).');
            $this->redirect(BASE_URL . '/respaldo');
        }

        $this->auditar('ELIMINAR', 'respaldo', 0, 'Respaldo eliminado: ' . $nombre);
        Logger::info('Respaldo eliminado', ['archivo' => $nombre, 'usuario' => $_SESSION['usuario_id']]);
        Flash::success('Respaldo eliminado correctamente.');

        $this->redirect(BASE_URL . '/respaldo');
    }

    // Restaura un respaldo: GET muestra la confirmacion, POST ejecuta la restauracion
    public function restaurar($archivo = null) {
        $ruta = Respaldo::rutaSegura($this->resolverArchivo($archivo));

        if ($ruta === null) {
            Flash::error('El respaldo solicitado no existe o el nombre no es válido.');
            $this->redirect(BASE_URL . '/respaldo');
        }

        $nombre = basename($ruta);
        $analisis = Respaldo::analizar($ruta);

        if (!$analisis['valido']) {
            Flash::error('No se puede restaurar: ' . implode(' ', $analisis['errores']));
            $this->redirect(BASE_URL . '/respaldo');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Confirmacion explicita: el administrador debe escribir el nombre del archivo
            $confirmacion = trim($_POST['confirmacion'] ?? '');
            $acepta = isset($_POST['aceptar']);

            if (strcasecmp($confirmacion, $nombre) !== 0) {
                Flash::error('Debes escribir exactamente el nombre del archivo para confirmar la restauración.');
                $this->redirect(BASE_URL . '/respaldo/restaurar?archivo=' . self::tokenArchivo($nombre));
            }
            if (!$acepta) {
                Flash::error('Debes marcar la casilla para confirmar la restauración.');
                $this->redirect(BASE_URL . '/respaldo/restaurar?archivo=' . self::tokenArchivo($nombre));
            }

            // Red de seguridad: se genera un respaldo del estado actual antes de tocar nada
            $seguridad = null;
            try {
                $seguridad = Respaldo::generar();
            } catch (\Throwable $e) {
                Logger::error('No se pudo crear el respaldo de seguridad previo', ['error' => $e->getMessage()]);
                Flash::error('No se pudo crear el respaldo de seguridad previo: ' . $e->getMessage()
                    . ' La restauración se canceló para no dejar el sistema sin protección.');
                $this->redirect(BASE_URL . '/respaldo/restaurar?archivo=' . self::tokenArchivo($nombre));
            }

            Logger::info('Restauracion iniciada', [
                'respaldo' => $nombre,
                'seguridad' => $seguridad['archivo'],
                'usuario' => $_SESSION['usuario_id']
            ]);

            // Se aplica el respaldo
            try {
                $informe = Respaldo::restaurar($ruta);
            } catch (\Throwable $e) {
                Logger::error('Error durante la restauracion', ['respaldo' => $nombre, 'error' => $e->getMessage()]);
                $this->revertirConRespaldo($seguridad, 'la restauración falló: ' . $e->getMessage());
                $this->redirect(BASE_URL . '/respaldo');
            }

            // Validacion de integridad de la informacion restaurada
            $validacion = Respaldo::validarIntegridad($analisis);

            if (!$validacion['valido']) {
                Logger::error('La restauracion no supero la validacion de integridad', [
                    'respaldo' => $nombre,
                    'errores' => $validacion['errores']
                ]);
                $this->revertirConRespaldo($seguridad, 'la validación de integridad falló');
                $this->redirect(BASE_URL . '/respaldo');
            }

            // Respaldo restaurado correctamente
            $this->auditar('RESTAURAR', 'respaldo', 0, 'Base de datos restaurada desde ' . $nombre
                . '. Tablas verificadas: ' . count($validacion['detalle'])
                . '. Respaldo de seguridad previo: ' . $seguridad['archivo'] . '.');

            Logger::info('Restauracion completada', [
                'respaldo' => $nombre,
                'sentencias' => $informe['sentencias'],
                'duracion' => $informe['duracion'],
                'usuario' => $_SESSION['usuario_id']
            ]);

            Flash::success('Restauración completada correctamente desde ' . $nombre
                . ' (' . $informe['sentencias'] . ' sentencias en ' . $informe['duracion'] . ' s).'
                . ' La integridad de los datos fue verificada.');

            if (!empty($validacion['advertencias'])) {
                Flash::warning(implode(' ', $validacion['advertencias']));
            }

            $this->redirect(BASE_URL . '/respaldo');
        }

        // Pantalla de confirmacion
        $this->vista('respaldo/restaurar', [
            'respaldo'  => [
                'archivo' => $nombre,
                'ruta'    => $ruta,
                'tamano'  => (int)filesize($ruta),
                'fecha'   => (int)filemtime($ruta)
            ],
            'analisis'  => $analisis,
            'flash'     => Flash::all()
        ]);
    }

    /**
     * Revierte la restauracion aplicando el respaldo de seguridad.
     */    private function revertirConRespaldo($seguridad, $motivo) {
        try {
            Respaldo::restaurar($seguridad['ruta']);
            $this->auditar('REVERTIR', 'respaldo', 0, 'Restauración revertida automaticamente desde ' . $seguridad['archivo'] . ' porque ' . $motivo . '.');
            Flash::error('La restauración no se completó porque ' . $motivo
                . '. El sistema se revirtió automáticamente con el respaldo de seguridad ' . $seguridad['archivo'] . '.');
        } catch (\Throwable $e) {
            Logger::critical('No se pudo revertir la restauracion automaticamente', ['error' => $e->getMessage()]);
            Flash::error('La restauración no se completó porque ' . $motivo
                . '. ADEMAS la reversión automática falló: ' . $e->getMessage()
                . '. Restaure manualmente el respaldo ' . $seguridad['archivo'] . '.');
        }
    }

    // Registrar en la tabla de auditoría (sin interrumpir la operación si falla)
}
