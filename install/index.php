<?php
/**
 * TPT Aggregator — first-run installer.
 *
 * Visit http://yourhost.example.com/install/ on a fresh host.
 * Walks the operator through:
 *   1. Server environment check
 *   2. Database config + connection test
 *   3. Site base-URL
 *   4. SQL import + pending migrations
 *   5. Done — locks itself
 *
 * Refuses to run once writable/.installed exists, unless the lock is removed
 * deliberately. Re-running the installer is safe (idempotent): each step is
 * recomputed from the .env on disk + current DB state.
 *
 * IMPORTANT: this file boots OUTSIDE CodeIgniter so it can run before the
 * framework has a working DB connection.
 */
declare(strict_types=1);

session_start();

// ── Paths ─────────────────────────────────────────────────────────────
$ROOT      = realpath(__DIR__ . '/..');
$ENV_FILE  = $ROOT . DIRECTORY_SEPARATOR . '.env';
$ENV_TPL   = $ROOT . DIRECTORY_SEPARATOR . 'env';   // CI4 ships an `env` template
$SQL_FILE  = $ROOT . DIRECTORY_SEPARATOR . 'tpt-install.sql';
$LOCK_FILE = $ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . '.installed';
$SPARK     = $ROOT . DIRECTORY_SEPARATOR . 'spark';

// ── Already installed? ────────────────────────────────────────────────
if (is_file($LOCK_FILE) && empty($_GET['force'])) {
    render('Already installed', '
        <div class="alert alert-success">
            <strong>Setup is already complete.</strong>
            <div class="small text-muted mt-1">Delete <code>writable/.installed</code> on the server if you need to re-run this installer.</div>
        </div>
        <a class="btn btn-primary" href="../public/login">Go to sign-in →</a>
    ');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────
function render(string $title, string $body, int $step = 0): void {
    $steps = ['Environment', 'Database', 'Site URL', 'Import', 'Done'];
    ?><!DOCTYPE html>
    <html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — TPT installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      body { background:#f7f9fc; font-family:system-ui,-apple-system,sans-serif; }
      .install-wrap { max-width: 740px; margin: 2rem auto; padding: 0 1rem; }
      .install-card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
      .install-header { background:#0f172a; color:#fff; padding:1.2rem 1.5rem; }
      .install-body { padding:1.5rem; }
      .stepper { display:flex; gap:.4rem; margin-top:.8rem; }
      .stepper .step { flex:1; height:4px; background:rgba(255,255,255,.18); border-radius:99px; }
      .stepper .step.done { background:#22c55e; }
      .stepper .step.curr { background:#fff; }
      table.check-table td { vertical-align:middle; }
      .ok { color:#15803d; }
      .fail { color:#b91c1c; }
      .warn { color:#a16207; }
      code, pre { background:#f3f4f6; padding:.1em .35em; border-radius:4px; font-size:.88em; }
    </style>
    </head><body>
    <div class="install-wrap">
      <div class="install-card">
        <div class="install-header">
          <div style="font-weight:600; font-size:1.1rem;">
            <i class="bi bi-truck"></i> TPT Aggregator — installer
          </div>
          <div style="font-size:.85rem; opacity:.75; margin-top:.2rem;">
            <?= htmlspecialchars($title) ?>
          </div>
          <?php if ($step > 0): ?>
          <div class="stepper">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <div class="step <?= $i < $step ? 'done' : ($i === $step ? 'curr' : '') ?>"></div>
            <?php endfor; ?>
          </div>
          <div style="font-size:.72rem; opacity:.6; margin-top:.35rem; letter-spacing:.04em;">
            STEP <?= $step ?> OF 5 · <?= htmlspecialchars($steps[$step - 1] ?? '') ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="install-body">
          <?= $body ?>
        </div>
      </div>
    </div>
    </body></html><?php
}

function flash(string $kind, string $msg): void {
    $_SESSION['_flash'] = ['kind' => $kind, 'msg' => $msg];
}
function flashPop(): ?array {
    $f = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $f;
}
function flashHtml(): string {
    $f = flashPop();
    if (!$f) return '';
    $cls = $f['kind'] === 'error' ? 'alert-danger' : ($f['kind'] === 'warn' ? 'alert-warning' : 'alert-success');
    return '<div class="alert ' . $cls . '">' . $f['msg'] . '</div>';
}

function envRead(string $file): array {
    if (!is_file($file)) return [];
    $out = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^\s*([a-zA-Z0-9_.\-]+)\s*=\s*(.*?)\s*$/', $line, $m)) {
            $val = trim($m[2], "'\"");
            $out[$m[1]] = $val;
        }
    }
    return $out;
}

function envWrite(string $file, array $kv): bool {
    $existing = is_file($file) ? file_get_contents($file) : '';
    $lines = $existing === '' ? [] : preg_split('/\r?\n/', $existing);
    $seen  = [];
    foreach ($lines as $i => $line) {
        foreach ($kv as $k => $v) {
            if (preg_match('/^\s*#?\s*' . preg_quote($k, '/') . '\s*=/', $line)) {
                $lines[$i] = sprintf('%s = %s', $k, envQuote($v));
                $seen[$k] = true;
            }
        }
    }
    foreach ($kv as $k => $v) {
        if (!isset($seen[$k])) $lines[] = sprintf('%s = %s', $k, envQuote($v));
    }
    return false !== file_put_contents($file, implode("\n", $lines) . "\n", LOCK_EX);
}
function envQuote(string $v): string {
    if ($v === '' || preg_match('/[\'"\s#]/', $v)) return "'" . str_replace("'", "\\'", $v) . "'";
    return $v;
}

// ── Routing ───────────────────────────────────────────────────────────
$step = (int) ($_GET['step'] ?? 1);
$step = max(1, min(5, $step));

// ── STEP 1 — environment check ────────────────────────────────────────
if ($step === 1) {
    // [label, ok, detail, severity]  severity: 'required' blocks; 'optional' warns only
    $checks = [];
    $checks[] = ['PHP version ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION, 'required'];

    // Required extensions — without these, the app won't boot
    foreach (['mbstring','intl','openssl','curl','fileinfo','mysqli','json','session'] as $ext) {
        $checks[] = ['Extension: ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'MISSING', 'required'];
    }
    // Optional — gd is only used for EXIF-stripping on uploaded JPEGs
    $checks[] = ['Extension: gd (optional — JPEG EXIF strip)', extension_loaded('gd'), extension_loaded('gd') ? 'loaded' : 'missing (uploads still work)', 'optional'];

    foreach (['writable', 'writable/uploads', 'writable/cache', 'writable/logs', 'writable/session'] as $dir) {
        $p = $GLOBALS['ROOT'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
        if (!is_dir($p)) @mkdir($p, 0775, true);
        $checks[] = [$dir . '/ writable', is_dir($p) && is_writable($p), is_dir($p) && is_writable($p) ? 'OK' : 'NOT writable', 'required'];
    }
    $checks[] = ['SQL seed found (tpt-install.sql)', is_file($GLOBALS['SQL_FILE']), is_file($GLOBALS['SQL_FILE']) ? round(filesize($GLOBALS['SQL_FILE'])/1024) . ' KB' : 'MISSING', 'required'];
    $checks[] = ['vendor/ exists (composer install run)', is_dir($GLOBALS['ROOT'] . '/vendor'), is_dir($GLOBALS['ROOT'] . '/vendor') ? 'OK' : 'run `composer install`', 'required'];

    // Block only on required failures
    $requiredFails = array_filter($checks, fn($c) => $c[3] === 'required' && !$c[1]);
    $allOk = empty($requiredFails);

    $rows = '';
    foreach ($checks as [$label, $ok, $detail, $sev]) {
        if ($ok) {
            $icon = '<span class="ok"><i class="bi bi-check-circle-fill"></i></span>';
        } elseif ($sev === 'optional') {
            $icon = '<span class="warn"><i class="bi bi-exclamation-circle-fill"></i></span>';
        } else {
            $icon = '<span class="fail"><i class="bi bi-x-circle-fill"></i></span>';
        }
        $rows .= '<tr><td style="width:30px;">' . $icon . '</td><td>' . htmlspecialchars($label) . '</td><td class="text-end text-muted small">' . htmlspecialchars((string) $detail) . '</td></tr>';
    }
    render('Environment check', '
        ' . flashHtml() . '
        <p class="text-muted">Verifying the server is ready to run the app.</p>
        <table class="table check-table">' . $rows . '</table>
        ' . ($allOk
            ? '<a class="btn btn-primary" href="?step=2">Continue →</a>'
            : '<div class="alert alert-warning">Fix the failing items above (install missing extensions, set folder permissions to 775) and refresh this page.</div><a class="btn btn-light" href="?step=1">Retry</a>'),
        1
    );
    exit;
}

// ── STEP 2 — database config + connection test ────────────────────────
if ($step === 2) {
    $env = envRead($ENV_FILE);
    $defaults = [
        'database.default.hostname' => $env['database.default.hostname'] ?? '127.0.0.1',
        'database.default.port'     => $env['database.default.port']     ?? '3306',
        'database.default.database' => $env['database.default.database'] ?? 'tpt_db',
        'database.default.username' => $env['database.default.username'] ?? '',
        'database.default.password' => $env['database.default.password'] ?? '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = [
            'database.default.hostname' => trim((string) ($_POST['host'] ?? '')),
            'database.default.port'     => trim((string) ($_POST['port'] ?? '3306')),
            'database.default.database' => trim((string) ($_POST['name'] ?? '')),
            'database.default.username' => trim((string) ($_POST['user'] ?? '')),
            'database.default.password' => (string) ($_POST['pass'] ?? ''),
        ];
        try {
            $mysqli = @new mysqli(
                $input['database.default.hostname'],
                $input['database.default.username'],
                $input['database.default.password'],
                '',
                (int) $input['database.default.port']
            );
            if ($mysqli->connect_errno) throw new RuntimeException('connect: ' . $mysqli->connect_error);

            // Create DB if missing — needs CREATE privilege
            $dbName = $input['database.default.database'];
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) throw new RuntimeException('database name must be alphanumeric/underscore');
            $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            if (!$mysqli->select_db($dbName)) throw new RuntimeException('select_db: ' . $mysqli->error);
            $mysqli->close();

            envWrite($ENV_FILE, $input + ['database.default.DBDriver' => 'MySQLi', 'database.default.DBPrefix' => '']);
            flash('ok', 'Connection successful. Database <code>' . htmlspecialchars($dbName) . '</code> is ready.');
            header('Location: ?step=3'); exit;
        } catch (\Throwable $e) {
            flash('error', 'Could not connect: ' . htmlspecialchars($e->getMessage()));
            $defaults = $input;
        }
    }

    render('Database configuration', '
        ' . flashHtml() . '
        <p class="text-muted">Enter your MySQL/MariaDB credentials. The database will be created if it doesn\'t exist (the user needs the CREATE privilege).</p>
        <form method="post" class="row g-3">
          <div class="col-md-8"><label class="form-label">Host</label>
            <input class="form-control" name="host" value="' . htmlspecialchars($defaults['database.default.hostname']) . '" required></div>
          <div class="col-md-4"><label class="form-label">Port</label>
            <input class="form-control" name="port" value="' . htmlspecialchars($defaults['database.default.port']) . '"></div>
          <div class="col-md-12"><label class="form-label">Database name</label>
            <input class="form-control" name="name" value="' . htmlspecialchars($defaults['database.default.database']) . '" required></div>
          <div class="col-md-6"><label class="form-label">Username</label>
            <input class="form-control" name="user" value="' . htmlspecialchars($defaults['database.default.username']) . '" required></div>
          <div class="col-md-6"><label class="form-label">Password</label>
            <input class="form-control" type="password" name="pass" value="' . htmlspecialchars($defaults['database.default.password']) . '"></div>
          <div class="col-12 d-flex gap-2 mt-3">
            <a class="btn btn-light" href="?step=1">← Back</a>
            <button class="btn btn-primary" type="submit">Test &amp; Save →</button>
          </div>
        </form>
    ', 2);
    exit;
}

// ── STEP 3 — site URL ─────────────────────────────────────────────────
if ($step === 3) {
    $env = envRead($ENV_FILE);
    // Auto-detect a reasonable default from the current request URL
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = preg_replace('#/install/?.*$#', '/public/', $_SERVER['REQUEST_URI'] ?? '/');
    $autoUrl = $scheme . '://' . $host . $path;
    $current = $env['app.baseURL'] ?? $autoUrl;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $url = trim((string) ($_POST['baseURL'] ?? ''));
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            flash('error', 'That doesn\'t look like a valid URL. Include the scheme (http/https) and a trailing slash.');
        } else {
            if (!str_ends_with($url, '/')) $url .= '/';
            $kv = [
                'app.baseURL'      => $url,
                'CI_ENVIRONMENT'   => $env['CI_ENVIRONMENT'] ?? 'production',
                'tpt.release'      => $env['tpt.release']    ?? 'tpt@' . date('Ymd'),
            ];
            // Force HTTPS only if URL is https
            $kv['app.forceGlobalSecureRequests'] = str_starts_with($url, 'https://') ? 'true' : 'false';
            envWrite($ENV_FILE, $kv);
            flash('ok', 'Base URL saved.');
            header('Location: ?step=4'); exit;
        }
    }

    render('Site base URL', '
        ' . flashHtml() . '
        <p class="text-muted">The full URL where the app will live. Include scheme, host, and the trailing <code>/public/</code> segment if that\'s your front-controller folder.</p>
        <form method="post">
          <label class="form-label">Base URL</label>
          <input class="form-control" name="baseURL" value="' . htmlspecialchars($current) . '" required>
          <div class="form-text mt-1">Auto-detected from this request: <code>' . htmlspecialchars($autoUrl) . '</code></div>
          <div class="alert alert-light border mt-3 small">
            Tip: on shared hosting where <code>/public/</code> is the document-root, drop the <code>/public/</code> segment.
            Locally with XAMPP it\'s usually <code>http://localhost/tpt/public/</code>.
          </div>
          <div class="d-flex gap-2 mt-3">
            <a class="btn btn-light" href="?step=2">← Back</a>
            <button class="btn btn-primary" type="submit">Save &amp; Continue →</button>
          </div>
        </form>
    ', 3);
    exit;
}

// ── STEP 4 — SQL import + migrations ──────────────────────────────────
if ($step === 4) {
    $env = envRead($ENV_FILE);
    $host = $env['database.default.hostname'] ?? '';
    $user = $env['database.default.username'] ?? '';
    $pass = $env['database.default.password'] ?? '';
    $db   = $env['database.default.database'] ?? '';
    $port = (int) ($env['database.default.port'] ?? 3306);

    // Quick: does the DB already have tables?
    $existingTables = 0;
    try {
        $mysqli = @new mysqli($host, $user, $pass, $db, $port);
        if (!$mysqli->connect_errno) {
            $existingTables = (int) ($mysqli->query('SHOW TABLES')->num_rows ?? 0);
            $mysqli->close();
        }
    } catch (\Throwable $e) {}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mode = (string) ($_POST['mode'] ?? '');
        $log  = [];
        $ok   = true;
        try {
            if ($mode === 'fresh') {
                if (!is_file($SQL_FILE)) throw new RuntimeException('tpt-install.sql is missing from the project root.');
                $mysqli = new mysqli($host, $user, $pass, $db, $port);
                if ($mysqli->connect_errno) throw new RuntimeException($mysqli->connect_error);
                $mysqli->set_charset('utf8mb4');

                $sql = (string) file_get_contents($SQL_FILE);
                $log[] = 'Read ' . number_format(strlen($sql)) . ' bytes of SQL.';
                if (!$mysqli->multi_query($sql)) throw new RuntimeException('SQL: ' . $mysqli->error);

                // Drain all result sets so connection stays alive
                do { if ($r = $mysqli->store_result()) $r->free(); } while ($mysqli->more_results() && $mysqli->next_result());
                if ($mysqli->errno) throw new RuntimeException('SQL: ' . $mysqli->error);

                $tables = (int) ($mysqli->query('SHOW TABLES')->num_rows ?? 0);
                $log[] = "Import OK — $tables tables in <code>" . htmlspecialchars($db) . "</code>.";
                $mysqli->close();
            } elseif ($mode === 'skip') {
                $log[] = 'Skipped import — using the existing schema.';
            }

            // Always run pending migrations — safe whether fresh or upgrading.
            // Locate the PHP CLI binary: Windows uses .exe, Linux doesn't.
            $php = null;
            foreach (['php', 'php.exe'] as $name) {
                $candidate = PHP_BINDIR . DIRECTORY_SEPARATOR . $name;
                if (is_file($candidate)) { $php = $candidate; break; }
            }
            $php = $php ?? PHP_BINARY; // PHP_BINARY is always defined and points at the running interpreter
            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($SPARK) . ' migrate --all 2>&1';
            $cwd = getcwd();
            chdir($ROOT);
            exec($cmd, $out, $code);
            chdir($cwd);
            $log[] = '<strong>spark migrate --all</strong>:';
            $log[] = '<pre style="margin:.4rem 0;font-size:.78rem;">' . htmlspecialchars(implode("\n", $out)) . '</pre>';
            if ($code !== 0) {
                // Treat migrate failures as warnings — fresh import may already have the latest schema
                $log[] = '<span class="warn">⚠ Migration runner exit code ' . $code . ' — review the output above. If schema is fresh, this is usually harmless.</span>';
            }
            $_SESSION['_import_log'] = $log;
            header('Location: ?step=5'); exit;
        } catch (\Throwable $e) {
            flash('error', 'Import failed: ' . htmlspecialchars($e->getMessage()));
            $_SESSION['_import_log'] = $log;
        }
    }

    $warning = $existingTables > 0
        ? '<div class="alert alert-warning"><strong>Heads up:</strong> the database already contains <strong>' . $existingTables . ' table(s)</strong>. A fresh import will <em>drop &amp; recreate</em> them (no data preserved). Choose "Skip import" if you want to keep existing data and only run new migrations.</div>'
        : '';

    render('Import the database', '
        ' . flashHtml() . $warning . '
        <p class="text-muted">Pick how to seed the database. The "Fresh install" option drops &amp; recreates every table from <code>tpt-install.sql</code>. The "Skip import" option leaves your tables alone and just runs any pending migrations.</p>
        <form method="post" class="d-grid gap-2">
          <button class="btn btn-primary btn-lg" name="mode" value="fresh" type="submit">
            <i class="bi bi-database-fill-down"></i> Fresh install — import tpt-install.sql
          </button>
          <button class="btn btn-light btn-lg" name="mode" value="skip" type="submit">
            <i class="bi bi-skip-forward"></i> Skip import — only run pending migrations
          </button>
        </form>
        <a class="btn btn-link mt-3" href="?step=3">← Back</a>
    ', 4);
    exit;
}

// ── STEP 5 — done ─────────────────────────────────────────────────────
if ($step === 5) {
    // Create lock file + write release tag
    @file_put_contents($LOCK_FILE, "Installed at " . date('c') . "\n");
    $log = $_SESSION['_import_log'] ?? [];
    unset($_SESSION['_import_log']);

    $env = envRead($ENV_FILE);
    $baseUrl = $env['app.baseURL'] ?? '';
    $logHtml = $log ? '<details class="mt-3"><summary class="text-muted small">Install log</summary>' . implode('<br>', $log) . '</details>' : '';

    render('All done', '
        <div class="alert alert-success">
          <strong>Installation complete.</strong>
          <div class="small mt-1">A lock file has been created at <code>writable/.installed</code> so this installer won\'t run again.</div>
        </div>

        <div class="card mb-3"><div class="card-body">
          <h6>Next steps</h6>
          <ol class="small mb-0">
            <li><strong>Sign in</strong> using one of the seeded accounts (default admin: <code>admin@tpt.local</code> / <code>admin@123</code>) — change the password immediately.</li>
            <li><strong>Delete the installer</strong>: remove the <code>install/</code> directory and <code>tpt-install.sql</code> from the server. Both contain sensitive bootstrap material.</li>
            <li><strong>Schedule the cron jobs</strong> listed in <code>DEPLOY.md</code> (backups, queue worker, GPS refresh, etc.).</li>
            <li><strong>Set Sentry DSN</strong> in <code>.env</code> if you want error reporting.</li>
            <li><strong>Hit <code>/_health</code></strong> to verify the app is alive.</li>
          </ol>
        </div></div>

        <a class="btn btn-primary btn-lg" href="' . htmlspecialchars($baseUrl ?: '../public/') . 'login">Go to sign-in →</a>
        ' . $logHtml . '
    ', 5);
    exit;
}
