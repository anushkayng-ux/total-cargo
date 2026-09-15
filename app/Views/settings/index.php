<?php
$logoPath  = $grouped['company']['company_logo_path'] ?? '';

// Diagnostic: does the logo file actually exist on the server?
// Shown next to the Upload button so operators can confirm the file is in place
// (fixes the "logo not appearing on PDFs" cause when the DB row exists but the
// physical file is missing after a redeploy).
$logoFsPath  = $logoPath ? (FCPATH . $logoPath) : '';
$logoOnDisk  = $logoFsPath !== '' && file_exists($logoFsPath) && is_readable($logoFsPath);
$logoSizeKb  = $logoOnDisk ? round(filesize($logoFsPath) / 1024, 1) : 0;

$email = $grouped['email'] ?? [];
$emailDriver = $email['email_driver'] ?? 'smtp';
$debugOn = ($grouped['system']['debug_toolbar_enabled'] ?? '0') === '1';
$gps      = $grouped['gps'] ?? [];
$priority = $gps['gps_priority']     ?? 'loconav,fasttag';
$ftagProv = $gps['fasttag_provider'] ?? 'generic';

// Quotation Terms & Conditions editor value.
// Empty ⇒ the quotation PDF falls back to a built-in default list, so we
// pre-fill the textarea with that default text on first visit for easy tweaking.
$defaultQuotationTerms = "Payment shall be made after dispatch of vehicle.\n"
    . "Loading point single and unloading point single will be charged; multi-point will be extra as per distance minimum INR 2500.\n"
    . "Weight will be as mentioned above. If more than that it's subject to situation and vehicle — same vehicle will be taking or have to offload material; no labour charges will be borne by us in that scenario.\n"
    . "Payment terms will be same as mentioned above.\n"
    . "In case of holding of vehicle at loading or unloading point, detention will be charged additional to freight INR 2000 PER DAY up to 2 days, thereafter INR 2500 PER DAY.\n"
    . "In case of vehicle hold due to documentation issues, party has to ensure prompt action for release of vehicle.\n"
    . "In case of any damages to sealed and packed goods we will not be able to take any responsibility for the same.\n"
    . "If the vehicle returns without loading, dead-freight / cancellation charge as per distance will apply.\n"
    . "All disputes subject to " . ($grouped['company']['company_state'] ?? 'Delhi') . " jurisdiction only.";
$quotationTermsValue = (string) ($grouped['quotation']['quotation_terms'] ?? '');
if ($quotationTermsValue === '') $quotationTermsValue = $defaultQuotationTerms;

$bank = $grouped['company'] ?? [];

$paymentTermsValue = trim((string) ($grouped['invoice']['payment_terms'] ?? ''));
if ($paymentTermsValue === '') {
    $paymentTermsValue = 'Payment due within 30 days of invoice date. Kindly remit by RTGS / NEFT to the bank account below. All disputes subject to '
                      . ($grouped['company']['company_state'] ?? 'Delhi') . ' jurisdiction only.';
}

$ratingOn = ($grouped['support']['support_rating_enabled'] ?? '1') === '1';

// ─────────────────────────── Alert rules matrix ───────────────────────────
$alertRules = $alertRules ?? [];
$grouped_rules = [];
foreach ($alertRules as $r) {
    $grouped_rules[$r['event_key']][] = $r;
}
$eventLabels = [
    'rfq_dispatched'   => ['RFQ dispatched',     'Vendors receive the RFQ invite with their tokenized quote link.'],
    'quote_selected'   => ['Quote selected',     'Winning vendor is notified; client gets a preview of the chosen rate.'],
    'trip_assigned'    => ['Truck assigned',     'A driver + vehicle is allocated to the booking.'],
    'trip_in_transit'  => ['Trip in transit',    'Trip has departed the loading point.'],
    'trip_arrived'     => ['Trip arrived',       'Vehicle reached the destination / unloading point.'],
    'trip_delivered'   => ['Trip delivered',     'Goods unloaded; POD pending.'],
    'driver_dispatch'  => ['Driver dispatch link generated', 'Token created for the driver tracker PWA.'],
];
$audienceLabels = [
    'vendor'   => 'Vendor',
    'client'   => 'Client',
    'driver'   => 'Driver',
    'internal' => 'Internal (agency)',
];
?>
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
    'extra'      => '<span class="text-muted" style="font-size:.78rem;">Company branding, email, GPS &amp; alert configuration — each tab saves independently.</span>',
]) ?>

<div class="tabs" id="settingsTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#set-branding">Branding</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#set-company">Company</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#set-email">Email</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#set-gps">GPS Tracking</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#set-alerts">Alerts</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#set-advanced">Advanced</button>
  <div class="spacer"></div>
</div>

<div class="tab-content">

  <!-- ══════════════════════════ Branding ══════════════════════════ -->
  <div class="tab-pane fade show active" id="set-branding">
    <div class="formwrap">
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="flex-direction:column;align-items:flex-start;gap:6px;">
          <label>Current logo :</label>
          <?php if ($logoPath): ?>
            <img src="<?= base_url($logoPath) ?>" alt="Logo" style="max-height:80px;max-width:200px;background:#fff;padding:8px;border:1px solid #eee;border-radius:6px;">
            <div style="font-size:.78rem;">
              <?php if ($logoOnDisk): ?>
                <span class="text-success"><i class="bi bi-check-circle-fill"></i> On server (<?= $logoSizeKb ?> KB) — will show on PDFs</span>
              <?php else: ?>
                <span class="text-danger"><i class="bi bi-x-circle-fill"></i> File missing on server — re-upload to fix Docket/Invoice logo</span>
                <div class="text-muted" style="font-size:.72rem;">Expected at: <code><?= esc($logoPath) ?></code></div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="text-muted" style="font-size:.85rem;">No logo uploaded yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="retro-row" style="align-items:flex-end;">
        <div class="retro-field" style="width:100%;flex-direction:column;align-items:flex-start;gap:6px;">
          <form method="post" action="<?= site_url('settings/logo') ?>" enctype="multipart/form-data" style="width:100%;">
            <?= csrf_field() ?>
            <label>Upload company logo :</label>
            <input type="file" class="retro-box wide" name="logo" accept=".png,.jpg,.jpeg,.webp" required>
            <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-upload"></i> Upload</button>
            <div class="text-muted" style="font-size:.78rem;margin-top:4px;">PNG / JPG / WEBP, up to 2 MB. Shown in both staff app and client portal.</div>
          </form>
        </div>
      </div>

      <?php if ($logoPath): ?>
      <div class="retro-row">
        <form method="post" action="<?= site_url('settings/logo/remove') ?>" onsubmit="return confirm('Remove the company logo?');">
          <?= csrf_field() ?>
          <button type="submit" class="retro-tbtn retro-danger" style="width:auto;flex-direction:row;"><i class="bi bi-trash"></i> Remove logo</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══════════════════════════ Company ══════════════════════════ -->
  <div class="tab-pane fade" id="set-company">
    <div class="formwrap">

      <!-- Company Bank Details — printed on every invoice PDF -->
      <h6 class="mb-2"><i class="bi bi-bank"></i> Company Bank Details <small class="text-muted">(prints on Invoice)</small></h6>
      <form method="post" action="<?= site_url('settings') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field"><label>Bank Name :</label><input class="retro-box wide" name="s[company][bank_name]" value="<?= esc($bank['bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank"></div>
          <div class="retro-field"><label>Account No. :</label><input class="retro-box wide" name="s[company][bank_ac]" value="<?= esc($bank['bank_ac'] ?? '') ?>"></div>
          <div class="retro-field"><label>IFSC Code :</label><input class="retro-box" name="s[company][bank_ifsc]" value="<?= esc($bank['bank_ifsc'] ?? '') ?>" style="text-transform:uppercase;"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Branch Address :</label><input class="retro-box xwide" style="width:100%;" name="s[company][bank_address]" value="<?= esc($bank['bank_address'] ?? '') ?>"></div>
        </div>
        <div class="retro-row">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save Bank Details</button>
        </div>
      </form>

      <hr class="my-3">

      <!-- Invoice — Default Payment Terms -->
      <h6 class="mb-2"><i class="bi bi-cash-stack"></i> Invoice — Default Payment Terms</h6>
      <p class="text-muted mb-2" style="font-size:.85rem;">
        Prints on every invoice PDF under "PAYMENT TERMS" unless the operator overrides it on that specific invoice's Payment Terms field.
      </p>
      <form method="post" action="<?= site_url('settings') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;flex-direction:column;align-items:flex-start;gap:6px;">
            <label>Default Payment Terms :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;font-size:.9rem;" name="s[invoice][payment_terms]" rows="4"><?= esc($paymentTermsValue) ?></textarea>
          </div>
        </div>
        <div class="retro-row">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save Payment Terms</button>
        </div>
      </form>

      <hr class="my-3">

      <!-- Quotation Terms & Conditions -->
      <h6 class="mb-2"><i class="bi bi-file-earmark-text"></i> Quotation — Terms &amp; Conditions</h6>
      <p class="text-muted mb-2" style="font-size:.85rem;">
        One term per line. Each line becomes a numbered point on the Client Quotation PDF.
        Empty this box + save to fall back to the built-in defaults.
      </p>
      <form method="post" action="<?= site_url('settings') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;flex-direction:column;align-items:flex-start;gap:6px;">
            <label>Terms &amp; Conditions :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.85rem;line-height:1.55;" name="s[quotation][quotation_terms]" rows="12"><?= esc($quotationTermsValue) ?></textarea>
          </div>
        </div>
        <div class="retro-row" style="align-items:center;">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save Terms</button>
          <span class="text-muted" style="font-size:.78rem;">
            <?php if (!empty($grouped['quotation']['quotation_terms'])): ?>
              <i class="bi bi-check-circle-fill text-success"></i> Custom terms in use.
            <?php else: ?>
              <i class="bi bi-info-circle"></i> Using default terms until you save.
            <?php endif; ?>
          </span>
        </div>
      </form>

      <?php
      // Any other setting groups not given their own dedicated card above
      // (email/system/gps/support have their own tabs/forms) fall back to
      // this generic key/value editor so nothing saved in the DB is hidden.
      $otherGroups = array_diff_key($grouped, array_flip(['email', 'system', 'gps', 'support']));
      ?>
      <hr class="my-3">
      <h6 class="mb-2"><i class="bi bi-sliders"></i> Other Settings</h6>
      <form method="post" action="<?= site_url('settings') ?>">
        <?= csrf_field() ?>
        <?php foreach ($otherGroups as $group => $items): ?>
          <div style="font-weight:600;font-size:12.5px;color:var(--v2-navy-700);margin:10px 0 4px;"><?= esc(ucfirst($group)) ?></div>
          <div class="retro-row">
            <?php foreach ($items as $key => $value): ?>
              <div class="retro-field"><label><?= esc(str_replace('_', ' ', ucwords($key, '_'))) ?> :</label><input type="text" class="retro-box wide" name="s[<?= esc($group) ?>][<?= esc($key) ?>]" value="<?= esc($value) ?>"></div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <div class="retro-row">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save</button>
        </div>
      </form>

    </div>
  </div>

  <!-- ══════════════════════════ Email ══════════════════════════ -->
  <div class="tab-pane fade" id="set-email">
    <div class="formwrap">
      <form method="post" action="<?= site_url('settings/email') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field"><label>Driver :</label>
            <select name="email_driver" class="retro-box" onchange="document.querySelectorAll('.drv-block').forEach(b=>b.style.display='none');document.getElementById('drv-'+this.value).style.display='';">
              <option value="brevo" <?= $emailDriver === 'brevo' ? 'selected' : '' ?>>Brevo (HTTP API)</option>
              <option value="ses"   <?= $emailDriver === 'ses'   ? 'selected' : '' ?>>Amazon SES (HTTP API · SigV4)</option>
              <option value="smtp"  <?= $emailDriver === 'smtp'  ? 'selected' : '' ?>>SMTP (Google / generic)</option>
            </select>
          </div>
          <div class="retro-field"><label>From email :</label><input class="retro-box wide" name="from_email" value="<?= esc($email['from_email'] ?? '') ?>" placeholder="no-reply@yourdomain.com" required></div>
          <div class="retro-field"><label>From name :</label><input class="retro-box wide" name="from_name" value="<?= esc($email['from_name'] ?? '') ?>" placeholder="<?= esc(env('tpt.appName', 'TPT Logistics')) ?>"></div>
        </div>

        <div id="drv-brevo" class="drv-block" style="display:<?= $emailDriver === 'brevo' ? '' : 'none' ?>;">
          <div class="retro-row">
            <div class="retro-field"><label>Brevo API key :</label><input class="retro-box xwide" name="brevo_api_key" value="<?= esc($email['brevo_api_key'] ?? '') ?>" placeholder="xkeysib-..."></div>
            <div class="retro-field"><label>Webhook secret (optional) :</label><input class="retro-box xwide" name="brevo_webhook_secret" value="<?= esc($email['brevo_webhook_secret'] ?? '') ?>" placeholder="set in Brevo webhook headers as X-Mailin-Custom"></div>
          </div>
          <div class="text-muted" style="font-size:.78rem;">Get from Brevo → SMTP &amp; API → API Keys.</div>
          <div class="text-muted" style="font-size:.78rem;margin-top:4px;">
            Webhook URL: <code><?= esc(site_url('webhooks/brevo')) ?></code> — configure in Brevo for events: <code>delivered, opened, click, hard_bounce, soft_bounce, spam, unsubscribed</code>.
          </div>
        </div>

        <div id="drv-ses" class="drv-block" style="display:<?= $emailDriver === 'ses' ? '' : 'none' ?>;">
          <div class="retro-row">
            <div class="retro-field"><label>Region :</label><input class="retro-box" name="ses_region" value="<?= esc($email['ses_region'] ?? 'us-east-1') ?>" placeholder="us-east-1"></div>
            <div class="retro-field"><label>Access key :</label><input class="retro-box wide" name="ses_access_key" value="<?= esc($email['ses_access_key'] ?? '') ?>" placeholder="AKIA..."></div>
            <div class="retro-field"><label>Secret key :</label><input class="retro-box wide" name="ses_secret_key" value="<?= esc($email['ses_secret_key'] ?? '') ?>" placeholder="••••"></div>
          </div>
          <div class="retro-row">
            <div class="retro-field" style="width:100%;"><label>Configuration set (optional) :</label><input class="retro-box xwide" style="width:100%;" name="ses_configuration_set" value="<?= esc($email['ses_configuration_set'] ?? '') ?>" placeholder="enables event publishing to SNS"></div>
          </div>
          <div class="text-muted" style="font-size:.78rem;">
            SNS topic webhook URL: <code><?= esc(site_url('webhooks/ses')) ?></code> — point your SES Configuration Set's event destination (SNS) to this URL.
          </div>
        </div>

        <div id="drv-smtp" class="drv-block" style="display:<?= $emailDriver === 'smtp' ? '' : 'none' ?>;">
          <div class="retro-row">
            <div class="retro-field"><label>Host :</label><input class="retro-box wide" name="smtp_host" value="<?= esc($email['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
            <div class="retro-field"><label>Port :</label><input class="retro-box narrow" name="smtp_port" value="<?= esc($email['smtp_port'] ?? '587') ?>"></div>
            <div class="retro-field"><label>Encryption :</label>
              <select class="retro-box" name="smtp_crypto">
                <option value="tls" <?= ($email['smtp_crypto'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                <option value="ssl" <?= ($email['smtp_crypto'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                <option value=""    <?= ($email['smtp_crypto'] ?? '') === ''    ? 'selected' : '' ?>>None</option>
              </select>
            </div>
            <div class="retro-field"><label>User :</label><input class="retro-box wide" name="smtp_user" value="<?= esc($email['smtp_user'] ?? '') ?>"></div>
          </div>
          <div class="retro-row">
            <div class="retro-field" style="width:100%;"><label>Password / App password :</label><input class="retro-box xwide" style="width:100%;" type="password" name="smtp_pass" value="<?= esc($email['smtp_pass'] ?? '') ?>"></div>
          </div>
          <div class="text-muted" style="font-size:.78rem;">
            For Google: enable 2FA on the account and create an <strong>App password</strong>; use it here. Note: SMTP drivers cannot deliver bounce/complaint events natively — use Brevo or SES for full event tracking.
          </div>
        </div>

        <div class="retro-row" style="margin-top:6px;">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save email settings</button>
        </div>
      </form>

      <hr class="my-3">

      <?php
      // Driver readiness check — shown next to the test-send button
      $emailSvc      = new \App\Libraries\EmailService();
      $activeDriver  = $emailSvc->driver();
      $driverKey     = $activeDriver?->key() ?? '(none)';
      $driverReady   = $activeDriver !== null;
      $testOverride  = $email['test_email_override'] ?? '';
      $defaultTestTo = $testOverride ?: ($email['mail_from_email'] ?? '');
      // Pull active templates for the optional dropdown
      $tplRows = \Config\Database::connect()->table('email_templates')
          ->select('template_key, subject')
          ->where('status', 1)->where('deleted_at', null)
          ->orderBy('template_key', 'ASC')->get()->getResultArray();
      ?>
      <h6 class="mb-2"><i class="bi bi-envelope-check"></i> Test the email pipeline</h6>
      <div class="mb-2 d-flex align-items-center flex-wrap gap-2" style="font-size:.85rem;">
        <span class="text-muted">Active driver:</span>
        <?php if ($driverReady): ?>
          <span class="badge bg-success">
            <i class="bi bi-check-circle-fill"></i> <?= esc($driverKey) ?> · ready
          </span>
        <?php else: ?>
          <span class="badge bg-warning text-dark">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= esc($driverKey) ?> · not configured
          </span>
          <small class="text-muted">Fill in credentials above and save.</small>
        <?php endif; ?>
        <?php if ($testOverride): ?>
          <span class="badge bg-info ms-auto"><i class="bi bi-bullseye"></i> Override active → <?= esc($testOverride) ?></span>
        <?php endif; ?>
      </div>

      <form method="post" action="<?= site_url('settings/email/test') ?>">
        <?= csrf_field() ?>
        <div class="retro-row" style="align-items:center;">
          <div class="retro-field"><label>Send test email to :</label><input class="retro-box wide" type="email" name="test_to" value="<?= esc($defaultTestTo) ?>" placeholder="you@example.com" required></div>
          <div class="retro-field"><label>Template (optional) :</label>
            <select class="retro-box wide" name="template_key">
              <option value="">Plain test (no template)</option>
              <?php foreach ($tplRows as $t): ?>
                <option value="<?= esc($t['template_key']) ?>"><?= esc($t['template_key']) ?> — <?= esc(mb_strimwidth($t['subject'], 0, 50, '…')) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-send-fill"></i> Send test</button>
        </div>
      </form>
      <small class="text-muted d-block mt-2">
        The test send writes to <code>email_logs</code>. If the driver is configured it delivers immediately; otherwise the row stays <code>Queued</code> and <code>php spark tpt:email-flush</code> will retry. If a "test override" is set, every send (including this test) is rerouted there.
      </small>
    </div>
  </div>

  <!-- ══════════════════════════ GPS Tracking ══════════════════════════ -->
  <div class="tab-pane fade" id="set-gps">
    <div class="formwrap">
      <p class="text-muted mb-3" style="font-size:.85rem;">
        Each active trip's location is pulled from these sources in order. The first to return a fresh fix wins. Driver-phone (PWA) tracking pushes coords directly and overrides polling when active.
      </p>
      <form method="post" action="<?= site_url('settings/gps') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Priority order (CSV) :</label><input class="retro-box xwide" name="gps_priority" value="<?= esc($priority) ?>" placeholder="loconav,fasttag"></div>
        </div>
        <div class="text-muted" style="font-size:.78rem;margin-top:-6px;">Allowed values: <code>loconav</code>, <code>fasttag</code>.</div>

        <hr class="my-3">
        <h6 class="mb-3">FastTag provider</h6>
        <div class="retro-row">
          <div class="retro-field"><label>Provider :</label>
            <select name="fasttag_provider" class="retro-box">
              <?php foreach (['generic','vamosys','roadcast','gpsgate','sastra'] as $p): ?>
                <option value="<?= $p ?>" <?= $ftagProv === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Endpoint :</label><input class="retro-box xwide" name="fasttag_endpoint" value="<?= esc($gps['fasttag_endpoint'] ?? '') ?>" placeholder="https://api.example.com/fastag"></div>
          <div class="retro-field"><label>API key / bearer token :</label><input class="retro-box wide" type="password" name="fasttag_api_key" value="<?= esc($gps['fasttag_api_key'] ?? '') ?>"></div>
        </div>
        <div class="text-muted" style="font-size:.78rem;margin-top:4px;">
          FastTag pings are sparse (toll plazas only) but cover ~95% of commercial trucks with zero driver action. Most providers use <code>GET /track?vehicle=&lt;reg&gt;</code> with a bearer token.
        </div>
        <div class="retro-row" style="margin-top:10px;">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save GPS settings</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ══════════════════════════ Alerts ══════════════════════════ -->
  <div class="tab-pane fade" id="set-alerts">
    <a id="alerts"></a>
    <?php if (empty($grouped_rules)): ?>
      <div class="formwrap">
        <div class="text-muted">No alert rules configured.</div>
      </div>
    <?php else: ?>
      <!-- The <form> itself holds only the CSRF token: every checkbox and the
           submit button below reference it via the HTML5 form="" attribute
           instead of nesting inside it, so the table can be a plain gridwrap
           sibling of .formwrap (avoids the formwrap/gridwrap flex:1 stretch
           bug that a wrapping <form> would otherwise sit between). -->
      <form id="alertRulesForm" method="post" action="<?= site_url('settings/alerts') ?>"><?= csrf_field() ?></form>
      <div class="formwrap" style="flex:0 0 auto;padding-bottom:0;">
        <div class="retro-row" style="align-items:center;">
          <div class="retro-field" style="font-weight:600;">Alert rules <i class="bi bi-bell"></i></div>
          <div class="text-muted" style="font-size:.78rem;">Toggle who gets emailed at each step.</div>
        </div>
      </div>
      <div class="gridwrap" style="padding:0;">
        <div class="table-responsive">
          <table class="table grid mb-0">
            <thead>
              <tr>
                <th style="min-width:220px;">Event</th>
                <th style="min-width:120px;">Audience</th>
                <th class="text-center">Email</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($grouped_rules as $eventKey => $rules):
                  [$evLabel, $evHelp] = $eventLabels[$eventKey] ?? [$eventKey, ''];
              ?>
                <?php foreach ($rules as $i => $r):
                    $aLabel = $audienceLabels[$r['audience']] ?? $r['audience'];
                    $name   = 'alert[' . $r['event_key'] . '|' . $r['audience'] . '|' . $r['channel'] . ']';
                ?>
                  <tr>
                    <?php if ($i === 0): ?>
                      <td rowspan="<?= count($rules) ?>" style="vertical-align:top;">
                        <strong><?= esc($evLabel) ?></strong>
                        <?php if ($evHelp): ?><div class="text-muted" style="font-size:.78rem;"><?= esc($evHelp) ?></div><?php endif; ?>
                      </td>
                    <?php endif; ?>
                    <td><?= esc($aLabel) ?></td>
                    <td class="text-center">
                      <label class="retro-checkline" style="justify-content:center;">
                        <input type="checkbox" form="alertRulesForm" name="<?= esc($name) ?>" value="1" <?= ((int) $r['enabled']) === 1 ? 'checked' : '' ?>>
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="retro-toolbar" style="position:static;">
        <button type="submit" form="alertRulesForm" class="retro-tbtn retro-primary"><i class="bi bi-check2"></i>Save alert rules</button>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════ Advanced ══════════════════════════ -->
  <div class="tab-pane fade" id="set-advanced">
    <div class="formwrap">

      <h6 class="mb-2"><i class="bi bi-bug"></i> Debug toolbar</h6>
      <form method="post" action="<?= site_url('settings/debug-toolbar') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="debug_toolbar_enabled" value="1" id="dbgtb" <?= $debugOn ? 'checked' : '' ?>> Show CodeIgniter debug toolbar at the bottom of pages</label>
        </div>
        <div class="text-muted" style="font-size:.78rem;">
          When on, the toolbar (queries, timers, vars, routes) renders only for <strong>super-admins and admins</strong> — never for regular staff or anonymous visitors.
          Also requires <code>CI_ENVIRONMENT=development</code> in <code>.env</code> to actually display, per CI4 framework safety.
        </div>
        <div class="retro-row" style="margin-top:10px;">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save</button>
        </div>
      </form>

      <hr class="my-3">

      <h6 class="mb-2"><i class="bi bi-life-preserver"></i> Support tickets</h6>
      <form method="post" action="<?= site_url('settings') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="s[support][support_rating_enabled]" value="0">
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="s[support][support_rating_enabled]" value="1" id="suprate" <?= $ratingOn ? 'checked' : '' ?>> Allow customers to rate support tickets after they are resolved</label>
        </div>
        <div class="text-muted" style="font-size:.78rem;">
          When on, the reporter sees a 1–5 star rating widget on a Resolved or Closed ticket, and the support index shows an aggregate rating card to agents.
        </div>
        <div class="retro-row" style="margin-top:10px;">
          <button type="submit" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-save-fill"></i> Save</button>
        </div>
      </form>

    </div>
  </div>

</div>
