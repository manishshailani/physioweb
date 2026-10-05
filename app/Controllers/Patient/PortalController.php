<?php

declare(strict_types=1);

namespace App\Controllers\Patient;

use App\Core\Controller;
use App\Models\Appointment;
use App\Models\Assessment;
use App\Models\ExerciseAssignment;
use App\Models\Invoice;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;

/**
 * Patient portal dashboard + the two simple read-only screens (treatment
 * plan, exercises) that don't need their own controller. Every query here
 * is scoped to $this->currentPatientId() -- the id of the patient actually
 * logged in via the session (see App\Core\Auth::patientUser()) -- never to
 * an id taken from the request, so one logged-in patient can never read
 * another patient's clinical data by guessing/editing a URL.
 */
final class PortalController extends Controller
{
    public function index(): void
    {
        $this->requirePatient();
        $patientId = $this->currentPatientId();

        $upcoming = array_values(array_filter(
            Appointment::forPatient($patientId),
            static fn (array $a) => in_array($a['status'], ['scheduled', 'confirmed'], true) && $a['scheduled_date'] >= date('Y-m-d')
        ));
        usort($upcoming, static fn ($a, $b) => $a['scheduled_date'] <=> $b['scheduled_date'] ?: $a['scheduled_time'] <=> $b['scheduled_time']);

        $this->view('patient.dashboard', [
            'page_title' => 'My Dashboard',
            'upcomingAppointments' => array_slice($upcoming, 0, 5),
            'activeTreatmentPlan' => $this->firstActivePlan($patientId),
            'currentExercises' => ExerciseAssignment::currentForPatient($patientId),
            'outstandingBalance' => Invoice::outstandingForPatient($patientId),
        ], 'layouts.patient');
    }

    public function treatmentPlan(): void
    {
        $this->requirePatient();
        $patientId = $this->currentPatientId();
        $plans = TreatmentPlan::forPatientWithTherapist($patientId);

        // Sessions and the pain/mobility progress series are only loaded
        // for the active plan (if any) -- a discharged/discontinued plan
        // is shown as a summary row only, matching what a patient actually
        // needs to see day-to-day.
        $activePlan = $this->firstOf($plans, 'active');
        $sessions = $activePlan !== null ? TreatmentSession::forPlan((int) $activePlan['id']) : [];
        $assessments = Assessment::forPatient($patientId);

        $this->view('patient.treatment-plan', [
            'page_title' => 'My Treatment Plan',
            'plans' => $plans,
            'activePlan' => $activePlan,
            'sessions' => $sessions,
            'assessments' => $assessments,
        ], 'layouts.patient');
    }

    public function exercises(): void
    {
        $this->requirePatient();
        $patientId = $this->currentPatientId();

        $this->view('patient.exercises', [
            'page_title' => 'My Exercises',
            'currentExercises' => ExerciseAssignment::currentForPatient($patientId),
            'pastExercises' => array_udiff(
                ExerciseAssignment::forPatientWithExercise($patientId),
                ExerciseAssignment::currentForPatient($patientId),
                static fn (array $a, array $b) => $a['id'] <=> $b['id']
            ),
        ], 'layouts.patient');
    }

    private function firstActivePlan(int $patientId): ?array
    {
        return $this->firstOf(TreatmentPlan::forPatient($patientId), 'active');
    }

    private function firstOf(array $plans, string $status): ?array
    {
        foreach ($plans as $plan) {
            if ($plan['status'] === $status) {
                return $plan;
            }
        }
        return null;
    }
}
