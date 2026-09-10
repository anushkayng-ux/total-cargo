<?php

/**
 * @var array  $rows
 * @var object $pager
 * @var int    $unread
 */
if (!function_exists('tpt_time_ago')) {
    function tpt_time_ago(?string $dt): string
    {
        if (!$dt) return '';
        $ts = strtotime($dt);
        $diff = time() - $ts;
        if ($diff < 60)      return 'just now';
        if ($diff < 3600)    return floor($diff / 60) . 'm ago';
        if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
        if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
        return date('d-m-Y', $ts);
    }
}
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!empty($pager)): ?>
    <span class="badge bg-secondary ms-1"><?= $pager->getTotal() ?> total</span>
  <?php endif; ?>
  <?php if ((int) $unread > 0): ?>
    <span class="badge bg-danger"><?= (int) $unread ?> unread</span>
    <form method="post" action="<?= site_url('notifications/read-all') ?>" class="ms-auto">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-light"><i class="bi bi-check2-all"></i> Mark all read</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="list-group list-group-flush">
    <?php if (empty($rows)): ?>
      <div class="list-group-item text-center text-muted py-5">
        <i class="bi bi-bell-slash" style="font-size:1.6rem;"></i>
        <div class="mt-2">No notifications yet.</div>
      </div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <a href="<?= site_url('notifications/' . $r['id'] . '/read') ?>"
           class="list-group-item list-group-item-action d-flex align-items-start gap-3 <?= empty($r['is_read']) ? 'bg-light' : '' ?>">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:36px;height:36px;background:<?= empty($r['is_read']) ? '#e7f1ff' : '#f1f3f5' ?>;">
            <i class="bi bi-<?= esc($r['icon'] ?: 'bell') ?>" style="color:#3b82f6;"></i>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-semibold"><?= esc($r['title']) ?></span>
              <?php if (empty($r['is_read'])): ?><span class="badge bg-primary" style="font-size:.6rem;">NEW</span><?php endif; ?>
              <span class="text-muted ms-auto" style="font-size:.78rem;"><?= tpt_time_ago($r['created_at']) ?></span>
            </div>
            <?php if (!empty($r['body'])): ?>
              <div class="text-muted" style="font-size:.88rem;"><?= esc($r['body']) ?></div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($pager)): ?>
  <div class="d-flex justify-content-center mt-3"><?= $pager->links() ?></div>
<?php endif; ?>
