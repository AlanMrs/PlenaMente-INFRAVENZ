<?php
namespace App\Core;

class App {
    // Definimos qué cargará por defecto si el usuario solo entra a la raíz
    protected $controladorActual = 'DashboardController';
    protected $metodoActual = 'index';
    protected $parametros = [];

    public function __construct() {
        $url = $this->parseUrl();

        // 1. BUSCAR EL CONTROLADOR
        // Verificamos si existe el archivo del controlador en la carpeta Controllers
        if (isset($url[0]) && file_exists('../app/Controllers/' . ucwords($url[0]) . 'Controller.php')) {
            $this->controladorActual = ucwords($url[0]) . 'Controller';
            unset($url[0]);
        }

        // Requerimos el controlador y lo instanciamos
        require_once '../app/Controllers/' . $this->controladorActual . '.php';
        // Agregamos el namespace dinámicamente
        $claseControlador = "\\App\\Controllers\\" . $this->controladorActual;
        $this->controladorActual = new $claseControlador;

        // BUSCAR EL MÉTODO
        // Verificamos si la URL pide ejecutar una función específica (ej: /paciente/editar)
        if (isset($url[1])) {
            if (method_exists($this->controladorActual, $url[1])) {
                $this->metodoActual = $url[1];
                unset($url[1]);
            }
        }

        // OBTENER LOS PARÁMETROS
        // Si hay más cosas en la URL (ej: el ID del paciente /paciente/editar/5), los guardamos
        $this->parametros = $url ? array_values($url) : [];

        // EJECUTAR
        // Llamamos al controlador y al método, pasándole los parámetros si existen
        call_user_func_array([$this->controladorActual, $this->metodoActual], $this->parametros);
    }

    // Función para limpiar y dividir la URL
    public function parseUrl() {
        if (isset($_GET['url'])) {
            // Elimina la barra final, limpia caracteres raros y divide por barras
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
?>