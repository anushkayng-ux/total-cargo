<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-life-preserver"></i> Help &amp; documentation</h5>
  <?php if (!empty($isAdmin)): ?>
    <a href="<?= site_url('help/admin') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-pencil-square"></i> Manage topics</a>
  <?php endif; ?>
</div>

<form method="get" class="mb-3"><div class="input-group input-group-sm" style="max-width:420px;">
  <input class="form-control" name="q" placeholder="Search help topics" value="<?= esc($search) ?>">
  <button class="btn btn-light"><i class="bi bi-search"></i></button>
</div></form>

<?php if (empty($byCategory)): ?>
  <div class="card"><div class="card-body text-muted">
    No help topics yet<?= $search !== '' ? ' for "<strong>' . esc($search) . '</strong>"' : '' ?>.
  </div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($byCategory as $cat => $topics): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100"><div class="card-body">
          <h6 class="mb-2"><i class="bi bi-folder2-open"></i> <?= esc($cat) ?></h6>
          <ul class="list-unstyled mb-0">
            <?php foreach ($topics as $t): ?>
              <li class="mb-1"><a href="<?= site_url('help/' . $t['slug']) ?>"><i class="bi bi-file-text"></i> <?= esc($t['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
