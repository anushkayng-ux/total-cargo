<?php

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\ClientAuth;

abstract class BaseController extends Controller
{
    protected $session;
    protected ClientAuth $clientAuth;
    protected $helpers = ['form', 'url', 'number', 'branding', 'datetime'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session    = service('session');
        $this->clientAuth = new ClientAuth();
    }

    protected function render(string $view, array $data = [], string $layout = 'portal/layouts/app'): string
    {
        $data['clientAuth']  = $this->clientAuth;
        $data['currentUser'] = $this->clientAuth->user();
        $data['viewFile']    = $view;
        return view($layout, $data);
    }

    /**
     * Defense-in-depth ownership check. Every fetched record must be re-validated
     * against the session's client_id before rendering.
     */
    protected function requireOwn(?array $row, string $field = 'client_id'): array
    {
        if (!$row || (int) ($row[$field] ?? 0) !== (int) $this->clientAuth->clientId()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $row;
    }

    /**
     * Audit a portal-side action into activity_logs (actor_type=client).
     */
    protected function audit(string $module, ?int $refId, string $action, ?string $description = null, array $context = []): void
    {
        $db = \Config\Database::connect();
        $db->table('activity_logs')->insert([
            'module_name'        => $module,
            'module_ref_id'      => $refId,
            'action_type'        => $action,
            'action_description' => $description ? substr($description, 0, 400) : null,
            'old_value_json'     => null,
            'new_value_json'     => $context ? json_encode($context) : null,
            'user_id'            => $this->clientAuth->id(),
            'actor_type'         => 'client',
            'client_id'          => $this->clientAuth->clientId(),
            'ip_address'         => $this->request->getIPAddress(),
            'user_agent'         => substr((string) $this->request->getUserAgent()->getAgentString(), 0, 255),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
