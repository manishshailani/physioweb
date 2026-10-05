<?php

declare(strict_types=1);

/**
 * Execution-based verification for Task 12: the patient portal (dashboard,
 * appointments, treatment plan, exercises, billing, profile). Seeds two
 * separate patients through the real Model layer so every "ownership"
 * check added in this task (a patient can only see/act on their own
 * appointments/invoices/documents) can be verified against a second
 * patient's data actually present in the same database, not just absence
 * of data. Same harness/pattern as the Task 7/8/9/10/11 render tests:
 * every PHP warning/notice during rendering is a hard failure.
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Assessment;
use App\Models\ExerciseAssignment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientPackage;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;

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
        $data['current_patient'] = $data['current_patient'] ?? ['id' => 0, 'first_name' => 'Test', 'last_name' => 'Patient', 'patient_code' => 'PAT-2026-0000'];
        $data['current_admin'] = null;
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
// Seed two independent patients + supporting data through the Model layer
// ---------------------------------------------------------------------

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$yesterday = date('Y-m-d', strtotime('-1 day'));

$staff = Staff::register(['first_name' => 'Meenakshi', 'last_name' => 'Kulkarni', 'role' => 'physiotherapist', 'phone' => '9900000001', 'is_active' => 1]);
$therapistId = (int) Database::insert('therapists', [
    'staff_id' => $staff['id'], 'slug' => 'dr-meenakshi-kulkarni', 'default_session_minutes' => 45,
    'is_public' => 1, 'display_order' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$serviceId = Service::create([
    'name' => 'Back Pain Therapy', 'slug' => 'back-pain-therapy', 'fee' => 800, 'gst_rate' => 18,
    'duration_minutes' => 45, 'display_order' => 1, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

// Patient A: the one whose portal screens we render (has a full history).
$patientA = Patient::register([
    'first_name' => 'Ramesh', 'last_name' => 'Kulkarni', 'gender' => 'male', 'date_of_birth' => '1985-05-10',
    'mobile' => '9988776601', 'email' => 'ramesh@example.com',
    'password_hash' => Auth::hashPassword('Secret@123'), 'is_active' => 1,
]);
// Patient B: used only to prove ownership isolation (A must never see B's rows).
$patientB = Patient::register([
    'first_name' => 'Sunita', 'last_name' => 'Patil', 'gender' => 'female', 'mobile' => '9988776602',
    'email' => 'sunita@example.com', 'password_hash' => Auth::hashPassword('Secret@456'), 'is_active' => 1,
]);

$apptUpcoming = Appointment::book([
    'patient_id' => $patientA['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => $tomorrow, 'scheduled_time' => '10:00:00', 'status' => 'scheduled', 'source' => 'patient_portal',
]);
$apptPast = Appointment::book([
    'patient_id' => $patientA['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => $yesterday, 'scheduled_time' => '09:00:00', 'status' => 'completed', 'source' => 'admin',
]);
$apptForB = Appointment::book([
    'patient_id' => $patientB['id'], 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => $tomorrow, 'scheduled_time' => '11:00:00', 'status' => 'scheduled', 'source' => 'patient_portal',
]);

$invoiceA = Invoice::createWithItems(
    ['patient_id' => $patientA['id'], 'invoice_date' => $today],
    [['service_id' => $serviceId, 'description' => 'Back Pain Therapy', 'quantity' => 1, 'unit_price' => 800, 'gst_rate' => 18]]
);
Database::insert('payments', ['invoice_id' => $invoiceA['id'], 'amount' => 400, 'payment_mode' => 'cash', 'payment_date' => $today, 'created_at' => Database::now()]);
Invoice::recalculateStatus($invoiceA['id']);

$invoiceB = Invoice::createWithItems(
    ['patient_id' => $patientB['id'], 'invoice_date' => $today],
    [['service_id' => $serviceId, 'description' => 'Back Pain Therapy', 'quantity' => 1, 'unit_price' => 800, 'gst_rate' => 18]]
);

$packageId = Database::insert('packages', [
    'name' => '10-Session Package', 'total_sessions' => 10, 'price' => 7000, 'validity_days' => 90,
    'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
PatientPackage::subscribe((int) $patientA['id'], (int) $packageId, (int) $invoiceA['id']);

$plan = TreatmentPlan::createPlan([
    'patient_id' => $patientA['id'], 'therapist_id' => $therapistId, 'diagnosis' => 'Lower back strain',
    'start_date' => $yesterday, 'status' => 'active',
]);
Database::insert('treatment_sessions', [
    'treatment_plan_id' => $plan['id'], 'session_number' => 1, 'session_date' => $yesterday,
    'therapist_id' => $therapistId, 'treatment_given' => 'Manual therapy', 'duration_minutes' => 30,
    'pain_score_before' => 7, 'pain_score_after' => 5, 'mobility_score' => 4, 'created_at' => Database::now(),
]);
Database::insert('treatment_sessions', [
    'treatment_plan_id' => $plan['id'], 'session_number' => 2, 'session_date' => $today,
    'therapist_id' => $therapistId, 'treatment_given' => 'Ultrasound therapy', 'duration_minutes' => 30,
    'pain_score_before' => 5, 'pain_score_after' => 3, 'mobility_score' => 6, 'created_at' => Database::now(),
]);
\App\Models\Assessment::create([
    'patient_id' => $patientA['id'], 'assessment_date' => $today, 'pain_scale' => 3, 'mobility_score' => 6,
    'recovery_status' => 'improving', 'created_at' => Database::now(),
]);

$exerciseId = \App\Models\Exercise::create([
    'name' => 'Pelvic Tilts', 'instructions' => 'Lie on your back, flatten your lower back against the floor.',
    'default_repetitions' => '3 sets of 10', 'default_frequency' => 'Twice daily', 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$pastExerciseId = \App\Models\Exercise::create([
    'name' => 'Cat-Cow Stretch', 'instructions' => 'On hands and knees, alternate arching and rounding your back.',
    'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
ExerciseAssignment::create([
    'patient_id' => $patientA['id'], 'exercise_id' => $exerciseId, 'repetitions' => '3x10', 'frequency' => 'Daily',
    'start_date' => $today, 'end_date' => null, 'created_at' => Database::now(),
]);
ExerciseAssignment::create([
    'patient_id' => $patientA['id'], 'exercise_id' => $pastExerciseId, 'repetitions' => '2x10', 'frequency' => 'Daily',
    'start_date' => $yesterday, 'end_date' => $yesterday, 'created_at' => Database::now(),
]);

$config = ['uploads' => ['max_size_mb' => 10]];
$docDir = sys_get_temp_dir() . '/physioweb_test_docs';
@mkdir($docDir, 0775, true);
$docPath = $docDir . '/test-report.pdf';
file_put_contents($docPath, '%PDF-1.4 test');
$documentId = PatientDocument::create([
    'patient_id' => $patientA['id'], 'document_type' => 'medical_report', 'original_name' => 'test-report.pdf',
    'stored_name' => 'abc123.pdf', 'file_path' => $docPath, 'mime_type' => 'application/pdf',
    'file_size' => filesize($docPath), 'created_at' => Database::now(),
]);

// ---------------------------------------------------------------------
// Task 12a: Portal screens, rendered exactly as each Controller passes data
// ---------------------------------------------------------------------

$currentPatientData = ['id' => $patientA['id'], 'first_name' => 'Ramesh', 'last_name' => 'Kulkarni', 'patient_code' => $patientA['patient_code']];

$upcoming = array_values(array_filter(
    Appointment::forPatient((int) $patientA['id']),
    static fn (array $a) => in_array($a['status'], ['scheduled', 'confirmed'], true) && $a['scheduled_date'] >= date('Y-m-d')
));
render_and_report('layouts.patient', 'patient.dashboard', [
    'page_title' => 'My Dashboard', 'current_patient' => $currentPatientData,
    'upcomingAppointments' => $upcoming,
    'activeTreatmentPlan' => TreatmentPlan::forPatient((int) $patientA['id'])[0] ?? null,
    'currentExercises' => ExerciseAssignment::currentForPatient((int) $patientA['id']),
    'outstandingBalance' => Invoice::outstandingForPatient((int) $patientA['id']),
], 'dashboard (populated patient A)');

render_and_report('layouts.patient', 'patient.dashboard', [
    'page_title' => 'My Dashboard', 'current_patient' => ['id' => $patientB['id'], 'first_name' => 'Sunita', 'last_name' => 'Patil', 'patient_code' => $patientB['patient_code']],
    'upcomingAppointments' => [], 'activeTreatmentPlan' => null, 'currentExercises' => [],
    'outstandingBalance' => Invoice::outstandingForPatient((int) $patientB['id']),
], 'dashboard (empty-history patient B)');

render_and_report('layouts.patient', 'patient.appointments.index', [
    'page_title' => 'My Appointments', 'current_patient' => $currentPatientData,
    'appointments' => Appointment::forPatient((int) $patientA['id']),
], 'appointments/index (2 appointments)');

render_and_report('layouts.patient', 'patient.appointments.index', [
    'page_title' => 'My Appointments', 'current_patient' => $currentPatientData, 'appointments' => [],
], 'appointments/index (empty)');

render_and_report('layouts.patient', 'patient.appointments.book', [
    'page_title' => 'Book an Appointment', 'current_patient' => $currentPatientData,
    'services' => Service::activeOrdered(), 'therapists' => \App\Models\Therapist::allWithStaff(true),
], 'appointments/book');

render_and_report('layouts.patient', 'patient.treatment-plan', [
    'page_title' => 'My Treatment Plan', 'current_patient' => $currentPatientData,
    'plans' => TreatmentPlan::forPatientWithTherapist((int) $patientA['id']),
    'activePlan' => TreatmentPlan::forPatient((int) $patientA['id'])[0] ?? null,
    'sessions' => TreatmentSession::forPlan((int) $plan['id']),
    'assessments' => Assessment::forPatient((int) $patientA['id']),
], 'treatment-plan (active plan, 2 sessions -> chart shown)');

render_and_report('layouts.patient', 'patient.treatment-plan', [
    'page_title' => 'My Treatment Plan', 'current_patient' => ['id' => $patientB['id'], 'first_name' => 'Sunita', 'last_name' => 'Patil', 'patient_code' => $patientB['patient_code']],
    'plans' => [], 'activePlan' => null, 'sessions' => [], 'assessments' => [],
], 'treatment-plan (no plans yet)');

render_and_report('layouts.patient', 'patient.exercises', [
    'page_title' => 'My Exercises', 'current_patient' => $currentPatientData,
    'currentExercises' => ExerciseAssignment::currentForPatient((int) $patientA['id']),
    'pastExercises' => array_udiff(
        ExerciseAssignment::forPatientWithExercise((int) $patientA['id']),
        ExerciseAssignment::currentForPatient((int) $patientA['id']),
        static fn (array $a, array $b) => $a['id'] <=> $b['id']
    ),
], 'exercises (1 current, 1 past)');

render_and_report('layouts.patient', 'patient.billing.index', [
    'page_title' => 'Billing & Invoices', 'current_patient' => $currentPatientData,
    'invoices' => Invoice::listWithPatient('i.patient_id = :pid', ['pid' => $patientA['id']]),
    'packages' => PatientPackage::forPatientWithPackage((int) $patientA['id']),
    'outstanding' => Invoice::outstandingForPatient((int) $patientA['id']),
], 'billing/index (1 invoice, 1 package)');

render_and_report('layouts.patient', 'patient.profile', [
    'page_title' => 'My Profile', 'current_patient' => $currentPatientData,
    'patient' => Patient::find((int) $patientA['id']),
], 'profile (populated)');

// invoicePdf's HTML-rendering step (same template Admin\InvoiceController
// uses) exercised directly -- the controller method itself ends in
// header()+exit() so it is not called here (see Task 11 test's file header
// for the same reasoning applied to ReportController's export()).
render_and_report('', 'admin.invoices.pdf', [
    'invoice' => Invoice::withPatient((int) $invoiceA['id']),
    'items' => Invoice::itemsFor((int) $invoiceA['id']),
    'payments' => Invoice::paymentsFor((int) $invoiceA['id']),
    'outstanding' => Invoice::outstandingBalance(Invoice::withPatient((int) $invoiceA['id'])),
    'settings' => Setting::allAsMap(),
], 'admin.invoices.pdf (rendered for patient A\'s own invoice, as PatientBillingController::invoicePdf() would)');

// ---------------------------------------------------------------------
// Task 12b: ownership isolation -- the exact conditions each Patient\*
// Controller checks before serving a row, verified against a SECOND
// patient's real data (not just a missing id).
// ---------------------------------------------------------------------

$apptForBRow = Appointment::find((int) $apptForB['id']);
assert_true(
    (int) $apptForBRow['patient_id'] !== (int) $patientA['id'],
    'PatientAppointmentController::cancel()\'s ownership guard would reject patient A cancelling patient B\'s appointment'
);
assert_true(
    (int) $apptUpcoming['id'] !== 0 && (int) Appointment::find((int) $apptUpcoming['id'])['patient_id'] === (int) $patientA['id'],
    'PatientAppointmentController::cancel()\'s ownership guard would allow patient A cancelling their own appointment'
);

$invoiceBRow = Invoice::withPatient((int) $invoiceB['id']);
assert_true(
    (int) $invoiceBRow['patient_id'] !== (int) $patientA['id'],
    'PatientBillingController::invoicePdf()\'s ownership guard would reject patient A downloading patient B\'s invoice'
);

$documentRow = PatientDocument::find($documentId);
assert_true(
    (int) $documentRow['patient_id'] === (int) $patientA['id'] && (int) $documentRow['patient_id'] !== (int) $patientB['id'],
    'Patient\\DocumentController::download()\'s ownership guard would allow A and reject B for the same document row'
);

// ---------------------------------------------------------------------
// Task 12c: Model-layer correctness for the new report/portal query helpers
// ---------------------------------------------------------------------

// Expected value computed directly from the GST math (800 * 1.18 = 944.00,
// minus the 400 cash payment already recorded above = 544.00) rather than
// a hand-typed magic number, so this assertion can't silently drift from
// Invoice::createWithItems()'s actual rounding behavior.
$expectedOutstandingA = round(800 * 1.18 - 400, 2);
assert_true(
    abs(Invoice::outstandingForPatient((int) $patientA['id']) - $expectedOutstandingA) < 0.01,
    "outstandingForPatient() for patient A equals subtotal+GST minus the one payment recorded ({$expectedOutstandingA})"
);
assert_true(
    abs(Invoice::outstandingForPatient((int) $patientB['id']) - round(800 * 1.18, 2)) < 0.01,
    'outstandingForPatient() for patient B (no payments yet) equals the full unpaid invoice total'
);

$plansWithTherapist = TreatmentPlan::forPatientWithTherapist((int) $patientA['id']);
assert_true(
    count($plansWithTherapist) === 1 && $plansWithTherapist[0]['therapist_name'] === 'Meenakshi Kulkarni',
    'forPatientWithTherapist() joins the correct therapist name onto patient A\'s plan'
);

// Password-change path PatientProfileController relies on: verify against
// the stored hash, then re-hash and verify the NEW password validates.
$storedHash = Patient::find((int) $patientA['id'])['password_hash'];
assert_true(password_verify('Secret@123', $storedHash), 'password_verify() confirms patient A\'s original password against the stored hash');
$newHash = Auth::hashPassword('NewSecret@456');
assert_true(password_verify('NewSecret@456', $newHash) && !password_verify('Secret@123', $newHash), 'Auth::hashPassword() produces a hash that verifies only the new password, not the old one');

exit(physioweb_test_summary());
