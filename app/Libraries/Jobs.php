<?php

namespace App\Libraries;

/**
 * Job-queue facade. Enqueue from anywhere with `Jobs::push(...)`; a worker
 * (`spark tpt:queue-work`) consumes rows by oldest-pending-first.
 *
 *  Jobs::push(\App\Jobs\SendInvoiceEmail::class, ['invoice_id' => 42]);
 *  Jobs::push('App\Jobs\GenerateInvoicePdf', ['invoice_id' => 42], queue: 'pdf');
 *
 * A "handler" is any class with a public `handle(array $payload): void`
 * method. Throwing rolls the job back and increments attempts; succeeding
 * marks it done. Past `max_attempts`, the row is parked in `failed` for an
 * admin to inspect via the audit log / SQL.
 */
class Jobs
{
    public static function push(string $handler, array $payload = [], string $queue = 'default', int $delaySeconds = 0, int $maxAttempts = 3): int
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $db->table('jobs')->insert([
            'queue'        => $queue,
            'handler'      => $handler,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status'       => 'pending',
            'max_attempts' => max(1, $maxAttempts),
            'available_at' => date('Y-m-d H:i:s', time() + max(0, $delaySeconds)),
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        return (int) $db->insertID();
    }

    /**
     * Reserve and execute one pending job atomically. Returns one of:
     *   ['picked' => false]                       no work available
     *   ['picked' => true, 'id' => N, 'ok' => bool, 'error' => ?string]
     *
     * Used by the worker command — not typically called directly.
     */
    public static function reserveAndRun(string $queue = 'default'): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $now = date('Y-m-d H:i:s');

        $row = $db->table('jobs')
            ->where('queue', $queue)
            ->where('status', 'pending')
            ->groupStart()
                ->where('available_at IS NULL', null, false)
                ->orWhere('available_at <=', $now)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()->getRowArray();

        if (!$row) { $db->transComplete(); return ['picked' => false]; }

        $db->table('jobs')->where('id', (int) $row['id'])->update([
            'status'       => 'running',
            'reserved_at'  => $now,
            'attempts'     => (int) $row['attempts'] + 1,
            'updated_at'   => $now,
        ]);
        $db->transComplete();

        // Run outside the transaction so locks aren't held during slow IO
        $payload = json_decode((string) $row['payload_json'], true) ?: [];
        $error = null;
        $ok    = false;
        try {
            $handler = (string) $row['handler'];
            if (!class_exists($handler)) throw new \RuntimeException("Handler not found: $handler");
            $obj = new $handler();
            if (!method_exists($obj, 'handle')) throw new \RuntimeException("Handler has no handle(): $handler");
            $obj->handle($payload);
            $ok = true;
        } catch (\Throwable $e) {
            $error = substr($e->getMessage(), 0, 480);
            Sentry::capture($e, ['job_id' => (int) $row['id'], 'handler' => $row['handler']]);
        }

        $upd = ['updated_at' => date('Y-m-d H:i:s')];
        $attempts = (int) $row['attempts'] + 1;
        if ($ok) {
            $upd['status']       = 'done';
            $upd['completed_at'] = date('Y-m-d H:i:s');
            $upd['error_text']   = null;
        } elseif ($attempts >= (int) $row['max_attempts']) {
            $upd['status']     = 'failed';
            $upd['failed_at']  = date('Y-m-d H:i:s');
            $upd['error_text'] = $error;
        } else {
            // Schedule a retry with exponential backoff: 1m, 5m, 25m...
            $delay = (int) pow(5, $attempts) * 60;
            $upd['status']       = 'pending';
            $upd['available_at'] = date('Y-m-d H:i:s', time() + $delay);
            $upd['error_text']   = $error;
        }
        $db->table('jobs')->where('id', (int) $row['id'])->update($upd);

        return ['picked' => true, 'id' => (int) $row['id'], 'ok' => $ok, 'error' => $error];
    }
}
