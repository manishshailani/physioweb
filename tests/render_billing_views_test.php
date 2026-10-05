<?php

declare(strict_types=1);

/**
 * Execution-based verification for Task 9 (billing): services, packages,
 * invoices (index/form/show/pdf) admin views, plus the Invoice model's new
 * paginatedWithPatient() and the PdfService's documented graceful-failure
 * path when dompdf is not installed (true in this sandbox - see
 * app/Services/PdfService.php's own doc comment).
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\View;
use App\Core\Database;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Package;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\PdfService;

physioweb_test_db();
View::init(PHYSIOWEB_ROOT . '/app/Views');

$warnings = [];
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$warnings) {
    $warnings[] = "$errstr in $errfile:$errline";
    return true;
});

function render_and_report(string $template, array $data, string $label): void
{
    try {
        $html = View::render($template, $data);
        echo "  ok - rendered {$label} (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "  FAIL - {$label} threw: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

$common = [
    'csrf_field' => '<input type="hidden" name="_csrf" value="test">',
    'csrf_token' => 'test',
    'current_admin' => ['id' => 1, 'name' => 'Dr. Meenakshi', 'role' => 'admin'],
    'current_patient' => null,
    'flash_success' => null,
    'flash_error' => null,
    'flash_errors' => [],
    'old' => [],
];

// ===== Services =====
render_and_report('admin.services.index', $common + ['page_title' => 'Services', 'services' => []], 'services/index (empty)');

$serviceId = Service::create([
    'name' => 'Neuro Rehabilitation', 'slug' => Service::uniqueSlug('Neuro Rehabilitation'), 'category' => 'Neuro Rehabilitation',
    'short_description' => 'Short', 'description' => 'Desc', 'duration_minutes' => 45, 'fee' => 500, 'gst_rate' => 18,
    'display_order' => 0, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$services = Service::all('display_order, name');
render_and_report('admin.services.index', $common + ['page_title' => 'Services', 'services' => $services], 'services/index (1 service)');
render_and_report('admin.services.form', $common + ['page_title' => 'Edit Service', 'service' => Service::find($serviceId)], 'services/form (edit)');

// ===== Packages =====
render_and_report('admin.packages.index', $common + ['page_title' => 'Packages', 'packages' => []], 'packages/index (empty)');
$packageId = Package::create([
    'name' => '10-Session Pack', 'description' => null, 'total_sessions' => 10, 'price' => 4000,
    'discount_percent' => 10, 'validity_days' => 90, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$packages = Package::all('price');
render_and_report('admin.packages.index', $common + ['page_title' => 'Packages', 'packages' => $packages], 'packages/index (1 package, with discount)');
render_and_report('admin.packages.form', $common + ['page_title' => 'Edit Package', 'package' => Package::find($packageId)], 'packages/form (edit)');

assert_true(abs(Package::discountedPrice(Package::find($packageId)) - 3600.0) < 0.001, 'discountedPrice applies the 10% discount correctly (4000 -> 3600)');

// ===== Invoices =====
$reg = Patient::register([
    'first_name' => 'Asha', 'last_name' => 'Kulkarni', 'gender' => 'female',
    'date_of_birth' => '1990-05-12', 'mobile' => '9900011122', 'alt_mobile' => null,
    'email' => 'asha@example.com', 'address' => 'MG Road', 'city' => 'Belgaum',
    'state' => 'Karnataka', 'country' => 'India', 'pincode' => '590001',
    'blood_group' => 'B+', 'allergies' => null, 'existing_conditions' => null, 'medical_history' => null,
    'emergency_contact_name' => null, 'emergency_contact_relationship' => null, 'emergency_contact_mobile' => null,
    'is_active' => 1,
]);
$patientId = $reg['id'];
$patients = Patient::activePaginated(1, 500)['data'];

render_and_report('admin.invoices.form', $common + [
    'page_title' => 'New Invoice', 'patients' => $patients, 'services' => $services, 'preselectedPatientId' => $patientId,
], 'invoices/form (create)');

$invResult = Invoice::createWithItems([
    'patient_id' => $patientId, 'invoice_date' => date('Y-m-d'), 'due_date' => null,
    'discount_amount' => 50, 'notes' => 'First visit package', 'created_by_user_id' => null,
], [
    ['service_id' => $serviceId, 'description' => 'Neuro Rehabilitation session', 'quantity' => 2, 'unit_price' => 500, 'gst_rate' => 18],
]);
$invoiceId = $invResult['id'];

// Pagination/listing
$paginated = Invoice::paginatedWithPatient(1, 15);
render_and_report('admin.invoices.index', $common + [
    'page_title' => 'Invoices', 'invoices' => $paginated['data'], 'pagination' => $paginated,
    'statuses' => ['unpaid', 'partially_paid', 'paid', 'cancelled'], 'filters' => ['status' => '', 'q' => ''],
], 'invoices/index (1 invoice, unpaid)');
assert_true($paginated['total'] === 1, 'paginatedWithPatient total count matches the 1 created invoice');

$searchPaginated = Invoice::paginatedWithPatient(1, 15, '(i.invoice_number LIKE :q OR p.patient_code LIKE :q)', ['q' => '%' . $reg['patient_code'] . '%']);
assert_true($searchPaginated['total'] === 1, 'search filter by patient_code finds the invoice');
$noMatchPaginated = Invoice::paginatedWithPatient(1, 15, 'status = :status', ['status' => 'paid']);
assert_true($noMatchPaginated['data'] === [], 'status=paid filter yields empty result before any payment is recorded');

// Record a partial payment, then full payment, exercising recalculateStatus via the real Payment model
Payment::recordForInvoice($invoiceId, ['amount' => 500, 'payment_mode' => 'cash', 'payment_date' => date('Y-m-d'), 'reference_number' => null, 'received_by_user_id' => null, 'notes' => null]);
$invoiceAfterPartial = Invoice::withPatient($invoiceId);
assert_true($invoiceAfterPartial['status'] === 'partially_paid', 'invoice status becomes partially_paid after a partial payment');

render_and_report('admin.invoices.show', $common + [
    'page_title' => 'Invoice', 'invoice' => $invoiceAfterPartial, 'items' => Invoice::itemsFor($invoiceId),
    'payments' => Invoice::paymentsFor($invoiceId), 'paymentModes' => ['cash', 'upi', 'credit_card', 'debit_card', 'net_banking'],
    'outstanding' => Invoice::outstandingBalance($invoiceAfterPartial),
], 'invoices/show (partially paid, outstanding > 0 shows payment form)');

// Total = subtotal(1000) + gst(180) - discount(50) = 1130; already paid 500, so 630 remains.
Payment::recordForInvoice($invoiceId, ['amount' => 630, 'payment_mode' => 'upi', 'payment_date' => date('Y-m-d'), 'reference_number' => 'UPI123', 'received_by_user_id' => null, 'notes' => null]);
$invoiceAfterFull = Invoice::withPatient($invoiceId);
assert_true($invoiceAfterFull['status'] === 'paid', 'invoice status becomes paid once amount_paid reaches total_amount');
assert_true(Invoice::outstandingBalance($invoiceAfterFull) === 0.0, 'outstanding balance is exactly zero once fully paid');

render_and_report('admin.invoices.show', $common + [
    'page_title' => 'Invoice', 'invoice' => $invoiceAfterFull, 'items' => Invoice::itemsFor($invoiceId),
    'payments' => Invoice::paymentsFor($invoiceId), 'paymentModes' => ['cash', 'upi', 'credit_card', 'debit_card', 'net_banking'],
    'outstanding' => Invoice::outstandingBalance($invoiceAfterFull),
], 'invoices/show (fully paid, outstanding = 0 hides payment form)');

// Over-payment rejection still works through the real admin flow's model call
try {
    Payment::recordForInvoice($invoiceId, ['amount' => 1, 'payment_mode' => 'cash', 'payment_date' => date('Y-m-d'), 'reference_number' => null, 'received_by_user_id' => null, 'notes' => null]);
    echo "  FAIL - over-payment was not rejected\n";
    exit(1);
} catch (\InvalidArgumentException $e) {
    echo "  ok - over-payment on a fully-paid invoice is rejected (" . $e->getMessage() . ")\n";
}

// ===== PDF template (rendered to HTML - the same HTML InvoiceController::pdf() hands to PdfService) =====
$pdfHtml = View::render('admin.invoices.pdf', [
    'invoice' => $invoiceAfterFull, 'items' => Invoice::itemsFor($invoiceId), 'payments' => Invoice::paymentsFor($invoiceId),
    'outstanding' => Invoice::outstandingBalance($invoiceAfterFull), 'settings' => [],
]);
assert_true(str_contains($pdfHtml, $invoiceAfterFull['invoice_number']), 'invoice PDF HTML template includes the invoice number');
assert_true(str_contains($pdfHtml, 'Asha Kulkarni'), 'invoice PDF HTML template includes the patient name');
echo "  ok - rendered admin.invoices.pdf standalone HTML template (" . strlen($pdfHtml) . " bytes)\n";

// PdfService's documented behavior when dompdf is not installed (true in this sandbox):
// it must throw RuntimeException rather than fatal-error, so the controller can catch it
// and flash a friendly message instead of a 500. We verify that contract directly.
try {
    (new PdfService())->renderHtmlToPdf($pdfHtml);
    echo "  NOTE - dompdf IS installed in this environment; PDF rendering executed directly (not just reviewed).\n";
} catch (\RuntimeException $e) {
    echo "  ok - PdfService::renderHtmlToPdf throws RuntimeException (not a fatal error) when dompdf is absent, exactly as InvoiceController::pdf() expects to catch\n";
}

restore_error_handler();

if ($warnings !== []) {
    echo "\n" . count($warnings) . " PHP warning(s)/notice(s) emitted during rendering:\n";
    foreach ($warnings as $w) {
        echo " - {$w}\n";
    }
    exit(1);
}

exit(physioweb_test_summary());
