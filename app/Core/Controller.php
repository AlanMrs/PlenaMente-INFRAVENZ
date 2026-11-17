<?php
namespace App\Core;

use App\Core\Flash;
use App\Core\Logger;

// No hay autoloader: el nucleo de permisos se carga de forma explicita
require_once __DIR__ . '/Permisos.php';

class Controller {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Método para cargar un modelo (Ej: interactuar con la Base de Datos)
    public function modelo($modelo) {
        require_once __DIR__ . '/../Models/' . $modelo . '.php';
        $claseModelo = "\\App\\Models\\" . $modelo;
        return new $claseModelo();
    }

    /**
     * Registra una accion critica en la tabla de auditoria del sistema.
     *
     * Nunca interrumpe la operacion de negocio: si la auditoria falla, solo
     * queda constancia en el log de la aplicacion.
     *
     * @param string $accion      CREAR, ACTUALIZAR, ELIMINAR, DESACTIVAR, RESTAURAR...
     * @param string $entidad     paciente, cita, expediente, usuario, respaldo...
     * @param int    $id_entidad  Identificador del registro afectado (0 si no aplica)
     * @param string $descripcion Detalle legible de la accion
     */
    protected function auditar($accion, $entidad, $id_entidad, $descripcion) {
        try {
            $this->modelo('Auditoria')->registrar(
                (int)($_SESSION['usuario_id'] ?? 0),
                $accion,
                $entidad,
                $id_entidad,
                $descripcion
            );
        } catch (\Throwable $e) {
            Logger::error('No se pudo registrar la acción en auditoría', [
                'accion' => $accion,
                'entidad' => $entidad,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Exige un permiso de modulo segun el rol de la sesion (ver Core\Permisos).
     * Si el usuario no tiene acceso, se registra el intento y se redirige.
     */
    protected function requiere($modulo, $mensaje = null) {
        if (!isset($_SESSION['usuario_id'])) {
            $this->redirect(BASE_URL . '/auth/login');
        }

        if (!Permisos::puede($modulo)) {
            Logger::warning('Acceso denegado por rol', [
                'usuario_id' => $_SESSION['usuario_id'],
                'id_rol' => Permisos::rolActual(),
                'modulo' => $modulo
            ]);
            Flash::error($mensaje ?? 'No tienes permisos para acceder a esta sección.');
            $this->redirect(BASE_URL . '/dashboard');
        }
    }

    // Método para cargar una vista visual (HTML)
    public function vista($vista, $datos = []) {
        $datos['flash'] = $datos['flash'] ?? Flash::all();
        // Ruta capturada ANTES del extract() para que ningún dato de la vista
        // pueda sobrescribir la variable $vista que define la ruta a cargar
        $__rutaVista = __DIR__ . '/../Views/' . $vista . '.php';
        // Verifica si el archivo de la vista existe
        if (file_exists($__rutaVista)) {
            // Extrae los datos para que puedan usarse como variables en la vista
            extract($datos);
            require_once $__rutaVista;
        } else {
            Logger::error("Vista no encontrada: {$vista}");
            die("La vista '$vista' no existe en el sistema.");
        }
    }

    protected function redirect(string $url, int $statusCode = 302): void {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    protected function back(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        $this->redirect($referer);
    }
}
?>