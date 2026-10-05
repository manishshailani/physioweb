<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Simple key/value store for site content (clinic info, homepage text). */
final class Setting extends Model
{
    protected static string $table = 'settings';

    /**
     * Named distinctly from the inherited Model::all() (which would return
     * raw rows) because callers of this Model always want the key => value
     * map, never the raw row list.
     */
    public static function allAsMap(): array
    {
        $rows = Database::fetchAll('SELECT key_name, value FROM settings');
        $map = [];
        foreach ($rows as $row) {
            $map[$row['key_name']] = $row['value'];
        }
        return $map;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Database::fetchColumn('SELECT value FROM settings WHERE key_name = :k', ['k' => $key]);
        return $value === null || $value === false ? $default : (string) $value;
    }

    public static function set(string $key, ?string $value): void
    {
        $existing = Database::fetchColumn('SELECT id FROM settings WHERE key_name = :k', ['k' => $key]);
        if ($existing) {
            Database::update('settings', ['value' => $value, 'updated_at' => Database::now()], ['key_name' => $key]);
        } else {
            Database::insert('settings', [
                'key_name' => $key,
                'value' => $value,
                'created_at' => Database::now(),
                'updated_at' => Database::now(),
            ]);
        }
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::set($key, $value);
        }
    }
}
