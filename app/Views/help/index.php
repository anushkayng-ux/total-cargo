<?php
$totalTopics = array_sum(array_map('count', $byCategory));
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input class="form-control form-control-sm" style="width:220px;" type="text" name="q" value="' . esc($search) . '" placeholder="Search help topics">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-search"></i></button>';
if ($search !== '') {
    $extra .= '<a class="btn btn-sm btn-light" href="' . site_url('help') . '">Clear</a>';
}
$extra .= '</form>';
if (!empty($isAdmin)) {
    $extra .= '<a class="btn btn-sm btn-light" href="' . site_url('help/admin') . '"><i class="bi bi-pencil-square"></i> Manage Topics</a>';
}
?>
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Help Topics</div>
  <div class="spacer"></div>
  <?php if (!empty($byCategory)): ?><div class="recordnav"><?= $totalTopics ?> topic<?= $totalTopics === 1 ? '' : 's' ?></div><?php endif; ?>
</div>

<div class="formwrap">
  <?php if (empty($byCategory)): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-life-preserver" style="font-size:1.6rem;"></i>
      <div class="mt-2">No help topics yet<?= $search !== '' ? ' for "<strong>' . esc($search) . '</strong>"' : '' ?>.</div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($byCategory as $cat => $topics): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100"><div class="card-body">
            <h6 class="mb-2"><i class="bi bi-folder2-open"></i> <?= esc($cat) ?></h6>
            <ul class="list-unstyled mb-0">
              <?php foreach ($topics as $t): ?>
                <li class="mb-1"><a href="<?= site_url('help/' . $t['slug']) ?>"><i class="bi bi-file-text"></i> <?= esc($t['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
