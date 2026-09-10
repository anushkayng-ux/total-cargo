<?php
$mon = mktime(0, 0, 0, $month, 1, $year);
$days = (int) date('t', $mon);
$byDate = [];
foreach ($rows as $r) $byDate[$r['attendance_date']] = $r;
$hasIn  = !empty($today['punch_in_at']);
$hasOut = !empty($today['punch_out_at']);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-clock-history"></i> My Attendance</h5>
  <small class="text-muted ms-auto">Today is <?= esc(date('D, d-m-Y')) ?></small>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-broadcast-pin"></i> Punch in / out</div>
  <div class="card-body">
    <?php if (!$hasIn): ?>
      <form method="post" action="<?= site_url('hrms/punch-in') ?>" id="punchInForm" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="lat" id="punchLat">
        <input type="hidden" name="lng" id="punchLng">
        <button class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Punch In</button>
      </form>
    <?php elseif (!$hasOut): ?>
      <div class="alert alert-success mb-2"><i class="bi bi-check-circle-fill"></i> Punched in at <strong><?= esc(date('H:i', strtotime($today['punch_in_at']))) ?></strong>.</div>
      <form method="post" action="<?= site_url('hrms/punch-out') ?>" id="punchOutForm" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="lat" id="punchOutLat">
        <input type="hidden" name="lng" id="punchOutLng">
        <button class="btn btn-warning"><i class="bi bi-box-arrow-right"></i> Punch Out</button>
      </form>
    <?php else: ?>
      <div class="alert alert-success mb-0">
        <i class="bi bi-check-circle-fill"></i>
        Day done: in <strong><?= esc(date('H:i', strtotime($today['punch_in_at']))) ?></strong>,
        out <strong><?= esc(date('H:i', strtotime($today['punch_out_at']))) ?></strong>,
        worked <strong><?= esc((string) $today['hours_worked']) ?> h</strong>.
      </div>
    <?php endif; ?>
    <small class="text-muted d-block mt-2" id="geoStatus">Location: requesting…</small>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <span><?= esc(date('F Y', $mon)) ?></span>
    <div class="ms-auto d-flex gap-1" style="font-size:.85rem;">
      <a class="btn btn-sm btn-light" href="?y=<?= (int) date('Y', strtotime("-1 month", $mon)) ?>&m=<?= (int) date('n', strtotime("-1 month", $mon)) ?>">&laquo; Prev</a>
      <a class="btn btn-sm btn-light" href="?y=<?= (int) date('Y') ?>&m=<?= (int) date('n') ?>">This month</a>
      <a class="btn btn-sm btn-light" href="?y=<?= (int) date('Y', strtotime("+1 month", $mon)) ?>&m=<?= (int) date('n', strtotime("+1 month", $mon)) ?>">Next &raquo;</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0" data-tpt-cols="my_attendance">
      <thead><tr><th data-col="date">Date</th><th data-col="day">Day</th><th data-col="in">In</th><th data-col="out">Out</th><th data-col="hours">Hours</th><th data-col="status">Status</th></tr></thead>
      <tbody>
        <?php for ($d = 1; $d <= $days; $d++):
          $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
          $r = $byDate[$date] ?? null;
          $dow = date('D', strtotime($date));
          $isWeekend = in_array($dow, ['Sat','Sun'], true);
        ?>
          <tr class="<?= $isWeekend && !$r ? 'text-muted' : '' ?>">
            <td data-col="date"><?= date('d-m', strtotime($date)) ?></td>
            <td data-col="day"><?= $dow ?></td>
            <td data-col="in"><?= $r && $r['punch_in_at']  ? esc(date('H:i', strtotime($r['punch_in_at']))) : '—' ?></td>
            <td data-col="out"><?= $r && $r['punch_out_at'] ? esc(date('H:i', strtotime($r['punch_out_at']))) : '—' ?></td>
            <td data-col="hours"><?= $r ? esc((string) $r['hours_worked']) : '—' ?></td>
            <td data-col="status">
              <?php $s = $r['status'] ?? ($isWeekend ? 'Weekend' : 'Absent');
                $cls = match($s) {
                  'Present' => 'success', 'HalfDay' => 'warning', 'Leave' => 'info',
                  'Holiday','Weekend' => 'secondary', default => 'danger',
                };
              ?>
              <span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($s) ?></span>
            </td>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  if (!navigator.geolocation) {
    var s = document.getElementById('geoStatus');
    if (s) s.textContent = 'Location: not supported by this browser';
    return;
  }
  navigator.geolocation.getCurrentPosition(function (pos) {
    var lat = pos.coords.latitude.toFixed(6), lng = pos.coords.longitude.toFixed(6);
    ['punchLat','punchOutLat'].forEach(function (id) { var el = document.getElementById(id); if (el) el.value = lat; });
    ['punchLng','punchOutLng'].forEach(function (id) { var el = document.getElementById(id); if (el) el.value = lng; });
    var s = document.getElementById('geoStatus');
    if (s) s.textContent = 'Location captured · accuracy ' + Math.round(pos.coords.accuracy) + ' m';
  }, function (err) {
    var s = document.getElementById('geoStatus');
    if (s) s.textContent = 'Location: ' + err.message + ' (punch will still work without GPS)';
  }, { enableHighAccuracy: true, timeout: 8000 });
})();
</script>
