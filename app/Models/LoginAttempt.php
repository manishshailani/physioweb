<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Backs login-attempt-limiting in App\Core\Auth. */
final class LoginAttempt extends Model
{
    protected static string $table = 'login_attempts';

    public static function record(string $identifier, string $ip, string $scope, bool $success): void
    {
        Database::insert('login_attempts', [
            'identifier' => mb_strtolower($identifier),
            'ip_address' => $ip,
            'scope' => $scope,
            'success' => $success ? 1 : 0,
            'created_at' => Database::now(),
        ]);
    }

    public static function countRecentFailures(string $identifier, string $ip, string $scope, int $withinMinutes): int
    {
        $since = date('Y-m-d H:i:s', time() - $withinMinutes * 60);
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = :identifier AND ip_address = :ip AND scope = :scope
               AND success = 0 AND created_at >= :since',
            [
                'identifier' => mb_strtolower($identifier),
                'ip' => $ip,
                'scope' => $scope,
                'since' => $since,
            ]
        );
    }
}
