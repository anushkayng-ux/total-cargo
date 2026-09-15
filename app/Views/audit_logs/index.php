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

$extra = '<form class="d-flex align-items-center gap-2 flex-wrap m-0" method="get" action="' . site_url('audit-logs') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" style="width:180px;" placeholder="Search module/action/description" value="' . esc($search) . '">'
    . '<select name="module" class="form-select form-select-sm" style="width:auto;"><option value="">All modules</option>';
foreach ($modules as $m) {
    $extra .= '<option value="' . esc($m) . '"' . ($module === $m ? ' selected' : '') . '>' . esc($m) . '</option>';
}
$extra .= '</select><select name="action" class="form-select form-select-sm" style="width:auto;"><option value="">All actions</option>';
foreach ($actions as $a) {
    $extra .= '<option value="' . esc($a) . '"' . ($action === $a ? ' selected' : '') . '>' . esc($a) . '</option>';
}
$extra .= '</select><select name="user" class="form-select form-select-sm" style="width:auto;"><option value="">All users</option>';
foreach ($users as $u) {
    $extra .= '<option value="' . (int) $u['id'] . '"' . ((int) $user === (int) $u['id'] ? ' selected' : '') . '>' . esc($u['name']) . '</option>';
}
$extra .= '</select>'
    . '<input type="date" class="form-control form-control-sm" style="width:140px;" name="from" value="' . esc($from) . '">'
    . '<input type="date" class="form-control form-control-sm" style="width:140px;" name="to" value="' . esc($to) . '">'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Audit Log</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= number_format($total) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="audit-logs">
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
          <tr class="row-link" data-href="<?= site_url('audit-logs/' . $r['id']) ?>">
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
</div>
