<?php
$extra = '<button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#waTestModal"><i class="bi bi-send-check"></i> Test send</button>';
echo tpt_toolbar([
    'new_href'       => null,
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <a class="tab" href="<?= site_url('whatsapp/logs') ?>">Message Logs</a>
  <a class="tab" href="<?= site_url('whatsapp/inbox') ?>">Inbox</a>
  <div class="tab active">Templates</div>
  <div class="spacer"></div>
  <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tplModal" onclick="openTpl()" style="margin-right:10px;"><i class="bi bi-plus-lg"></i> New Template</button>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="alert" style="font-size:.85rem;margin-bottom:0;">
    Templates defined here are local drafts used for <strong>preview and variable mapping</strong>.
    For real WhatsApp sending you must also create and approve the matching template in
    <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank">Meta WA Manager</a> under the same <code>template_name</code>.
  </div>
</div>

<!-- Test send modal — fires a plaintext WA message via WhatsAppService -->
<div class="modal fade" id="waTestModal" tabindex="-1"><div class="modal-dialog">
  <form class="modal-content" method="post" action="<?= site_url('whatsapp/test-send') ?>">
    <?= csrf_field() ?>
    <div class="modal-header"><h6 class="modal-title">Test WhatsApp send</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-2 small text-muted">Sends a plaintext message via the configured Meta WA Cloud API credentials. Useful to verify your access token + phone number ID before relying on templates.</div>
      <div class="mb-2"><label class="form-label">Recipient mobile</label>
        <input class="form-control" name="to" placeholder="10-digit Indian number or full E.164" required></div>
      <div class="mb-2"><label class="form-label">Message</label>
        <textarea class="form-control" name="body" rows="3" required>TPT Aggregator — test WhatsApp send. Reply STOP to opt out.</textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Send</button>
    </div>
  </form>
</div></div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="wa-templates">
      <thead>
        <tr><th data-col="key">Key</th><th data-col="audience">Audience</th><th data-col="meta-name">Meta Name</th><th data-col="lang">Lang</th><th data-col="body">Body</th><th data-col="status">Status</th><th class="text-end" data-col="actions">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="key" data-label="Key"><code><?= esc($r['template_key']) ?></code></td>
            <td data-col="audience" data-label="Audience"><span class="badge-soft"><?= esc($r['audience_type']) ?></span></td>
            <td data-col="meta-name" data-label="Meta Name"><code><?= esc($r['template_name']) ?></code></td>
            <td data-col="lang" data-label="Lang"><?= esc($r['language_code']) ?></td>
            <td data-col="body" data-label="Body" style="max-width:400px;"><pre class="m-0" style="white-space:pre-wrap;font-family:inherit;font-size:.85rem;"><?= esc($r['body_text']) ?></pre></td>
            <td data-col="status" data-label="Status"><?= (int)$r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Disabled</span>' ?></td>
            <td class="text-end" data-col="actions" data-label="Actions">
              <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#tplModal"
                      onclick='openTpl(<?= json_encode($r, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                <i class="bi bi-pencil"></i>
              </button>
              <form class="d-inline" method="post" action="<?= site_url('whatsapp/templates/' . $r['id'] . '/delete') ?>" data-confirm="Delete template?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="tplModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="<?= site_url('whatsapp/templates/save') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="tpl_id">
      <div class="modal-header"><h6 class="modal-title" id="tplTitle">Template</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Template Key</label>
            <input class="form-control" name="template_key" id="tpl_key" required></div>
          <div class="col-md-6"><label class="form-label">Meta Template Name</label>
            <input class="form-control" name="template_name" id="tpl_name" required placeholder="must exactly match approved name in Meta"></div>
          <div class="col-md-4"><label class="form-label">Audience</label>
            <select class="form-select" name="audience_type" id="tpl_aud">
              <option value="client">Client</option><option value="vendor">Vendor</option>
              <option value="internal">Internal</option><option value="driver">Driver</option>
            </select></div>
          <div class="col-md-4"><label class="form-label">Language</label>
            <input class="form-control" name="language_code" id="tpl_lang" value="en"></div>
          <div class="col-md-4">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status" id="tpl_status" value="1" checked>
              <label for="tpl_status">Active</label></div>
          </div>
          <div class="col-12"><label class="form-label">Body <small class="text-muted">use <code>{{1}}</code>, <code>{{2}}</code>… for variables</small></label>
            <textarea class="form-control" name="body_text" id="tpl_body" rows="5" required></textarea></div>
          <div class="col-12"><label class="form-label">Variables (docs, freeform)</label>
            <input class="form-control" name="variables" id="tpl_vars" placeholder="1=ref, 2=route, 3=vehicle, 4=material, 5=weight, 6=loading_date"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Save</button>
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTpl(r) {
  r = r || {};
  document.getElementById('tpl_id').value    = r.id || '';
  document.getElementById('tpl_key').value   = r.template_key || '';
  document.getElementById('tpl_name').value  = r.template_name || '';
  document.getElementById('tpl_aud').value   = r.audience_type || 'vendor';
  document.getElementById('tpl_lang').value  = r.language_code || 'en';
  document.getElementById('tpl_status').checked = r.status === undefined ? true : (parseInt(r.status) === 1);
  document.getElementById('tpl_body').value  = r.body_text || '';
  document.getElementById('tpl_vars').value  = r.variables || '';
  document.getElementById('tplTitle').textContent = r.id ? 'Edit Template' : 'New Template';
}
</script>
