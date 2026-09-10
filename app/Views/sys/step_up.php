<h5 class="mb-3"><i class="bi bi-shield-check"></i> Re-confirm your password</h5>
<p class="text-muted" style="font-size:.9rem;">This action requires a recent password confirmation. Step-up is valid for 10 minutes.</p>

<div class="card" style="max-width:480px;">
  <div class="card-body">
    <form method="post" action="<?= site_url('sys/step-up') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= esc($next) ?>">
      <div class="mb-3">
        <label class="form-label">Your super-admin password</label>
        <input type="password" class="form-control" name="password" required autocomplete="current-password" autofocus>
      </div>
      <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Confirm</button>
      <a href="<?= site_url('sys') ?>" class="btn btn-light ms-1">Cancel</a>
    </form>
  </div>
</div>
