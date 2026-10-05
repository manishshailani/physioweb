<?php use App\Core\View; ?>
<h2 class="mb-4">Billing &amp; Invoices</h2>

<div class="card p-3 mb-4 d-inline-block">
  <div class="text-muted small">Outstanding Balance</div>
  <div class="fs-3 fw-bold <?= $outstanding > 0 ? 'text-danger' : 'text-success' ?>">&#8377;<?= number_format($outstanding, 2) ?></div>
</div>

<div class="card mb-4">
  <div class="card-header fw-semibold">Invoices</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$invoices): ?><tr><td colspan="6" class="text-center text-muted py-4">No invoices yet.</td></tr><?php endif; ?>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><?= View::e($inv['invoice_number']) ?></td>
            <td><?= View::dateFormat($inv['invoice_date']) ?></td>
            <td>&#8377;<?= number_format((float) $inv['total_amount'], 2) ?></td>
            <td>&#8377;<?= number_format((float) $inv['amount_paid'], 2) ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst(str_replace('_', ' ', $inv['status']))) ?></span></td>
            <td class="text-end">
              <a href="/patient/billing/invoices/<?= (int) $inv['id'] ?>/pdf" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($packages): ?>
<div class="card">
  <div class="card-header fw-semibold">My Packages</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Package</th><th>Purchased</th><th>Expires</th><th>Sessions Used</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($packages as $p): ?>
          <tr>
            <td><?= View::e($p['package_name']) ?></td>
            <td><?= View::dateFormat($p['purchased_date']) ?></td>
            <td><?= View::dateFormat($p['expiry_date']) ?></td>
            <td><?= (int) $p['sessions_used'] ?> / <?= (int) $p['total_sessions'] ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($p['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
