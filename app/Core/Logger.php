<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal file-based logger. Writes to storage/logs/app.log.
 *
 * Purpose: capture operational errors (DB failures, mail failures, unhandled
 * exceptions) without ever echoing sensitive details (stack traces, DSNs,
 * credentials) to the browser — see Core/ErrorHandler for the HTTP side of
 * that boundary.
 */
final class Logger
{
    private static ?string $path = null;

    public static function init(string $path): void
    {
        self::$path = $path;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $path = self::$path ?? sys_get_temp_dir() . '/physioweb-app.log';
        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
            PHP_EOL
        );
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
