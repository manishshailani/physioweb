<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class ExerciseAssignment extends Model
{
    protected static string $table = 'exercise_assignments';

    public static function forPatientWithExercise(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT ea.*, e.name AS exercise_name, e.instructions, e.video_link, e.category
             FROM exercise_assignments ea
             JOIN exercises e ON e.id = ea.exercise_id
             WHERE ea.patient_id = :pid
             ORDER BY ea.start_date DESC',
            ['pid' => $patientId]
        );
    }

    /** Only currently-active assignments (no end date, or end date in the future). */
    public static function currentForPatient(int $patientId): array
    {
        $today = date('Y-m-d');
        return Database::fetchAll(
            'SELECT ea.*, e.name AS exercise_name, e.instructions, e.video_link, e.category
             FROM exercise_assignments ea
             JOIN exercises e ON e.id = ea.exercise_id
             WHERE ea.patient_id = :pid AND (ea.end_date IS NULL OR ea.end_date >= :today)
             ORDER BY ea.start_date DESC',
            ['pid' => $patientId, 'today' => $today]
        );
    }
}
