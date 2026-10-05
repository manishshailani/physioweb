<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

final class AdminAuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::isAdmin()) {
            $this->redirect('/admin');
        }
        $this->view('auth.admin-login', ['page_title' => 'Staff Login']);
    }

    public function login(Request $request): void
    {
        $this->verifyCsrf($request);

        $email = (string) $request->input('email');
        $password = (string) $request->input('password');

        $v = new Validator(['email' => $email, 'password' => $password]);
        $v->required('email')->email('email');
        $v->required('password');

        if ($v->fails()) {
            $this->flashErrors($v->errors(), ['email' => $email]);
            $this->redirect('/admin/login');
        }

        $result = Auth::attemptAdminLogin($email, $password, $request->ip());

        if (!$result['ok']) {
            $this->flashError($result['error']);
            $this->redirect('/admin/login');
        }

        User::touchLastLogin((int) $result['user']['id']);
        AuditLogger::log((int) $result['user']['id'], 'admin', 'login', 'user', (int) $result['user']['id'], 'Admin login');

        $this->redirect('/admin');
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        $user = Auth::adminUser();
        if ($user !== null) {
            AuditLogger::log((int) $user['id'], 'admin', 'logout', 'user', (int) $user['id'], 'Admin logout');
        }
        Auth::logoutAdmin();
        $this->redirect('/admin/login');
    }
}
