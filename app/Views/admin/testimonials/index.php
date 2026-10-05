<?php use App\Core\View; ?>
<h2 class="mb-4">Testimonials</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add Testimonial</h6>
  <form method="post" action="/admin/testimonials" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Patient Name <span class="text-danger">*</span></label>
      <input type="text" name="patient_name" class="form-control <?= isset($flash_errors['patient_name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['patient_name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['patient_name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['patient_name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Rating <span class="text-danger">*</span></label>
      <select name="rating" class="form-select <?= isset($flash_errors['rating']) ? 'is-invalid' : '' ?>" required>
        <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?>
      </select>
      <?php if (isset($flash_errors['rating'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['rating'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Testimonial <span class="text-danger">*</span></label>
      <textarea name="content" class="form-control <?= isset($flash_errors['content']) ? 'is-invalid' : '' ?>" rows="1" required><?= View::e($old['content'] ?? '') ?></textarea>
      <?php if (isset($flash_errors['content'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['content'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add &amp; Publish</button>
    </div>
  </form>
</div>

<h5 class="mt-4">Pending Approval (<?= count($pending) ?>)</h5>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Name</th><th>Rating</th><th>Content</th><th>Submitted</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$pending): ?><tr><td colspan="5" class="text-center text-muted py-4">Nothing pending.</td></tr><?php endif; ?>
        <?php foreach ($pending as $t): ?>
          <tr>
            <td><?= View::e($t['patient_name']) ?></td>
            <td><?= str_repeat('&#9733;', (int) $t['rating']) ?></td>
            <td><?= View::e($t['content']) ?></td>
            <td><?= View::dateFormat($t['created_at']) ?></td>
            <td class="text-end">
              <form method="post" action="/admin/testimonials/<?= (int) $t['id'] ?>/approve" class="d-inline">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-check"></i></button>
              </form>
              <form method="post" action="/admin/testimonials/<?= (int) $t['id'] ?>/reject" class="d-inline">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<h5>Approved &amp; Published (<?= count($approved) ?>)</h5>
<div class="card mb-4">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Name</th><th>Rating</th><th>Content</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$approved): ?><tr><td colspan="4" class="text-center text-muted py-4">None published yet.</td></tr><?php endif; ?>
        <?php foreach ($approved as $t): ?>
          <tr>
            <td><?= View::e($t['patient_name']) ?></td>
            <td><?= str_repeat('&#9733;', (int) $t['rating']) ?></td>
            <td><?= View::e($t['content']) ?></td>
            <td class="text-end">
              <form method="post" action="/admin/testimonials/<?= (int) $t['id'] ?>/delete" onsubmit="return confirm('Delete this testimonial?');">
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

<?php if ($rejected): ?>
<h5>Rejected (<?= count($rejected) ?>)</h5>
<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Name</th><th>Content</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rejected as $t): ?>
          <tr>
            <td><?= View::e($t['patient_name']) ?></td>
            <td><?= View::e($t['content']) ?></td>
            <td class="text-end">
              <form method="post" action="/admin/testimonials/<?= (int) $t['id'] ?>/delete" onsubmit="return confirm('Delete this testimonial?');">
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
<?php endif; ?>
