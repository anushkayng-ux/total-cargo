<?php
$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('hrms/team-attendance') . '"><i class="bi bi-calendar3"></i> Team Attendance</a>'
    . '<a class="btn btn-sm btn-outline-dark" href="' . site_url('hrms/approvals') . '"><i class="bi bi-clipboard-check"></i> Leave Approvals</a>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">Team — Employees</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> staff</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="hrms-team">
      <thead>
        <tr><th data-col="name">Name</th><th data-col="code">Code</th><th data-col="role">Role</th><th data-col="designation">Designation</th><th data-col="department">Department</th><th data-col="joined">Joined</th><th data-col="profile">Profile</th><th class="text-end" data-col="actions">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">No employees.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('hrms/team/' . (int) $r['id']) ?>">
            <td data-col="name" data-label="Name"><?= esc($r['name']) ?><br><small class="text-muted"><?= esc($r['email']) ?></small></td>
            <td data-col="code" data-label="Code"><?= esc($r['employee_code'] ?? '—') ?></td>
            <td data-col="role" data-label="Role"><?= esc($r['role_name'] ?? '—') ?></td>
            <td data-col="designation" data-label="Designation"><?= esc($r['designation'] ?? '—') ?></td>
            <td data-col="department" data-label="Department"><?= esc($r['department'] ?? '—') ?></td>
            <td data-col="joined" data-label="Joined"><?= !empty($r['date_of_joining']) ? esc(date('d-m-Y', strtotime($r['date_of_joining']))) : '—' ?></td>
            <td data-col="profile" data-label="Profile">
              <?php if (!empty($r['profile_completed'])): ?>
                <span class="badge-soft badge-ok">Complete</span>
              <?php else: ?>
                <span class="badge-soft badge-warn">Incomplete</span>
              <?php endif; ?>
            </td>
            <td class="text-end" data-col="actions" data-label="Actions"><a class="btn btn-sm btn-light" href="<?= site_url('hrms/team/' . (int) $r['id']) ?>"><i class="bi bi-pencil"></i> Manage</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
