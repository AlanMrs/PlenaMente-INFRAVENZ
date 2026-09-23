<?php
namespace App\Core;

class Flash {
    const KEY = '__flash_messages';

    public static function set(string $type, string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::KEY][$type][] = $message;
    }

    public static function success(string $message): void {
        self::set('success', $message);
    }

    public static function error(string $message): void {
        self::set('error', $message);
    }

    public static function warning(string $message): void {
        self::set('warning', $message);
    }

    public static function info(string $message): void {
        self::set('info', $message);
    }

    public static function get(string $type): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $messages = $_SESSION[self::KEY][$type] ?? [];
        unset($_SESSION[self::KEY][$type]);
        return $messages;
    }

    public static function all(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $all = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        return $all;
    }

    public static function has(string $type): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION[self::KEY][$type]);
    }

    public static function output(): string {
        $html = '';
        foreach (self::all() as $type => $messages) {
            foreach ($messages as $msg) {
                $alertClass = match($type) {
                    'success' => 'alert-success',
                    'error' => 'alert-danger',
                    'warning' => 'alert-warning',
                    'info' => 'alert-info',
                    default => 'alert-secondary'
                };
                $html .= "<div class=\"alert {$alertClass} alert-dismissible fade show\" role=\"alert\">";
                $html .= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
                $html .= "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
                $html .= "</div>";
            }
        }
        return $html;
    }
}