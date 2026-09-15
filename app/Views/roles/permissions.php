<?php
$grouped = [];
foreach ($permissions as $p) {
    $grouped[$p['module_key']][] = $p;
}
?>
<?= tpt_toolbar([
    'save_form'   => 'permissionsForm',
    'close_href'  => site_url('roles'),
    'auth'        => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Permissions</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('roles') ?>"><i class="bi bi-list"></i> List</a> &middot;
    <?= esc($role['role_name']) ?>
  </div>
</div>

<form id="permissionsForm" method="post" action="<?= site_url('roles/' . $role['id'] . '/permissions') ?>">
  <?= csrf_field() ?>
  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mb-0">
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
</form>

<div class="retro-toolbar mt-3" style="position:static;">
  <button type="submit" form="permissionsForm" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Permissions</button>
  <a class="retro-tbtn" href="<?= site_url('roles') ?>"><i class="bi bi-x-circle"></i>Close</a>
</div>
