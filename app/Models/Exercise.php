<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Exercise extends Model
{
    protected static string $table = 'exercises';

    public static function activeOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM exercises WHERE is_active = 1 ORDER BY name');
    }
}
