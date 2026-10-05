<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Invoice;
use App\Models\Payment;

final class PaymentController extends Controller
{
    private const PAYMENT_MODES = ['cash', 'upi', 'credit_card', 'debit_card', 'net_banking'];

    public function store(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $invoiceId = (int) $params['invoiceId'];
        $invoice = Invoice::find($invoiceId);
        if ($invoice === null) {
            Response::notFound('Invoice not found.');
        }

        $data = $request->only(['amount', 'payment_mode', 'payment_date', 'reference_number', 'notes']);

        $v = new Validator($data);
        $v->required('amount')->numeric('amount')->min('amount', 0.01);
        $v->required('payment_mode')->in('payment_mode', self::PAYMENT_MODES);
        $v->required('payment_date')->date('payment_date');
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->all());
            $this->redirect('/admin/invoices/' . $invoiceId);
        }

        try {
            $paymentId = Payment::recordForInvoice($invoiceId, [
                'amount' => (float) $data['amount'],
                'payment_mode' => $data['payment_mode'],
                'payment_date' => $data['payment_date'],
                'reference_number' => $data['reference_number'] !== '' ? $data['reference_number'] : null,
                'received_by_user_id' => $this->currentAdminId(),
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/admin/invoices/' . $invoiceId);
        }

        AuditLogger::log($this->currentAdminId(), 'admin', 'create', 'payment', $paymentId, 'Recorded payment of ' . number_format((float) $data['amount'], 2) . ' for invoice ' . $invoice['invoice_number']);

        $this->flashSuccess('Payment recorded.');
        $this->redirect('/admin/invoices/' . $invoiceId);
    }

    public function destroy(array $params, Request $request): void
    {
        $this->requireAdmin();
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $payment = Payment::find($id);
        if ($payment === null) {
            Response::notFound('Payment not found.');
        }

        $invoiceId = (int) $payment['invoice_id'];
        Payment::deleteAndRecalculate($id);
        AuditLogger::log($this->currentAdminId(), 'admin', 'delete', 'payment', $id, 'Deleted payment of ' . number_format((float) $payment['amount'], 2) . ' from invoice #' . $invoiceId);

        $this->flashSuccess('Payment deleted and invoice balance recalculated.');
        $this->redirect('/admin/invoices/' . $invoiceId);
    }
}
