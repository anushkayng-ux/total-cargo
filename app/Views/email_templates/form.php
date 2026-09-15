<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('email-templates/' . $row['id']) : site_url('email-templates/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
$extra = $isEdit ? '<a class="btn btn-sm btn-outline-dark" target="_blank" href="' . site_url('email-templates/' . $row['id'] . '/preview') . '"><i class="bi bi-eye"></i> Preview</a>' : '';
?>
<?= tpt_toolbar([
    'save_form'      => 'emailTplForm',
    'delete_href'    => $isEdit ? site_url('email-templates/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete template?',
    'close_href'     => site_url('email-templates'),
    'extra'          => $extra,
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Template Details</div>
  <div class="spacer"></div>
</div>

<form id="emailTplForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:36%;"><label>Template Key <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" name="template_key" required value="<?= esc($v('template_key')) ?>" placeholder="e.g. invoice_share">
      </div>
      <div class="retro-field"><label>Audience :</label>
        <select class="retro-box wide" name="audience_type">
          <?php foreach (['client','vendor','internal','driver'] as $a): ?>
            <option value="<?= $a ?>" <?= $v('audience_type', 'client') === $a ? 'selected' : '' ?>><?= ucfirst($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-8px;margin-bottom:10px;">Lowercase, snake_case. Used in code to reference this template.</div>

    <div class="retro-row">
      <div class="retro-field" style="width:100%;"><label>Variables (JSON sample) :</label>
        <input class="retro-box xwide" style="width:100%;" name="variables_json" value="<?= esc($v('variables_json')) ?>" placeholder='["INV001","Acme Corp"]'>
      </div>
    </div>

    <div class="retro-row">
      <div class="retro-field" style="width:100%;"><label>Subject <span class="retro-required">*</span> :</label>
        <input class="retro-box xwide" style="width:100%;" name="subject" required value="<?= esc($v('subject')) ?>" placeholder="Invoice {{1}} from your transporter">
      </div>
    </div>

    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">HTML Body <span class="retro-required">*</span> :</label>
        <textarea class="retro-box font-monospace" style="width:100%;" name="body_html" rows="14" required><?= esc($v('body_html')) ?></textarea>
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-6px;">Use <code>{{1}}, {{2}}…</code> or named <code>{{client.name}}</code> placeholders. The branded layout (header/footer/unsubscribe) is added automatically.</div>

    <div class="retro-row" style="align-items:flex-start;margin-top:10px;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Plain-text Body :</label>
        <textarea class="retro-box font-monospace" style="width:100%;" name="body_text" rows="5"><?= esc($v('body_text')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('email-templates') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
