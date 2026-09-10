<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <span class="text-muted ms-2" style="font-size:.85rem;">Where the margin is leaking vs. where it's being recovered.</span>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('reports') ?>"><i class="bi bi-arrow-left"></i> All Reports</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>Category</th><th class="text-end">Entries</th><th class="text-end">Internal</th><th class="text-end">Billable</th><th class="text-end">Unbilled</th><th class="text-end">Total</th><th>Recovery</th></tr>
      </thead>
      <tbody>
        <?php
          $tot = ['internal' => 0, 'billable' => 0, 'unbilled' => 0, 'total' => 0, 'count' => 0];
          foreach ($rows as $r) {
              $tot['internal'] += (float) $r['internal'];
              $tot['billable'] += (float) $r['billable'];
              $tot['unbilled'] += (float) $r['unbilled'];
              $tot['total']    += (float) $r['total'];
              $tot['count']    += (int)   $r['count'];
          }
        ?>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No expenses recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $billed = (float) $r['billable'] - (float) $r['unbilled'];
          $recovery = (float) $r['billable'] > 0 ? round($billed * 100 / (float) $r['billable'], 1) : null;
        ?>
          <tr>
            <td data-label="Category"><strong><?= esc($r['category']) ?></strong></td>
            <td class="text-end" data-label="Entries"><?= (int) $r['count'] ?></td>
            <td class="text-end" data-label="Internal"><?= (float) $r['internal'] > 0 ? '₹' . number_format((float) $r['internal'], 0) : '—' ?></td>
            <td class="text-end" data-label="Billable"><?= (float) $r['billable'] > 0 ? '₹' . number_format((float) $r['billable'], 0) : '—' ?></td>
            <td class="text-end" data-label="Unbilled"><?= (float) $r['unbilled'] > 0 ? '<span class="text-danger">₹' . number_format((float) $r['unbilled'], 0) . '</span>' : '—' ?></td>
            <td class="text-end" data-label="Total">₹<?= number_format((float) $r['total'], 0) ?></td>
            <td data-label="Recovery"><?= $recovery === null ? '—' : esc($recovery) . '%' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
      <tfoot>
        <tr>
          <th>Total</th>
          <th class="text-end"><?= $tot['count'] ?></th>
          <th class="text-end">₹<?= number_format($tot['internal'], 0) ?></th>
          <th class="text-end">₹<?= number_format($tot['billable'], 0) ?></th>
          <th class="text-end">₹<?= number_format($tot['unbilled'], 0) ?></th>
          <th class="text-end">₹<?= number_format($tot['total'], 0) ?></th>
          <th></th>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
