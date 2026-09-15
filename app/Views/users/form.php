<?php
$isEdit = !empty($user);
$action = $isEdit ? site_url('users/' . $user['id']) : site_url('users/store');
?>
<?= tpt_toolbar([
    'save_form'      => 'userForm',
    'delete_href'    => ($isEdit && (int) $user['id'] !== (int) ($currentUser['id'] ?? 0)) ? site_url('users/' . $user['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this user?',
    'close_href'     => site_url('users'),
    'auth'           => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">User Details</div>
  <div class="spacer"></div>
</div>

<form id="userForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:48%;"><label>Full Name <span class="retro-required">*</span> :</label>
        <input type="text" class="retro-box" style="width:100%;" name="name" required value="<?= esc(old('name', $user['name'] ?? '')) ?>">
      </div>
      <div class="retro-field" style="width:48%;margin-left:auto;"><label>Email <span class="retro-required">*</span> :</label>
        <input type="email" class="retro-box" style="width:100%;" name="email" required value="<?= esc(old('email', $user['email'] ?? '')) ?>">
      </div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Mobile :</label><input type="text" class="retro-box wide" name="mobile" value="<?= esc(old('mobile', $user['mobile'] ?? '')) ?>"></div>
      <div class="retro-field" style="width:100%;"><label>Role <span class="retro-required">*</span> :</label>
        <select class="retro-box xwide" name="role_id" required>
          <option value="">— Select —</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>" <?= (int)old('role_id', $user['role_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
              <?= esc($r['role_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep)</small>' : '<span class="retro-required">*</span>' ?> :</label>
        <input type="password" class="retro-box wide" name="password" <?= $isEdit ? '' : 'required minlength="6"' ?>>
      </div>
      <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int)($user['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
    </div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('users') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
