<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** CMS/admin login accounts. */
final class User extends Model
{
    protected static string $table = 'users';

    public static function activeAdmins(): array
    {
        return \App\Core\Database::fetchAll(
            "SELECT * FROM users WHERE is_active = 1 ORDER BY name"
        );
    }

    public static function touchLastLogin(int $id): void
    {
        static::updateById($id, ['last_login_at' => \App\Core\Database::now()]);
    }
}
