<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

if ($id > 0) {
    $stmt = $c->prepare("SELECT pr.*, u.name AS finalised_by_name FROM payroll_runs pr LEFT JOIN users u ON u.id = pr.finalised_by WHERE pr.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $pr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM payroll_runs WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM payroll_runs WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM payroll_runs")->fetch_assoc()['c'] ?? 0;

    shell_start('General Masters', 'Payroll Run [HRMS]');
    ?>
    <div class="tabs">
      <div class="tab active">Run Summary</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="payrollruns.php">&#9776; List</a> &middot;
        Record <?= $pr ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$pr): ?>
      <div class="notfound">Payroll run not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Pay Period :</label><div class="box wide"><?= v($months[(int)$pr['pay_period_month']]) ?> <?= v($pr['pay_period_year']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($pr['run_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Total Gross :</label><div class="box"><?= money($pr['total_gross']) ?></div></div>
          <div class="field"><label>Total Deductions :</label><div class="box"><?= money($pr['total_deductions']) ?></div></div>
          <div class="field"><label>Total Net :</label><div class="box" style="font-weight:700;"><?= money($pr['total_net']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Finalised By :</label><div class="box wide empty"><?= v($pr['finalised_by_name']) ?></div></div>
          <div class="field"><label>Finalised At :</label><div class="box wide empty"><?= v($pr['finalised_at']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($pr['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM payroll_runs")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, pay_period_year, pay_period_month, run_status, total_gross, total_deductions, total_net FROM payroll_runs ORDER BY pay_period_year DESC, pay_period_month DESC");

    shell_start('General Masters', 'Payroll Run [HRMS] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Payroll Runs</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['run_status']) { 'Paid' => 'green', 'Finalised' => 'blue', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($months[(int)$r['pay_period_month']]) ?> <?= v($r['pay_period_year']) ?></td>
            <td><?= money($r['total_gross']) ?></td>
            <td><?= money($r['total_deductions']) ?></td>
            <td><?= money($r['total_net']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['run_status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
