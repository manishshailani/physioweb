<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class PatientDocument extends Model
{
    protected static string $table = 'patient_documents';

    public static function forPatient(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT * FROM patient_documents WHERE patient_id = :pid ORDER BY created_at DESC',
            ['pid' => $patientId]
        );
    }
}
