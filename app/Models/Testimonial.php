<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Testimonial extends Model
{
    protected static string $table = 'testimonials';

    public static function approved(): array
    {
        return Database::fetchAll("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY approved_at DESC");
    }

    public static function pending(): array
    {
        return Database::fetchAll("SELECT * FROM testimonials WHERE status = 'pending' ORDER BY created_at");
    }

    public static function approve(int $id): void
    {
        static::updateById($id, ['status' => 'approved', 'approved_at' => Database::now()]);
    }

    public static function reject(int $id): void
    {
        static::updateById($id, ['status' => 'rejected']);
    }
}
