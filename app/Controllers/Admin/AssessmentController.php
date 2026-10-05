<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Assessment;
use App\Models\Patient;
use App\Models\Therapist;

final class AssessmentController extends Controller
{
    private const RECOVERY_STATUSES = ['improving', 'stable', 'worsening'];

    public function index(array $params): void
    {
        $this->requireAdmin();
        $patientId = (int) $params['patientId'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $this->view('admin.assessments.index', [
            'page_title' => 'Assessments - ' . Patient::fullName($patient),
            'patient' => $patient,
            'assessments' => Assessment::forPatient($patientId),
            'therapists' => Therapist::allWithStaff(false),
            'recoveryStatuses' => self::RECOVERY_STATUSES,
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

        $data = $request->only(['therapist_id', 'assessment_date', 'diagnosis', 'symptoms', 'pain_scale', 'mobility_score', 'recovery_status', 'clinical_notes']);

        $v = new Validator($data);
        $v->required('assessment_date')->date('assessment_date');
        if ($data['pain_scale'] !== '') {
            $v->integer('pain_scale');
            if (is_numeric($data['pain_scale']) && ((int) $data['pain_scale'] < 0 || (int) $data['pain_scale'] > 10)) {
                $this->flashErrors(['pain_scale' => ['Pain scale must be between 0 and 10.']], $request->all());
                $this->redirect('/admin/patients/' . $patientId . '/assessments');
            }
        }
        if ($data['recovery_status'] !== '') {
            $v->in('recovery_status', self::RECOVERY_STATUSES);
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/patients/' . $patientId . '/assessments');
        }

        $id = Assessment::create([
            'patient_id' => $patientId,
            'therapist_id' => $data['therapist_id'] !== '' ? (int) $data['therapist_id'] : null,
            'assessment_date' => $data['assessment_date'],
            'diagnosis' => $data['diagnosis'] !== '' ? $data['diagnosis'] : null,
            'symptoms' => $data['symptoms'] !== '' ? $data['symptoms'] : null,
            'pain_scale' => $data['pain_scale'] !== '' ? (int) $data['pain_scale'] : null,
            'mobility_score' => $data['mobility_score'] !== '' ? (int) $data['mobility_score'] : null,
            'recovery_status' => $data['recovery_status'] !== '' ? $data['recovery_status'] : null,
            'clinical_notes' => $data['clinical_notes'] !== '' ? $data['clinical_notes'] : null,
            'created_at' => \App\Core\Database::now(),
        ]);

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'assessment', $id, 'Recorded assessment for ' . $patient['patient_code']);

        $this->flashSuccess('Assessment recorded.');
        $this->redirect('/admin/patients/' . $patientId . '/assessments');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $assessment = Assessment::find($id);
        if ($assessment === null) {
            Response::notFound('Assessment not found.');
        }

        Assessment::deleteById($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'assessment', $id, 'Deleted assessment dated ' . $assessment['assessment_date']);

        $this->flashSuccess('Assessment deleted.');
        $this->redirect('/admin/patients/' . $assessment['patient_id'] . '/assessments');
    }

    /** JSON feed for the pain/mobility progress chart on the assessments page. */
    public function progressData(array $params): void
    {
        $this->requireAdmin();
        $patientId = (int) $params['patientId'];
        $series = Assessment::progressSeries($patientId);

        $this->json([
            'labels' => array_column($series, 'assessment_date'),
            'pain' => array_map(static fn ($v) => $v !== null ? (int) $v : null, array_column($series, 'pain_scale')),
            'mobility' => array_map(static fn ($v) => $v !== null ? (int) $v : null, array_column($series, 'mobility_score')),
        ]);
    }
}
