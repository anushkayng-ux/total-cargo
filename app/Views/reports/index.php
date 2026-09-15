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
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Reports</div>
  <div class="spacer"></div>
  <?php if (!empty($visible)): ?><div class="recordnav"><?= count($visible) ?> available</div><?php endif; ?>
</div>

<div class="formwrap">
  <?php if (empty($visible)): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-bar-chart" style="font-size:1.6rem;"></i>
      <div class="mt-2">No reports are available with your current permissions.</div>
      <div class="mt-1" style="font-size:.85rem;">Ask your admin to grant View on the modules whose reports you need (e.g. Vendor Bills for Payables Aging).</div>
    </div>
  <?php else: ?>
    <div class="masterlinks">
      <?php foreach ($visible as $c): ?>
        <a class="mastercard" href="<?= site_url($c['url']) ?>">
          <div class="mastercard-icon"><i class="bi bi-<?= esc($c['icon']) ?>"></i></div>
          <div class="mastercard-label"><?= esc($c['title']) ?></div>
          <div class="mastercard-desc"><?= esc($c['tag']) ?> — <?= esc($c['note']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
