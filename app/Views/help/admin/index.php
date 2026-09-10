<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Manage Help Topics</h5>
  <a href="<?= site_url('help') ?>" class="btn btn-sm btn-light">View Help</a>
  <a href="<?= site_url('help/admin/create') ?>" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-plus-lg"></i> New topic</a>
</div>

<div class="card"><div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead><tr>
      <th>Category</th><th>Title</th><th>Slug</th><th>Roles</th>
      <th>Sort</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">No topics yet. <a href="<?= site_url('help/admin/create') ?>">Create one →</a></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><span class="badge-soft"><?= esc($r['category']) ?></span></td>
        <td><a href="<?= site_url('help/' . $r['slug']) ?>"><?= esc($r['title']) ?></a></td>
        <td><code class="small"><?= esc($r['slug']) ?></code></td>
        <td class="small text-muted"><?= esc($r['applicable_roles']) ?></td>
        <td><?= (int) $r['sort_order'] ?></td>
        <td>
          <?php if ((int) $r['is_published'] === 1): ?>
            <span class="badge-soft badge-ok">Published</span>
          <?php else: ?>
            <span class="badge-soft">Draft</span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <a class="btn btn-sm btn-light" href="<?= site_url('help/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="<?= site_url('help/' . $r['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete topic?');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div></div>
