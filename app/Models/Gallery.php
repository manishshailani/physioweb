<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Gallery extends Model
{
    protected static string $table = 'gallery';

    public static function byCategory(?string $category = null): array
    {
        if ($category === null || $category === 'all') {
            return Database::fetchAll('SELECT * FROM gallery ORDER BY display_order, created_at DESC');
        }
        return Database::fetchAll(
            'SELECT * FROM gallery WHERE category = :c ORDER BY display_order, created_at DESC',
            ['c' => $category]
        );
    }
}
