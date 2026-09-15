<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$route = trim(($row['pickup_city'] ?? '') . ' → ' . ($row['drop_city'] ?? ''), ' →');
$otherQuickAdd = array_values(array_filter(
    tpt_quick_add_items($auth),
    fn ($qa) => rtrim($qa['url'], '/') !== rtrim(site_url('leads/create'), '/')
));
?>
<div class="retro-toolbar is-sticky">
  <?php if ($otherQuickAdd): ?>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>
      <ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('leads/create') ?>"><i class="bi bi-plus-circle"></i> New Lead</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Other…</h6></li>
        <?php foreach ($otherQuickAdd as $qa): ?>
          <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= esc($qa['url']) ?>"><i class="bi bi-<?= esc($qa['icon']) ?>"></i> <?= esc($qa['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php else: ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('leads/create') ?>"><i class="bi bi-plus-circle-fill"></i>New</a>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('leads/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil-fill"></i>Edit</a>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-save-fill"></i>Save</div>
  <?php if (in_array((string) $row['current_status'], ['New', 'Under Review'], true)): ?>
    <form method="post" action="<?= site_url('leads/' . $row['id'] . '/status') ?>" class="d-inline" data-confirm="Send this query to the Purchase team?">
      <?= csrf_field() ?>
      <input type="hidden" name="new_status" value="Sent to Purchase">
      <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-arrow-right-circle"></i>Send to Purchase</button>
    </form>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('rfq/create?lead_id=' . $row['id']) ?>"><i class="bi bi-send"></i>Create RFQ</a>
  <a class="retro-tbtn" href="<?= site_url('leads/' . $row['id'] . '/quotation.pdf') ?>" target="_blank"><i class="bi bi-file-earmark-arrow-down"></i>Quotation</a>
  <form method="post" action="<?= site_url('leads/' . $row['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this lead?">
    <?= csrf_field() ?>
    <button type="submit" class="retro-tbtn"><i class="bi bi-trash-fill"></i>Delete</button>
  </form>
  <button type="button" class="retro-tbtn" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
  <a class="retro-tbtn" href="<?= site_url('leads') ?>"><i class="bi bi-x-lg"></i>Close</a>
</div>

<div class="tabs" id="leadTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#lead-details">Lead Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#lead-followups">Follow-ups</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#lead-status">Status &amp; Assignment</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('leads') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Lead <?= esc($row['lead_no']) ?> &middot; <?= esc($row['current_status']) ?>
  </div>
</div>

<div class="tab-content">

  <div class="tab-pane fade show active" id="lead-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Source :</label><div class="retro-box wide"><?= $val($row['source_name'] ?? null) ?></div></div>
          <div class="retro-field"><label>Priority :</label><div class="retro-box"><?= $val($row['priority']) ?></div></div>
          <div class="retro-field"><label>Captured :</label><div class="retro-box wide"><?= $val(tpt_dt($row['lead_datetime'])) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:60%;"><label>Client / Company :</label><div class="retro-box xwide"><?= $val($row['client_company'] ?? ($row['company_name'] ?? null)) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Contact Name :</label><div class="retro-box wide"><?= $val($row['client_name']) ?></div></div>
          <div class="retro-field"><label>Mobile :</label><div class="retro-box"><?= $val($row['mobile']) ?></div></div>
          <div class="retro-field"><label>Alt Mobile :</label><div class="retro-box empty"><?= $val($row['alt_mobile'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Route :</label><div class="retro-box xwide"><?= $val($route ?: null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle :</label><div class="retro-box wide"><?= $val($row['vehicle_type_required']) ?></div></div>
          <div class="retro-field"><label>Weight :</label><div class="retro-box"><?= $val(trim(($row['weight'] ?? '') . ' ' . ($row['weight_unit'] ?? ''))) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Material :</label><div class="retro-box wide"><?= $val($row['material_type']) ?></div></div>
          <div class="retro-field"><label>Expected Dispatch :</label><div class="retro-box"><?= $val($row['expected_dispatch_date']) ?></div></div>
          <div class="retro-field"><label>Assigned To :</label><div class="retro-box wide"><?= $val($row['assignee_name'] ?? null) ?></div></div>
        </div>
        <?php if (!empty($row['lost_reason'])): ?>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Lost Reason :</label>
            <div class="retro-particulars" style="color:var(--v2-danger);"><?= nl2br(esc($row['lost_reason'])) ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="retro-detail-side">
        <h4>Remarks :</h4>
        <div class="remarksbox"><?= !empty($row['remarks']) ? nl2br(esc($row['remarks'])) : 'No remarks recorded.' ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="lead-followups">
    <div class="formwrap" style="flex:0 0 auto;">
      <h6 class="mb-2"><i class="bi bi-plus-circle"></i> Add Follow-up</h6>
      <form method="post" action="<?= site_url('leads/' . $row['id'] . '/followups') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field"><label>When :</label>
            <input type="datetime-local" class="retro-box wide" name="followup_datetime" value="<?= date('Y-m-d\TH:i') ?>" required></div>
          <div class="retro-field"><label>Type :</label>
            <select class="retro-box" name="followup_type">
              <?php foreach (['Call','WhatsApp','Email','Meeting','Note'] as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Next Follow-up :</label>
            <input type="datetime-local" class="retro-box wide" name="next_followup_datetime"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Discussion Notes :</label>
            <textarea class="retro-particulars" name="discussion_notes" rows="2" required></textarea></div>
        </div>
        <div class="mt-2"><button class="btn btn-primary btn-sm">Save Follow-up</button></div>
      </form>
    </div>
    <div class="gridwrap" style="margin-top:14px;padding:16px 20px;">
      <h6 class="mb-2"><i class="bi bi-clock-history"></i> Follow-up History</h6>
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

  <div class="tab-pane fade" id="lead-status">
    <div class="formwrap" style="flex:0 0 auto;">
      <div class="row g-3">
        <div class="col-md-6">
          <h6 class="mb-2"><i class="bi bi-arrow-repeat"></i> Change Status</h6>
          <form method="post" action="<?= site_url('leads/' . $row['id'] . '/status') ?>">
            <?= csrf_field() ?>
            <div class="retro-row">
              <div class="retro-field"><label>New Status :</label>
                <select class="retro-box wide" name="new_status">
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= esc($s) ?>" <?= $row['current_status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Remarks :</label>
                <input class="retro-box wide" name="remarks">
              </div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Lost Reason <small class="text-muted">(if Lost)</small> :</label>
                <input class="retro-box wide" name="lost_reason">
              </div>
            </div>
            <button class="btn btn-primary btn-sm">Save Status</button>
          </form>
        </div>
        <div class="col-md-6">
          <h6 class="mb-2"><i class="bi bi-person-check"></i> Assign</h6>
          <form method="post" action="<?= site_url('leads/' . $row['id'] . '/assign') ?>">
            <?= csrf_field() ?>
            <div class="retro-row">
              <div class="retro-field">
                <select class="retro-box xwide" name="assigned_crm_user_id">
                  <option value="">— Unassigned —</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int) $row['assigned_crm_user_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button class="btn btn-primary btn-sm">Update Assignee</button>
          </form>
        </div>
      </div>
    </div>
    <div class="gridwrap" style="margin-top:14px;padding:16px 20px;">
      <h6 class="mb-2"><i class="bi bi-clock-history"></i> Status History</h6>
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
