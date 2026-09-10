<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Auth;

/**
 * Global gate. When `maintenance_mode` setting is "1", every request returns 503
 * EXCEPT super-admin URLs and webhooks/email-track (so cron/ESPs keep working).
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $on = (string) ((new \App\Models\SettingModel())->get('maintenance_mode', '0') ?? '0');
        if ($on !== '1') return;

        $path = trim((string) $request->getUri()->getPath(), '/');
        $base = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
        if ($base && strpos($path, $base) === 0) {
            $path = trim(substr($path, strlen($base)), '/');
        }

        // Always-allowed paths
        $allow = ['login', 'logout', 'login/2fa'];
        $allowPrefix = ['sys', 'webhooks/', 'email-track/', 'd/', 'epod/'];

        if (in_array($path, $allow, true)) return;
        foreach ($allowPrefix as $p) {
            if ($p === $path || str_starts_with($path, $p)) return;
        }

        // Allow super-admins through everything
        $auth = new Auth();
        if ($auth->check() && $auth->isSuperAdmin()) return;

        // Otherwise show maintenance page
        return service('response')
            ->setStatusCode(503)
            ->setHeader('Retry-After', '300')
            ->setBody(view('sys/maintenance_off', [
                'message' => (string) ((new \App\Models\SettingModel())->get('maintenance_message', 'The system is undergoing maintenance. Please check back shortly.') ?? ''),
            ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
