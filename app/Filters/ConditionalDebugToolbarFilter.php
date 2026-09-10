<?php

namespace App\Filters;

use CodeIgniter\Filters\DebugToolbar as BaseDebugToolbar;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Auth;
use App\Models\SettingModel;

/**
 * Wraps CI4's built-in DebugToolbar so the toolbar only renders when:
 *   1. setting `debug_toolbar_enabled` = '1'
 *   2. visitor is signed in (no anonymous leakage)
 *   3. visitor is a super-admin OR has settings permission (admin / management)
 *
 * This is in addition to CI4's own gate (CI_ENVIRONMENT must allow debug). If
 * the env is "production", the parent filter still won't render the toolbar
 * regardless of this setting — that's a deliberate framework safety.
 */
class ConditionalDebugToolbarFilter extends BaseDebugToolbar
{
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $on = (string) ((new SettingModel())->get('debug_toolbar_enabled', '0') ?? '0');
        if ($on !== '1') return;

        $auth = new Auth();
        if (!$auth->check()) return;
        if (!$auth->isSuperAdmin() && !$auth->can('settings')) return;

        return parent::after($request, $response, $arguments);
    }
}
