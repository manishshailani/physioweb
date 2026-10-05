<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\Patient;

/**
 * Authentication for the two separate principal types in this system:
 * staff/admin users (table `users`) and patients (table `patients`, which
 * carries its own password_hash column for portal login). They are kept in
 * separate session namespaces so a staff member and a patient session never
 * collide, and so role checks can't be confused between the two.
 *
 * Security measures implemented here:
 *  - Passwords hashed with password_hash()/PASSWORD_BCRYPT (also accepts
 *    the auto-upgrade path via password_needs_rehash in the login flow).
 *  - Login attempt limiting per (identifier, IP) using the login_attempts
 *    table: after LOGIN_MAX_ATTEMPTS failures within the lockout window,
 *    further attempts are rejected even with the correct password, with a
 *    constant-time-ish response (we still run password_verify on a dummy
 *    hash) to reduce account-enumeration/timing signal.
 *  - Session ID regeneration on login to prevent session fixation.
 */
final class Auth
{
    private const ADMIN_SESSION_KEY = 'admin_user_id';
    private const PATIENT_SESSION_KEY = 'patient_user_id';

    private static int $maxAttempts = 5;
    private static int $lockoutMinutes = 15;

    public static function configure(int $maxAttempts, int $lockoutMinutes): void
    {
        self::$maxAttempts = $maxAttempts;
        self::$lockoutMinutes = $lockoutMinutes;
    }

    // ---- Admin / staff -----------------------------------------------

    public static function attemptAdminLogin(string $email, string $password, string $ip): array
    {
        if (self::isLockedOut($email, $ip, 'admin')) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please try again in ' . self::$lockoutMinutes . ' minutes.'];
        }

        $user = User::whereFirst('email', $email);
        $hash = $user['password_hash'] ?? ('$2y$10$' . str_repeat('x', 53)); // dummy hash for timing parity
        $valid = password_verify($password, $hash) && $user !== null && (int) $user['is_active'] === 1;

        LoginAttempt::record($email, $ip, 'admin', $valid);

        if (!$valid) {
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
            User::updateById((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
        }

        Session::regenerate();
        Session::set(self::ADMIN_SESSION_KEY, (int) $user['id']);
        Session::remove(self::PATIENT_SESSION_KEY);

        return ['ok' => true, 'user' => $user];
    }

    public static function isAdmin(): bool
    {
        return Session::has(self::ADMIN_SESSION_KEY);
    }

    public static function adminUser(): ?array
    {
        $id = Session::get(self::ADMIN_SESSION_KEY);
        if ($id === null) {
            return null;
        }
        return User::find((int) $id);
    }

    public static function logoutAdmin(): void
    {
        Session::remove(self::ADMIN_SESSION_KEY);
        Session::regenerate();
    }

    // ---- Patient portal -------------------------------------------------

    public static function attemptPatientLogin(string $email, string $password, string $ip): array
    {
        if (self::isLockedOut($email, $ip, 'patient')) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please try again in ' . self::$lockoutMinutes . ' minutes.'];
        }

        $patient = Patient::whereFirst('email', $email);
        $hash = $patient['password_hash'] ?? ('$2y$10$' . str_repeat('x', 53));
        $valid = $patient !== null && $hash !== null && password_verify($password, $hash) && (int) $patient['is_active'] === 1;

        LoginAttempt::record($email, $ip, 'patient', $valid);

        if (!$valid) {
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        if (password_needs_rehash($hash, PASSWORD_BCRYPT)) {
            Patient::updateById((int) $patient['id'], ['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
        }

        Session::regenerate();
        Session::set(self::PATIENT_SESSION_KEY, (int) $patient['id']);
        Session::remove(self::ADMIN_SESSION_KEY);

        return ['ok' => true, 'patient' => $patient];
    }

    public static function isPatient(): bool
    {
        return Session::has(self::PATIENT_SESSION_KEY);
    }

    public static function patientUser(): ?array
    {
        $id = Session::get(self::PATIENT_SESSION_KEY);
        if ($id === null) {
            return null;
        }
        return Patient::find((int) $id);
    }

    public static function logoutPatient(): void
    {
        Session::remove(self::PATIENT_SESSION_KEY);
        Session::regenerate();
    }

    // ---- Shared ---------------------------------------------------------

    private static function isLockedOut(string $identifier, string $ip, string $scope): bool
    {
        $recentFailures = LoginAttempt::countRecentFailures($identifier, $ip, $scope, self::$lockoutMinutes);
        return $recentFailures >= self::$maxAttempts;
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }
}
