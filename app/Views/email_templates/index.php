<?= tpt_toolbar([
    'new_href'       => site_url('email-templates/create'),
    'new_item_label' => 'New Email Template',
    'close_href'     => site_url('dashboard'),
    'auth'           => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">All Email Templates</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="email-templates">
      <thead><tr><th data-col="key">Key</th><th data-col="audience">Audience</th><th data-col="subject">Subject</th><th data-col="status">Status</th><th class="text-end" data-col="actions">Actions</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No templates yet. <a href="<?= site_url('email-templates/create') ?>">Create one →</a></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="row-link" data-href="<?= site_url('email-templates/' . $r['id'] . '/edit') ?>">
          <td data-col="key"><code><?= esc($r['template_key']) ?></code></td>
          <td data-col="audience"><?= esc($r['audience_type']) ?></td>
          <td data-col="subject"><?= esc($r['subject']) ?></td>
          <td data-col="status"><?= (int) $r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Disabled</span>' ?></td>
          <td class="text-end" data-col="actions">
            <a class="btn btn-sm btn-light" target="_blank" href="<?= site_url('email-templates/' . $r['id'] . '/preview') ?>"><i class="bi bi-eye"></i> Preview</a>
            <a class="btn btn-sm btn-light" href="<?= site_url('email-templates/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
            <form class="d-inline" method="post" action="<?= site_url('email-templates/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('Delete template?');">
              <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
