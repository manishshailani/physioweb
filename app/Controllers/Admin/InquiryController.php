<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\ContactInquiry;

final class InquiryController extends Controller
{
    private const STATUSES = ['new', 'read', 'responded', 'closed'];

    public function index(Request $request): void
    {
        $this->requireAdmin();
        $status = (string) $request->query('status', '');
        $where = $status !== '' && in_array($status, self::STATUSES, true) ? 'status = :status' : '1=1';
        $params = $where === '1=1' ? [] : ['status' => $status];

        $this->view('admin.inquiries.index', [
            'page_title' => 'Inquiries',
            'inquiries' => Database::fetchAll("SELECT * FROM contact_inquiries WHERE {$where} ORDER BY created_at DESC", $params),
            'statuses' => self::STATUSES,
            'filterStatus' => $status,
        ], 'layouts.admin');
    }

    public function updateStatus(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $status = (string) $request->input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            $this->flashError('Invalid status.');
            $this->redirect('/admin/inquiries');
        }

        ContactInquiry::updateById($id, ['status' => $status]);
        AuditLogger::log($this->currentAdminId(), 'admin', 'status_change', 'contact_inquiry', $id, 'Marked inquiry as ' . $status);

        $this->flashSuccess('Inquiry updated.');
        $this->redirect('/admin/inquiries');
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        ContactInquiry::deleteById($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'contact_inquiry', $id, 'Deleted inquiry');
        $this->flashSuccess('Inquiry deleted.');
        $this->redirect('/admin/inquiries');
    }
}
