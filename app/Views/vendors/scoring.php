<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Vendor Scorecard</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> vendors</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-0" style="font-size:.85rem;">Higher composite score = better vendor. Includes response rate, response speed, POD rate, rating, cancellation penalty, preferred/blacklist flags.</p>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr>
          <th>#</th><th>Vendor</th>
          <th class="text-end">Score</th>
          <th class="text-end">RFQs</th>
          <th class="text-end">Response %</th>
          <th class="text-end">Avg Resp</th>
          <th class="text-end">Win %</th>
          <th class="text-end">Bookings</th>
          <th class="text-end">Cancel %</th>
          <th class="text-end">POD %</th>
          <th class="text-end">Rating</th>
          <th>Flags</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="12" class="text-center text-muted">No vendors.</td></tr><?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= esc($r['company_name']) ?> <small class="text-muted"><code><?= esc($r['vendor_code']) ?></code></small></td>
            <td class="text-end"><strong><?= esc($r['composite_score']) ?></strong></td>
            <td class="text-end"><?= (int) $r['rfqs_received'] ?></td>
            <td class="text-end"><?= $r['response_rate'] !== null ? esc($r['response_rate']) . '%' : '—' ?></td>
            <td class="text-end"><?= $r['avg_resp_mins'] !== null ? esc($r['avg_resp_mins']) . ' min' : '—' ?></td>
            <td class="text-end"><?= $r['win_rate']      !== null ? esc($r['win_rate']) . '%'      : '—' ?></td>
            <td class="text-end"><?= (int) $r['bookings_count'] ?></td>
            <td class="text-end"><?= $r['cancel_rate']   !== null ? esc($r['cancel_rate']) . '%'   : '—' ?></td>
            <td class="text-end"><?= $r['pod_rate']      !== null ? esc($r['pod_rate']) . '%'      : '—' ?></td>
            <td class="text-end"><?= esc(number_format((float) $r['rating'], 1)) ?>/5</td>
            <td>
              <?php if ((int) $r['is_preferred']): ?><span class="badge-soft badge-ok">Preferred</span><?php endif; ?>
              <?php if ((int) $r['is_blacklisted']): ?><span class="badge-soft badge-danger">Blacklisted</span><?php endif; ?>
              <?php if (!(int) $r['status']): ?><span class="badge-soft">Inactive</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
