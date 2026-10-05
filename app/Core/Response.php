<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small HTTP response helper for redirects and JSON output. Views render
 * directly to stdout via View::render() (see Controller::view()); this
 * class covers the other response shapes controllers need.
 */
final class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function notFound(string $message = 'Page not found'): never
    {
        http_response_code(404);
        echo '<!doctype html><html><head><title>404</title></head><body>'
            . '<h1>404</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        exit;
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        http_response_code(403);
        echo '<!doctype html><html><head><title>403</title></head><body>'
            . '<h1>403</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        exit;
    }

    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0'); // deprecated header; modern browsers use CSP instead
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: blob:; "
            . "connect-src 'self';"
        );
    }
}
