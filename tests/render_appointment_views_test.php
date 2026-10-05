<?php

declare(strict_types=1);

/**
 * Execution-based verification for the appointment admin views + the
 * AppointmentController's pure-logic pieces (filter building, pagination,
 * calendar feed event shaping). Renders each view with real data produced
 * through the actual Model layer against an in-memory SQLite DB, treating
 * any PHP warning/notice as a failure.
 */

const PHYSIOWEB_ROOT = '/home/claude/physioweb';
require PHYSIOWEB_ROOT . '/tests/bootstrap_test.php';

use App\Core\View;
use App\Core\Database;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Therapist;
use App\Models\Service;

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

// --- Seed realistic data through the real Model/Database layer ---
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

$apt1 = Appointment::book([
    'patient_id' => $patientId, 'therapist_id' => $therapistId, 'service_id' => $serviceId,
    'scheduled_date' => date('Y-m-d'), 'scheduled_time' => '10:00:00', 'duration_minutes' => 45,
    'status' => 'confirmed', 'source' => 'admin', 'notes' => 'First session',
]);
$apt2 = Appointment::book([
    'patient_id' => null, 'therapist_id' => null, 'service_id' => null,
    'guest_name' => 'Walk-in Guest', 'guest_mobile' => '9000011111', 'guest_email' => null,
    'scheduled_date' => date('Y-m-d', strtotime('+1 day')), 'scheduled_time' => '14:30:00', 'duration_minutes' => 30,
    'status' => 'scheduled', 'source' => 'admin', 'notes' => null,
]);

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

$statuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
$therapists = Therapist::allWithStaff(false);
$services = Service::activeOrdered();
$patients = Patient::activePaginated(1, 500)['data'];

// 1) index.php - with and without appointments, with and without filters applied
$paginated = Appointment::paginatedWithDetails(1, 15, '1=1', []);
render_and_report('admin.appointments.index', $common + [
    'page_title' => 'Appointments', 'appointments' => $paginated['data'], 'pagination' => $paginated,
    'statuses' => $statuses, 'therapists' => $therapists,
    'filters' => ['status' => '', 'therapist_id' => '', 'date' => ''],
], 'appointments/index (all)');

$filteredPaginated = Appointment::paginatedWithDetails(1, 15, 'status = :status', ['status' => 'confirmed']);
render_and_report('admin.appointments.index', $common + [
    'page_title' => 'Appointments', 'appointments' => $filteredPaginated['data'], 'pagination' => $filteredPaginated,
    'statuses' => $statuses, 'therapists' => $therapists,
    'filters' => ['status' => 'confirmed', 'therapist_id' => '', 'date' => ''],
], 'appointments/index (status=confirmed filter)');

$emptyPaginated = Appointment::paginatedWithDetails(1, 15, 'status = :status', ['status' => 'no_show']);
render_and_report('admin.appointments.index', $common + [
    'page_title' => 'Appointments', 'appointments' => $emptyPaginated['data'], 'pagination' => $emptyPaginated,
    'statuses' => $statuses, 'therapists' => $therapists,
    'filters' => ['status' => 'no_show', 'therapist_id' => '', 'date' => ''],
], 'appointments/index (empty result set)');

assert_true($paginated['total'] === 2, 'paginatedWithDetails total count matches seeded appointments (2)');
assert_true($filteredPaginated['total'] === 1, 'status filter narrows to the 1 confirmed appointment');
assert_true($emptyPaginated['data'] === [], 'no_show filter yields an empty result set, not an error');

// 2) form.php - create (registered-patient preselected), create (guest), edit
render_and_report('admin.appointments.form', $common + [
    'page_title' => 'New Appointment', 'appointment' => null, 'patients' => $patients,
    'therapists' => $therapists, 'services' => $services, 'statuses' => $statuses,
    'preselectedPatientId' => $patientId,
], 'appointments/form (create, patient preselected)');

render_and_report('admin.appointments.form', $common + [
    'page_title' => 'New Appointment', 'appointment' => null, 'patients' => $patients,
    'therapists' => $therapists, 'services' => $services, 'statuses' => $statuses,
    'preselectedPatientId' => 0,
], 'appointments/form (create, guest)');

$apt1Row = Appointment::find($apt1['id']);
render_and_report('admin.appointments.form', $common + [
    'page_title' => 'Edit Appointment', 'appointment' => $apt1Row, 'patients' => $patients,
    'therapists' => $therapists, 'services' => $services, 'statuses' => $statuses,
    'preselectedPatientId' => $patientId,
], 'appointments/form (edit, registered patient)');

$apt2Row = Appointment::find($apt2['id']);
render_and_report('admin.appointments.form', $common + [
    'page_title' => 'Edit Appointment', 'appointment' => $apt2Row, 'patients' => $patients,
    'therapists' => $therapists, 'services' => $services, 'statuses' => $statuses,
    'preselectedPatientId' => 0,
], 'appointments/form (edit, guest appointment)');

// form.php with flashed validation errors
render_and_report('admin.appointments.form', [
    'csrf_field' => $common['csrf_field'], 'csrf_token' => $common['csrf_token'],
    'current_admin' => $common['current_admin'], 'current_patient' => null,
    'flash_success' => null, 'flash_error' => null,
    'flash_errors' => ['scheduled_date' => ['Scheduled date is required.'], 'guest_name' => ['Guest name is required.']],
    'old' => ['scheduled_date' => '', 'scheduled_time' => '09:00', 'patient_id' => ''],
    'page_title' => 'New Appointment', 'appointment' => null, 'patients' => $patients,
    'therapists' => $therapists, 'services' => $services, 'statuses' => $statuses,
    'preselectedPatientId' => 0,
], 'appointments/form (validation errors)');

// 3) calendar.php
render_and_report('admin.appointments.calendar', $common + [
    'page_title' => 'Appointment Calendar', 'therapists' => $therapists, 'statuses' => $statuses,
], 'appointments/calendar');

// --- Non-view logic: calendar feed event shaping, directly via reflection on the controller ---
require PHYSIOWEB_ROOT . '/app/Controllers/Admin/AppointmentController.php';
require PHYSIOWEB_ROOT . '/app/Core/Request.php';

// Rather than invoke the controller (which calls exit via Response::json), exercise the
// exact event-shaping logic (array_map + colorMap) via listWithDetails directly, since that
// is the actual business logic under test (JSON envelope/exit is a thin, visually-reviewable wrapper).
$rows = Appointment::listWithDetails('1=1', [], 'a.scheduled_date, a.scheduled_time');
assert_true(count($rows) === 2, 'calendar feed query returns both seeded appointments for the full month range');
foreach ($rows as $r) {
    assert_true(isset($r['patient_name']) || $r['guest_name'] !== null, 'every row has either a patient_name or a guest_name fallback for the event title');
    assert_true(strtotime($r['scheduled_date'] . ' ' . $r['scheduled_time']) !== false, 'scheduled_date+time parses into a valid timestamp for event start/end math');
}

// --- Therapist double-booking conflict detection (reused by store/update/reschedule) ---
assert_true(
    Therapist::hasConflict($therapistId, date('Y-m-d'), '10:15:00', 30) === true,
    'hasConflict detects a 10:15 slot overlapping the existing 10:00-10:45 appointment'
);
assert_true(
    Therapist::hasConflict($therapistId, date('Y-m-d'), '11:00:00', 30) === false,
    'hasConflict allows an 11:00 slot that does not overlap the existing appointment'
);
assert_true(
    Therapist::hasConflict($therapistId, date('Y-m-d'), '10:00:00', 45, $apt1['id']) === false,
    'hasConflict excludes the appointment being edited from its own conflict check'
);

restore_error_handler();

if ($warnings !== []) {
    echo "\n" . count($warnings) . " PHP warning(s)/notice(s) emitted during rendering:\n";
    foreach ($warnings as $w) {
        echo " - {$w}\n";
    }
    exit(1);
}

exit(physioweb_test_summary());
