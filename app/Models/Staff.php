<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\IdGenerator;
use App\Core\Model;

final class Staff extends Model
{
    protected static string $table = 'staff';

    public static function register(array $data): array
    {
        Database::beginTransaction();
        try {
            $code = IdGenerator::next('STF');
            $data['staff_code'] = $code;
            $data['created_at'] = Database::now();
            $data['updated_at'] = Database::now();
            $id = Database::insert('staff', $data);
            Database::commit();
            return ['id' => $id, 'staff_code' => $code];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function activeByRole(string $role): array
    {
        return Database::fetchAll(
            'SELECT * FROM staff WHERE is_active = 1 AND role = :role ORDER BY first_name',
            ['role' => $role]
        );
    }

    public static function allActive(): array
    {
        return Database::fetchAll('SELECT * FROM staff WHERE is_active = 1 ORDER BY first_name');
    }

    public static function fullName(array $staff): string
    {
        return trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
    }
}
