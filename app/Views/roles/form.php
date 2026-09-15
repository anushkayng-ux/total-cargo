<?php
$isEdit = !empty($role);
$action = $isEdit ? site_url('roles/' . $role['id']) : site_url('roles/store');
?>
<?= tpt_toolbar([
    'save_form'   => 'roleForm',
    'close_href'  => site_url('roles'),
    'auth'        => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Role Details</div>
  <div class="spacer"></div>
</div>

<form id="roleForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:48%;"><label>Role Name <span class="retro-required">*</span> :</label>
        <input type="text" class="retro-box" style="width:100%;" name="role_name" required value="<?= esc(old('role_name', $role['role_name'] ?? '')) ?>">
      </div>
      <div class="retro-field" style="width:48%;margin-left:auto;"><label>Role Key :</label>
        <input type="text" class="retro-box" style="width:100%;" name="role_key" value="<?= esc(old('role_key', $role['role_key'] ?? '')) ?>">
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-8px;">Unique, lowercase.</div>
    <div class="retro-row" style="margin-top:10px;">
      <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int)($role['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
    </div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('roles') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
