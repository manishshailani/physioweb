<?php use App\Core\View; use App\Models\Patient; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Patients</h2>
  <a href="/admin/patients/create" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Patient</a>
</div>

<div class="card p-3 mb-3">
  <form method="get" action="/admin/patients" class="row g-2">
    <div class="col-md-5">
      <input type="text" name="q" class="form-control" placeholder="Search by name, patient ID, or mobile" value="<?= View::e($search) ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-primary w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i> Search</button>
    </div>
    <?php if ($search !== ''): ?>
    <div class="col-md-2">
      <a href="/admin/patients" class="btn btn-outline-secondary w-100">Clear</a>
    </div>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Patient ID</th>
          <th>Name</th>
          <th>Gender</th>
          <th>Age</th>
          <th>Mobile</th>
          <th>Email</th>
          <th>Registered</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$patients): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No patients found.</td></tr>
        <?php endif; ?>
        <?php foreach ($patients as $patient): ?>
          <tr>
            <td><a href="/admin/patients/<?= (int) $patient['id'] ?>" class="fw-semibold text-decoration-none"><?= View::e($patient['patient_code']) ?></a></td>
            <td><?= View::e(Patient::fullName($patient)) ?></td>
            <td class="text-capitalize"><?= View::e($patient['gender']) ?></td>
            <td><?= Patient::age($patient) ?? '&mdash;' ?></td>
            <td><?= View::e($patient['mobile']) ?></td>
            <td><?= View::e($patient['email'] ?? '') ?></td>
            <td><?= View::dateFormat($patient['created_at']) ?></td>
            <td class="text-end">
              <a href="/admin/patients/<?= (int) $patient['id'] ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="fa-solid fa-eye"></i></a>
              <a href="/admin/patients/<?= (int) $patient['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Deactivate this patient? Their records are kept but they will no longer appear in default listings.');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="fa-solid fa-user-slash"></i></button>
              </form>
            </td>
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
        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
<p class="text-muted small text-center"><?= (int) $pagination['total'] ?> patient(s) total.</p>
