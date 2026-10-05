<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class BlogCategory extends Model
{
    protected static string $table = 'blog_categories';

    public static function allOrdered(): array
    {
        return Database::fetchAll('SELECT * FROM blog_categories ORDER BY name');
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT * FROM blog_categories WHERE slug = :slug', ['slug' => $slug]);
    }
}
