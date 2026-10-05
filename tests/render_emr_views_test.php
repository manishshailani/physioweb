<?php

declare(strict_types=1);

/**
 * Execution-based verification for the Task 8 (EMR) admin views:
 * treatment-plans/{index,form}, assessments/index, exercises/{index,form},
 * and the updated patients/show.php (now carrying exerciseLibrary + the
 * assign/unassign exercise UI and the new "Manage..." links). Renders each
 * with real data produced through the actual Model layer against an
 * in-memory SQLite DB; any PHP warning/notice is a hard failure.
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\View;
use App\Core\Database;
use App\Models\Patient;
use App\Models\Therapist;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;
use App\Models\Assessment;
use App\Models\Exercise;
use App\Models\ExerciseAssignment;
use App\Models\PatientDocument;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PatientPackage;

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

// --- Seed data through the real Model layer ---
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
$patient = Patient::find($patientId);

$therapists = Therapist::allWithStaff(false);

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

// ===== Treatment Plans =====
render_and_report('admin.treatment-plans.index', $common + [
    'page_title' => 'Treatment Plans', 'patient' => $patient, 'plans' => [], 'sessionsByPlan' => [], 'therapists' => $therapists,
], 'treatment-plans/index (no plans)');

$plan = TreatmentPlan::createPlan([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'diagnosis' => 'Chronic knee pain',
    'start_date' => date('Y-m-d'), 'end_date' => null, 'status' => 'active', 'notes' => 'Weekly sessions',
]);
TreatmentSession::create([
    'treatment_plan_id' => $plan['id'], 'session_number' => TreatmentSession::nextSessionNumber($plan['id']),
    'session_date' => date('Y-m-d'), 'therapist_id' => $therapistId, 'treatment_given' => 'Ultrasound therapy',
    'duration_minutes' => 30, 'pain_score_before' => 7, 'pain_score_after' => 4, 'mobility_score' => 6,
    'progress_notes' => 'Good response', 'recommendations' => 'Continue', 'created_at' => Database::now(),
]);
$plans = TreatmentPlan::forPatient($patientId);
$sessionsByPlan = [];
foreach ($plans as $p) {
    $sessionsByPlan[$p['id']] = TreatmentSession::forPlan((int) $p['id']);
}
render_and_report('admin.treatment-plans.index', $common + [
    'page_title' => 'Treatment Plans', 'patient' => $patient, 'plans' => $plans, 'sessionsByPlan' => $sessionsByPlan, 'therapists' => $therapists,
], 'treatment-plans/index (1 plan, 1 session)');

$planRow = TreatmentPlan::find($plan['id']);
render_and_report('admin.treatment-plans.form', $common + [
    'page_title' => 'Edit Treatment Plan', 'plan' => $planRow, 'patient' => $patient, 'therapists' => $therapists,
], 'treatment-plans/form (edit)');

// with validation errors + old input
render_and_report('admin.treatment-plans.index', [
    'csrf_field' => $common['csrf_field'], 'csrf_token' => $common['csrf_token'],
    'current_admin' => $common['current_admin'], 'current_patient' => null,
    'flash_success' => null, 'flash_error' => null,
    'flash_errors' => ['diagnosis' => ['Diagnosis is required.'], 'start_date' => ['Start date is required.']],
    'old' => ['diagnosis' => '', 'start_date' => '', 'notes' => 'draft notes'],
    'page_title' => 'Treatment Plans', 'patient' => $patient, 'plans' => $plans, 'sessionsByPlan' => $sessionsByPlan, 'therapists' => $therapists,
], 'treatment-plans/index (validation errors on create form)');

// ===== Assessments =====
$recoveryStatuses = ['improving', 'stable', 'worsening'];
render_and_report('admin.assessments.index', $common + [
    'page_title' => 'Assessments', 'patient' => $patient, 'assessments' => [], 'therapists' => $therapists, 'recoveryStatuses' => $recoveryStatuses,
], 'assessments/index (no assessments, chart hidden)');

Assessment::create([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'assessment_date' => date('Y-m-d', strtotime('-7 days')),
    'diagnosis' => 'Chronic knee pain', 'symptoms' => 'Stiffness', 'pain_scale' => 7, 'mobility_score' => 5,
    'recovery_status' => 'stable', 'clinical_notes' => 'Baseline', 'created_at' => Database::now(),
]);
Assessment::create([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'assessment_date' => date('Y-m-d'),
    'diagnosis' => 'Chronic knee pain', 'symptoms' => 'Improving', 'pain_scale' => 4, 'mobility_score' => 7,
    'recovery_status' => 'improving', 'clinical_notes' => 'Responding well', 'created_at' => Database::now(),
]);
$assessments = Assessment::forPatient($patientId);
render_and_report('admin.assessments.index', $common + [
    'page_title' => 'Assessments', 'patient' => $patient, 'assessments' => $assessments, 'therapists' => $therapists, 'recoveryStatuses' => $recoveryStatuses,
], 'assessments/index (2 assessments, chart shown)');

$progressSeries = Assessment::progressSeries($patientId);
assert_true(count($progressSeries) === 2, 'progressSeries returns both seeded assessments');
assert_true($progressSeries[0]['pain_scale'] === 7 && $progressSeries[1]['pain_scale'] === 4, 'progressSeries is ordered chronologically (pain trending down)');

// ===== Exercises =====
render_and_report('admin.exercises.index', $common + [
    'page_title' => 'Exercise Library', 'exercises' => [],
], 'exercises/index (empty library)');

$exerciseId = Exercise::create([
    'name' => 'Quad Stretch', 'category' => 'Mobility', 'instructions' => 'Stretch slowly, hold 30s',
    'default_repetitions' => '3x10', 'default_frequency' => 'Daily', 'video_link' => 'https://example.com/video',
    'is_active' => 1, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$exerciseIdInactive = Exercise::create([
    'name' => 'Old Routine', 'category' => 'Legacy', 'instructions' => 'Deprecated',
    'default_repetitions' => null, 'default_frequency' => null, 'video_link' => null,
    'is_active' => 0, 'created_at' => Database::now(), 'updated_at' => Database::now(),
]);
$exercises = Exercise::all('name');
render_and_report('admin.exercises.index', $common + [
    'page_title' => 'Exercise Library', 'exercises' => $exercises,
], 'exercises/index (active + inactive exercises)');

$exerciseRow = Exercise::find($exerciseId);
render_and_report('admin.exercises.form', $common + [
    'page_title' => 'Edit Exercise', 'exercise' => $exerciseRow,
], 'exercises/form (edit)');

// ===== Patient show.php with exercise assignments (assign/unassign UI + exerciseLibrary) =====
ExerciseAssignment::create([
    'patient_id' => $patientId, 'exercise_id' => $exerciseId, 'repetitions' => '3x10',
    'frequency' => 'Daily', 'start_date' => date('Y-m-d'), 'end_date' => null, 'notes' => null,
    'assigned_by_user_id' => null, 'created_at' => Database::now(),
]);

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
    'exerciseLibrary' => Exercise::activeOrdered(),
    'availablePackages' => \App\Models\Package::activeOrdered(),
], 'patients/show (with exercise library + assignment, with treatment plan + assessments present)');

assert_true(count(Exercise::activeOrdered()) === 1, 'activeOrdered() excludes the deactivated exercise from the assign dropdown');

restore_error_handler();

if ($warnings !== []) {
    echo "\n" . count($warnings) . " PHP warning(s)/notice(s) emitted during rendering:\n";
    foreach ($warnings as $w) {
        echo " - {$w}\n";
    }
    exit(1);
}

exit(physioweb_test_summary());
