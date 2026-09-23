<?php
session_start();

// Configuración y utilidades base (rutas absolutas para no depender del CWD)
require_once __DIR__ . '/../app/Config/App.php';
require_once __DIR__ . '/../app/Core/Logger.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/App.php';
require_once __DIR__ . '/../app/Core/Flash.php';

use App\Config\App;
use App\Core\Logger;

// Definir BASE_URL dinámicamente
define('BASE_URL', App::baseUrl());

// Inicializar Logger
Logger::init();

// Detectar entorno: local = desarrollo, otro host = producción
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1'])
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');

date_default_timezone_set('America/El_Salvador');

// Mostrar errores solo en local
ini_set('display_errors', $isLocal ? 1 : 0);
ini_set('display_startup_errors', $isLocal ? 1 : 0);
error_reporting(E_ALL);

// Manejador global de excepciones
set_exception_handler(function (\Throwable $e) {
    Logger::exception($e);
    if (ini_get('display_errors')) {
        echo "<h1>Error del sistema</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    } else {
        echo "<h1>Error interno del servidor</h1><p>Por favor intente más tarde.</p>";
    }
});

// Manejador global de errores fatales
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        Logger::error('Fatal error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Inicializar la Aplicación (El Enrutador)
$app = new \App\Core\App();
?>