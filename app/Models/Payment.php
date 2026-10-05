<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Payment extends Model
{
    protected static string $table = 'payments';

    /**
     * Record a payment and keep the parent invoice's amount_paid/status in
     * sync in the same transaction. A payment that would push amount_paid
     * past the invoice total is rejected (over-payment is not modeled by
     * this system; a refund/credit workflow is out of scope — see README
     * limitations).
     */
    public static function recordForInvoice(int $invoiceId, array $data): int
    {
        Database::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            $alreadyPaid = (float) Database::fetchColumn(
                'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = :id',
                ['id' => $invoiceId]
            );
            $newAmount = round((float) $data['amount'], 2);
            if ($newAmount <= 0) {
                throw new \InvalidArgumentException('Payment amount must be greater than zero.');
            }
            if (round($alreadyPaid + $newAmount, 2) > round((float) $invoice['total_amount'], 2) + 0.01) {
                throw new \InvalidArgumentException('This payment would exceed the invoice total. Remaining balance is ' . number_format((float) $invoice['total_amount'] - $alreadyPaid, 2) . '.');
            }

            $data['amount'] = $newAmount;
            $data['invoice_id'] = $invoiceId;
            $data['created_at'] = Database::now();
            $id = Database::insert('payments', $data);

            Invoice::recalculateStatus($invoiceId);

            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function deleteAndRecalculate(int $paymentId): void
    {
        Database::beginTransaction();
        try {
            $payment = static::findOrFail($paymentId);
            static::deleteById($paymentId);
            Invoice::recalculateStatus((int) $payment['invoice_id']);
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function byMode(string $from, string $to): array
    {
        return Database::fetchAll(
            'SELECT payment_mode, COALESCE(SUM(amount),0) AS total, COUNT(*) AS count
             FROM payments WHERE payment_date BETWEEN :from AND :to
             GROUP BY payment_mode',
            ['from' => $from, 'to' => $to]
        );
    }
}
