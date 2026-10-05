<?php

declare(strict_types=1);

/**
 * Execution-based verification (not just php -l) for the three new patient
 * admin views: renders each with real data produced by the actual Model
 * layer against an in-memory SQLite DB, and treats any PHP warning/notice
 * emitted during rendering as a test failure (undefined-variable/array-key
 * access would otherwise only show up at runtime in production).
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\View;
use App\Models\Patient;
use App\Models\Staff;
use App\Models\Therapist;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\TreatmentPlan;
use App\Models\Assessment;
use App\Models\Exercise;
use App\Models\ExerciseAssignment;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PatientPackage;
use App\Models\PatientDocument;
use App\Core\Database;

$pdo = physioweb_test_db();
View::init(PHYSIOWEB_ROOT . '/app/Views');

$warnings = [];
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$warnings) {
    $warnings[] = "$errstr in $errfile:$errline";
    return true;
});

// --- Build a realistic graph of related rows through the real Model layer ---
$reg = Patient::register([
    'first_name' => 'Asha', 'last_name' => 'Kulkarni', 'gender' => 'female',
    'date_of_birth' => '1990-05-12', 'mobile' => '9900011122', 'alt_mobile' => null,
    'email' => 'asha@example.com', 'address' => 'MG Road', 'city' => 'Belgaum',
    'state' => 'Karnataka', 'country' => 'India', 'pincode' => '590001',
    'blood_group' => 'B+', 'allergies' => 'None', 'existing_conditions' => 'Knee pain',
    'medical_history' => 'ACL surgery 2019',
    'emergency_contact_name' => 'Ramesh Kulkarni', 'emergency_contact_relationship' => 'Spouse',
    'emergency_contact_mobile' => '9900099999', 'is_active' => 1,
]);
$patientId = $reg['id'];
$patient = Patient::find($patientId);

$staffId = Database::insert('staff', [
    'staff_code' => 'STF-0001', 'first_name' => 'Meenakshi', 'last_name' => 'Doctor', 'role' => 'physiotherapist',
    'qualification' => 'BPT, MPT', 'specialization' => 'Neuro Rehab',
    'email' => 'meenakshi@example.com', 'phone' => '8882688868', 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$therapistId = Database::insert('therapists', [
    'staff_id' => $staffId, 'slug' => 'dr-meenakshi', 'bio' => 'Bio',
    'default_session_minutes' => 45, 'is_public' => 1, 'display_order' => 0,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$serviceId = Database::insert('services', [
    'name' => 'Neuro Rehabilitation', 'slug' => 'neuro-rehabilitation', 'category' => 'Neuro Rehabilitation',
    'short_description' => 'Short desc', 'description' => 'Desc', 'duration_minutes' => 45,
    'fee' => 500, 'gst_rate' => 18, 'display_order' => 0, 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);

$apt = Appointment::book([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => date('Y-m-d'), 'scheduled_time' => '10:00:00', 'duration_minutes' => 45,
    'status' => 'confirmed', 'source' => 'admin', 'notes' => null,
]);

$plan = TreatmentPlan::createPlan([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'diagnosis' => 'Chronic knee pain',
    'start_date' => date('Y-m-d'), 'end_date' => null, 'status' => 'active', 'notes' => 'Weekly sessions',
]);

Database::insert('assessments', [
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'assessment_date' => date('Y-m-d'),
    'diagnosis' => 'Chronic knee pain', 'symptoms' => 'Stiffness', 'pain_scale' => 6, 'mobility_score' => 7,
    'recovery_status' => 'improving', 'clinical_notes' => 'Responding well', 'created_at' => Database::now(),
]);

$exerciseId = Database::insert('exercises', [
    'name' => 'Quad Stretch', 'category' => 'Mobility', 'instructions' => 'Stretch slowly',
    'default_repetitions' => '3x10', 'default_frequency' => 'Daily',
    'video_link' => null, 'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
Database::insert('exercise_assignments', [
    'patient_id' => $patientId, 'exercise_id' => $exerciseId, 'repetitions' => '3x10',
    'frequency' => 'Daily', 'start_date' => date('Y-m-d'), 'end_date' => null, 'notes' => null,
    'assigned_by_user_id' => null, 'created_at' => Database::now(),
]);

$inv = Invoice::createWithItems([
    'patient_id' => $patientId, 'invoice_date' => date('Y-m-d'), 'discount_amount' => 0,
], [
    ['service_id' => $serviceId, 'description' => 'Neuro Rehabilitation session', 'quantity' => 1, 'unit_price' => 500, 'gst_rate' => 18],
]);
Database::insert('payments', [
    'invoice_id' => $inv['id'], 'amount' => 200, 'payment_date' => date('Y-m-d'),
    'payment_mode' => 'cash', 'reference_number' => null, 'received_by_user_id' => null, 'notes' => null,
    'created_at' => Database::now(),
]);
Invoice::recalculateStatus($inv['id']);

$packageId = Database::insert('packages', [
    'name' => '10-Session Pack', 'total_sessions' => 10, 'price' => 4000, 'discount_percent' => 0,
    'validity_days' => 90, 'description' => null, 'is_active' => 1,
    'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
PatientPackage::subscribe($patientId, $packageId, $inv['id']);

PatientDocument::create([
    'patient_id' => $patientId, 'document_type' => 'medical_report', 'original_name' => 'mri-report.pdf',
    'stored_name' => 'abc123.pdf', 'file_path' => '/storage/uploads/abc123.pdf', 'mime_type' => 'application/pdf',
    'file_size' => 12345, 'uploaded_by_user_id' => null, 'created_at' => Database::now(),
]);

// --- Common view scaffolding that Controller::view() normally injects ---
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

function render_and_report(string $template, array $data, string $label): void
{
    try {
        $html = View::render($template, $data);
        $len = strlen($html);
        echo "  ok - rendered {$label} ({$len} bytes)\n";
    } catch (\Throwable $e) {
        echo "  FAIL - {$label} threw: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

// 1) index.php
$paginated = Patient::activePaginated(1, 15, '');
render_and_report('admin.patients.index', $common + [
    'page_title' => 'Patients', 'patients' => $paginated['data'], 'pagination' => $paginated, 'search' => '',
], 'patients/index (no search)');

$paginatedSearch = Patient::activePaginated(1, 15, 'Asha');
render_and_report('admin.patients.index', $common + [
    'page_title' => 'Patients', 'patients' => $paginatedSearch['data'], 'pagination' => $paginatedSearch, 'search' => 'Asha',
], 'patients/index (with search)');

// 2) form.php - create mode (patient = null) and edit mode (patient = real row)
render_and_report('admin.patients.form', $common + [
    'page_title' => 'New Patient', 'patient' => null,
], 'patients/form (create)');

render_and_report('admin.patients.form', $common + [
    'page_title' => 'Edit Patient', 'patient' => $patient,
], 'patients/form (edit)');

// form.php with flashed validation errors + old input (the error path)
render_and_report('admin.patients.form', ([
    'csrf_field' => $common['csrf_field'], 'csrf_token' => $common['csrf_token'],
    'current_admin' => $common['current_admin'], 'current_patient' => null,
    'flash_success' => null, 'flash_error' => null,
    'flash_errors' => ['first_name' => ['First name is required.'], 'mobile' => ['Mobile must be a valid phone number.']],
    'old' => ['first_name' => '', 'last_name' => 'Kulkarni', 'gender' => 'female', 'mobile' => 'abc'],
]) + [
    'page_title' => 'New Patient', 'patient' => null,
], 'patients/form (validation errors)');

// 3) show.php - full detail page with every related collection populated
render_and_report('admin.patients.show', $common + [
    'page_title' => Patient::fullName($patient),
    'patient' => $patient,
    'documents' => PatientDocument::forPatient($patientId),
    'treatmentPlans' => TreatmentPlan::forPatient($patientId),
    'assessments' => Assessment::forPatient($patientId),
    'appointments' => Appointment::forPatient($patientId),
    'exerciseAssignments' => ExerciseAssignment::forPatientWithExercise($patientId),
    'invoices' => Invoice::listWithPatient('i.patient_id = :pid', ['pid' => $patientId]),
    'packages' => PatientPackage::forPatientWithPackage($patientId),
    'exerciseLibrary' => \App\Models\Exercise::activeOrdered(),
    'availablePackages' => \App\Models\Package::activeOrdered(),
], 'patients/show (fully populated)');

// show.php with every collection EMPTY (a brand-new patient with no history) -
// this is the path most likely to hit "undefined array key" on a stray $x[0] assumption.
$reg2 = Patient::register([
    'first_name' => 'New', 'last_name' => 'Patient', 'gender' => 'male',
    'date_of_birth' => null, 'mobile' => '9000000000', 'alt_mobile' => null, 'email' => null,
    'address' => null, 'city' => null, 'state' => null, 'country' => null, 'pincode' => null,
    'blood_group' => null, 'allergies' => null, 'existing_conditions' => null, 'medical_history' => null,
    'emergency_contact_name' => null, 'emergency_contact_relationship' => null, 'emergency_contact_mobile' => null,
    'is_active' => 1,
]);
$patient2Id = $reg2['id'];
$patient2 = Patient::find($patient2Id);
render_and_report('admin.patients.show', $common + [
    'page_title' => Patient::fullName($patient2),
    'patient' => $patient2,
    'documents' => PatientDocument::forPatient($patient2Id),
    'treatmentPlans' => TreatmentPlan::forPatient($patient2Id),
    'assessments' => Assessment::forPatient($patient2Id),
    'appointments' => Appointment::forPatient($patient2Id),
    'exerciseAssignments' => ExerciseAssignment::forPatientWithExercise($patient2Id),
    'invoices' => Invoice::listWithPatient('i.patient_id = :pid', ['pid' => $patient2Id]),
    'packages' => PatientPackage::forPatientWithPackage($patient2Id),
    'exerciseLibrary' => \App\Models\Exercise::activeOrdered(),
    'availablePackages' => \App\Models\Package::activeOrdered(),
], 'patients/show (empty-history patient)');

restore_error_handler();

if ($warnings !== []) {
    echo "\n" . count($warnings) . " PHP warning(s)/notice(s) emitted during rendering:\n";
    foreach ($warnings as $w) {
        echo " - {$w}\n";
    }
    exit(1);
}

echo "\nAll patient admin views rendered successfully with zero PHP warnings/notices.\n";
exit(0);
