<!doctype html>
<html lang="hi"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
<meta name="theme-color" content="#0b0f17" id="themeColorMeta">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="robots" content="noindex,nofollow">
<title>Driver Tracker · <?= esc($appName) ?></title>
<link rel="manifest" href="<?= site_url('d/' . $token . '/manifest.webmanifest') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
  (function () {
    var KEY = 'tpt_theme_<?= esc(substr($token, 0, 8)) ?>';
    var pref = null;
    try { pref = localStorage.getItem(KEY); } catch (e) {}
    var dark;
    if (pref === 'dark')      dark = true;
    else if (pref === 'light') dark = false;
    else                       dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  })();
</script>
<style>
  :root {
    --pri:#2563eb; --pri-hover:#1d4ed8; --pri-soft:#60a5fa;
    --accent:#10b981; --danger:#dc2626;
    --bar-h:56px; --tab-h:64px;
    --r-sm:8px; --r-md:12px; --r-lg:16px; --r-xl:20px;
    --t-fast:.15s; --t-med:.22s;
  }
  html[data-theme="dark"] {
    --bg:#0b0f17; --bg-elev:#11161f;
    --card:#161c27; --surface-2:#1c2330; --surface-3:#252e3d;
    --br:#232a36; --br-soft:#1c2330;
    --txt:#e8eaed; --txt-soft:#cbd1da; --muted:#9aa3b2; --muted-2:#6b7280;
    --grip:#3a4250;
    --ok-bg:#0b3a2c;  --ok-br:#166534;  --ok-fg:#4ade80;
    --warn-bg:#3a2a0b; --warn-br:#a16207; --warn-fg:#fbbf24;
    --err-bg:#3b1212;  --err-br:#b91c1c;  --err-fg:#fca5a5;
    --info-bg:#0b2540; --info-br:#1e40af; --info-fg:#60a5fa;
    --idle-bg:#1c2330; --idle-fg:#9aa3b2;
    --tab-active:#60a5fa;
    --tab-idle:#7a8499;
    --backdrop:rgba(2,6,14,.65);
    --shadow-sm:0 1px 2px rgba(0,0,0,.4), 0 1px 1px rgba(0,0,0,.3);
    --shadow-md:0 4px 12px rgba(0,0,0,.4);
    --shadow-lg:0 12px 32px rgba(0,0,0,.55);
    --hairline:rgba(255,255,255,.05);
  }
  html[data-theme="light"] {
    --bg:#f4f6fa; --bg-elev:#ffffff;
    --card:#ffffff; --surface-2:#f3f5f9; --surface-3:#e8ecf2;
    --br:#e3e7ee; --br-soft:#eef1f5;
    --txt:#0f172a; --txt-soft:#374151; --muted:#6b7280; --muted-2:#9ca3af;
    --grip:#cbd5e1;
    --ok-bg:#dcfce7;  --ok-br:#86efac;  --ok-fg:#065f46;
    --warn-bg:#fef3c7; --warn-br:#fcd34d; --warn-fg:#92400e;
    --err-bg:#fee2e2;  --err-br:#fca5a5;  --err-fg:#991b1b;
    --info-bg:#dbeafe; --info-br:#bfdbfe; --info-fg:#1e40af;
    --idle-bg:#f3f5f9; --idle-fg:#6b7280;
    --tab-active:#2563eb;
    --tab-idle:#94a3b8;
    --backdrop:rgba(15,23,42,.45);
    --shadow-sm:0 1px 2px rgba(15,23,42,.06), 0 1px 1px rgba(15,23,42,.04);
    --shadow-md:0 4px 12px rgba(15,23,42,.08);
    --shadow-lg:0 12px 32px rgba(15,23,42,.15);
    --hairline:rgba(15,23,42,.04);
  }

  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body { margin:0; padding:0; background:var(--bg); color:var(--txt); min-height:100vh; }
  body {
    font-family:'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size:15px; line-height:1.45; letter-spacing:-.01em;
    -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
    padding-bottom: calc(var(--tab-h) + env(safe-area-inset-bottom));
    transition: background var(--t-fast), color var(--t-fast);
  }
  html[lang="hi"] body {
    font-family:'Noto Sans Devanagari', 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  }
  button { font-family: inherit; }
  a { color: var(--pri); text-decoration: none; }
  ::-webkit-scrollbar { width:6px; height:6px; }
  ::-webkit-scrollbar-thumb { background: var(--surface-3); border-radius: 3px; }

  /* ─── App bar ─── */
  .app-bar {
    position:sticky; top:0; z-index:80;
    background:var(--card); border-bottom:1px solid var(--br);
    display:flex; align-items:center; gap:.25rem;
    padding:0 .5rem;
    height:calc(var(--bar-h) + env(safe-area-inset-top));
    padding-top:env(safe-area-inset-top);
    backdrop-filter: saturate(180%) blur(8px);
    -webkit-backdrop-filter: saturate(180%) blur(8px);
  }
  .ab-btn {
    background:transparent; color:var(--txt); border:0;
    width:40px; height:40px; border-radius:10px;
    font-size:1.25rem; display:inline-flex; align-items:center; justify-content:center;
    cursor:pointer; transition:background var(--t-fast);
  }
  .ab-btn:hover { background:var(--surface-2); }
  .ab-btn:active { background:var(--surface-3); transform:scale(.97); }
  .ab-title { flex:1; min-width:0; padding:0 .35rem; }
  .ab-title-main { font-weight:600; font-size:1.025rem; line-height:1.2; color:var(--txt); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:-.015em; }
  .ab-title-sub  { font-size:.72rem; color:var(--muted); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500; }
  .ab-lang { font-size:.78rem; font-weight:700; letter-spacing:.02em; min-width:42px; }

  /* ─── App body / tabs ─── */
  .app-body { padding:1rem .9rem; max-width:560px; margin:0 auto; }
  .tab-pane { display:none; }
  .tab-pane.active { display:block; animation:fadeIn .25s cubic-bezier(.2,.6,.2,1); }
  @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }

  /* ─── Card primitive ─── */
  .card {
    background:var(--card); border:1px solid var(--br); border-radius:var(--r-lg);
    padding:1rem 1.05rem; margin-bottom:.85rem;
    box-shadow:var(--shadow-sm);
  }
  .card-header {
    display:flex; align-items:center; gap:.55rem;
    margin:-.15rem 0 .85rem; padding-bottom:.7rem;
    border-bottom:1px solid var(--br-soft);
  }
  .card-header .ch-ic { font-size:1rem; color:var(--muted); }
  .card-header h2 {
    margin:0; font-size:.92rem; font-weight:600; color:var(--txt-soft);
    letter-spacing:.005em;
  }

  /* ─── Buttons ─── */
  .btn {
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    width:100%; padding:.95rem 1rem; border-radius:var(--r-md); border:0;
    font-size:.98rem; font-weight:600; letter-spacing:-.005em;
    cursor:pointer; transition:transform var(--t-fast), background var(--t-fast), opacity var(--t-fast);
  }
  .btn:active { transform:scale(.985); }
  .btn-primary { background:var(--pri); color:#fff; }
  .btn-primary:hover { background:var(--pri-hover); }
  .btn-stop {
    background:var(--err-bg); color:var(--err-fg);
    border:1px solid var(--err-br);
  }
  .btn .bi { font-size:1.1rem; }

  /* ─── Live status banner ─── */
  .status {
    display:flex; align-items:center; gap:.65rem;
    padding:.7rem .9rem; border-radius:var(--r-md); margin-bottom:.75rem;
    font-size:.88rem; font-weight:500;
    border:1px solid transparent;
  }
  .status-idle   { background:var(--idle-bg); color:var(--idle-fg);  border-color:var(--br); }
  .status-active { background:var(--ok-bg);   color:var(--ok-fg);    border-color:var(--ok-br); }
  .status-error  { background:var(--err-bg);  color:var(--err-fg);   border-color:var(--err-br); }
  .status-buffer { background:var(--warn-bg); color:var(--warn-fg);  border-color:var(--warn-br); }
  .status .dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
  .dot-idle   { background:var(--muted-2); }
  .dot-active { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.18); animation:pulse 1.8s infinite; }
  .dot-error  { background:#ef4444; }
  .dot-buffer { background:#f59e0b; }
  @keyframes pulse { 0%,100%{opacity:.6;} 50%{opacity:1;} }

  /* ─── Stat grid ─── */
  .stat-row { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
  .stat {
    background:var(--surface-2); border:1px solid var(--br-soft);
    padding:.6rem .75rem; border-radius:var(--r-sm);
  }
  .stat .l { font-size:.66rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; font-weight:600; }
  .stat .v { font-weight:600; font-size:.95rem; margin-top:.15rem; color:var(--txt); }

  /* ─── Trip hero ─── */
  .trip-hero {
    text-align:center; padding:1.4rem 1rem 1.2rem;
    background:linear-gradient(180deg, var(--surface-2) 0%, var(--card) 100%);
    border:1px solid var(--br); border-radius:var(--r-lg);
    box-shadow:var(--shadow-sm);
    margin-bottom:.85rem;
  }
  .trip-hero .hero-ic {
    width:56px; height:56px; border-radius:16px;
    background:var(--info-bg); color:var(--info-fg);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.55rem; margin-bottom:.7rem;
  }
  .trip-hero .hero-no { font-size:1.05rem; font-weight:700; color:var(--txt); margin-bottom:.5rem; letter-spacing:-.01em; }
  .trip-status-pill {
    display:inline-flex; align-items:center; gap:.45rem;
    background:var(--info-bg); border:1px solid var(--info-br); color:var(--info-fg);
    padding:.4rem .85rem; border-radius:999px; font-size:.82rem; font-weight:600;
  }
  .trip-status-pill .blip { width:7px; height:7px; border-radius:50%; background:var(--info-fg); box-shadow:0 0 0 3px color-mix(in srgb, var(--info-fg) 20%, transparent); }
  .trip-status-pill.delivered { background:var(--ok-bg); border-color:var(--ok-br); color:var(--ok-fg); }
  .trip-status-pill.delivered .blip { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); }

  .trip-meta { font-size:.86rem; line-height:1.7; color:var(--txt-soft); }
  .trip-meta .row { display:flex; align-items:baseline; gap:.6rem; padding:.2rem 0; }
  .trip-meta .row .lbl { color:var(--muted); font-size:.74rem; text-transform:uppercase; letter-spacing:.05em; font-weight:600; min-width:64px; }
  .trip-meta .row .val { color:var(--txt); font-weight:500; flex:1; }

  /* ─── Privacy box ─── */
  .privacy { font-size:.83rem; color:var(--muted); line-height:1.6; padding:.85rem 1rem; background:var(--surface-2); border-radius:var(--r-md); border:1px solid var(--br-soft); }
  .privacy p { margin:0 0 .5rem; }
  .privacy p:last-child { margin:0; }
  .privacy strong { color:var(--txt-soft); font-weight:600; }

  /* ─── Milestone steps ─── */
  #steps { display:flex; flex-direction:column; gap:.45rem; }
  .step {
    display:flex; align-items:center; gap:.9rem;
    padding:.9rem 1rem;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    color:var(--txt);
    cursor:pointer;
    transition: background var(--t-fast), border-color var(--t-fast), transform var(--t-fast), box-shadow var(--t-fast);
  }
  .step:active { transform:scale(.99); }
  .step .ic {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--surface-3); color:var(--muted); font-weight:600; font-size:.9rem; flex-shrink:0;
    transition: background var(--t-fast), color var(--t-fast);
  }
  .step .ic .check { display:none; }
  .step .ic .num { display:inline; }
  .step .body { flex:1; min-width:0; }
  .step .body .lbl { font-weight:600; font-size:.94rem; line-height:1.3; color:var(--txt); }
  .step .body .sub { font-size:.74rem; color:var(--muted); margin-top:.15rem; }
  .step .chev { color:var(--muted-2); font-size:1rem; }
  .step.done    { background:var(--ok-bg); border-color:var(--ok-br); }
  .step.done .lbl  { color:var(--ok-fg); }
  .step.done .sub  { color:color-mix(in srgb, var(--ok-fg) 70%, transparent); }
  .step.done .ic   { background:#22c55e; color:#fff; }
  .step.done .ic .num { display:none; }
  .step.done .ic .check { display:inline; }
  .step.done .chev { color:var(--ok-fg); }
  .step.current   {
    background:var(--info-bg); border-color:var(--info-br);
    box-shadow:0 0 0 3px color-mix(in srgb, var(--info-br) 18%, transparent);
  }
  .step.current .lbl { color:var(--info-fg); }
  .step.current .ic  { background:var(--pri); color:#fff; }
  .step.busy { opacity:.55; pointer-events:none; }

  /* ─── POD upload ─── */
  .pod-input { display:none; }
  .pod-trigger {
    display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem;
    width:100%; padding:1.6rem 1rem;
    border-radius:var(--r-md);
    border:2px dashed var(--info-br);
    background:var(--info-bg); color:var(--info-fg);
    font-size:.92rem; font-weight:600;
    cursor:pointer; transition: opacity var(--t-fast), background var(--t-fast);
  }
  .pod-trigger .bi { font-size:1.8rem; }
  .pod-trigger.uploading { opacity:.55; pointer-events:none; }
  .pod-preview { margin-top:.7rem; }
  .pod-preview img { width:100%; border-radius:var(--r-md); border:1px solid var(--br); display:block; }
  .pod-done {
    display:flex; align-items:center; gap:.6rem;
    padding:1rem; background:var(--ok-bg); border:1px solid var(--ok-br);
    border-radius:var(--r-md); color:var(--ok-fg); font-weight:600;
  }
  .pod-done .bi { font-size:1.3rem; }

  /* ─── Bottom tab bar ─── */
  .tab-bar {
    position:fixed; left:0; right:0; bottom:0; z-index:90;
    background:color-mix(in srgb, var(--card) 92%, transparent);
    border-top:1px solid var(--br);
    display:flex;
    padding-bottom:env(safe-area-inset-bottom);
    height:calc(var(--tab-h) + env(safe-area-inset-bottom));
    backdrop-filter: saturate(180%) blur(12px);
    -webkit-backdrop-filter: saturate(180%) blur(12px);
  }
  .tab-btn {
    flex:1; background:transparent; color:var(--tab-idle); border:0;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:3px;
    cursor:pointer; position:relative; padding:0;
    transition: color var(--t-fast);
  }
  .tab-btn .tab-ic { font-size:1.35rem; line-height:1; transition: transform var(--t-fast); }
  .tab-btn .tab-lbl { font-size:.66rem; font-weight:600; letter-spacing:.01em; }
  .tab-btn:active .tab-ic { transform:scale(.92); }
  .tab-btn.active { color:var(--tab-active); }
  .tab-btn.active::after {
    content:""; position:absolute; top:0; left:50%; transform:translateX(-50%);
    width:28px; height:3px; border-radius:0 0 3px 3px; background:var(--pri);
    animation: tabSlide var(--t-med) ease-out;
  }
  @keyframes tabSlide { from { width:0; } to { width:28px; } }

  /* ─── Drawer ─── */
  .drawer { position:fixed; inset:0; z-index:200; display:flex; pointer-events:none; }
  .drawer-backdrop {
    position:absolute; inset:0; background:var(--backdrop);
    opacity:0; transition:opacity var(--t-med);
  }
  .drawer-panel {
    position:relative; width:86%; max-width:340px;
    background:var(--card); border-right:1px solid var(--br);
    transform:translateX(-100%); transition:transform var(--t-med) cubic-bezier(.2,.7,.2,1);
    display:flex; flex-direction:column;
    padding-top:env(safe-area-inset-top);
    padding-bottom:env(safe-area-inset-bottom);
    box-shadow:var(--shadow-lg);
  }
  .drawer.show { pointer-events:auto; }
  .drawer.show .drawer-backdrop { opacity:1; }
  .drawer.show .drawer-panel    { transform:translateX(0); }
  .drawer-head {
    display:flex; align-items:center; gap:.6rem;
    padding:1.1rem 1.2rem; border-bottom:1px solid var(--br-soft);
  }
  .drawer-head .dh-title { font-size:1.05rem; font-weight:700; flex:1; color:var(--txt); letter-spacing:-.01em; }
  .drawer-body { flex:1; overflow:auto; padding:.85rem 1.1rem 1.5rem; }
  .drawer-section { margin-bottom:1.2rem; }
  .drawer-section .dr-label {
    font-size:.66rem; text-transform:uppercase; color:var(--muted);
    letter-spacing:.08em; font-weight:600; margin-bottom:.5rem;
  }
  .drawer-meta { font-size:.85rem; color:var(--txt-soft); line-height:1.75; }
  .drawer-meta strong { color:var(--txt); font-weight:600; }
  .drawer-link {
    display:flex; align-items:center; gap:.7rem;
    width:100%; padding:.85rem 1rem; margin:.3rem 0;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    color:var(--txt); text-align:left; font-size:.92rem; font-weight:500;
    cursor:pointer; transition: background var(--t-fast);
  }
  .drawer-link:hover { background:var(--surface-3); }
  .drawer-link .bi { font-size:1.05rem; color:var(--muted); }
  .drawer-link:hover .bi { color:var(--txt); }

  /* Segmented toggle */
  .seg-toggle {
    display:inline-flex; background:var(--surface-2); border:1px solid var(--br-soft); border-radius:999px;
    padding:3px; gap:0;
  }
  .seg-toggle button {
    background:transparent; color:var(--muted); border:0;
    padding:.4rem .85rem; border-radius:999px; cursor:pointer;
    font-weight:600; font-size:.78rem;
    display:inline-flex; align-items:center; gap:.35rem;
    transition: background var(--t-fast), color var(--t-fast);
  }
  .seg-toggle button .bi { font-size:.85rem; }
  .seg-toggle button.on { background:var(--card); color:var(--txt); box-shadow:var(--shadow-sm); }
  #themeSeg button.on { background:var(--pri); color:#fff; }

  /* ─── Contacts ─── */
  .contact-row {
    display:flex; align-items:flex-start; gap:.85rem;
    padding:.9rem 0;
    border-bottom:1px solid var(--br-soft);
  }
  .contact-row:first-child { padding-top:.3rem; }
  .contact-row:last-child { border-bottom:0; padding-bottom:.3rem; }
  .contact-row .c-ic {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    background:var(--info-bg); color:var(--info-fg);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.15rem;
  }
  .contact-row.role-agency .c-ic       { background:var(--info-bg); color:var(--info-fg); }
  .contact-row.role-transporter .c-ic  { background:var(--warn-bg); color:var(--warn-fg); }
  .contact-row.role-pickup .c-ic       { background:var(--ok-bg);   color:var(--ok-fg); }
  .contact-row.role-destination .c-ic  { background:var(--err-bg);  color:var(--err-fg); }
  .contact-row .c-body { flex:1; min-width:0; }
  .contact-row .c-role { font-size:.66rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); font-weight:600; }
  .contact-row .c-name { font-weight:600; color:var(--txt); margin-top:.15rem; font-size:.95rem; letter-spacing:-.005em; }
  .contact-row .c-sub  { font-size:.78rem; color:var(--muted); margin-top:.2rem; line-height:1.5; }
  .contact-row .c-sub .bi { vertical-align:-.05em; margin-right:.25rem; opacity:.7; }
  .contact-row .c-actions { display:flex; gap:.4rem; margin-top:.65rem; flex-wrap:wrap; }
  .contact-action {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.42rem .8rem; border-radius:99px; border:1px solid var(--br);
    background:var(--card); color:var(--txt-soft);
    font-size:.78rem; font-weight:600;
    transition: transform var(--t-fast), background var(--t-fast);
  }
  .contact-action:active { transform:scale(.97); }
  .contact-action .bi { font-size:.9rem; }
  .contact-action.call { background:var(--pri); color:#fff; border-color:var(--pri); }
  .contact-action.call:hover { background:var(--pri-hover); border-color:var(--pri-hover); }
  .contact-action.wa { background:#25D366; color:#fff; border-color:#1eb858; }
  .contact-action.wa:hover { background:#1eb858; }
  .contact-empty { color:var(--muted); font-size:.85rem; padding:.5rem 0; text-align:center; }

  /* ─── Chat ─── */
  .chat-thread {
    display:flex; flex-direction:column;
    min-height:280px; max-height:55vh; overflow-y:auto;
    padding:.4rem .15rem;
    gap:.4rem;
  }
  .chat-empty {
    display:flex; flex-direction:column; align-items:center; gap:.4rem;
    text-align:center; color:var(--muted); font-size:.88rem;
    padding:3rem 1rem;
  }
  .chat-empty .bi { font-size:2.5rem; opacity:.45; margin-bottom:.4rem; }
  .bubble {
    max-width:80%;
    padding:.55rem .8rem; border-radius:16px;
    word-wrap:break-word;
    box-shadow:var(--shadow-sm);
    font-size:.92rem; line-height:1.4;
  }
  .bubble .b-body  { white-space:pre-wrap; }
  .bubble .b-meta  { font-size:.65rem; opacity:.65; margin-top:.3rem; text-align:right; font-weight:500; }
  .bubble.driver   { background:var(--pri); color:#fff; align-self:flex-end; border-bottom-right-radius:5px; }
  .bubble.staff    { background:var(--surface-2); color:var(--txt); align-self:flex-start; border-bottom-left-radius:5px; border:1px solid var(--br-soft); }
  .bubble.system   { background:transparent; color:var(--muted); align-self:center; font-size:.74rem; text-align:center; max-width:92%; padding:.25rem .55rem; box-shadow:none; }
  .bubble .b-attach { margin-top:.4rem; }
  .bubble .b-attach img { max-width:100%; border-radius:10px; display:block; }
  .bubble .b-attach-link {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.5rem .7rem; border-radius:8px;
    background:rgba(255,255,255,.16); color:inherit;
    font-size:.8rem; font-weight:600;
  }
  .bubble.staff .b-attach-link { background:var(--surface-3); color:var(--txt-soft); }
  .bubble .b-attach-link .bi { font-size:1rem; }

  .composer {
    margin-top:.6rem;
    background:var(--card); border:1px solid var(--br); border-radius:var(--r-lg);
    padding:.5rem;
    box-shadow:var(--shadow-sm);
  }
  .composer-preview {
    display:flex; align-items:center; gap:.55rem;
    padding:.5rem .65rem; margin-bottom:.5rem;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    font-size:.82rem; color:var(--txt-soft);
  }
  .composer-preview .c-name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500; }
  .composer-preview .c-x {
    background:transparent; color:var(--muted); border:0;
    width:28px; height:28px; border-radius:50%; cursor:pointer;
    font-size:1rem; display:inline-flex; align-items:center; justify-content:center;
    transition: background var(--t-fast);
  }
  .composer-preview .c-x:hover { background:var(--surface-3); }
  .composer-preview img { width:42px; height:42px; object-fit:cover; border-radius:6px; }
  .composer-row { display:flex; align-items:flex-end; gap:.3rem; }
  .composer-icon {
    background:transparent; border:0; color:var(--muted);
    width:40px; height:40px; border-radius:10px;
    font-size:1.2rem; cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center;
    flex-shrink:0;
    transition: background var(--t-fast), color var(--t-fast);
  }
  .composer-icon:hover { background:var(--surface-2); color:var(--txt); }
  .composer-input {
    flex:1; min-width:0;
    background:var(--surface-2); color:var(--txt);
    border:1px solid var(--br-soft); border-radius:18px;
    padding:.55rem .9rem;
    font-family:inherit; font-size:.93rem; line-height:1.4;
    resize:none; max-height:120px; overflow-y:auto;
    transition: border-color var(--t-fast);
  }
  .composer-input:focus { outline:0; border-color:var(--pri); }
  .composer-input::placeholder { color:var(--muted-2); }
  .composer-send {
    background:var(--pri); color:#fff; border:0;
    width:40px; height:40px; border-radius:50%;
    font-size:1.05rem; cursor:pointer; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    transition: background var(--t-fast), transform var(--t-fast), opacity var(--t-fast);
  }
  .composer-send:hover { background:var(--pri-hover); }
  .composer-send:active { transform:scale(.94); }
  .composer-send:disabled { opacity:.35; cursor:not-allowed; }

  .tab-badge {
    position:absolute; top:8px; left:50%; transform:translate(8px,0);
    background:var(--danger); color:#fff;
    min-width:16px; height:16px; padding:0 4px; border-radius:99px;
    font-size:.6rem; line-height:16px; font-weight:700;
    box-shadow:0 0 0 2px var(--card);
  }

  /* ─── Toast ─── */
  .toast {
    position:fixed; left:50%; bottom:calc(var(--tab-h) + 1rem + env(safe-area-inset-bottom));
    transform:translateX(-50%);
    background:var(--card); color:var(--txt); border:1px solid var(--br);
    padding:.7rem 1.1rem; border-radius:var(--r-md); font-size:.88rem; font-weight:500;
    box-shadow:var(--shadow-lg);
    opacity:0; pointer-events:none;
    transition:opacity var(--t-fast), transform var(--t-fast);
    z-index:9999; max-width:90vw; text-align:center;
  }
  .toast.show { opacity:1; transform:translate(-50%, -4px); }
  .toast.ok  { border-color:var(--ok-br); }
  .toast.err { border-color:var(--err-br); }

  /* ─── Install sheet ─── */
  .install-sheet {
    position:fixed; inset:0; z-index:10000;
    display:flex; align-items:flex-end; justify-content:center;
    background:var(--backdrop);
    opacity:0; pointer-events:none;
    transition:opacity var(--t-med);
  }
  .install-sheet.show { opacity:1; pointer-events:auto; }
  .install-panel {
    background:var(--card); color:var(--txt);
    width:100%; max-width:520px;
    border-top-left-radius:24px; border-top-right-radius:24px;
    border:1px solid var(--br); border-bottom:0;
    padding:1.5rem 1.25rem 1.6rem; padding-bottom:calc(1.6rem + env(safe-area-inset-bottom));
    box-shadow:var(--shadow-lg);
    transform:translateY(20px); transition:transform var(--t-med);
  }
  .install-sheet.show .install-panel { transform:translateY(0); }
  .install-grip { width:44px; height:4px; background:var(--grip); border-radius:99px; margin:0 auto 1rem; }
  .install-icon {
    width:56px; height:56px; border-radius:16px;
    background:var(--info-bg); color:var(--info-fg);
    display:flex; align-items:center; justify-content:center;
    font-size:1.8rem; margin:0 auto .8rem;
  }
  .install-panel h3 { margin:0 0 .4rem; font-size:1.15rem; font-weight:700; text-align:center; color:var(--txt); letter-spacing:-.01em; }
  .install-panel p  { margin:0 0 1.1rem; font-size:.9rem; color:var(--muted); text-align:center; line-height:1.55; }
  .install-link {
    display:block; width:100%; text-align:center;
    background:transparent; color:var(--muted); border:0;
    padding:.7rem; font-size:.88rem; font-weight:500; cursor:pointer;
  }
  .install-link:hover { color:var(--txt); }
  .install-hint {
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    padding:.85rem 1rem; margin-bottom:.85rem;
    font-size:.86rem; line-height:1.65; color:var(--txt-soft);
  }
  .install-hint .step-no { font-weight:700; color:var(--pri); margin-right:.2rem; }

  /* ─── Documents list ─── */
  .doc-list { display:flex; flex-direction:column; gap:.4rem; }
  .doc-row {
    display:flex; align-items:center; gap:.85rem;
    padding:.85rem .95rem;
    background:var(--surface-2); border:1px solid var(--br-soft); border-radius:var(--r-md);
    color:var(--txt); text-decoration:none;
    cursor:pointer; transition: background var(--t-fast), transform var(--t-fast);
  }
  .doc-row:active { transform:scale(.99); }
  .doc-row:hover { background:var(--surface-3); }
  .doc-row .d-ic {
    width:38px; height:38px; border-radius:10px; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.15rem;
  }
  .doc-row.kind-generated .d-ic { background:var(--info-bg); color:var(--info-fg); }
  .doc-row.kind-pdf       .d-ic { background:#fee2e2; color:#dc2626; }
  .doc-row.kind-image     .d-ic { background:var(--ok-bg);   color:var(--ok-fg); }
  .doc-row.kind-other     .d-ic { background:var(--surface-3); color:var(--muted); }
  .doc-row.kind-pod       .d-ic { background:var(--warn-bg); color:var(--warn-fg); }
  .doc-row .d-body { flex:1; min-width:0; }
  .doc-row .d-title { font-weight:600; font-size:.92rem; color:var(--txt); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:-.005em; }
  .doc-row .d-sub { font-size:.74rem; color:var(--muted); margin-top:.15rem; }
  .doc-row .chev { color:var(--muted-2); font-size:1.05rem; flex-shrink:0; }
  .doc-empty {
    color:var(--muted); font-size:.85rem; text-align:center;
    padding:1.4rem 1rem;
    display:flex; flex-direction:column; align-items:center; gap:.5rem;
  }
  .doc-empty .bi { font-size:2rem; opacity:.45; }
</style>
</head><body>

<header class="app-bar">
  <button class="ab-btn" id="menuBtn" aria-label="Menu"><i class="bi bi-list"></i></button>
  <div class="ab-title">
    <div class="ab-title-main" data-i18n="app_title">Driver Tracker</div>
    <div class="ab-title-sub" id="abSub">—</div>
  </div>
  <button class="ab-btn ab-lang" id="langQuick" aria-label="Language">हिं</button>
</header>

<aside class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-backdrop" id="drawerBackdrop"></div>
  <div class="drawer-panel" role="dialog" aria-modal="true">
    <div class="drawer-head">
      <div class="dh-title" data-i18n="menu_title">Menu</div>
      <button class="ab-btn" id="drawerClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="drawer-body">

      <div class="drawer-section">
        <div class="dr-label" data-i18n="menu_lang">Language</div>
        <div class="seg-toggle" id="langSeg">
          <button type="button" data-lang="en">EN</button>
          <button type="button" data-lang="hi" class="on">हिं</button>
        </div>
      </div>

      <div class="drawer-section">
        <div class="dr-label" data-i18n="menu_theme">Theme</div>
        <div class="seg-toggle" id="themeSeg">
          <button type="button" data-theme="auto"><i class="bi bi-circle-half"></i><span data-i18n="theme_auto">Auto</span></button>
          <button type="button" data-theme="light"><i class="bi bi-sun"></i><span data-i18n="theme_light">Light</span></button>
          <button type="button" data-theme="dark"><i class="bi bi-moon-stars"></i><span data-i18n="theme_dark">Dark</span></button>
        </div>
      </div>

      <div class="drawer-section">
        <button class="drawer-link" id="reInstall">
          <i class="bi bi-phone"></i>
          <span data-i18n="menu_install">Add to Home Screen</span>
        </button>
      </div>

      <div class="drawer-section">
        <div class="dr-label" data-i18n="menu_trip">Trip</div>
        <div class="drawer-meta">
          <div><strong data-i18n="lbl_trip">Trip:</strong> <?= esc($trip['trip_no']) ?><?= !empty($trip['lr_no']) ? ' · LR ' . esc($trip['lr_no']) : '' ?></div>
          <?php if (!empty($route)): ?><div><strong data-i18n="lbl_route">Route:</strong> <?= esc($route) ?></div><?php endif; ?>
          <?php if (!empty($trip['vehicle_number'])): ?><div><strong data-i18n="lbl_vehicle">Vehicle:</strong> <?= esc($trip['vehicle_number']) ?></div><?php endif; ?>
          <?php if (!empty($trip['driver_name'])): ?><div><strong data-i18n="lbl_driver">Driver:</strong> <?= esc($trip['driver_name']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="drawer-section">
        <div class="dr-label" data-i18n="menu_info">About</div>
        <div class="privacy">
          <p><strong data-i18n="about_h">About this tracker:</strong> <span data-i18n="about_body">your phone will send its location to <?= esc($appName) ?> every 30 seconds while this page is open.</span></p>
          <p><strong data-i18n="tips_h">Tips:</strong> <span data-i18n="tips_body">keep this page open with mobile data on. If you lose signal, your phone stores the locations and sends them when you're back online.</span></p>
        </div>
      </div>

    </div>
  </div>
</aside>

<main class="app-body">

  <section class="tab-pane" data-tab="trip">
    <div class="trip-hero">
      <div class="hero-ic"><i class="bi bi-truck"></i></div>
      <div class="hero-no"><?= esc($trip['trip_no']) ?><?= !empty($trip['lr_no']) ? ' · LR ' . esc($trip['lr_no']) : '' ?></div>
      <div class="trip-status-pill" id="tripStatusPill" data-status-key="<?= esc($trip['current_status']) ?>">
        <span class="blip"></span><span id="tripStatusPillText"><?= esc($trip['current_status']) ?></span>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-info-circle ch-ic"></i><h2 data-i18n="trip_details">Trip details</h2></div>
      <div class="trip-meta">
        <?php if (!empty($route)): ?>
        <div class="row"><span class="lbl" data-i18n="lbl_route">Route</span><span class="val"><?= esc($route) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($trip['vehicle_number'])): ?>
        <div class="row"><span class="lbl" data-i18n="lbl_vehicle">Vehicle</span><span class="val"><?= esc($trip['vehicle_number']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($trip['driver_name'])): ?>
        <div class="row"><span class="lbl" data-i18n="lbl_driver">Driver</span><span class="val"><?= esc($trip['driver_name']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-person-rolodex ch-ic"></i><h2 data-i18n="contacts_title">Important contacts</h2></div>
      <?php
      $contactsList = $contacts ?? [];
      $contactIcons = [
          'agency'      => 'bi-buildings',
          'transporter' => 'bi-truck',
          'pickup'      => 'bi-box-seam',
          'destination' => 'bi-geo-alt-fill',
      ];
      $contactRoleKey = [
          'agency' => 'contact_agency', 'transporter' => 'contact_transporter',
          'pickup' => 'contact_pickup', 'destination' => 'contact_destination',
      ];
      $renderedAny = false;
      foreach ($contactsList as $key => $c):
          if (!$c) continue;
          if (empty($c['phone']) && empty($c['name'])) continue;
          $renderedAny = true;
      ?>
        <div class="contact-row role-<?= esc($key) ?>">
          <div class="c-ic"><i class="bi <?= esc($contactIcons[$key] ?? 'bi-person') ?>"></i></div>
          <div class="c-body">
            <div class="c-role" data-i18n="<?= esc($contactRoleKey[$key] ?? '') ?>"><?= esc($key) ?></div>
            <?php if (!empty($c['name'])): ?><div class="c-name"><?= esc($c['name']) ?></div><?php endif; ?>
            <?php if (!empty($c['detail'])): ?><div class="c-sub"><i class="bi bi-person"></i><?= esc($c['detail']) ?></div><?php endif; ?>
            <?php if (!empty($c['address'])): ?><div class="c-sub"><i class="bi bi-geo-alt"></i><?= esc($c['address']) ?></div><?php endif; ?>
            <div class="c-actions">
              <?php if (!empty($c['phone'])): ?>
                <a class="contact-action call" href="tel:+<?= esc($c['phone']) ?>"><i class="bi bi-telephone-fill"></i><span data-i18n="contact_call">Call</span></a>
                <a class="contact-action wa"   href="https://wa.me/<?= esc($c['phone']) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i><span data-i18n="contact_whatsapp">WhatsApp</span></a>
              <?php endif; ?>
              <?php if (!empty($c['alt'])): ?>
                <a class="contact-action" href="tel:+<?= esc($c['alt']) ?>"><i class="bi bi-telephone"></i>+<?= esc($c['alt']) ?></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$renderedAny): ?>
        <div class="contact-empty" data-i18n="contacts_empty">No contacts on file for this trip.</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-shield-check ch-ic"></i><h2 data-i18n="menu_info">About this tracker</h2></div>
      <div class="privacy">
        <p><strong data-i18n="about_h">About this tracker:</strong> <span data-i18n="about_body">your phone will send its location to <?= esc($appName) ?> every 30 seconds while this page is open.</span></p>
        <p><strong data-i18n="tips_h">Tips:</strong> <span data-i18n="tips_body">keep this page open with mobile data on. If you lose signal, your phone stores the locations and sends them when you're back online.</span></p>
      </div>
    </div>
  </section>

  <section class="tab-pane active" data-tab="location">
    <div class="card">
      <div class="card-header"><i class="bi bi-broadcast-pin ch-ic"></i><h2 data-i18n="loc_share_title">Live location sharing</h2></div>
      <div id="status" class="status status-idle">
        <span class="dot dot-idle"></span>
        <span id="statusText" data-i18n="status_idle_initial">Tap "Start sharing" to begin sending your location.</span>
      </div>

      <button id="startBtn" class="btn btn-primary"><i class="bi bi-broadcast-pin"></i><span data-i18n="btn_start">Start sharing location</span></button>
      <button id="stopBtn"  class="btn btn-stop" style="display:none; margin-top:.6rem;"><i class="bi bi-stop-circle"></i><span data-i18n="btn_stop">Stop sharing</span></button>

      <div class="stat-row" id="stats" style="display:none; margin-top:.85rem;">
        <div class="stat"><div class="l" data-i18n="stat_last">Last update</div><div class="v" id="lastTs">—</div></div>
        <div class="stat"><div class="l" data-i18n="stat_sent">Pings sent</div><div class="v" id="pingCount">0</div></div>
        <div class="stat"><div class="l" data-i18n="stat_buf">Buffered</div><div class="v" id="bufferCount">0</div></div>
        <div class="stat"><div class="l" data-i18n="stat_acc">Accuracy</div><div class="v" id="acc">—</div></div>
      </div>
    </div>
  </section>

  <section class="tab-pane" data-tab="steps">
    <div class="card">
      <div class="card-header"><i class="bi bi-list-check ch-ic"></i><h2 data-i18n="milestones_title">Trip milestones</h2></div>
      <div id="steps">
        <div class="step" data-stage="started_to_pickup" data-target="">
          <div class="ic"><span class="num">1</span><i class="bi bi-check-lg check"></i></div>
          <div class="body"><div class="lbl" data-i18n="step1_lbl">Started toward pickup</div><div class="sub" data-i18n="step1_sub">Tap when you've started the journey</div></div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
        <div class="step" data-stage="reached_pickup" data-target="Loading">
          <div class="ic"><span class="num">2</span><i class="bi bi-check-lg check"></i></div>
          <div class="body"><div class="lbl" data-i18n="step2_lbl">Reached pickup</div><div class="sub" data-i18n="step2_sub">Arrived at the loading point</div></div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
        <div class="step" data-stage="goods_loaded" data-target="In Transit">
          <div class="ic"><span class="num">3</span><i class="bi bi-check-lg check"></i></div>
          <div class="body"><div class="lbl" data-i18n="step3_lbl">Goods loaded · in transit</div><div class="sub" data-i18n="step3_sub">Loading complete, on the way</div></div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
        <div class="step" data-stage="reached_destination" data-target="Arrived">
          <div class="ic"><span class="num">4</span><i class="bi bi-check-lg check"></i></div>
          <div class="body"><div class="lbl" data-i18n="step4_lbl">Reached destination</div><div class="sub" data-i18n="step4_sub">Arrived at the unloading point</div></div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
        <div class="step" data-stage="unloaded" data-target="Delivered">
          <div class="ic"><span class="num">5</span><i class="bi bi-check-lg check"></i></div>
          <div class="body"><div class="lbl" data-i18n="step5_lbl">Unloaded at destination</div><div class="sub" data-i18n="step5_sub">Unloading complete</div></div>
          <i class="bi bi-chevron-right chev"></i>
        </div>
      </div>
    </div>
  </section>

  <section class="tab-pane" data-tab="chat">
    <div class="card">
      <div class="card-header"><i class="bi bi-chat-dots ch-ic"></i><h2 data-i18n="chat_title">Chat with dispatch</h2></div>
      <div class="chat-thread" id="chatThread"></div>
      <div class="chat-empty" id="chatEmpty" hidden>
        <i class="bi bi-chat-square-dots"></i>
        <span data-i18n="chat_no_msgs">No messages yet — say hello!</span>
      </div>
    </div>
    <div class="composer">
      <div class="composer-preview" id="composerPreview" hidden>
        <div id="composerPreviewThumb"></div>
        <span class="c-name" id="composerPreviewName"></span>
        <button type="button" class="c-x" id="composerPreviewRemove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="composer-row">
        <button type="button" class="composer-icon" id="attachBtn" aria-label="Attach file"><i class="bi bi-paperclip"></i></button>
        <button type="button" class="composer-icon" id="cameraBtn" aria-label="Take photo"><i class="bi bi-camera"></i></button>
        <textarea class="composer-input" id="composerInput" rows="1" data-i18n-placeholder="chat_placeholder" placeholder="Type a message…"></textarea>
        <button type="button" class="composer-send" id="sendBtn" disabled aria-label="Send"><i class="bi bi-send-fill"></i></button>
      </div>
      <input type="file" id="attachInput" class="pod-input" accept="image/*,application/pdf,video/mp4,audio/*">
      <input type="file" id="cameraInput" class="pod-input" accept="image/*" capture="environment">
    </div>
  </section>

  <section class="tab-pane" data-tab="docs">
    <div class="card" id="podCard">
      <div class="card-header"><i class="bi bi-camera ch-ic"></i><h2 data-i18n="pod_title">Proof of Delivery (POD)</h2></div>
      <div id="podArea">
        <label for="podFile" class="pod-trigger" id="podTrigger">
          <i class="bi bi-camera"></i>
          <span id="podTriggerLabel" data-i18n="pod_btn">Take / upload POD photo</span>
        </label>
        <input id="podFile" class="pod-input" type="file" accept="image/*,application/pdf" capture="environment">
        <div id="podPreview" class="pod-preview" style="display:none;"></div>
      </div>
    </div>

    <?php
    $generatedDocs = [
        ['kind' => 'lr',             'label_key' => 'doc_lr',             'label_en' => 'Lorry Receipt (LR)'],
        ['kind' => 'loading-advice', 'label_key' => 'doc_loading_advice', 'label_en' => 'Loading Advice'],
        ['kind' => 'trip-sheet',     'label_key' => 'doc_trip_sheet',     'label_en' => 'Trip Sheet'],
        ['kind' => 'gate-pass',      'label_key' => 'doc_gate_pass',      'label_en' => 'Gate Pass'],
        ['kind' => 'pod-blank',      'label_key' => 'doc_pod_blank',      'label_en' => 'POD form (blank)'],
    ];
    ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-file-earmark-pdf ch-ic"></i><h2 data-i18n="docs_generated">Trip paperwork</h2></div>
      <div class="doc-list">
        <?php foreach ($generatedDocs as $g): ?>
          <a class="doc-row kind-generated" href="<?= site_url('d/' . $token . '/dispatch/' . $g['kind'] . '.pdf') ?>" target="_blank" rel="noopener">
            <div class="d-ic"><i class="bi bi-file-earmark-pdf"></i></div>
            <div class="d-body">
              <div class="d-title" data-i18n="<?= esc($g['label_key']) ?>"><?= esc($g['label_en']) ?></div>
              <div class="d-sub" data-i18n="docs_open_pdf">Tap to open PDF</div>
            </div>
            <i class="bi bi-chevron-right chev"></i>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php
    $uploadedDocs = $tripDocs ?? [];
    $docTypeIcon = function ($mime, $type) {
        if (stripos((string) $mime, 'image/') === 0) return ['kind' => 'image', 'icon' => 'bi-image'];
        if (stripos((string) $mime, 'pdf') !== false) return ['kind' => 'pdf', 'icon' => 'bi-file-earmark-pdf'];
        if (strtoupper((string) $type) === 'POD')     return ['kind' => 'pod', 'icon' => 'bi-clipboard-check'];
        return ['kind' => 'other', 'icon' => 'bi-file-earmark'];
    };
    $fmtBytes = function ($n) {
        $n = (int) $n;
        if ($n <= 0) return '';
        if ($n < 1024) return $n . ' B';
        if ($n < 1024 * 1024) return number_format($n / 1024, 0) . ' KB';
        return number_format($n / 1048576, 1) . ' MB';
    };
    ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-files ch-ic"></i><h2 data-i18n="docs_uploaded">Documents on file</h2></div>
      <?php if (empty($uploadedDocs)): ?>
        <div class="doc-empty">
          <i class="bi bi-inbox"></i>
          <span data-i18n="docs_empty">No documents uploaded for this trip yet.</span>
        </div>
      <?php else: ?>
        <div class="doc-list">
          <?php foreach ($uploadedDocs as $d):
            $meta = $docTypeIcon($d['mime_type'], $d['document_type']);
            $sub = $d['document_type'] ?: 'Document';
            if (!empty($d['file_size'])) $sub .= ' · ' . $fmtBytes($d['file_size']);
            if (!empty($d['created_at'])) $sub .= ' · ' . date('d-m', strtotime($d['created_at']));
          ?>
            <a class="doc-row kind-<?= esc($meta['kind']) ?>" href="<?= site_url('d/' . $token . '/doc/' . (int) $d['id']) ?>" target="_blank" rel="noopener">
              <div class="d-ic"><i class="bi <?= esc($meta['icon']) ?>"></i></div>
              <div class="d-body">
                <div class="d-title"><?= esc($d['original_file_name'] ?: 'Document') ?></div>
                <div class="d-sub"><?= esc($sub) ?></div>
              </div>
              <i class="bi bi-chevron-right chev"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

</main>

<nav class="tab-bar" aria-label="Sections">
  <button type="button" class="tab-btn" data-tab="trip">
    <i class="bi bi-truck tab-ic"></i><span class="tab-lbl" data-i18n="tab_trip">Trip</span>
  </button>
  <button type="button" class="tab-btn active" data-tab="location">
    <i class="bi bi-geo-alt-fill tab-ic"></i><span class="tab-lbl" data-i18n="tab_location">Location</span>
  </button>
  <button type="button" class="tab-btn" data-tab="steps">
    <i class="bi bi-list-check tab-ic"></i><span class="tab-lbl" data-i18n="tab_steps">Steps</span>
  </button>
  <button type="button" class="tab-btn" data-tab="chat">
    <i class="bi bi-chat-dots-fill tab-ic"></i><span class="tab-lbl" data-i18n="tab_chat">Chat</span>
    <span class="tab-badge" id="chatBadge" hidden>0</span>
  </button>
  <button type="button" class="tab-btn" data-tab="docs">
    <i class="bi bi-files tab-ic"></i><span class="tab-lbl" data-i18n="tab_docs">Docs</span>
  </button>
</nav>

<div class="install-sheet" id="installSheet" aria-hidden="true">
  <div class="install-panel" role="dialog" aria-modal="true" aria-labelledby="installTitle">
    <div class="install-grip"></div>
    <div class="install-icon"><i class="bi bi-phone"></i></div>
    <h3 id="installTitle" data-i18n="inst_title">Add to Home Screen</h3>
    <p data-i18n="inst_body">Keep this tracker on your phone like an app — one tap to open it next time.</p>

    <div class="install-hint" id="installHintIos" hidden>
      <div><span class="step-no">1.</span> <span data-i18n="inst_ios_1">Tap the Share button below in Safari</span></div>
      <div><span class="step-no">2.</span> <span data-i18n="inst_ios_2">Choose "Add to Home Screen"</span></div>
      <div><span class="step-no">3.</span> <span data-i18n="inst_ios_3">Tap "Add" — done.</span></div>
    </div>

    <div class="install-hint" id="installHintManual" hidden>
      <div><span class="step-no">1.</span> <span data-i18n="inst_man_1">Open the browser menu</span></div>
      <div><span class="step-no">2.</span> <span data-i18n="inst_man_2">Tap "Install app" or "Add to Home screen"</span></div>
    </div>

    <button id="installBtn" class="btn btn-primary" hidden><i class="bi bi-download"></i><span data-i18n="inst_btn">Install</span></button>
    <button id="installDone" class="btn btn-primary" hidden><i class="bi bi-check-lg"></i><span data-i18n="inst_done">Got it</span></button>
    <button id="installLater" class="install-link" data-i18n="inst_later">Not now</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
  const PING_URL      = <?= json_encode(site_url('d/' . $token . '/ping')) ?>;
  const STOP_URL      = <?= json_encode(site_url('d/' . $token . '/stop')) ?>;
  const MILESTONE_URL = <?= json_encode(site_url('d/' . $token . '/milestone')) ?>;
  const POD_URL       = <?= json_encode(site_url('d/' . $token . '/pod')) ?>;
  const SW_URL        = <?= json_encode(site_url('d/' . $token . '/sw.js')) ?>;
  const MSG_URL_LIST  = <?= json_encode(site_url('d/' . $token . '/messages')) ?>;
  const MSG_URL_SEND  = <?= json_encode(site_url('d/' . $token . '/message')) ?>;
  const ATTACH_URL_BASE = <?= json_encode(site_url('d/' . $token . '/attach/')) ?>;
  const BUF_KEY       = 'tpt_pings_<?= esc(substr($token, 0, 8)) ?>';
  const LANG_KEY      = 'tpt_lang_<?= esc(substr($token, 0, 8)) ?>';
  const THEME_KEY     = 'tpt_theme_<?= esc(substr($token, 0, 8)) ?>';
  const TAB_KEY       = 'tpt_tab_<?= esc(substr($token, 0, 8)) ?>';
  const STARTED_KEY   = 'tpt_started_<?= esc(substr($token, 0, 8)) ?>';
  const INSTALL_KEY   = 'tpt_install_seen_<?= esc(substr($token, 0, 8)) ?>';
  const POLL_MS       = 30000;
  const APP_NAME      = <?= json_encode($appName) ?>;
  let   currentStatus = <?= json_encode($trip['current_status']) ?>;
  const podAlready    = <?= !empty($trip['pod_received_at']) ? 'true' : 'false' ?>;
  const TRIP_NO       = <?= json_encode($trip['trip_no']) ?>;
  const INITIAL_MESSAGES = <?= json_encode($messages ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  // ─── i18n ───────────────────────────────────────────────────
  const I18N = {
    en: {
      app_title:'Driver Tracker',
      menu_title:'Menu', menu_lang:'Language', menu_theme:'Theme',
      theme_auto:'Auto', theme_light:'Light', theme_dark:'Dark',
      menu_install:'Add to Home Screen',
      menu_trip:'Trip', menu_info:'About',
      tab_trip:'Trip', tab_location:'Location', tab_steps:'Steps', tab_chat:'Chat', tab_pod:'POD', tab_docs:'Docs',
      trip_details:'Trip details',
      lbl_trip:'Trip:', lbl_route:'Route', lbl_vehicle:'Vehicle', lbl_driver:'Driver',
      loc_share_title:'Live location sharing',
      status_idle_initial:'Tap "Start sharing" to begin sending your location.',
      btn_start:'Start sharing location', btn_stop:'Stop sharing',
      stat_last:'Last update', stat_sent:'Pings sent', stat_buf:'Buffered', stat_acc:'Accuracy',
      milestones_title:'Trip milestones',
      step1_lbl:'Started toward pickup',  step1_sub:"Tap when you've started the journey",
      step2_lbl:'Reached pickup',         step2_sub:'Arrived at the loading point',
      step3_lbl:'Goods loaded · in transit', step3_sub:'Loading complete, on the way',
      step4_lbl:'Reached destination',    step4_sub:'Arrived at the unloading point',
      step5_lbl:'Unloaded at destination', step5_sub:'Unloading complete',
      pod_title:'Proof of Delivery (POD)', pod_btn:'Take / upload POD photo',
      pod_uploading:'Uploading…', pod_done:'POD uploaded — thank you.', pod_already:'POD already on file.',
      about_h:'About this tracker:',
      about_body:'your phone will send its location to ' + APP_NAME + ' every 30 seconds while this page is open, so the dispatch team can see your trip’s progress. Sharing stops when the trip is closed or when you tap Stop sharing.',
      tips_h:'Tips:',
      tips_body:'keep this page open with mobile data on. If you lose signal, your phone stores the locations and sends them when you’re back online.',
      st_requesting:'Requesting location permission…',
      st_active:'Sharing your location every 30 s. Keep this page open.',
      st_started:'Sharing started. Pings will go every 30 s.',
      st_offline:'Offline — saved to phone, will retry shortly.',
      st_flushing:'Flushing {n} buffered ping(s)…',
      st_stopped:'Sharing stopped. Tap "Start sharing" to resume.',
      st_no_geo:'This phone does not support location sharing.',
      st_loc_err:'Location error: {msg}',
      confirm_action:'Confirm: "{label}"?',
      err_update:'Could not update — try again when online.',
      err_upload:'Upload failed — try again.',
      err_too_large:'File too large (max 10 MB).',
      err_send:'Could not send — try again.',
      toast_pod_ok:'POD received',
      contacts_title:'Important contacts',
      contacts_empty:'No contacts on file for this trip.',
      contact_agency:'Booking agency',
      contact_transporter:'Transporter',
      contact_pickup:'Client (pickup)',
      contact_destination:'Destination contact',
      contact_call:'Call', contact_whatsapp:'WhatsApp',
      chat_title:'Chat with dispatch',
      chat_placeholder:'Type a message…',
      chat_no_msgs:'No messages yet — say hello!',
      chat_attachment:'Attachment',
      docs_generated:'Trip paperwork',
      docs_uploaded:'Documents on file',
      docs_open_pdf:'Tap to open PDF',
      docs_empty:'No documents uploaded for this trip yet.',
      doc_lr:'Lorry Receipt (LR)',
      doc_loading_advice:'Loading Advice',
      doc_trip_sheet:'Trip Sheet',
      doc_gate_pass:'Gate Pass',
      doc_pod_blank:'POD form (blank, for delivery)',
      'st_Booking Created':'Booking Created',
      'st_Vehicle Placed':'Vehicle Placed',
      st_Loading:'Loading',
      'st_In Transit':'In Transit',
      st_Arrived:'Arrived',
      st_Unloading:'Unloading',
      st_Delivered:'Delivered',
      'st_POD Received':'POD Received',
      st_Closed:'Closed',
      st_Cancelled:'Cancelled',
      inst_title:'Add to Home Screen',
      inst_body:'Keep this tracker on your phone like an app — one tap to open it next time.',
      inst_btn:'Install', inst_done:'Got it', inst_later:'Not now',
      inst_ios_1:'Tap the Share button below in Safari',
      inst_ios_2:'Choose "Add to Home Screen"',
      inst_ios_3:'Tap "Add" — done.',
      inst_man_1:'Open the browser menu',
      inst_man_2:'Tap "Install app" or "Add to Home screen"',
    },
    hi: {
      app_title:'ड्राइवर ट्रैकर',
      menu_title:'मेन्यू', menu_lang:'भाषा', menu_theme:'थीम',
      theme_auto:'ऑटो', theme_light:'लाइट', theme_dark:'डार्क',
      menu_install:'होम स्क्रीन पर लगाएं',
      menu_trip:'ट्रिप', menu_info:'जानकारी',
      tab_trip:'ट्रिप', tab_location:'लोकेशन', tab_steps:'चरण', tab_chat:'मैसेज', tab_pod:'POD', tab_docs:'दस्तावेज़',
      trip_details:'ट्रिप की जानकारी',
      lbl_trip:'ट्रिप:', lbl_route:'रूट', lbl_vehicle:'गाड़ी', lbl_driver:'ड्राइवर',
      loc_share_title:'लाइव लोकेशन शेयरिंग',
      status_idle_initial:'लोकेशन भेजना शुरू करने के लिए "शुरू करें" दबाएं।',
      btn_start:'लोकेशन शेयर करना शुरू करें', btn_stop:'शेयरिंग रोकें',
      stat_last:'आख़िरी अपडेट', stat_sent:'भेजे गए पिंग', stat_buf:'रुके हुए', stat_acc:'एक्यूरेसी',
      milestones_title:'ट्रिप के चरण',
      step1_lbl:'पिकअप के लिए रवाना हुए',   step1_sub:'जब आप रवाना हों तब दबाएं',
      step2_lbl:'पिकअप पर पहुँचे',           step2_sub:'लोडिंग पॉइंट पर आ गए',
      step3_lbl:'माल लोड हुआ · ट्रांज़िट में', step3_sub:'लोडिंग पूरी, रवाना हो गए',
      step4_lbl:'गंतव्य पर पहुँचे',            step4_sub:'अनलोडिंग पॉइंट पर आ गए',
      step5_lbl:'गंतव्य पर अनलोड हुआ',        step5_sub:'अनलोडिंग पूरी',
      pod_title:'डिलीवरी का प्रमाण (POD)',  pod_btn:'POD की फ़ोटो लें / अपलोड करें',
      pod_uploading:'अपलोड हो रहा है…', pod_done:'POD अपलोड हो गया — धन्यवाद।', pod_already:'POD पहले से सेव है।',
      about_h:'इस ट्रैकर के बारे में:',
      about_body:'जब तक यह पेज खुला है, आपका फ़ोन हर 30 सेकंड में ' + APP_NAME + ' को आपकी लोकेशन भेजेगा, जिससे डिस्पैच टीम आपकी ट्रिप का अपडेट देख सके। ट्रिप बंद होने पर या "शेयरिंग रोकें" दबाने पर शेयरिंग अपने आप बंद हो जाएगी।',
      tips_h:'सुझाव:',
      tips_body:'इस पेज को खुला रखें और मोबाइल डेटा चालू रखें। अगर नेटवर्क चला जाए, तो फ़ोन लोकेशन सेव कर लेगा और नेटवर्क आते ही भेज देगा।',
      st_requesting:'लोकेशन की अनुमति माँग रहे हैं…',
      st_active:'हर 30 सेकंड में आपकी लोकेशन भेज रहे हैं। पेज खुला रखें।',
      st_started:'शेयरिंग शुरू हो गई। हर 30 सेकंड में पिंग जाएगा।',
      st_offline:'ऑफ़लाइन — फ़ोन में सेव कर लिया, थोड़ी देर में दोबारा भेजेंगे।',
      st_flushing:'{n} रुके हुए पिंग भेज रहे हैं…',
      st_stopped:'शेयरिंग बंद हो गई। दोबारा शुरू करने के लिए "शुरू करें" दबाएं।',
      st_no_geo:'यह फ़ोन लोकेशन शेयरिंग सपोर्ट नहीं करता।',
      st_loc_err:'लोकेशन में दिक्कत: {msg}',
      confirm_action:'पुष्टि करें: "{label}"?',
      err_update:'अपडेट नहीं हो पाया — नेटवर्क आने पर दोबारा कोशिश करें।',
      err_upload:'अपलोड नहीं हुआ — दोबारा कोशिश करें।',
      err_too_large:'फ़ाइल बहुत बड़ी है (अधिकतम 10 MB)।',
      err_send:'भेज नहीं पाए — दोबारा कोशिश करें।',
      toast_pod_ok:'POD मिल गया',
      contacts_title:'ज़रूरी संपर्क',
      contacts_empty:'इस ट्रिप के लिए कोई संपर्क सेव नहीं है।',
      contact_agency:'बुकिंग एजेंसी',
      contact_transporter:'ट्रांसपोर्टर',
      contact_pickup:'क्लाइंट (पिकअप)',
      contact_destination:'गंतव्य संपर्क',
      contact_call:'कॉल', contact_whatsapp:'व्हाट्सऐप',
      chat_title:'डिस्पैच टीम से बात करें',
      chat_placeholder:'मैसेज लिखें…',
      chat_no_msgs:'अभी कोई मैसेज नहीं — हाय बोलें!',
      chat_attachment:'अटैचमेंट',
      docs_generated:'ट्रिप के पेपर',
      docs_uploaded:'फ़ाइल किए गए दस्तावेज़',
      docs_open_pdf:'PDF खोलने के लिए दबाएं',
      docs_empty:'इस ट्रिप के लिए अभी कोई दस्तावेज़ अपलोड नहीं है।',
      doc_lr:'लॉरी रसीद (LR)',
      doc_loading_advice:'लोडिंग एडवाइस',
      doc_trip_sheet:'ट्रिप शीट',
      doc_gate_pass:'गेट पास',
      doc_pod_blank:'POD फ़ॉर्म (खाली, डिलीवरी के लिए)',
      'st_Booking Created':'बुकिंग बनी',
      'st_Vehicle Placed':'गाड़ी लगी',
      st_Loading:'लोडिंग',
      'st_In Transit':'ट्रांज़िट में',
      st_Arrived:'पहुँच गए',
      st_Unloading:'अनलोडिंग',
      st_Delivered:'डिलीवर हो गया',
      'st_POD Received':'POD मिल गया',
      st_Closed:'बंद',
      st_Cancelled:'रद्द',
      inst_title:'होम स्क्रीन पर लगाएं',
      inst_body:'इस ट्रैकर को ऐप की तरह फ़ोन की होम स्क्रीन पर लगा लें — अगली बार एक टैप से खुलेगा।',
      inst_btn:'इंस्टॉल करें', inst_done:'हो गया', inst_later:'अभी नहीं',
      inst_ios_1:'Safari में नीचे शेयर बटन दबाएं',
      inst_ios_2:'"Add to Home Screen" चुनें',
      inst_ios_3:'"Add" दबाएं — हो गया।',
      inst_man_1:'ब्राउज़र का मेन्यू खोलें',
      inst_man_2:'"Install app" या "Add to Home screen" दबाएं',
    }
  };
  let LANG = 'hi';
  try { const saved = localStorage.getItem(LANG_KEY); if (saved === 'hi' || saved === 'en') LANG = saved; } catch (e) {}

  function t(key, vars) {
    let s = (I18N[LANG] && I18N[LANG][key]) || (I18N.en[key] || key);
    if (vars) for (const k in vars) s = s.replace('{' + k + '}', vars[k]);
    return s;
  }

  function applyLang() {
    document.documentElement.setAttribute('lang', LANG);
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const k = el.getAttribute('data-i18n');
      const v = I18N[LANG][k];
      if (typeof v === 'string') el.textContent = v;
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      const k = el.getAttribute('data-i18n-placeholder');
      const v = I18N[LANG][k];
      if (typeof v === 'string') el.setAttribute('placeholder', v);
    });
    document.querySelectorAll('#langSeg button').forEach((b) => {
      b.classList.toggle('on', b.getAttribute('data-lang') === LANG);
    });
    const lq = document.getElementById('langQuick');
    if (lq) lq.textContent = (LANG === 'hi') ? 'EN' : 'हिं';
    refreshStatusPill();
    refreshAppBarSub();
    if (currentStatusTextKey) setStatus(currentStatusKind, t(currentStatusTextKey, currentStatusTextVars));
    const podLbl = document.getElementById('podTriggerLabel');
    if (podLbl) podLbl.textContent = (podLbl.dataset.altKey ? t(podLbl.dataset.altKey) : t('pod_btn'));
    const podDone = document.querySelector('#podArea .pod-done-text');
    if (podDone) podDone.textContent = t(podDone.dataset.key || 'pod_done');
  }

  // Theme
  let THEME = 'auto';
  try { const t2 = localStorage.getItem(THEME_KEY); if (t2 === 'light' || t2 === 'dark' || t2 === 'auto') THEME = t2; } catch (e) {}
  const sysDark = window.matchMedia('(prefers-color-scheme: dark)');
  function effectiveTheme() {
    if (THEME === 'auto') return sysDark.matches ? 'dark' : 'light';
    return THEME;
  }
  function applyTheme() {
    const eff = effectiveTheme();
    document.documentElement.setAttribute('data-theme', eff);
    const meta = document.getElementById('themeColorMeta');
    if (meta) meta.setAttribute('content', eff === 'dark' ? '#0b0f17' : '#ffffff');
    document.querySelectorAll('#themeSeg button').forEach((b) => {
      b.classList.toggle('on', b.getAttribute('data-theme') === THEME);
    });
  }
  if (sysDark.addEventListener) sysDark.addEventListener('change', () => { if (THEME === 'auto') applyTheme(); });
  else if (sysDark.addListener) sysDark.addListener(() => { if (THEME === 'auto') applyTheme(); });
  document.querySelectorAll('#themeSeg button').forEach((btn) => {
    btn.addEventListener('click', () => {
      THEME = btn.getAttribute('data-theme');
      try { localStorage.setItem(THEME_KEY, THEME); } catch (e) {}
      applyTheme();
    });
  });

  function refreshStatusPill() {
    const pill = document.getElementById('tripStatusPill');
    const txt  = document.getElementById('tripStatusPillText');
    if (!pill || !txt) return;
    pill.setAttribute('data-status-key', currentStatus);
    txt.textContent = (I18N[LANG]['st_' + currentStatus]) || currentStatus;
    pill.classList.toggle('delivered', currentStatus === 'Delivered' || currentStatus === 'POD Received' || currentStatus === 'Closed');
  }
  function refreshAppBarSub() {
    const sub = document.getElementById('abSub');
    if (!sub) return;
    sub.textContent = TRIP_NO + ' · ' + ((I18N[LANG]['st_' + currentStatus]) || currentStatus);
  }

  let currentStatusKind = 'idle';
  let currentStatusTextKey = 'status_idle_initial';
  let currentStatusTextVars = null;
  let watchId = null, lastPos = null, pingCount = 0, wakeLock = null, pollTimer = null;

  const els = {
    start: document.getElementById('startBtn'),
    stop:  document.getElementById('stopBtn'),
    statusBox:  document.getElementById('status'),
    statusText: document.getElementById('statusText'),
    stats: document.getElementById('stats'),
    lastTs: document.getElementById('lastTs'),
    pingCount: document.getElementById('pingCount'),
    bufferCount: document.getElementById('bufferCount'),
    acc: document.getElementById('acc'),
    pill: document.getElementById('tripStatusPill'),
    steps: document.getElementById('steps'),
    podTrigger: document.getElementById('podTrigger'),
    podTriggerLabel: document.getElementById('podTriggerLabel'),
    podFile: document.getElementById('podFile'),
    podArea: document.getElementById('podArea'),
    podPreview: document.getElementById('podPreview'),
    toast: document.getElementById('toast'),
  };

  // Tabs
  function selectTab(name) {
    document.querySelectorAll('.tab-pane').forEach((el) => el.classList.toggle('active', el.getAttribute('data-tab') === name));
    document.querySelectorAll('.tab-btn').forEach((el) => el.classList.toggle('active', el.getAttribute('data-tab') === name));
    try { localStorage.setItem(TAB_KEY, name); } catch (e) {}
    window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
  }
  document.querySelectorAll('.tab-btn').forEach((btn) => btn.addEventListener('click', () => selectTab(btn.getAttribute('data-tab'))));
  try { const saved = localStorage.getItem(TAB_KEY); if (saved) selectTab(saved); } catch (e) {}

  // Drawer
  const drawer = document.getElementById('drawer');
  function openDrawer()  { drawer.classList.add('show');    drawer.setAttribute('aria-hidden','false'); }
  function closeDrawer() { drawer.classList.remove('show'); drawer.setAttribute('aria-hidden','true');  }
  document.getElementById('menuBtn').addEventListener('click', openDrawer);
  document.getElementById('drawerClose').addEventListener('click', closeDrawer);
  document.getElementById('drawerBackdrop').addEventListener('click', closeDrawer);
  document.getElementById('reInstall').addEventListener('click', () => {
    closeDrawer();
    if (isIOS) showSheet('ios');
    else if (deferredPrompt) showSheet('native');
    else showSheet('manual');
  });

  document.getElementById('langQuick').addEventListener('click', () => {
    LANG = (LANG === 'hi') ? 'en' : 'hi';
    try { localStorage.setItem(LANG_KEY, LANG); } catch (e) {}
    applyLang();
  });
  document.querySelectorAll('#langSeg button').forEach((btn) => {
    btn.addEventListener('click', () => {
      LANG = btn.getAttribute('data-lang');
      try { localStorage.setItem(LANG_KEY, LANG); } catch (e) {}
      applyLang();
    });
  });

  let toastTimer = null;
  function toast(kind, msg) {
    els.toast.className = 'toast ' + kind + ' show';
    els.toast.textContent = msg;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { els.toast.className = 'toast ' + kind; }, 3000);
  }

  // Milestones
  const STATUS_ORDER = ['Vehicle Placed','Loading','In Transit','Arrived','Unloading','Delivered','POD Received','Closed'];
  function setStarted() { try { localStorage.setItem(STARTED_KEY, '1'); } catch (e) {} }
  function getStarted() { try { return localStorage.getItem(STARTED_KEY) === '1'; } catch (e) { return false; } }

  function renderSteps() {
    refreshStatusPill();
    refreshAppBarSub();
    const curIdx = STATUS_ORDER.indexOf(currentStatus);
    els.steps.querySelectorAll('.step').forEach((el) => {
      const stage  = el.getAttribute('data-stage');
      const target = el.getAttribute('data-target') || null;
      let done = false, current = false;
      if (stage === 'started_to_pickup') {
        done = getStarted() || curIdx >= STATUS_ORDER.indexOf('Loading');
        current = !done;
      } else if (target) {
        const tIdx = STATUS_ORDER.indexOf(target);
        done = curIdx >= tIdx;
        current = !done && STATUS_ORDER[curIdx + 1] === target;
      }
      el.classList.toggle('done', done);
      el.classList.toggle('current', current);
    });
  }

  async function postMilestone(stage) {
    const r = await fetch(MILESTONE_URL, {
      method:'POST', headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({ stage }),
    });
    if (!r.ok) throw new Error('http_' + r.status);
    return await r.json();
  }
  els.steps.addEventListener('click', async (ev) => {
    const el = ev.target.closest('.step');
    if (!el || el.classList.contains('busy')) return;
    // Forward-only: completed milestones are not re-clickable. Tapping a done
    // step just shows a brief acknowledgement instead of re-firing it.
    if (el.classList.contains('done')) {
      const lblDone = el.querySelector('.lbl').textContent.trim();
      toast('ok', lblDone + ' ✓');
      return;
    }
    const stage = el.getAttribute('data-stage');
    const lbl   = el.querySelector('.lbl').textContent.trim();
    if (!confirm(t('confirm_action', { label: lbl }))) return;
    el.classList.add('busy');
    try {
      const res = await postMilestone(stage);
      if (!res.ok) {
        // Server's forward-only guard responded — surface its message if any
        if (res.reason === 'backward_blocked') { toast('err', res.message || t('err_update')); return; }
        throw new Error(res.reason || 'failed');
      }
      if (stage === 'started_to_pickup') setStarted();
      if (res.status) currentStatus = res.status;
      renderSteps();
      toast('ok', lbl);
    } catch (e) {
      toast('err', t('err_update'));
    } finally {
      el.classList.remove('busy');
    }
  });

  // POD
  function markPodDone(key) {
    const wrap = document.createElement('div');
    wrap.className = 'pod-done';
    const ic = document.createElement('i');
    ic.className = 'bi bi-check-circle-fill';
    const txt = document.createElement('span');
    txt.className = 'pod-done-text';
    txt.dataset.key = key;
    txt.textContent = t(key);
    wrap.appendChild(ic);
    wrap.appendChild(txt);
    els.podArea.innerHTML = '';
    els.podArea.appendChild(wrap);
    if (els.podPreview && els.podPreview.innerHTML) {
      els.podArea.appendChild(els.podPreview);
      els.podPreview.style.display = 'block';
    }
  }
  if (els.podFile) {
    els.podFile.addEventListener('change', async () => {
      const f = els.podFile.files && els.podFile.files[0];
      if (!f) return;
      if (f.size > 10 * 1024 * 1024) { toast('err', t('err_too_large')); return; }
      if (f.type && f.type.startsWith('image/')) {
        const url = URL.createObjectURL(f);
        els.podPreview.innerHTML = '<img src="' + url + '" alt="POD preview">';
        els.podPreview.style.display = 'block';
      }
      els.podTrigger.classList.add('uploading');
      els.podTriggerLabel.dataset.altKey = 'pod_uploading';
      els.podTriggerLabel.textContent = t('pod_uploading');
      const fd = new FormData(); fd.append('pod_file', f);
      try {
        const r = await fetch(POD_URL, { method:'POST', body: fd });
        const j = await r.json();
        if (!r.ok || !j.ok) throw new Error(j.reason || 'failed');
        currentStatus = 'POD Received';
        markPodDone('pod_done');
        renderSteps();
        toast('ok', t('toast_pod_ok'));
      } catch (e) {
        els.podTrigger.classList.remove('uploading');
        delete els.podTriggerLabel.dataset.altKey;
        els.podTriggerLabel.textContent = t('pod_btn');
        toast('err', t('err_upload'));
      }
    });
  }
  if (podAlready) markPodDone('pod_already');

  // Live location
  function setStatus(kind, text) {
    els.statusBox.className = 'status status-' + kind;
    els.statusBox.querySelector('.dot').className = 'dot dot-' + kind;
    els.statusText.textContent = text;
    currentStatusKind = kind;
  }
  function setStatusI18n(kind, key, vars) {
    currentStatusKind = kind;
    currentStatusTextKey = key;
    currentStatusTextVars = vars || null;
    setStatus(kind, t(key, vars));
  }
  function readBuffer() { try { return JSON.parse(localStorage.getItem(BUF_KEY) || '[]'); } catch (e) { return []; } }
  function writeBuffer(arr) { try { localStorage.setItem(BUF_KEY, JSON.stringify(arr.slice(-200))); } catch (e) {} }
  function bufferPing(p) {
    const buf = readBuffer(); buf.push(p); writeBuffer(buf);
    els.bufferCount.textContent = buf.length;
  }
  async function postPing(p) {
    const r = await fetch(PING_URL, { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(p), keepalive: true });
    if (!r.ok) throw new Error('http_' + r.status);
    return await r.json();
  }
  async function flushBuffer() {
    let buf = readBuffer();
    if (buf.length === 0) { els.bufferCount.textContent = 0; return; }
    setStatusI18n('buffer', 'st_flushing', { n: buf.length });
    while (buf.length) {
      try { await postPing(buf[0]); buf.shift(); writeBuffer(buf); els.bufferCount.textContent = buf.length; }
      catch (e) { break; }
    }
  }
  async function sendCurrent() {
    if (!lastPos) return;
    const p = { lat:lastPos.coords.latitude, lng:lastPos.coords.longitude, acc:lastPos.coords.accuracy, speed:lastPos.coords.speed, ts:Date.now() };
    try {
      await postPing(p);
      pingCount++;
      els.pingCount.textContent = pingCount;
      els.lastTs.textContent = new Date().toLocaleTimeString();
      els.acc.textContent = Math.round(p.acc) + ' m';
      setStatusI18n('active', 'st_active');
      flushBuffer();
    } catch (e) {
      bufferPing(p);
      setStatusI18n('buffer', 'st_offline');
    }
  }
  async function requestWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try { wakeLock = await navigator.wakeLock.request('screen'); } catch (e) {}
  }
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && watchId !== null && !wakeLock) requestWakeLock();
  });
  function start() {
    if (!('geolocation' in navigator)) { setStatusI18n('error', 'st_no_geo'); return; }
    setStatusI18n('idle', 'st_requesting');
    watchId = navigator.geolocation.watchPosition(
      (pos) => { lastPos = pos; },
      (err) => { setStatusI18n('error', 'st_loc_err', { msg: (err && err.message) || 'unknown' }); },
      { enableHighAccuracy: true, maximumAge: 10000, timeout: 60000 }
    );
    requestWakeLock();
    pollTimer = setInterval(sendCurrent, POLL_MS);
    setTimeout(sendCurrent, 4000);
    els.start.style.display = 'none';
    els.stop.style.display  = 'flex';
    els.stats.style.display = 'grid';
    setStatusI18n('active', 'st_started');
    if ('serviceWorker' in navigator) navigator.serviceWorker.register(SW_URL).catch(() => {});
  }
  async function stop() {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
    if (pollTimer) clearInterval(pollTimer);
    if (wakeLock && wakeLock.release) { try { await wakeLock.release(); } catch (e) {} wakeLock = null; }
    try { await fetch(STOP_URL, { method:'POST', keepalive:true }); } catch (e) {}
    els.start.style.display = 'flex';
    els.stop.style.display  = 'none';
    els.stats.style.display = 'none';
    setStatusI18n('idle', 'st_stopped');
  }
  els.start.addEventListener('click', start);
  els.stop.addEventListener('click', stop);

  // Chat
  const chatEls = {
    thread:    document.getElementById('chatThread'),
    empty:     document.getElementById('chatEmpty'),
    input:     document.getElementById('composerInput'),
    send:      document.getElementById('sendBtn'),
    attachBtn: document.getElementById('attachBtn'),
    cameraBtn: document.getElementById('cameraBtn'),
    attachIn:  document.getElementById('attachInput'),
    cameraIn:  document.getElementById('cameraInput'),
    preview:   document.getElementById('composerPreview'),
    pName:     document.getElementById('composerPreviewName'),
    pThumb:    document.getElementById('composerPreviewThumb'),
    pRemove:   document.getElementById('composerPreviewRemove'),
    badge:     document.getElementById('chatBadge'),
  };

  let lastMsgId = 0;
  let unreadStaffCount = 0;
  let pendingFile = null;

  function fmtMsgTime(iso) {
    if (!iso) return '';
    const d = new Date((iso + '').replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    const today = new Date();
    const sameDay = d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && d.getDate() === today.getDate();
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    if (sameDay) return hh + ':' + mm;
    const dd = String(d.getDate()).padStart(2, '0');
    const mo = String(d.getMonth() + 1).padStart(2, '0');
    return dd + '/' + mo + ' ' + hh + ':' + mm;
  }

  function bubbleEl(m) {
    const wrap = document.createElement('div');
    wrap.className = 'bubble ' + (m.direction || 'staff');
    wrap.dataset.id = m.id;
    if (m.direction === 'system') {
      wrap.textContent = m.body || '';
      return wrap;
    }
    if (m.body) {
      const b = document.createElement('div');
      b.className = 'b-body';
      b.textContent = m.body;
      wrap.appendChild(b);
    }
    if (m.attachment_path) {
      const a = document.createElement('div');
      a.className = 'b-attach';
      const url = ATTACH_URL_BASE + m.id;
      const isImg = (m.attachment_mime || '').indexOf('image/') === 0;
      if (isImg) {
        const link = document.createElement('a');
        link.href = url; link.target = '_blank'; link.rel = 'noopener';
        const img = document.createElement('img');
        img.src = url; img.alt = m.attachment_orig || 'Attachment';
        link.appendChild(img);
        a.appendChild(link);
      } else {
        const link = document.createElement('a');
        link.className = 'b-attach-link';
        link.href = url; link.target = '_blank'; link.rel = 'noopener';
        link.innerHTML = '<i class="bi bi-paperclip"></i><span>' + (m.attachment_orig || t('chat_attachment')) + '</span>';
        a.appendChild(link);
      }
      wrap.appendChild(a);
    }
    const meta = document.createElement('div');
    meta.className = 'b-meta';
    meta.textContent = fmtMsgTime(m.created_at);
    wrap.appendChild(meta);
    return wrap;
  }
  function appendMessage(m, skipScroll) {
    chatEls.empty.hidden = true;
    chatEls.thread.appendChild(bubbleEl(m));
    if (m.id && !isNaN(parseInt(m.id, 10))) {
      const n = parseInt(m.id, 10);
      if (n > lastMsgId) lastMsgId = n;
    }
    if (!skipScroll) chatEls.thread.scrollTop = chatEls.thread.scrollHeight;
  }
  function renderInitialMessages() {
    chatEls.thread.innerHTML = '';
    if (!INITIAL_MESSAGES || INITIAL_MESSAGES.length === 0) {
      chatEls.empty.hidden = false;
      return;
    }
    INITIAL_MESSAGES.forEach((m) => appendMessage(m, true));
    chatEls.thread.scrollTop = chatEls.thread.scrollHeight;
  }
  function setBadge(n) {
    unreadStaffCount = n;
    if (n > 0) { chatEls.badge.hidden = false; chatEls.badge.textContent = n > 9 ? '9+' : String(n); }
    else { chatEls.badge.hidden = true; }
  }
  function currentTab() {
    const active = document.querySelector('.tab-btn.active');
    return active ? active.getAttribute('data-tab') : '';
  }
  async function fetchNewMessages() {
    try {
      const r = await fetch(MSG_URL_LIST + '?since=' + lastMsgId, { credentials:'same-origin' });
      if (!r.ok) return;
      const j = await r.json();
      if (!j.ok || !j.messages) return;
      let newStaff = 0;
      j.messages.forEach((m) => {
        if (chatEls.thread.querySelector('[data-id="' + m.id + '"]')) return;
        appendMessage(m);
        if (m.direction === 'staff') newStaff++;
      });
      if (newStaff > 0 && currentTab() !== 'chat') setBadge(unreadStaffCount + newStaff);
    } catch (e) {}
  }
  function clearComposerFile() {
    pendingFile = null;
    chatEls.preview.hidden = true;
    chatEls.pThumb.innerHTML = '';
    chatEls.pName.textContent = '';
    chatEls.attachIn.value = '';
    chatEls.cameraIn.value = '';
    updateSendEnabled();
  }
  function setComposerFile(f) {
    pendingFile = f;
    chatEls.preview.hidden = false;
    chatEls.pName.textContent = f.name;
    chatEls.pThumb.innerHTML = '';
    if (f.type && f.type.indexOf('image/') === 0) {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      chatEls.pThumb.appendChild(img);
    }
    updateSendEnabled();
  }
  function updateSendEnabled() {
    const hasText = chatEls.input.value.trim().length > 0;
    chatEls.send.disabled = !(hasText || pendingFile);
  }
  chatEls.attachBtn.addEventListener('click', () => chatEls.attachIn.click());
  chatEls.cameraBtn.addEventListener('click', () => chatEls.cameraIn.click());
  chatEls.attachIn.addEventListener('change', () => { const f = chatEls.attachIn.files[0]; if (f) setComposerFile(f); });
  chatEls.cameraIn.addEventListener('change', () => { const f = chatEls.cameraIn.files[0]; if (f) setComposerFile(f); });
  chatEls.pRemove.addEventListener('click', clearComposerFile);
  chatEls.input.addEventListener('input', () => {
    chatEls.input.style.height = 'auto';
    chatEls.input.style.height = Math.min(chatEls.input.scrollHeight, 120) + 'px';
    updateSendEnabled();
  });
  async function sendMessage() {
    if (chatEls.send.disabled) return;
    const text = chatEls.input.value.trim();
    const file = pendingFile;
    chatEls.send.disabled = true;
    const fd = new FormData();
    if (text)  fd.append('body', text);
    if (file)  fd.append('attachment', file);
    const tempId = 'tmp-' + Date.now();
    appendMessage({
      id: tempId, direction:'driver',
      body: text || null,
      attachment_path: file ? 'pending' : null,
      attachment_mime: file ? file.type : null,
      attachment_orig: file ? file.name : null,
      created_at: new Date().toISOString().slice(0,19).replace('T',' '),
    });
    chatEls.input.value = '';
    chatEls.input.style.height = 'auto';
    clearComposerFile();
    try {
      const r = await fetch(MSG_URL_SEND, { method:'POST', body: fd });
      const j = await r.json();
      if (!r.ok || !j.ok) throw new Error(j.reason || 'failed');
      const tmp = chatEls.thread.querySelector('[data-id="' + tempId + '"]');
      if (tmp) tmp.remove();
      appendMessage(j.message);
    } catch (e) {
      const tmp = chatEls.thread.querySelector('[data-id="' + tempId + '"]');
      if (tmp) tmp.remove();
      toast('err', t('err_send'));
    } finally { updateSendEnabled(); }
  }
  chatEls.send.addEventListener('click', sendMessage);
  chatEls.input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
  document.querySelectorAll('.tab-btn[data-tab="chat"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      setBadge(0);
      setTimeout(() => { chatEls.thread.scrollTop = chatEls.thread.scrollHeight; }, 50);
    });
  });
  renderInitialMessages();
  setInterval(fetchNewMessages, 15000);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') fetchNewMessages();
  });

  // Install prompt
  const sheet = document.getElementById('installSheet');
  const installBtn   = document.getElementById('installBtn');
  const installDone  = document.getElementById('installDone');
  const installLater = document.getElementById('installLater');
  const hintIos      = document.getElementById('installHintIos');
  const hintManual   = document.getElementById('installHintManual');
  const ua = navigator.userAgent || '';
  const isIOS = (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) ||
                (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  const isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  let deferredPrompt = null;
  let installSheetShown = false;
  function markInstallSeen() { try { localStorage.setItem(INSTALL_KEY, '1'); } catch (e) {} }
  function alreadySeen()    { try { return localStorage.getItem(INSTALL_KEY) === '1'; } catch (e) { return false; } }
  function hideSheet() { sheet.classList.remove('show'); sheet.setAttribute('aria-hidden','true'); installSheetShown = false; }
  function showSheet(mode) {
    hintIos.hidden     = (mode !== 'ios');
    hintManual.hidden  = (mode !== 'manual');
    installBtn.hidden  = (mode !== 'native');
    installDone.hidden = (mode === 'native');
    sheet.classList.add('show');
    sheet.setAttribute('aria-hidden','false');
    installSheetShown = true;
    applyLang();
  }
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    if (!isStandalone && !alreadySeen()) showSheet('native');
  });
  window.addEventListener('appinstalled', () => { markInstallSeen(); hideSheet(); });
  installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    try { const { outcome } = await deferredPrompt.userChoice; if (outcome === 'accepted') markInstallSeen(); } catch (e) {}
    deferredPrompt = null;
    hideSheet();
  });
  installDone.addEventListener('click',  () => { markInstallSeen(); hideSheet(); });
  installLater.addEventListener('click', () => { markInstallSeen(); hideSheet(); });
  sheet.addEventListener('click', (e) => { if (e.target === sheet) { markInstallSeen(); hideSheet(); } });
  if (!isStandalone && !alreadySeen()) {
    if (isIOS) setTimeout(() => showSheet('ios'), 800);
    else       setTimeout(() => { if (!installSheetShown) showSheet('manual'); }, 2500);
  }

  // Initial render
  applyTheme();
  els.bufferCount.textContent = readBuffer().length;
  applyLang();
  renderSteps();
  if (readBuffer().length > 0) flushBuffer();
})();
</script>
</body></html>
