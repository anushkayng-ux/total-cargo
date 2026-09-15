<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-<?= esc($icon) ?>"></i> <?= esc($heading) ?></h5>
  <div class="text-muted" style="font-size:.85rem;"><?= esc($subtitle) ?></div>
</div>

<div class="masterlinks">
  <?php if (empty($cards)): ?>
    <div class="text-muted">No modules available for your role.</div>
  <?php endif; ?>
  <?php foreach ($cards as $c): ?>
    <a class="mastercard" href="<?= site_url($c['url']) ?>">
      <div class="mastercard-icon"><i class="bi bi-<?= esc($c['icon']) ?>"></i></div>
      <div class="mastercard-label"><?= esc($c['label']) ?></div>
    </a>
  <?php endforeach; ?>
</div>
