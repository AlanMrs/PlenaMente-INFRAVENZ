<?php
namespace App\Config;

class App {
    public static function baseUrl(): string {
        // Detecta esquema (compatible con proxy SSL de Hostinger)
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // dirname(SCRIPT_NAME) ya es la base real en cualquier entorno:
        // Local: /plenamente-infravenz/public -> http://localhost/plenamente-infravenz/public
        // Hostinger con docroot en /public: / -> https://plenamente.dsoftin.net
        // Hostinger con proyecto completo: /public -> https://plenamente.dsoftin.net/public
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $dir = ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : $dir;

        return "{$scheme}://{$host}{$dir}";
    }

    public static function url(string $path = ''): string {
        return self::baseUrl() . ($path ? '/' . ltrim($path, '/') : '');
    }
}