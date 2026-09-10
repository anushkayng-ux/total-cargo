<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-calendar3"></i> Team Attendance · <?= esc(date('D, d-m-Y', strtotime($date))) ?></h5>
  <form method="get" class="ms-auto d-flex gap-2 align-items-center">
    <input class="form-control form-control-sm" type="date" name="d" value="<?= esc($date) ?>">
    <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-right"></i></button>
    <a class="btn btn-sm btn-light" href="?d=<?= date('Y-m-d') ?>">Today</a>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0" data-tpt-cols="hrms-team-attendance">
      <thead><tr><th data-col="employee">Employee</th><th data-col="punch-in">Punch in</th><th data-col="punch-out">Punch out</th><th data-col="hours">Hours</th><th data-col="status">Status</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r):
          $s = $r['att_status'] ?? 'Absent';
          $cls = match($s) {
            'Present' => 'success', 'HalfDay' => 'warning', 'Leave' => 'info',
            'Holiday','Weekend' => 'secondary', default => 'danger',
          };
        ?>
          <tr>
            <td data-col="employee"><?= esc($r['name']) ?></td>
            <td data-col="punch-in"><?= !empty($r['punch_in_at']) ? esc(date('H:i', strtotime($r['punch_in_at']))) : '—' ?></td>
            <td data-col="punch-out"><?= !empty($r['punch_out_at']) ? esc(date('H:i', strtotime($r['punch_out_at']))) : '—' ?></td>
            <td data-col="hours"><?= esc((string) ($r['hours_worked'] ?? 0)) ?></td>
            <td data-col="status"><span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($s) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
