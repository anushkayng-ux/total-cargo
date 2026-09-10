<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('help/' . $row['id'] . '/update') : site_url('help/admin/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Title</label>
      <input class="form-control" name="title" required maxlength="200" value="<?= esc($v('title')) ?>">
    </div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <input class="form-control" name="category" required maxlength="60" value="<?= esc($v('category', 'Getting Started')) ?>" list="cats">
      <datalist id="cats">
        <option>Getting Started</option><option>Master Data</option><option>CRM</option>
        <option>Operations</option><option>Finance</option><option>Communication</option>
        <option>Compliance</option><option>Client Portal</option><option>Reports</option>
        <option>Admin</option><option>Super Admin</option>
      </datalist>
    </div>
    <div class="col-md-3"><label class="form-label">Sort order</label>
      <input type="number" class="form-control" name="sort_order" value="<?= esc($v('sort_order', 100)) ?>">
    </div>

    <div class="col-md-6"><label class="form-label">Slug <small class="text-muted">(auto if blank)</small></label>
      <input class="form-control" name="slug" value="<?= esc($v('slug')) ?>">
    </div>
    <div class="col-md-4"><label class="form-label">Applicable roles <small class="text-muted">(CSV or "all")</small></label>
      <input class="form-control" name="applicable_roles" value="<?= esc($v('applicable_roles', 'all')) ?>"
             placeholder="all  or  admin,owner,booker">
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <div class="form-check mt-2">
        <input type="checkbox" class="form-check-input" name="is_published" id="pub" value="1" <?= (int) $v('is_published', 1) === 1 ? 'checked' : '' ?>>
        <label for="pub" class="form-check-label">Published</label>
      </div>
    </div>

    <div class="col-12"><label class="form-label">Body (Markdown)</label>
      <textarea class="form-control" name="body_md" rows="18" required style="font-family:ui-monospace,monospace;"><?= esc($v('body_md')) ?></textarea>
      <div class="form-text">Supports # H1–H4, **bold**, *italic*, `code`, ```fenced```, - / 1. lists, &gt; quote, [text](url), --- rule.</div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
    <a class="btn btn-light" href="<?= site_url('help/admin') ?>">Cancel</a>
  </div>
</form>
</div></div>
