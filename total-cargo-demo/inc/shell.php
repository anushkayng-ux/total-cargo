<?php
/**
 * Shared retro app shell. Call shell_start() then output content, then shell_end().
 */
function shell_start(string $activeMenu, string $windowTitle, string $pageTitle = 'Total Cargo Express'): void {
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= esc($pageTitle) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/retro.css">
</head>
<body>
<div class="stage">
  <div class="app">
    <a class="app-topbar" href="index.php">
      TOTAL CARGO EXPRESS PRIVATE LIMITED &nbsp;–&nbsp; DELHI [2026-2027]
      <small>Demo UI &middot; classic layout preview &middot; read-only, connected to live data</small>
    </a>
    <div class="app-body">
      <div class="sidebar">
        <?php
        $items = [
            'General Masters'  => 'index.php',
            'Transportation'   => 'transport.php',
            'Accounts'         => 'accounts.php',
            'Search'           => 'bookings.php',
            'Change Password'  => null,
            'Administration'   => 'administration.php',
            'Exit'             => null,
        ];
        foreach ($items as $label => $href) {
            $isActive = strcasecmp($label, $activeMenu) === 0;
            if ($href) {
                printf('<a class="%s" href="%s">%s</a>' . "\n", $isActive ? 'active' : '', esc($href), esc($label));
            } else {
                printf('<a class="inert" title="Not part of this demo">%s</a>' . "\n", esc($label));
            }
        }
        ?>
      </div>
      <div class="desk">
        <div class="win">
          <div class="win-titlebar">
            <div class="win-title"><?= esc($windowTitle) ?></div>
          </div>
          <div class="toolbar">
            <div class="tbtn"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#2f6fe0"/><path d="M12 7v10M7 12h10" stroke="#fff" stroke-width="2"/></svg>New</div>
            <div class="tbtn"><svg viewBox="0 0 24 24" fill="none"><path d="M4 20l1-4L16 5l3 3L8 19l-4 1z" stroke="#3a4a68" stroke-width="1.6"/></svg>Edit</div>
            <div class="tbtn"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="1.5" fill="#2f6fe0"/><rect x="7" y="6" width="10" height="5" fill="#fff"/><rect x="7" y="14" width="10" height="4" fill="#fff"/></svg>Save</div>
            <div class="tbtn"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#d33" stroke-width="2"/><line x1="6" y1="6" x2="18" y2="18" stroke="#d33" stroke-width="2"/></svg>Cancel</div>
            <div class="tbtn"><svg viewBox="0 0 24 24" fill="none"><circle cx="10" cy="10" r="6" stroke="#3a4a68" stroke-width="1.8"/><line x1="15" y1="15" x2="20" y2="20" stroke="#3a4a68" stroke-width="1.8"/></svg>View</div>
            <div class="tbtn" onclick="window.print()"><svg viewBox="0 0 24 24" fill="none"><rect x="6" y="3" width="12" height="6" fill="#3a4a68"/><rect x="4" y="9" width="16" height="7" fill="#7a8bab"/><rect x="7" y="15" width="10" height="6" fill="#fff" stroke="#3a4a68"/></svg>Print</div>
            <a class="tbtn" href="index.php"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" fill="#6b7b9a"/><line x1="8" y1="8" x2="16" y2="16" stroke="#fff" stroke-width="2"/><line x1="16" y1="8" x2="8" y2="16" stroke="#fff" stroke-width="2"/></svg>Close</a>
            <?php if (!empty($GLOBALS['_shell_search'])): $s = $GLOBALS['_shell_search']; ?>
            <form class="searchbar" method="get" style="margin-left:auto;">
              <input type="text" name="q" placeholder="<?= esc($s['placeholder']) ?>" value="<?= esc($s['value']) ?>">
              <button type="submit">Find</button>
            </form>
            <?php endif; ?>
          </div>
    <?php
}

function shell_search(string $placeholder, string $value): void {
    $GLOBALS['_shell_search'] = ['placeholder' => $placeholder, 'value' => $value];
}

function shell_end(): void {
    ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
    <?php
}
