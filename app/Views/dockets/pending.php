<?php
/**
 * Trips awaiting docket — one-page ops queue.
 * Every row is a trip whose LR number is still blank. Click "Fill docket"
 * to jump into the Create Docket form with all party/route data prefilled.
 */
?>
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h5 class="m-0"><i class="bi bi-file-earmark-ruled"></i> Trips awaiting docket</h5>
    <small class="text-muted">Every active trip whose LR / docket number hasn't been filled in yet — click a row to fill the docket.</small>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0" style="font-size:.9rem;" data-tpt-cols="dockets_pending">
        <thead>
          <tr>
            <th data-col="trip">Trip</th>
            <th data-col="client">Client</th>
            <th data-col="route">Route</th>
            <th data-col="weight" class="text-end">Chg. wt (kg)</th>
            <th data-col="date">Loading date</th>
            <th data-col="vehicle">Vehicle</th>
            <th data-col="status">Status</th>
            <th data-col="action" class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-check2-circle text-success"></i>
                Nothing waiting — every active trip has its docket / LR number.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $route = trim(($r['loading_point'] ?? '') . ' → ' . ($r['unloading_point'] ?? ''), ' →');
                if ($route === '') $route = $r['route_text'] ?? '—';
                $loadingDate = !empty($r['loading_date']) ? date('d M Y', strtotime($r['loading_date'])) : '<span class="text-muted">—</span>';
                $vehicle = trim(($r['vehicle_number'] ?? '') . ($r['driver_mobile'] ? ' · ' . $r['driver_mobile'] : ''), ' ·');
              ?>
              <tr style="cursor:pointer;" onclick="window.location='<?= site_url('dockets/create/' . (int) $r['id']) ?>'">
                <td data-col="trip">
                  <div style="font-weight:600;"><?= esc($r['trip_no']) ?></div>
                  <?php if (!empty($r['booking_no'])): ?>
                    <small class="text-muted"><?= esc($r['booking_no']) ?></small>
                  <?php endif; ?>
                </td>
                <td data-col="client"><?= esc($r['client_company'] ?? '—') ?></td>
                <td data-col="route"><?= esc($route) ?></td>
                <td data-col="weight" class="text-end"><?= !empty($r['charge_weight_kg']) ? number_format((float) $r['charge_weight_kg']) : '<span class="text-muted">—</span>' ?></td>
                <td data-col="date"><?= $loadingDate ?></td>
                <td data-col="vehicle"><?= $vehicle !== '' ? esc($vehicle) : '<span class="text-muted">—</span>' ?></td>
                <td data-col="status"><span class="badge-soft"><?= esc($r['current_status'] ?? '—') ?></span></td>
                <td data-col="action" class="text-end">
                  <a href="<?= site_url('dockets/create/' . (int) $r['id']) ?>" class="btn btn-sm btn-primary" onclick="event.stopPropagation();">
                    Fill docket <i class="bi bi-arrow-right"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
