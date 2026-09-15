<?php
$fmtDt   = fn ($x) => $x ? date('d-m-Y H:i', strtotime((string) $x)) : '—';
$dtLocal = fn ($x) => $x ? date('Y-m-d\TH:i', strtotime((string) $x)) : '';
$fbMap   = $feedbackByTrip ?? [];
?>
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Active Trips</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<?php if (empty($rows)): ?>
  <div class="gridwrap"><div class="text-center text-muted py-4">No active trips.</div></div>
<?php else: ?>
  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid table-hover align-middle mb-0" style="font-size:.9rem;" data-tpt-cols="arrival">
        <thead class="table-light">
          <tr>
            <th style="width:32px;"></th>
            <th data-col="trip">Trip / LR</th>
            <th data-col="route">Route</th>
            <th data-col="vehicle">Vehicle</th>
            <th data-col="status">Status</th>
            <th data-col="unloading-arrived">Unloading Arrived</th>
            <th data-col="unloading-departed">Unloading Departed</th>
            <th data-col="pod">POD</th>
            <th data-col="feedback">Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $t): ?>
            <?php
              $arrLbl  = $t['unloading_arrived_at']  ? $fmtDt($t['unloading_arrived_at'])  : '—';
              $depLbl  = $t['unloading_departed_at'] ? $fmtDt($t['unloading_departed_at']) : '—';
              $fbState =
                !empty($t['feedback_requested_at']) ? 'requested' :
                (in_array($t['current_status'] ?? '', ['Delivered','POD Received'], true) ? 'ready' : 'pending');
            ?>
            <tr class="ar-row" data-target="#ar-<?= (int) $t['id'] ?>" style="cursor:pointer;">
              <td class="text-muted"><i class="bi bi-chevron-right ar-chevron"></i></td>
              <td data-col="trip">
                <strong><?= esc($t['trip_no']) ?></strong>
                <?php if (!empty($t['lr_no'])): ?><br><small class="text-muted">LR <?= esc($t['lr_no']) ?></small><?php endif; ?>
              </td>
              <td data-col="route"><?= esc($t['route_text'] ?? '—') ?></td>
              <td data-col="vehicle"><code><?= esc($t['vehicle_number'] ?? '—') ?></code></td>
              <td data-col="status"><span class="badge-soft"><?= esc($t['current_status']) ?></span></td>
              <td data-col="unloading-arrived"><?= esc($arrLbl) ?></td>
              <td data-col="unloading-departed"><?= esc($depLbl) ?></td>
              <td data-col="pod"><span class="badge-soft <?= ($t['pod_status'] ?? '') === 'Received' ? 'badge-ok' : (($t['pod_status'] ?? '') === 'Uploaded' ? 'badge-ok' : 'badge-warn') ?>"><?= esc($t['pod_status'] ?? '—') ?></span></td>
              <td data-col="feedback">
                <?php if ($fbState === 'requested'): ?>
                  <span class="badge-soft badge-ok">Requested</span>
                <?php elseif ($fbState === 'ready'): ?>
                  <span class="badge-soft badge-warn">Ready to send</span>
                <?php else: ?>
                  <span class="badge-soft">Not yet requested</span>
                <?php endif; ?>
              </td>
            </tr>
            <tr id="ar-<?= (int) $t['id'] ?>" class="ar-detail" style="display:none;background:#fafcff;">
              <td></td>
              <td colspan="8" style="padding:14px 18px;">
                <form method="post" action="<?= site_url('arrival/' . $t['id'] . '/save') ?>" class="row g-3">
                  <?= csrf_field() ?>
                  <div class="col-md-3">
                    <label class="form-label">Unloading Arrived</label>
                    <input type="datetime-local" class="form-control form-control-sm" name="unloading_arrived_at" value="<?= $dtLocal($t['unloading_arrived_at']) ?>">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Unloading Departed</label>
                    <input type="datetime-local" class="form-control form-control-sm" name="unloading_departed_at" value="<?= $dtLocal($t['unloading_departed_at']) ?>">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">POD Status</label>
                    <select class="form-select form-select-sm tpt-no-search" name="pod_status">
                      <?php foreach (['Pending','Received','Uploaded'] as $ps): ?>
                        <option value="<?= $ps ?>" <?= ($t['pod_status'] ?? '') === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Remarks</label>
                    <input class="form-control form-control-sm" name="remarks" value="<?= esc($t['remarks'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a class="btn btn-sm btn-light" href="<?= site_url('trips/' . $t['id']) ?>">Open Trip</a>
                  </div>
                </form>

                <?php
                  $fb = $fbMap[(int) $t['id']] ?? null;
                  $fbSubmitted = !empty($fb['submitted_at']);
                  $fbLink = !empty($fb['feedback_token']) ? site_url('feedback/' . $fb['feedback_token']) : null;
                ?>
                <hr class="my-3">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                  <div style="min-width:200px;">
                    <div class="text-muted" style="font-size:.72rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Client Feedback</div>
                    <?php if ($fbSubmitted): ?>
                      <div style="font-size:1.6rem;color:#f59e0b;line-height:1;">
                        <?php $stars = (int) $fb['rating_overall'];
                          for ($i=0;$i<$stars;$i++) echo '★';
                          for ($i=$stars;$i<5;$i++) echo '<span style="color:#d1d5db;">★</span>';
                        ?>
                      </div>
                      <div class="text-muted" style="font-size:.78rem;">
                        <?= esc(date('d-m-Y', strtotime($fb['submitted_at']))) ?>
                        <?php if (!empty($fb['submitter_name'])): ?> · <?= esc($fb['submitter_name']) ?><?php endif; ?>
                      </div>
                    <?php elseif ($fb): ?>
                      <span class="badge-soft badge-warn">Pending</span>
                    <?php else: ?>
                      <span class="badge-soft">Not yet requested</span>
                    <?php endif; ?>
                  </div>

                  <?php if ($fbSubmitted): ?>
                    <?php $extras = array_filter([
                      'On-time'         => $fb['rating_on_time'],
                      'Goods condition' => $fb['rating_goods_condition'],
                      'Driver'          => $fb['rating_driver'],
                      'Communication'   => $fb['rating_communication'],
                    ]); ?>
                    <?php if ($extras || !empty($fb['comments'])): ?>
                    <div class="flex-grow-1" style="min-width:260px;">
                      <?php foreach ($extras as $lbl => $val): ?>
                        <div class="d-flex justify-content-between" style="border-bottom:1px solid #f1f3f5;padding:.15rem 0;font-size:.82rem;">
                          <span class="text-muted"><?= esc($lbl) ?></span>
                          <span><?php for ($i=0;$i<(int)$val;$i++) echo '★'; ?><span class="text-muted"><?php for ($i=(int)$val;$i<5;$i++) echo '★'; ?></span></span>
                        </div>
                      <?php endforeach; ?>
                      <?php if (!empty($fb['comments'])): ?>
                        <div class="mt-2" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:.5rem .7rem;font-size:.85rem;color:#374151;">
                          <?= nl2br(esc($fb['comments'])) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                  <?php elseif ($fbLink): ?>
                    <div class="input-group input-group-sm" style="max-width:420px;">
                      <input class="form-control" type="text" readonly value="<?= esc($fbLink) ?>" onclick="this.select();">
                      <a class="btn btn-light" href="<?= esc($fbLink) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
  </div>
<?php endif; ?>

<script>
// Row click → toggle the detail row + chevron rotation
document.querySelectorAll('tr.ar-row').forEach(function (tr) {
  tr.addEventListener('click', function (e) {
    // Ignore clicks on links/buttons inside the row
    if (e.target.closest('a, button, input, select, textarea, label')) return;
    var tgt = document.querySelector(tr.getAttribute('data-target'));
    var chev = tr.querySelector('.ar-chevron');
    if (!tgt) return;
    var isOpen = tgt.style.display !== 'none';
    tgt.style.display = isOpen ? 'none' : 'table-row';
    if (chev) chev.classList.toggle('bi-chevron-right', isOpen);
    if (chev) chev.classList.toggle('bi-chevron-down',  !isOpen);
  });
});
</script>
