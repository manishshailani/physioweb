<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Patient;
use App\Models\Therapist;
use App\Models\TreatmentPlan;
use App\Models\TreatmentSession;

final class TreatmentPlanController extends Controller
{
    public function index(array $params): void
    {
        $this->requireAdmin();
        $patientId = (int) $params['patientId'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $plans = TreatmentPlan::forPatient($patientId);
        $sessionsByPlan = [];
        foreach ($plans as $plan) {
            $sessionsByPlan[$plan['id']] = TreatmentSession::forPlan((int) $plan['id']);
        }

        $this->view('admin.treatment-plans.index', [
            'page_title' => 'Treatment Plans - ' . Patient::fullName($patient),
            'patient' => $patient,
            'plans' => $plans,
            'sessionsByPlan' => $sessionsByPlan,
            'therapists' => Therapist::allWithStaff(false),
        ], 'layouts.admin');
    }

    public function store(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $patientId = (int) $params['patientId'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        [$data, $errors] = $this->validatedPlanData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/patients/' . $patientId . '/treatment-plans');
        }

        $data['patient_id'] = $patientId;
        $plan = TreatmentPlan::createPlan($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'treatment_plan', $plan['id'], 'Created treatment plan ' . $plan['plan_number'] . ' for ' . $patient['patient_code']);

        $this->flashSuccess('Treatment plan ' . $plan['plan_number'] . ' created.');
        $this->redirect('/admin/patients/' . $patientId . '/treatment-plans');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $plan = TreatmentPlan::find((int) $params['id']);
        if ($plan === null) {
            Response::notFound('Treatment plan not found.');
        }
        $patient = Patient::find((int) $plan['patient_id']);

        $this->view('admin.treatment-plans.form', [
            'page_title' => 'Edit Treatment Plan ' . $plan['plan_number'],
            'plan' => $plan,
            'patient' => $patient,
            'therapists' => Therapist::allWithStaff(false),
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $plan = TreatmentPlan::find($id);
        if ($plan === null) {
            Response::notFound('Treatment plan not found.');
        }

        [$data, $errors] = $this->validatedPlanData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/treatment-plans/' . $id . '/edit');
        }

        $data['updated_at'] = Database::now();
        TreatmentPlan::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'treatment_plan', $id, 'Updated treatment plan ' . $plan['plan_number']);

        $this->flashSuccess('Treatment plan updated.');
        $this->redirect('/admin/patients/' . $plan['patient_id'] . '/treatment-plans');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $plan = TreatmentPlan::find($id);
        if ($plan === null) {
            Response::notFound('Treatment plan not found.');
        }

        TreatmentPlan::updateById($id, ['status' => 'discontinued', 'updated_at' => Database::now()]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'discontinue', 'treatment_plan', $id, 'Discontinued treatment plan ' . $plan['plan_number']);

        $this->flashSuccess('Treatment plan discontinued.');
        $this->redirect('/admin/patients/' . $plan['patient_id'] . '/treatment-plans');
    }

    public function addSession(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $planId = (int) $params['id'];
        $plan = TreatmentPlan::find($planId);
        if ($plan === null) {
            Response::notFound('Treatment plan not found.');
        }

        $data = $request->only(['session_date', 'therapist_id', 'treatment_given', 'duration_minutes', 'pain_score_before', 'pain_score_after', 'mobility_score', 'progress_notes', 'recommendations']);

        $v = new Validator($data);
        $v->required('session_date')->date('session_date');
        $v->required('treatment_given')->maxLength('treatment_given', 500);
        foreach (['pain_score_before', 'pain_score_after', 'mobility_score'] as $scoreField) {
            if ($data[$scoreField] !== '') {
                $v->integer($scoreField);
            }
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/patients/' . $plan['patient_id'] . '/treatment-plans');
        }

        TreatmentSession::create([
            'treatment_plan_id' => $planId,
            'session_number' => TreatmentSession::nextSessionNumber($planId),
            'session_date' => $data['session_date'],
            'therapist_id' => $data['therapist_id'] !== '' ? (int) $data['therapist_id'] : null,
            'treatment_given' => $data['treatment_given'],
            'duration_minutes' => $data['duration_minutes'] !== '' ? (int) $data['duration_minutes'] : 30,
            'pain_score_before' => $data['pain_score_before'] !== '' ? (int) $data['pain_score_before'] : null,
            'pain_score_after' => $data['pain_score_after'] !== '' ? (int) $data['pain_score_after'] : null,
            'mobility_score' => $data['mobility_score'] !== '' ? (int) $data['mobility_score'] : null,
            'progress_notes' => $data['progress_notes'] !== '' ? $data['progress_notes'] : null,
            'recommendations' => $data['recommendations'] !== '' ? $data['recommendations'] : null,
            'created_at' => Database::now(),
        ]);

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'treatment_session', $planId, 'Logged session for plan ' . $plan['plan_number']);

        $this->flashSuccess('Session recorded.');
        $this->redirect('/admin/patients/' . $plan['patient_id'] . '/treatment-plans');
    }

    public function deleteSession(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $session = TreatmentSession::find($id);
        if ($session !== null) {
            $plan = TreatmentPlan::find((int) $session['treatment_plan_id']);
            TreatmentSession::deleteById($id);
            AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'treatment_session', $id, 'Deleted session #' . $session['session_number']);
            $this->flashSuccess('Session deleted.');
            $this->redirect('/admin/patients/' . ($plan['patient_id'] ?? '') . '/treatment-plans');
        }
        $this->flashError('Session not found.');
        $this->redirectBack('/admin/patients');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedPlanData(Request $request): array
    {
        $data = $request->only(['therapist_id', 'diagnosis', 'start_date', 'end_date', 'status', 'notes']);

        $v = new Validator($data);
        $v->required('diagnosis')->maxLength('diagnosis', 500);
        $v->required('start_date')->date('start_date');
        if ($data['end_date'] !== '') {
            $v->date('end_date');
        }
        $v->in('status', ['active', 'completed', 'discontinued']);

        $sanitized = [
            'therapist_id' => $data['therapist_id'] !== '' ? (int) $data['therapist_id'] : null,
            'diagnosis' => $data['diagnosis'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] !== '' ? $data['end_date'] : null,
            'status' => $data['status'] !== '' ? $data['status'] : 'active',
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ];

        return [$sanitized, $v->errors()];
    }
}
