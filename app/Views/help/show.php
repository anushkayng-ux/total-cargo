<?= tpt_toolbar([
    'edit_href'  => !empty($isAdmin) ? site_url('help/' . $row['id'] . '/edit') : null,
    'close_href' => site_url('help'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active"><?= esc($row['title']) ?></div>
  <div class="spacer"></div>
  <div class="recordnav"><span class="badge-soft"><?= esc($row['category']) ?></span></div>
</div>

<div class="formwrap">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="help-body"><?= $rendered ?></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-body">
        <h6 class="mb-2"><i class="bi bi-folder2"></i> Related in <?= esc($row['category']) ?></h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ($siblings as $s): ?>
            <li class="mb-1">
              <?php if ((int) $s['id'] === (int) $row['id']): ?>
                <strong><?= esc($s['title']) ?></strong>
              <?php else: ?>
                <a href="<?= site_url('help/' . $s['slug']) ?>"><?= esc($s['title']) ?></a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div></div>
      <div class="card mt-3"><div class="card-body">
        <h6 class="mb-2">Need more help?</h6>
        <p class="small mb-2 text-muted">Can't find what you need or spotted a bug? Raise a support ticket and our team will respond.</p>
        <a class="btn btn-sm btn-primary" href="<?= site_url('support/create') ?>"><i class="bi bi-plus-lg"></i> Raise ticket</a>
      </div></div>
    </div>
  </div>
</div>

<style>
.help-body h1, .help-body h2, .help-body h3, .help-body h4 { margin-top:1rem; }
.help-body h1 { font-size:1.6rem; }
.help-body h2 { font-size:1.35rem; }
.help-body h3 { font-size:1.15rem; }
.help-body h4 { font-size:1rem; font-weight:600; }
.help-body pre { background:#f6f8fa; padding:.75rem; border-radius:.4rem; overflow:auto; }
.help-body code { background:#f6f8fa; padding:.1rem .3rem; border-radius:.25rem; }
.help-body pre code { background:none; padding:0; }
.help-body blockquote { border-left:3px solid #c5cad1; padding:.25rem .75rem; color:#555; background:#fafbfc; margin:.5rem 0; }
.help-body ul, .help-body ol { padding-left:1.25rem; }
</style>
