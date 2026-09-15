<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Lead Sources</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> total records</div>
</div>

<div class="formwrap">
  <div class="row g-3">
    <div class="col-md-4">
      <h6 class="mb-2"><i class="bi bi-plus-circle"></i> Add Source</h6>
      <form method="post" action="<?= site_url('lead-sources/store') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Name :</label><input class="retro-box" style="width:100%;" name="source_name" required></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Key :</label><input class="retro-box" style="width:100%;" name="source_key" placeholder="lowercase-unique"></div>
        </div>
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="status" value="1" checked> Active</label>
        </div>
        <button class="btn btn-primary btn-sm mt-1" type="submit">Add</button>
      </form>
    </div>
    <div class="col-md-8">
      <div class="gridwrap" style="padding:0;">
        <table class="table grid mb-0" data-tpt-cols="lead-sources">
          <thead><tr><th data-col="name">Name</th><th data-col="key">Key</th><th data-col="status">Status</th><th class="text-end" data-col="actions">Actions</th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td data-col="name" data-label="Name"><?= esc($r['source_name']) ?></td>
                <td data-col="key" data-label="Key"><code><?= esc($r['source_key']) ?></code></td>
                <td data-col="status" data-label="Status"><?= (int)$r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Inactive</span>' ?></td>
                <td class="text-end" data-col="actions" data-label="Actions">
                  <form class="d-inline" method="post" action="<?= site_url('lead-sources/' . $r['id'] . '/delete') ?>" data-confirm="Delete?">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
