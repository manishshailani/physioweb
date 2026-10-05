<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Patient;

final class PatientAuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::isPatient()) {
            $this->redirect('/patient');
        }
        $this->view('auth.patient-login', ['page_title' => 'Patient Login'], 'layouts.public');
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
            $this->redirect('/patient/login');
        }

        $result = Auth::attemptPatientLogin($email, $password, $request->ip());

        if (!$result['ok']) {
            $this->flashError($result['error']);
            $this->redirect('/patient/login');
        }

        AuditLogger::log((int) $result['patient']['id'], 'patient', 'login', 'patient', (int) $result['patient']['id'], 'Patient portal login');

        $this->redirect('/patient');
    }

    public function showRegister(): void
    {
        if (Auth::isPatient()) {
            $this->redirect('/patient');
        }
        $this->view('auth.patient-register', ['page_title' => 'Patient Registration'], 'layouts.public');
    }

    /**
     * Self-registration for the patient portal. This creates a NEW patient
     * record with a portal login; it is intentionally separate from
     * Admin\PatientController::store(), which front-desk staff use to
     * register a patient who may never use the portal themselves. If a
     * patient already exists in the clinic's records under the same
     * email (registered by the front desk), this form will reject the
     * duplicate email and the patient should be given portal access by
     * staff instead (see docs/USER_MANUAL.md).
     */
    public function register(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = $request->only(['first_name', 'last_name', 'gender', 'mobile', 'email', 'password', 'password_confirmation']);

        $v = new Validator($data);
        $v->required('first_name')->maxLength('first_name', 100);
        $v->required('last_name')->maxLength('last_name', 100);
        $v->required('gender')->in('gender', ['male', 'female', 'other']);
        $v->required('mobile')->mobile('mobile');
        $v->required('email')->email('email');
        $v->required('password')->minLength('password', 8);
        $v->matches('password_confirmation', 'password');

        if ($v->passes()) {
            $existing = Patient::whereFirst('email', $data['email']);
            if ($existing !== null) {
                $this->flashErrors(['email' => ['An account with this email already exists. Please log in, or contact the clinic if you believe this is an error.']], $data);
                $this->redirect('/patient/register');
            }
        }

        if ($v->fails()) {
            $this->flashErrors($v->errors(), $data);
            $this->redirect('/patient/register');
        }

        $result = Patient::register([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'password_hash' => Auth::hashPassword($data['password']),
            'is_active' => 1,
        ]);

        AuditLogger::log($result['id'], 'patient', 'create', 'patient', $result['id'], 'Patient self-registered via portal');

        Auth::attemptPatientLogin($data['email'], $data['password'], $request->ip());

        $this->flashSuccess('Welcome! Your patient ID is ' . $result['patient_code'] . '.');
        $this->redirect('/patient');
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        $patient = Auth::patientUser();
        if ($patient !== null) {
            AuditLogger::log((int) $patient['id'], 'patient', 'logout', 'patient', (int) $patient['id'], 'Patient portal logout');
        }
        Auth::logoutPatient();
        $this->redirect('/patient/login');
    }
}
