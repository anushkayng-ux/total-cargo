<?php

namespace App\Controllers;

use App\Models\SuperAdminAuditModel;
use App\Models\FeatureFlagModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use App\Libraries\Totp;

/**
 * Super-admin "Sys" console. All routes are gated by `superadmin` filter.
 * Tenant admins cannot see or invoke any of these — the filter raises 404.
 */
class SysController extends BaseController
{
    private SuperAdminAuditModel $audit;

    protected $helpers = ['form', 'url', 'number'];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->audit = new SuperAdminAuditModel();
    }

    protected function render(string $view, array $data = [], string $layout = 'sys/layouts/app'): string
    {
        $data['auth']        = $this->auth;
        $data['currentUser'] = $this->auth->user();
        $data['viewFile']    = $view;
        return view($layout, $data);
    }

    private function log(string $action, ?string $description = null, array $details = [], ?string $targetType = null, ?int $targetId = null): void
    {
        $this->audit->record((int) $this->auth->id(), $action, $description, $details, $targetType, $targetId);
    }

    // ── Dashboard / diagnostics ───────────────────────────────────────────

    public function dashboard()
    {
        $db = \Config\Database::connect();
        $diag = [
            'php_version'   => PHP_VERSION,
            'ci_version'    => \CodeIgniter\CodeIgniter::CI_VERSION,
            'env'           => env('CI_ENVIRONMENT', 'production'),
            'server_time'   => date('Y-m-d H:i:s'),
            'db_version'    => (string) ($db->query('SELECT VERSION() AS v')->getRow('v') ?? '?'),
            'db_name'       => $db->getDatabase(),
            'writable_ok'   => is_writable(WRITEPATH),
            'cache_ok'      => is_writable(WRITEPATH . 'cache'),
            'uploads_ok'    => is_writable(WRITEPATH . 'uploads') || @mkdir(WRITEPATH . 'uploads', 0775, true),
            'public_ok'     => is_writable(FCPATH),
            'free_disk_gb'  => round(@disk_free_space(FCPATH) / 1024 / 1024 / 1024, 1),
            'memory_limit'  => ini_get('memory_limit'),
            'max_upload'    => ini_get('upload_max_filesize'),
            'max_post'      => ini_get('post_max_size'),
            'mail_driver'   => (string) ((new SettingModel())->get('email_driver', 'smtp') ?? ''),
        ];

        $tableSizes = [];
        foreach (['leads','clients','vendors','bookings','trips','invoices','receipts','vendor_bills','email_logs','email_events','whatsapp_logs','activity_logs'] as $t) {
            try {
                $tableSizes[$t] = (int) ($db->table($t)->countAllResults() ?? 0);
            } catch (\Throwable $e) { $tableSizes[$t] = -1; }
        }

        $emailQueue = $db->query("SELECT status, COUNT(*) AS c FROM email_logs GROUP BY status")->getResultArray();
        $whatsappQueue = $db->query("SELECT delivery_status, COUNT(*) AS c FROM whatsapp_logs GROUP BY delivery_status")->getResultArray();

        $maintMode = (string) ((new SettingModel())->get('maintenance_mode', '0') ?? '0') === '1';
        $ipAllowlist = (string) ((new SettingModel())->get('super_admin_ip_allowlist', '') ?? '');

        return $this->render('sys/dashboard', [
            'pageTitle'      => 'Diagnostics',
            'diag'           => $diag,
            'tableSizes'     => $tableSizes,
            'emailQueue'     => $emailQueue,
            'whatsappQueue'  => $whatsappQueue,
            'maintMode'      => $maintMode,
            'ipAllowlist'    => $ipAllowlist,
        ]);
    }

    // ── Step-up auth ──────────────────────────────────────────────────────

    public function stepUpForm()
    {
        return $this->render('sys/step_up', [
            'pageTitle' => 'Re-confirm password',
            'next'      => (string) $this->request->getGet('next'),
        ]);
    }

    public function stepUpSubmit()
    {
        $pw = (string) $this->request->getPost('password');
        $user = (new UserModel())->find($this->auth->id());
        if (!$user || !password_verify($pw, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password incorrect.');
        }
        $this->auth->markStepUp(600);
        $this->log('step_up_passed');
        $next = (string) $this->request->getPost('next');
        if ($next !== '' && preg_match('#^[a-z0-9/_-]+$#i', $next)) {
            return redirect()->to(site_url($next));
        }
        return redirect()->to(site_url('sys'));
    }

    // ── Feature flags ─────────────────────────────────────────────────────

    public function featureFlags()
    {
        return $this->render('sys/feature_flags', [
            'pageTitle' => 'Feature Flags',
            'flags'     => (new FeatureFlagModel())->all(),
        ]);
    }

    public function toggleFlag(int $id)
    {
        $m = new FeatureFlagModel();
        $f = $m->find($id);
        if (!$f) return redirect()->back()->with('error', 'Flag not found.');
        $newVal = (int) $f['enabled'] === 1 ? 0 : 1;
        $m->update($id, ['enabled' => $newVal, 'updated_by' => $this->auth->id(), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->log('feature_flag_toggle', $f['flag_key'] . ' → ' . ($newVal ? 'on' : 'off'), ['flag' => $f['flag_key'], 'enabled' => $newVal]);
        return redirect()->back()->with('success', 'Flag updated.');
    }

    // ── Maintenance ───────────────────────────────────────────────────────

    public function toggleMaintenance()
    {
        $s = new SettingModel();
        $cur = (string) ($s->get('maintenance_mode', '0') ?? '0');
        $next = $cur === '1' ? '0' : '1';
        $s->put('maintenance_mode', $next, 'system', $this->auth->id());
        $msg = trim((string) $this->request->getPost('maintenance_message'));
        if ($msg !== '') $s->put('maintenance_message', $msg, 'system', $this->auth->id());
        $this->log('maintenance_toggle', 'mode=' . $next);
        return redirect()->back()->with('success', $next === '1' ? 'Maintenance mode ENABLED.' : 'Maintenance mode disabled.');
    }

    public function saveIpAllowlist()
    {
        (new SettingModel())->put('super_admin_ip_allowlist', trim((string) $this->request->getPost('list')), 'system', $this->auth->id());
        $this->log('ip_allowlist_update');
        return redirect()->back()->with('success', 'IP allowlist updated.');
    }

    // ── Cache + sessions ──────────────────────────────────────────────────

    public function clearCache()
    {
        service('cache')->clean();
        $this->log('cache_clear');
        return redirect()->back()->with('success', 'Cache cleared.');
    }

    public function purgeSessions()
    {
        // Remove all session files (FileHandler default)
        $dir = WRITEPATH . 'session';
        if (is_dir($dir)) {
            foreach (glob($dir . '/ci_session*') ?: [] as $f) @unlink($f);
        }
        $this->log('sessions_purge');
        return redirect()->back()->with('success', 'All user sessions purged. Everyone will need to sign in again.');
    }

    // ── SQL console (READ-ONLY) ───────────────────────────────────────────

    public function sqlForm()
    {
        return $this->render('sys/sql', [
            'pageTitle' => 'SQL Console (read-only)',
            'sql'       => '',
            'rows'      => null,
            'columns'   => [],
            'error'     => null,
            'rowCount'  => 0,
        ]);
    }

    public function sqlRun()
    {
        $sql = trim((string) $this->request->getPost('sql'));
        $error = null; $rows = []; $columns = []; $rowCount = 0;

        if ($sql !== '') {
            $danger = '/\b(insert|update|delete|drop|truncate|alter|create|rename|grant|revoke|replace|load_file|into\s+outfile)\b/i';
            $first  = strtoupper(strtok($sql, " \t\n("));
            if (!in_array($first, ['SELECT','SHOW','DESCRIBE','DESC','EXPLAIN'], true) || preg_match($danger, $sql)) {
                $error = 'Only SELECT / SHOW / DESCRIBE / EXPLAIN are allowed in this console.';
            } else {
                try {
                    $db = \Config\Database::connect();
                    if (stripos($sql, 'limit ') === false) $sql .= ' LIMIT 1000';
                    $q = $db->query($sql);
                    $rows = $q ? $q->getResultArray() : [];
                    $rowCount = count($rows);
                    if ($rowCount > 0) $columns = array_keys($rows[0]);
                    $this->log('sql_run', substr($sql, 0, 400), ['rows' => $rowCount]);
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
        return $this->render('sys/sql', [
            'pageTitle' => 'SQL Console (read-only)',
            'sql'       => $sql,
            'rows'      => $rows,
            'columns'   => $columns,
            'error'     => $error,
            'rowCount'  => $rowCount,
        ]);
    }

    // ── Email queue ───────────────────────────────────────────────────────

    public function emailQueue()
    {
        $logs = (new \App\Models\EmailLogModel())
            ->whereIn('status', ['Queued','Sending','Failed'])
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->find();
        return $this->render('sys/email_queue', [
            'pageTitle' => 'Email Queue',
            'logs'      => $logs,
        ]);
    }

    public function emailFlush()
    {
        $r = (new \App\Libraries\EmailService())->flushQueue(200);
        $this->log('email_flush', null, $r);
        return redirect()->back()->with('success', sprintf('Flush done: scanned %d, sent %d, failed %d, reaped %d.', $r['scanned'] ?? 0, $r['sent'] ?? 0, $r['failed'] ?? 0, $r['reaped'] ?? 0));
    }

    public function emailRetry(int $id)
    {
        $r = (new \App\Libraries\EmailService())->dispatch($id);
        $this->log('email_retry', null, ['log_id' => $id, 'result' => $r], 'email_log', $id);
        return redirect()->back()->with(!empty($r['ok']) ? 'success' : 'error', !empty($r['ok']) ? 'Retried.' : ('Retry failed: ' . ($r['error'] ?? 'unknown')));
    }

    // ── Migrations ────────────────────────────────────────────────────────

    public function migrations()
    {
        $db = \Config\Database::connect();
        $applied = [];
        try {
            $applied = $db->table('migrations')->orderBy('id', 'DESC')->get()->getResultArray();
        } catch (\Throwable $e) {}
        // Compare with files on disk
        $dir = APPPATH . 'Database/Migrations';
        $files = is_dir($dir) ? array_values(array_filter(scandir($dir) ?: [], fn($f) => preg_match('/\.php$/', $f))) : [];
        sort($files);
        return $this->render('sys/migrations', [
            'pageTitle' => 'Migrations',
            'applied'   => $applied,
            'files'     => $files,
        ]);
    }

    public function migrationsRun()
    {
        try {
            $runner = service('migrations');
            $runner->latest();
            $this->log('migrations_run');
            return redirect()->back()->with('success', 'Migrations run.');
        } catch (\Throwable $e) {
            $this->log('migrations_run_failed', $e->getMessage());
            return redirect()->back()->with('error', 'Migrate failed: ' . $e->getMessage());
        }
    }

    // ── Audit log ─────────────────────────────────────────────────────────

    public function auditLog()
    {
        $rows = $this->audit->select('super_admin_audit.*, u.name AS author')
            ->join('users u', 'u.id = super_admin_audit.user_id', 'left')
            ->orderBy('super_admin_audit.id', 'DESC')
            ->paginate(50);
        return $this->render('sys/audit', [
            'pageTitle' => 'Super-Admin Audit',
            'rows'      => $rows,
            'pager'     => $this->audit->pager,
        ]);
    }

    // ── Profile + 2FA ─────────────────────────────────────────────────────

    public function profile()
    {
        $user = (new UserModel())->find($this->auth->id());
        $issuer = (string) ((new SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics');
        $newSecret  = $this->session->get('totp_setup_secret');
        $provisioning = $newSecret ? Totp::provisioningUri($newSecret, $user['email'], $issuer) : null;
        return $this->render('sys/profile', [
            'pageTitle'    => 'My Profile',
            'user'         => $user,
            'newSecret'    => $newSecret,
            'provisioning' => $provisioning,
        ]);
    }

    public function totpStart()
    {
        $secret = Totp::generateSecret();
        $this->session->set('totp_setup_secret', $secret);
        return redirect()->to(site_url('sys/profile'))->with('success', 'Scan the QR or enter the secret in your authenticator, then enter the 6-digit code below to confirm.');
    }

    public function totpEnable()
    {
        $secret = (string) $this->session->get('totp_setup_secret');
        $code = (string) $this->request->getPost('code');
        if ($secret === '') return redirect()->to(site_url('sys/profile'))->with('error', 'Start 2FA setup first.');
        if (!Totp::verify($secret, $code)) {
            return redirect()->to(site_url('sys/profile'))->with('error', 'That code did not verify. Please try again.');
        }
        (new UserModel())->update($this->auth->id(), [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ]);
        $this->session->remove('totp_setup_secret');
        $this->log('totp_enabled');
        return redirect()->to(site_url('sys/profile'))->with('success', '2FA enabled. You will be asked for a code on next sign-in.');
    }

    public function totpDisable()
    {
        $code = (string) $this->request->getPost('code');
        $user = (new UserModel())->find($this->auth->id());
        if (empty($user['totp_enabled'])) {
            return redirect()->to(site_url('sys/profile'))->with('error', '2FA is not enabled.');
        }
        if (!Totp::verify((string) $user['totp_secret'], $code)) {
            return redirect()->to(site_url('sys/profile'))->with('error', 'Code did not verify.');
        }
        (new UserModel())->update($this->auth->id(), [
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ]);
        $this->log('totp_disabled');
        return redirect()->to(site_url('sys/profile'))->with('success', '2FA disabled.');
    }

    // ── Impersonation ─────────────────────────────────────────────────────

    public function userPicker()
    {
        $rows = (new UserModel())
            ->select('users.id, users.name, users.email, users.is_super_admin, roles.role_name')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.deleted_at', null)
            ->where('users.status', 1)
            ->where('users.is_super_admin', 0)
            ->orderBy('users.name', 'ASC')
            ->find();
        return $this->render('sys/impersonate', [
            'pageTitle' => 'Impersonate Tenant User',
            'rows'      => $rows,
        ]);
    }

    public function impersonate(int $userId)
    {
        if ($this->auth->startImpersonation($userId)) {
            $this->log('impersonate_start', null, ['target' => $userId], 'user', $userId);
            return redirect()->to(site_url('dashboard'))
                ->with('success', 'Now impersonating user. Use the banner in the top bar to return to super-admin mode.');
        }
        return redirect()->back()->with('error', 'Cannot impersonate that user.');
    }

    public function stopImpersonate()
    {
        $impId = (int) $this->session->get('auth_impersonator_id');
        if ($this->auth->stopImpersonation()) {
            (new SuperAdminAuditModel())->record($impId, 'impersonate_stop');
            return redirect()->to(site_url('sys'))->with('success', 'Returned to super-admin mode.');
        }
        return redirect()->to(site_url('dashboard'));
    }

    // ── Backup (SQL dump) ─────────────────────────────────────────────────

    public function backup()
    {
        $cfg = config('Database')->default;
        $bin = (string) ((new SettingModel())->get('mysqldump_path', '') ?? '');
        $candidates = array_filter([$bin, 'mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe']);

        $tmp = tempnam(sys_get_temp_dir(), 'tpt_dump_');
        $err = null; $ok = false; $used = '';
        foreach ($candidates as $cand) {
            $cmd = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --routines --default-character-set=utf8mb4 %s 2>&1',
                escapeshellcmd($cand),
                escapeshellarg($cfg['hostname'] ?? 'localhost'),
                escapeshellarg((string) ($cfg['port'] ?? 3306)),
                escapeshellarg($cfg['username'] ?? 'root'),
                escapeshellarg($cfg['password'] ?? ''),
                escapeshellarg($cfg['database'])
            );
            $out = @shell_exec($cmd);
            if ($out !== null && stripos($out, 'CREATE TABLE') !== false) {
                file_put_contents($tmp, $out);
                $ok = true; $used = $cand; break;
            } else {
                $err = is_string($out) ? substr($out, 0, 400) : 'no output';
            }
        }
        if (!$ok) {
            @unlink($tmp);
            $this->log('backup_failed', $err);
            return redirect()->back()->with('error', 'Backup failed: ' . ($err ?? 'mysqldump not found'));
        }

        $this->log('backup_download', 'used=' . $used);
        $name = $cfg['database'] . '-' . date('Ymd-His') . '.sql';
        $body = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $this->response
            ->setHeader('Content-Type', 'application/sql')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($body);
    }
}
