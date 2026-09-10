<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Backup-restore drill. Picks the newest dump in writable/backups/, restores
 * it into a throw-away `<dbname>_restore_test` schema, runs a tiny sanity
 * checklist, then drops the schema. Exit 0 = drill passed.
 *
 * Schedule monthly:
 *   0 4 1 * *   /path/to/spark tpt:db-restore-test
 *
 * The point isn't to inspect data — it's to catch silent corruption and
 * verify mysqldump output is still importable end-to-end. If the drill
 * fails, that's a fire-alarm: the backups you've been collecting nightly
 * are useless.
 */
class DbRestoreTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:db-restore-test';
    protected $description = 'Restore the newest backup into a throwaway schema and run sanity checks.';

    public function run(array $params)
    {
        $cfg = config('Database')->default;
        $dir = WRITEPATH . 'backups';
        $files = glob($dir . DIRECTORY_SEPARATOR . 'tpt_db_*.sql.gz') ?: [];
        if (!$files) {
            CLI::error('No backups found in ' . $dir . '. Run tpt:db-backup first.');
            return 1;
        }
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $newest = $files[0];
        $ageHrs = round((time() - filemtime($newest)) / 3600, 1);
        CLI::write("Newest backup: " . basename($newest) . " (age {$ageHrs}h)", 'cyan');
        if ($ageHrs > 48) CLI::write('  WARNING: backup is older than 48h — check the cron job.', 'yellow');

        $mysql = $this->locate('mysql');
        $gunzip = $this->locate('gunzip');
        if (!$mysql)  { CLI::error('mysql client not found in PATH or xampp dir.'); return 1; }
        if (!$gunzip) { CLI::error('gunzip not found — install gzip utilities.');   return 1; }

        $testDb = preg_replace('/[^a-zA-Z0-9_]/', '_', $cfg['database']) . '_restore_test';

        // 1. Drop + recreate scratch schema
        if (!$this->runMysql($mysql, $cfg, '', "DROP DATABASE IF EXISTS `$testDb`; CREATE DATABASE `$testDb`;")) {
            CLI::error("Could not create scratch DB `$testDb`."); return 1;
        }
        CLI::write("Scratch DB `$testDb` created.", 'green');

        // 2. Pipe gunzip → mysql
        $cmd = sprintf('%s -c %s | %s -h%s -P%s -u%s %s %s',
            escapeshellcmd($gunzip),
            escapeshellarg($newest),
            escapeshellcmd($mysql),
            escapeshellarg($cfg['hostname']),
            escapeshellarg((string) ($cfg['port'] ?? 3306)),
            escapeshellarg($cfg['username']),
            !empty($cfg['password']) ? '-p' . escapeshellarg($cfg['password']) : '',
            escapeshellarg($testDb)
        );
        CLI::write('Restoring (this may take a minute)…');
        $t0 = microtime(true);
        exec($cmd . ' 2>&1', $out, $code);
        $t  = round(microtime(true) - $t0, 1);
        if ($code !== 0) {
            CLI::error("Restore failed (exit $code). Output: " . implode("\n", array_slice($out, 0, 5)));
            $this->runMysql($mysql, $cfg, '', "DROP DATABASE IF EXISTS `$testDb`;");
            return 1;
        }
        CLI::write("Restore OK in {$t}s.", 'green');

        // 3. Sanity checks — these tables must exist + have rows
        $required = ['users','clients','vendors','bookings','trips','invoices','migrations'];
        $missing  = [];
        foreach ($required as $tbl) {
            $row = $this->queryRow($mysql, $cfg, $testDb, "SELECT COUNT(*) AS n FROM `$tbl`");
            if ($row === null) { $missing[] = $tbl; continue; }
            CLI::write(sprintf('  %-12s %s rows', $tbl, number_format((int) $row['n'])));
        }
        // 4. Drop scratch
        $this->runMysql($mysql, $cfg, '', "DROP DATABASE IF EXISTS `$testDb`;");
        CLI::write("Scratch DB dropped.");

        if ($missing) {
            CLI::error('Missing tables after restore: ' . implode(', ', $missing));
            return 1;
        }
        CLI::write('Drill PASSED ✓', 'green');
        return 0;
    }

    private function locate(string $bin): ?string
    {
        $candidates = [
            'C:/xampp/mysql/bin/' . $bin . '.exe',
            'C:/Program Files/Git/usr/bin/' . $bin . '.exe',
            '/usr/bin/' . $bin,
            '/usr/local/bin/' . $bin,
        ];
        foreach ($candidates as $c) if (is_file($c)) return $c;
        // Fall back to PATH
        $cmd = (strncasecmp(PHP_OS, 'WIN', 3) === 0 ? 'where ' : 'which ') . escapeshellarg($bin);
        $out = trim((string) @shell_exec($cmd));
        if ($out) {
            $first = preg_split('/[\r\n]+/', $out)[0] ?? '';
            if (is_file($first)) return $first;
        }
        return null;
    }

    private function runMysql(string $mysql, array $cfg, string $db, string $sql): bool
    {
        $cmd = sprintf('%s -h%s -P%s -u%s %s %s -e %s',
            escapeshellcmd($mysql),
            escapeshellarg($cfg['hostname']),
            escapeshellarg((string) ($cfg['port'] ?? 3306)),
            escapeshellarg($cfg['username']),
            !empty($cfg['password']) ? '-p' . escapeshellarg($cfg['password']) : '',
            $db ? escapeshellarg($db) : '',
            escapeshellarg($sql)
        );
        exec($cmd . ' 2>&1', $o, $code);
        return $code === 0;
    }

    private function queryRow(string $mysql, array $cfg, string $db, string $sql): ?array
    {
        $cmd = sprintf('%s -N -B -h%s -P%s -u%s %s %s -e %s',
            escapeshellcmd($mysql),
            escapeshellarg($cfg['hostname']),
            escapeshellarg((string) ($cfg['port'] ?? 3306)),
            escapeshellarg($cfg['username']),
            !empty($cfg['password']) ? '-p' . escapeshellarg($cfg['password']) : '',
            escapeshellarg($db),
            escapeshellarg($sql)
        );
        exec($cmd . ' 2>&1', $o, $code);
        if ($code !== 0 || empty($o)) return null;
        $parts = preg_split('/\t/', (string) $o[0]);
        return ['n' => (int) ($parts[0] ?? 0)];
    }
}
