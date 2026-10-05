<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\IdGenerator;
use App\Core\Model;

/**
 * Invoice totals (subtotal, gst_amount, total_amount) are computed in PHP
 * from the submitted line items — never trusted from client input beyond
 * the raw quantity/unit_price/gst_rate per line — and persisted alongside
 * the items so historical invoices remain stable even if a service's
 * price changes later. amount_paid / status are kept in sync by
 * recalculateStatus(), called after every payment insert/delete.
 */
final class Invoice extends Model
{
    protected static string $table = 'invoices';

    /**
     * @param array $items each: ['service_id' => ?int, 'description' => string, 'quantity' => int, 'unit_price' => float, 'gst_rate' => float]
     */
    public static function createWithItems(array $invoiceData, array $items): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('An invoice must have at least one line item.');
        }

        $subtotal = 0.0;
        $gstAmount = 0.0;
        $lineRows = [];

        foreach ($items as $item) {
            $quantity = max(1, (int) $item['quantity']);
            $unitPrice = round((float) $item['unit_price'], 2);
            $gstRate = max(0.0, (float) ($item['gst_rate'] ?? 0));

            $lineBase = $quantity * $unitPrice;
            $lineGst = round($lineBase * ($gstRate / 100), 2);
            $lineTotal = round($lineBase + $lineGst, 2);

            $subtotal += $lineBase;
            $gstAmount += $lineGst;

            $lineRows[] = [
                'service_id' => $item['service_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gst_rate' => $gstRate,
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $gstAmount = round($gstAmount, 2);
        $discount = round((float) ($invoiceData['discount_amount'] ?? 0), 2);
        $total = round($subtotal + $gstAmount - $discount, 2);

        if ($total < 0) {
            throw new \InvalidArgumentException('Discount cannot exceed the invoice subtotal plus tax.');
        }

        Database::beginTransaction();
        try {
            $number = IdGenerator::next('INV');

            $invoiceId = Database::insert('invoices', array_merge($invoiceData, [
                'invoice_number' => $number,
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'amount_paid' => 0,
                'status' => 'unpaid',
                'created_at' => Database::now(),
                'updated_at' => Database::now(),
            ]));

            foreach ($lineRows as $row) {
                Database::insert('invoice_items', array_merge($row, ['invoice_id' => $invoiceId]));
            }

            Database::commit();

            return ['id' => $invoiceId, 'invoice_number' => $number, 'total_amount' => $total];
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function itemsFor(int $invoiceId): array
    {
        return Database::fetchAll('SELECT * FROM invoice_items WHERE invoice_id = :id', ['id' => $invoiceId]);
    }

    public static function paymentsFor(int $invoiceId): array
    {
        return Database::fetchAll(
            'SELECT * FROM payments WHERE invoice_id = :id ORDER BY payment_date, id',
            ['id' => $invoiceId]
        );
    }

    public static function withPatient(int $invoiceId): ?array
    {
        return Database::fetchOne(
            'SELECT i.*, p.first_name, p.last_name, p.patient_code, p.mobile, p.email, p.address, p.city, p.state, p.pincode
             FROM invoices i JOIN patients p ON p.id = i.patient_id
             WHERE i.id = :id',
            ['id' => $invoiceId]
        );
    }

    public static function listWithPatient(string $whereSql = '1=1', array $params = [], string $orderBy = 'i.invoice_date DESC, i.id DESC'): array
    {
        $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        return Database::fetchAll(
            "SELECT i.*, {$concat} AS patient_name, p.patient_code
             FROM invoices i JOIN patients p ON p.id = i.patient_id
             WHERE {$whereSql} ORDER BY {$orderBy}",
            $params
        );
    }

    /**
     * Recomputes amount_paid from the payments table and derives status.
     * Called after every payment is recorded or deleted so the invoice
     * is always the source of truth derived from its payments, rather
     * than two values that can drift apart.
     */
    public static function recalculateStatus(int $invoiceId): void
    {
        $invoice = static::findOrFail($invoiceId);
        $totalPaid = (float) Database::fetchColumn(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = :id',
            ['id' => $invoiceId]
        );
        $totalPaid = round($totalPaid, 2);
        $total = (float) $invoice['total_amount'];

        if ($invoice['status'] === 'cancelled') {
            $status = 'cancelled';
        } elseif ($totalPaid <= 0) {
            $status = 'unpaid';
        } elseif ($totalPaid >= $total) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        static::updateById($invoiceId, [
            'amount_paid' => $totalPaid,
            'status' => $status,
            'updated_at' => Database::now(),
        ]);
    }

    public static function outstandingBalance(array $invoice): float
    {
        return round((float) $invoice['total_amount'] - (float) $invoice['amount_paid'], 2);
    }

    public static function totalOutstanding(): float
    {
        return (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(total_amount - amount_paid), 0) FROM invoices WHERE status != 'cancelled'"
        );
    }

    /** Outstanding balance across all of one patient's invoices, for the patient portal dashboard/billing summary. */
    public static function outstandingForPatient(int $patientId): float
    {
        return (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(total_amount - amount_paid), 0) FROM invoices WHERE patient_id = :pid AND status != 'cancelled'",
            ['pid' => $patientId]
        );
    }

    public static function revenueBetween(string $from, string $to): float
    {
        return (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN :from AND :to",
            ['from' => $from, 'to' => $to]
        );
    }

    public static function revenueToday(): float
    {
        $today = date('Y-m-d');
        return static::revenueBetween($today, $today);
    }

    public static function revenueThisMonth(): float
    {
        return static::revenueBetween(date('Y-m-01'), date('Y-m-t'));
    }

    /**
     * Paginated invoice listing with patient name/code joined in, for the
     * admin invoices index. $whereSql must use 'i.'-prefixed column names
     * for columns that also exist on the joined patients table (id), and
     * may use unqualified names for invoice-only columns (status,
     * invoice_date) since those are unambiguous.
     */
    public static function paginatedWithPatient(int $page, int $perPage, string $whereSql = '1=1', array $params = [], string $orderBy = 'i.invoice_date DESC, i.id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM invoices i JOIN patients p ON p.id = i.patient_id WHERE {$whereSql}",
            $params
        );
        $rows = static::listWithPatient($whereSql, $params, $orderBy . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public static function revenueTrend(int $days): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));
        return Database::fetchAll(
            "SELECT payment_date, SUM(amount) AS total
             FROM payments WHERE payment_date >= :since
             GROUP BY payment_date ORDER BY payment_date",
            ['since' => $since]
        );
    }

    /**
     * Payments received within [from, to], joined with invoice/patient
     * details, for the Revenue report table and its export. Grouped by
     * payment (not by invoice) since a single invoice can receive several
     * payments and the report's job is to reconcile cash actually received.
     */
    public static function paymentsBetween(string $from, string $to): array
    {
        $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        return Database::fetchAll(
            "SELECT pay.*, i.invoice_number, {$concat} AS patient_name
             FROM payments pay
             JOIN invoices i ON i.id = pay.invoice_id
             JOIN patients p ON p.id = i.patient_id
             WHERE pay.payment_date BETWEEN :from AND :to
             ORDER BY pay.payment_date, pay.id",
            ['from' => $from, 'to' => $to]
        );
    }

    /** Revenue broken down by payment mode within [from, to], for the Revenue report's summary cards. */
    public static function paymentModeBreakdown(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT payment_mode, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS total
             FROM payments WHERE payment_date BETWEEN :from AND :to
             GROUP BY payment_mode ORDER BY total DESC",
            ['from' => $from, 'to' => $to]
        );
    }

    /**
     * Invoices issued within [from, to] broken down by status, with both
     * a count and the billed total per status -- e.g. how much is still
     * sitting unpaid/partially_paid vs. collected, for invoices raised in
     * that window specifically (as distinct from totalOutstanding(),
     * which is a point-in-time snapshot across all invoices ever issued).
     */
    public static function statusBreakdownBetween(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT status, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total
             FROM invoices WHERE invoice_date BETWEEN :from AND :to
             GROUP BY status ORDER BY status",
            ['from' => $from, 'to' => $to]
        );
    }

    /**
     * Revenue grouped by calendar month (YYYY-MM) within [from, to]. Uses
     * SUBSTR(payment_date, 1, 7) rather than MySQL's DATE_FORMAT() or
     * SQLite's strftime(), since SUBSTR/SUBSTRING is ANSI SQL and behaves
     * identically on both drivers for a 'YYYY-MM-DD' string column -- see
     * Database::concat() for the same cross-driver-portability principle
     * applied to string concatenation.
     */
    public static function monthlyRevenueBetween(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT SUBSTR(payment_date, 1, 7) AS month, COALESCE(SUM(amount), 0) AS total
             FROM payments WHERE payment_date BETWEEN :from AND :to
             GROUP BY month ORDER BY month",
            ['from' => $from, 'to' => $to]
        );
    }
}
