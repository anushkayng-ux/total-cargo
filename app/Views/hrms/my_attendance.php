<?php
$mon = mktime(0, 0, 0, $month, 1, $year);
$days = (int) date('t', $mon);
$byDate = [];
foreach ($rows as $r) $byDate[$r['attendance_date']] = $r;
$hasIn  = !empty($today['punch_in_at']);
$hasOut = !empty($today['punch_out_at']);

$extra = '<div class="d-flex align-items-center gap-2">'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . (int) date('Y', strtotime('-1 month', $mon)) . '&m=' . (int) date('n', strtotime('-1 month', $mon)) . '"><i class="bi bi-chevron-left"></i> Prev</a>'
    . '<span style="font-weight:600;">' . esc(date('F Y', $mon)) . '</span>'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . (int) date('Y', strtotime('+1 month', $mon)) . '&m=' . (int) date('n', strtotime('+1 month', $mon)) . '">Next <i class="bi bi-chevron-right"></i></a>'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . (int) date('Y') . '&m=' . (int) date('n') . '">This month</a>'
    . '</div>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">My Attendance</div>
  <div class="spacer"></div>
  <div class="recordnav">Today is <?= esc(date('D, d-m-Y')) ?></div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="retro-row" style="align-items:center;">
    <?php if (!$hasIn): ?>
      <form method="post" action="<?= site_url('hrms/punch-in') ?>" id="punchInForm" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="lat" id="punchLat">
        <input type="hidden" name="lng" id="punchLng">
        <button class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-box-arrow-in-right"></i> Punch In</button>
      </form>
    <?php elseif (!$hasOut): ?>
      <div class="retro-field" style="color:#1a7f37;"><i class="bi bi-check-circle-fill"></i> Punched in at <strong><?= esc(date('H:i', strtotime($today['punch_in_at']))) ?></strong>.</div>
      <form method="post" action="<?= site_url('hrms/punch-out') ?>" id="punchOutForm" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="lat" id="punchOutLat">
        <input type="hidden" name="lng" id="punchOutLng">
        <button class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-box-arrow-right"></i> Punch Out</button>
      </form>
    <?php else: ?>
      <div class="retro-field">
        <i class="bi bi-check-circle-fill" style="color:#1a7f37;"></i>
        Day done: in <strong><?= esc(date('H:i', strtotime($today['punch_in_at']))) ?></strong>,
        out <strong><?= esc(date('H:i', strtotime($today['punch_out_at']))) ?></strong>,
        worked <strong><?= esc((string) $today['hours_worked']) ?> h</strong>.
      </div>
    <?php endif; ?>
  </div>
  <div class="retro-row" style="margin-top:4px;margin-bottom:0;">
    <small class="text-muted" id="geoStatus" style="font-size:11.5px;">Location: requesting…</small>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="my_attendance">
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
                  'Present' => 'badge-ok', 'HalfDay' => 'badge-warn',
                  'Leave', 'Holiday', 'Weekend' => '', default => 'badge-danger',
                };
              ?>
              <span class="badge-soft <?= $cls ?>"><?= esc($s) ?></span>
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
