<?= tpt_toolbar([
    'new_href'       => site_url('help/admin/create'),
    'new_item_label' => 'New Help Topic',
    'close_href'     => site_url('help'),
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">All Help Topics</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="help-admin">
      <thead><tr>
        <th data-col="category">Category</th><th data-col="title">Title</th><th data-col="slug">Slug</th><th data-col="roles">Roles</th>
        <th data-col="sort">Sort</th><th data-col="status">Status</th><th class="text-end" data-col="actions"></th>
      </tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No topics yet. <a href="<?= site_url('help/admin/create') ?>">Create one →</a></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="row-link" data-href="<?= site_url('help/' . $r['id'] . '/edit') ?>">
          <td data-col="category" data-label="Category"><span class="badge-soft"><?= esc($r['category']) ?></span></td>
          <td data-col="title" data-label="Title"><strong><?= esc($r['title']) ?></strong></td>
          <td data-col="slug" data-label="Slug"><code class="small"><?= esc($r['slug']) ?></code></td>
          <td data-col="roles" data-label="Roles" class="small text-muted"><?= esc($r['applicable_roles']) ?></td>
          <td data-col="sort" data-label="Sort"><?= (int) $r['sort_order'] ?></td>
          <td data-col="status" data-label="Status">
            <?php if ((int) $r['is_published'] === 1): ?>
              <span class="badge-soft badge-ok">Published</span>
            <?php else: ?>
              <span class="badge-soft">Draft</span>
            <?php endif; ?>
          </td>
          <td class="text-end" data-col="actions">
            <a class="btn btn-sm btn-light" href="<?= site_url('help/' . $r['slug']) ?>" target="_blank" title="View"><i class="bi bi-eye"></i></a>
            <a class="btn btn-sm btn-light" href="<?= site_url('help/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" action="<?= site_url('help/' . $r['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete topic?">
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
