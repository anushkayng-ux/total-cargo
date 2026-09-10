<h5 class="mb-3"><i class="bi bi-sliders"></i> Dashboard widgets</h5>
<p class="text-muted small">Pick the cards you want to see when you open the dashboard. Settings are per-user — won't affect anyone else.</p>

<div class="card"><div class="card-body">
<form method="post" action="<?= site_url('dashboard/widgets') ?>">
  <?= csrf_field() ?>
  <?php foreach ($catalog as $key => $label): ?>
    <div class="form-check form-switch mb-2">
      <input class="form-check-input" type="checkbox" name="widgets[]" value="<?= esc($key) ?>"
             id="w_<?= esc($key) ?>" <?= !empty($widgetsOn[$key]) ? 'checked' : '' ?>>
      <label class="form-check-label" for="w_<?= esc($key) ?>"><?= esc($label) ?></label>
    </div>
  <?php endforeach; ?>
  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
    <a class="btn btn-light" href="<?= site_url('dashboard') ?>">Back</a>
  </div>
</form>
</div></div>
