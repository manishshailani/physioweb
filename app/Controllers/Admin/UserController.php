<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Staff;
use App\Models\User;

final class UserController extends Controller
{
    private const ROLES = ['admin', 'receptionist', 'therapist_staff'];

    public function index(): void
    {
        $this->requireAdminRole('admin');
        $this->view('admin.users.index', [
            'page_title' => 'CMS Users',
            'users' => User::all('is_active DESC, name'),
            'staff' => Staff::allActive(),
            'roles' => self::ROLES,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdminRole('admin');
        $this->verifyCsrf($request);

        $data = $request->only(['staff_id', 'name', 'email', 'password', 'role']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('email')->email('email');
        $v->required('password')->minLength('password', 8);
        $v->required('role')->in('role', self::ROLES);
        if ($data['email'] !== '' && User::whereFirst('email', $data['email']) !== null) {
            $v->custom('email', static fn () => false, 'That email is already registered to another user.');
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/users');
        }

        $id = User::create([
            'staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => Auth::hashPassword($data['password']),
            'role' => $data['role'],
            'is_active' => 1,
            'created_at' => Database::now(),
            'updated_at' => Database::now(),
        ]);

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'user', $id, 'Created CMS user ' . $data['email'] . ' (' . $data['role'] . ')');

        $this->flashSuccess('User created.');
        $this->redirect('/admin/users');
    }

    public function edit(array $params): void
    {
        $this->requireAdminRole('admin');
        $user = User::find((int) $params['id']);
        if ($user === null) {
            Response::notFound('User not found.');
        }
        $this->view('admin.users.form', [
            'page_title' => 'Edit User',
            'editUser' => $user,
            'staff' => Staff::allActive(),
            'roles' => self::ROLES,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdminRole('admin');
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $user = User::find($id);
        if ($user === null) {
            Response::notFound('User not found.');
        }

        $data = $request->only(['staff_id', 'name', 'email', 'password', 'role']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('email')->email('email');
        $v->required('role')->in('role', self::ROLES);
        if ($data['password'] !== '') {
            $v->minLength('password', 8);
        }
        $existing = User::whereFirst('email', $data['email']);
        if ($existing !== null && (int) $existing['id'] !== $id) {
            $v->custom('email', static fn () => false, 'That email is already registered to another user.');
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/users/' . $id . '/edit');
        }

        $update = [
            'staff_id' => $data['staff_id'] !== '' ? (int) $data['staff_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'updated_at' => Database::now(),
        ];
        if ($data['password'] !== '') {
            $update['password_hash'] = Auth::hashPassword($data['password']);
        }

        User::updateById($id, $update);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'user', $id, 'Updated CMS user ' . $data['email']);

        $this->flashSuccess('User updated.');
        $this->redirect('/admin/users');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdminRole('admin');
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        if ($id === $this->currentAdminId()) {
            $this->flashError('You cannot deactivate your own account while logged in.');
            $this->redirect('/admin/users');
        }

        $user = User::find($id);
        if ($user !== null) {
            User::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'user', $id, 'Deactivated CMS user ' . $user['email']);
        }
        $this->flashSuccess('User deactivated.');
        $this->redirect('/admin/users');
    }
}
