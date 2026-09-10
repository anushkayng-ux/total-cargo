<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\ClientAuth;

class ClientAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = new ClientAuth();
        if (!$auth->check()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['error' => 'Unauthenticated']);
            }
            return redirect()->to(site_url('portal/login'))
                ->with('error', 'Please sign in to continue.');
        }

        // Live status recheck — if the user was disabled or their client's portal
        // was switched off mid-session, eject immediately. Cached lightly via static
        // to avoid double DB hit per request.
        static $statusChecked = false;
        if (!$statusChecked) {
            $statusChecked = true;
            $userId = $auth->id();
            $cid    = $auth->clientId();
            $row = \Config\Database::connect()->query(
                'SELECT cu.status AS user_status, c.portal_enabled, c.status AS client_status
                   FROM client_users cu LEFT JOIN clients c ON c.id = cu.client_id
                  WHERE cu.id = ? AND cu.deleted_at IS NULL LIMIT 1',
                [$userId]
            )->getRowArray();
            if (!$row
                || (int) ($row['user_status'] ?? 0) !== 1
                || (int) ($row['client_status'] ?? 0) !== 1
                || (int) ($row['portal_enabled'] ?? 0) !== 1
            ) {
                $auth->logout();
                return redirect()->to(site_url('portal/login'))
                    ->with('error', 'Your portal access has been disabled. Please contact your account manager.');
            }
        }

        // Force password change on first login (except the change-password page itself + logout)
        $path = trim((string) $request->getUri()->getPath(), '/');
        $base = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
        if ($base && strpos($path, $base) === 0) {
            $path = trim(substr($path, strlen($base)), '/');
        }

        $user = $auth->user();
        if (!empty($user['must_change'])
            && $path !== 'portal/profile/change-password'
            && $path !== 'portal/logout'
        ) {
            return redirect()->to(site_url('portal/profile/change-password'))
                ->with('error', 'Please set a new password before continuing.');
        }

        if (!empty($arguments)) {
            $required = $arguments[0];
            if (!$auth->can($required)) {
                return redirect()->to(site_url('portal'))
                    ->with('error', 'Your account role does not have access to that section.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
