<?php
namespace App\Core;

class Logger {
    private static string $logDir;
    private static array $levels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    private static string $minLevel = 'error';

    public static function init(string $logDir = null, string $minLevel = 'error'): void {
        self::$logDir = $logDir ?? __DIR__ . '/../../logs';
        self::$minLevel = strtolower($minLevel);
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    public static function log(string $level, string $message, array $context = []): void {
        $level = strtolower($level);
        if (!isset(self::$levels[$level])) {
            $level = 'error';
        }
        if (self::$levels[$level] < self::$levels[self::$minLevel]) {
            return;
        }
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] [".strtoupper($level)."] {$message}{$contextStr}" . PHP_EOL;
        $file = self::$logDir . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $message, array $context = []): void {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void {
        self::log('critical', $message, $context);
    }

    public static function exception(\Throwable $e, array $context = []): void {
        $context['exception'] = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        self::log('error', 'Uncaught exception: ' . $e->getMessage(), $context);
    }
}