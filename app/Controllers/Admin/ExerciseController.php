<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Exercise;
use App\Models\ExerciseAssignment;
use App\Models\Patient;

final class ExerciseController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.exercises.index', [
            'page_title' => 'Exercise Library',
            'exercises' => Exercise::all('name'),
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/exercises');
        }

        $data['is_active'] = 1;
        $data['created_at'] = Database::now();
        $data['updated_at'] = Database::now();
        $id = Exercise::create($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'exercise', $id, 'Added exercise ' . $data['name'] . ' to the library');

        $this->flashSuccess('Exercise added to the library.');
        $this->redirect('/admin/exercises');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $exercise = Exercise::find((int) $params['id']);
        if ($exercise === null) {
            Response::notFound('Exercise not found.');
        }
        $this->view('admin.exercises.form', [
            'page_title' => 'Edit Exercise',
            'exercise' => $exercise,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $exercise = Exercise::find($id);
        if ($exercise === null) {
            Response::notFound('Exercise not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/exercises/' . $id . '/edit');
        }

        $data['updated_at'] = Database::now();
        Exercise::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'exercise', $id, 'Updated exercise ' . $data['name']);

        $this->flashSuccess('Exercise updated.');
        $this->redirect('/admin/exercises');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $exercise = Exercise::find($id);
        if ($exercise !== null) {
            // Soft-deactivate rather than hard-delete: existing exercise_assignments
            // reference this row (ON DELETE CASCADE would silently wipe a patient's
            // assignment history), and clinicians may want to re-enable it later.
            Exercise::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'exercise', $id, 'Deactivated exercise ' . $exercise['name']);
        }
        $this->flashSuccess('Exercise removed from the active library.');
        $this->redirect('/admin/exercises');
    }

    public function assign(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $patientId = (int) $params['patientId'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $data = $request->only(['exercise_id', 'repetitions', 'frequency', 'start_date', 'end_date', 'notes']);

        $v = new Validator($data);
        $v->required('exercise_id')->integer('exercise_id');
        $v->required('start_date')->date('start_date');
        if ($data['end_date'] !== '') {
            $v->date('end_date');
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/patients/' . $patientId);
        }

        ExerciseAssignment::create([
            'patient_id' => $patientId,
            'exercise_id' => (int) $data['exercise_id'],
            'repetitions' => $data['repetitions'] !== '' ? $data['repetitions'] : null,
            'frequency' => $data['frequency'] !== '' ? $data['frequency'] : null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] !== '' ? $data['end_date'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            'assigned_by_user_id' => $this->currentAdminId(),
            'created_at' => Database::now(),
        ]);

        AuditLogger::log($this->currentAdminId(), 'admin', 'assign', 'exercise_assignment', $patientId, 'Assigned exercise to ' . $patient['patient_code']);

        $this->flashSuccess('Exercise assigned.');
        $this->redirect('/admin/patients/' . $patientId);
    }

    public function unassign(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $assignment = ExerciseAssignment::find($id);
        if ($assignment !== null) {
            ExerciseAssignment::deleteById($id);
            AuditLogger::log($this->currentAdminId(), 'admin', 'unassign', 'exercise_assignment', $id, 'Removed exercise assignment');
            $this->flashSuccess('Exercise assignment removed.');
            $this->redirect('/admin/patients/' . $assignment['patient_id']);
        }
        $this->flashError('Assignment not found.');
        $this->redirectBack('/admin/patients');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['name', 'category', 'instructions', 'default_repetitions', 'default_frequency', 'video_link']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('instructions');
        if ($data['video_link'] !== '' && filter_var($data['video_link'], FILTER_VALIDATE_URL) === false) {
            $v->custom('video_link', static fn () => false, 'Video link must be a valid URL.');
        }

        $sanitized = [
            'name' => $data['name'],
            'category' => $data['category'] !== '' ? $data['category'] : null,
            'instructions' => $data['instructions'],
            'default_repetitions' => $data['default_repetitions'] !== '' ? $data['default_repetitions'] : null,
            'default_frequency' => $data['default_frequency'] !== '' ? $data['default_frequency'] : null,
            'video_link' => $data['video_link'] !== '' ? $data['video_link'] : null,
        ];

        return [$sanitized, $v->errors()];
    }
}
