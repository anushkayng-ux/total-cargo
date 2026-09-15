<?php
use App\Controllers\QuotationsController;

$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$route = trim(($row['pickup_city'] ?? '') . ' → ' . ($row['drop_city'] ?? ''), ' →');
$ranked = QuotationsController::rankQuotations($quotations);

$statusColor = function (string $s): string {
    $map = [
        'Pending'  => 'badge-soft',
        'Queued'   => 'badge-soft badge-warn',
        'Sent'     => 'badge-soft badge-warn',
        'Failed'   => 'badge-soft badge-danger',
        'Received' => 'badge-soft badge-ok',
    ];
    return $map[$s] ?? 'badge-soft';
};

$previewText = null;
if (!empty($template)) {
    $previewText = (new \App\Libraries\WhatsAppService())->renderLocalTemplate('rfq_vendor', [
        $row['masked_reference'],
        trim(($row['pickup_city'] ?? '') . ' → ' . ($row['drop_city'] ?? ''), ' →'),
        $row['vehicle_type']       ?? '-',
        $row['material_category']  ?? '-',
        trim(($row['weight'] ?? '-') . ' ' . ($row['weight_unit'] ?? '')),
        $row['loading_date']       ?? 'TBC',
    ]);
}
?>
<div class="retro-toolbar is-sticky">
  <a class="retro-tbtn retro-primary" href="<?= site_url('rfq/create') ?>"><i class="bi bi-plus-circle-fill"></i>New</a>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-pencil-fill"></i>Edit</div>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/dispatch') ?>" class="d-inline" data-confirm="Send WhatsApp to all pending vendors now?">
    <?= csrf_field() ?>
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-whatsapp"></i>Dispatch</button>
  </form>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/remind') ?>" class="d-inline" data-confirm="Send reminder to vendors who haven't responded?">
    <?= csrf_field() ?>
    <button type="submit" class="retro-tbtn"><i class="bi bi-bell"></i>Remind</button>
  </form>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/close') ?>" class="d-inline" data-confirm="Close this RFQ?">
    <?= csrf_field() ?>
    <button type="submit" class="retro-tbtn"><i class="bi bi-lock"></i>Close</button>
  </form>
  <?php if ($row['status'] === 'Awarded'): ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('bookings/from-rfq/' . $row['id']) ?>"><i class="bi bi-arrow-right-circle"></i>To Booking</a>
  <?php endif; ?>
  <button type="button" class="retro-tbtn" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
  <a class="retro-tbtn" href="<?= site_url('rfq') ?>"><i class="bi bi-x-lg"></i>Close</a>

  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/assign') ?>" class="d-inline-flex align-items-center gap-1" style="margin-left:auto;">
    <?= csrf_field() ?>
    <i class="bi bi-person-check text-muted"></i>
    <select name="assigned_to" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" title="Assign this RFQ to a team member">
      <option value="">— Assign to —</option>
      <?php foreach (($staff ?? []) as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) ($row['assigned_to'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
          <?= esc($s['name']) ?><?= !empty($s['role_name']) ? ' (' . esc($s['role_name']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="tabs" role="tablist" id="rfqTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#rfq-details">RFQ Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#rfq-vendors">Vendors</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#rfq-quotes">Quotations</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('rfq') ?>"><i class="bi bi-list"></i> List</a> &middot;
    RFQ <?= esc($row['rfq_no']) ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('rfq/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('rfq/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">

  <div class="tab-pane fade show active" id="rfq-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Status :</label><div class="retro-box wide"><?= $val($row['status']) ?></div></div>
          <div class="retro-field" style="margin-left:auto;"><label>Masked Ref :</label><div class="retro-box wide"><?= $val($row['masked_reference']) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Route :</label><div class="retro-box xwide"><?= $val($route ?: null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle :</label><div class="retro-box wide"><?= $val($row['vehicle_type']) ?></div></div>
          <div class="retro-field"><label>Material :</label><div class="retro-box wide"><?= $val($row['material_category']) ?></div></div>
          <div class="retro-field"><label>Weight :</label><div class="retro-box"><?= $val(trim(($row['weight'] ?? '') . ' ' . ($row['weight_unit'] ?? ''))) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Loading Date :</label><div class="retro-box wide"><?= $val($row['loading_date']) ?></div></div>
          <div class="retro-field"><label>Created By :</label><div class="retro-box wide"><?= $val($row['created_by_name'] ?? null) ?></div></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">From Lead :</label>
            <div class="retro-particulars">
              <?php if (!empty($row['lead_id'])): ?>
                <a href="<?= site_url('leads/' . $row['lead_id']) ?>"><?= esc($row['lead_no']) ?></a> (client hidden from vendors)
              <?php else: ?>
                Standalone RFQ
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="retro-detail-side">
        <h4>WhatsApp Preview :</h4>
        <?php if ($previewText): ?>
          <div class="remarksbox" style="white-space:pre-wrap;"><?= esc($previewText) ?></div>
          <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:6px;">Template: <?= esc($template['template_name']) ?> · Lang <?= esc($template['language_code']) ?></div>
        <?php else: ?>
          <div class="remarksbox" style="color:var(--v2-danger);">Template "rfq_vendor" not found.</div>
        <?php endif; ?>
        <?php if (!$wa->isConfigured()): ?>
          <div class="alert alert-danger mt-2 mb-0" style="font-size:.78rem;">
            WhatsApp not configured — dispatches are queued/logged only.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="rfq-vendors">
    <div class="formwrap" style="flex:0 0 auto;padding-bottom:0;">
      <div class="retro-row" style="align-items:center;">
        <div class="retro-field" style="font-weight:600;">Vendors on this RFQ</div>
        <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/vendors') ?>" class="d-flex gap-2" style="margin-left:auto;">
          <?= csrf_field() ?>
          <input type="number" class="form-control form-control-sm" name="vendor_id" placeholder="Vendor ID" style="width:130px;" required>
          <button class="btn btn-sm btn-outline-dark">Add Vendor</button>
        </form>
      </div>
    </div>
    <div class="gridwrap" style="padding:0;">
      <div class="table-responsive">
        <table class="table grid mb-0">
          <thead>
            <tr><th>Vendor</th><th>WhatsApp</th><th>Sent At</th><th>Reminders</th><th>Status</th><th>Responded</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($rfqVendors)): ?><tr><td colspan="7" class="text-center text-muted">No vendors attached.</td></tr><?php endif; ?>
            <?php foreach ($rfqVendors as $v): ?>
              <tr>
                <td data-label="Vendor"><?= esc($v['company_name']) ?><br><small class="text-muted"><?= esc($v['owner_name']) ?></small></td>
                <td data-label="WhatsApp"><?= esc($v['whatsapp_no'] ?: $v['mobile']) ?></td>
                <td data-label="Sent At"><?= esc($v['sent_at'] ?? '—') ?></td>
                <td data-label="Reminders"><?= (int) $v['reminder_count'] ?></td>
                <td data-label="Status"><span class="<?= $statusColor((string) $v['response_status']) ?>"><?= esc($v['response_status']) ?></span></td>
                <td data-label="Responded"><?= esc($v['responded_at'] ?? '—') ?></td>
                <td class="text-end" data-label="Actions">
                  <?php if ($v['response_status'] === 'Pending' || $v['response_status'] === 'Failed'): ?>
                    <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/dispatch') ?>" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="only_vendor_id" value="<?= (int) $v['vendor_id'] ?>">
                      <button class="btn btn-sm btn-light" title="Send WA"><i class="bi bi-whatsapp"></i></button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/vendors/' . $v['id'] . '/remove') ?>" class="d-inline" data-confirm="Remove this vendor?">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-light"><i class="bi bi-x"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="rfq-quotes">
    <div class="formwrap" style="flex:0 0 auto;">
      <h6 class="mb-2"><i class="bi bi-pencil-square"></i> Manual Quotation Entry <small class="text-muted">For quotes received via phone/email</small></h6>
      <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/quotations/manual') ?>">
        <?= csrf_field() ?>
        <div class="row g-2">
          <div class="col-md-3">
            <select class="form-select form-select-sm" name="vendor_id" required>
              <option value="">— Vendor —</option>
              <?php foreach ($rfqVendors as $v): ?>
                <option value="<?= (int) $v['vendor_id'] ?>"><?= esc($v['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2"><input type="number" step="0.01" class="form-control form-control-sm" name="quote_amount" placeholder="Amount (INR)" required></div>
          <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="transit_days" placeholder="Transit days"></div>
          <div class="col-md-2"><input type="date" class="form-control form-control-sm" name="quote_valid_till" placeholder="Valid till"></div>
          <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="remarks" placeholder="Remarks"></div>
        </div>
        <div class="mt-2"><button class="btn btn-primary btn-sm">Save Quotation</button></div>
      </form>
    </div>
    <div class="gridwrap" style="margin-top:10px;padding:0;">
      <div class="table-responsive">
        <table class="table grid mb-0">
          <thead>
            <tr><th>#</th><th>Vendor</th><th class="text-end">Amount</th><th>Transit</th><th>Rating</th><th>Source</th><th>Score</th><th>Status</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($ranked)): ?><tr><td colspan="9" class="text-center text-muted">No quotations yet.</td></tr><?php endif; ?>
            <?php foreach ($ranked as $i => $q): ?>
              <tr <?= !empty($q['is_final_selected']) ? 'style="background:#e6f4ea;"' : '' ?>>
                <td><?= $i + 1 ?></td>
                <td><?= esc($q['company_name']) ?>
                  <?= !empty($q['is_preferred'])   ? ' <span class="badge-soft badge-ok">Preferred</span>' : '' ?>
                  <?= !empty($q['is_blacklisted']) ? ' <span class="badge-soft badge-danger">Blacklisted</span>' : '' ?>
                </td>
                <td class="text-end">₹<?= number_format((float) $q['quote_amount'], 2) ?></td>
                <td><?= esc($q['transit_days'] ?? '—') ?> d</td>
                <td><?= esc(number_format((float) $q['rating'], 1)) ?>/5</td>
                <td><span class="badge-soft"><?= esc($q['response_source']) ?></span></td>
                <td><code><?= esc($q['_score']) ?></code></td>
                <td>
                  <?php if (!empty($q['is_final_selected'])): ?>
                    <span class="badge-soft badge-ok">Awarded</span>
                  <?php elseif (!empty($q['is_shortlisted'])): ?>
                    <span class="badge-soft badge-warn">Shortlisted</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <?php if (empty($q['is_final_selected'])): ?>
                    <?php if (empty($q['is_shortlisted'])): ?>
                      <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/quotations/' . $q['id'] . '/shortlist') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-light" title="Shortlist"><i class="bi bi-bookmark"></i></button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/quotations/' . $q['id'] . '/unshortlist') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-light" title="Remove from shortlist"><i class="bi bi-bookmark-x"></i></button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/quotations/' . $q['id'] . '/select') ?>" class="d-inline" data-confirm="Award to this vendor?">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-primary" title="Award"><i class="bi bi-trophy"></i> Award</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
