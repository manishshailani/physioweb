<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Invoices</h2>
  <a href="/admin/invoices/create" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Invoice</a>
</div>

<div class="card p-3 mb-3">
  <form method="get" action="/admin/invoices" class="row g-2">
    <div class="col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Search invoice #, patient name or ID" value="<?= View::e($filters['q']) ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
    </div>
    <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?>
    <div class="col-md-2"><a href="/admin/invoices" class="btn btn-outline-secondary w-100">Clear</a></div>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Invoice #</th><th>Date</th><th>Patient</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$invoices): ?><tr><td colspan="8" class="text-center text-muted py-4">No invoices found.</td></tr><?php endif; ?>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><a href="/admin/invoices/<?= (int) $inv['id'] ?>" class="text-decoration-none fw-semibold"><?= View::e($inv['invoice_number']) ?></a></td>
            <td><?= View::dateFormat($inv['invoice_date']) ?></td>
            <td><?= View::e($inv['patient_name']) ?> <span class="text-muted small">(<?= View::e($inv['patient_code']) ?>)</span></td>
            <td>&#8377;<?= View::money($inv['total_amount']) ?></td>
            <td>&#8377;<?= View::money($inv['amount_paid']) ?></td>
            <td>&#8377;<?= View::money((float) $inv['total_amount'] - (float) $inv['amount_paid']) ?></td>
            <td><span class="badge badge-invoice-<?= View::e($inv['status']) ?>"><?= View::e($inv['status']) ?></span></td>
            <td class="text-end"><a href="/admin/invoices/<?= (int) $inv['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filters['status']) ?>&q=<?= urlencode($filters['q']) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
