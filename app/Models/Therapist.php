<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Public-facing + schedulable physiotherapist profile (joins to staff for HR fields). */
final class Therapist extends Model
{
    protected static string $table = 'therapists';

    /** Therapist rows joined with their staff HR record, for display. */
    public static function allWithStaff(bool $publicOnly = false): array
    {
        $where = $publicOnly ? 'WHERE t.is_public = 1 AND s.is_active = 1' : 'WHERE s.is_active = 1';
        return Database::fetchAll(
            "SELECT t.*, s.first_name, s.last_name, s.qualification, s.specialization,
                    s.experience_years, s.photo, s.phone, s.email
             FROM therapists t
             JOIN staff s ON s.id = t.staff_id
             {$where}
             ORDER BY t.display_order, s.first_name"
        );
    }

    public static function findWithStaff(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT t.*, s.first_name, s.last_name, s.qualification, s.specialization,
                    s.experience_years, s.photo, s.phone, s.email
             FROM therapists t
             JOIN staff s ON s.id = t.staff_id
             WHERE t.id = :id",
            ['id' => $id]
        );
    }

    public static function findBySlugWithStaff(string $slug): ?array
    {
        return Database::fetchOne(
            "SELECT t.*, s.first_name, s.last_name, s.qualification, s.specialization,
                    s.experience_years, s.photo, s.phone, s.email
             FROM therapists t
             JOIN staff s ON s.id = t.staff_id
             WHERE t.slug = :slug AND t.is_public = 1 AND s.is_active = 1",
            ['slug' => $slug]
        );
    }

    /**
     * Check whether a therapist already has an overlapping appointment at
     * the requested date/time. Used by both the public booking form and
     * the admin scheduler to prevent double-booking.
     */
    public static function hasConflict(int $therapistId, string $date, string $time, int $durationMinutes, ?int $excludeAppointmentId = null): bool
    {
        $start = strtotime("{$date} {$time}");
        $end = $start + $durationMinutes * 60;

        $sql = "SELECT id, scheduled_time, duration_minutes FROM appointments
                WHERE therapist_id = :therapist_id AND scheduled_date = :date
                  AND status NOT IN ('cancelled','no_show')";
        $params = ['therapist_id' => $therapistId, 'date' => $date];

        if ($excludeAppointmentId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeAppointmentId;
        }

        $existing = Database::fetchAll($sql, $params);

        foreach ($existing as $row) {
            $existingStart = strtotime("{$date} {$row['scheduled_time']}");
            $existingEnd = $existingStart + ((int) $row['duration_minutes']) * 60;
            if ($start < $existingEnd && $end > $existingStart) {
                return true;
            }
        }

        return false;
    }
}
