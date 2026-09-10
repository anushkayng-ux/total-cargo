<?php
use App\Controllers\QuotationsController;

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
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">RFQ <code><?= esc($row['rfq_no']) ?></code></h5>
  <span class="badge-soft"><?= esc($row['status']) ?></span>
  <span class="badge-soft">Masked Ref: <code><?= esc($row['masked_reference']) ?></code></span>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/assign') ?>" class="d-inline-flex align-items-center gap-1 ms-auto">
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
  <a class="btn btn-sm btn-light" href="<?= site_url('rfq') ?>"><i class="bi bi-arrow-left"></i> Back</a>

  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/dispatch') ?>" class="d-inline" data-confirm="Send WhatsApp to all pending vendors now?">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-primary"><i class="bi bi-whatsapp"></i> Dispatch Pending</button>
  </form>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/remind') ?>" class="d-inline" data-confirm="Send reminder to vendors who haven't responded?">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-bell"></i> Remind</button>
  </form>
  <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/close') ?>" class="d-inline" data-confirm="Close this RFQ?">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-light"><i class="bi bi-lock"></i> Close</button>
  </form>
  <?php if ($row['status'] === 'Awarded'): ?>
    <a class="btn btn-sm btn-primary" href="<?= site_url('bookings/from-rfq/' . $row['id']) ?>"><i class="bi bi-arrow-right-circle"></i> Convert to Booking</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">RFQ Details (vendor-visible)</div>
      <div class="card-body">
        <div class="row g-3" style="font-size:.92rem;">
          <div class="col-md-4"><div class="text-muted">Route</div><div><?= esc($route ?: '—') ?></div></div>
          <div class="col-md-3"><div class="text-muted">Vehicle</div><div><?= esc($row['vehicle_type']) ?></div></div>
          <div class="col-md-3"><div class="text-muted">Material</div><div><?= esc($row['material_category']) ?></div></div>
          <div class="col-md-2"><div class="text-muted">Weight</div><div><?= esc($row['weight'] . ' ' . $row['weight_unit']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Loading Date</div><div><?= esc($row['loading_date']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">From Lead</div>
            <?php if (!empty($row['lead_id'])): ?>
              <a href="<?= site_url('leads/' . $row['lead_id']) ?>"><code><?= esc($row['lead_no']) ?></code></a>
              <small class="text-muted d-block">(client hidden from vendors)</small>
            <?php else: ?>
              <span class="text-muted">Standalone</span>
            <?php endif; ?>
          </div>
          <div class="col-md-4"><div class="text-muted">Created By</div><div><?= esc($row['created_by_name'] ?? '—') ?></div></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">WhatsApp Preview</div>
      <div class="card-body">
        <?php if ($previewText): ?>
          <pre class="mb-1" style="white-space:pre-wrap;font-family:inherit;background:var(--tpt-surface);padding:10px;border-radius:10px;"><?= esc($previewText) ?></pre>
          <small class="text-muted">Template: <code><?= esc($template['template_name']) ?></code> · Language <code><?= esc($template['language_code']) ?></code></small>
        <?php else: ?>
          <div class="text-danger">Template "rfq_vendor" not found.</div>
        <?php endif; ?>
        <?php if (!$wa->isConfigured()): ?>
          <div class="alert alert-danger mt-2 mb-0" style="font-size:.82rem;">
            WhatsApp credentials not configured — dispatches will be queued and logged but not sent.
            Set <code>whatsapp.accessToken</code> and <code>whatsapp.phoneNumberId</code> in <code>.env</code> to go live.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex align-items-center gap-2 flex-wrap">
    <span>Vendors on this RFQ</span>
    <div class="ms-auto d-flex gap-2">
      <form method="post" action="<?= site_url('rfq/' . $row['id'] . '/vendors') ?>" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="number" class="form-control form-control-sm" name="vendor_id" placeholder="Vendor ID" style="width:130px;" required>
        <button class="btn btn-sm btn-outline-dark">Add Vendor</button>
      </form>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table mb-0">
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

<div class="card mb-3">
  <div class="card-header">Manual Quotation Entry <small class="text-muted">For quotes received via phone/email</small></div>
  <div class="card-body">
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
</div>

<div class="card">
  <div class="card-header">Quotations <small class="text-muted">Ranked by engine (lower score = better)</small></div>
  <div class="table-responsive">
    <table class="table mb-0">
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
