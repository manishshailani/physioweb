<?php use App\Core\View; ?>
<h2 class="mb-4">My Exercises</h2>

<h6 class="text-uppercase text-muted small mb-2">Current</h6>
<div class="row g-3 mb-4">
  <?php if (!$currentExercises): ?>
    <div class="col-12"><div class="alert alert-light border text-muted mb-0">No exercises are currently assigned to you.</div></div>
  <?php endif; ?>
  <?php foreach ($currentExercises as $ex): ?>
    <div class="col-md-6">
      <div class="card h-100 p-3">
        <h6 class="mb-1"><?= View::e($ex['exercise_name']) ?></h6>
        <p class="text-muted small mb-2"><?= View::e($ex['category'] ?? '') ?></p>
        <p class="mb-2"><?= nl2br(View::e($ex['instructions'])) ?></p>
        <p class="small mb-1"><strong>Repetitions:</strong> <?= View::e($ex['repetitions'] ?? 'As advised') ?></p>
        <p class="small mb-1"><strong>Frequency:</strong> <?= View::e($ex['frequency'] ?? 'As advised') ?></p>
        <?php if (!empty($ex['notes'])): ?><p class="small text-muted mb-2">Note: <?= View::e($ex['notes']) ?></p><?php endif; ?>
        <?php if (!empty($ex['video_link'])): ?>
          <a href="<?= View::e($ex['video_link']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary align-self-start">
            <i class="fa-solid fa-circle-play me-1"></i>Watch Demonstration
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($pastExercises): ?>
<h6 class="text-uppercase text-muted small mb-2">Past</h6>
<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Exercise</th><th>Started</th><th>Ended</th></tr></thead>
      <tbody>
        <?php foreach ($pastExercises as $ex): ?>
          <tr>
            <td><?= View::e($ex['exercise_name']) ?></td>
            <td><?= View::dateFormat($ex['start_date']) ?></td>
            <td><?= $ex['end_date'] ? View::dateFormat($ex['end_date']) : '&mdash;' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
