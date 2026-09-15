<?php
$preselected = $preselected ?? 'clients';
$extra = '<a class="retro-tbtn" id="templateLink" href="' . site_url('import/clients/template') . '"><i class="bi bi-download"></i>Download template</a>';
?>
<?= tpt_toolbar([
    'save_form'   => 'importChooserForm',
    'close_href'  => site_url('dashboard'),
    'extra'       => $extra,
    'auth'        => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Import Data</div>
  <div class="spacer"></div>
</div>

<div class="formwrap">
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card"><div class="card-body">

        <form id="importChooserForm" method="post" action="<?= site_url('import') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="retro-row">
            <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">What are you importing? :</label>
              <select class="retro-box wide" name="type" id="importType" required>
                <option value="clients" <?= $preselected === 'clients' ? 'selected' : '' ?>>Clients (with GST)</option>
                <option value="vendors" <?= $preselected === 'vendors' ? 'selected' : '' ?>>Vendors / Domestic Vendor Data Sheet</option>
              </select>
            </div>
          </div>

          <div class="retro-row">
            <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Excel or CSV file :</label>
              <input class="retro-box" style="width:100%;" type="file" name="file" accept=".xlsx,.csv,.txt" required>
            </div>
          </div>
          <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-6px;">Both <strong>.xlsx</strong> and <strong>.csv</strong> work. Max 8 MB. First worksheet only for .xlsx. Column headers are matched fuzzily — order doesn't matter.</div>
        </form>

        <?php if (!empty($lastClientImport) || !empty($lastVendorImport)): ?>
        <hr>
        <div class="row g-2">
          <?php if (!empty($lastClientImport)): ?>
            <div class="col-sm-6"><div class="border rounded p-2" style="font-size:.82rem;">
              <strong>Last clients import</strong><br>
              <?= esc($lastClientImport['when']) ?> · <?= (int) $lastClientImport['created'] ?> created · <?= (int) ($lastClientImport['updated'] ?? 0) ?> updated · <?= (int) $lastClientImport['skipped'] ?> skipped
            </div></div>
          <?php endif; ?>
          <?php if (!empty($lastVendorImport)): ?>
            <div class="col-sm-6"><div class="border rounded p-2" style="font-size:.82rem;">
              <strong>Last vendors import</strong><br>
              <?= esc($lastVendorImport['when']) ?> · <?= (int) $lastVendorImport['created'] ?> created · <?= (int) ($lastVendorImport['updated'] ?? 0) ?> updated · <?= (int) $lastVendorImport['skipped'] ?> skipped
            </div></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div></div>
    </div>

    <div class="col-lg-5">
      <div class="card"><div class="card-body" style="font-size:.85rem;">
        <h6 class="mb-2">Recognised columns</h6>
        <p class="text-muted mb-2" style="font-size:.8rem;">Header names are matched fuzzily — case, spaces and punctuation are ignored. <code>"CUSTMORE NAME"</code>, <code>"Customer Name"</code> and <code>"Company"</code> all map to the same column.</p>

        <div id="cols-clients">
          <strong>Client file columns:</strong>
          <ol class="mb-2 ps-3">
            <li><code>S. No.</code></li>
            <li><code>Customer Name</code> <span class="text-danger">*required</span></li>
            <li><code>GST Number</code></li>
          </ol>
        </div>

        <div id="cols-vendors" style="display:none;">
          <strong>Vendor file columns (DOMESTIC VENDOR DATA SHEET):</strong>
          <ol class="mb-2 ps-3">
            <li><code>S.NO</code></li>
            <li><code>Vendor's Name</code> <span class="text-danger">*required</span></li>
            <li><code>Broker / Fleet Owner</code></li>
            <li><code>Vendor Address</code></li>
            <li><code>Contact Person</code></li>
            <li><code>Contact Number &amp; Email ID</code></li>
            <li><code>Open</code> (vehicle types — open body)</li>
            <li><code>Closed Body</code> (vehicle types)</li>
            <li><code>Origin</code> (Loading Point)</li>
            <li><code>Destination</code> (Offloading Point)</li>
          </ol>
        </div>

        <p class="text-muted mb-0" style="font-size:.78rem;">
          Duplicates by GSTIN or company name are detected on the next screen — you'll choose whether to <em>skip</em> or <em>update</em> them.
        </p>
      </div></div>
    </div>
  </div>
</div>

<div class="retro-toolbar mt-3" style="position:static;">
  <button type="submit" form="importChooserForm" class="retro-tbtn retro-primary"><i class="bi bi-upload"></i>Parse &amp; Preview</button>
  <a class="retro-tbtn" href="<?= site_url('dashboard') ?>"><i class="bi bi-x-circle"></i>Close</a>
</div>

<script>
(function () {
  var sel  = document.getElementById('importType');
  var tpl  = document.getElementById('templateLink');
  var cC   = document.getElementById('cols-clients');
  var cV   = document.getElementById('cols-vendors');
  var base = '<?= site_url('import') ?>';

  function sync () {
    var t = sel.value;
    tpl.href = base + '/' + t + '/template';
    tpl.innerHTML = '<i class="bi bi-download"></i> Download ' + (t === 'clients' ? 'Clients' : 'Vendors') + ' template';
    cC.style.display = (t === 'clients') ? '' : 'none';
    cV.style.display = (t === 'vendors') ? '' : 'none';
  }
  sel?.addEventListener('change', sync);
  sync();
})();
</script>
