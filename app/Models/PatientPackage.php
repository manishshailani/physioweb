<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class PatientPackage extends Model
{
    protected static string $table = 'patient_packages';

    public static function subscribe(int $patientId, int $packageId, ?int $invoiceId = null): int
    {
        $package = Package::findOrFail($packageId);
        $purchasedDate = date('Y-m-d');
        $expiryDate = date('Y-m-d', strtotime($purchasedDate . ' +' . (int) $package['validity_days'] . ' days'));

        return Database::insert('patient_packages', [
            'patient_id' => $patientId,
            'package_id' => $packageId,
            'invoice_id' => $invoiceId,
            'purchased_date' => $purchasedDate,
            'expiry_date' => $expiryDate,
            'sessions_used' => 0,
            'status' => 'active',
            'created_at' => Database::now(),
        ]);
    }

    public static function forPatientWithPackage(int $patientId): array
    {
        return Database::fetchAll(
            'SELECT pp.*, pk.name AS package_name, pk.total_sessions, pk.price
             FROM patient_packages pp
             JOIN packages pk ON pk.id = pp.package_id
             WHERE pp.patient_id = :pid
             ORDER BY pp.purchased_date DESC',
            ['pid' => $patientId]
        );
    }

    public static function incrementUsage(int $id): void
    {
        Database::query(
            'UPDATE patient_packages SET sessions_used = sessions_used + 1 WHERE id = :id',
            ['id' => $id]
        );
    }
}
