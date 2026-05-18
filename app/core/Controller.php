<?php
namespace App\Core;

class Controller {
    
    // Método para cargar un modelo (Ej: interactuar con la Base de Datos)
    public function modelo($modelo) {
        require_once '../app/Models/' . $modelo . '.php';
        $claseModelo = "\\App\\Models\\" . $modelo;
        return new $claseModelo();
    }

    // Método para cargar una vista visual (HTML)
    public function vista($vista, $datos = []) {
        // Verifica si el archivo de la vista existe
        if (file_exists('../app/Views/' . $vista . '.php')) {
            // Extrae los datos para que puedan usarse como variables en la vista
            extract($datos);
            require_once '../app/Views/' . $vista . '.php';
        } else {
            die("La vista '$vista' no existe en el sistema.");
        }
    }
}
?>