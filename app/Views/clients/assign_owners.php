<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-people-fill"></i> Assign Client Owners</h5>
  <small class="text-muted">Only the assigned owner (plus admins) will see each client</small>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('clients') ?>"><i class="bi bi-arrow-left"></i> Back to Clients</a>
</div>

<form method="get" action="<?= site_url('clients/assign-owners') ?>" class="card mb-3">
  <div class="card-body row g-2 align-items-end">
    <div class="col-md-5">
      <label class="form-label">Search</label>
      <input class="form-control form-control-sm" name="q" value="<?= esc($q) ?>" placeholder="Company / city / mobile / GSTIN">
    </div>
    <div class="col-md-4">
      <label class="form-label">Filter by current owner</label>
      <select class="form-select form-select-sm" name="mgr">
        <option value="">— All —</option>
        <option value="unassigned" <?= $filter === 'unassigned' ? 'selected' : '' ?>>— Unassigned only —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>" <?= (string) $filter === (string) $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Apply</button>
    </div>
  </div>
</form>

<form method="post" action="<?= site_url('clients/assign-owners') ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
      <span class="text-muted" style="font-size:.85rem;">Tick clients then pick an owner:</span>
      <select class="form-select form-select-sm" name="assign_to" style="max-width:280px;" required>
        <option value="">— Owner —</option>
        <option value="0">— Unassign —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Reassign selected</button>
      <small class="ms-auto text-muted"><?= count($rows) ?> shown</small>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="aoAll" title="Select all shown"></th>
            <th>Client</th>
            <th>City · State</th>
            <th>Current Owner</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="ao-row"></td>
              <td>
                <a href="<?= site_url('clients/' . $r['id'] . '/edit') ?>" target="_blank" style="text-decoration:none;">
                  <strong><?= esc($r['company_name']) ?></strong>
                </a>
                <?php if (!empty($r['gst_no'])): ?><br><small class="text-muted">GST <?= esc($r['gst_no']) ?></small><?php endif; ?>
              </td>
              <td><?= esc(trim(($r['city'] ?? '') . ($r['state'] ? ' · ' . $r['state'] : ''))) ?></td>
              <td>
                <?php if (!empty($r['manager_name'])): ?>
                  <span class="badge-soft badge-ok"><?= esc($r['manager_name']) ?></span>
                <?php else: ?>
                  <span class="badge-soft badge-warn">— unassigned —</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pager)): ?>
      <div class="card-body"><?= $pager->links() ?></div>
    <?php endif; ?>
  </div>
</form>

<script>
document.getElementById('aoAll').addEventListener('change', function () {
  var v = this.checked;
  document.querySelectorAll('.ao-row').forEach(function (cb) { cb.checked = v; });
});
</script>
