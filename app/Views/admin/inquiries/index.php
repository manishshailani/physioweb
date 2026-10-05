<?php use App\Core\View; ?>
<h2 class="mb-4">Inquiries</h2>

<div class="card p-3 mb-3">
  <form method="get" action="/admin/inquiries" class="row g-2">
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Contact</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$inquiries): ?><tr><td colspan="7" class="text-center text-muted py-4">No inquiries found.</td></tr><?php endif; ?>
        <?php foreach ($inquiries as $i): ?>
          <tr>
            <td><?= View::e($i['name']) ?></td>
            <td><?= View::e($i['mobile'] ?? '') ?><?php if (!empty($i['email'])): ?><br><?= View::e($i['email']) ?><?php endif; ?></td>
            <td><?= View::e($i['subject'] ?? '') ?></td>
            <td style="max-width:260px;"><?= View::e($i['message']) ?></td>
            <td><?= View::dateFormat($i['created_at'], 'd M Y H:i') ?></td>
            <td>
              <select class="form-select form-select-sm status-select" data-id="<?= (int) $i['id'] ?>" style="width:auto;">
                <?php foreach ($statuses as $s): ?>
                  <option value="<?= $s ?>" <?= $i['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td class="text-end">
              <form method="post" action="/admin/inquiries/<?= (int) $i['id'] ?>/delete" onsubmit="return confirm('Delete this inquiry?');">
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

<form id="statusForm" method="post" style="display:none;">
  <?= $csrf_field ?>
  <input type="hidden" name="status" id="statusFormValue">
</form>
<script>
document.querySelectorAll('.status-select').forEach(function (sel) {
  sel.addEventListener('change', function () {
    var form = document.getElementById('statusForm');
    form.action = '/admin/inquiries/' + sel.dataset.id + '/status';
    document.getElementById('statusFormValue').value = sel.value;
    form.submit();
  });
});
</script>
