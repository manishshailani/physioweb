<?php

declare(strict_types=1);

/**
 * Test bootstrap: wires up the same classes as app/bootstrap.php but
 * against an in-memory SQLite database loaded from
 * database/schema.sqlite.sql, instead of a real MySQL server. See
 * tests/README.md for exactly what this does and does not prove.
 */

$rootPath = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($rootPath) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = $rootPath . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Core\Database;
use App\Core\Logger;

Logger::init($rootPath . '/tests/tmp/test.log');

function physioweb_test_db(): \PDO
{
    Database::reset();
    $pdo = Database::connect(['driver' => 'sqlite', 'database' => ':memory:']);
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sqlite.sql');
    $pdo->exec($schema);
    return $pdo;
}

/** Minimal assertion helpers (no PHPUnit available in this sandbox — see tests/README.md). */
function assert_true(bool $condition, string $message): void
{
    global $physioweb_test_failures, $physioweb_test_count;
    $physioweb_test_count = ($physioweb_test_count ?? 0) + 1;
    if (!$condition) {
        $physioweb_test_failures = ($physioweb_test_failures ?? []);
        $physioweb_test_failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    } else {
        echo "  ok - {$message}\n";
    }
}

function assert_equals(mixed $expected, mixed $actual, string $message): void
{
    assert_true($expected === $actual, "{$message} (expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
}

function physioweb_test_summary(): int
{
    global $physioweb_test_failures, $physioweb_test_count;
    $failures = $physioweb_test_failures ?? [];
    $count = $physioweb_test_count ?? 0;
    $passed = $count - count($failures);
    echo "\n{$passed}/{$count} assertions passed.\n";
    if ($failures !== []) {
        echo count($failures) . " FAILURE(S):\n";
        foreach ($failures as $f) {
            echo " - {$f}\n";
        }
        return 1;
    }
    return 0;
}
