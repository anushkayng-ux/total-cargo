<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);
// Build a milestone timeline: every status reached, with timestamp.
$timeline = [];
foreach ($milestones as $m) {
    if (!empty($m['new_status'])) {
        $timeline[] = [
            'status' => $m['new_status'],
            'at'     => $m['changed_at'],
            'notes'  => $m['notes'] ?? null,
        ];
    }
}
$reached = array_column($timeline, 'status');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-1">Trip <?= esc($row['trip_no']) ?></h5>
    <span class="text-muted" style="font-size:.9rem;">
      Booking <a href="<?= site_url('portal/bookings/' . ((int) ($row['booking_id'] ?? 0))) ?>"><?= esc($row['booking_no']) ?></a>
      · <?= esc(tpt_route($row['route_text'] ?? "")) ?>
    </span>
  </div>
  <a href="<?= site_url('portal/trips') ?>" class="btn btn-sm btn-light">&larr; All trips</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Status timeline</div>
      <div class="card-body">
        <ol class="list-unstyled mb-0" style="font-size:.92rem;">
          <?php foreach ($statuses as $s): ?>
            <?php $hit = in_array($s, $reached, true); ?>
            <?php
              $when = null;
              foreach (array_reverse($timeline) as $t) {
                if ($t['status'] === $s) { $when = $t['at']; break; }
              }
            ?>
            <li style="padding:.4rem 0; border-left:3px solid <?= $hit ? '#166c3b' : '#ddd' ?>; padding-left:.8rem; margin-left:.4rem;">
              <strong style="<?= $hit ? '' : 'color:#aaa;' ?>"><?= esc($s) ?></strong>
              <?php if ($when): ?>
                <span class="text-muted ms-2" style="font-size:.85rem;"><?= esc(date('d-m-Y H:i', strtotime($when))) ?></span>
              <?php endif; ?>
              <?php if ($s === $row['current_status']): ?>
                <span class="badge bg-dark ms-2">Current</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>

    <?php if (!empty($row['pod_status']) && $row['pod_status'] === 'Received'): ?>
      <div class="card mb-3">
        <div class="card-header">POD</div>
        <div class="card-body">
          <i class="bi bi-check-circle text-success"></i>
          POD received on <?= !empty($row['pod_received_at']) ? esc(date('d-m-Y H:i', strtotime($row['pod_received_at']))) : '—' ?>.
          <span class="text-muted ms-2">Contact your account manager to download a copy.</span>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">Vehicle &amp; driver</div>
      <div class="card-body" style="font-size:.92rem;">
        <div><strong>Vehicle:</strong> <?= esc($row['vehicle_number'] ?: '—') ?></div>
        <div><strong>Driver:</strong> <?= esc($row['driver_name'] ?: '—') ?></div>
        <div class="text-muted" style="font-size:.85rem;">
          For driver mobile, please contact your account manager.
        </div>
      </div>
    </div>

    <?php if ($gps): ?>
      <div class="card mb-3">
        <div class="card-header">Last known location</div>
        <div class="card-body" style="font-size:.92rem;">
          <?php if (!empty($gps['address'])): ?>
            <div><?= esc($gps['address']) ?></div>
          <?php elseif (!empty($gps['latitude'])): ?>
            <div><?= esc(number_format((float) $gps['latitude'], 5)) ?>, <?= esc(number_format((float) $gps['longitude'], 5)) ?></div>
          <?php endif; ?>
          <?php if (!empty($gps['eta_text'])): ?>
            <div class="text-muted">ETA: <?= esc($gps['eta_text']) ?></div>
          <?php endif; ?>
          <div class="text-muted" style="font-size:.8rem;">
            As of <?= !empty($gps['gps_timestamp']) ? esc(date('d-m H:i', strtotime($gps['gps_timestamp']))) : '—' ?>
            <?php if (!empty($gps['stale'])): ?>
              <span class="text-danger ms-1">(stale)</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($invoices)): ?>
      <div class="card">
        <div class="card-header">Invoices</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Invoice</th><th class="text-end">Total</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($invoices as $i): ?>
                <tr>
                  <td><a href="<?= site_url('portal/invoices/' . $i['id']) ?>"><?= esc($i['invoice_no']) ?></a></td>
                  <td class="text-end"><?= $fmt($i['total_amount']) ?></td>
                  <td><?= esc($i['invoice_status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
