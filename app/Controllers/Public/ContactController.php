<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\ContactInquiry;
use App\Models\Setting;

final class ContactController extends Controller
{
    public function index(): void
    {
        $this->view('public.contact', [
            'settings' => Setting::allAsMap(),
            'page_title' => 'Contact Us',
        ], 'layouts.public');
    }

    public function store(Request $request): void
    {
        $this->verifyCsrf($request);

        $data = $request->only(['name', 'mobile', 'email', 'subject', 'message']);

        $v = new Validator($data);
        $v->required('name')->maxLength('name', 150);
        $v->required('message')->maxLength('message', 2000);
        if ($data['email'] !== '') {
            $v->email('email');
        }
        if ($data['mobile'] !== '') {
            $v->mobile('mobile');
        }

        if ($v->fails()) {
            $this->flashErrors($v->errors(), $data);
            $this->redirect('/contact');
        }

        $id = ContactInquiry::create(array_merge($data, [
            'status' => 'new',
            'created_at' => \App\Core\Database::now(),
        ]));

        AuditLogger::log(null, 'system', 'create', 'contact_inquiry', $id, 'New contact inquiry from ' . $data['name']);

        $this->flashSuccess('Thank you for reaching out. We will get back to you shortly.');
        $this->redirect('/contact');
    }
}
