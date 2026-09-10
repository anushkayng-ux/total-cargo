<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<?= view('dispatch/_styles') ?>
<style>
  body { font-family: 'Poppins', sans-serif; }
  .tpt-toolbar {
    background: #fff; border-bottom: 1px solid #e5e5e5;
    padding: 12px 16px; position: sticky; top: 0; z-index: 20;
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  }
  .tpt-toolbar h1 { font-size: 16px; margin: 0; font-weight: 600; }
  .tpt-toolbar a, .tpt-toolbar button {
    font-size: .85rem; font-family: inherit;
    padding: 7px 14px; border-radius: 8px; cursor: pointer;
    text-decoration: none; border: 1px solid #ddd; background: #fff; color: #111;
  }
  .tpt-toolbar .primary { background: #111; color: #fff; border-color: #111; }
  .tpt-toolbar a:hover, .tpt-toolbar button:hover { background: #f4f4f6; }
  .tpt-toolbar .primary:hover { background: #333; color: #fff; }
  .selcount { font-size: .85rem; color: #444; font-weight: 500; }

  /* Dropdown for document selection */
  .docs-picker { position: relative; display: inline-block; }
  .docs-picker .picker-toggle {
    display: inline-flex; align-items: center; gap: 8px;
  }
  .docs-picker .pill {
    background: #111; color: #fff; border-radius: 999px; padding: 1px 8px; font-size: .72rem;
  }
  .docs-picker .picker-menu {
    position: absolute; top: calc(100% + 6px); right: 0;
    background: #fff; border: 1px solid #ddd; border-radius: 10px;
    min-width: 300px; padding: 8px; z-index: 30;
    box-shadow: 0 10px 30px rgba(0,0,0,.12);
    display: none;
  }
  .docs-picker.open .picker-menu { display: block; }
  .docs-picker .picker-head {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 8px 10px; border-bottom: 1px solid #eee; margin-bottom: 4px;
  }
  .docs-picker .picker-head strong { font-size: .9rem; }
  .docs-picker .picker-head button {
    padding: 3px 10px; font-size: .75rem; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-family: inherit;
  }
  .docs-picker .picker-head button:hover { background: #f4f4f6; }
  .docs-picker label.incl {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 8px; cursor: pointer; font-size: .9rem;
  }
  .docs-picker label.incl:hover { background: #f4f4f6; }
  .docs-picker label.incl input { width: 16px; height: 16px; cursor: pointer; }

  .doc-actions {
    max-width: 210mm; margin: 8mm auto -2mm;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    padding: 8px 12px;
    font-family: 'Poppins', sans-serif;
    background: #fff; border: 1px solid #e5e5e5; border-radius: 10px;
  }
  .doc-actions .title { font-weight: 600; margin-right: auto; font-size: .95rem; }
  .doc-actions a { font-size: .8rem; padding: 5px 12px; border-radius: 8px; background: #eee; color: #111; text-decoration: none; }
  .doc-actions a:hover { background: #ddd; }

  .doc-block.doc-excluded { opacity: .45; }
  .doc-block.doc-excluded::before {
    content: "Excluded from print / PDF";
    display: block; text-align: center; font-size: .85rem;
    color: #a77800; background: #fff3cd; padding: 4px 10px;
    max-width: 210mm; margin: -2mm auto 0; border-radius: 6px;
    font-family: Poppins, sans-serif;
  }

  @media print {
    .tpt-toolbar, .doc-actions { display: none !important; }
    .doc-block.doc-excluded { display: none !important; }
    .doc-block { opacity: 1 !important; }
    .doc-block.doc-excluded::before { display: none !important; }
  }
</style>
</head>
<body>

<div class="tpt-toolbar no-print">
  <a href="<?= site_url('trips/' . $trip['id']) ?>">← Back</a>
  <h1>Dispatch Pack</h1>
  <span style="color:#666;font-size:.85rem;">
    Trip <?= esc($trip['trip_no']) ?>
    · Booking <?= esc($booking['booking_no']) ?>
    <?php if (!empty($trip['lr_no'])): ?>
      · <strong style="color:#111;">LR <?= esc($trip['lr_no']) ?></strong>
    <?php else: ?>
      · <em style="color:#c00;">LR not yet assigned</em>
    <?php endif; ?>
  </span>
  <div style="flex:1"></div>

  <div class="docs-picker" id="docsPicker">
    <button type="button" class="picker-toggle" id="pickerToggle" aria-haspopup="true" aria-expanded="false">
      <i class="bi bi-list-check"></i>
      <span>Select documents</span>
      <span class="pill"><span id="selCount"><?= count($blocks) ?></span> / <?= count($blocks) ?></span>
      <i class="bi bi-chevron-down"></i>
    </button>
    <div class="picker-menu" role="menu">
      <div class="picker-head">
        <strong>Include in print / PDF</strong>
        <div style="flex:1"></div>
        <button type="button" id="selAll">All</button>
        <button type="button" id="selNone">None</button>
      </div>
      <?php foreach ($blocks as $key => $b): ?>
        <label class="incl">
          <input type="checkbox" class="incl-chk" value="<?= esc($key) ?>" checked>
          <span><?= esc($b['label']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <button type="button" id="btnPrint"><i class="bi bi-printer"></i> Print</button>
  <a id="btnPdf" class="primary" href="<?= site_url('trips/' . $trip['id'] . '/dispatch/pack.pdf') ?>" target="_blank">
    <i class="bi bi-file-earmark-pdf"></i> Download PDF
  </a>
</div>

<!-- Compact LR editor row — lives BELOW the main toolbar so it doesn't
     collide with Select documents / Print / Download PDF. LR is primarily
     entered on the Booking form; this row is for corrections. -->
<div class="no-print" style="background:#f8f9fa;border-bottom:1px solid #e5e7eb;padding:8px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
  <span style="font-size:.85rem;color:#333;font-weight:600;">
    <i class="bi bi-hash"></i> LR / Docket Number:
  </span>
  <form method="post" action="<?= site_url('trips/' . $trip['id'] . '/dispatch/lr/generate') ?>" style="display:inline-flex;gap:6px;align-items:center;">
    <?= csrf_field() ?>
    <input type="text" name="lr_no" autocomplete="off"
           value="<?= esc($trip['lr_no'] ?? '') ?>"
           placeholder="Type LR (e.g. 5013)"
           style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;font-size:.85rem;min-width:180px;">
    <button type="submit" style="padding:6px 14px;background:#111;color:#fff;border:0;border-radius:6px;font-size:.85rem;cursor:pointer;">
      <i class="bi bi-check-lg"></i> <?= !empty($trip['lr_no']) ? 'Update' : 'Save' ?>
    </button>
  </form>
  <span style="font-size:.78rem;color:#6b7280;font-style:italic;margin-left:6px;">
    Tip: LR can also be entered on the Booking form when you first create it — that value carries over here automatically.
  </span>
</div>

<?php foreach ($blocks as $key => $b): ?>
  <div class="doc-block" data-type="<?= esc($key) ?>" id="block-<?= esc($key) ?>">
    <div class="doc-actions no-print">
      <div class="title"><i class="bi bi-file-earmark-text"></i> <?= esc($b['label']) ?></div>
      <a href="<?= site_url('trips/' . $trip['id'] . '/dispatch/' . $key) ?>" target="_blank">Open in new tab</a>
      <a href="<?= site_url('trips/' . $trip['id'] . '/dispatch/' . $key . '.pdf') ?>" target="_blank">Download PDF</a>
      <?php if ($key === 'lr'):
        // Multi-select: tick one or more copies, then Download / Share.
        // - Download: fires one save per ticked copy (browser prompts once each).
        // - Share on WhatsApp: opens WhatsApp Web with a pre-filled message
        //   containing one line per ticked copy's PDF URL. Recipient taps
        //   any link to download that specific copy.
        $lrBaseUrl = site_url('trips/' . $trip['id'] . '/dispatch/lr.pdf');
        $lrLabel   = 'Docket ' . ($trip['lr_no'] ?? '#' . $trip['id']);
        $lrCopies  = [
          'consignee' => 'Consignee',
          'consignor' => 'Consignor',
          'driver'    => 'Driver',
          'record'    => 'Record',
        ];
      ?>
        <span style="color:#999;margin:0 6px;">|</span>
        <strong style="font-size:.8rem;color:#555;">LR Copies:</strong>
        <span class="lr-copy-picks" data-trip="<?= (int) $trip['id'] ?>"
              style="display:inline-flex;gap:.5rem;align-items:center;">
          <?php foreach ($lrCopies as $ck => $cl): ?>
            <label style="display:inline-flex;align-items:center;gap:.25rem;font-size:.82rem;cursor:pointer;margin:0;">
              <input type="checkbox" class="lr-copy-pick" value="<?= $ck ?>"
                     <?= $ck === 'consignee' ? 'checked' : '' ?>>
              <?= $cl ?>
            </label>
          <?php endforeach; ?>
        </span>
        <a href="#" id="lrCopyDownload-<?= (int) $trip['id'] ?>" class="btn btn-sm btn-outline-dark"
           style="padding:.15rem .55rem;font-size:.78rem;" title="Download every ticked copy">
          <i class="bi bi-download"></i> Download <span class="lr-count">1</span>
        </a>
        <a href="#" id="lrCopyShare-<?= (int) $trip['id'] ?>" class="btn btn-sm"
           style="padding:.15rem .55rem;font-size:.78rem;background:#25D366;color:#fff;border-color:#25D366;"
           target="_blank" rel="noopener" title="Share ticked copies on WhatsApp">
          <i class="bi bi-whatsapp"></i> Share on WhatsApp
        </a>
        <script>
        (function () {
          var tid    = <?= (int) $trip['id'] ?>;
          var base   = <?= json_encode($lrBaseUrl) ?>;
          var label  = <?= json_encode($lrLabel) ?>;
          var wrap   = document.querySelector('.lr-copy-picks[data-trip="' + tid + '"]');
          var dl     = document.getElementById('lrCopyDownload-' + tid);
          var sh     = document.getElementById('lrCopyShare-' + tid);
          var picks  = wrap.querySelectorAll('.lr-copy-pick');

          function selected() {
            return Array.from(picks).filter(function (cb) { return cb.checked; }).map(function (cb) {
              return { key: cb.value, label: cb.parentNode.textContent.trim() };
            });
          }
          function refreshShare() {
            var chosen = selected();
            dl.querySelector('.lr-count').textContent = chosen.length;
            dl.classList.toggle('disabled', chosen.length === 0);
            if (chosen.length === 0) {
              sh.href = '#';
              return;
            }
            var lines = chosen.map(function (c) {
              return c.label + ': ' + base + '?copy=' + encodeURIComponent(c.key);
            });
            var text = label + '\n' + lines.join('\n');
            sh.href = 'https://wa.me/?text=' + encodeURIComponent(text);
          }
          // Trigger one download per ticked copy — each gets its own filename.
          dl.addEventListener('click', function (e) {
            e.preventDefault();
            var chosen = selected();
            if (chosen.length === 0) return;
            chosen.forEach(function (c, i) {
              var a = document.createElement('a');
              a.href = base + '?copy=' + encodeURIComponent(c.key) + '&download=1';
              a.setAttribute('download', 'Docket-' + c.key + '.pdf');
              a.style.display = 'none';
              document.body.appendChild(a);
              // Stagger clicks slightly so the browser doesn't merge/cancel them
              setTimeout(function () { a.click(); a.remove(); }, i * 250);
            });
          });
          picks.forEach(function (cb) { cb.addEventListener('change', refreshShare); });
          refreshShare();
        })();
        </script>
      <?php endif; ?>
    </div>
    <?= $b['html'] ?>
  </div>
<?php endforeach; ?>

<script>
(function () {
  const totalN     = document.querySelectorAll('.incl-chk').length;
  const selCount   = document.getElementById('selCount');
  const btnPrint   = document.getElementById('btnPrint');
  const btnPdf     = document.getElementById('btnPdf');
  const btnAll     = document.getElementById('selAll');
  const btnNone    = document.getElementById('selNone');
  const picker     = document.getElementById('docsPicker');
  const toggle     = document.getElementById('pickerToggle');
  const pdfBase    = "<?= site_url('trips/' . $trip['id'] . '/dispatch/pack.pdf') ?>";
  const STORAGE_KEY = 'tpt_dispatch_sel_<?= (int) $trip['id'] ?>';

  function selected() {
    return Array.from(document.querySelectorAll('.incl-chk:checked')).map(c => c.value);
  }

  function applyState() {
    const chosen = selected();
    selCount.textContent = chosen.length;

    document.querySelectorAll('.doc-block').forEach(b => {
      b.classList.toggle('doc-excluded', !chosen.includes(b.dataset.type));
    });

    btnPrint.disabled = chosen.length === 0;
    btnPrint.style.opacity = chosen.length === 0 ? '.5' : '';

    if (chosen.length === 0) {
      btnPdf.removeAttribute('href');
      btnPdf.style.pointerEvents = 'none';
      btnPdf.style.opacity = '.5';
    } else {
      btnPdf.style.pointerEvents = '';
      btnPdf.style.opacity = '';
      btnPdf.href = (chosen.length === totalN)
        ? pdfBase
        : pdfBase + '?types=' + encodeURIComponent(chosen.join(','));
    }

    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(chosen)); } catch (e) {}
  }

  // Restore prior selection if present
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const chosen = JSON.parse(raw);
      if (Array.isArray(chosen)) {
        document.querySelectorAll('.incl-chk').forEach(c => c.checked = chosen.includes(c.value));
      }
    }
  } catch (e) {}

  // Dropdown open / close
  function openPicker(on) {
    picker.classList.toggle('open', on);
    toggle.setAttribute('aria-expanded', on ? 'true' : 'false');
  }
  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    openPicker(!picker.classList.contains('open'));
  });
  document.addEventListener('click', (e) => {
    if (!picker.contains(e.target)) openPicker(false);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') openPicker(false);
  });

  document.querySelectorAll('.incl-chk').forEach(c => c.addEventListener('change', applyState));
  btnAll .addEventListener('click', () => { document.querySelectorAll('.incl-chk').forEach(c => c.checked = true);  applyState(); });
  btnNone.addEventListener('click', () => { document.querySelectorAll('.incl-chk').forEach(c => c.checked = false); applyState(); });

  btnPrint.addEventListener('click', () => {
    if (selected().length === 0) return;
    window.print();
  });

  applyState();
})();
</script>

</body>
</html>
