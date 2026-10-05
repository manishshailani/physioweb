<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientPackage;

final class PackageController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.packages.index', [
            'page_title' => 'Packages',
            'packages' => Package::all('price'),
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/packages');
        }

        $data['is_active'] = 1;
        $data['created_at'] = Database::now();
        $data['updated_at'] = Database::now();
        $id = Package::create($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'package', $id, 'Created package ' . $data['name']);

        $this->flashSuccess('Package created.');
        $this->redirect('/admin/packages');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $package = Package::find((int) $params['id']);
        if ($package === null) {
            Response::notFound('Package not found.');
        }
        $this->view('admin.packages.form', [
            'page_title' => 'Edit Package',
            'package' => $package,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $package = Package::find($id);
        if ($package === null) {
            Response::notFound('Package not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/packages/' . $id . '/edit');
        }

        $data['updated_at'] = Database::now();
        Package::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'package', $id, 'Updated package ' . $data['name']);

        $this->flashSuccess('Package updated.');
        $this->redirect('/admin/packages');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $package = Package::find($id);
        if ($package !== null) {
            // Soft-deactivate: patient_packages.package_id uses ON DELETE RESTRICT,
            // so a hard delete of a package that any patient has ever purchased would
            // fail at the database level anyway; deactivating hides it from new sales
            // while preserving already-sold packages' history intact.
            Package::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'package', $id, 'Deactivated package ' . $package['name']);
        }
        $this->flashSuccess('Package deactivated.');
        $this->redirect('/admin/packages');
    }

    /** Subscribe a patient to a package (called from the patient profile page). */
    public function subscribe(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $patientId = (int) $params['patientId'];
        $patient = Patient::find($patientId);
        if ($patient === null) {
            Response::notFound('Patient not found.');
        }

        $packageId = (int) $request->input('package_id', 0);
        $package = Package::find($packageId);
        if ($package === null) {
            $this->flashError('Please select a valid package.');
            $this->redirect('/admin/patients/' . $patientId);
        }

        $id = PatientPackage::subscribe($patientId, $packageId);
        AuditLogger::log($this->currentAdminId(), 'admin', 'subscribe', 'patient_package', $id, 'Subscribed ' . $patient['patient_code'] . ' to package ' . $package['name']);

        $this->flashSuccess('Patient subscribed to ' . $package['name'] . '.');
        $this->redirect('/admin/patients/' . $patientId);
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['name', 'description', 'total_sessions', 'price', 'discount_percent', 'validity_days']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('total_sessions')->integer('total_sessions')->min('total_sessions', 1);
        $v->required('price')->numeric('price')->min('price', 0);
        if ($data['discount_percent'] !== '') {
            $v->numeric('discount_percent')->min('discount_percent', 0);
        }
        if ($data['validity_days'] !== '') {
            $v->integer('validity_days')->min('validity_days', 1);
        }

        $sanitized = [
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'total_sessions' => $data['total_sessions'] !== '' ? (int) $data['total_sessions'] : 1,
            'price' => $data['price'] !== '' ? round((float) $data['price'], 2) : 0.0,
            'discount_percent' => $data['discount_percent'] !== '' ? round((float) $data['discount_percent'], 2) : 0.0,
            'validity_days' => $data['validity_days'] !== '' ? (int) $data['validity_days'] : 90,
        ];

        return [$sanitized, $v->errors()];
    }
}
