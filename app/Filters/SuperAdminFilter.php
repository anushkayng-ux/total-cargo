<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Auth;

class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = new Auth();
        if (!$auth->check() || !$auth->isSuperAdmin()) {
            // Pretend the route doesn't exist to anyone who isn't a super admin.
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (!Auth::ipAllowed()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Step-up auth — actions tagged 'stepup' require a recent password re-confirm.
        if (!empty($arguments) && in_array('stepup', (array) $arguments, true)) {
            if (!$auth->hasStepUp()) {
                $next = urlencode(trim((string) $request->getUri()->getPath(), '/'));
                return redirect()->to(site_url('sys/step-up?next=' . $next))
                    ->with('error', 'Please re-enter your password to continue with this action.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
