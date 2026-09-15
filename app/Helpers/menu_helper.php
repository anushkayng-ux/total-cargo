<?php

if (!function_exists('tpt_quick_add_items')) {
    /**
     * The "New…" dropdown's options — every record type the current user
     * is allowed to create, regardless of which page they're on. Shared by
     * the banner's global New button and each page's own toolbar New
     * button (see tpt_toolbar()), so both list the same options instead of
     * drifting apart.
     */
    function tpt_quick_add_items(\App\Libraries\Auth $auth): array
    {
        $items = [];
        if ($auth->can('leads', 'can_add'))      $items[] = ['url' => site_url('leads/create'),    'icon' => 'person-plus',        'label' => 'Add Lead'];
        if ($auth->can('rfq', 'can_add'))        $items[] = ['url' => site_url('rfq/create'),       'icon' => 'file-earmark-plus',  'label' => 'Add RFQ'];
        if ($auth->can('bookings', 'can_add'))   $items[] = ['url' => site_url('bookings/create'),  'icon' => 'journal-plus',       'label' => 'Add Booking'];
        if ($auth->can('trips', 'can_edit'))     $items[] = ['url' => site_url('dockets/create'),   'icon' => 'file-earmark-ruled', 'label' => 'Create Docket (LR)'];
        if ($auth->can('clients', 'can_add'))    $items[] = ['url' => site_url('clients/create'),   'icon' => 'building-add',       'label' => 'Add Client'];
        if ($auth->can('vendors', 'can_add'))    $items[] = ['url' => site_url('vendors/create'),   'icon' => 'truck',              'label' => 'Add Vendor'];
        return $items;
    }
}

if (!function_exists('tpt_menu')) {
    /**
     * Sidebar menu — organised into 9 purpose-driven groups. No "More"
     * dumping-ground: every link belongs to a section whose name describes
     * what it's for.
     *   Home · Sales · Operations · Fleet · Billing · Purchase · Reports · Team · Admin
     */
    function tpt_menu(): array
    {
        return [
            ['label' => 'Home', 'icon' => 'house', 'url' => 'dashboard', 'module' => 'dashboard'],

            // ── Sales: pre-booking pipeline ──
            [
                'label' => 'Sales', 'icon' => 'people', 'module' => null, 'children' => [
                    ['label' => 'Leads',         'url' => 'leads',        'module' => 'leads'],
                    ['label' => 'Clients',       'url' => 'clients',      'module' => 'clients'],
                    ['label' => 'RFQs',          'url' => 'rfq',          'module' => 'rfq'],
                    ['label' => 'Quotations',    'url' => 'quotations',   'module' => 'quotations'],
                    ['label' => 'Calendar',      'url' => 'calendar',     'module' => 'bookings'],
                    ['label' => 'Lead Sources',  'url' => 'lead-sources', 'module' => 'lead_sources'],
                ],
            ],

            // ── Operations: everything after a booking exists ──
            [
                'label' => 'Operations', 'icon' => 'truck', 'module' => null, 'children' => [
                    ['label' => 'Bookings',         'url' => 'bookings',        'module' => 'bookings'],
                    ['label' => 'Trips',            'url' => 'trips',           'module' => 'trips'],
                    ['label' => 'Awaiting Docket',  'url' => 'dockets/pending', 'module' => 'trips'],
                    ['label' => 'Arrival',          'url' => 'arrival',         'module' => 'trips'],
                    ['label' => 'GPS',              'url' => 'gps',             'module' => 'gps'],
                ],
            ],

            // ── Fleet: physical asset management ──
            [
                'label' => 'Fleet', 'icon' => 'truck-front', 'module' => null, 'children' => [
                    ['label' => 'Drivers',    'url' => 'drivers',   'module' => 'drivers'],
                    ['label' => 'Vehicles',   'url' => 'vehicles',  'module' => 'vehicles'],
                    ['label' => 'Documents',  'url' => 'documents', 'module' => 'documents'],
                ],
            ],

            // ── Billing: customer money in ──
            [
                'label' => 'Billing', 'icon' => 'receipt', 'module' => null, 'children' => [
                    ['label' => 'Invoices',        'url' => 'invoices',        'module' => 'invoices'],
                    ['label' => 'Client Receipts', 'url' => 'receipts',        'module' => 'receipts'],
                    ['label' => 'Bank Import',     'url' => 'receipts/import', 'module' => 'receipts'],
                    ['label' => 'GST Returns',     'url' => 'gst-returns',     'module' => 'invoices'],
                ],
            ],

            // ── Purchase: vendor money out ──
            [
                'label' => 'Purchase', 'icon' => 'currency-rupee', 'module' => null, 'children' => array_values(array_filter([
                    ['label' => 'Vendors',                 'url' => 'vendors',            'module' => 'vendors'],
                    ['label' => 'Purchase Inbox',          'url' => 'rfq/queue',          'module' => 'rfq'],
                    ['label' => 'Vendor Bills',            'url' => 'vendor-bills',       'module' => 'vendor_bills'],
                    ['label' => 'Vendor Payments',         'url' => 'vendor-payments',    'module' => 'vendor_payments'],
                    ['label' => 'Driver Advance / Bhatta', 'url' => 'purchase/advances',  'module' => 'trips'],
                    ['label' => 'Cargo Insurance',         'url' => 'purchase/insurance', 'module' => 'trips'],
                    function_exists('tpt_feature_enabled') && tpt_feature_enabled('rate_contracts')
                        ? ['label' => 'Rate Contracts',    'url' => 'rate-contracts',    'module' => 'rate_contracts']   : null,
                    function_exists('tpt_feature_enabled') && tpt_feature_enabled('tds_certificates')
                        ? ['label' => 'TDS Certificates',  'url' => 'tds-certificates',  'module' => 'tds_certificates'] : null,
                    function_exists('tpt_feature_enabled') && tpt_feature_enabled('vendor_deposits')
                        ? ['label' => 'Vendor Deposits',   'url' => 'vendor-deposits',   'module' => 'vendor_deposits']  : null,
                ])),
            ],

            // ── Reports: every dashboard / drill-down / analytics page ──
            [
                'label' => 'Reports', 'icon' => 'graph-up', 'module' => null, 'children' => [
                    ['label' => 'All reports',        'url' => 'reports',                   'module' => null],
                    ['label' => 'Executive dashboard','url' => 'reports/executive',         'module' => 'report_sop_kpis'],
                    ['label' => 'Profitability',      'url' => 'reports/profitability',     'module' => 'report_profitability'],
                    ['label' => 'Receivables Aging',  'url' => 'reports/receivables',       'module' => 'report_receivables'],
                    ['label' => 'Payables Aging',     'url' => 'reports/payables',          'module' => 'report_payables'],
                    ['label' => 'Trip Expenses',      'url' => 'reports/trip-expenses',     'module' => 'report_trip_expenses'],
                    ['label' => 'Unbilled Billable',  'url' => 'reports/unbilled-billable', 'module' => 'report_unbilled_billable'],
                    ['label' => 'Lead Funnel',        'url' => 'reports/lead-funnel',       'module' => 'report_lead_funnel'],
                    ['label' => 'Email Analytics',    'url' => 'reports/email-analytics',   'module' => 'report_email_analytics'],
                    ['label' => 'Vendor Scorecard',   'url' => 'vendors/scoring',           'module' => 'report_vendor_scoring'],
                ],
            ],

            // ── Team: HR, meetings, payroll ──
            [
                'label' => 'Team', 'icon' => 'person-badge', 'module' => null, 'children' => [
                    ['label' => 'My Profile',       'url' => 'hrms/profile',        'module' => 'hrms'],
                    ['label' => 'My Attendance',    'url' => 'hrms/attendance',     'module' => 'hrms'],
                    ['label' => 'My Leaves',        'url' => 'hrms/leaves',         'module' => 'hrms'],
                    ['label' => 'Team roster',      'url' => 'hrms/team',           'module' => 'hrms'],
                    ['label' => 'Team Attendance',  'url' => 'hrms/team-attendance','module' => 'hrms'],
                    ['label' => 'Leave Approvals',  'url' => 'hrms/approvals',      'module' => 'hrms'],
                    ['label' => 'Holiday Calendar', 'url' => 'hrms/holidays',       'module' => 'hrms'],
                    ['label' => 'Meetings',         'url' => 'meetings',            'module' => 'meetings'],
                    ['label' => 'Payroll',          'url' => 'payroll',             'module' => 'payroll'],
                ],
            ],

            // ── Communication: WA + Email inboxes, logs, templates ──
            [
                'label' => 'Communication', 'icon' => 'chat-dots', 'module' => null, 'children' => [
                    ['label' => 'WhatsApp Inbox',     'url' => 'whatsapp/inbox',     'module' => 'whatsapp'],
                    ['label' => 'WhatsApp Logs',      'url' => 'whatsapp/logs',      'module' => 'whatsapp'],
                    ['label' => 'WhatsApp Templates', 'url' => 'whatsapp/templates', 'module' => 'whatsapp'],
                    ['label' => 'Email Logs',         'url' => 'email-logs',         'module' => 'email'],
                    ['label' => 'Email Templates',    'url' => 'email-templates',    'module' => 'email'],
                ],
            ],

            // ── Admin: system-wide setup, master data, user & role config ──
            [
                'label' => 'Admin', 'icon' => 'gear', 'module' => null, 'children' => [
                    ['label' => 'Users',       'url' => 'users',      'module' => 'users'],
                    ['label' => 'Roles',       'url' => 'roles',      'module' => 'roles'],
                    ['label' => 'Cities',      'url' => 'cities',     'module' => 'cities'],
                    ['label' => 'Settings',    'url' => 'settings',   'module' => 'settings'],
                    ['label' => 'Import Data', 'url' => 'import',     'module' => 'clients'],
                    ['label' => 'Audit Logs',  'url' => 'audit-logs', 'module' => 'audit_logs'],
                    ['label' => 'Support',     'url' => 'support',    'module' => null],
                    ['label' => 'Help',        'url' => 'help',       'module' => null],
                ],
            ],
        ];
    }
}

if (!function_exists('tpt_menu_visible')) {
    function tpt_menu_visible(\App\Libraries\Auth $auth): array
    {
        $menu = tpt_menu();
        $out  = [];
        foreach ($menu as $item) {
            if (array_key_exists('children', $item)) {
                $children = [];
                foreach (($item['children'] ?? []) as $c) {
                    if (empty($c['module']) || $auth->can($c['module'])) {
                        $children[] = $c;
                    }
                }
                if (!empty($children)) {
                    $item['children'] = $children;
                    $out[] = $item;
                }
            } else {
                if (empty($item['module']) || $auth->can($item['module'])) {
                    $out[] = $item;
                }
            }
        }
        return $out;
    }
}

if (!function_exists('tpt_hub_buckets')) {
    /**
     * Groups every visible menu link into the 4 retro-demo "hub" pages
     * (masters / transportation / accounts / administration) so the sidebar
     * can be collapsed to the demo's 7 items without losing any real route.
     * Anything not explicitly bucketed below falls into 'administration' —
     * a safety net so a future new menu item is never unreachable.
     */
    function tpt_hub_buckets(\App\Libraries\Auth $auth): array
    {
        $mastersUrls = ['clients', 'vehicles', 'drivers', 'vendors', 'cities'];
        $transportUrls = [
            'leads', 'rfq', 'rfq/queue', 'quotations', 'calendar', 'lead-sources',
            'bookings', 'trips', 'dockets/pending', 'arrival', 'gps', 'documents',
        ];
        $accountsUrls = [
            'invoices', 'receipts', 'receipts/import', 'gst-returns',
            'vendor-bills', 'vendor-payments', 'purchase/advances', 'purchase/insurance',
            'rate-contracts', 'tds-certificates', 'vendor-deposits',
        ];

        $buckets = ['masters' => [], 'transportation' => [], 'accounts' => [], 'administration' => []];

        foreach (tpt_menu_visible($auth) as $item) {
            $candidates = !empty($item['children']) ? $item['children'] : [$item];
            foreach ($candidates as $c) {
                $url = trim((string) ($c['url'] ?? ''), '/');
                if ($url === '' || $url === 'dashboard') {
                    continue;
                }
                $card = [
                    'label' => $c['label'],
                    'url'   => $c['url'],
                    'icon'  => $c['icon'] ?? $item['icon'] ?? 'folder',
                ];
                if (in_array($url, $mastersUrls, true)) {
                    $buckets['masters'][] = $card;
                } elseif (in_array($url, $transportUrls, true)) {
                    $buckets['transportation'][] = $card;
                } elseif (in_array($url, $accountsUrls, true)) {
                    $buckets['accounts'][] = $card;
                } else {
                    $buckets['administration'][] = $card;
                }
            }
        }

        return $buckets;
    }
}

if (!function_exists('tpt_active_hub')) {
    /**
     * Which of the 7 flat sidebar items (masters/transportation/accounts/
     * search/password/administration/exit) owns the current request —
     * used to highlight the right sidebar link now that the accordion
     * sub-menus are gone.
     */
    function tpt_active_hub(\App\Libraries\Auth $auth): string
    {
        $current = trim(service('request')->getUri()->getPath(), '/');
        $base    = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
        if ($base && strpos($current, $base) === 0) {
            $current = trim(substr($current, strlen($base)), '/');
        }
        // Dev server / no-rewrite URLs keep a literal "index.php/" prefix.
        if (strpos($current, 'index.php/') === 0) {
            $current = substr($current, strlen('index.php/'));
        } elseif ($current === 'index.php') {
            $current = '';
        }

        if ($current === '' || $current === 'dashboard') return 'masters';
        if ($current === '_search') return 'search';
        if (strpos($current, 'profile') === 0) return 'password';
        if ($current === 'logout') return 'exit';
        if (strpos($current, 'hub/transportation') === 0) return 'transportation';
        if (strpos($current, 'hub/accounts') === 0) return 'accounts';
        if (strpos($current, 'hub/administration') === 0) return 'administration';

        $buckets = tpt_hub_buckets($auth);
        foreach (['masters', 'transportation', 'accounts', 'administration'] as $key) {
            foreach ($buckets[$key] as $c) {
                $u = trim((string) $c['url'], '/');
                if ($u !== '' && ($u === $current || strpos($current, $u . '/') === 0)) {
                    return $key;
                }
            }
        }
        return '';
    }
}

if (!function_exists('tpt_active')) {
    /**
     * Marks a sidebar link active only if it is the LONGEST-matching menu URL
     * for the current request. Prevents `vendors/scoring` from also lighting up
     * the parent `vendors` link.
     */
    function tpt_active(string $url): string
    {
        static $bestMatch = null;
        if ($bestMatch === null) {
            $current = trim(service('request')->getUri()->getPath(), '/');
            $base    = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
            if ($base && strpos($current, $base) === 0) {
                $current = trim(substr($current, strlen($base)), '/');
            }
            $longest = '';
            foreach (tpt_menu() as $item) {
                $candidates = !empty($item['children']) ? $item['children'] : [$item];
                foreach ($candidates as $c) {
                    $u = trim((string) ($c['url'] ?? ''), '/');
                    if ($u === '') continue;
                    if ($u === $current || strpos($current, $u . '/') === 0) {
                        if (strlen($u) > strlen($longest)) $longest = $u;
                    }
                }
            }
            $bestMatch = $longest;
        }
        $url = trim($url, '/');
        return $url !== '' && $url === $bestMatch ? 'active' : '';
    }
}
