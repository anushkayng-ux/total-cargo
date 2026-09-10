<?php
$pageTitle = 'Settings';
$logoPath  = $grouped['company']['company_logo_path'] ?? '';

// Diagnostic: does the logo file actually exist on the server?
// Shown next to the Upload button so operators can confirm the file is in place
// (fixes the "logo not appearing on PDFs" cause when the DB row exists but the
// physical file is missing after a redeploy).
$logoFsPath  = $logoPath ? (FCPATH . $logoPath) : '';
$logoOnDisk  = $logoFsPath !== '' && file_exists($logoFsPath) && is_readable($logoFsPath);
$logoSizeKb  = $logoOnDisk ? round(filesize($logoFsPath) / 1024, 1) : 0;
?>
<div class="d-flex align-items-center mb-3"><h5 class="m-0"><?= esc($pageTitle) ?></h5></div>

<div class="card mb-3">
  <div class="card-header">Branding</div>
  <div class="card-body">
    <div class="row g-3 align-items-center">
      <div class="col-md-3">
        <?php if ($logoPath): ?>
          <img src="<?= base_url($logoPath) ?>" alt="Logo" style="max-height:80px;max-width:200px;background:#fff;padding:8px;border:1px solid #eee;border-radius:6px;">
          <div class="mt-2" style="font-size:.78rem;">
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
      <div class="col-md-6">
        <form method="post" action="<?= site_url('settings/logo') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <label class="form-label">Upload company logo</label>
          <input type="file" class="form-control" name="logo" accept=".png,.jpg,.jpeg,.webp" required>
          <div class="form-text" style="font-size:.78rem;">PNG / JPG / WEBP, up to 2 MB. Shown in both staff app and client portal.</div>
          <button class="btn btn-sm btn-primary mt-2">Upload</button>
        </form>
      </div>
      <?php if ($logoPath): ?>
      <div class="col-md-3">
        <form method="post" action="<?= site_url('settings/logo/remove') ?>" onsubmit="return confirm('Remove the company logo?');">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger">Remove logo</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
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
?>

<!-- ── Company Bank Details — printed on every invoice PDF ─── -->
<?php $bank = $grouped['company'] ?? []; ?>
<div class="card mb-3" id="company-bank">
  <div class="card-header"><i class="bi bi-bank"></i> Company Bank Details (prints on Invoice)</div>
  <div class="card-body">
    <form method="post" action="<?= site_url('settings') ?>" class="row g-2">
      <?= csrf_field() ?>
      <div class="col-md-4">
        <label class="form-label">Bank Name</label>
        <input class="form-control" name="s[company][bank_name]" value="<?= esc($bank['bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank">
      </div>
      <div class="col-md-4">
        <label class="form-label">Account No.</label>
        <input class="form-control" name="s[company][bank_ac]" value="<?= esc($bank['bank_ac'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">IFSC Code</label>
        <input class="form-control" name="s[company][bank_ifsc]" value="<?= esc($bank['bank_ifsc'] ?? '') ?>" style="text-transform:uppercase;">
      </div>
      <div class="col-12">
        <label class="form-label">Branch Address</label>
        <input class="form-control" name="s[company][bank_address]" value="<?= esc($bank['bank_address'] ?? '') ?>">
      </div>
      <div class="col-12">
        <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Bank Details</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Invoice Payment Terms ────────────────────────────────────
     Default block that prints on every Invoice PDF under "Payment Terms".
     Operators can still override per-invoice on the invoice form. -->
<?php
  $paymentTermsValue = trim((string) ($grouped['invoice']['payment_terms'] ?? ''));
  if ($paymentTermsValue === '') {
      $paymentTermsValue = 'Payment due within 30 days of invoice date. Kindly remit by RTGS / NEFT to the bank account below. All disputes subject to '
                        . ($grouped['company']['company_state'] ?? 'Delhi') . ' jurisdiction only.';
  }
?>
<div class="card mb-3" id="invoice-payment-terms">
  <div class="card-header"><i class="bi bi-cash-stack"></i> Invoice — Default Payment Terms</div>
  <div class="card-body">
    <p class="text-muted mb-2" style="font-size:.85rem;">
      Prints on every invoice PDF under "PAYMENT TERMS" unless the operator overrides it on that specific invoice's Payment Terms field.
    </p>
    <form method="post" action="<?= site_url('settings') ?>">
      <?= csrf_field() ?>
      <div class="mb-2">
        <label class="form-label">Default Payment Terms</label>
        <textarea class="form-control" name="s[invoice][payment_terms]" rows="4" style="font-size:.9rem;"><?= esc($paymentTermsValue) ?></textarea>
      </div>
      <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Payment Terms</button>
    </form>
  </div>
</div>

<!-- ── Quotation Terms &amp; Conditions ───────────────────────────────
     One term per line. Whatever is saved here prints on every Client
     Quotation PDF exactly as typed. Clear the box to fall back to the
     built-in default list. -->
<div class="card mb-3" id="quotation-terms">
  <div class="card-header"><i class="bi bi-file-earmark-text"></i> Quotation — Terms &amp; Conditions</div>
  <div class="card-body">
    <p class="text-muted mb-2" style="font-size:.85rem;">
      One term per line. Each line becomes a numbered point on the Client Quotation PDF.
      Empty this box + save to fall back to the built-in defaults.
    </p>
    <form method="post" action="<?= site_url('settings') ?>">
      <?= csrf_field() ?>
      <div class="mb-2">
        <label class="form-label">Terms &amp; Conditions</label>
        <textarea class="form-control" name="s[quotation][quotation_terms]" rows="12" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.85rem;line-height:1.55;"><?= esc($quotationTermsValue) ?></textarea>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Terms</button>
        <span class="text-muted" style="font-size:.78rem;">
          <?php if (!empty($grouped['quotation']['quotation_terms'])): ?>
            <i class="bi bi-check-circle-fill text-success"></i> Custom terms in use.
          <?php else: ?>
            <i class="bi bi-info-circle"></i> Using default terms until you save.
          <?php endif; ?>
        </span>
      </div>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-broadcast"></i> GPS sources &amp; priority</div>
  <div class="card-body">
    <p class="text-muted mb-3" style="font-size:.85rem;">
      Each active trip's location is pulled from these sources in order. The first to return a fresh fix wins. Driver-phone (PWA) tracking pushes coords directly and overrides polling when active.
    </p>
    <form method="post" action="<?= site_url('settings/gps') ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Priority order (CSV)</label>
          <input class="form-control" name="gps_priority" value="<?= esc($priority) ?>" placeholder="loconav,fasttag">
          <div class="form-text" style="font-size:.78rem;">Allowed values: <code>loconav</code>, <code>fasttag</code>.</div>
        </div>
      </div>

      <hr class="my-3">
      <h6 class="mb-3">FastTag provider</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Provider</label>
          <select name="fasttag_provider" class="form-select">
            <?php foreach (['generic','vamosys','roadcast','gpsgate','sastra'] as $p): ?>
              <option value="<?= $p ?>" <?= $ftagProv === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Endpoint</label>
          <input class="form-control" name="fasttag_endpoint" value="<?= esc($gps['fasttag_endpoint'] ?? '') ?>" placeholder="https://api.example.com/fastag">
        </div>
        <div class="col-md-4">
          <label class="form-label">API key / bearer token</label>
          <input class="form-control" type="password" name="fasttag_api_key" value="<?= esc($gps['fasttag_api_key'] ?? '') ?>">
        </div>
      </div>
      <div class="form-text mt-2" style="font-size:.78rem;">
        FastTag pings are sparse (toll plazas only) but cover ~95% of commercial trucks with zero driver action. Most providers use <code>GET /track?vehicle=&lt;reg&gt;</code> with a bearer token.
      </div>
      <button class="btn btn-sm btn-primary mt-3">Save GPS settings</button>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-bug"></i> Debug toolbar</div>
  <div class="card-body">
    <form method="post" action="<?= site_url('settings/debug-toolbar') ?>">
      <?= csrf_field() ?>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="debug_toolbar_enabled" value="1" id="dbgtb" <?= $debugOn ? 'checked' : '' ?>>
        <label class="form-check-label" for="dbgtb">
          Show CodeIgniter debug toolbar at the bottom of pages
        </label>
      </div>
      <div class="form-text" style="font-size:.78rem;">
        When on, the toolbar (queries, timers, vars, routes) renders only for <strong>super-admins and admins</strong> — never for regular staff or anonymous visitors.
        Also requires <code>CI_ENVIRONMENT=development</code> in <code>.env</code> to actually display, per CI4 framework safety.
      </div>
      <button class="btn btn-sm btn-primary mt-2">Save</button>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-envelope"></i> Email — Transport &amp; credentials</div>
  <div class="card-body">
    <form method="post" action="<?= site_url('settings/email') ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Driver</label>
          <select name="email_driver" class="form-select" onchange="document.querySelectorAll('.drv-block').forEach(b=>b.style.display='none');document.getElementById('drv-'+this.value).style.display='';">
            <option value="brevo" <?= $emailDriver === 'brevo' ? 'selected' : '' ?>>Brevo (HTTP API)</option>
            <option value="ses"   <?= $emailDriver === 'ses'   ? 'selected' : '' ?>>Amazon SES (HTTP API · SigV4)</option>
            <option value="smtp"  <?= $emailDriver === 'smtp'  ? 'selected' : '' ?>>SMTP (Google / generic)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">From email</label>
          <input class="form-control" name="from_email" value="<?= esc($email['from_email'] ?? '') ?>" placeholder="no-reply@yourdomain.com" required>
        </div>
        <div class="col-md-5">
          <label class="form-label">From name</label>
          <input class="form-control" name="from_name" value="<?= esc($email['from_name'] ?? '') ?>" placeholder="<?= esc(env('tpt.appName', 'TPT Logistics')) ?>">
        </div>
      </div>

      <div id="drv-brevo" class="drv-block mt-3" style="display:<?= $emailDriver === 'brevo' ? '' : 'none' ?>;">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Brevo API key</label>
            <input class="form-control" name="brevo_api_key" value="<?= esc($email['brevo_api_key'] ?? '') ?>" placeholder="xkeysib-...">
            <div class="form-text" style="font-size:.78rem;">Get from Brevo → SMTP &amp; API → API Keys.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Webhook secret (optional)</label>
            <input class="form-control" name="brevo_webhook_secret" value="<?= esc($email['brevo_webhook_secret'] ?? '') ?>" placeholder="set in Brevo webhook headers as X-Mailin-Custom">
          </div>
        </div>
        <div class="form-text mt-2" style="font-size:.78rem;">
          Webhook URL: <code><?= esc(site_url('webhooks/brevo')) ?></code> — configure in Brevo for events: <code>delivered, opened, click, hard_bounce, soft_bounce, spam, unsubscribed</code>.
        </div>
      </div>

      <div id="drv-ses" class="drv-block mt-3" style="display:<?= $emailDriver === 'ses' ? '' : 'none' ?>;">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Region</label>
            <input class="form-control" name="ses_region" value="<?= esc($email['ses_region'] ?? 'us-east-1') ?>" placeholder="us-east-1">
          </div>
          <div class="col-md-4">
            <label class="form-label">Access key</label>
            <input class="form-control" name="ses_access_key" value="<?= esc($email['ses_access_key'] ?? '') ?>" placeholder="AKIA...">
          </div>
          <div class="col-md-5">
            <label class="form-label">Secret key</label>
            <input class="form-control" name="ses_secret_key" value="<?= esc($email['ses_secret_key'] ?? '') ?>" placeholder="••••">
          </div>
          <div class="col-md-6">
            <label class="form-label">Configuration set (optional)</label>
            <input class="form-control" name="ses_configuration_set" value="<?= esc($email['ses_configuration_set'] ?? '') ?>" placeholder="enables event publishing to SNS">
          </div>
        </div>
        <div class="form-text mt-2" style="font-size:.78rem;">
          SNS topic webhook URL: <code><?= esc(site_url('webhooks/ses')) ?></code> — point your SES Configuration Set's event destination (SNS) to this URL.
        </div>
      </div>

      <div id="drv-smtp" class="drv-block mt-3" style="display:<?= $emailDriver === 'smtp' ? '' : 'none' ?>;">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Host</label><input class="form-control" name="smtp_host" value="<?= esc($email['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
          <div class="col-md-2"><label class="form-label">Port</label><input class="form-control" name="smtp_port" value="<?= esc($email['smtp_port'] ?? '587') ?>"></div>
          <div class="col-md-3"><label class="form-label">Encryption</label>
            <select class="form-select" name="smtp_crypto">
              <option value="tls" <?= ($email['smtp_crypto'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
              <option value="ssl" <?= ($email['smtp_crypto'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
              <option value=""    <?= ($email['smtp_crypto'] ?? '') === ''    ? 'selected' : '' ?>>None</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">User</label><input class="form-control" name="smtp_user" value="<?= esc($email['smtp_user'] ?? '') ?>"></div>
          <div class="col-md-12"><label class="form-label">Password / App password</label><input class="form-control" type="password" name="smtp_pass" value="<?= esc($email['smtp_pass'] ?? '') ?>"></div>
        </div>
        <div class="form-text mt-2" style="font-size:.78rem;">
          For Google: enable 2FA on the account and create an <strong>App password</strong>; use it here. Note: SMTP drivers cannot deliver bounce/complaint events natively — use Brevo or SES for full event tracking.
        </div>
      </div>

      <button class="btn btn-primary mt-3">Save email settings</button>
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

    <form method="post" action="<?= site_url('settings/email/test') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-5">
        <label class="form-label">Send test email to</label>
        <input class="form-control" type="email" name="test_to" value="<?= esc($defaultTestTo) ?>" placeholder="you@example.com" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Template (optional)</label>
        <select class="form-select" name="template_key">
          <option value="">Plain test (no template)</option>
          <?php foreach ($tplRows as $t): ?>
            <option value="<?= esc($t['template_key']) ?>"><?= esc($t['template_key']) ?> — <?= esc(mb_strimwidth($t['subject'], 0, 50, '…')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100" type="submit">
          <i class="bi bi-send-fill"></i> Send test
        </button>
      </div>
    </form>
    <small class="text-muted d-block mt-2">
      The test send writes to <code>email_logs</code>. If the driver is configured it delivers immediately; otherwise the row stays <code>Queued</code> and <code>php spark tpt:email-flush</code> will retry. If a "test override" is set, every send (including this test) is rerouted there.
    </small>
  </div>
</div>

<?php $ratingOn = ($grouped['support']['support_rating_enabled'] ?? '1') === '1'; ?>
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-life-preserver"></i> Support tickets</div>
  <div class="card-body">
    <form method="post" action="<?= site_url('settings') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="s[support][support_rating_enabled]" value="0">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="s[support][support_rating_enabled]" value="1" id="suprate" <?= $ratingOn ? 'checked' : '' ?>>
        <label class="form-check-label" for="suprate">
          Allow customers to rate support tickets after they are resolved
        </label>
      </div>
      <div class="form-text" style="font-size:.78rem;">
        When on, the reporter sees a 1–5 star rating widget on a Resolved or Closed ticket, and the support index shows an aggregate rating card to agents.
      </div>
      <button class="btn btn-sm btn-primary mt-2">Save</button>
    </form>
  </div>
</div>

<form method="post" action="<?= site_url('settings') ?>">
  <?= csrf_field() ?>
  <?php foreach ($grouped as $group => $items): if (in_array($group, ['email','system','gps','support'], true)) continue; ?>
    <div class="card mb-3">
      <div class="card-header"><?= esc(ucfirst($group)) ?></div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($items as $key => $value): ?>
            <div class="col-md-6">
              <label class="form-label"><?= esc(str_replace('_', ' ', ucwords($key, '_'))) ?></label>
              <input type="text" class="form-control" name="s[<?= esc($group) ?>][<?= esc($key) ?>]" value="<?= esc($value) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>

<?php
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
<a id="alerts"></a>
<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="bi bi-bell"></i> Alert rules</span>
    <small class="text-muted">Toggle who gets emailed at each step.</small>
  </div>
  <div class="card-body">
    <?php if (empty($grouped_rules)): ?>
      <div class="text-muted">No alert rules configured.</div>
    <?php else: ?>
      <form method="post" action="<?= site_url('settings/alerts') ?>">
        <?= csrf_field() ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
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
                      <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="<?= esc($name) ?>" value="1" <?= ((int) $r['enabled']) === 1 ? 'checked' : '' ?>>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2"></i> Save alert rules</button>
      </form>
    <?php endif; ?>
  </div>
</div>
