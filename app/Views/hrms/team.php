<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-people"></i> Team — Employees</h5>
  <span class="badge bg-secondary"><?= count($rows) ?> staff</span>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('hrms/team-attendance') ?>"><i class="bi bi-calendar3"></i> Team attendance</a>
  <a class="btn btn-sm btn-light" href="<?= site_url('hrms/approvals') ?>"><i class="bi bi-clipboard-check"></i> Leave approvals</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0" data-tpt-cols="hrms-team">
      <thead>
        <tr><th data-col="name">Name</th><th data-col="code">Code</th><th data-col="role">Role</th><th data-col="designation">Designation</th><th data-col="department">Department</th><th data-col="joined">Joined</th><th data-col="profile">Profile</th><th class="text-end" data-col="actions">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="name"><?= esc($r['name']) ?><br><small class="text-muted"><?= esc($r['email']) ?></small></td>
            <td data-col="code"><?= esc($r['employee_code'] ?? '—') ?></td>
            <td data-col="role"><?= esc($r['role_name'] ?? '—') ?></td>
            <td data-col="designation"><?= esc($r['designation'] ?? '—') ?></td>
            <td data-col="department"><?= esc($r['department'] ?? '—') ?></td>
            <td data-col="joined"><?= !empty($r['date_of_joining']) ? esc(date('d-m-Y', strtotime($r['date_of_joining']))) : '—' ?></td>
            <td data-col="profile">
              <?php if (!empty($r['profile_completed'])): ?>
                <span class="badge bg-success">Complete</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Incomplete</span>
              <?php endif; ?>
            </td>
            <td class="text-end" data-col="actions"><a class="btn btn-sm btn-light" href="<?= site_url('hrms/team/' . (int) $r['id']) ?>"><i class="bi bi-pencil"></i> Manage</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
