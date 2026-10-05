<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Global error/exception handler.
 *
 * Boundary enforced here: full exception details (message, file, line,
 * stack trace) are ALWAYS written to the server-side log, and are shown to
 * the browser ONLY when APP_DEBUG=true. In production (APP_DEBUG=false)
 * the browser gets a generic message, deliberately avoiding disclosure of
 * file paths, SQL, or stack traces to an end user.
 */
final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        set_exception_handler(function (Throwable $e) use ($debug) {
            Logger::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            http_response_code(500);
            if ($debug) {
                echo '<pre style="padding:1rem;background:#1e1e1e;color:#f66;overflow:auto;">';
                echo htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                echo '<!doctype html><html><head><title>Something went wrong</title></head><body>'
                    . '<h1>Something went wrong</h1><p>Please try again later. If this continues, contact the clinic.</p></body></html>';
            }
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
