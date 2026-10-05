<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Therapist;
use App\Services\NotificationService;

final class BookingController extends Controller
{
    public function index(): void
    {
        $this->view('public.booking', [
            'services' => Service::activeOrdered(),
            'therapists' => Therapist::allWithStaff(true),
            'settings' => \App\Models\Setting::allAsMap(),
            'page_title' => 'Book an Appointment',
        ], 'layouts.public');
    }

    public function store(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = $request->only(['name', 'mobile', 'email', 'preferred_date', 'preferred_time', 'service_id', 'therapist_id', 'notes']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('mobile')->mobile('mobile');
        $v->required('preferred_date')->date('preferred_date');
        $v->required('preferred_time');
        if ($data['email'] !== '') {
            $v->email('email');
        }

        $requestedDate = (string) $data['preferred_date'];
        if ($v->passes() && strtotime($requestedDate) < strtotime(date('Y-m-d'))) {
            $this->flashErrors(['preferred_date' => ['Please choose a date from today onward.']], $data);
            $this->redirect('/book-appointment');
        }

        if ($v->fails()) {
            $this->flashErrors($v->errors(), $data);
            $this->redirect('/book-appointment');
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
            $this->redirect('/book-appointment');
        }

        $booking = Appointment::book([
            'guest_name' => $data['name'],
            'guest_mobile' => $data['mobile'],
            'guest_email' => $data['email'] ?: null,
            'therapist_id' => $therapistId,
            'service_id' => $serviceId,
            'scheduled_date' => $requestedDate,
            'scheduled_time' => $data['preferred_time'],
            'duration_minutes' => $durationMinutes,
            'status' => 'scheduled',
            'source' => 'public_website',
            'notes' => $data['notes'] ?: null,
        ]);

        AuditLogger::log(null, 'system', 'create', 'appointment', $booking['id'], 'Public booking by ' . $data['name']);

        if ($data['email'] !== '') {
            $config = require dirname(__DIR__, 3) . '/config/config.php';
            $notifier = NotificationService::fromConfig($config);
            $notifier->sendAppointmentConfirmationEmail(
                array_merge($booking, ['scheduled_date' => $requestedDate, 'scheduled_time' => $data['preferred_time']]),
                $data['email'],
                $data['name']
            );
        }

        $this->flashSuccess('Your appointment request (' . $booking['appointment_number'] . ') has been received. Our team will confirm shortly.');
        $this->redirect('/book-appointment');
    }
}
