<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 2);
$period = date('F Y', mktime(0,0,0,(int)$run['pay_period_month'],1,(int)$run['pay_period_year']));
$earnings = [
    ['Basic',             $line['basic']],
    ['HRA',               $line['hra']],
    ['Special allowance', $line['special_allowance']],
    ['Conveyance',        $line['conveyance']],
    ['Medical',           $line['medical']],
    ['Other earnings',    $line['other_earnings']],
];
$deductions = [
    ['Provident Fund',    $line['pf_deduction']],
    ['ESI',               $line['esi_deduction']],
    ['Professional Tax',  $line['pt_deduction']],
    ['TDS',               $line['tds_deduction']],
    ['Loss of Pay',       $line['lop_deduction']],
    ['Other deductions',  $line['other_deductions']],
];
?>
<style>
  @media print { .no-print { display:none; } body { background:#fff; } .card { box-shadow:none; border:1px solid #ccc; } }
  .payslip-meta { font-size:.85rem; line-height:1.7; }
  .payslip-meta .label { color:#6b7280; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
  .payslip-row { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; }
  .payslip-total { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:.9rem 1rem; }
  .payslip-net { background:#065f46; color:#fff; border-radius:8px; padding:1rem 1.2rem; }
</style>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
  <h5 class="m-0"><i class="bi bi-file-earmark-text"></i> Payslip · <?= esc($user['name']) ?> · <?= esc($period) ?></h5>
  <button class="btn btn-sm btn-primary ms-auto" onclick="window.print();"><i class="bi bi-printer"></i> Print / Save PDF</button>
  <a class="btn btn-sm btn-light" href="<?= site_url('payroll/' . (int) $line['payroll_run_id']) ?>"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-start mb-3 pb-3" style="border-bottom:2px solid #0f172a;">
      <div>
        <div style="font-size:1.4rem;font-weight:700;letter-spacing:-.01em;"><?= esc($company['name']) ?></div>
        <div class="text-muted small"><?= esc($company['addr'] ?: '') ?></div>
        <?php if ($company['gstin']): ?><div class="text-muted small">GSTIN: <?= esc($company['gstin']) ?></div><?php endif; ?>
      </div>
      <div class="text-end">
        <div style="font-size:.95rem;font-weight:700;">PAYSLIP</div>
        <div class="text-muted small">Period: <strong><?= esc($period) ?></strong></div>
      </div>
    </div>

    <div class="payslip-row payslip-meta mb-4">
      <div>
        <div class="label">Employee</div>
        <div><?= esc($user['name']) ?></div>
        <?php if (!empty($profile['employee_code'])): ?><div class="text-muted small"><?= esc($profile['employee_code']) ?></div><?php endif; ?>
        <?php if (!empty($profile['designation'])):   ?><div class="text-muted small"><?= esc($profile['designation']) ?><?php if (!empty($profile['department'])): ?> · <?= esc($profile['department']) ?><?php endif; ?></div><?php endif; ?>
      </div>
      <div>
        <div class="label">Bank</div>
        <div><?= esc($profile['bank_name'] ?? '—') ?></div>
        <?php if (!empty($profile['bank_account_no'])): ?><div class="text-muted small">A/C <?= esc($profile['bank_account_no']) ?><?php if (!empty($profile['bank_ifsc'])): ?> · IFSC <?= esc($profile['bank_ifsc']) ?><?php endif; ?></div><?php endif; ?>
        <?php if (!empty($profile['pan_no'])): ?><div class="text-muted small">PAN <?= esc($profile['pan_no']) ?></div><?php endif; ?>
      </div>
      <div>
        <div class="label">Days in month</div>
        <div><?= esc((string) $line['days_in_month']) ?></div>
      </div>
      <div>
        <div class="label">Days paid</div>
        <div><?= esc((string) $line['days_paid']) ?> <span class="text-muted small">(LOP <?= esc((string) $line['lop_days']) ?>)</span></div>
      </div>
    </div>

    <div class="payslip-row mb-4">
      <div>
        <h6 class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.05em;">Earnings</h6>
        <table class="table table-sm">
          <tbody>
            <?php foreach ($earnings as $e): ?>
              <tr><td><?= esc($e[0]) ?></td><td class="text-end"><?= $fmt($e[1]) ?></td></tr>
            <?php endforeach; ?>
            <tr style="border-top:2px solid #0f172a;"><td class="fw-bold">Gross earnings</td><td class="text-end fw-bold"><?= $fmt($line['gross']) ?></td></tr>
          </tbody>
        </table>
      </div>
      <div>
        <h6 class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.05em;">Deductions</h6>
        <table class="table table-sm">
          <tbody>
            <?php foreach ($deductions as $d): ?>
              <tr><td><?= esc($d[0]) ?></td><td class="text-end"><?= $fmt($d[1]) ?></td></tr>
            <?php endforeach; ?>
            <tr style="border-top:2px solid #0f172a;"><td class="fw-bold">Total deductions</td><td class="text-end fw-bold"><?= $fmt($line['total_deductions']) ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="payslip-net d-flex justify-content-between align-items-center">
      <div>Net pay for <?= esc($period) ?></div>
      <div style="font-size:1.6rem;font-weight:700;"><?= $fmt($line['net_pay']) ?></div>
    </div>

    <div class="mt-4 text-muted small">
      <p class="mb-1">This is a computer-generated payslip. No signature is required.</p>
      <p class="mb-0">Run finalised: <?= esc($run['finalised_at'] ?? '—') ?></p>
    </div>

  </div>
</div>
