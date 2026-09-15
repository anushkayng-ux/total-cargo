<?php
$extra = '<form method="get" class="d-flex align-items-center gap-2 m-0">'
    . '<input class="form-control form-control-sm" type="date" name="d" value="' . esc($date) . '">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-arrow-right"></i></button>'
    . '<a class="btn btn-sm btn-outline-dark" href="?d=' . date('Y-m-d') . '">Today</a>'
    . '</form>';

echo tpt_toolbar([
    'close_href' => site_url('hrms/team'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Team Attendance — <?= esc(date('D, d-m-Y', strtotime($date))) ?></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> staff</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="hrms-team-attendance">
      <thead><tr><th data-col="employee">Employee</th><th data-col="punch-in">Punch in</th><th data-col="punch-out">Punch out</th><th data-col="hours">Hours</th><th data-col="status">Status</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="5" class="text-center text-muted py-3">No staff found.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $s = $r['att_status'] ?? 'Absent';
          $cls = match($s) {
            'Present' => 'badge-ok', 'HalfDay' => 'badge-warn',
            'Leave', 'Holiday', 'Weekend' => '', default => 'badge-danger',
          };
        ?>
          <tr>
            <td data-col="employee"><?= esc($r['name']) ?></td>
            <td data-col="punch-in"><?= !empty($r['punch_in_at']) ? esc(date('H:i', strtotime($r['punch_in_at']))) : '—' ?></td>
            <td data-col="punch-out"><?= !empty($r['punch_out_at']) ? esc(date('H:i', strtotime($r['punch_out_at']))) : '—' ?></td>
            <td data-col="hours"><?= esc((string) ($r['hours_worked'] ?? 0)) ?></td>
            <td data-col="status"><span class="badge-soft <?= $cls ?>"><?= esc($s) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
