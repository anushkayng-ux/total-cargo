<?php
/**
 * Reports hub — cards are auto-filtered by underlying module permission so
 * each role sees only the reports it has data access to.
 *
 *   Purchase has vendors + vendor_bills  → sees Payables Aging + Vendor Scorecard.
 *   Accounts has invoices + vendor_bills → sees Receivables + Payables.
 *   Admin / Management with reports      → sees everything.
 *
 *  @var \App\Libraries\Auth $auth
 */
$cards = [
    ['mod' => 'report_email_analytics',    'url' => 'reports/email-analytics',   'tag' => 'EMAIL',       'icon' => 'envelope-paper',     'title' => 'Email Analytics',           'note' => 'Sent, delivered, open / click rates, bounces, complaints, per-template performance.'],
    ['mod' => 'report_sop_kpis',           'url' => 'reports/sop-kpis',          'tag' => 'SOP / SLA',   'icon' => 'stopwatch',          'title' => 'SOP / TAT KPIs',            'note' => 'Turnaround for each SOP step + per-user scorecard.'],
    ['mod' => 'report_profitability',      'url' => 'reports/profitability',     'tag' => 'OPERATIONS',  'icon' => 'graph-up-arrow',     'title' => 'Profitability',             'note' => 'Booking-level buy vs sell, margin, margin %, with client/vendor/date filters.'],
    ['mod' => 'report_receivables',        'url' => 'reports/receivables',       'tag' => 'CASH IN',     'icon' => 'hourglass-split',    'title' => 'Receivables Aging',         'note' => 'Outstanding client invoices bucketed into current / 0-30 / 31-60 / 61-90 / 90+.'],
    ['mod' => 'report_payables',           'url' => 'reports/payables',          'tag' => 'CASH OUT',    'icon' => 'hourglass-top',      'title' => 'Payables Aging',            'note' => 'Pending vendor bills bucketed by overdue age, with vendor grouping.'],
    ['mod' => 'report_lead_funnel',        'url' => 'reports/lead-funnel',       'tag' => 'CRM',         'icon' => 'funnel',             'title' => 'Lead Funnel',               'note' => 'Lead counts at every workflow stage from New through Won/Lost.'],
    ['mod' => 'report_vendor_scoring',     'url' => 'vendors/scoring',           'tag' => 'PROCUREMENT', 'icon' => 'trophy',             'title' => 'Vendor Scorecard',          'note' => 'Composite vendor score with response rate, win rate, cancellation, POD.'],
    ['mod' => 'report_trip_expenses',      'url' => 'reports/trip-expenses',     'tag' => 'OPERATIONS',  'icon' => 'wallet2',            'title' => 'Trip Expenses',             'note' => 'All trip-level costs — toll, bhatta, loading, detention. Filter by category / vendor / date.'],
    ['mod' => 'report_unbilled_billable',  'url' => 'reports/unbilled-billable', 'tag' => 'LEAKAGE',     'icon' => 'exclamation-triangle','title' => 'Unbilled Billable Expenses', 'note' => 'Client-reimbursable charges recorded but not yet invoiced — margin to recover.'],
    ['mod' => 'report_expense_categories', 'url' => 'reports/expense-categories','tag' => 'OPERATIONS',  'icon' => 'pie-chart',          'title' => 'Expense Category Summary',  'note' => 'Where costs flow vs recovered. Shows recovery % per category.'],
];

$visible = array_values(array_filter($cards, fn ($c) => $auth->can($c['mod'], 'can_view')));
?>
<div class="d-flex align-items-center mb-3 gap-2">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!empty($visible)): ?>
    <span class="badge bg-secondary"><?= count($visible) ?> available</span>
  <?php endif; ?>
</div>

<?php if (empty($visible)): ?>
  <div class="card">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-bar-chart" style="font-size:1.6rem;"></i>
      <div class="mt-2">No reports are available with your current permissions.</div>
      <div class="mt-1" style="font-size:.85rem;">Ask your admin to grant View on the modules whose reports you need (e.g. Vendor Bills for Payables Aging).</div>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($visible as $c): ?>
      <div class="col-md-6 col-lg-4">
        <a class="card d-block text-decoration-none" href="<?= site_url($c['url']) ?>">
          <div class="card-body">
            <div class="text-muted mb-1" style="font-size:.8rem;"><?= esc($c['tag']) ?></div>
            <h6 class="mb-1"><i class="bi bi-<?= esc($c['icon']) ?>"></i> <?= esc($c['title']) ?></h6>
            <div class="text-muted" style="font-size:.85rem;"><?= esc($c['note']) ?></div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
