<?php

declare(strict_types=1);

namespace App\Controllers\Patient;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Response;
use App\Core\View;
use App\Models\Invoice;
use App\Models\PatientPackage;
use App\Models\Setting;
use App\Services\PdfService;

final class PatientBillingController extends Controller
{
    public function index(): void
    {
        $this->requirePatient();
        $patientId = $this->currentPatientId();

        $this->view('patient.billing.index', [
            'page_title' => 'Billing & Invoices',
            'invoices' => Invoice::listWithPatient('i.patient_id = :pid', ['pid' => $patientId]),
            'packages' => PatientPackage::forPatientWithPackage($patientId),
            'outstanding' => Invoice::outstandingForPatient($patientId),
        ], 'layouts.patient');
    }

    /**
     * Streams one of the logged-in patient's own invoices as a PDF.
     * Ownership is checked against $this->currentPatientId() (the session,
     * not the request) before anything is rendered, so a patient can never
     * download another patient's invoice by guessing/incrementing the id
     * in the URL -- this is the one place this controller differs from
     * Admin\InvoiceController::pdf(), which is intentionally allowed to
     * fetch any invoice.
     */
    public function invoicePdf(array $params): void
    {
        $this->requirePatient();
        $id = (int) $params['id'];
        $invoice = Invoice::withPatient($id);
        if ($invoice === null || (int) $invoice['patient_id'] !== $this->currentPatientId()) {
            Response::notFound('Invoice not found.');
        }

        // Reuses the exact same admin.invoices.pdf template as
        // Admin\InvoiceController::pdf() (see that method's private
        // renderInvoiceHtml()) so a patient's downloaded invoice is
        // byte-for-byte identical in layout to the one staff can generate
        // -- there is deliberately no separate "patient-facing" invoice
        // template to keep in sync.
        $html = View::render('admin.invoices.pdf', [
            'invoice' => $invoice,
            'items' => Invoice::itemsFor($id),
            'payments' => Invoice::paymentsFor($id),
            'outstanding' => Invoice::outstandingBalance($invoice),
            'settings' => Setting::allAsMap(),
        ]);

        try {
            $pdfBytes = (new PdfService())->renderHtmlToPdf($html);
        } catch (\RuntimeException $e) {
            Logger::warning('Patient invoice PDF generation unavailable: ' . $e->getMessage());
            $this->flashError('PDF generation is not available on this server yet. Please contact the clinic for a copy of your invoice.');
            $this->redirect('/patient/billing');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.pdf"');
        header('Content-Length: ' . strlen($pdfBytes));
        echo $pdfBytes;
        exit;
    }
}
