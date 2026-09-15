<?= tpt_toolbar([
    'close_href'  => site_url('receipts'),
    'auth'        => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Review Import</div>
  <div class="spacer"></div>
  <?php if (!empty($proposed)): ?><div class="recordnav"><?= count($proposed) ?> rows parsed</div><?php endif; ?>
</div>

<?php if (empty($proposed)): ?>
  <div class="formwrap">
    <div class="alert alert-light border">Nothing to review. <a href="<?= site_url('receipts/import') ?>">Upload another CSV.</a></div>
  </div>
<?php else: ?>

<form method="post" action="<?= site_url('receipts/import/confirm') ?>" id="importReviewForm">
  <?= csrf_field() ?>
  <div class="formwrap" style="flex:0 0 auto;">
    <p class="text-muted mb-0">Review the matches below. <strong>Tick</strong> the rows you want to import as receipts. High-confidence matches are pre-selected.</p>
  </div>

  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mb-0">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selAllImport" aria-label="Select all"></th>
            <th>Date</th><th class="text-end">Amount</th><th>Ref / UTR</th><th>Narration</th><th>Suggested invoice</th><th>Confidence</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proposed as $i => $p):
            $autoCheck = !empty($p['invoice_id']) && $p['confidence'] >= 70 && empty($p['duplicate']);
            $cls = !empty($p['duplicate']) ? 'table-secondary'
                 : ($p['confidence'] >= 85  ? 'table-success'
                 : ($p['confidence'] >= 60  ? ''
                 : ($p['invoice_id']        ? 'table-warning'
                 :                            'table-light')));
          ?>
            <tr class="<?= $cls ?>">
              <td>
                <?php if (!empty($p['duplicate'])): ?>
                  <input type="checkbox" disabled aria-label="Duplicate — skipped">
                <?php else: ?>
                  <input type="checkbox" name="idx[]" value="<?= (int) $i ?>" <?= $autoCheck ? 'checked' : '' ?>
                         aria-label="Import row <?= (int) $i + 1 ?>">
                <?php endif; ?>
              </td>
              <td><?= esc(date('d-m-Y', strtotime($p['date']))) ?></td>
              <td class="text-end"><strong>₹<?= number_format((float) $p['amount'], 2) ?></strong></td>
              <td><code><?= esc($p['ref']) ?></code></td>
              <td style="max-width:280px;font-size:.82rem;"><?= esc(mb_substr((string) ($p['narration'] ?? ''), 0, 120)) ?></td>
              <td>
                <?php if (!empty($p['duplicate'])): ?>
                  <span class="badge bg-secondary">Duplicate ref</span>
                <?php elseif ($p['invoice_id']): ?>
                  <code><?= esc($p['invoice_no']) ?></code><br>
                  <small class="text-muted"><?= esc($p['client_company']) ?></small>
                <?php else: ?>
                  <span class="text-muted">No match — skip</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($p['duplicate'])): ?>
                  <span class="badge bg-light text-dark">—</span>
                <?php elseif ($p['confidence'] >= 85): ?>
                  <span class="badge bg-success"><?= (int) $p['confidence'] ?>%</span>
                <?php elseif ($p['confidence'] >= 60): ?>
                  <span class="badge bg-info text-dark"><?= (int) $p['confidence'] ?>%</span>
                <?php elseif ($p['invoice_id']): ?>
                  <span class="badge bg-warning text-dark"><?= (int) $p['confidence'] ?>%</span>
                <?php else: ?>
                  <span class="badge bg-light text-dark">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<div class="retro-toolbar mt-3" style="position:static;">
  <button type="submit" form="importReviewForm" class="retro-tbtn retro-primary" data-confirm="Create receipts for the selected rows?"><i class="bi bi-check2-circle"></i>Import Selected</button>
  <a class="retro-tbtn" href="<?= site_url('receipts/import') ?>"><i class="bi bi-x-circle"></i>Close</a>
</div>

<script>
document.getElementById('selAllImport')?.addEventListener('change', function (e) {
  document.querySelectorAll('input[name="idx[]"]:not([disabled])').forEach(c => c.checked = e.target.checked);
});
</script>

<?php endif; ?>
