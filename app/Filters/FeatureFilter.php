<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\FeatureFlagModel;

/**
 * Route-level gate for feature-flagged modules. Usage:
 *   ['filter' => 'feature:rate_contracts']
 *
 * When the flag is OFF the route silently 404s — the module looks like it
 * doesn't exist. Super-admins always see it (so they can debug / verify).
 */
class FeatureFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = $arguments[0] ?? '';
        if ($key === '') return;

        // Super-admins bypass — they need to see everything
        $auth = new \App\Libraries\Auth();
        if ($auth->isSuperAdmin()) return;

        if (!FeatureFlagModel::enabled($key)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
