<?php
$isEdit = !empty($role);
$action = $isEdit ? site_url('roles/' . $role['id']) : site_url('roles/store');
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Role Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="role_name" required
                 value="<?= esc(old('role_name', $role['role_name'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Role Key <small class="text-muted">(unique, lowercase)</small></label>
          <input type="text" class="form-control" name="role_key"
                 value="<?= esc(old('role_key', $role['role_key'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label d-block">Status</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                   <?= (int)($role['status'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="statusSwitch">Active</label>
          </div>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn btn-light" href="<?= site_url('roles') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
