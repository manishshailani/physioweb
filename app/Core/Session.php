<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session lifecycle management.
 *
 * Security properties enforced here:
 *  - Cookies are HttpOnly + SameSite=Lax always, and Secure when the request
 *    is served over HTTPS (detected via HTTPS/X-Forwarded-Proto so it also
 *    works behind a TLS-terminating reverse proxy).
 *  - Session ID is regenerated on privilege changes (login) by the caller
 *    (see AuthService) to prevent session fixation.
 *  - Idle timeout: if SESSION_LIFETIME_MINUTES has elapsed since the last
 *    request, the session is destroyed and the user is treated as logged
 *    out. This is enforced on every request in start().
 */
final class Session
{
    private static bool $started = false;

    public static function start(string $name, int $lifetimeMinutes): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $secure = (($_SERVER['HTTPS'] ?? '') === 'on')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0, // session cookie; idle timeout is enforced server-side below
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;

        $now = time();
        $lastActivity = self::get('_last_activity');
        if ($lastActivity !== null && ($now - (int) $lastActivity) > ($lifetimeMinutes * 60)) {
            self::destroy();
            session_start();
        }
        self::set('_last_activity', $now);

        // Regenerate the session ID periodically (every 15 minutes) to limit
        // the window in which a stolen session ID remains useful, without
        // regenerating on every single request (which would break concurrent
        // AJAX requests mid-flight).
        $lastRegeneration = self::get('_last_regeneration');
        if ($lastRegeneration === null || ($now - (int) $lastRegeneration) > 900) {
            session_regenerate_id(true);
            self::set('_last_regeneration', $now);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Read a flash value and remove it (one-time read, used after redirects). */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function regenerate(): void
    {
        // Guard against being called with no active session (e.g. a CLI
        // script or test harness that uses Auth directly without Session::
        // start() having run first) — session_regenerate_id() emits a
        // warning rather than failing silently in that case otherwise.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            session_destroy();
        }
    }
}
