<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class TreatmentSession extends Model
{
    protected static string $table = 'treatment_sessions';

    public static function forPlan(int $planId): array
    {
        return Database::fetchAll(
            'SELECT * FROM treatment_sessions WHERE treatment_plan_id = :pid ORDER BY session_number',
            ['pid' => $planId]
        );
    }

    public static function nextSessionNumber(int $planId): int
    {
        $max = Database::fetchColumn(
            'SELECT MAX(session_number) FROM treatment_sessions WHERE treatment_plan_id = :pid',
            ['pid' => $planId]
        );
        return ((int) $max) + 1;
    }

    /** Pain/mobility scores over time for a plan, used for the progress chart. */
    public static function progressSeries(int $planId): array
    {
        return Database::fetchAll(
            'SELECT session_date, session_number, pain_score_before, pain_score_after, mobility_score
             FROM treatment_sessions WHERE treatment_plan_id = :pid ORDER BY session_number',
            ['pid' => $planId]
        );
    }
}
