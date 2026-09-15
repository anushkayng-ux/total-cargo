<?php

namespace App\Controllers;

use App\Libraries\Analytics;
use App\Libraries\ComplianceTracker;

class DashboardController extends BaseController
{
    /** Cache hot dashboard reads for this many seconds (per-role key). */
    private const CACHE_TTL = 60;

    public function index()
    {
        // Widget visibility prefs (set via /dashboard/widgets) — empty means
        // show everything. Pre-existing users get the default until they
        // customise.
        $widgets = $this->loadWidgetPrefs();

        // Cache the expensive aggregate queries — dashboardCounts() alone hits
        // 8+ tables; we don't need second-fresh accuracy here. Key by role so
        // RBAC-scoped data (if any) isn't cross-leaked, and bump version on
        // schema changes to invalidate cleanly.
        $cache    = service('cache');
        $roleId   = (int) ($this->auth->user()['role_id'] ?? 0);
        // CI4's cache forbids `{}()/\@:` in keys — use underscores instead.
        $cacheKey = 'tpt_dashboard_v1_role_' . $roleId;

        $payload = $cache->get($cacheKey);
        if (!is_array($payload)) {
            $payload = [
                'counts'     => Analytics::dashboardCounts(),
                'monthly'    => Analytics::monthlyRevenue(6),
                'topClients' => Analytics::topClients(5),
                'topVendors' => Analytics::topVendors(5),
                'funnel'     => Analytics::leadFunnel(),
            ];
            $cache->save($cacheKey, $payload, self::CACHE_TTL);
        }
        // Compliance is small + user-relevant — keep fresh
        $payload['compliance'] = (new ComplianceTracker())->summary(60);
        $payload['widgetsOn']  = $widgets;
        $payload['pageTitle']  = 'General Masters [Home]';

        return $this->render('dashboard/index', $payload, retroFixedShell: true);
    }

    public function widgets()
    {
        return $this->render('dashboard/widgets', [
            'pageTitle' => 'Dashboard widgets',
            'widgetsOn' => $this->loadWidgetPrefs(),
            'catalog'   => self::WIDGET_CATALOG,
        ]);
    }

    public function saveWidgets()
    {
        $allowed = array_keys(self::WIDGET_CATALOG);
        $picked  = array_intersect($allowed, (array) $this->request->getPost('widgets'));
        $db = \Config\Database::connect();
        $db->table('user_prefs')->replace([
            'user_id' => $this->auth->id(),
            'pref_key' => 'dashboard_widgets',
            'pref_value' => json_encode(array_values($picked)),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to(site_url('dashboard'))->with('success', 'Widgets updated.');
    }

    /** Widgets shown by default for a user who has never opened Customise widgets. */
    private const DEFAULT_WIDGETS = ['kpis', 'compliance'];

    /** Loads widgetID => bool map. Empty/missing => the compact default set. */
    private function loadWidgetPrefs(): array
    {
        $row = \Config\Database::connect()->table('user_prefs')
            ->where(['user_id' => $this->auth->id(), 'pref_key' => 'dashboard_widgets'])
            ->get()->getRowArray();
        $picked = $row ? json_decode((string) $row['pref_value'], true) : null;
        $all    = array_keys(self::WIDGET_CATALOG);
        if (!is_array($picked)) {
            $on = array_fill_keys($all, false);
            foreach (self::DEFAULT_WIDGETS as $k) if (isset($on[$k])) $on[$k] = true;
            return $on;
        }
        $on = array_fill_keys($all, false);
        foreach ($picked as $k) if (isset($on[$k])) $on[$k] = true;
        return $on;
    }

    public const WIDGET_CATALOG = [
        'kpis'        => 'Top KPI cards (revenue, trips, receivables, unpaid invoices)',
        'compliance'  => 'Compliance — expiring documents (drivers + vehicles)',
        'revenue'     => 'Monthly revenue chart',
        'funnel'      => 'Lead funnel',
        'top_clients' => 'Top clients by revenue',
        'top_vendors' => 'Top vendors by spend',
    ];
}
