<?php use App\Core\View; ?>
<h2 class="mb-4">Audit Logs</h2>
<p class="text-muted small">A read-only, append-only trail of who did what, when. This screen never lets you edit or delete a log entry.</p>

<div class="card p-3 mb-3">
  <form method="get" action="/admin/audit-logs" class="row g-2">
    <div class="col-md-2">
      <select name="entity_type" class="form-select">
        <option value="">All Entities</option>
        <?php foreach ($entityTypes as $et): ?>
          <option value="<?= View::e($et) ?>" <?= $filters['entity_type'] === $et ? 'selected' : '' ?>><?= View::e(ucwords(str_replace('_', ' ', $et))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="action" class="form-select">
        <option value="">All Actions</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= View::e($a) ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= View::e(ucfirst($a)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="user_type" class="form-select">
        <option value="">All User Types</option>
        <?php foreach (['admin' => 'Admin/Staff', 'patient' => 'Patient', 'system' => 'System'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= $filters['user_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="date_from" class="form-control" value="<?= View::e($filters['date_from']) ?>" placeholder="From">
    </div>
    <div class="col-md-2">
      <input type="date" name="date_to" class="form-control" value="<?= View::e($filters['date_to']) ?>" placeholder="To">
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-outline-primary flex-fill"><i class="fa-solid fa-filter me-1"></i>Filter</button>
      <a href="/admin/audit-logs" class="btn btn-outline-secondary">Clear</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Date/Time</th>
          <th>Actor</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Description</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$logs): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No audit log entries match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td class="text-nowrap"><?= View::dateFormat($log['created_at'], 'd M Y H:i:s') ?></td>
            <td>
              <?php if ($log['user_type'] === 'admin' && !empty($log['admin_name'])): ?>
                <?= View::e($log['admin_name']) ?>
              <?php else: ?>
                <span class="badge bg-light text-dark border"><?= View::e(ucfirst($log['user_type'])) ?></span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-secondary"><?= View::e($log['action']) ?></span></td>
            <td><?= View::e(ucwords(str_replace('_', ' ', $log['entity_type']))) ?><?= $log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '' ?></td>
            <td><?= View::e($log['description'] ?? '') ?></td>
            <td class="text-muted small"><?= View::e($log['ip_address'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
  $qs = http_build_query(array_filter($filters));
  $qs = $qs !== '' ? '&' . $qs : '';
?>
<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i . $qs ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
<p class="text-muted small text-center"><?= (int) $pagination['total'] ?> log entr<?= $pagination['total'] === 1 ? 'y' : 'ies' ?> total.</p>
