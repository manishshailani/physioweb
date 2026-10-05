<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Service;

final class ServiceController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.services.index', [
            'page_title' => 'Services',
            'services' => Service::all('display_order, name'),
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/services');
        }

        $data['slug'] = Service::uniqueSlug($data['name']);
        $data['is_active'] = 1;
        $data['created_at'] = Database::now();
        $data['updated_at'] = Database::now();
        $id = Service::create($data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'service', $id, 'Created service ' . $data['name']);

        $this->flashSuccess('Service created.');
        $this->redirect('/admin/services');
    }

    public function edit(array $params): void
    {
        $this->requireAdmin();
        $service = Service::find((int) $params['id']);
        if ($service === null) {
            Response::notFound('Service not found.');
        }
        $this->view('admin.services.form', [
            'page_title' => 'Edit Service',
            'service' => $service,
        ], 'layouts.admin');
    }

    public function update(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $service = Service::find($id);
        if ($service === null) {
            Response::notFound('Service not found.');
        }

        [$data, $errors] = $this->validatedData($request);
        if ($errors !== []) {
            $this->flashErrors($errors, $request->all());
            $this->redirect('/admin/services/' . $id . '/edit');
        }

        if ($data['name'] !== $service['name']) {
            $data['slug'] = Service::uniqueSlug($data['name'], $id);
        }
        $data['updated_at'] = Database::now();
        Service::updateById($id, $data);
        AuditLogger::log($this->currentAdminId(), 'admin', 'update', 'service', $id, 'Updated service ' . $data['name']);

        $this->flashSuccess('Service updated.');
        $this->redirect('/admin/services');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $service = Service::find($id);
        if ($service !== null) {
            // Soft-deactivate: invoice_items.service_id uses ON DELETE SET NULL and
            // appointments.service_id likewise, so a hard delete would silently orphan
            // historical invoices/appointments rather than simply hiding the service
            // from new bookings.
            Service::updateById($id, ['is_active' => 0, 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'deactivate', 'service', $id, 'Deactivated service ' . $service['name']);
        }
        $this->flashSuccess('Service deactivated.');
        $this->redirect('/admin/services');
    }

    /** @return array{0: array, 1: array} [sanitized data, validation errors] */
    private function validatedData(Request $request): array
    {
        $data = $request->only(['name', 'category', 'short_description', 'description', 'fee', 'gst_rate', 'duration_minutes', 'display_order']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('fee')->numeric('fee')->min('fee', 0);
        if ($data['gst_rate'] !== '') {
            $v->numeric('gst_rate')->min('gst_rate', 0);
        }
        if ($data['duration_minutes'] !== '') {
            $v->integer('duration_minutes')->min('duration_minutes', 5);
        }

        $sanitized = [
            'name' => $data['name'],
            'category' => $data['category'] !== '' ? $data['category'] : null,
            'short_description' => $data['short_description'] !== '' ? $data['short_description'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'fee' => $data['fee'] !== '' ? round((float) $data['fee'], 2) : 0.0,
            'gst_rate' => $data['gst_rate'] !== '' ? round((float) $data['gst_rate'], 2) : 0.0,
            'duration_minutes' => $data['duration_minutes'] !== '' ? (int) $data['duration_minutes'] : 45,
            'display_order' => $data['display_order'] !== '' ? (int) $data['display_order'] : 0,
        ];

        return [$sanitized, $v->errors()];
    }
}
