<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card"><div class="card-header">Add Source</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('lead-sources/store') ?>">
          <?= csrf_field() ?>
          <div class="mb-2"><label class="form-label">Name</label>
            <input class="form-control" name="source_name" required></div>
          <div class="mb-2"><label class="form-label">Key</label>
            <input class="form-control" name="source_key" placeholder="lowercase-unique"></div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="s" checked>
            <label class="form-check-label" for="s">Active</label>
          </div>
          <button class="btn btn-primary" type="submit">Add</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0" data-tpt-cols="lead-sources">
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
