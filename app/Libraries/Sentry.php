<?php

namespace App\Libraries;

/**
 * Tiny Sentry forwarder — no SDK dependency, just curl.
 *
 * Wire by setting `sentry.dsn` in .env. Format:
 *   sentry.dsn = "https://<key>@<host>/<project_id>"
 *
 * Captures unhandled exceptions with stack trace, current URL, user id,
 * server hostname, and the project's release tag (.env tpt.release).
 * No-op if DSN is empty or curl is unavailable. We deliberately keep this
 * fire-and-forget: an error in Sentry shouldn't shadow the original error.
 *
 * To install: copy the DSN from sentry.io project settings → Client Keys
 * and add `sentry.dsn = "..."` to .env. Restart any long-running workers.
 */
class Sentry
{
    private static ?array $cfg = null;

    /** Capture a Throwable. Safe to call when DSN is unset (no-op). */
    public static function capture(\Throwable $e, array $extra = []): void
    {
        $cfg = self::config();
        if (!$cfg) return;

        $event = [
            'event_id'    => bin2hex(random_bytes(16)),
            'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
            'platform'    => 'php',
            'level'       => 'error',
            'logger'      => 'tpt',
            'release'     => (string) env('tpt.release', 'tpt@' . date('Ymd')),
            'environment' => (string) env('CI_ENVIRONMENT', 'production'),
            'server_name' => gethostname() ?: 'unknown',
            'message'     => $e->getMessage(),
            'exception'   => [
                'values' => self::chain($e),
            ],
            'request'     => self::request(),
            'user'        => self::user(),
            'extra'       => $extra,
            'tags'        => ['php_version' => PHP_VERSION],
        ];

        self::send($cfg, $event);
    }

    /** Capture a non-exception message at given level. */
    public static function message(string $msg, string $level = 'info', array $extra = []): void
    {
        $cfg = self::config();
        if (!$cfg) return;

        $event = [
            'event_id'    => bin2hex(random_bytes(16)),
            'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
            'platform'    => 'php',
            'level'       => $level,
            'logger'      => 'tpt',
            'release'     => (string) env('tpt.release', 'tpt@' . date('Ymd')),
            'environment' => (string) env('CI_ENVIRONMENT', 'production'),
            'server_name' => gethostname() ?: 'unknown',
            'message'     => $msg,
            'request'     => self::request(),
            'user'        => self::user(),
            'extra'       => $extra,
        ];

        self::send($cfg, $event);
    }

    private static function chain(\Throwable $e): array
    {
        $out = [];
        $t = $e;
        while ($t) {
            $out[] = [
                'type'       => get_class($t),
                'value'      => $t->getMessage(),
                'stacktrace' => ['frames' => self::frames($t)],
            ];
            $t = $t->getPrevious();
        }
        // Sentry expects oldest-first
        return array_reverse($out);
    }

    private static function frames(\Throwable $e): array
    {
        $frames = [];
        $trace  = $e->getTrace();
        // Add the throw site itself
        array_unshift($trace, ['file' => $e->getFile(), 'line' => $e->getLine(), 'function' => '<throw>']);
        foreach ($trace as $f) {
            $frames[] = [
                'filename' => $f['file'] ?? '<unknown>',
                'lineno'   => (int) ($f['line'] ?? 0),
                'function' => trim(($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '')),
                'in_app'   => !empty($f['file']) && !str_contains((string) $f['file'], 'vendor/'),
            ];
        }
        return array_reverse($frames);
    }

    private static function request(): ?array
    {
        try {
            $r = service('request');
            if (!$r) return null;
            return [
                'url'     => (string) $r->getUri(),
                'method'  => (string) $r->getMethod(),
                'headers' => ['User-Agent' => (string) $r->getUserAgent()],
            ];
        } catch (\Throwable $t) { return null; }
    }

    private static function user(): ?array
    {
        try {
            $auth = new Auth();
            if (!$auth->check()) return null;
            $u = $auth->user();
            return ['id' => (string) ($u['id'] ?? ''), 'email' => (string) ($u['email'] ?? ''), 'username' => (string) ($u['name'] ?? '')];
        } catch (\Throwable $t) { return null; }
    }

    /** Lazy-parse DSN. Cached for the rest of the request. */
    private static function config(): ?array
    {
        if (self::$cfg !== null) return self::$cfg ?: null;
        $dsn = (string) env('sentry.dsn', '');
        if (!$dsn || !function_exists('curl_init')) { self::$cfg = []; return null; }

        $p = parse_url($dsn);
        if (!$p || empty($p['user']) || empty($p['host']) || empty($p['path'])) { self::$cfg = []; return null; }

        $projectId = ltrim((string) $p['path'], '/');
        $endpoint  = ($p['scheme'] ?? 'https') . '://' . $p['host']
            . (empty($p['port']) ? '' : ':' . $p['port'])
            . '/api/' . $projectId . '/store/';

        self::$cfg = [
            'endpoint'   => $endpoint,
            'public_key' => $p['user'],
            'secret_key' => $p['pass'] ?? null,
        ];
        return self::$cfg;
    }

    private static function send(array $cfg, array $event): void
    {
        $auth = sprintf(
            'Sentry sentry_version=7,sentry_client=tpt-mini/1.0,sentry_timestamp=%d,sentry_key=%s%s',
            time(),
            $cfg['public_key'],
            $cfg['secret_key'] ? ',sentry_secret=' . $cfg['secret_key'] : ''
        );
        $ch = curl_init($cfg['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($event),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Sentry-Auth: ' . $auth,
                'User-Agent: tpt-mini/1.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 3,
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}
