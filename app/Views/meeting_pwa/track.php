<?php
$eventDefs = [
    'depart_office'  => ['label' => 'Depart office',     'sub' => 'Tap when you leave for the meeting',  'icon' => 'bi-car-front-fill',  'num' => 1],
    'arrive_meeting' => ['label' => 'Arrived at meeting','sub' => 'Tap when you reach the location',     'icon' => 'bi-geo-alt-fill',    'num' => 2],
    'leave_meeting'  => ['label' => 'Leave meeting',     'sub' => 'Tap when the meeting is over',        'icon' => 'bi-door-open-fill',  'num' => 3],
    'return_office'  => ['label' => 'Back at office',    'sub' => 'Tap when you return to the office',   'icon' => 'bi-building',        'num' => 4],
];
$byType = [];
foreach ($events as $e) $byType[$e['event_type']] = $e;
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
<meta name="theme-color" content="#0b0f17" id="themeColorMeta">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="robots" content="noindex,nofollow">
<title>Meeting · <?= esc($meeting['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
  (function () {
    var KEY = 'tpt_theme_meeting';
    var pref = null;
    try { pref = localStorage.getItem(KEY); } catch (e) {}
    var dark = pref === 'dark' || (pref !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  })();
</script>
<style>
  :root {
    --pri:#2563eb; --pri-hover:#1d4ed8; --pri-soft:#60a5fa; --accent:#10b981; --danger:#dc2626;
    --bar-h:56px;
    --r-sm:8px; --r-md:12px; --r-lg:16px;
    --t-fast:.15s; --t-med:.22s;
  }
  html[data-theme="dark"] {
    --bg:#0b0f17; --bg-elev:#11161f;
    --card:#161c27; --surface-2:#1c2330; --surface-3:#252e3d;
    --br:#232a36; --br-soft:#1c2330;
    --txt:#e8eaed; --txt-soft:#cbd1da; --muted:#9aa3b2; --muted-2:#6b7280;
    --grip:#3a4250;
    --ok-bg:#0b3a2c; --ok-br:#166534; --ok-fg:#4ade80;
    --warn-bg:#3a2a0b; --warn-br:#a16207; --warn-fg:#fbbf24;
    --err-bg:#3b1212; --err-br:#b91c1c; --err-fg:#fca5a5;
    --info-bg:#0b2540; --info-br:#1e40af; --info-fg:#60a5fa;
    --backdrop:rgba(2,6,14,.65);
    --shadow-sm:0 1px 2px rgba(0,0,0,.4), 0 1px 1px rgba(0,0,0,.3);
    --shadow-md:0 4px 12px rgba(0,0,0,.4);
    --shadow-lg:0 12px 32px rgba(0,0,0,.55);
  }
  html[data-theme="light"] {
    --bg:#f4f6fa; --bg-elev:#ffffff;
    --card:#ffffff; --surface-2:#f3f5f9; --surface-3:#e8ecf2;
    --br:#e3e7ee; --br-soft:#eef1f5;
    --txt:#0f172a; --txt-soft:#374151; --muted:#6b7280; --muted-2:#9ca3af;
    --grip:#cbd5e1;
    --ok-bg:#dcfce7; --ok-br:#86efac; --ok-fg:#065f46;
    --warn-bg:#fef3c7; --warn-br:#fcd34d; --warn-fg:#92400e;
    --err-bg:#fee2e2; --err-br:#fca5a5; --err-fg:#991b1b;
    --info-bg:#dbeafe; --info-br:#bfdbfe; --info-fg:#1e40af;
    --backdrop:rgba(15,23,42,.45);
    --shadow-sm:0 1px 2px rgba(15,23,42,.06), 0 1px 1px rgba(15,23,42,.04);
    --shadow-md:0 4px 12px rgba(15,23,42,.08);
    --shadow-lg:0 12px 32px rgba(15,23,42,.15);
  }
  * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  html, body { margin:0; padding:0; background:var(--bg); color:var(--txt); min-height:100vh; }
  body {
    font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    font-size:15px; line-height:1.45; letter-spacing:-.01em;
    -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;
    padding-bottom:env(safe-area-inset-bottom);
    transition: background var(--t-fast), color var(--t-fast);
  }
  button { font-family: inherit; }
  ::-webkit-scrollbar { width:6px; }
  ::-webkit-scrollbar-thumb { background:var(--surface-3); border-radius:3px; }

  /* ─── App bar ─── */
  .app-bar {
    position:sticky; top:0; z-index:80;
    background:var(--card); border-bottom:1px solid var(--br);
    display:flex; align-items:center; gap:.3rem;
    padding:0 .5rem;
    height:calc(var(--bar-h) + env(safe-area-inset-top));
    padding-top:env(safe-area-inset-top);
    backdrop-filter:saturate(180%) blur(8px);
    -webkit-backdrop-filter:saturate(180%) blur(8px);
  }
  .ab-btn {
    background:transparent; color:var(--txt); border:0;
    width:40px; height:40px; border-radius:10px;
    font-size:1.25rem; display:inline-flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background var(--t-fast);
  }
  .ab-btn:hover { background:var(--surface-2); }
  .ab-btn:active { background:var(--surface-3); transform:scale(.97); }
  .ab-title { flex:1; min-width:0; padding:0 .25rem; }
  .ab-title-main { font-weight:600; font-size:1rem; line-height:1.15; color:var(--txt); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .ab-title-sub  { font-size:.7rem; color:var(--muted); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500; }

  /* ─── Body ─── */
  .app-body { padding:1rem .9rem 1.4rem; max-width:560px; margin:0 auto; animation:fadeIn .2s ease-out; }
  @keyframes fadeIn { from { opacity:0; transform:translateY(4px);} to { opacity:1; transform:none; } }

  .card {
    background:var(--card); border:1px solid var(--br); border-radius:var(--r-lg);
    padding:1rem 1.05rem; margin-bottom:.85rem;
    box-shadow:var(--shadow-sm);
  }
  .card-header {
    display:flex; align-items:center; gap:.5rem;
    margin:-.1rem 0 .8rem; padding-bottom:.65rem;
    border-bottom:1px solid var(--br-soft);
  }
  .card-header .ch-ic { font-size:1rem; color:var(--muted); }
  .card-header h2 { margin:0; font-size:.9rem; font-weight:600; color:var(--txt-soft); }

  /* ─── Hero status pill ─── */
  .hero {
    text-align:center; padding:1rem 1rem .85rem;
    background:linear-gradient(180deg, var(--surface-2) 0%, var(--card) 100%);
    border:1px solid var(--br); border-radius:var(--r-lg);
    margin-bottom:.85rem;
  }
  .hero-ic {
    width:48px; height:48px; border-radius:14px;
    background:var(--info-bg); color:var(--info-fg);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.3rem; margin-bottom:.55rem;
  }
  .hero-title { font-size:1.02rem; font-weight:700; color:var(--txt); margin-bottom:.45rem; line-height:1.3; letter-spacing:-.01em; }
  .status-pill {
    display:inline-flex; align-items:center; gap:.4rem;
    background:var(--info-bg); border:1px solid var(--info-br); color:var(--info-fg);
    padding:.32rem .75rem; border-radius:999px; font-size:.74rem; font-weight:600;
  }
  .status-pill .blip { width:7px; height:7px; border-radius:50%; background:var(--info-fg); box-shadow:0 0 0 3px color-mix(in srgb, var(--info-fg) 20%, transparent); }
  .status-pill.in-progress { background:var(--warn-bg); border-color:var(--warn-br); color:var(--warn-fg); }
  .status-pill.in-progress .blip { background:#f59e0b; box-shadow:0 0 0 3px rgba(245,158,11,.2); animation:pulse 1.8s infinite; }
  .status-pill.completed { background:var(--ok-bg); border-color:var(--ok-br); color:var(--ok-fg); }
  .status-pill.completed .blip { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); }
  @keyframes pulse { 0%,100%{opacity:.5;} 50%{opacity:1;} }
  .hero-when { font-size:.78rem; color:var(--muted); margin-top:.4rem; font-weight:500; }

  /* ─── Meta rows ─── */
  .meta { font-size:.88rem; line-height:1.65; color:var(--txt-soft); }
  .meta .row { display:flex; gap:.6rem; padding:.16rem 0; }
  .meta .lbl { color:var(--muted); font-size:.66rem; text-transform:uppercase; letter-spacing:.06em; font-weight:600; min-width:74px; }
  .meta .val { color:var(--txt); flex:1; }
  .meta .val a { color:var(--pri-soft); text-decoration:none; }

  /* ─── Steps ─── */
  #steps { display:flex; flex-direction:column; gap:.5rem; }
  .step {
    display:flex; align-items:center; gap:.85rem;
    padding:.95rem 1rem;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    color:var(--txt);
    cursor:pointer;
    transition: background var(--t-fast), border-color var(--t-fast), transform var(--t-fast), box-shadow var(--t-fast);
  }
  .step:active { transform:scale(.99); }
  .step .ic {
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--surface-3); color:var(--muted); font-size:1rem; flex-shrink:0;
    transition: background var(--t-fast), color var(--t-fast);
  }
  .step .ic .num { font-weight:700; font-size:.85rem; }
  .step .ic .check { display:none; font-size:1.1rem; }
  .step .body { flex:1; min-width:0; }
  .step .body .lbl { font-weight:600; font-size:.94rem; line-height:1.25; color:var(--txt); }
  .step .body .sub { font-size:.74rem; color:var(--muted); margin-top:.15rem; }
  .step .chev { color:var(--muted-2); font-size:1rem; }
  .step.done    { background:var(--ok-bg); border-color:var(--ok-br); }
  .step.done .lbl  { color:var(--ok-fg); }
  .step.done .sub  { color:color-mix(in srgb, var(--ok-fg) 70%, transparent); }
  .step.done .ic   { background:#22c55e; color:#fff; }
  .step.done .ic .num { display:none; }
  .step.done .ic .check { display:inline; }
  .step.done .chev { color:var(--ok-fg); }
  .step.current {
    background:var(--info-bg); border-color:var(--info-br);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--info-br) 18%, transparent);
  }
  .step.current .lbl { color:var(--info-fg); }
  .step.current .ic  { background:var(--pri); color:#fff; }
  .step.busy { opacity:.55; pointer-events:none; }
  .geo-status {
    text-align:center; font-size:.72rem; color:var(--muted);
    padding:.5rem; margin-top:.4rem; font-weight:500;
  }
  .geo-status .bi { vertical-align:-.08em; margin-right:.2rem; }

  /* ─── Notes form ─── */
  textarea {
    width:100%; background:var(--surface-2); color:var(--txt);
    border:1px solid var(--br-soft); border-radius:var(--r-sm);
    padding:.6rem .8rem; font:inherit; font-size:.92rem; line-height:1.45;
    resize:vertical; min-height:80px;
    transition: border-color var(--t-fast);
  }
  textarea:focus { outline:0; border-color:var(--pri); box-shadow:0 0 0 3px color-mix(in srgb, var(--pri) 12%, transparent); }
  textarea::placeholder { color:var(--muted-2); }
  .btn-primary {
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    width:100%; padding:.85rem 1rem; border-radius:var(--r-md); border:0;
    font-size:.95rem; font-weight:600;
    background:var(--pri); color:#fff; cursor:pointer;
    transition: background var(--t-fast), transform var(--t-fast);
  }
  .btn-primary:hover { background:var(--pri-hover); }
  .btn-primary:active { transform:scale(.985); }
  .form-row { margin-bottom:.65rem; }
  .form-row label { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:600; color:var(--muted); margin-bottom:.3rem; }

  /* ─── Drawer ─── */
  .drawer { position:fixed; inset:0; z-index:200; display:flex; pointer-events:none; }
  .drawer-backdrop { position:absolute; inset:0; background:var(--backdrop); opacity:0; transition:opacity var(--t-med); }
  .drawer-panel {
    position:relative; width:86%; max-width:340px;
    background:var(--card); border-right:1px solid var(--br);
    transform:translateX(-100%); transition:transform var(--t-med) cubic-bezier(.2,.7,.2,1);
    display:flex; flex-direction:column;
    padding-top:env(safe-area-inset-top); padding-bottom:env(safe-area-inset-bottom);
    box-shadow:var(--shadow-lg);
  }
  .drawer.show { pointer-events:auto; }
  .drawer.show .drawer-backdrop { opacity:1; }
  .drawer.show .drawer-panel    { transform:translateX(0); }
  .drawer-head { display:flex; align-items:center; gap:.6rem; padding:1.1rem 1.2rem; border-bottom:1px solid var(--br-soft); }
  .drawer-head .dh-title { font-size:1.05rem; font-weight:700; flex:1; color:var(--txt); }
  .drawer-body { flex:1; overflow:auto; padding:.85rem 1.1rem 1.5rem; }
  .drawer-section { margin-bottom:1.1rem; }
  .drawer-section .dr-label { font-size:.66rem; text-transform:uppercase; color:var(--muted); letter-spacing:.08em; font-weight:600; margin-bottom:.45rem; }
  .drawer-link {
    display:flex; align-items:center; gap:.7rem;
    width:100%; padding:.75rem .85rem; margin:.3rem 0;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:9px;
    color:var(--txt); text-align:left; font-size:.9rem; font-weight:500;
    cursor:pointer; text-decoration:none;
  }
  .drawer-link:hover { background:var(--surface-3); }
  .drawer-link .bi { font-size:1.05rem; color:var(--muted); }
  .seg-toggle { display:inline-flex; background:var(--surface-2); border:1px solid var(--br-soft); border-radius:999px; padding:3px; }
  .seg-toggle button {
    background:transparent; color:var(--muted); border:0;
    padding:.4rem .8rem; border-radius:999px; cursor:pointer;
    font-weight:600; font-size:.78rem;
    display:inline-flex; align-items:center; gap:.3rem;
    transition: background var(--t-fast), color var(--t-fast);
  }
  .seg-toggle button .bi { font-size:.85rem; }
  #themeSeg button.on { background:var(--pri); color:#fff; }
  .me { font-size:.85rem; color:var(--txt-soft); line-height:1.6; }
  .me strong { color:var(--txt); }

  /* ─── Toast ─── */
  .toast {
    position:fixed; left:50%; bottom:1.4rem; transform:translateX(-50%);
    background:var(--card); color:var(--txt); border:1px solid var(--br);
    padding:.7rem 1.1rem; border-radius:var(--r-md); font-size:.88rem; font-weight:500;
    box-shadow:var(--shadow-lg);
    opacity:0; pointer-events:none;
    transition:opacity var(--t-fast), transform var(--t-fast);
    z-index:9999; max-width:90vw; text-align:center;
  }
  .toast.show { opacity:1; transform:translate(-50%, -4px); }
  .toast.ok   { border-color:var(--ok-br); }
  .toast.err  { border-color:var(--err-br); }

  /* ─── Install sheet ─── */
  .install-sheet { position:fixed; inset:0; z-index:10000; display:flex; align-items:flex-end; justify-content:center; background:var(--backdrop); opacity:0; pointer-events:none; transition:opacity var(--t-med); }
  .install-sheet.show { opacity:1; pointer-events:auto; }
  .install-panel { background:var(--card); color:var(--txt); width:100%; max-width:520px; border-top-left-radius:22px; border-top-right-radius:22px; border:1px solid var(--br); border-bottom:0; padding:1.4rem 1.2rem 1.5rem; padding-bottom:calc(1.5rem + env(safe-area-inset-bottom)); box-shadow:var(--shadow-lg); transform:translateY(20px); transition:transform var(--t-med); }
  .install-sheet.show .install-panel { transform:translateY(0); }
  .install-grip { width:42px; height:4px; background:var(--grip); border-radius:99px; margin:0 auto .9rem; }
  .install-icon { width:54px; height:54px; border-radius:14px; background:var(--info-bg); color:var(--info-fg); display:flex; align-items:center; justify-content:center; font-size:1.7rem; margin:0 auto .8rem; }
  .install-panel h3 { margin:0 0 .35rem; font-size:1.1rem; font-weight:700; text-align:center; color:var(--txt); }
  .install-panel p { margin:0 0 1rem; font-size:.9rem; color:var(--muted); text-align:center; line-height:1.55; }
  .install-link { display:block; width:100%; text-align:center; background:transparent; color:var(--muted); border:0; padding:.7rem; font-size:.86rem; font-weight:500; cursor:pointer; }
  .install-hint { background:var(--surface-2); border:1px solid var(--br-soft); border-radius:8px; padding:.75rem .9rem; margin-bottom:.7rem; font-size:.84rem; line-height:1.6; color:var(--txt-soft); }
  .install-hint .step-no { font-weight:700; color:var(--pri); margin-right:.2rem; }
</style>
</head><body>

<header class="app-bar">
  <button class="ab-btn" id="menuBtn" aria-label="Menu"><i class="bi bi-list"></i></button>
  <div class="ab-title">
    <div class="ab-title-main">Meeting tracker</div>
    <div class="ab-title-sub"><?= esc($meeting['title']) ?></div>
  </div>
  <button class="ab-btn" id="themeQuick" aria-label="Toggle theme" title="Toggle theme"><i class="bi bi-circle-half"></i></button>
  <a class="ab-btn" href="<?= site_url('meetings/' . (int) $meeting['id']) ?>" title="Open in staff app"><i class="bi bi-box-arrow-up-right"></i></a>
</header>

<aside class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-backdrop" id="drawerBackdrop"></div>
  <div class="drawer-panel" role="dialog" aria-modal="true">
    <div class="drawer-head">
      <div class="dh-title">Menu</div>
      <button class="ab-btn" id="drawerClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="drawer-body">
      <div class="drawer-section">
        <div class="dr-label">Signed in</div>
        <div class="me"><strong><?= esc($me['name'] ?? '—') ?></strong><br><span style="color:var(--muted);"><?= esc($me['email'] ?? '') ?></span></div>
      </div>
      <div class="drawer-section">
        <div class="dr-label">Theme</div>
        <div class="seg-toggle" id="themeSeg">
          <button type="button" data-theme="auto"><i class="bi bi-circle-half"></i><span>Auto</span></button>
          <button type="button" data-theme="light"><i class="bi bi-sun"></i><span>Light</span></button>
          <button type="button" data-theme="dark"><i class="bi bi-moon-stars"></i><span>Dark</span></button>
        </div>
      </div>
      <div class="drawer-section">
        <a class="drawer-link" href="<?= site_url('meetings') ?>"><i class="bi bi-card-list"></i> All my meetings</a>
        <a class="drawer-link" href="<?= site_url('meetings/' . (int) $meeting['id']) ?>"><i class="bi bi-pencil-square"></i> Edit / outcome (web)</a>
        <button class="drawer-link" id="reInstall" type="button"><i class="bi bi-phone"></i> Add to home screen</button>
        <a class="drawer-link" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Sign out</a>
      </div>
    </div>
  </div>
</aside>

<main class="app-body">

  <div class="hero">
    <div class="hero-ic"><i class="bi bi-people-fill"></i></div>
    <div class="hero-title"><?= esc($meeting['title']) ?></div>
    <?php
    $st = $meeting['status'];
    $cls = $st === 'InProgress' ? 'in-progress' : ($st === 'Completed' ? 'completed' : '');
    ?>
    <span class="status-pill <?= $cls ?>" id="meetingStatus"><span class="blip"></span><?= esc($st) ?></span>
    <div class="hero-when"><?= esc(date('D · d-m · H:i', strtotime($meeting['scheduled_at']))) ?></div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-card-text ch-ic"></i><h2>Details</h2></div>
    <div class="meta">
      <?php if (!empty($meeting['with_company'])): ?>
        <div class="row"><span class="lbl">With</span><span class="val"><?= esc($meeting['with_company']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($meeting['with_contact_name'])): ?>
        <div class="row"><span class="lbl">Contact</span><span class="val"><?= esc($meeting['with_contact_name']) ?>
          <?php if (!empty($meeting['with_contact_phone'])): ?>
            · <a href="tel:<?= esc($meeting['with_contact_phone']) ?>"><?= esc($meeting['with_contact_phone']) ?></a>
          <?php endif; ?>
        </span></div>
      <?php endif; ?>
      <?php if (!empty($meeting['location'])): ?>
        <div class="row"><span class="lbl">Location</span><span class="val"><?= esc($meeting['location']) ?></span></div>
      <?php endif; ?>
      <?php if (!$isOwner): ?>
        <div class="row"><span class="lbl">Owner</span><span class="val"><?= esc($owner['name'] ?? '—') ?> <span style="color:var(--warn-fg);font-size:.74rem;">· read-only for you</span></span></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-list-check ch-ic"></i><h2>Travel events</h2></div>
    <div id="steps">
      <?php foreach (['depart_office','arrive_meeting','leave_meeting','return_office'] as $et):
        $def  = $eventDefs[$et];
        $done = isset($byType[$et]);
      ?>
        <div class="step <?= $done ? 'done' : '' ?>" data-event="<?= esc($et) ?>">
          <div class="ic"><span class="num"><?= $def['num'] ?></span><i class="bi bi-check-lg check"></i></div>
          <div class="body">
            <div class="lbl"><?= esc($def['label']) ?></div>
            <?php if ($done): ?>
              <div class="sub">✓ <?= esc(date('d-m · H:i', strtotime($byType[$et]['occurred_at']))) ?><?php if (!empty($byType[$et]['latitude'])): ?> · <a href="https://maps.google.com/?q=<?= esc((string) $byType[$et]['latitude']) ?>,<?= esc((string) $byType[$et]['longitude']) ?>" target="_blank" rel="noopener" style="color:var(--ok-fg);">map</a><?php endif; ?></div>
            <?php else: ?>
              <div class="sub"><?= esc($def['sub']) ?></div>
            <?php endif; ?>
          </div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="geo-status" id="geoStatus"><i class="bi bi-broadcast"></i> Locating…</div>
  </div>

  <div class="card">
    <div class="card-header"><i class="bi bi-journal-text ch-ic"></i><h2>Meeting notes</h2></div>
    <div class="form-row">
      <label>Outcome / discussion</label>
      <textarea id="outcome" placeholder="What was discussed, decisions made…"><?= esc($meeting['outcome'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <label>Next steps</label>
      <textarea id="next_steps" placeholder="Actions, follow-ups, deadlines…"><?= esc($meeting['next_steps'] ?? '') ?></textarea>
    </div>
    <button class="btn-primary" id="saveNotes" <?= $isOwner ? '' : 'disabled' ?>>
      <i class="bi bi-check2"></i> <?= $isOwner ? 'Save notes' : 'Read-only' ?>
    </button>
  </div>

</main>

<!-- Install sheet -->
<div class="install-sheet" id="installSheet" aria-hidden="true">
  <div class="install-panel" role="dialog" aria-modal="true">
    <div class="install-grip"></div>
    <div class="install-icon"><i class="bi bi-phone"></i></div>
    <h3>Add to Home Screen</h3>
    <p>Keep this meeting tracker one tap away on your phone.</p>
    <div class="install-hint" id="installHintIos" hidden>
      <div><span class="step-no">1.</span> Tap the Share button below in Safari</div>
      <div><span class="step-no">2.</span> Choose "Add to Home Screen"</div>
      <div><span class="step-no">3.</span> Tap "Add" — done.</div>
    </div>
    <div class="install-hint" id="installHintManual" hidden>
      <div><span class="step-no">1.</span> Open the browser menu</div>
      <div><span class="step-no">2.</span> Tap "Install app" or "Add to Home screen"</div>
    </div>
    <button id="installBtn" class="btn-primary" hidden><i class="bi bi-download"></i> Install</button>
    <button id="installDone" class="btn-primary" hidden><i class="bi bi-check-lg"></i> Got it</button>
    <button id="installLater" class="install-link">Not now</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
  const PUNCH_URL = <?= json_encode(site_url('m/' . $token . '/punch/')) ?>;
  const NOTES_URL = <?= json_encode(site_url('m/' . $token . '/notes')) ?>;
  const IS_OWNER  = <?= $isOwner ? 'true' : 'false' ?>;
  const THEME_KEY = 'tpt_theme_meeting';
  const INSTALL_KEY = 'tpt_install_meeting_seen';

  let lastPos = null;
  let THEME = 'auto';
  try { const s = localStorage.getItem(THEME_KEY); if (s === 'light' || s === 'dark' || s === 'auto') THEME = s; } catch (e) {}
  const sysDark = window.matchMedia('(prefers-color-scheme: dark)');
  function effectiveTheme() { return THEME === 'auto' ? (sysDark.matches ? 'dark' : 'light') : THEME; }
  function applyTheme() {
    const eff = effectiveTheme();
    document.documentElement.setAttribute('data-theme', eff);
    const meta = document.getElementById('themeColorMeta');
    if (meta) meta.setAttribute('content', eff === 'dark' ? '#0b0f17' : '#ffffff');
    document.querySelectorAll('#themeSeg button').forEach(b => b.classList.toggle('on', b.dataset.theme === THEME));
  }
  document.querySelectorAll('#themeSeg button').forEach(b => b.addEventListener('click', () => {
    THEME = b.dataset.theme;
    try { localStorage.setItem(THEME_KEY, THEME); } catch (e) {}
    applyTheme();
  }));
  document.getElementById('themeQuick').addEventListener('click', () => {
    THEME = (effectiveTheme() === 'dark') ? 'light' : 'dark';
    try { localStorage.setItem(THEME_KEY, THEME); } catch (e) {}
    applyTheme();
  });
  if (sysDark.addEventListener) sysDark.addEventListener('change', () => { if (THEME === 'auto') applyTheme(); });
  applyTheme();

  // Drawer
  const drawer = document.getElementById('drawer');
  document.getElementById('menuBtn').addEventListener('click', () => { drawer.classList.add('show'); drawer.setAttribute('aria-hidden','false'); });
  document.getElementById('drawerClose').addEventListener('click', () => drawer.classList.remove('show'));
  document.getElementById('drawerBackdrop').addEventListener('click', () => drawer.classList.remove('show'));

  // Toast
  function toast(kind, msg) {
    const el = document.getElementById('toast');
    el.className = 'toast ' + kind + ' show';
    el.textContent = msg;
    clearTimeout(window.__t);
    window.__t = setTimeout(() => { el.className = 'toast ' + kind; }, 2400);
  }

  // Geolocation watch
  function setGeo(text, icon) {
    const el = document.getElementById('geoStatus');
    el.innerHTML = '<i class="bi ' + (icon || 'bi-broadcast') + '"></i> ' + text;
  }
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      (p) => { lastPos = p; setGeo('Location ready · ±' + Math.round(p.coords.accuracy) + ' m', 'bi-geo-alt-fill'); },
      (e) => { setGeo('Location unavailable: ' + (e.message || '—') + ' (punches still work)', 'bi-geo-alt'); },
      { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
    );
  } else {
    setGeo('Geolocation not supported');
  }

  // Punches
  async function punch(stage, labelText) {
    if (!IS_OWNER) { toast('err', 'Read-only — owner only'); return; }
    if (!confirm('Confirm: "' + labelText + '"?')) return;
    const body = {};
    if (lastPos) { body.lat = lastPos.coords.latitude; body.lng = lastPos.coords.longitude; body.acc = lastPos.coords.accuracy; }
    try {
      const r = await fetch(PUNCH_URL + stage, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.reason || 'failed');
      toast('ok', '✓ ' + labelText);
      rerender(j.events, j.status);
    } catch (e) {
      toast('err', 'Could not save — try again.');
    }
  }
  function rerender(events, status) {
    const done = {};
    events.forEach(e => done[e.event_type] = e);
    document.querySelectorAll('.step').forEach(el => {
      const ev = el.getAttribute('data-event');
      const e = done[ev];
      if (e && !el.classList.contains('done')) {
        el.classList.add('done');
        const body = el.querySelector('.body');
        const lbl  = body.querySelector('.lbl').textContent;
        const t    = new Date((e.occurred_at + '').replace(' ', 'T'));
        const ts   = t.toLocaleString([], { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
        body.innerHTML = '<div class="lbl">' + lbl + '</div><div class="sub">✓ ' + ts + '</div>';
      }
    });
    if (status) {
      const pill = document.getElementById('meetingStatus');
      pill.textContent = '';
      const blip = document.createElement('span'); blip.className = 'blip'; pill.appendChild(blip);
      pill.appendChild(document.createTextNode(status));
      pill.classList.remove('in-progress', 'completed');
      if (status === 'InProgress') pill.classList.add('in-progress');
      if (status === 'Completed')  pill.classList.add('completed');
    }
  }
  document.querySelectorAll('.step').forEach(el => {
    el.addEventListener('click', () => {
      if (el.classList.contains('done') || el.classList.contains('busy')) return;
      const ev    = el.getAttribute('data-event');
      const label = el.querySelector('.lbl').textContent;
      el.classList.add('busy');
      punch(ev, label).finally(() => el.classList.remove('busy'));
    });
  });

  // Notes
  if (IS_OWNER) {
    document.getElementById('saveNotes').addEventListener('click', async function () {
      try {
        const r = await fetch(NOTES_URL, {
          method:'POST', headers:{ 'Content-Type':'application/json' },
          body: JSON.stringify({ outcome: document.getElementById('outcome').value, next_steps: document.getElementById('next_steps').value }),
        });
        const j = await r.json();
        if (!j.ok) throw new Error('failed');
        toast('ok', '✓ Notes saved');
      } catch (e) { toast('err', 'Save failed'); }
    });
  }

  // Install prompt (Android Chrome / iOS / generic)
  const sheet = document.getElementById('installSheet');
  const ua = navigator.userAgent || '';
  const isIOS = (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  const isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  let deferredPrompt = null;
  function alreadySeen() { try { return localStorage.getItem(INSTALL_KEY) === '1'; } catch (e) { return false; } }
  function markSeen() { try { localStorage.setItem(INSTALL_KEY, '1'); } catch (e) {} }
  function showSheet(mode) {
    document.getElementById('installHintIos').hidden    = (mode !== 'ios');
    document.getElementById('installHintManual').hidden = (mode !== 'manual');
    document.getElementById('installBtn').hidden        = (mode !== 'native');
    document.getElementById('installDone').hidden       = (mode === 'native');
    sheet.classList.add('show');
  }
  function hideSheet() { sheet.classList.remove('show'); }
  window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferredPrompt = e; if (!isStandalone && !alreadySeen()) showSheet('native'); });
  document.getElementById('installBtn').addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    try { const { outcome } = await deferredPrompt.userChoice; if (outcome === 'accepted') markSeen(); } catch (e) {}
    deferredPrompt = null; hideSheet();
  });
  document.getElementById('installDone').addEventListener('click', () => { markSeen(); hideSheet(); });
  document.getElementById('installLater').addEventListener('click', () => { markSeen(); hideSheet(); });
  document.getElementById('reInstall').addEventListener('click', () => { drawer.classList.remove('show'); if (isIOS) showSheet('ios'); else if (deferredPrompt) showSheet('native'); else showSheet('manual'); });
  if (!isStandalone && !alreadySeen()) {
    if (isIOS) setTimeout(() => showSheet('ios'), 1200);
    else       setTimeout(() => { if (!sheet.classList.contains('show')) showSheet('manual'); }, 3000);
  }
})();
</script>
</body></html>
