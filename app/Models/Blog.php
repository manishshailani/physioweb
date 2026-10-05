<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Blog extends Model
{
    protected static string $table = 'blogs';

    public static function publishedPaginated(int $page, int $perPage, ?int $categoryId = null, string $search = ''): array
    {
        $where = "status = 'published' AND published_at <= :now";
        $params = ['now' => Database::now()];

        if ($categoryId !== null) {
            $where .= ' AND category_id = :cat';
            $params['cat'] = $categoryId;
        }
        if ($search !== '') {
            $where .= ' AND (title LIKE :s1 OR excerpt LIKE :s2 OR content LIKE :s3)';
            $like = '%' . $search . '%';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
        }

        return static::paginate($page, $perPage, $where, $params, 'published_at DESC');
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM blogs WHERE slug = :slug AND status = 'published' AND published_at <= :now",
            ['slug' => $slug, 'now' => Database::now()]
        );
    }

    public static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = Service::slugify($base);
        $candidate = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM blogs WHERE slug = :slug';
            $params = ['slug' => $candidate];
            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }
            if ((int) Database::fetchColumn($sql, $params) === 0) {
                return $candidate;
            }
            $i++;
            $candidate = $slug . '-' . $i;
        }
    }
}
