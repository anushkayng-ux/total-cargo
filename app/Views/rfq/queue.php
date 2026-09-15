<?php /** @var array $rows */ ?>
<?= tpt_toolbar([
    'new_href'       => site_url('rfq/create'),
    'new_item_label' => 'New RFQ',
    'close_href'     => site_url('dashboard'),
    'extra'          => '<a class="btn btn-sm btn-outline-dark" href="' . site_url('rfq') . '"><i class="bi bi-list-ul"></i> All RFQs</a>',
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Purchase Inbox</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> waiting</div>
</div>

<div class="formwrap" style="flex:0 0 auto;padding-bottom:0;">
  <p class="text-muted mb-0" style="font-size:.9rem;">Queries Sales has sent to Purchase that don't have an RFQ yet. Create one and dispatch to vendors in a click.</p>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid table-hover align-middle mb-0" data-tpt-cols="rfq-queue">
      <thead class="table-light">
        <tr>
          <th data-col="lead">Lead</th>
          <th data-col="route">Route</th>
          <th data-col="vehicle">Vehicle</th>
          <th data-col="material">Material / Wt</th>
          <th data-col="dispatch">Dispatch</th>
          <th data-col="priority">Priority</th>
          <th data-col="from">From</th>
          <th class="text-end" data-col="actions">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-5">
            <i class="bi bi-inbox" style="font-size:1.6rem;"></i>
            <div class="mt-2">Inbox is empty — no pending queries from Sales.</div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $route = trim(($r['pickup_city'] ?? '') . ' → ' . ($r['drop_city'] ?? ''), ' →');
              $matWt = trim(($r['material_type'] ?? '') . ' · ' . ($r['weight'] ?? '') . ' ' . ($r['weight_unit'] ?? ''), ' ·');
              $prCls = match ((string) ($r['priority'] ?? '')) {
                  'Urgent' => 'bg-danger', 'High' => 'bg-warning text-dark', default => 'bg-secondary',
              };
            ?>
            <tr class="row-link" data-href="<?= site_url('leads/' . $r['id']) ?>">
              <td data-col="lead"><a href="<?= site_url('leads/' . $r['id']) ?>"><code><?= esc($r['lead_no'] ?? ('#' . $r['id'])) ?></code></a></td>
              <td data-col="route"><?= esc($route ?: '—') ?></td>
              <td data-col="vehicle"><?= esc($r['vehicle_type_required'] ?: '—') ?></td>
              <td data-col="material"><?= esc($matWt ?: '—') ?></td>
              <td data-col="dispatch"><?= esc($r['expected_dispatch_date'] ?: '—') ?></td>
              <td data-col="priority"><?php if (!empty($r['priority'])): ?><span class="badge <?= $prCls ?>"><?= esc($r['priority']) ?></span><?php else: ?>—<?php endif; ?></td>
              <td data-col="from" class="text-muted" style="font-size:.85rem;"><?= esc($r['assigned_name'] ?: '—') ?></td>
              <td class="text-end" data-col="actions">
                <a class="btn btn-sm btn-primary" href="<?= site_url('rfq/create?lead_id=' . $r['id']) ?>">
                  <i class="bi bi-send"></i> Create RFQ &amp; Dispatch
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
