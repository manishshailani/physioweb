<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Service extends Model
{
    protected static string $table = 'services';

    public static function activeOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM services WHERE is_active = 1 ORDER BY display_order, name');
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT * FROM services WHERE slug = :slug AND is_active = 1', ['slug' => $slug]);
    }

    public static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = self::slugify($base);
        $candidate = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM services WHERE slug = :slug';
            $params = ['slug' => $candidate];
            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }
            $exists = (int) Database::fetchColumn($sql, $params);
            if ($exists === 0) {
                return $candidate;
            }
            $i++;
            $candidate = $slug . '-' . $i;
        }
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }
}
