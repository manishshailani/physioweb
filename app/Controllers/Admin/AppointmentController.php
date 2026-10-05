<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Therapist;
use App\Services\NotificationService;

final class AppointmentController extends Controller
{
    private const STATUSES = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];

    public function index(Request $request): void
    {
        $this->requireAdmin();

        $status = (string) $request->query('status', '');
        $therapistId = (string) $request->query('therapist_id', '');
        $date = (string) $request->query('date', '');
        $page = max(1, (int) $request->query('page', 1));

        [$where, $params] = $this->buildFilters($status, $therapistId, $date);
        $result = Appointment::paginatedWithDetails($page, 15, $where, $params);

        $this->view('admin.appointments.index', [
            'page_title' => 'Appointments',
            'appointments' => $result['data'],
            'pagination' => $result,
            'statuses' => self::STATUSES,
            'therapists' => Therapist::allWithStaff(false),
            'filters' => ['status' => $status, 'therapist_id' => $therapistId, 'date' => $date],
        ], 'layouts.admin');
    }

    public function calendar(): void
    {
        $this->requireAdmin();
        $this->view('admin.appointments.calendar', [
            'page_title' => 'Appointment Calendar',
            'therapists' => Therapist::allWithStaff(false),
            'statuses' => self::STATUSES,
        ], 'layouts.admin');
    }

    /**
     * JSON feed consumed by FullCalendar (events source). Accepts FullCalendar's
     * `start`/`end` range query params (ISO date strings) and an optional
     * `therapist_id` filter.
     */
    public function calendarFeed(Request $request): void
    {
        $this->requireAdmin();

        $start = (string) $request->query('start', date('Y-m-01'));
        $end = (string) $request->query('end', date('Y-m-t'));
        // FullCalendar sends full ISO datetimes (e.g. 2026-10-01T00:00:00+05:30); only the date part is relevant here.
        $start = substr($start, 0, 10);
        $end = substr($end, 0, 10);

        $where = 'scheduled_date BETWEEN :start AND :end';
        $params = ['start' => $start, 'end' => $end];

        $therapistId = (string) $request->query('therapist_id', '');
        if ($therapistId !== '') {
            $where .= ' AND therapist_id = :therapist_id';
            $params['therapist_id'] = (int) $therapistId;
        }

        $rows = Appointment::listWithDetails($where, $params, 'a.scheduled_date, a.scheduled_time');

        $colorMap = [
            'scheduled' => '#6c757d', 'confirmed' => '#0d6efd', 'completed' => '#198754',
            'cancelled' => '#dc3545', 'no_show' => '#fd7e14',
        ];

        $events = array_map(static function (array $a) use ($colorMap): array {
            $start = $a['scheduled_date'] . 'T' . $a['scheduled_time'];
            $end = date('Y-m-d\TH:i:s', strtotime($start) + ((int) $a['duration_minutes']) * 60);
            return [
                'id' => (int) $a['id'],
                'title' => ($a['patient_name'] ?: 'Guest') . ' - ' . ($a['service_name'] ?? 'Appointment'),
                'start' => $start,
                'end' => $end,
                'color' => $colorMap[$a['status']] ?? '#6c757d',
                'extendedProps' => [
                    'status' => $a['status'],
                    'therapist' => $a['therapist_name'],
                    'appointment_number' => $a['appointment_number'],
                ],
            ];
        }, $rows);

        $this->json($events);
    }

    public function create(Request $request): void
    {
        $this->requireAdmin();
        $preselectedPatientId = (int) $request->query('patient_id', 0);
        $this->view('admin.appointments.form', [
            'page_title' => 'New Appointment',
            'appointment' => null,
            'patients' => Patient::activePaginated(1, 500)['data'],
            'therapists' => Therapist::allWithStaff(false),
            'services' => Service::activeOrdered(),
            'statuses' => self::STATUSES,
            'preselectedPatientId' => $preselectedPatientId,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/appointments/create');
        }

        if ($data['therapist_id'] !== null && Therapist::hasConflict($data['therapist_id'], $data['scheduled_date'], $data['scheduled_time'], $data['duration_minutes'])) {
            $this->flashErrors(['scheduled_time' => ['This therapist already has an appointment that overlaps this time slot.']], $request->all());
            $this->redirect('/admin/appointments/create');
        }

        $data['source'] = 'admin';
        $booking = Appointment::book($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'appointment', $booking['id'], 'Created appointment ' . $booking['appointment_number']);

        $this->notifyIfEmailAvailable($data['patient_id'] ?? null, $booking, $data['scheduled_date'], $data['scheduled_time']);

        $this->flashSuccess('Appointment ' . $booking['appointment_number'] . ' created.');
        $this->redirect('/admin/appointments');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $appointment = Appointment::find((int) $params['id']);
        if ($appointment === null) {
            Response::notFound('Appointment not found.');
        }

        $this->view('admin.appointments.form', [
            'page_title' => 'Edit Appointment',
            'appointment' => $appointment,
            'patients' => Patient::activePaginated(1, 500)['data'],
            'therapists' => Therapist::allWithStaff(false),
            'services' => Service::activeOrdered(),
            'statuses' => self::STATUSES,
            'preselectedPatientId' => (int) ($appointment['patient_id'] ?? 0),
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $appointment = Appointment::find($id);
        if ($appointment === null) {
            Response::notFound('Appointment not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/appointments/' . $id . '/edit');
        }

        if ($data['therapist_id'] !== null && Therapist::hasConflict($data['therapist_id'], $data['scheduled_date'], $data['scheduled_time'], $data['duration_minutes'], $id)) {
            $this->flashErrors(['scheduled_time' => ['This therapist already has an appointment that overlaps this time slot.']], $request->all());
            $this->redirect('/admin/appointments/' . $id . '/edit');
        }

        $data['updated_at'] = Database::now();
        Appointment::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'appointment', $id, 'Updated appointment ' . $appointment['appointment_number']);

        $this->flashSuccess('Appointment updated.');
        $this->redirect('/admin/appointments');
    }

    /** AJAX endpoint for the calendar's drag-and-drop / resize rescheduling. */
    public function reschedule(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $appointment = Appointment::find($id);
        if ($appointment === null) {
            $this->json(['ok' => false, 'error' => 'Appointment not found.'], 404);
        }

        $newDate = (string) $request->input('scheduled_date', '');
        $newTime = (string) $request->input('scheduled_time', '');

        $v = new Validator(['scheduled_date' => $newDate, 'scheduled_time' => $newTime]);
        $v->required('scheduled_date')->date('scheduled_date');
        $v->required('scheduled_time');
        if ($v->fails()) {
            $this->json(['ok' => false, 'error' => 'Invalid date or time.'], 422);
        }

        $therapistId = $appointment['therapist_id'] !== null ? (int) $appointment['therapist_id'] : null;
        if ($therapistId !== null && Therapist::hasConflict($therapistId, $newDate, $newTime, (int) $appointment['duration_minutes'], $id)) {
            $this->json(['ok' => false, 'error' => 'The therapist already has an overlapping appointment at that time.'], 409);
        }

        Appointment::updateById($id, [
            'scheduled_date' => $newDate,
            'scheduled_time' => $newTime,
            'updated_at' => Database::now(),
        ]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'reschedule', 'appointment', $id, 'Rescheduled appointment ' . $appointment['appointment_number'] . ' to ' . $newDate . ' ' . $newTime);

        $this->json(['ok' => true]);
    }

    /** AJAX endpoint used by both the index table and the calendar event click popover. */
    public function updateStatus(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $appointment = Appointment::find($id);
        if ($appointment === null) {
            $this->json(['ok' => false, 'error' => 'Appointment not found.'], 404);
        }

        $status = (string) $request->input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            $this->json(['ok' => false, 'error' => 'Invalid status.'], 422);
        }

        Appointment::updateById($id, ['status' => $status, 'updated_at' => Database::now()]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'status_change', 'appointment', $id, 'Marked appointment ' . $appointment['appointment_number'] . ' as ' . $status);

        $this->json(['ok' => true, 'status' => $status]);
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $appointment = Appointment::find($id);
        if ($appointment !== null) {
            Appointment::updateById($id, ['status' => 'cancelled', 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'cancel', 'appointment', $id, 'Cancelled appointment ' . $appointment['appointment_number']);
        }
        $this->flashSuccess('Appointment cancelled.');
        $this->redirect('/admin/appointments');
    }

    /** @return array{0: array<string, mixed>, 1: array} */
    private function buildFilters(string $status, string $therapistId, string $date): array
    {
        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($therapistId !== '') {
            $where[] = 'therapist_id = :therapist_id';
            $params['therapist_id'] = (int) $therapistId;
        }
        if ($date !== '') {
            $where[] = 'scheduled_date = :date';
            $params['date'] = $date;
        }

        return [$where === [] ? '1=1' : implode(' AND ', $where), $params];
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $patientId = (string) $request->input('patient_id', '');
        $therapistId = (string) $request->input('therapist_id', '');
        $serviceId = (string) $request->input('service_id', '');

        $data = [
            'patient_id' => $patientId !== '' ? (int) $patientId : null,
            'therapist_id' => $therapistId !== '' ? (int) $therapistId : null,
            'service_id' => $serviceId !== '' ? (int) $serviceId : null,
            'scheduled_date' => (string) $request->input('scheduled_date', ''),
            'scheduled_time' => (string) $request->input('scheduled_time', ''),
            'duration_minutes' => (int) $request->input('duration_minutes', 45),
            'status' => (string) $request->input('status', 'scheduled'),
            'notes' => (string) $request->input('notes', ''),
            'guest_name' => (string) $request->input('guest_name', ''),
            'guest_mobile' => (string) $request->input('guest_mobile', ''),
            'guest_email' => (string) $request->input('guest_email', ''),
        ];

        $v = new Validator($data);
        $v->required('scheduled_date')->date('scheduled_date');
        $v->required('scheduled_time');
        $v->in('status', self::STATUSES);
        $v->min('duration_minutes', 5);

        if ($data['patient_id'] === null) {
            $v->required('guest_name')->maxLength('guest_name', 150);
            $v->required('guest_mobile')->mobile('guest_mobile');
        }

        $data['notes'] = $data['notes'] !== '' ? $data['notes'] : null;
        $data['guest_name'] = $data['patient_id'] === null && $data['guest_name'] !== '' ? $data['guest_name'] : null;
        $data['guest_mobile'] = $data['patient_id'] === null && $data['guest_mobile'] !== '' ? $data['guest_mobile'] : null;
        $data['guest_email'] = $data['patient_id'] === null && $data['guest_email'] !== '' ? $data['guest_email'] : null;

        return [$data, $v->errors()];
    }

    private function notifyIfEmailAvailable(?int $patientId, array $booking, string $date, string $time): void
    {
        if ($patientId === null) {
            return;
        }
        $patient = Patient::find($patientId);
        if ($patient === null || empty($patient['email'])) {
            return;
        }

        $config = require dirname(__DIR__, 3) . '/config/config.php';
        $notifier = NotificationService::fromConfig($config);
        $notifier->sendAppointmentConfirmationEmail(
            array_merge($booking, ['scheduled_date' => $date, 'scheduled_time' => $time]),
            $patient['email'],
            Patient::fullName($patient)
        );
    }
}
