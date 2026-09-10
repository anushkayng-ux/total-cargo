<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($appName ?? 'Notification') ?></title>
<style>
  /* Inlined for max client compatibility */
  body { margin:0; padding:0; background:#f4f4f4; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color:#333; line-height:1.5; }
  .wrap { width:100%; background:#f4f4f4; padding:24px 12px; }
  .container { max-width:600px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
  .header { background:#1a1a1a; color:#ffffff; padding:20px 24px; }
  .header img { display:block; max-height:36px; max-width:200px; }
  .header h1 { margin:0; font-size:18px; font-weight:600; color:#ffffff; }
  .content { padding:28px 24px; color:#222; font-size:15px; }
  .content h2 { font-size:18px; margin:0 0 12px 0; color:#111; }
  .content p { margin:0 0 14px 0; }
  .btn { display:inline-block; padding:11px 22px; background:#1a1a1a; color:#ffffff !important; text-decoration:none; border-radius:6px; font-weight:600; font-size:14px; }
  .meta-card { background:#fafafa; border:1px solid #eee; border-radius:6px; padding:14px 16px; margin:16px 0; font-size:14px; }
  .meta-row { display:block; padding:3px 0; }
  .meta-label { color:#777; display:inline-block; min-width:120px; }
  .footer { padding:18px 24px; background:#fafafa; border-top:1px solid #eee; font-size:12px; color:#888; text-align:center; }
  .footer a { color:#666; text-decoration:underline; }
  .preview { display:none; max-height:0; overflow:hidden; }
  @media (max-width:600px) {
    .content { padding:20px 16px; }
    .header { padding:16px 18px; }
  }
</style>
</head>
<body>
<div class="preview"><?= esc($appName ?? '') ?> notification</div>
<div class="wrap">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr><td align="center">
      <div class="container">
        <div class="header">
          <?php if (!empty($logoUrl)): ?>
            <img src="<?= esc($logoUrl) ?>" alt="<?= esc($appName) ?>">
          <?php else: ?>
            <h1><?= esc($appName ?? 'TPT Logistics') ?></h1>
          <?php endif; ?>
        </div>
        <div class="content">
          <?= $content /* Already trusted HTML from template (admin-managed) */ ?>
        </div>
        <div class="footer">
          <?php if (!empty($companyAddr)): ?>
            <div style="margin-bottom:8px;"><?= esc($companyAddr) ?></div>
          <?php endif; ?>
          <div>
            <a href="<?= esc($viewUrl) ?>">View in browser</a>
            &middot;
            <a href="<?= esc($unsubUrl) ?>">Unsubscribe</a>
          </div>
          <div style="margin-top:8px;color:#bbb;">
            Sent to <?= esc($toEmail) ?>
          </div>
        </div>
      </div>
    </td></tr>
  </table>
</div>
</body>
</html>
