<?php
$extra = '<div class="d-flex align-items-center gap-2">'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . ($year - 1) . '">&laquo; ' . ($year - 1) . '</a>'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . (int) date('Y') . '">' . (int) date('Y') . '</a>'
    . '<a class="btn btn-sm btn-outline-dark" href="?y=' . ($year + 1) . '">' . ($year + 1) . ' &raquo;</a>'
    . '</div>';

echo tpt_toolbar([
    'new_href'       => '#add-holiday-form',
    'new_item_label' => 'Add Holiday',
    'close_href'     => site_url('hrms/team'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Holiday Calendar — <?= esc((string) $year) ?></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> holidays</div>
</div>

<div class="formwrap" id="add-holiday-form" style="flex:0 0 auto;">
  <form method="post" action="<?= site_url('hrms/holidays') ?>">
    <?= csrf_field() ?>
    <div class="retro-row" style="align-items:flex-end;">
      <div class="retro-field"><label>Date <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="holiday_date" required></div>
      <div class="retro-field" style="width:32%;"><label>Name <span class="retro-required">*</span> :</label><input class="retro-box xwide" style="width:100%;" type="text" name="name" placeholder="e.g. Diwali, Pongal" maxlength="120" required></div>
      <div class="retro-field"><label>Pay Status :</label>
        <select class="retro-box" name="pay_status">
          <option value="paid" selected>Paid (default)</option>
          <option value="unpaid">Unpaid</option>
          <option value="optional">Optional / restricted</option>
        </select>
      </div>
      <div class="retro-field">
        <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-plus-lg"></i>Add</button>
      </div>
    </div>
    <div class="retro-row" style="margin-top:-4px;margin-bottom:0;">
      <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;max-width:760px;">Paid holidays don't deduct from salary; unpaid does. Optional is treated as paid by payroll but flagged in attendance. Holidays count as paid days during payroll — employees who punch in on a holiday still get the day credited. Weekends are auto-detected separately.</div>
    </div>
  </form>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid align-middle mb-0" data-tpt-cols="holidays">
      <thead><tr><th data-col="date">Date</th><th data-col="day">Day</th><th data-col="name">Name</th><th data-col="pay">Pay</th><th data-col="actions" class="text-end"></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="text-center text-muted py-3">No holidays yet for <?= esc((string) $year) ?>. Use the form above to add one.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $t = strtotime($r['holiday_date']);
        ?>
          <tr>
            <td data-col="date"><?= esc(date('d-m-Y', $t)) ?></td>
            <td data-col="day"><?= esc(date('D', $t)) ?></td>
            <td data-col="name"><strong><?= esc($r['name']) ?></strong></td>
            <td data-col="pay">
              <?php
              $cls = match($r['pay_status']) { 'paid' => 'badge-ok', 'unpaid' => 'badge-danger', 'optional' => 'badge-warn', default => '' };
              ?>
              <span class="badge-soft <?= $cls ?>"><?= esc($r['pay_status']) ?></span>
            </td>
            <td data-col="actions" class="text-end">
              <form method="post" action="<?= site_url('hrms/holidays/' . (int) $r['id'] . '/delete') ?>" class="d-inline" data-confirm="Remove this holiday?">
                <?= csrf_field() ?>
                <button class="retro-tbtn" style="width:auto;flex-direction:row;padding:2px 8px;"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
