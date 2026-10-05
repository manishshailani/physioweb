<?php

declare(strict_types=1);

namespace App\Controllers\Patient;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Therapist;

/**
 * Self-service appointment booking/cancellation for a logged-in patient.
 * Mirrors App\Controllers\Public\BookingController's validation and
 * double-booking-prevention logic (same Therapist::hasConflict() check,
 * same date-not-in-the-past rule), but books against the patient's own
 * patient_id from the session instead of free-text guest name/mobile, and
 * every read/write is scoped to that patient_id so one patient can never
 * view or cancel another patient's appointment by editing the URL.
 */
final class PatientAppointmentController extends Controller
{
    public function index(): void
    {
        $this->requirePatient();
        $appointments = Appointment::forPatient($this->currentPatientId());
        usort($appointments, static fn ($a, $b) => [$b['scheduled_date'], $b['scheduled_time']] <=> [$a['scheduled_date'], $a['scheduled_time']]);

        $this->view('patient.appointments.index', [
            'page_title' => 'My Appointments',
            'appointments' => $appointments,
        ], 'layouts.patient');
    }

    public function create(): void
    {
        $this->requirePatient();
        $this->view('patient.appointments.book', [
            'page_title' => 'Book an Appointment',
            'services' => Service::activeOrdered(),
            'therapists' => Therapist::allWithStaff(true),
        ], 'layouts.patient');
    }

    public function store(Request $request): void
    {
        $this->requirePatient();
        $this->verifyCsrf($request);
        $patientId = $this->currentPatientId();

        $data = $request->only(['preferred_date', 'preferred_time', 'service_id', 'therapist_id', 'notes']);

        $v = new Validator($data);
        $v->required('preferred_date')->date('preferred_date');
        $v->required('preferred_time');

        $requestedDate = (string) $data['preferred_date'];
        if ($v->passes() && strtotime($requestedDate) < strtotime(date('Y-m-d'))) {
            $this->flashErrors(['preferred_date' => ['Please choose a date from today onward.']], $data);
            $this->redirect('/patient/appointments/book');
        }

        if ($v->fails()) {
            $this->flashErrors($v->errors(), $data);
            $this->redirect('/patient/appointments/book');
        }

        $therapistId = $data['therapist_id'] !== '' ? (int) $data['therapist_id'] : null;
        $serviceId = $data['service_id'] !== '' ? (int) $data['service_id'] : null;

        $durationMinutes = 45;
        if ($serviceId !== null) {
            $service = Service::find($serviceId);
            if ($service !== null) {
                $durationMinutes = (int) $service['duration_minutes'];
            }
        }

        if ($therapistId !== null && Therapist::hasConflict($therapistId, $requestedDate, (string) $data['preferred_time'], $durationMinutes)) {
            $this->flashErrors(['preferred_time' => ['That therapist is already booked at the selected time. Please choose another slot.']], $data);
            $this->redirect('/patient/appointments/book');
        }

        $booking = Appointment::book([
            'patient_id' => $patientId,
            'therapist_id' => $therapistId,
            'service_id' => $serviceId,
            'scheduled_date' => $requestedDate,
            'scheduled_time' => $data['preferred_time'],
            'duration_minutes' => $durationMinutes,
            'status' => 'scheduled',
            'source' => 'patient_portal',
            'notes' => $data['notes'] ?: null,
        ]);

        AuditLogger::log($patientId, 'patient', 'create', 'appointment', $booking['id'], 'Patient self-booked appointment ' . $booking['appointment_number']);

        $this->flashSuccess('Your appointment request (' . $booking['appointment_number'] . ') has been received. Our team will confirm shortly.');
        $this->redirect('/patient/appointments');
    }

    public function cancel(array $params, Request $request): void
    {
        $this->requirePatient();
        $this->verifyCsrf($request);
        $patientId = $this->currentPatientId();
        $id = (int) $params['id'];

        $appointment = Appointment::find($id);
        if ($appointment === null || (int) $appointment['patient_id'] !== $patientId) {
            // Deliberately the same "not found" response whether the
            // appointment doesn't exist or belongs to someone else --
            // never reveal which case it is to an unauthorized caller.
            Response::notFound('Appointment not found.');
        }

        if (!in_array($appointment['status'], ['scheduled', 'confirmed'], true)) {
            $this->flashError('This appointment can no longer be cancelled.');
            $this->redirect('/patient/appointments');
        }

        Appointment::updateById($id, ['status' => 'cancelled', 'updated_at' => Database::now()]);
        AuditLogger::log($patientId, 'patient', 'cancel', 'appointment', $id, 'Patient cancelled appointment ' . $appointment['appointment_number']);

        $this->flashSuccess('Appointment cancelled.');
        $this->redirect('/patient/appointments');
    }
}
