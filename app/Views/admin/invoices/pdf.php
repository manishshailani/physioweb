<?php
use App\Core\View;
$clinicName = $settings['clinic_name'] ?? 'Clinic';
$clinicAddress = $settings['clinic_address'] ?? '';
$clinicPhone = $settings['clinic_phone'] ?? '';
$clinicEmail = $settings['clinic_email'] ?? '';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice <?= View::e($invoice['invoice_number']) ?></title>
<style>
  body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 24px; }
  h1 { font-size: 20px; margin: 0 0 4px; }
  .muted { color: #666; }
  .header { display: table; width: 100%; margin-bottom: 20px; }
  .header .col { display: table-cell; vertical-align: top; }
  .header .col.right { text-align: right; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; }
  th { background: #f5f5f5; text-transform: uppercase; font-size: 10px; }
  .text-right { text-align: right; }
  .totals td { border: none; padding: 3px 4px; }
  .totals .label { text-align: right; }
  .grand-total { font-weight: bold; font-size: 14px; border-top: 2px solid #222; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; color: #fff; }
  .badge-unpaid { background: #dc3545; }
  .badge-partially_paid { background: #fd7e14; }
  .badge-paid { background: #198754; }
  .badge-cancelled { background: #6c757d; }
</style>
</head>
<body>
  <div class="header">
    <div class="col">
      <h1><?= View::e($clinicName) ?></h1>
      <div class="muted"><?= nl2br(View::e($clinicAddress)) ?></div>
      <div class="muted"><?= View::e($clinicPhone) ?> <?= $clinicPhone && $clinicEmail ? '&middot;' : '' ?> <?= View::e($clinicEmail) ?></div>
    </div>
    <div class="col right">
      <h1>INVOICE</h1>
      <div><?= View::e($invoice['invoice_number']) ?></div>
      <div class="muted">Date: <?= View::dateFormat($invoice['invoice_date']) ?></div>
      <?php if (!empty($invoice['due_date'])): ?><div class="muted">Due: <?= View::dateFormat($invoice['due_date']) ?></div><?php endif; ?>
      <div class="badge badge-<?= View::e($invoice['status']) ?>"><?= strtoupper(str_replace('_', ' ', $invoice['status'])) ?></div>
    </div>
  </div>

  <div style="margin-bottom:16px;">
    <strong>Billed To</strong><br>
    <?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?> (<?= View::e($invoice['patient_code']) ?>)<br>
    <?= View::e($invoice['mobile'] ?? '') ?><?= !empty($invoice['email']) ? ' &middot; ' . View::e($invoice['email']) : '' ?><br>
    <?= View::e(trim(($invoice['address'] ?? '') . ', ' . ($invoice['city'] ?? '') . ', ' . ($invoice['state'] ?? '') . ' ' . ($invoice['pincode'] ?? ''), ', ')) ?>
  </div>

  <table>
    <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>GST %</th><th class="text-right">Line Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= View::e($item['description']) ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td>Rs. <?= View::money($item['unit_price']) ?></td>
          <td><?= View::e($item['gst_rate']) ?>%</td>
          <td class="text-right">Rs. <?= View::money($item['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="totals" style="width:300px; margin-left:auto;">
    <tr><td class="label">Subtotal</td><td class="text-right">Rs. <?= View::money($invoice['subtotal']) ?></td></tr>
    <tr><td class="label">GST</td><td class="text-right">Rs. <?= View::money($invoice['gst_amount']) ?></td></tr>
    <?php if ((float) $invoice['discount_amount'] > 0): ?>
    <tr><td class="label">Discount</td><td class="text-right">- Rs. <?= View::money($invoice['discount_amount']) ?></td></tr>
    <?php endif; ?>
    <tr class="grand-total"><td class="label">Total</td><td class="text-right">Rs. <?= View::money($invoice['total_amount']) ?></td></tr>
    <tr><td class="label">Paid</td><td class="text-right">Rs. <?= View::money($invoice['amount_paid']) ?></td></tr>
    <tr style="font-weight:bold;"><td class="label">Outstanding</td><td class="text-right">Rs. <?= View::money($outstanding) ?></td></tr>
  </table>

  <?php if ($payments): ?>
  <div style="margin-top:20px;">
    <strong>Payment History</strong>
    <table>
      <thead><tr><th>Date</th><th>Mode</th><th>Reference</th><th class="text-right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= View::dateFormat($p['payment_date']) ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $p['payment_mode'])) ?></td>
            <td><?= View::e($p['reference_number'] ?? '') ?></td>
            <td class="text-right">Rs. <?= View::money($p['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php if (!empty($invoice['notes'])): ?>
  <div style="margin-top:16px;"><strong>Notes:</strong> <?= View::e($invoice['notes']) ?></div>
  <?php endif; ?>

  <div style="margin-top:30px;" class="muted">This is a system-generated invoice.</div>
</body>
</html>
