<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\IdGenerator;
use App\Core\Model;

/**
 * Patients are never hard-deleted by the application (see deactivate()) to
 * preserve the clinical/billing history required for medico-legal records;
 * "delete" in the admin UI maps to is_active = 0, and inactive patients are
 * excluded from default listings but remain fully intact for audit,
 * invoices already issued, and historical reports.
 */
final class Patient extends Model
{
    protected static string $table = 'patients';

    /**
     * Register a new patient with a generated PAT-YYYY-#### code.
     * Runs inside its own transaction because the code generation
     * (id_sequences row) and the patient insert must succeed or fail
     * together.
     */
    public static function register(array $data): array
    {
        Database::beginTransaction();
        try {
            $code = IdGenerator::next('PAT');
            $data['patient_code'] = $code;
            $data['created_at'] = Database::now();
            $data['updated_at'] = Database::now();
            $id = Database::insert('patients', $data);
            Database::commit();
            return ['id' => $id, 'patient_code' => $code];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function search(string $term, int $limit = 20): array
    {
        $like = '%' . $term . '%';
        // See Database::concat() for why this can't be a hard-coded `||`.
        $concat = Database::concat(['first_name', "' '", 'last_name']);

        return Database::fetchAll(
            "SELECT * FROM patients
             WHERE is_active = 1 AND (
                patient_code LIKE :t1 OR mobile LIKE :t2 OR email LIKE :t3
                OR first_name LIKE :t4 OR last_name LIKE :t5
                OR {$concat} LIKE :t6
             )
             ORDER BY last_name, first_name
             LIMIT " . (int) $limit,
            ['t1' => $like, 't2' => $like, 't3' => $like, 't4' => $like, 't5' => $like, 't6' => $like]
        );
    }

    public static function activePaginated(int $page, int $perPage, string $search = ''): array
    {
        if ($search !== '') {
            $like = '%' . $search . '%';
            return static::paginate(
                $page,
                $perPage,
                "is_active = 1 AND (patient_code LIKE :t1 OR mobile LIKE :t2 OR first_name LIKE :t3 OR last_name LIKE :t4)",
                ['t1' => $like, 't2' => $like, 't3' => $like, 't4' => $like],
                'last_name, first_name'
            );
        }
        return static::paginate($page, $perPage, 'is_active = 1', [], 'last_name, first_name');
    }

    public static function deactivate(int $id): void
    {
        static::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
    }

    public static function fullName(array $patient): string
    {
        return trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
    }

    public static function age(array $patient): ?int
    {
        if (empty($patient['date_of_birth'])) {
            return null;
        }
        $dob = new \DateTimeImmutable($patient['date_of_birth']);
        return $dob->diff(new \DateTimeImmutable())->y;
    }

    public static function countActive(): int
    {
        return static::count('is_active = 1');
    }

    public static function countNewSince(string $sinceDate): int
    {
        return static::count('is_active = 1 AND created_at >= :since', ['since' => $sinceDate]);
    }

    /**
     * Patients registered within [from, to] (inclusive calendar dates),
     * for the admin Patients report. created_at is a DATETIME, so the
     * upper bound includes the full day rather than truncating at
     * midnight.
     */
    public static function registeredBetween(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT * FROM patients
             WHERE created_at >= :from AND created_at <= :to
             ORDER BY created_at",
            ['from' => $from . ' 00:00:00', 'to' => $to . ' 23:59:59']
        );
    }

    /**
     * Gender distribution across all currently-active patients. Scoped to
     * is_active = 1 (not a date range) because "demographics" describes
     * the current patient base, not a cohort registered in some window --
     * the registeredBetween() report above covers the date-scoped view.
     */
    public static function genderDistribution(): array
    {
        return Database::fetchAll(
            "SELECT gender, COUNT(*) AS total FROM patients WHERE is_active = 1 GROUP BY gender ORDER BY gender"
        );
    }

    /**
     * Active-patient counts bucketed into fixed age bands, computed in PHP
     * from each patient's date_of_birth rather than in SQL, because age
     * bucketing from a DATE column needs DATEDIFF/julianday-style functions
     * that differ between MySQL and SQLite -- see Database::concat() for
     * the same cross-driver-portability rationale applied elsewhere. A
     * patient with no recorded date_of_birth is counted under "Unknown"
     * rather than silently dropped, so the bucket totals still sum to the
     * active patient count.
     */
    public static function ageGroupDistribution(): array
    {
        $rows = Database::fetchAll('SELECT date_of_birth FROM patients WHERE is_active = 1');
        $buckets = ['0-17' => 0, '18-30' => 0, '31-45' => 0, '46-60' => 0, '60+' => 0, 'Unknown' => 0];
        $now = new \DateTimeImmutable();

        foreach ($rows as $row) {
            if (empty($row['date_of_birth'])) {
                $buckets['Unknown']++;
                continue;
            }
            $age = (new \DateTimeImmutable($row['date_of_birth']))->diff($now)->y;
            $buckets[match (true) {
                $age < 18 => '0-17',
                $age <= 30 => '18-30',
                $age <= 45 => '31-45',
                $age <= 60 => '46-60',
                default => '60+',
            }]++;
        }

        return $buckets;
    }
}
