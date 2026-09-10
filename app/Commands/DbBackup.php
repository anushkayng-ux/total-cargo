<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Dumps the configured MySQL database to writable/backups/<date>.sql.gz
 * and rotates older files. Schedule via cron / Task Scheduler:
 *
 *   0 2 * * *  C:\xampp\php\php.exe C:\xampp\htdocs\tpt\spark tpt:db-backup
 *   0 2 * * *  /usr/bin/php /var/www/tpt/spark tpt:db-backup
 *
 * Options (positional):
 *   tpt:db-backup [--keep=14]   how many of the most recent backups to keep
 */
class DbBackup extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:db-backup';
    protected $description = 'Dump the configured MySQL DB to writable/backups/, gzip + rotate.';

    public function run(array $params)
    {
        $keep = 14;
        foreach ($params as $p) {
            if (preg_match('/^--keep=(\d+)$/', $p, $m)) $keep = max(1, (int) $m[1]);
        }

        $cfg = config('Database')->default;
        $dir = WRITEPATH . 'backups';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $stamp    = date('Y-m-d_His');
        $sqlFile  = $dir . DIRECTORY_SEPARATOR . "tpt_db_{$stamp}.sql";
        $gzFile   = $sqlFile . '.gz';

        $mysqldump = $this->locateMysqldump();
        if (!$mysqldump) {
            CLI::error('Could not find mysqldump in PATH. Set MYSQLDUMP=/path/to/mysqldump in env.');
            return;
        }

        // Build the command without exposing the password to the process list.
        // Use a temporary defaults file so credentials never appear in `ps`.
        $defaults = tempnam(sys_get_temp_dir(), 'mydump');
        file_put_contents($defaults, "[client]\nuser={$cfg['username']}\npassword={$cfg['password']}\nhost={$cfg['hostname']}\nport={$cfg['port']}\n");
        @chmod($defaults, 0600);

        $args = [
            '--defaults-extra-file=' . escapeshellarg($defaults),
            '--add-drop-table',
            '--routines', '--triggers',
            '--default-character-set=utf8mb4',
            '--no-tablespaces',
            '--single-transaction',
            escapeshellarg($cfg['database']),
            '>', escapeshellarg($sqlFile),
        ];
        $cmd = escapeshellcmd($mysqldump) . ' ' . implode(' ', $args) . ' 2>&1';

        $start = microtime(true);
        exec($cmd, $out, $code);
        @unlink($defaults);
        if ($code !== 0 || !is_file($sqlFile)) {
            CLI::error('mysqldump failed: ' . implode("\n", $out));
            return;
        }
        $sizeBefore = filesize($sqlFile);

        // gzip — uses PHP's built-in (no shell gzip dependency)
        $gzh = gzopen($gzFile, 'wb9');
        $fh  = fopen($sqlFile, 'rb');
        while (!feof($fh)) gzwrite($gzh, fread($fh, 65536));
        fclose($fh);
        gzclose($gzh);
        @unlink($sqlFile);

        $sizeAfter = filesize($gzFile);
        $elapsed   = round(microtime(true) - $start, 2);

        CLI::write(sprintf(
            'Backup → %s (%.2f MB raw → %.2f MB gz · %ss)',
            basename($gzFile),
            $sizeBefore / 1048576, $sizeAfter / 1048576, $elapsed
        ), 'green');

        // Rotate: keep only the $keep most-recent .sql.gz files
        $files = glob($dir . DIRECTORY_SEPARATOR . 'tpt_db_*.sql.gz') ?: [];
        rsort($files);
        $delete = array_slice($files, $keep);
        foreach ($delete as $f) { @unlink($f); CLI::write('  pruned: ' . basename($f), 'yellow'); }

        CLI::write('Done — ' . (count($files) - count($delete)) . ' backup(s) on disk.');
    }

    private function locateMysqldump(): ?string
    {
        $env = getenv('MYSQLDUMP');
        if ($env && is_file($env)) return $env;
        // Common locations
        $candidates = [
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/xampp/mysql/bin/mariadb-dump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ];
        foreach ($candidates as $c) if (is_file($c)) return $c;
        // Fall back to PATH lookup
        $exe = (stripos(PHP_OS, 'WIN') === 0) ? 'where mysqldump' : 'which mysqldump';
        $r = @shell_exec($exe);
        $line = $r ? trim(strtok((string) $r, "\n")) : '';
        return ($line && is_file($line)) ? $line : null;
    }
}
