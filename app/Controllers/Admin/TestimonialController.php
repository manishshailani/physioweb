<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Testimonial;

final class TestimonialController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->view('admin.testimonials.index', [
            'page_title' => 'Testimonials',
            'pending' => Testimonial::pending(),
            'approved' => Testimonial::approved(),
            'rejected' => Database::fetchAll("SELECT * FROM testimonials WHERE status = 'rejected' ORDER BY created_at DESC"),
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        $data = $request->only(['patient_name', 'rating', 'content']);
        $v = new Validator($data);
        $v->required('patient_name')->maxLength('patient_name', 150);
        $v->required('content');
        $v->required('rating')->integer('rating');
        if ($v->passes() && ((int) $data['rating'] < 1 || (int) $data['rating'] > 5)) {
            $this->flashErrors(['rating' => ['Rating must be between 1 and 5.']], $request->all());
            $this->redirect('/admin/testimonials');
        }
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/testimonials');
        }

        $id = Testimonial::create([
            'patient_name' => $data['patient_name'],
            'patient_photo' => null,
            'rating' => (int) $data['rating'],
            'content' => $data['content'],
            'status' => 'approved',
            'source' => 'admin_added',
            'created_at' => Database::now(),
            'approved_at' => Database::now(),
        ]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'testimonial', $id, 'Added testimonial from ' . $data['patient_name']);

        $this->flashSuccess('Testimonial added and published.');
        $this->redirect('/admin/testimonials');
    }

    public function approve(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        Testimonial::approve($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'approve', 'testimonial', $id, 'Approved testimonial');
        $this->flashSuccess('Testimonial approved and published.');
        $this->redirect('/admin/testimonials');
    }

    public function reject(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        Testimonial::reject($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'reject', 'testimonial', $id, 'Rejected testimonial');
        $this->flashSuccess('Testimonial rejected.');
        $this->redirect('/admin/testimonials');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        Testimonial::deleteById($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'testimonial', $id, 'Deleted testimonial');
        $this->flashSuccess('Testimonial deleted.');
        $this->redirect('/admin/testimonials');
    }
}
