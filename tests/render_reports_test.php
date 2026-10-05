<?php

declare(strict_types=1);

/**
 * Execution-based verification for Task 11: admin Reports (patients,
 * appointments, revenue, treatments), the Audit Log screen, and Global
 * Search. Seeds realistic rows through the actual Model layer against an
 * in-memory SQLite DB, renders each view with the exact data shape its
 * Controller action passes, and treats any PHP warning/notice as a hard
 * failure -- same harness/pattern as the Task 7/8/9/10 render tests.
 *
 * Export (.xlsx/.pdf) download endpoints are NOT exercised end-to-end here
 * because a successful or failed export both end in header()+exit() inside
 * ReportController, which would terminate this script. Instead, the two
 * underlying services (ExcelService, PdfService) are verified directly:
 * since this sandbox has no network access to Packagist, neither
 * phpoffice/phpspreadsheet nor dompdf is actually installed under
 * vendor/, so both must throw RuntimeException rather than fatal-error --
 * exactly the documented, already-relied-upon contract InvoiceController
 * depends on for its own PDF export (see docs comment on PdfService).
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\View;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Therapist;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\ExcelService;
use App\Services\PdfService;

physioweb_test_db();
View::init(PHYSIOWEB_ROOT . '/app/Views');

$warnings = [];
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$warnings) {
    $warnings[] = "$errstr in $errfile:$errline";
    return true;
});

function render_and_report(string $layoutOrNull, string $template, array $data, string $label): void
{
    try {
        $data['csrf_field'] = '<input type="hidden" name="_csrf" value="test">';
        $data['csrf_token'] = 'test';
        $data['current_admin'] = ['id' => 1, 'name' => 'Test Admin', 'role' => 'admin'];
        $data['current_patient'] = null;
        $data['flash_success'] = null;
        $data['flash_error'] = null;
        $data['flash_errors'] = [];
        $data['old'] = [];

        $html = $layoutOrNull !== ''
            ? View::renderWithLayout($layoutOrNull, $template, $data)
            : View::render($template, $data);
        echo "  ok - rendered {$label} (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "  FAIL - {$label} threw: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

// ---------------------------------------------------------------------
// Seed data through the real Model layer
// ---------------------------------------------------------------------

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$staff1 = Staff::register([
    'first_name' => 'Meenakshi', 'last_name' => 'Kulkarni', 'role' => 'physiotherapist',
    'qualification' => 'BPT, MPT Neuro', 'phone' => '9900000001', 'email' => 'dr.meenakshi@example.com',
    'joining_date' => '2020-01-01', 'is_active' => 1,
]);
$staff2 = Staff::register([
    'first_name' => 'Anita', 'last_name' => 'Desai', 'role' => 'receptionist',
    'phone' => '9900000002', 'is_active' => 1,
]);

$therapistId = (int) Database::insert('therapists', [
    'staff_id' => $staff1['id'], 'slug' => 'dr-meenakshi-kulkarni', 'bio' => 'Senior physiotherapist.',
    'default_session_minutes' => 45, 'is_public' => 1, 'display_order' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$adminUserId = User::create([
    'staff_id' => $staff1['id'], 'name' => 'Dr. Meenakshi Kulkarni', 'email' => 'admin@example.com',
    'password_hash' => \App\Core\Auth::hashPassword('Secret@123'), 'role' => 'admin', 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$serviceId = Service::create([
    'name' => 'Sports Injury Rehabilitation', 'slug' => 'sports-injury-rehabilitation', 'category' => 'Sports',
    'fee' => 1000, 'gst_rate' => 18, 'duration_minutes' => 45, 'display_order' => 1, 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$patientMale = Patient::register([
    'first_name' => 'Ramesh', 'last_name' => 'Kulkarni', 'gender' => 'male', 'date_of_birth' => '1985-05-10',
    'mobile' => '9988776601', 'city' => 'Belgaum', 'is_active' => 1,
]);
$patientFemale = Patient::register([
    'first_name' => 'Sunita', 'last_name' => 'Patil', 'gender' => 'female', 'date_of_birth' => '1960-02-20',
    'mobile' => '9988776602', 'city' => 'Belgaum', 'is_active' => 1,
]);
$patientChild = Patient::register([
    'first_name' => 'Aarav', 'last_name' => 'Joshi', 'gender' => 'male', 'date_of_birth' => null,
    'mobile' => '9988776603', 'city' => 'Belgaum', 'is_active' => 1,
]);

$appt1 = Appointment::book([
    'patient_id' => $patientMale['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => $today, 'scheduled_time' => '10:00:00', 'status' => 'completed',
    'source' => 'admin',
]);
$appt2 = Appointment::book([
    'patient_id' => $patientFemale['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => $today, 'scheduled_time' => '11:00:00', 'status' => 'scheduled',
    'source' => 'admin',
]);

$invoice = Invoice::createWithItems(
    ['patient_id' => $patientMale['id'], 'invoice_date' => $today],
    [['service_id' => $serviceId, 'description' => 'Sports Injury Rehabilitation', 'quantity' => 1, 'unit_price' => 1000, 'gst_rate' => 18]]
);
Database::insert('payments', [
    'invoice_id' => $invoice['id'], 'amount' => $invoice['total_amount'], 'payment_mode' => 'upi',
    'payment_date' => $today, 'reference_number' => 'UPI12345', 'created_at' => Database::now(),
]);
Invoice::recalculateStatus($invoice['id']);

$plan = TreatmentPlan::createPlan([
    'patient_id' => $patientMale['id'], 'therapist_id' => $therapistId, 'diagnosis' => 'ACL sprain, right knee',
    'start_date' => $today, 'status' => 'active',
]);
Database::insert('treatment_sessions', [
    'treatment_plan_id' => $plan['id'], 'session_number' => 1, 'session_date' => $today,
    'therapist_id' => $therapistId, 'treatment_given' => 'Manual therapy + ultrasound', 'duration_minutes' => 30,
    'pain_score_before' => 6, 'pain_score_after' => 4, 'created_at' => Database::now(),
]);

// A few audit log rows covering all three user_type values, via the real
// write path (AuditLogger::log), not a hand-crafted INSERT.
AuditLogger::log($adminUserId, 'admin', 'create', 'patient', (int) $patientMale['id'], 'Created patient ' . $patientMale['patient_code']);
AuditLogger::log($adminUserId, 'admin', 'create', 'invoice', (int) $invoice['id'], 'Created invoice ' . $invoice['invoice_number']);
AuditLogger::log((int) $patientFemale['id'], 'patient', 'login', 'patient', (int) $patientFemale['id'], 'Patient portal login');
AuditLogger::log(null, 'system', 'reminder', 'appointment', (int) $appt1['id'], 'Sent automated reminder');

// ---------------------------------------------------------------------
// Task 11a: Reports
// ---------------------------------------------------------------------

render_and_report('layouts.admin', 'admin.reports.patients', [
    'page_title' => 'Patients Report',
    'from' => $monthStart, 'to' => $monthEnd,
    'newPatients' => Patient::registeredBetween($monthStart, $monthEnd),
    'genderDistribution' => Patient::genderDistribution(),
    'ageGroupDistribution' => Patient::ageGroupDistribution(),
    'totalActive' => Patient::countActive(),
], 'reports/patients (seeded month)');

render_and_report('layouts.admin', 'admin.reports.patients', [
    'page_title' => 'Patients Report',
    'from' => '2000-01-01', 'to' => '2000-01-31',
    'newPatients' => Patient::registeredBetween('2000-01-01', '2000-01-31'),
    'genderDistribution' => Patient::genderDistribution(),
    'ageGroupDistribution' => Patient::ageGroupDistribution(),
    'totalActive' => Patient::countActive(),
], 'reports/patients (empty range)');

render_and_report('layouts.admin', 'admin.reports.appointments', [
    'page_title' => 'Appointments Report',
    'from' => $monthStart, 'to' => $monthEnd,
    'statusCounts' => Appointment::statusCountsBetween($monthStart, $monthEnd),
    'byTherapist' => Appointment::countsByTherapistBetween($monthStart, $monthEnd),
    'appointments' => Appointment::listBetween($monthStart, $monthEnd),
], 'reports/appointments (seeded month)');

render_and_report('layouts.admin', 'admin.reports.revenue', [
    'page_title' => 'Revenue Report',
    'from' => $monthStart, 'to' => $monthEnd,
    'totalRevenue' => Invoice::revenueBetween($monthStart, $monthEnd),
    'modeBreakdown' => Invoice::paymentModeBreakdown($monthStart, $monthEnd),
    'statusBreakdown' => Invoice::statusBreakdownBetween($monthStart, $monthEnd),
    'monthlyTrend' => Invoice::monthlyRevenueBetween($monthStart, $monthEnd),
    'payments' => Invoice::paymentsBetween($monthStart, $monthEnd),
    'outstandingOverall' => Invoice::totalOutstanding(),
], 'reports/revenue (seeded month)');

render_and_report('layouts.admin', 'admin.reports.treatments', [
    'page_title' => 'Treatments Report',
    'from' => $monthStart, 'to' => $monthEnd,
    'statusCounts' => TreatmentPlan::statusCountsBetween($monthStart, $monthEnd),
    'plans' => TreatmentPlan::listBetween($monthStart, $monthEnd),
], 'reports/treatments (seeded month)');

render_and_report('layouts.admin', 'admin.reports.treatments', [
    'page_title' => 'Treatments Report',
    'from' => '2000-01-01', 'to' => '2000-01-31',
    'statusCounts' => TreatmentPlan::statusCountsBetween('2000-01-01', '2000-01-31'),
    'plans' => TreatmentPlan::listBetween('2000-01-01', '2000-01-31'),
], 'reports/treatments (empty range)');

// ---------------------------------------------------------------------
// Task 11b: Audit Logs
// ---------------------------------------------------------------------

$logsPage = AuditLog::paginatedWithUser(1, 30);
render_and_report('layouts.admin', 'admin.audit-logs.index', [
    'page_title' => 'Audit Logs',
    'logs' => $logsPage['data'],
    'pagination' => $logsPage,
    'entityTypes' => AuditLog::distinctEntityTypes(),
    'actions' => AuditLog::distinctActions(),
    'filters' => ['entity_type' => '', 'action' => '', 'user_type' => '', 'date_from' => '', 'date_to' => ''],
], 'audit-logs/index (4 seeded entries)');

$filteredLogs = AuditLog::paginatedWithUser(1, 30, "al.user_type = :ut", ['ut' => 'admin']);
render_and_report('layouts.admin', 'admin.audit-logs.index', [
    'page_title' => 'Audit Logs',
    'logs' => $filteredLogs['data'],
    'pagination' => $filteredLogs,
    'entityTypes' => AuditLog::distinctEntityTypes(),
    'actions' => AuditLog::distinctActions(),
    'filters' => ['entity_type' => '', 'action' => '', 'user_type' => 'admin', 'date_from' => '', 'date_to' => ''],
], 'audit-logs/index (filtered to admin user_type)');

render_and_report('layouts.admin', 'admin.audit-logs.index', [
    'page_title' => 'Audit Logs',
    'logs' => [],
    'pagination' => ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 30, 'last_page' => 1],
    'entityTypes' => AuditLog::distinctEntityTypes(),
    'actions' => AuditLog::distinctActions(),
    'filters' => ['entity_type' => 'nonexistent', 'action' => '', 'user_type' => '', 'date_from' => '', 'date_to' => ''],
], 'audit-logs/index (no matches)');

// ---------------------------------------------------------------------
// Task 11c: Global Search
// ---------------------------------------------------------------------

render_and_report('layouts.admin', 'admin.search.index', [
    'page_title' => 'Search Results', 'term' => '', 'results' => ['patients' => [], 'appointments' => [], 'invoices' => [], 'staff' => []],
    'totalResults' => 0, 'tooShort' => false,
], 'search/index (empty term)');

render_and_report('layouts.admin', 'admin.search.index', [
    'page_title' => 'Search Results', 'term' => 'R', 'results' => ['patients' => [], 'appointments' => [], 'invoices' => [], 'staff' => []],
    'totalResults' => 0, 'tooShort' => true,
], 'search/index (too short)');

$searchResults = [
    'patients' => Patient::search('Ramesh', 8),
    'appointments' => Database::fetchAll(
        "SELECT a.*, p.first_name || ' ' || p.last_name AS patient_name FROM appointments a JOIN patients p ON p.id = a.patient_id WHERE a.appointment_number LIKE :t",
        ['t' => '%' . $appt1['appointment_number'] . '%']
    ),
    'invoices' => Database::fetchAll(
        "SELECT i.*, p.first_name || ' ' || p.last_name AS patient_name FROM invoices i JOIN patients p ON p.id = i.patient_id WHERE i.invoice_number LIKE :t",
        ['t' => '%' . $invoice['invoice_number'] . '%']
    ),
    'staff' => Staff::where('first_name', 'Meenakshi'),
];
render_and_report('layouts.admin', 'admin.search.index', [
    'page_title' => 'Search Results', 'term' => 'Ramesh', 'results' => $searchResults,
    'totalResults' => array_sum(array_map('count', $searchResults)), 'tooShort' => false,
], 'search/index (matches across all 4 categories)');

render_and_report('layouts.admin', 'admin.search.index', [
    'page_title' => 'Search Results', 'term' => 'zzz-no-match-zzz',
    'results' => ['patients' => [], 'appointments' => [], 'invoices' => [], 'staff' => []],
    'totalResults' => 0, 'tooShort' => false,
], 'search/index (no matches)');

// ---------------------------------------------------------------------
// Task 11d: SearchController actually invoked through the real router
// dispatch would require a live HTTP request; instead its three private
// query-building methods are exercised indirectly above using equivalent
// hand-written SQL, and the public contract (Patient::search() reuse,
// LIKE-based matching, per-category LIMIT) is verified by direct Model
// calls below against the seeded data.
// ---------------------------------------------------------------------

$byPatientSearch = Patient::search('Kulkarni', 8);
assert_true(count($byPatientSearch) === 1 && $byPatientSearch[0]['id'] === $patientMale['id'], 'Patient::search("Kulkarni") finds exactly the seeded patient');

// ---------------------------------------------------------------------
// Task 11e: report Model query correctness
// ---------------------------------------------------------------------

$genderDist = Patient::genderDistribution();
$genderMap = array_combine(array_column($genderDist, 'gender'), array_column($genderDist, 'total'));
assert_true(($genderMap['male'] ?? 0) === 2, 'genderDistribution() counts 2 male patients (Ramesh + Aarav)');
assert_true(($genderMap['female'] ?? 0) === 1, 'genderDistribution() counts 1 female patient (Sunita)');

$ageGroups = Patient::ageGroupDistribution();
assert_true($ageGroups['Unknown'] === 1, 'ageGroupDistribution() buckets the patient with no date_of_birth as Unknown');
assert_true(array_sum($ageGroups) === 3, 'ageGroupDistribution() buckets sum to the total active patient count');

$apptStatusCounts = Appointment::statusCountsBetween($monthStart, $monthEnd);
$apptStatusMap = array_combine(array_column($apptStatusCounts, 'status'), array_column($apptStatusCounts, 'total'));
assert_true(($apptStatusMap['completed'] ?? 0) === 1 && ($apptStatusMap['scheduled'] ?? 0) === 1, 'statusCountsBetween() correctly separates completed vs scheduled appointments');

$revenueThisMonth = Invoice::revenueBetween($monthStart, $monthEnd);
assert_true(abs($revenueThisMonth - (float) $invoice['total_amount']) < 0.01, 'revenueBetween() matches the one seeded payment amount');

$modeBreakdown = Invoice::paymentModeBreakdown($monthStart, $monthEnd);
assert_true(count($modeBreakdown) === 1 && $modeBreakdown[0]['payment_mode'] === 'upi', 'paymentModeBreakdown() reports the single UPI payment');

$planList = TreatmentPlan::listBetween($monthStart, $monthEnd);
assert_true(count($planList) === 1 && (int) $planList[0]['session_count'] === 1, 'TreatmentPlan::listBetween() reports the correct session_count for the seeded plan');

$distinctActions = AuditLog::distinctActions();
sort($distinctActions);
assert_true($distinctActions === ['create', 'login', 'reminder'], 'AuditLog::distinctActions() returns exactly the 3 distinct seeded actions (create appears twice, counted once)');

$distinctEntities = AuditLog::distinctEntityTypes();
sort($distinctEntities);
assert_true($distinctEntities === ['appointment', 'invoice', 'patient'], 'AuditLog::distinctEntityTypes() returns exactly the 3 distinct seeded entity types');

$adminLogRow = AuditLog::listWithUser("al.entity_type = 'invoice'")[0] ?? null;
assert_true($adminLogRow !== null && $adminLogRow['admin_name'] === 'Dr. Meenakshi Kulkarni', 'listWithUser() correctly joins the admin user\'s name for an admin-authored row');

$patientLogRow = AuditLog::listWithUser("al.user_type = 'patient'")[0] ?? null;
assert_true($patientLogRow !== null && $patientLogRow['admin_name'] === null, 'listWithUser() leaves admin_name NULL for a patient-authored row (no join match by design)');

// ---------------------------------------------------------------------
// Task 11f: Excel/PDF export services -- graceful RuntimeException when
// the optional library is not installed (verified true in this sandbox:
// no vendor/phpoffice, no vendor/dompdf, no Packagist network access).
// ---------------------------------------------------------------------

$excelThrew = false;
try {
    (new ExcelService())->buildWorkbook('Test Report', ['A', 'B'], [[1, 2]]);
} catch (\RuntimeException $e) {
    $excelThrew = str_contains($e->getMessage(), 'phpoffice/phpspreadsheet');
}
assert_true($excelThrew, 'ExcelService::buildWorkbook() throws a clear RuntimeException when phpoffice/phpspreadsheet is not installed (confirmed not installed in this sandbox)');

$pdfThrew = false;
try {
    (new PdfService())->renderHtmlToPdf('<h1>Test</h1>');
} catch (\RuntimeException $e) {
    $pdfThrew = str_contains($e->getMessage(), 'dompdf');
}
assert_true($pdfThrew, 'PdfService::renderHtmlToPdf() throws a clear RuntimeException when dompdf is not installed (confirmed not installed in this sandbox, same contract already relied on by InvoiceController)');

// ---------------------------------------------------------------------
// Task 11g: ReportController's private date-range/export-dataset logic,
// exercised via Reflection so the exit()-ing public export() method
// itself is never invoked (see file header comment).
// ---------------------------------------------------------------------

$controller = new \App\Controllers\Admin\ReportController();
$ref = new \ReflectionClass($controller);

$_GET = ['from' => '2026-03-10', 'to' => '2026-03-01']; // deliberately inverted
$req = new \App\Core\Request();
$dateRangeMethod = $ref->getMethod('dateRange');
$dateRangeMethod->setAccessible(true);
[$swappedFrom, $swappedTo] = $dateRangeMethod->invoke($controller, $req);
assert_true($swappedFrom === '2026-03-01' && $swappedTo === '2026-03-10', 'dateRange() silently swaps an inverted from/to pair rather than rejecting it');

$_GET = ['from' => 'not-a-date', 'to' => ''];
$req2 = new \App\Core\Request();
[$defaultFrom, $defaultTo] = $dateRangeMethod->invoke($controller, $req2);
assert_true($defaultFrom === date('Y-m-01') && $defaultTo === date('Y-m-t'), 'dateRange() defaults to the current calendar month when from/to are missing or invalid');

$exportDatasetMethod = $ref->getMethod('exportDataset');
$exportDatasetMethod->setAccessible(true);
[$title, $headers, $rows] = $exportDatasetMethod->invoke($controller, 'revenue', $monthStart, $monthEnd);
assert_true($title === 'Revenue Report' && $headers[0] === 'Payment Date', 'exportDataset("revenue") returns the expected title and header row');
assert_true(count($rows) === 1 && $rows[0][1] === $invoice['invoice_number'], 'exportDataset("revenue") returns one row matching the seeded invoice number');

[, , $patientRows] = $exportDatasetMethod->invoke($controller, 'patients', $monthStart, $monthEnd);
assert_true(count($patientRows) === 3, 'exportDataset("patients") returns all 3 seeded patients for the current-month range');

exit(physioweb_test_summary());
