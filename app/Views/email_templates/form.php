<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('email-templates/' . $row['id']) : site_url('email-templates/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Template key *</label>
          <input class="form-control" name="template_key" required value="<?= esc($v('template_key')) ?>" placeholder="e.g. invoice_share">
          <div class="form-text" style="font-size:.78rem;">Lowercase, snake_case. Used in code to reference this template.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Audience</label>
          <select class="form-select" name="audience_type">
            <?php foreach (['client','vendor','internal','driver'] as $a): ?>
              <option value="<?= $a ?>" <?= $v('audience_type', 'client') === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">Status</label>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?> id="s"><label class="form-check-label" for="s">Active</label></div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Variables (JSON sample)</label>
          <input class="form-control" name="variables_json" value="<?= esc($v('variables_json')) ?>" placeholder='["INV001","Acme Corp"]'>
        </div>

        <div class="col-12">
          <label class="form-label">Subject *</label>
          <input class="form-control" name="subject" required value="<?= esc($v('subject')) ?>" placeholder="Invoice {{1}} from your transporter">
        </div>
        <div class="col-12">
          <label class="form-label">HTML body *</label>
          <textarea class="form-control font-monospace" name="body_html" rows="14" required><?= esc($v('body_html')) ?></textarea>
          <div class="form-text" style="font-size:.78rem;">Use <code>{{1}}, {{2}}…</code> or named <code>{{client.name}}</code> placeholders. The branded layout (header/footer/unsubscribe) is added automatically.</div>
        </div>
        <div class="col-12">
          <label class="form-label">Plain-text body (optional fallback)</label>
          <textarea class="form-control font-monospace" name="body_text" rows="5"><?= esc($v('body_text')) ?></textarea>
        </div>
      </div>

      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Save</button>
        <a class="btn btn-light" href="<?= site_url('email-templates') ?>">Cancel</a>
        <?php if ($isEdit): ?>
          <a class="btn btn-light ms-auto" target="_blank" href="<?= site_url('email-templates/' . $row['id'] . '/preview') ?>"><i class="bi bi-eye"></i> Preview</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
