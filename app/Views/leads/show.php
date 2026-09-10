<?php
$statusBadge = function (string $s): string {
    $map = [
        'Won' => 'badge-soft badge-ok',
        'Lost' => 'badge-soft badge-danger',
    ];
    return '<span class="' . ($map[$s] ?? 'badge-soft') . '">' . esc($s) . '</span>';
};
$route = trim(($row['pickup_city'] ?? '') . ' → ' . ($row['drop_city'] ?? ''), ' →');
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Lead <code><?= esc($row['lead_no']) ?></code></h5>
  <?= $statusBadge((string) $row['current_status']) ?>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('leads') ?>"><i class="bi bi-arrow-left"></i> Back</a>
  <?php if (in_array((string) $row['current_status'], ['New', 'Under Review'], true)): ?>
    <form class="d-inline" method="post" action="<?= site_url('leads/' . $row['id'] . '/status') ?>" data-confirm="Send this query to the Purchase team?">
      <?= csrf_field() ?>
      <input type="hidden" name="new_status" value="Sent to Purchase">
      <button class="btn btn-sm btn-warning" type="submit"><i class="bi bi-arrow-right-circle"></i> Send to Purchase</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-sm btn-primary" href="<?= site_url('rfq/create?lead_id=' . $row['id']) ?>"><i class="bi bi-send"></i> Create RFQ</a>
  <a class="btn btn-sm btn-outline-success" href="<?= site_url('leads/' . $row['id'] . '/quotation.pdf') ?>" target="_blank"><i class="bi bi-file-earmark-arrow-down"></i> Download Quotation</a>
  <a class="btn btn-sm btn-outline-dark" href="<?= site_url('leads/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit</a>
  <form class="d-inline" method="post" action="<?= site_url('leads/' . $row['id'] . '/delete') ?>" data-confirm="Delete this lead?">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-trash"></i></button>
  </form>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Lead Details</div>
      <div class="card-body">
        <div class="row g-3" style="font-size:.92rem;">
          <div class="col-md-4"><div class="text-muted">Source</div><div><?= esc($row['source_name'] ?? '—') ?></div></div>
          <div class="col-md-4"><div class="text-muted">Priority</div><div><?= esc($row['priority']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Captured</div><div><?= esc(tpt_dt($row['lead_datetime'])) ?></div></div>

          <div class="col-md-6"><div class="text-muted">Client / Company</div>
            <div><?= esc($row['client_company'] ?? ($row['company_name'] ?? '—')) ?></div>
            <small class="text-muted"><?= esc($row['client_name']) ?></small>
          </div>
          <div class="col-md-3"><div class="text-muted">Mobile</div><div><?= esc($row['mobile']) ?></div></div>
          <div class="col-md-3"><div class="text-muted">Alt</div><div><?= esc($row['alt_mobile']) ?></div></div>

          <div class="col-md-6"><div class="text-muted">Route</div><div><?= esc($route ?: '—') ?></div></div>
          <div class="col-md-3"><div class="text-muted">Vehicle</div><div><?= esc($row['vehicle_type_required']) ?></div></div>
          <div class="col-md-3"><div class="text-muted">Weight</div><div><?= esc(($row['weight'] ?? '') . ' ' . ($row['weight_unit'] ?? '')) ?></div></div>

          <div class="col-md-4"><div class="text-muted">Material</div><div><?= esc($row['material_type']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Expected Dispatch</div><div><?= esc($row['expected_dispatch_date']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Assigned To</div><div><?= esc($row['assignee_name'] ?? '—') ?></div></div>

          <?php if (!empty($row['remarks'])): ?>
            <div class="col-12"><div class="text-muted">Remarks</div><div><?= nl2br(esc($row['remarks'])) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($row['lost_reason'])): ?>
            <div class="col-12"><div class="text-muted">Lost Reason</div><div class="text-danger"><?= nl2br(esc($row['lost_reason'])) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Add Follow-up</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('leads/' . $row['id'] . '/followups') ?>">
          <?= csrf_field() ?>
          <div class="row g-2">
            <div class="col-md-4"><label class="form-label">When</label>
              <input type="datetime-local" class="form-control" name="followup_datetime" value="<?= date('Y-m-d\TH:i') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Type</label>
              <select class="form-select" name="followup_type">
                <?php foreach (['Call','WhatsApp','Email','Meeting','Note'] as $t): ?>
                  <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5"><label class="form-label">Next Follow-up</label>
              <input type="datetime-local" class="form-control" name="next_followup_datetime"></div>
            <div class="col-12"><label class="form-label">Discussion Notes</label>
              <textarea class="form-control" name="discussion_notes" rows="2" required></textarea></div>
          </div>
          <div class="mt-2"><button class="btn btn-primary btn-sm">Save Follow-up</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Follow-ups</div>
      <div class="card-body">
        <?php if (empty($followups)): ?>
          <div class="text-muted">No follow-ups logged yet.</div>
        <?php else: ?>
          <ul class="list-unstyled m-0">
            <?php foreach ($followups as $f): ?>
              <li class="mb-3 pb-3" style="border-bottom:1px solid var(--tpt-border);">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <span class="badge-soft"><?= esc($f['followup_type'] ?: 'Note') ?></span>
                  <strong><?= esc(tpt_dt($f['followup_datetime'])) ?></strong>
                  <span class="text-muted" style="font-size:.8rem;">by <?= esc($f['created_by_name'] ?? '—') ?></span>
                  <?php if (!empty($f['next_followup_datetime'])): ?>
                    <span class="ms-auto badge-soft badge-warn">Next: <?= esc(tpt_dt($f['next_followup_datetime'])) ?></span>
                  <?php endif; ?>
                </div>
                <div class="mt-1"><?= nl2br(esc($f['discussion_notes'])) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header">Change Status</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('leads/' . $row['id'] . '/status') ?>">
          <?= csrf_field() ?>
          <div class="mb-2">
            <label class="form-label">New Status</label>
            <select class="form-select" name="new_status">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s) ?>" <?= $row['current_status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Remarks</label>
            <input class="form-control" name="remarks">
          </div>
          <div class="mb-2">
            <label class="form-label">Lost Reason <small class="text-muted">(if Lost)</small></label>
            <input class="form-control" name="lost_reason">
          </div>
          <button class="btn btn-primary btn-sm w-100">Save Status</button>
        </form>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Assign</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('leads/' . $row['id'] . '/assign') ?>">
          <?= csrf_field() ?>
          <select class="form-select mb-2" name="assigned_crm_user_id">
            <option value="">— Unassigned —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>" <?= (int) $row['assigned_crm_user_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary btn-sm w-100">Update Assignee</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Status History</div>
      <div class="card-body">
        <?php if (empty($history)): ?>
          <div class="text-muted">No history yet.</div>
        <?php else: ?>
          <ul class="list-unstyled m-0" style="font-size:.88rem;">
            <?php foreach ($history as $h): ?>
              <li class="mb-2 pb-2" style="border-bottom:1px solid var(--tpt-border);">
                <div>
                  <?php if (!empty($h['old_status'])): ?>
                    <code class="text-muted" style="font-size:.75rem;"><?= esc($h['old_status']) ?></code>
                    <i class="bi bi-arrow-right"></i>
                  <?php endif; ?>
                  <strong><?= esc($h['new_status']) ?></strong>
                </div>
                <small class="text-muted"><?= esc(tpt_dt($h['changed_at'])) ?> · <?= esc($h['changed_by_name'] ?? '—') ?></small>
                <?php if (!empty($h['remarks'])): ?>
                  <div class="mt-1 text-muted" style="font-size:.85rem;"><?= esc($h['remarks']) ?></div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
