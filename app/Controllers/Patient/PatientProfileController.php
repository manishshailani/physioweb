<?php

declare(strict_types=1);

namespace App\Controllers\Patient;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Patient;

/**
 * Self-service profile editing: contact/address/medical-history fields and
 * an optional password change, both in one form submit. Password change is
 * optional per submit (fields left blank = "don't change it") rather than
 * a separate form, which is why validation on the three password fields is
 * applied manually below instead of via Validator::required() -- they are
 * only required relative to each other, not unconditionally.
 */
final class PatientProfileController extends Controller
{
    public function edit(): void
    {
        $this->requirePatient();
        $patient = Patient::find($this->currentPatientId());

        $this->view('patient.profile', [
            'page_title' => 'My Profile',
            'patient' => $patient,
        ], 'layouts.patient');
    }

    public function update(Request $request): void
    {
        $this->requirePatient();
        $this->verifyCsrf($request);
        $patientId = $this->currentPatientId();
        $patient = Patient::find($patientId);

        $data = $request->only([
            'first_name', 'last_name', 'mobile', 'alt_mobile', 'email',
            'address', 'city', 'state', 'country', 'pincode', 'blood_group',
            'allergies', 'existing_conditions', 'medical_history',
            'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_mobile',
        ]);

        $v = new Validator($data);
        $v->required('first_name')->maxLength('first_name', 100);
        $v->required('last_name')->maxLength('last_name', 100);
        $v->required('mobile')->mobile('mobile');
        if ($data['email'] !== '') {
            $v->email('email');
            $existing = Patient::whereFirst('email', $data['email']);
            if ($existing !== null && (int) $existing['id'] !== $patientId) {
                $v->custom('email', static fn () => false, 'This email is already used by another account.');
            }
        } else {
            $data['email'] = null;
        }
        foreach (['alt_mobile', 'address', 'city', 'state', 'pincode', 'blood_group', 'allergies', 'existing_conditions', 'medical_history', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_mobile'] as $optional) {
            if ($data[$optional] === '') {
                $data[$optional] = null;
            }
        }

        $currentPassword = (string) $request->input('current_password', '');
        $newPassword = (string) $request->input('new_password', '');
        $newPasswordConfirmation = (string) $request->input('new_password_confirmation', '');
        $changingPassword = $currentPassword !== '' || $newPassword !== '' || $newPasswordConfirmation !== '';

        if ($changingPassword) {
            if (!password_verify($currentPassword, (string) $patient['password_hash'])) {
                $v->custom('current_password', static fn () => false, 'Your current password is incorrect.');
            }
            if (mb_strlen($newPassword) < 8) {
                $v->custom('new_password', static fn () => false, 'New password must be at least 8 characters.');
            } elseif ($newPassword !== $newPasswordConfirmation) {
                $v->custom('new_password_confirmation', static fn () => false, 'New password confirmation does not match.');
            }
        }

        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/patient/profile');
        }

        $data['updated_at'] = Database::now();
        if ($changingPassword) {
            $data['password_hash'] = Auth::hashPassword($newPassword);
        }

        Patient::updateById($patientId, $data);
        AuditLogger::log($patientId, 'patient', 'update', 'patient', $patientId, 'Patient updated own profile' . ($changingPassword ? ' and changed password' : ''));

        $this->flashSuccess('Profile updated.');
        $this->redirect('/patient/profile');
    }
}
