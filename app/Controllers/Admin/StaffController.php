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
use App\Models\Staff;

final class StaffController extends Controller
{
    private const ROLES = ['physiotherapist', 'receptionist', 'assistant', 'admin'];

    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.staff.index', [
            'page_title' => 'Staff',
            'staff' => Staff::all('is_active DESC, first_name'),
            'roles' => self::ROLES,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/staff');
        }

        $photo = $request->file('photo');
        if ($photo !== null) {
            $uploaded = FileUpload::store($photo, dirname(__DIR__, 3) . '/public/uploads/staff', 'image', 5);
            if (!$uploaded['ok']) {
                $this->flashError($uploaded['error']);
                $this->redirect('/admin/staff');
            }
            $data['photo'] = '/uploads/staff/' . $uploaded['stored_name'];
        }

        $data['is_active'] = 1;
        $result = Staff::register($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'staff', $result['id'], 'Added staff member ' . $data['first_name'] . ' ' . $data['last_name']);

        $this->flashSuccess('Staff member ' . $result['staff_code'] . ' added.');
        $this->redirect('/admin/staff');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $staff = Staff::find((int) $params['id']);
        if ($staff === null) {
            Response::notFound('Staff member not found.');
        }
        $this->view('admin.staff.form', [
            'page_title' => 'Edit Staff Member',
            'staff' => $staff,
            'roles' => self::ROLES,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $staff = Staff::find($id);
        if ($staff === null) {
            Response::notFound('Staff member not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/staff/' . $id . '/edit');
        }

        $photo = $request->file('photo');
        if ($photo !== null) {
            $uploaded = FileUpload::store($photo, dirname(__DIR__, 3) . '/public/uploads/staff', 'image', 5);
            if (!$uploaded['ok']) {
                $this->flashError($uploaded['error']);
                $this->redirect('/admin/staff/' . $id . '/edit');
            }
            $data['photo'] = '/uploads/staff/' . $uploaded['stored_name'];
        }

        $data['updated_at'] = Database::now();
        Staff::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'staff', $id, 'Updated staff member ' . $data['first_name'] . ' ' . $data['last_name']);

        $this->flashSuccess('Staff member updated.');
        $this->redirect('/admin/staff');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $staff = Staff::find($id);
        if ($staff !== null) {
            // Soft-deactivate: therapists.staff_id and users.staff_id reference this row
            // (therapists CASCADEs, users SET NULLs), so a hard delete would silently
            // remove a therapist's public profile or detach a CMS login - deactivating
            // instead just hides the staff member from active listings.
            Staff::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'staff', $id, 'Deactivated staff member ' . $staff['first_name'] . ' ' . $staff['last_name']);
        }
        $this->flashSuccess('Staff member deactivated.');
        $this->redirect('/admin/staff');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['first_name', 'last_name', 'role', 'qualification', 'specialization', 'experience_years', 'phone', 'email', 'joining_date']);

        $v = new Validator($data);
        $v->required('first_name')->maxLength('first_name', 100);
        $v->required('role')->in('role', self::ROLES);
        if ($data['email'] !== '') {
            $v->email('email');
        }
        if ($data['experience_years'] !== '') {
            $v->numeric('experience_years')->min('experience_years', 0);
        }
        if ($data['joining_date'] !== '') {
            $v->date('joining_date');
        }

        $sanitized = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] !== '' ? $data['last_name'] : '',
            'role' => $data['role'],
            'qualification' => $data['qualification'] !== '' ? $data['qualification'] : null,
            'specialization' => $data['specialization'] !== '' ? $data['specialization'] : null,
            'experience_years' => $data['experience_years'] !== '' ? (float) $data['experience_years'] : null,
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'email' => $data['email'] !== '' ? $data['email'] : null,
            'joining_date' => $data['joining_date'] !== '' ? $data['joining_date'] : null,
        ];

        return [$sanitized, $v->errors()];
    }
}
