<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Generates human-facing sequential IDs of the form PREFIX-YYYY-0001.
 *
 * Reliability note: the counter is derived from the current MAX sequence
 * number stored for the given prefix+year in the id_sequences table, and
 * the increment is done inside the same DB transaction as the row insert
 * that consumes it (see Models\Patient::register, Models\Invoice::create,
 * Models\Appointment::create) using SELECT ... FOR UPDATE on MySQL to
 * prevent two concurrent requests from generating the same number. Under
 * SQLite (test harness only), table-level locking is implicit so the same
 * code path is still race-free for the single-process test scenarios it is
 * used in; it is not a substitute for row locking under real concurrent
 * MySQL load, which is why production always goes through MySQL's
 * SELECT ... FOR UPDATE here.
 */
final class IdGenerator
{
    /**
     * Must be called within an active transaction (Database::beginTransaction()).
     */
    public static function next(string $prefix, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        $driver = Database::driver();

        $lockClause = $driver === 'mysql' ? ' FOR UPDATE' : '';

        $row = Database::fetchOne(
            "SELECT id, last_value FROM id_sequences WHERE prefix = :prefix AND year = :year" . $lockClause,
            ['prefix' => $prefix, 'year' => $year]
        );

        if ($row === null) {
            Database::insert('id_sequences', [
                'prefix' => $prefix,
                'year' => $year,
                'last_value' => 1,
            ]);
            $next = 1;
        } else {
            $next = (int) $row['last_value'] + 1;
            Database::update('id_sequences', ['last_value' => $next], ['id' => (int) $row['id']]);
        }

        return sprintf('%s-%d-%04d', $prefix, $year, $next);
    }
}
