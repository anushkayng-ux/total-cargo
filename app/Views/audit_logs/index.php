<?php
$totalPages = (int) ceil($total / $perPage);
$actionCls = fn(string $a) => match (strtolower($a)) {
    'create'   => 'badge-soft badge-ok',
    'update'   => 'badge-soft',
    'delete'   => 'badge-soft badge-danger',
    'approve'  => 'badge-soft badge-ok',
    'status'   => 'badge-soft badge-warn',
    'login'    => 'badge-soft',
    'dispatch' => 'badge-soft badge-warn',
    default    => 'badge-soft',
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <span class="text-muted ms-2" style="font-size:.85rem;">Total <?= number_format($total) ?> rows</span>
</div>

<div class="card mb-3"><div class="card-body">
  <form class="row g-2" method="get" action="<?= site_url('audit-logs') ?>">
    <div class="col-md-3"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search module/action/description" value="<?= esc($search) ?>"></div>
    <div class="col-md-2">
      <select name="module" class="form-select form-select-sm">
        <option value="">All modules</option>
        <?php foreach ($modules as $m): ?>
          <option value="<?= esc($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= esc($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="action" class="form-select form-select-sm">
        <option value="">All actions</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= esc($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= esc($a) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="user" class="form-select form-select-sm">
        <option value="">All users</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>" <?= (int) $user === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><input type="date" class="form-control form-control-sm" name="from" value="<?= esc($from) ?>"></div>
    <div class="col-md-1"><input type="date" class="form-control form-control-sm" name="to"   value="<?= esc($to) ?>"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-outline-dark w-100">Filter</button></div>
  </form>
</div></div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0" data-tpt-cols="audit-logs">
      <thead>
        <tr><th data-col="when">When</th><th data-col="module">Module</th><th data-col="action">Action</th><th data-col="description">Description</th><th data-col="user">User</th><th data-col="ip">IP</th><th class="text-end" data-col="actions"></th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted">
            No audit entries match.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="when" data-label="When"><?= esc(substr((string) $r['created_at'], 0, 19)) ?></td>
            <td data-col="module" data-label="Module"><span class="badge-soft"><?= esc($r['module_name']) ?></span><?= $r['module_ref_id'] ? ' <code>#' . (int) $r['module_ref_id'] . '</code>' : '' ?></td>
            <td data-col="action" data-label="Action"><span class="<?= $actionCls((string) $r['action_type']) ?>"><?= esc($r['action_type']) ?></span></td>
            <td data-col="description" data-label="Description" style="max-width:480px;"><?= esc($r['action_description']) ?></td>
            <td data-col="user" data-label="User"><?= esc($r['user_name'] ?? '—') ?></td>
            <td data-col="ip" data-label="IP"><code style="font-size:.75rem;"><?= esc($r['ip_address']) ?></code></td>
            <td class="text-end" data-col="actions"><a class="btn btn-sm btn-light" href="<?= site_url('audit-logs/' . $r['id']) ?>"><i class="bi bi-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <div class="mt-3 d-flex justify-content-center align-items-center gap-2">
    <?php
      $qs = $_GET; unset($qs['page']);
      $link = fn($p) => site_url('audit-logs') . '?' . http_build_query($qs + ['page' => $p]);
    ?>
    <?php if ($page > 1): ?><a class="btn btn-sm btn-light" href="<?= $link($page - 1) ?>">← Prev</a><?php endif; ?>
    <span class="text-muted" style="font-size:.85rem;">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-light" href="<?= $link($page + 1) ?>">Next →</a><?php endif; ?>
  </div>
<?php endif; ?>
