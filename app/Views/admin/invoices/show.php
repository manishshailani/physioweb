<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1">Invoice <?= View::e($invoice['invoice_number']) ?>
      <span class="badge badge-invoice-<?= View::e($invoice['status']) ?>"><?= View::e($invoice['status']) ?></span>
    </h2>
    <p class="text-muted mb-0">
      <a href="/admin/patients/<?= (int) $invoice['patient_id'] ?>"><?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></a>
      (<?= View::e($invoice['patient_code']) ?>) &middot; Dated <?= View::dateFormat($invoice['invoice_date']) ?>
      <?php if (!empty($invoice['due_date'])): ?> &middot; Due <?= View::dateFormat($invoice['due_date']) ?><?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/invoices/<?= (int) $invoice['id'] ?>/pdf" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-pdf me-1"></i> Download PDF</a>
    <form method="post" action="/admin/invoices/<?= (int) $invoice['id'] ?>/email" class="d-inline">
      <?= $csrf_field ?>
      <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-envelope me-1"></i> Email to Patient</button>
    </form>
    <?php if ($invoice['status'] !== 'cancelled'): ?>
    <form method="post" action="/admin/invoices/<?= (int) $invoice['id'] ?>/delete" onsubmit="return confirm('Cancel this invoice?');" class="d-inline">
      <?= $csrf_field ?>
      <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-ban me-1"></i> Cancel Invoice</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>GST %</th><th class="text-end">Line Total</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?= View::e($item['description']) ?></td>
            <td><?= (int) $item['quantity'] ?></td>
            <td>&#8377;<?= View::money($item['unit_price']) ?></td>
            <td><?= View::e($item['gst_rate']) ?>%</td>
            <td class="text-end">&#8377;<?= View::money($item['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">&#8377;<?= View::money($invoice['subtotal']) ?></td></tr>
        <tr><td colspan="4" class="text-end">GST</td><td class="text-end">&#8377;<?= View::money($invoice['gst_amount']) ?></td></tr>
        <?php if ((float) $invoice['discount_amount'] > 0): ?>
        <tr><td colspan="4" class="text-end">Discount</td><td class="text-end">-&#8377;<?= View::money($invoice['discount_amount']) ?></td></tr>
        <?php endif; ?>
        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">&#8377;<?= View::money($invoice['total_amount']) ?></td></tr>
        <tr><td colspan="4" class="text-end">Paid</td><td class="text-end">&#8377;<?= View::money($invoice['amount_paid']) ?></td></tr>
        <tr class="fw-bold text-danger"><td colspan="4" class="text-end">Outstanding</td><td class="text-end">&#8377;<?= View::money($outstanding) ?></td></tr>
      </tfoot>
    </table>
  </div>
</div>

<?php if (!empty($invoice['notes'])): ?>
  <div class="card p-3 mb-3"><strong>Notes:</strong> <?= View::e($invoice['notes']) ?></div>
<?php endif; ?>

<?php if ($outstanding > 0 && $invoice['status'] !== 'cancelled'): ?>
<div class="card p-4 mb-3">
  <h6 class="text-uppercase text-muted small mb-3">Record Payment</h6>
  <form method="post" action="/admin/invoices/<?= (int) $invoice['id'] ?>/payments" class="row g-2 align-items-end">
    <?= $csrf_field ?>
    <div class="col-md-2">
      <label class="form-label small">Amount</label>
      <input type="number" step="0.01" name="amount" class="form-control" max="<?= $outstanding ?>" value="<?= $outstanding ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Mode</label>
      <select name="payment_mode" class="form-select" required>
        <?php foreach ($paymentModes as $m): ?>
          <option value="<?= $m ?>"><?= ucfirst(str_replace('_', ' ', $m)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Date</label>
      <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Reference #</label>
      <input type="text" name="reference_number" class="form-control">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Record</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Date</th><th>Mode</th><th>Amount</th><th>Reference</th><th></th></tr></thead>
      <tbody>
        <?php if (!$payments): ?><tr><td colspan="5" class="text-center text-muted py-4">No payments recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= View::dateFormat($p['payment_date']) ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $p['payment_mode'])) ?></td>
            <td>&#8377;<?= View::money($p['amount']) ?></td>
            <td><?= View::e($p['reference_number'] ?? '') ?></td>
            <td>
              <form method="post" action="/admin/payments/<?= (int) $p['id'] ?>/delete" onsubmit="return confirm('Delete this payment? The invoice balance will be recalculated.');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
