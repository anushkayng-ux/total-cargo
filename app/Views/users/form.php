<?php
$isEdit = !empty($user);
$action = $isEdit ? site_url('users/' . $user['id']) : site_url('users/store');
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="name" required
                 value="<?= esc(old('name', $user['name'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" class="form-control" name="email" required
                 value="<?= esc(old('email', $user['email'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Mobile</label>
          <input type="text" class="form-control" name="mobile"
                 value="<?= esc(old('mobile', $user['mobile'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select class="form-select" name="role_id" required>
            <option value="">— Select —</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= (int)old('role_id', $user['role_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                <?= esc($r['role_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep)</small>' : '<span class="text-danger">*</span>' ?></label>
          <input type="password" class="form-control" name="password" <?= $isEdit ? '' : 'required minlength="6"' ?>>
        </div>
        <div class="col-md-6">
          <label class="form-label d-block">Status</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                   <?= (int)($user['status'] ?? 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="statusSwitch">Active</label>
          </div>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save</button>
        <a class="btn btn-light" href="<?= site_url('users') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
