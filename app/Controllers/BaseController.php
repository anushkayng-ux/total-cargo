<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Auth;

abstract class BaseController extends Controller
{
    protected $session;
    protected Auth $auth;
    protected $helpers = ['form', 'url', 'menu', 'number', 'branding', 'datetime'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session = service('session');
        $this->auth    = new Auth();
    }

    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $data['auth']        = $this->auth;
        $data['currentUser'] = $this->auth->user();
        $data['viewFile']    = $view;
        return view($layout, $data);
    }

    protected function jsonOk($data = [], string $message = 'OK')
    {
        return $this->response->setJSON(['status' => 'ok', 'message' => $message, 'data' => $data]);
    }

    protected function jsonFail(string $message, int $code = 400, array $errors = [])
    {
        return $this->response->setStatusCode($code)->setJSON(['status' => 'error', 'message' => $message, 'errors' => $errors]);
    }

    /**
     * Resolve the page-size for a list view. Reads `?per=N` from the query,
     * clamped to [10, 500], falls back to $default. Lets users show many
     * more rows without paging when they need to see everything at once.
     */
    protected function perPage(int $default = 100): int
    {
        $req = (int) ($this->request->getGet('per') ?? 0);
        if ($req <= 0) return $default;
        return max(10, min(500, $req));
    }

    /**
     * Return the list of client IDs the current viewer is allowed to see, or
     * `null` when no scope should be applied (admin / feature off).
     *
     * Turned on globally via Settings: `clients.scope_by_owner` = '1'.
     * Admins (super-admin flag OR role_key='admin') always see everything.
     * Non-admins get only clients whose `account_manager_user_id` = their id.
     *
     * Returning `[]` (empty array) means "user has no clients" — controllers
     * should coerce that to a WHERE-in [0] so no rows come back.
     */
    protected function scopedClientIds(): ?array
    {
        try {
            $enabled = (int) ((new \App\Models\SettingModel())->get('clients.scope_by_owner', '0')) === 1;
        } catch (\Throwable $e) { $enabled = false; }
        if (!$enabled) return null;

        if ($this->auth->isSuperAdmin()) return null;

        $uid = (int) $this->auth->id();
        // Cache per-request — this can get called many times per page.
        static $cache = [];
        if (isset($cache[$uid])) return $cache[$uid];

        try {
            $db = \Config\Database::connect();

            // 1) Directly owned client rows.
            $owned = $db->table('clients')
                ->select('id, gst_no, company_name')
                ->where('account_manager_user_id', $uid)
                ->get()->getResultArray();
            if (empty($owned)) return $cache[$uid] = [];

            $ids       = [];
            $gstins    = [];
            $nameKeys  = [];
            $placeholderGst = ['', 'URP', '000000000', 'XXXXXXXXXXXXXXX', 'xxxxxxxxxxxxxxx'];
            foreach ($owned as $r) {
                $ids[] = (int) $r['id'];
                $g = trim((string) ($r['gst_no'] ?? ''));
                if ($g !== '' && !in_array($g, $placeholderGst, true)) $gstins[$g] = true;
                $n = strtoupper(trim((string) ($r['company_name'] ?? '')));
                if ($n !== '') $nameKeys[$n] = true;
            }

            // 2) Include TWIN client rows — same GSTIN or same company name
            // (case-insensitive). Handles the "same real-world client got
            // imported twice with different IDs" case, so historical bookings
            // linked to the twin id still show up for the right salesperson.
            if ($gstins || $nameKeys) {
                $twinQ = $db->table('clients')->select('id')->whereNotIn('id', $ids)->groupStart();
                $addedFirst = false;
                if ($gstins) {
                    $twinQ->whereIn('gst_no', array_keys($gstins));
                    $addedFirst = true;
                }
                if ($nameKeys) {
                    $method = $addedFirst ? 'orWhereIn' : 'whereIn';
                    // Compare uppercased trimmed names — needs a raw expression.
                    // Use RAW so CI doesn't backtick-escape the function call.
                    $inList = "'" . implode("','", array_map(
                        static fn ($k) => str_replace("'", "''", $k), array_keys($nameKeys)
                    )) . "'";
                    $twinQ->{$addedFirst ? 'orWhere' : 'where'}(
                        "UPPER(TRIM(company_name)) IN ($inList)", null, false
                    );
                }
                $twinQ->groupEnd();
                $twinRows = $twinQ->get()->getResultArray();
                foreach ($twinRows as $r) $ids[] = (int) $r['id'];
            }

            return $cache[$uid] = array_values(array_unique($ids));
        } catch (\Throwable $e) { return null; }
    }

}
