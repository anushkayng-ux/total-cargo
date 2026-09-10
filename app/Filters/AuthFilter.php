<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Auth;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = new Auth();
        if (!$auth->check()) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['error' => 'Unauthenticated']);
            }
            // Stash the intended URL so AuthController can redirect back after a
            // successful login. Only GETs — POST/PUT/DELETE are skipped because
            // re-firing the original request after login would re-submit a form
            // the user may not even remember.
            if (strtoupper($request->getMethod()) === 'GET') {
                $uri = (string) $request->getUri();
                // Don't stash the login page itself or auth-flow URLs
                if ($uri !== '' && !preg_match('#/(login|logout|login/2fa)(\?|$)#i', $uri)) {
                    session()->set('intended_url', $uri);
                }
            }
            return redirect()->to(site_url('login'))->with('error', 'Please log in to continue.');
        }

        if (!empty($arguments)) {
            $module = $arguments[0];
            $action = $arguments[1] ?? 'can_view';
            if (!$auth->can($module, $action)) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON(['error' => 'Forbidden']);
                }
                // Loop guard: never redirect to /dashboard if THAT is the page
                // being denied — that creates an ERR_TOO_MANY_REDIRECTS loop.
                // Instead, find the first module the user CAN access, or log
                // them out if they truly have nothing.
                if ($module === 'dashboard') {
                    $perms = (array) (session()->get('auth_perms') ?? []);
                    $fallback = null;
                    foreach ($perms as $modKey => $flags) {
                        if (!empty($flags['can_view'])) { $fallback = $modKey; break; }
                    }
                    if ($fallback !== null && $fallback !== 'dashboard') {
                        return redirect()->to(site_url($fallback))
                            ->with('error', 'You do not have access to the Dashboard. Showing ' . $fallback . ' instead.');
                    }
                    // No accessible modules at all — log out cleanly
                    $auth->logout();
                    return redirect()->to(site_url('login'))
                        ->with('error', 'Your account has no module access. Contact admin.');
                }
                return redirect()->to(site_url('dashboard'))->with('error', 'You do not have access to that section.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
