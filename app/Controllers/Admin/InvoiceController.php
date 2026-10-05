<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Services\PdfService;

final class InvoiceController extends Controller
{
    private const STATUSES = ['unpaid', 'partially_paid', 'paid', 'cancelled'];
    private const PAYMENT_MODES = ['cash', 'upi', 'credit_card', 'debit_card', 'net_banking'];

    public function index(Request $request): void
    {
        $this->requireAdmin();
        $status = (string) $request->query('status', '');
        $search = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));

        [$where, $params] = $this->buildFilters($status, $search);
        $result = Invoice::paginatedWithPatient($page, 15, $where, $params);

        $this->view('admin.invoices.index', [
            'page_title' => 'Invoices',
            'invoices' => $result['data'],
            'pagination' => $result,
            'statuses' => self::STATUSES,
            'filters' => ['status' => $status, 'q' => $search],
        ], 'layouts.admin');
    }

    public function create(Request $request): void
    {
        $this->requireAdmin();
        $preselectedPatientId = (int) $request->query('patient_id', 0);
        $this->view('admin.invoices.form', [
            'page_title' => 'New Invoice',
            'patients' => Patient::activePaginated(1, 500)['data'],
            'services' => Service::activeOrdered(),
            'preselectedPatientId' => $preselectedPatientId,
        ], 'layouts.admin');
    }

    public function store(Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);

        $patientId = (int) $request->input('patient_id', 0);
        $patient = Patient::find($patientId);
        if ($patient === null) {
            $this->flashError('Please select a valid patient.');
            $this->redirect('/admin/invoices/create');
        }

        $rawItems = $request->input('items', []);
        $items = $this->sanitizeItems(is_array($rawItems) ? $rawItems : []);
        if ($items === []) {
            $this->flashError('Add at least one line item with a description, quantity, and unit price.');
            $this->redirect('/admin/invoices/create');
        }

        $invoiceDate = (string) $request->input('invoice_date', date('Y-m-d'));
        $dueDate = (string) $request->input('due_date', '');
        $discount = (float) $request->input('discount_amount', 0);
        $notes = (string) $request->input('notes', '');

        try {
            $invoice = Invoice::createWithItems([
                'patient_id' => $patientId,
                'invoice_date' => $invoiceDate !== '' ? $invoiceDate : date('Y-m-d'),
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'discount_amount' => $discount,
                'notes' => $notes !== '' ? $notes : null,
                'created_by_user_id' => $this->currentAdminId(),
            ], $items);
        } catch (\InvalidArgumentException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/admin/invoices/create');
        }

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'invoice', $invoice['id'], 'Created invoice ' . $invoice['invoice_number'] . ' for ' . $patient['patient_code']);

        $this->flashSuccess('Invoice ' . $invoice['invoice_number'] . ' created.');
        $this->redirect('/admin/invoices/' . $invoice['id']);
    }

    public function show(array $params): void
    {
        $this->requireAdmin();
        $id = (int) $params['id'];
        $invoice = Invoice::withPatient($id);
        if ($invoice === null) {
            Response::notFound('Invoice not found.');
        }

        $this->view('admin.invoices.show', [
            'page_title' => 'Invoice ' . $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => Invoice::itemsFor($id),
            'payments' => Invoice::paymentsFor($id),
            'paymentModes' => self::PAYMENT_MODES,
            'outstanding' => Invoice::outstandingBalance($invoice),
        ], 'layouts.admin');
    }

    /** Streams the invoice as a PDF download. Requires dompdf (composer install) - see docs/INSTALL.md. */
    public function pdf(array $params): void
    {
        $this->requireAdmin();
        $id = (int) $params['id'];
        $invoice = Invoice::withPatient($id);
        if ($invoice === null) {
            Response::notFound('Invoice not found.');
        }

        $html = $this->renderInvoiceHtml($invoice);

        try {
            $pdfBytes = (new PdfService())->renderHtmlToPdf($html);
        } catch (\RuntimeException $e) {
            Logger::warning('Invoice PDF generation unavailable: ' . $e->getMessage());
            $this->flashError('PDF generation is not available on this server yet (the dompdf library is not installed). See docs/INSTALL.md.');
            $this->redirect('/admin/invoices/' . $id);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.pdf"');
        header('Content-Length: ' . strlen($pdfBytes));
        echo $pdfBytes;
        exit;
    }

    /** Emails the invoice (as an HTML summary, with the PDF attached when dompdf is available) to the patient. */
    public function email(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $invoice = Invoice::withPatient($id);
        if ($invoice === null) {
            Response::notFound('Invoice not found.');
        }

        if (empty($invoice['email'])) {
            $this->flashError('This patient has no email address on file.');
            $this->redirect('/admin/invoices/' . $id);
        }

        $attachments = [];
        try {
            $pdfBytes = (new PdfService())->renderHtmlToPdf($this->renderInvoiceHtml($invoice));
            $attachments[] = ['content' => $pdfBytes, 'name' => $invoice['invoice_number'] . '.pdf', 'mime' => 'application/pdf'];
        } catch (\RuntimeException $e) {
            Logger::warning('Invoice email sent without PDF attachment: ' . $e->getMessage());
        }

        $mailer = Mailer::fromEnv();
        $sent = $mailer->send(
            $invoice['email'],
            $invoice['first_name'] . ' ' . $invoice['last_name'],
            'Invoice ' . $invoice['invoice_number'],
            $this->renderInvoiceEmailHtml($invoice),
            '',
            $attachments
        );

        if ($sent) {
            AuditLogger::log($this->currentAdminId(), 'admin', 'email', 'invoice', $id, 'Emailed invoice ' . $invoice['invoice_number'] . ' to ' . $invoice['email']);
            $this->flashSuccess('Invoice emailed to ' . $invoice['email'] . '.');
        } else {
            $this->flashError('Could not send the email. Check the mail server configuration (see docs/INSTALL.md) and server logs.');
        }

        $this->redirect('/admin/invoices/' . $id);
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $invoice = Invoice::find($id);
        if ($invoice !== null) {
            Invoice::updateById($id, ['status' => 'cancelled', 'updated_at' => Database::now()]);
            AuditLogger::log($this->currentAdminId(), 'admin', 'cancel', 'invoice', $id, 'Cancelled invoice ' . $invoice['invoice_number']);
        }
        $this->flashSuccess('Invoice cancelled.');
        $this->redirect('/admin/invoices');
    }

    /** @return array{0: string, 1: array} */
    private function buildFilters(string $status, string $search): array
    {
        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $concat = Database::concat(['p.first_name', "' '", 'p.last_name']);
            $where[] = "(i.invoice_number LIKE :q OR p.patient_code LIKE :q OR {$concat} LIKE :q)";
            $params['q'] = '%' . $search . '%';
        }

        return [$where === [] ? '1=1' : implode(' AND ', $where), $params];
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @return array<int, array{service_id: ?int, description: string, quantity: int, unit_price: float, gst_rate: float}>
     */
    private function sanitizeItems(array $rawItems): array
    {
        $items = [];
        foreach ($rawItems as $row) {
            if (!is_array($row)) {
                continue;
            }
            $description = trim((string) ($row['description'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            if ($description === '' || $quantity < 1 || $unitPrice < 0) {
                continue;
            }
            $serviceId = isset($row['service_id']) && $row['service_id'] !== '' ? (int) $row['service_id'] : null;
            $items[] = [
                'service_id' => $serviceId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gst_rate' => (float) ($row['gst_rate'] ?? 0),
            ];
        }
        return $items;
    }

    /** Renders the standalone (no admin chrome) HTML document used both for the PDF and visually for reference. */
    private function renderInvoiceHtml(array $invoice): string
    {
        return \App\Core\View::render('admin.invoices.pdf', [
            'invoice' => $invoice,
            'items' => Invoice::itemsFor((int) $invoice['id']),
            'payments' => Invoice::paymentsFor((int) $invoice['id']),
            'outstanding' => Invoice::outstandingBalance($invoice),
            'settings' => \App\Models\Setting::allAsMap(),
        ]);
    }

    private function renderInvoiceEmailHtml(array $invoice): string
    {
        $number = htmlspecialchars((string) $invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
        $total = number_format((float) $invoice['total_amount'], 2);
        $outstanding = number_format(Invoice::outstandingBalance($invoice), 2);
        $name = htmlspecialchars((string) $invoice['first_name'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;">
                <h2>Invoice {$number}</h2>
                <p>Dear {$name},</p>
                <p>Please find your invoice attached (if PDF generation is available on the server).</p>
                <p>Total amount: <strong>&#8377;{$total}</strong><br>Outstanding balance: <strong>&#8377;{$outstanding}</strong></p>
                <p>Thank you for choosing us for your care.</p>
            </div>
            HTML;
    }
}
