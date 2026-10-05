<?php

declare(strict_types=1);

/**
 * Smoke test: exercises the Model layer against a real (in-memory SQLite)
 * database to prove the core business logic actually executes correctly —
 * not just that it lints. See tests/README.md for scope and limitations.
 *
 * Run: php tests/smoke_test_models.php
 */

require __DIR__ . '/bootstrap_test.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\IdGenerator;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Therapist;
use App\Models\User;

physioweb_test_db();
Auth::configure(5, 15);

echo "== Patient registration + sequential ID generation ==\n";
$p1 = Patient::register([
    'first_name' => 'Priya', 'last_name' => 'Sharma', 'gender' => 'female',
    'mobile' => '9811111111', 'email' => 'priya@example.com',
]);
$p2 = Patient::register([
    'first_name' => 'Arjun', 'last_name' => 'Kapoor', 'gender' => 'male',
    'mobile' => '9822222222', 'email' => 'arjun@example.com',
]);
assert_equals('PAT-' . date('Y') . '-0001', $p1['patient_code'], 'first patient gets PAT-YYYY-0001');
assert_equals('PAT-' . date('Y') . '-0002', $p2['patient_code'], 'second patient gets sequential 0002');

echo "\n== Patient search ==\n";
$results = Patient::search('Sharma');
assert_equals(1, count($results), 'search by last name finds exactly one match');
$results = Patient::search('Priya Sharma');
assert_equals(1, count($results), 'search by full name (concat) finds the patient on SQLite driver');

echo "\n== Staff + Therapist + booking conflict detection ==\n";
$staff = Staff::register([
    'first_name' => 'Anjali', 'last_name' => 'Rao', 'role' => 'physiotherapist',
    'is_active' => 1,
]);
$therapistId = Database::insert('therapists', [
    'staff_id' => $staff['id'], 'slug' => 'dr-anjali-rao', 'is_public' => 1, 'display_order' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$serviceId = Database::insert('services', [
    'name' => 'Orthopedic Physiotherapy', 'slug' => 'ortho', 'fee' => 1000, 'gst_rate' => 0,
    'duration_minutes' => 45, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$appt1 = Appointment::book([
    'patient_id' => $p1['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => '2026-10-10', 'scheduled_time' => '10:00:00', 'duration_minutes' => 45,
    'status' => 'scheduled', 'source' => 'admin',
]);
assert_equals('APT-' . date('Y') . '-0001', $appt1['appointment_number'], 'first appointment gets APT-YYYY-0001');

$conflict = Therapist::hasConflict($therapistId, '2026-10-10', '10:15:00', 30);
assert_true($conflict, 'overlapping slot (10:15, within 10:00-10:45) is detected as a conflict');

$noConflict = Therapist::hasConflict($therapistId, '2026-10-10', '11:00:00', 30);
assert_true(!$noConflict, 'non-overlapping slot (11:00) is NOT flagged as a conflict');

echo "\n== Invoice creation, GST/discount math, payment & balance tracking ==\n";
$invoice = Invoice::createWithItems(
    ['patient_id' => $p1['id'], 'invoice_date' => '2026-10-03', 'discount_amount' => 50],
    [
        ['service_id' => $serviceId, 'description' => 'Orthopedic Physiotherapy', 'quantity' => 2, 'unit_price' => 1000, 'gst_rate' => 18],
    ]
);
// subtotal = 2*1000 = 2000; gst = 2000*0.18 = 360; total = 2000+360-50 = 2310
assert_equals('INV-' . date('Y') . '-0001', $invoice['invoice_number'], 'first invoice gets INV-YYYY-0001');
assert_equals(2310.0, $invoice['total_amount'], 'invoice total = subtotal + GST - discount (2000 + 360 - 50)');

$row = Invoice::find($invoice['id']);
assert_equals('unpaid', $row['status'], 'new invoice starts unpaid');

Payment::recordForInvoice($invoice['id'], [
    'amount' => 1000, 'payment_mode' => 'upi', 'payment_date' => '2026-10-03',
]);
$row = Invoice::find($invoice['id']);
assert_equals('partially_paid', $row['status'], 'partial payment -> status partially_paid');
assert_equals(1000.0, (float) $row['amount_paid'], 'amount_paid reflects the single payment recorded');
assert_equals(1310.0, Invoice::outstandingBalance($row), 'outstanding balance = total - paid (2310 - 1000)');

Payment::recordForInvoice($invoice['id'], [
    'amount' => 1310, 'payment_mode' => 'cash', 'payment_date' => '2026-10-04',
]);
$row = Invoice::find($invoice['id']);
assert_equals('paid', $row['status'], 'full payment brings status to paid');
assert_equals(0.0, Invoice::outstandingBalance($row), 'outstanding balance is zero once fully paid');

echo "\n== Over-payment rejection ==\n";
$rejected = false;
try {
    Payment::recordForInvoice($invoice['id'], ['amount' => 500, 'payment_mode' => 'cash', 'payment_date' => '2026-10-05']);
} catch (\InvalidArgumentException $e) {
    $rejected = true;
}
assert_true($rejected, 'a payment that would exceed the invoice total is rejected');

echo "\n== Auth: password hashing, login, lockout ==\n";
$hash = Auth::hashPassword('Admin@12345');
Database::insert('users', [
    'name' => 'Clinic Admin', 'email' => 'admin@physioweb.test', 'password_hash' => $hash,
    'role' => 'admin', 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$result = Auth::attemptAdminLogin('admin@physioweb.test', 'wrong-password', '127.0.0.1');
assert_true(!$result['ok'], 'login with wrong password fails');

$result = Auth::attemptAdminLogin('admin@physioweb.test', 'Admin@12345', '127.0.0.1');
assert_true($result['ok'], 'login with correct password succeeds');

for ($i = 0; $i < 5; $i++) {
    Auth::attemptAdminLogin('admin@physioweb.test', 'wrong-again', '10.0.0.9');
}
$locked = Auth::attemptAdminLogin('admin@physioweb.test', 'Admin@12345', '10.0.0.9');
assert_true(!$locked['ok'], 'account is locked out after 5 failed attempts from the same IP, even with the correct password');
assert_true(str_contains($locked['error'], 'Too many'), 'lockout error message explains the reason');

echo "\n== Service slug uniqueness ==\n";
$slug1 = Service::uniqueSlug('Sports Injury Rehabilitation');
Database::insert('services', [
    'name' => 'Sports Injury Rehabilitation', 'slug' => $slug1, 'fee' => 1200, 'gst_rate' => 0,
    'duration_minutes' => 45, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$slug2 = Service::uniqueSlug('Sports Injury Rehabilitation');
assert_true($slug1 !== $slug2, 'a second service with the same name gets a different, de-duplicated slug');
assert_equals('sports-injury-rehabilitation-2', $slug2, 'de-duplication appends -2 on collision');

echo "\n== IdGenerator year rollover isolation ==\n";
$thisYear = IdGenerator::next('TST');
$otherYear = IdGenerator::next('TST', ((int) date('Y')) + 1);
assert_true(str_ends_with($thisYear, '-0001'), 'first id this year is 0001');
assert_true(str_ends_with($otherYear, '-0001'), 'sequence for a different year starts independently at 0001');

exit(physioweb_test_summary());
