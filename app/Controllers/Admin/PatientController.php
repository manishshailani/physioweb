<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\FileUpload;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Patient;
use App\Models\PatientDocument;

final class PatientController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAdmin();
        $page = max(1, (int) $request->query('page', 1));
        $search = (string) $request->query('q', '');
        $result = Patient::activePaginated($page, 15, $search);

        $this->view('admin.patients.index', [
            'page_title' => 'Patients',
            'patients' => $result['data'],
            'pagination' => $result,
            'search' => $search,
        ], 'layouts.admin');
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->view('admin.patients.form', ['page_title' => 'New Patient', 'patient' => null], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/patients/create');
        }

        $result = Patient::register($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'patient', $result['id'], 'Created patient ' . $result['patient_code']);

        $this->flashSuccess('Patient ' . $result['patient_code'] . ' created.');
        $this->redirect('/admin/patients/' . $result['id']);
    }

    public function show(array $params): void
    {
        $this->requireAdmin();
        $patient = Patient::find((int) $params['id']);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $this->view('admin.patients.show', [
            'page_title' => Patient::fullName($patient),
            'patient' => $patient,
            'documents' => PatientDocument::forPatient((int) $patient['id']),
            'treatmentPlans' => \App\Models\TreatmentPlan::forPatient((int) $patient['id']),
            'assessments' => \App\Models\Assessment::forPatient((int) $patient['id']),
            'appointments' => \App\Models\Appointment::forPatient((int) $patient['id']),
            'exerciseAssignments' => \App\Models\ExerciseAssignment::forPatientWithExercise((int) $patient['id']),
            'invoices' => \App\Models\Invoice::listWithPatient('i.patient_id = :pid', ['pid' => (int) $patient['id']]),
            'packages' => \App\Models\PatientPackage::forPatientWithPackage((int) $patient['id']),
            'exerciseLibrary' => \App\Models\Exercise::activeOrdered(),
            'availablePackages' => \App\Models\Package::activeOrdered(),
        ], 'layouts.admin');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $patient = Patient::find((int) $params['id']);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }
        $this->view('admin.patients.form', ['page_title' => 'Edit Patient', 'patient' => $patient], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $patient = Patient::find($id);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        [$data, $errors] = $this->validatedData($request, $id);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/patients/' . $id . '/edit');
        }

        $data['updated_at'] = Database::now();
        Patient::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'patient', $id, 'Updated patient ' . $patient['patient_code']);

        $this->flashSuccess('Patient updated.');
        $this->redirect('/admin/patients/' . $id);
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $patient = Patient::find($id);
        if ($patient !== null) {
            Patient::deactivate($id);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'patient', $id, 'Deactivated patient ' . $patient['patient_code']);
        }
        $this->flashSuccess('Patient deactivated.');
        $this->redirect('/admin/patients');
    }

    public function uploadDocument(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $patientId = (int) $params['id'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $file = $request->file('document');
        $type = (string) $request->input('document_type', 'other');

        if ($file === null) {
            $this->flashError('Please choose a file to upload.');
            $this->redirect('/admin/patients/' . $patientId);
        }

        $config = require dirname(__DIR__, 3) . '/config/config.php';
        $destDir = dirname(__DIR__, 3) . '/storage/uploads/patients/documents';
        $uploaded = FileUpload::store($file, $destDir, 'document', (int) $config['uploads']['max_size_mb']);

        if (!$uploaded['ok']) {
            $this->flashError($uploaded['error']);
            $this->redirect('/admin/patients/' . $patientId);
        }

        $adminId = $this->currentAdminId();
        PatientDocument::create([
            'patient_id' => $patientId,
            'document_type' => $type,
            'original_name' => $uploaded['original_name'],
            'stored_name' => $uploaded['stored_name'],
            'file_path' => $uploaded['path'],
            'mime_type' => $uploaded['mime'],
            'file_size' => $uploaded['size'],
            'uploaded_by_user_id' => $adminId,
            'created_at' => Database::now(),
        ]);

        AuditLogger::log($adminId, 'admin', 'upload', 'patient_document', $patientId, 'Uploaded document for patient ' . $patient['patient_code']);

        $this->flashSuccess('Document uploaded.');
        $this->redirect('/admin/patients/' . $patientId);
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request, ?int $excludeId = null): array
    {
        $data = $request->only([
            'first_name', 'last_name', 'gender', 'date_of_birth', 'mobile', 'alt_mobile', 'email',
            'address', 'city', 'state', 'country', 'pincode', 'blood_group',
            'allergies', 'existing_conditions', 'medical_history',
            'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_mobile',
        ]);

        $v = new Validator($data);
        $v->required('first_name')->maxLength('first_name', 100);
        $v->required('last_name')->maxLength('last_name', 100);
        $v->required('gender')->in('gender', ['male', 'female', 'other']);
        $v->required('mobile')->mobile('mobile');
        if ($data['email'] !== '') {
            $v->email('email');
            $existing = \App\Models\Patient::whereFirst('email', $data['email']);
            if ($existing !== null && (int) $existing['id'] !== $excludeId) {
                $v->custom('email', static fn () => false, 'This email is already used by another patient.');
            }
        } else {
            $data['email'] = null;
        }
        if ($data['date_of_birth'] !== '') {
            $v->date('date_of_birth');
        } else {
            $data['date_of_birth'] = null;
        }

        foreach (['alt_mobile', 'address', 'city', 'state', 'pincode', 'blood_group', 'allergies', 'existing_conditions', 'medical_history', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_mobile'] as $optional) {
            if ($data[$optional] === '') {
                $data[$optional] = null;
            }
        }

        $data['is_active'] = 1;

        return [$data, $v->errors()];
    }
}
