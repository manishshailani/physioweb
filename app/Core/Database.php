<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper providing a single shared connection (per request) and
 * convenience helpers that always use prepared statements.
 *
 * Driver support: this project targets MySQL 8 in production (as specified
 * by the project requirements). The wrapper also supports a `sqlite` driver
 * purely so the automated smoke tests in tests/ can exercise real SQL
 * against a throwaway in-memory database without requiring a MySQL server
 * in every environment (e.g. CI, or this development sandbox). Application
 * code must not rely on MySQL-only SQL syntax inside Model classes where a
 * portable alternative exists, so the same Model code can be verified
 * against SQLite; schema-level MySQL-specific features (ENUM, AUTO_INCREMENT
 * syntax, etc.) live only in database/schema.mysql.sql and are translated
 * separately for the SQLite test schema (database/schema.sqlite.sql).
 *
 * Security: every query path here uses PDO prepared statements with bound
 * parameters. No Model or Controller in this codebase should concatenate
 * untrusted input directly into SQL.
 */
final class Database
{
    private static ?PDO $connection = null;
    private static string $driver = 'mysql';

    public static function connect(array $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        self::$driver = $config['driver'] ?? 'mysql';

        try {
            if (self::$driver === 'sqlite') {
                $path = $config['database'] ?? ':memory:';
                $pdo = new PDO('sqlite:' . $path);
                $pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $config['host'],
                    (int) $config['port'],
                    $config['database']
                );
                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, sql_mode='STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'",
                ]);
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Never leak DSN/credentials to the end user; log server-side.
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please check server logs.');
        }

        self::$connection = $pdo;
        return $pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$connection === null) {
            throw new RuntimeException('Database::connect() must be called before Database::pdo().');
        }
        return self::$connection;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    /** Reset the shared connection. Used only by the test harness between tests. */
    public static function reset(): void
    {
        self::$connection = null;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quoteIdentifier($table),
            implode(', ', array_map([self::class, 'quoteIdentifier'], $columns)),
            implode(', ', $placeholders)
        );
        $stmt = self::pdo()->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->execute();
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = self::quoteIdentifier($col) . ' = :set_' . $col;
        }
        $whereParts = [];
        foreach (array_keys($where) as $col) {
            $whereParts[] = self::quoteIdentifier($col) . ' = :where_' . $col;
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::quoteIdentifier($table),
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );
        $stmt = self::pdo()->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':set_' . $col, $val);
        }
        foreach ($where as $col => $val) {
            $stmt->bindValue(':where_' . $col, $val);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function delete(string $table, array $where): int
    {
        $whereParts = [];
        foreach (array_keys($where) as $col) {
            $whereParts[] = self::quoteIdentifier($col) . ' = :where_' . $col;
        }
        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            self::quoteIdentifier($table),
            implode(' AND ', $whereParts)
        );
        $stmt = self::pdo()->prepare($sql);
        foreach ($where as $col => $val) {
            $stmt->bindValue(':where_' . $col, $val);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function beginTransaction(): bool
    {
        return self::pdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::pdo()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::pdo()->inTransaction()) {
            return self::pdo()->rollBack();
        }
        return false;
    }

    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Quote a simple identifier (table/column name). Only used internally
     * with identifiers that originate from our own Model code (hard-coded
     * strings), never from user input, but quoted defensively regardless.
     */
    public static function quoteIdentifier(string $identifier): string
    {
        $identifier = str_replace('`', '', $identifier);
        return self::$driver === 'sqlite' ? '"' . $identifier . '"' : '`' . $identifier . '`';
    }

    /** Current timestamp expression compatible with the active driver. */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Build a portable SQL string-concatenation expression from column/SQL
     * fragments. `||` means concatenation in SQLite but logical OR in
     * MySQL's default sql_mode, and CONCAT() does not exist in SQLite
     * before 3.44 — so any code that needs "first_name + ' ' + last_name"
     * style concatenation across both drivers must go through here rather
     * than hard-coding either syntax.
     *
     * Example: Database::concat(['first_name', "' '", 'last_name'])
     */
    public static function concat(array $parts): string
    {
        return self::$driver === 'sqlite'
            ? '(' . implode(' || ', $parts) . ')'
            : 'CONCAT(' . implode(', ', $parts) . ')';
    }
}
