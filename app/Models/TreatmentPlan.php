<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\IdGenerator;
use App\Core\Model;

final class TreatmentPlan extends Model
{
    protected static string $table = 'treatment_plans';

    public static function createPlan(array $data): array
    {
        Database::beginTransaction();
        try {
            $number = IdGenerator::next('PLAN');
            $data['plan_number'] = $number;
            $data['created_at'] = Database::now();
            $data['updated_at'] = Database::now();
            $id = Database::insert('treatment_plans', $data);
            Database::commit();
            return ['id' => $id, 'plan_number' => $number];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function forPatient(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT * FROM treatment_plans WHERE patient_id = :pid ORDER BY start_date DESC',
            ['pid' => $patientId]
        );
    }

    /** Same as forPatient(), with the assigned therapist's name joined in -- used by the patient portal's own treatment-plan view. */
    public static function forPatientWithTherapist(int $patientId): array
    {
        $staffConcat = Database::concat(['s.first_name', "' '", 's.last_name']);
        return Database::fetchAll(
            "SELECT tp.*, {$staffConcat} AS therapist_name
             FROM treatment_plans tp
             LEFT JOIN therapists t ON t.id = tp.therapist_id
             LEFT JOIN staff s ON s.id = t.staff_id
             WHERE tp.patient_id = :pid
             ORDER BY tp.start_date DESC",
            ['pid' => $patientId]
        );
    }

    public static function activeCount(): int
    {
        return static::count("status = 'active'");
    }

    /** Treatment plan counts by status, for plans started within [from, to]. */
    public static function statusCountsBetween(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM treatment_plans WHERE start_date BETWEEN :from AND :to
             GROUP BY status ORDER BY status",
            ['from' => $from, 'to' => $to]
        );
    }

    /**
     * Treatment plans started within [from, to], joined with patient and
     * therapist names and each plan's logged session count, for the
     * Treatments report table and its export. The session count is a
     * correlated subquery rather than a GROUP BY/JOIN against
     * treatment_sessions, so a plan with zero sessions still shows 0
     * instead of being silently dropped by an inner join.
     */
    public static function listBetween(string $from, string $to): array
    {
        $patientConcat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        $staffConcat = Database::concat(['s.first_name', "' '", 's.last_name']);
        return Database::fetchAll(
            "SELECT tp.*,
                    {$patientConcat} AS patient_name,
                    {$staffConcat} AS therapist_name,
                    (SELECT COUNT(*) FROM treatment_sessions ts WHERE ts.treatment_plan_id = tp.id) AS session_count
             FROM treatment_plans tp
             JOIN patients p ON p.id = tp.patient_id
             LEFT JOIN therapists t ON t.id = tp.therapist_id
             LEFT JOIN staff s ON s.id = t.staff_id
             WHERE tp.start_date BETWEEN :from AND :to
             ORDER BY tp.start_date DESC",
            ['from' => $from, 'to' => $to]
        );
    }
}
