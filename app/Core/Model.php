<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight Active-Record-ish base class. Deliberately small: this is not
 * a full ORM. Each concrete Model declares its table name and (optionally)
 * fillable columns; CRUD helpers below cover the common cases, and each
 * Model is free to add hand-written queries for anything more specific
 * (joins, aggregates, report queries) using Database:: directly — those are
 * exactly the place-specific business queries that belong in the Model
 * layer rather than in Controllers.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            sprintf('SELECT * FROM %s WHERE %s = :id', static::$table, static::$primaryKey),
            ['id' => $id]
        );
    }

    public static function findOrFail(int $id): array
    {
        $row = static::find($id);
        if ($row === null) {
            throw new \RuntimeException(static::class . " record {$id} not found.");
        }
        return $row;
    }

    public static function all(string $orderBy = ''): array
    {
        $sql = sprintf('SELECT * FROM %s', static::$table);
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return Database::fetchAll($sql);
    }

    public static function where(string $column, mixed $value): array
    {
        return Database::fetchAll(
            sprintf('SELECT * FROM %s WHERE %s = :v', static::$table, $column),
            ['v' => $value]
        );
    }

    public static function whereFirst(string $column, mixed $value): ?array
    {
        return Database::fetchOne(
            sprintf('SELECT * FROM %s WHERE %s = :v LIMIT 1', static::$table, $column),
            ['v' => $value]
        );
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        return Database::update(static::$table, $data, [static::$primaryKey => $id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::delete(static::$table, [static::$primaryKey => $id]);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return (int) Database::fetchColumn(
            sprintf('SELECT COUNT(*) FROM %s WHERE %s', static::$table, $where),
            $params
        );
    }

    public static function paginate(int $page, int $perPage, string $where = '1=1', array $params = [], string $orderBy = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $order = $orderBy !== '' ? 'ORDER BY ' . $orderBy : '';
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s %s LIMIT %d OFFSET %d',
            static::$table,
            $where,
            $order,
            $perPage,
            $offset
        );
        $rows = Database::fetchAll($sql, $params);
        $total = static::count($where, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public static function tableName(): string
    {
        return static::$table;
    }
}
