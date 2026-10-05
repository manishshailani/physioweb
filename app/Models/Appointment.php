<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\IdGenerator;
use App\Core\Model;

final class Appointment extends Model
{
    protected static string $table = 'appointments';

    public static function book(array $data): array
    {
        Database::beginTransaction();
        try {
            $number = IdGenerator::next('APT');
            $data['appointment_number'] = $number;
            $data['created_at'] = Database::now();
            $data['updated_at'] = Database::now();
            $id = Database::insert('appointments', $data);
            Database::commit();
            return ['id' => $id, 'appointment_number' => $number];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /** Appointments joined with patient/therapist/service names, for list views. */
    public static function listWithDetails(string $whereSql = '1=1', array $params = [], string $orderBy = 'a.scheduled_date DESC, a.scheduled_time DESC', int $limit = 0): array
    {
        $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        $staffConcat = Database::concat(['s.first_name', "' '", 's.last_name']);
        $sql = "SELECT a.*,
                    COALESCE({$concat}, a.guest_name) AS patient_name,
                    {$staffConcat} AS therapist_name,
                    sv.name AS service_name
                FROM appointments a
                LEFT JOIN patients p ON p.id = a.patient_id
                LEFT JOIN therapists t ON t.id = a.therapist_id
                LEFT JOIN staff s ON s.id = t.staff_id
                LEFT JOIN services sv ON sv.id = a.service_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        return Database::fetchAll($sql, $params);
    }

    public static function findWithDetails(int $id): ?array
    {
        $rows = static::listWithDetails('a.id = :id', ['id' => $id]);
        return $rows[0] ?? null;
    }

    public static function forCalendarRange(string $start, string $end): array
    {
        return static::listWithDetails(
            'a.scheduled_date BETWEEN :start AND :end',
            ['start' => $start, 'end' => $end],
            'a.scheduled_date, a.scheduled_time'
        );
    }

    public static function todayCount(): int
    {
        return static::count('scheduled_date = :today', ['today' => date('Y-m-d')]);
    }

    public static function pendingCount(): int
    {
        return static::count("status IN ('scheduled','confirmed') AND scheduled_date >= :today", ['today' => date('Y-m-d')]);
    }

    public static function trendLastNDays(int $days): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        $rows = Database::fetchAll(
            "SELECT scheduled_date, COUNT(*) AS total
             FROM appointments
             WHERE scheduled_date >= :since
             GROUP BY scheduled_date
             ORDER BY scheduled_date",
            ['since' => $since]
        );
        return $rows;
    }

    public static function forPatient(int $patientId): array
    {
        return static::listWithDetails('a.patient_id = :pid', ['pid' => $patientId]);
    }

    /**
     * Paginated appointment listing with patient/therapist/service names
     * joined in, for the admin appointments index. $whereSql must use
     * unqualified or 'a.'-prefixed column names that are unambiguous across
     * the joined tables (status, scheduled_date, therapist_id, service_id,
     * patient_id all qualify - none of those names are duplicated in
     * patients/therapists/staff/services).
     */
    public static function paginatedWithDetails(int $page, int $perPage, string $whereSql = '1=1', array $params = [], string $orderBy = 'a.scheduled_date DESC, a.scheduled_time DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = static::count($whereSql, $params);

        $rows = static::listWithDetails($whereSql, $params, $orderBy . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** Appointment counts by status within [from, to], for the Appointments report. */
    public static function statusCountsBetween(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM appointments
             WHERE scheduled_date BETWEEN :from AND :to
             GROUP BY status ORDER BY status",
            ['from' => $from, 'to' => $to]
        );
    }

    /** Appointment counts by therapist within [from, to], for the Appointments report. */
    public static function countsByTherapistBetween(string $from, string $to): array
    {
        $concat = Database::concat(['s.first_name', "' '", 's.last_name']);
        return Database::fetchAll(
            "SELECT {$concat} AS therapist_name, COUNT(*) AS total
             FROM appointments a
             JOIN therapists t ON t.id = a.therapist_id
             JOIN staff s ON s.id = t.staff_id
             WHERE a.scheduled_date BETWEEN :from AND :to
             GROUP BY therapist_name
             ORDER BY total DESC",
            ['from' => $from, 'to' => $to]
        );
    }

    /** Full appointment list (with joined names) within [from, to], for the report table and its export. */
    public static function listBetween(string $from, string $to): array
    {
        return static::listWithDetails(
            'a.scheduled_date BETWEEN :from AND :to',
            ['from' => $from, 'to' => $to],
            'a.scheduled_date, a.scheduled_time'
        );
    }
}
