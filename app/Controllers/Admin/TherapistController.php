<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Service as ServiceModel;
use App\Models\Staff;
use App\Models\Therapist;

final class TherapistController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $therapists = Therapist::allWithStaff(false);
        $therapistStaffIds = array_column($therapists, 'staff_id');
        $eligibleStaff = array_values(array_filter(
            Staff::activeByRole('physiotherapist'),
            static fn (array $s): bool => !in_array((int) $s['id'], $therapistStaffIds, true)
        ));

        $this->view('admin.therapists.index', [
            'page_title' => 'Therapists',
            'therapists' => $therapists,
            'eligibleStaff' => $eligibleStaff,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/therapists');
        }

        $data['created_at'] = Database::now();
        $data['updated_at'] = Database::now();
        $id = Therapist::create($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'therapist', $id, 'Created public therapist profile (slug ' . $data['slug'] . ')');

        $this->flashSuccess('Therapist profile created.');
        $this->redirect('/admin/therapists');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $therapist = Therapist::find((int) $params['id']);
        if ($therapist === null) {
            Response::notFound('Therapist not found.');
        }
        $this->view('admin.therapists.form', [
            'page_title' => 'Edit Therapist Profile',
            'therapist' => $therapist,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $therapist = Therapist::find($id);
        if ($therapist === null) {
            Response::notFound('Therapist not found.');
        }

        $data = $request->only(['bio', 'default_session_minutes', 'is_public', 'display_order']);
        $v = new Validator($data);
        if ($data['default_session_minutes'] !== '') {
            $v->integer('default_session_minutes')->min('default_session_minutes', 5);
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/therapists/' . $id . '/edit');
        }

        Therapist::updateById($id, [
            'bio' => $data['bio'] !== '' ? $data['bio'] : null,
            'default_session_minutes' => $data['default_session_minutes'] !== '' ? (int) $data['default_session_minutes'] : 45,
            'is_public' => $request->input('is_public') !== null ? 1 : 0,
            'display_order' => $data['display_order'] !== '' ? (int) $data['display_order'] : 0,
            'updated_at' => Database::now(),
        ]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'therapist', $id, 'Updated therapist profile ' . $therapist['slug']);

        $this->flashSuccess('Therapist profile updated.');
        $this->redirect('/admin/therapists');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $therapist = Therapist::find($id);
        if ($therapist !== null) {
            // Unpublish rather than delete the therapist row: appointments.therapist_id
            // and treatment_plans.therapist_id reference it with ON DELETE SET NULL,
            // which would silently strip the therapist from historical records.
            Therapist::updateById($id, ['is_public' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'unpublish', 'therapist', $id, 'Unpublished therapist profile ' . $therapist['slug']);
        }
        $this->flashSuccess('Therapist profile hidden from the public website.');
        $this->redirect('/admin/therapists');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['staff_id', 'slug', 'bio', 'default_session_minutes', 'display_order']);

        $v = new Validator($data);
        $v->required('staff_id')->integer('staff_id');
        $v->required('slug')->maxLength('slug', 150);
        if ($data['default_session_minutes'] !== '') {
            $v->integer('default_session_minutes')->min('default_session_minutes', 5);
        }

        $slug = $data['slug'] !== '' ? ServiceModel::slugify($data['slug']) : '';
        if ($slug !== '' && Therapist::whereFirst('slug', $slug) !== null) {
            $v->custom('slug', static fn () => false, 'That slug is already in use by another therapist.');
        }

        $sanitized = [
            'staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            'slug' => $slug,
            'bio' => $data['bio'] !== '' ? $data['bio'] : null,
            'default_session_minutes' => $data['default_session_minutes'] !== '' ? (int) $data['default_session_minutes'] : 45,
            'is_public' => 1,
            'display_order' => $data['display_order'] !== '' ? (int) $data['display_order'] : 0,
        ];

        return [$sanitized, $v->errors()];
    }
}
