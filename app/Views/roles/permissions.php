<?php
$grouped = [];
foreach ($permissions as $p) {
    $grouped[$p['module_key']][] = $p;
}
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Permissions · <?= esc($role['role_name']) ?></h5>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('roles') ?>"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="post" action="<?= site_url('roles/' . $role['id'] . '/permissions') ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="min-width:220px;">Module</th>
            <th class="text-center">View</th>
            <th class="text-center">Add</th>
            <th class="text-center">Edit</th>
            <th class="text-center">Delete</th>
            <th class="text-center">Approve</th>
            <th class="text-center">Export</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($permissions as $p):
              $pid = (int)$p['id'];
              $c   = $map[$pid] ?? [];
          ?>
          <tr>
            <td data-label="Module"><?= esc($p['label']) ?><br><code class="text-muted" style="font-size:.72rem;"><?= esc($p['module_key']) ?></code></td>
            <?php foreach (['can_view','can_add','can_edit','can_delete','can_approve','can_export'] as $flag): ?>
              <td class="text-center" data-label="<?= ucfirst(str_replace('can_','', $flag)) ?>">
                <input type="checkbox" class="form-check-input"
                       name="p[<?= $pid ?>][<?= $flag ?>]" value="1"
                       <?= !empty($c[$flag]) ? 'checked' : '' ?>>
              </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save Permissions</button>
    <a class="btn btn-light" href="<?= site_url('roles') ?>">Cancel</a>
  </div>
</form>
