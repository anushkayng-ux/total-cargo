<?php
/**
 * Bootstrap 5 pagination template — replaces CI4's default which emits bare
 * <li>/<a> without `page-item`/`page-link` classes, leaving links unstyled.
 *
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(2);
$total   = method_exists($pager, 'getTotal')       ? $pager->getTotal()       : null;
$pageCnt = method_exists($pager, 'getPageCount')   ? $pager->getPageCount()   : null;
$current = method_exists($pager, 'getCurrentPage') ? $pager->getCurrentPage() : null;
?>
<nav aria-label="Page navigation" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
  <?php if ($pageCnt !== null && $current !== null): ?>
    <div class="text-muted small">
      Page <strong><?= $current ?></strong> of <strong><?= $pageCnt ?></strong>
      <?php if ($total !== null): ?> · <strong><?= number_format($total) ?></strong> total<?php endif; ?>
    </div>
  <?php endif; ?>
  <ul class="pagination pagination-sm mb-0">
    <?php if ($pager->hasPrevious()): ?>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="First">&laquo;</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Previous">&lsaquo;</a>
      </li>
    <?php else: ?>
      <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
      <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
    <?php endif ?>

    <?php foreach ($pager->links() as $link): ?>
      <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
        <?php if ($link['active']): ?>
          <span class="page-link"><?= $link['title'] ?></span>
        <?php else: ?>
          <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
        <?php endif; ?>
      </li>
    <?php endforeach ?>

    <?php if ($pager->hasNext()): ?>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Next">&rsaquo;</a>
      </li>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Last">&raquo;</a>
      </li>
    <?php else: ?>
      <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
      <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
    <?php endif ?>
  </ul>
</nav>
