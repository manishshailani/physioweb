<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generation and verification.
 *
 * Design: one token per session (not per-form), stored server-side in the
 * session and compared with hash_equals() to avoid timing side-channels.
 * A new token is minted once per session (not regenerated per request),
 * which allows multiple forms/tabs open at once to all submit successfully
 * — a common usability bug with per-request tokens.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function verify(?string $submitted): bool
    {
        $expected = Session::get(self::SESSION_KEY);
        if (!is_string($expected) || !is_string($submitted) || $submitted === '') {
            return false;
        }
        return hash_equals($expected, $submitted);
    }
}
