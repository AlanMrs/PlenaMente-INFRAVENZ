<?php
namespace App\Core;

use App\Core\Flash;
use App\Core\Logger;

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