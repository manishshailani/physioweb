<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Package extends Model
{
    protected static string $table = 'packages';

    public static function activeOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM packages WHERE is_active = 1 ORDER BY price');
    }

    public static function discountedPrice(array $package): float
    {
        $price = (float) $package['price'];
        $discount = (float) $package['discount_percent'];
        return round($price - ($price * $discount / 100), 2);
    }
}
