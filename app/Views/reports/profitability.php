<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('reports') ?>"><i class="bi bi-arrow-left"></i> All Reports</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="<?= site_url('reports/profitability') ?>">
      <div class="col-md-2"><label class="form-label">From</label>
        <input type="date" class="form-control form-control-sm" name="from" value="<?= esc($filters['from']) ?>"></div>
      <div class="col-md-2"><label class="form-label">To</label>
        <input type="date" class="form-control form-control-sm" name="to" value="<?= esc($filters['to']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Client</label>
        <select class="form-select form-select-sm" name="client_id">
          <option value="">All</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int)$filters['client_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-3"><label class="form-label">Vendor</label>
        <select class="form-select form-select-sm" name="vendor_id">
          <option value="">All</option>
          <?php foreach ($vendors as $v): ?>
            <option value="<?= $v['id'] ?>" <?= (int)$filters['vendor_id'] === (int)$v['id'] ? 'selected' : '' ?>><?= esc($v['company_name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-2"><label class="form-label">Status</label>
        <select class="form-select form-select-sm" name="status">
          <option value="">All</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= esc($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-12"><button class="btn btn-sm btn-primary">Apply</button>
        <a class="btn btn-sm btn-light" href="<?= site_url('reports/profitability') ?>">Reset</a></div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-2"><div class="stat"><span class="label">Sell</span><span class="value">₹<?= number_format((float) $totals['sell'], 0) ?></span></div></div>
  <div class="col-6 col-md-2"><div class="stat"><span class="label">Buy</span><span class="value">₹<?= number_format((float) $totals['buy'], 0) ?></span></div></div>
  <div class="col-6 col-md-2"><div class="stat"><span class="label">Booked Margin</span><span class="value">₹<?= number_format((float) $totals['margin'], 0) ?></span></div></div>
  <div class="col-6 col-md-2"><div class="stat"><span class="label">Internal Exp</span><span class="value">₹<?= number_format((float) ($totals['internal_exp'] ?? 0), 0) ?></span></div></div>
  <div class="col-6 col-md-2"><div class="stat"><span class="label">Billable Recovered</span><span class="value">₹<?= number_format((float) ($totals['billable_recovered'] ?? 0), 0) ?></span></div></div>
  <div class="col-6 col-md-2"><div class="stat"><span class="label">True Margin</span><span class="value">₹<?= number_format((float) ($totals['true_margin'] ?? 0), 0) ?></span><small class="text-muted" style="font-size:.7rem;"><?= esc($totals['true_margin_pct'] ?? 0) ?>%</small></div></div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>Booking</th><th>Route</th><th>Client</th><th>Vendor</th><th class="text-end">Buy</th><th class="text-end">Sell</th><th class="text-end">Margin</th><th class="text-end">Int Exp</th><th class="text-end">Recov.</th><th class="text-end">True M.</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="11" class="text-center text-muted">No bookings match.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $buy = (float) $r['final_buy_rate']; $sell = (float) $r['final_sell_rate'];
          $trueM = (float) ($r['true_margin'] ?? $r['margin_amount']);
        ?>
          <tr>
            <td data-label="Booking"><a href="<?= site_url('bookings/' . $r['id']) ?>"><code><?= esc($r['booking_no']) ?></code></a>
              <?php if ($r['trip_no']): ?><br><small class="text-muted"><?= esc($r['trip_no']) ?></small><?php endif; ?>
            </td>
            <td data-label="Route"><?= esc($r['route_text']) ?><br><small class="text-muted"><?= esc($r['loading_date']) ?></small></td>
            <td data-label="Client"><?= esc($r['client_name']) ?></td>
            <td data-label="Vendor"><?= esc($r['vendor_name']) ?></td>
            <td class="text-end" data-label="Buy">₹<?= number_format($buy, 0) ?></td>
            <td class="text-end" data-label="Sell">₹<?= number_format($sell, 0) ?></td>
            <td class="text-end" data-label="Margin">₹<?= number_format((float) $r['margin_amount'], 0) ?></td>
            <td class="text-end" data-label="Int Exp"><?php $ie = (float) ($r['internal_expenses'] ?? 0); ?><?= $ie > 0 ? '<span class="text-danger">₹' . number_format($ie, 0) . '</span>' : '—' ?></td>
            <td class="text-end" data-label="Recov."><?php $br = (float) ($r['billed_recovered'] ?? 0); ?><?= $br > 0 ? '₹' . number_format($br, 0) : '—' ?></td>
            <td class="text-end" data-label="True M."><strong>₹<?= number_format($trueM, 0) ?></strong></td>
            <td data-label="Status"><span class="badge-soft"><?= esc($r['booking_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
