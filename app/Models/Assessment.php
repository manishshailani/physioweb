<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Assessment extends Model
{
    protected static string $table = 'assessments';

    public static function forPatient(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT * FROM assessments WHERE patient_id = :pid ORDER BY assessment_date DESC',
            ['pid' => $patientId]
        );
    }

    public static function progressSeries(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT assessment_date, pain_scale, mobility_score, recovery_status
             FROM assessments WHERE patient_id = :pid ORDER BY assessment_date',
            ['pid' => $patientId]
        );
    }
}
