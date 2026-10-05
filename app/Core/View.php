<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Minimal server-side template renderer using plain PHP files as templates
 * (no templating DSL). This keeps the dependency list small and the
 * behavior of every template fully transparent (it is just PHP).
 *
 * XSS boundary: View::e() is the single sanctioned place output is escaped.
 * Every template MUST pass dynamic data through e() (or ev() for the
 * "void-safe" variant used in attributes) before printing it. Raw() exists
 * only for trusted, pre-escaped HTML fragments (e.g. a WYSIWYG field that
 * was itself sanitized at save time — see Services/HtmlSanitizer).
 */
final class View
{
    private static string $basePath = '';

    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    /**
     * Render a template with the given data and return the HTML as a string.
     * $template uses dot notation, e.g. "admin.patients.index" maps to
     * app/Views/admin/patients/index.php
     */
    public static function render(string $template, array $data = []): string
    {
        $path = self::resolvePath($template);
        if (!is_file($path)) {
            throw new \RuntimeException("View template not found: {$template} ({$path})");
        }

        $renderer = function () use ($path, $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            try {
                include $path;
                return ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        };

        return $renderer();
    }

    /** Render $template wrapped inside a layout, exposing the rendered body as $content. */
    public static function renderWithLayout(string $layout, string $template, array $data = []): string
    {
        $content = self::render($template, $data);
        return self::render($layout, array_merge($data, ['content' => $content]));
    }

    /** Include a partial from within another template (same data scope as caller). */
    public static function partial(string $template, array $data = []): void
    {
        echo self::render($template, $data);
    }

    private static function resolvePath(string $template): string
    {
        $relative = str_replace('.', '/', $template) . '.php';
        return self::$basePath . '/' . $relative;
    }

    /** Escape for HTML body/attribute context. */
    public static function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Output already-sanitized/trusted HTML verbatim. Use sparingly and only for sanitized content. */
    public static function raw(mixed $value): string
    {
        return (string) ($value ?? '');
    }

    public static function money(int|float $amount): string
    {
        return number_format((float) $amount, 2);
    }

    public static function dateFormat(?string $date, string $format = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $ts = strtotime($date);
        return $ts === false ? '' : date($format, $ts);
    }
}
