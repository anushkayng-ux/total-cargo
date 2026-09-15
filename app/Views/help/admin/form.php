<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('help/' . $row['id'] . '/update') : site_url('help/admin/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'   => 'helpTopicForm',
    'close_href'  => site_url('help/admin'),
    'auth'        => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Topic Details</div>
  <div class="spacer"></div>
</div>

<form id="helpTopicForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:60%;"><label>Title <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" name="title" required maxlength="200" value="<?= esc($v('title')) ?>">
      </div>
      <div class="retro-field" style="width:36%;margin-left:auto;"><label>Category <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" name="category" required maxlength="60" value="<?= esc($v('category', 'Getting Started')) ?>" list="cats">
        <datalist id="cats">
          <option>Getting Started</option><option>Master Data</option><option>CRM</option>
          <option>Operations</option><option>Finance</option><option>Communication</option>
          <option>Compliance</option><option>Client Portal</option><option>Reports</option>
          <option>Admin</option><option>Super Admin</option>
        </datalist>
      </div>
    </div>

    <div class="retro-row">
      <div class="retro-field"><label>Slug <small style="font-weight:normal;color:var(--v2-fg-muted);">(auto if blank)</small> :</label>
        <input class="retro-box wide" name="slug" value="<?= esc($v('slug')) ?>">
      </div>
      <div class="retro-field"><label>Sort Order :</label><input type="number" class="retro-box narrow" name="sort_order" value="<?= esc($v('sort_order', 100)) ?>"></div>
      <div class="retro-field"><label>Applicable Roles :</label>
        <input class="retro-box wide" name="applicable_roles" value="<?= esc($v('applicable_roles', 'all')) ?>" placeholder="all  or  admin,owner,booker">
      </div>
      <label class="retro-checkline"><input type="checkbox" name="is_published" id="pub" value="1" <?= (int) $v('is_published', 1) === 1 ? 'checked' : '' ?>> Published</label>
    </div>

    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Body (Markdown) <span class="retro-required">*</span> :</label>
        <textarea class="retro-box retro-particulars" style="width:100%;font-family:ui-monospace,monospace;" name="body_md" rows="18" required><?= esc($v('body_md')) ?></textarea>
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-8px;">Supports # H1–H4, **bold**, *italic*, `code`, ```fenced```, - / 1. lists, &gt; quote, [text](url), --- rule.</div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('help/admin') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
