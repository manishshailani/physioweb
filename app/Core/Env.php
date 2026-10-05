<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env file loader.
 *
 * Purpose: populate getenv()/$_ENV with key=value pairs from a .env file so
 * the rest of the application can read configuration through Env::get().
 * We deliberately avoid a third-party dependency (vlucas/phpdotenv) for this
 * single, well-understood task to keep the dependency surface small.
 *
 * Assumptions:
 *  - The .env file uses simple KEY=VALUE lines, optional quotes, and '#'
 *    comments. It does not support multi-line values or variable
 *    interpolation (e.g. ${OTHER_VAR}) — not required by this project.
 *
 * Side effects: mutates $_ENV / $_SERVER / putenv() for the current process.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path) || !is_readable($path)) {
            // Fall back silently to real environment variables (e.g. set by
            // the web server or a container orchestrator) — this is not an
            // error condition in production.
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            // Strip matching surrounding quotes.
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }
            $_ENV[$key] = $_ENV[$key] ?? $value;
            $_SERVER[$key] = $_SERVER[$key] ?? $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        // Normalize common boolean strings since .env has no native type.
        $lower = strtolower((string) $value);
        if (in_array($lower, ['true', '(true)'], true)) {
            return true;
        }
        if (in_array($lower, ['false', '(false)'], true)) {
            return false;
        }
        if ($lower === 'null' || $lower === '(null)') {
            return null;
        }
        return $value;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }
        return (int) $value;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
