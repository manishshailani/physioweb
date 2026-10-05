<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Patient;

/**
 * Global search across the handful of entities an admin/receptionist is
 * most likely to look up by a partial name, code, or number: patients,
 * appointments, invoices, and staff/therapists. Each category is queried
 * and capped independently (rather than a single UNION query) so one slow
 * or zero-match category never suppresses results from another, and so
 * each category can use its own most-relevant ORDER BY.
 */
final class SearchController extends Controller
{
    private const MIN_LENGTH = 2;
    private const LIMIT_PER_CATEGORY = 8;

    public function index(Request $request): void
    {
        $this->requireAdmin();

        $term = trim((string) $request->query('q', ''));
        $results = [
            'patients' => [],
            'appointments' => [],
            'invoices' => [],
            'staff' => [],
        ];

        if (mb_strlen($term) >= self::MIN_LENGTH) {
            $results['patients'] = Patient::search($term, self::LIMIT_PER_CATEGORY);
            $results['appointments'] = $this->searchAppointments($term);
            $results['invoices'] = $this->searchInvoices($term);
            $results['staff'] = $this->searchStaff($term);
        }

        $totalResults = array_sum(array_map('count', $results));

        $this->view('admin.search.index', [
            'page_title' => 'Search Results',
            'term' => $term,
            'results' => $results,
            'totalResults' => $totalResults,
            'tooShort' => $term !== '' && mb_strlen($term) < self::MIN_LENGTH,
        ], 'layouts.admin');
    }

    private function searchAppointments(string $term): array
    {
        $like = '%' . $term . '%';
        $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        return Database::fetchAll(
            "SELECT a.*, COALESCE({$concat}, a.guest_name) AS patient_name
             FROM appointments a
             LEFT JOIN patients p ON p.id = a.patient_id
             WHERE a.appointment_number LIKE :t1 OR a.guest_name LIKE :t2 OR {$concat} LIKE :t3
             ORDER BY a.scheduled_date DESC
             LIMIT " . self::LIMIT_PER_CATEGORY,
            ['t1' => $like, 't2' => $like, 't3' => $like]
        );
    }

    private function searchInvoices(string $term): array
    {
        $like = '%' . $term . '%';
        $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
        return Database::fetchAll(
            "SELECT i.*, {$concat} AS patient_name
             FROM invoices i JOIN patients p ON p.id = i.patient_id
             WHERE i.invoice_number LIKE :t1 OR {$concat} LIKE :t2
             ORDER BY i.invoice_date DESC
             LIMIT " . self::LIMIT_PER_CATEGORY,
            ['t1' => $like, 't2' => $like]
        );
    }

    private function searchStaff(string $term): array
    {
        $like = '%' . $term . '%';
        $concat = Database::concat(['first_name', "' '", 'last_name']);
        return Database::fetchAll(
            "SELECT * FROM staff
             WHERE is_active = 1 AND (staff_code LIKE :t1 OR {$concat} LIKE :t2 OR phone LIKE :t3)
             ORDER BY first_name
             LIMIT " . self::LIMIT_PER_CATEGORY,
            ['t1' => $like, 't2' => $like, 't3' => $like]
        );
    }
}
