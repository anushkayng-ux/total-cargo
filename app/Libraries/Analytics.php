<?php

namespace App\Libraries;

/**
 * Centralized analytics queries. Controllers call static methods here so
 * business metrics have one source of truth and are easy to reuse across
 * dashboards and reports.
 */
class Analytics
{
    /** Top-level counts for the executive dashboard. Cached 5 min — stat is OK to lag. */
    public static function dashboardCounts(): array
    {
        $cache = service('cache');
        $key   = 'dashboard.counts.v2';
        $hit   = $cache->get($key);
        if (is_array($hit)) return $hit;

        $r = self::dashboardCountsUncached();
        $cache->save($key, $r, 300);
        return $r;
    }

    private static function dashboardCountsUncached(): array
    {
        $db = \Config\Database::connect();
        $monthStart = date('Y-m-01');
        $today      = date('Y-m-d');

        return [
            'leads_total'       => (int) $db->table('leads')->where('deleted_at IS NULL')->countAllResults(),
            'leads_this_month'  => (int) $db->table('leads')->where('deleted_at IS NULL')->where('lead_datetime >=', $monthStart)->countAllResults(),
            'open_rfq'          => (int) $db->table('rfq_master')->whereIn('status', ['Open', 'In Progress', 'Quote Received'])->countAllResults(),
            'bookings_total'    => (int) $db->table('bookings')->where('deleted_at IS NULL')->countAllResults(),
            'active_trips'      => (int) $db->table('trips')->whereNotIn('current_status', ['Closed', 'Cancelled'])->where('deleted_at IS NULL')->countAllResults(),
            'delayed_trips'     => (int) $db->table('trips')
                ->join('latest_vehicle_status lvs', 'lvs.trip_id = trips.id', 'left')
                ->whereNotIn('trips.current_status', ['Closed', 'Cancelled'])
                ->where('trips.deleted_at IS NULL')
                ->groupStart()
                    ->where('lvs.delay_flag', 1)
                    ->orWhere("trips.delay_reason IS NOT NULL AND trips.delay_reason != ''")
                ->groupEnd()
                ->countAllResults(),
            'pod_pending'       => (int) $db->table('trips')->where('pod_status', 'Pending')->whereNotIn('current_status', ['Booking Created', 'Cancelled'])->where('deleted_at IS NULL')->countAllResults(),
            'invoices_unpaid'   => (int) $db->table('invoices')->whereIn('invoice_status', ['Issued', 'Partially Paid'])->where('deleted_at IS NULL')->countAllResults(),
            'invoices_overdue'  => (int) $db->table('invoices')->where('balance_due >', 0)->where('due_date <', $today)->where('invoice_status !=', 'Cancelled')->where('deleted_at IS NULL')->countAllResults(),
            'receivables_total' => (float) ($db->table('invoices')->selectSum('balance_due', 't')->where('invoice_status !=', 'Cancelled')->where('deleted_at IS NULL')->get()->getRow('t') ?? 0),
            'payables_total'    => (float) ($db->table('vendor_bills')->selectSum('balance_due', 't')->where('status !=', 'Cancelled')->get()->getRow('t') ?? 0),
            'vendors_active'    => (int) $db->table('vendors')->where('status', 1)->where('is_blacklisted', 0)->where('deleted_at IS NULL')->countAllResults(),
            'clients_total'     => (int) $db->table('clients')->where('deleted_at IS NULL')->countAllResults(),
            'margin_this_month' => (float) ($db->table('bookings')
                ->selectSum('margin_amount', 't')
                ->where('deleted_at IS NULL')
                ->whereIn('booking_status', ['Approved', 'Handed Over', 'Completed'])
                ->where('loading_date >=', $monthStart)->get()->getRow('t') ?? 0),
            'revenue_this_month'=> (float) ($db->table('invoices')
                ->selectSum('total_amount', 't')
                ->where('invoice_status !=', 'Cancelled')
                ->where('deleted_at IS NULL')
                ->where('invoice_date >=', $monthStart)->get()->getRow('t') ?? 0),
            'collected_this_month' => (float) ($db->table('receipts')
                ->selectSum('amount_received', 't')
                ->where('receipt_date >=', $monthStart)->get()->getRow('t') ?? 0),
            'internal_expenses_mtd' => (float) ($db->table('trip_expenses')
                ->selectSum('amount', 't')
                ->where('is_billable', 0)
                ->where('deleted_at IS NULL')
                ->where('expense_date >=', $monthStart)->get()->getRow('t') ?? 0),
            'billable_expenses_mtd' => (float) ($db->table('trip_expenses')
                ->selectSum('amount', 't')
                ->where('is_billable', 1)
                ->where('deleted_at IS NULL')
                ->where('expense_date >=', $monthStart)->get()->getRow('t') ?? 0),
            'unbilled_billable_total' => (float) ($db->table('trip_expenses')
                ->selectSum('amount', 't')
                ->where('is_billable', 1)
                ->where('billed_on_invoice_id IS NULL')
                ->where('deleted_at IS NULL')
                ->get()->getRow('t') ?? 0),
        ];
    }

    /** Monthly billed + collected for the last N months (ascending). Cached 5 min. */
    public static function monthlyRevenue(int $months = 6): array
    {
        $cache = service('cache');
        $key   = 'dashboard.monthly.' . $months;
        $hit   = $cache->get($key);
        if (is_array($hit)) return $hit;

        $db = \Config\Database::connect();
        $start = date('Y-m-01', strtotime("-" . ($months - 1) . " months"));
        $end   = date('Y-m-t');

        // Three single GROUP BY queries instead of months * 3 separate scans.
        $billed = $db->query(
            "SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS m, COALESCE(SUM(total_amount),0) AS t
               FROM invoices
              WHERE invoice_status != 'Cancelled' AND deleted_at IS NULL
                AND invoice_date BETWEEN ? AND ?
              GROUP BY m",
            [$start, $end]
        )->getResultArray();
        $received = $db->query(
            "SELECT DATE_FORMAT(receipt_date, '%Y-%m') AS m, COALESCE(SUM(amount_received),0) AS t
               FROM receipts
              WHERE receipt_date BETWEEN ? AND ?
              GROUP BY m",
            [$start, $end]
        )->getResultArray();
        $margin = $db->query(
            "SELECT DATE_FORMAT(loading_date, '%Y-%m') AS m, COALESCE(SUM(margin_amount),0) AS t
               FROM bookings
              WHERE deleted_at IS NULL AND booking_status IN ('Approved','Handed Over','Completed')
                AND loading_date BETWEEN ? AND ?
              GROUP BY m",
            [$start, $end]
        )->getResultArray();

        $bm = []; foreach ($billed   as $r) $bm[$r['m']] = (float) $r['t'];
        $rm = []; foreach ($received as $r) $rm[$r['m']] = (float) $r['t'];
        $mm = []; foreach ($margin   as $r) $mm[$r['m']] = (float) $r['t'];

        $rows = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key2 = date('Y-m', strtotime("-$i months"));
            $rows[] = [
                'month'    => date('M', strtotime($key2 . '-01')),
                'billed'   => round($bm[$key2] ?? 0, 2),
                'received' => round($rm[$key2] ?? 0, 2),
                'margin'   => round($mm[$key2] ?? 0, 2),
            ];
        }
        $cache->save($key, $rows, 300);
        return $rows;
    }

    /** Top-N clients by total billed. */
    public static function topClients(int $limit = 5): array
    {
        $db = \Config\Database::connect();
        return $db->table('invoices')
            ->select('clients.id, clients.company_name, SUM(invoices.total_amount) AS total_billed, SUM(invoices.amount_received) AS total_received, SUM(invoices.balance_due) AS outstanding')
            ->join('clients', 'clients.id = invoices.client_id')
            ->where('invoices.invoice_status !=', 'Cancelled')
            ->where('invoices.deleted_at IS NULL')
            ->groupBy('clients.id')
            ->orderBy('total_billed', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /** Top-N vendors by number of bookings and total bill amount. */
    public static function topVendors(int $limit = 5): array
    {
        $db = \Config\Database::connect();
        return $db->table('bookings')
            ->select('vendors.id, vendors.company_name, vendors.rating, COUNT(bookings.id) AS bookings_count, SUM(bookings.final_buy_rate) AS total_buy, SUM(bookings.margin_amount) AS total_margin')
            ->join('vendors', 'vendors.id = bookings.vendor_id')
            ->where('bookings.deleted_at IS NULL')
            ->whereIn('bookings.booking_status', ['Approved', 'Handed Over', 'Completed'])
            ->groupBy('vendors.id')
            ->orderBy('bookings_count', 'DESC')
            ->orderBy('total_buy', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /** Lead funnel counts by status. */
    public static function leadFunnel(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('leads')
            ->select('current_status, COUNT(*) AS c')
            ->where('deleted_at IS NULL')
            ->groupBy('current_status')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) $map[$r['current_status']] = (int) $r['c'];
        $statuses = \App\Models\LeadModel::STATUSES;
        $out = [];
        foreach ($statuses as $s) {
            $out[] = ['status' => $s, 'count' => $map[$s] ?? 0];
        }
        return $out;
    }

    /** Aging buckets from a set of rows with a balance + date column. */
    public static function aging(array $rows, string $balanceKey, string $dateKey): array
    {
        $buckets = ['Current' => 0, '0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $today   = strtotime(date('Y-m-d'));
        foreach ($rows as $r) {
            $bal = (float) ($r[$balanceKey] ?? 0);
            if ($bal <= 0.01) continue;
            $due = !empty($r[$dateKey]) ? strtotime((string) $r[$dateKey]) : null;
            if (!$due) { $buckets['Current'] += $bal; continue; }
            $days = (int) floor(($today - $due) / 86400);
            if ($days <= 0)        $buckets['Current'] += $bal;
            elseif ($days <= 30)   $buckets['0-30']    += $bal;
            elseif ($days <= 60)   $buckets['31-60']   += $bal;
            elseif ($days <= 90)   $buckets['61-90']   += $bal;
            else                   $buckets['90+']     += $bal;
        }
        foreach ($buckets as $k => $v) $buckets[$k] = round($v, 2);
        return $buckets;
    }

    /**
     * Compute vendor scorecards for all vendors (or a single one if id given).
     * Replaces 11-correlated-subquery pattern with a handful of GROUP BY aggregates
     * collected per vendor and stitched in PHP — safe at hundreds of vendors.
     */
    public static function vendorScorecards(?int $vendorId = null): array
    {
        $db = \Config\Database::connect();
        $cache = service('cache');
        $cacheKey = 'analytics.vendor_scorecards.' . ($vendorId ?: 'all');
        $hit = $cache->get($cacheKey);
        if (is_array($hit)) return $hit;

        // Base vendors list
        $vBuilder = $db->table('vendors')
            ->select('id, vendor_code, company_name, rating, is_preferred, is_blacklisted, status')
            ->where('deleted_at IS NULL')
            ->orderBy('is_preferred', 'DESC')
            ->orderBy('rating', 'DESC')
            ->orderBy('company_name', 'ASC');
        if ($vendorId) $vBuilder->where('id', $vendorId);
        $vendors = $vBuilder->get()->getResultArray();
        if (empty($vendors)) return [];

        $vIds = array_map(fn($v) => (int) $v['id'], $vendors);
        $vIdsSql = implode(',', $vIds);  // safe: ints from query result

        // RFQ counts
        $rfqs = $db->query("SELECT vendor_id, COUNT(*) AS c FROM rfq_vendors WHERE vendor_id IN ($vIdsSql) GROUP BY vendor_id")->getResultArray();
        // Quotations: count + avg response + awarded
        $quotes = $db->query("SELECT vendor_id,
                                COUNT(*) AS c,
                                AVG(response_time_minutes) AS avg_resp,
                                SUM(CASE WHEN is_final_selected = 1 THEN 1 ELSE 0 END) AS awarded
                              FROM quotations WHERE vendor_id IN ($vIdsSql) GROUP BY vendor_id")->getResultArray();
        // Bookings: total + cancelled
        $books = $db->query("SELECT vendor_id,
                                COUNT(*) AS c,
                                SUM(CASE WHEN booking_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
                              FROM bookings WHERE vendor_id IN ($vIdsSql) AND deleted_at IS NULL GROUP BY vendor_id")->getResultArray();
        // Trips: delivered + POD received (joined via bookings)
        $trips = $db->query("SELECT b.vendor_id,
                                SUM(CASE WHEN t.current_status IN ('Delivered','POD Received','Closed') THEN 1 ELSE 0 END) AS delivered,
                                SUM(CASE WHEN t.pod_status = 'Received' THEN 1 ELSE 0 END) AS pod_recv
                              FROM trips t INNER JOIN bookings b ON b.id = t.booking_id
                              WHERE b.vendor_id IN ($vIdsSql) GROUP BY b.vendor_id")->getResultArray();
        // Vendor bills aggregates
        $bills = $db->query("SELECT vendor_id,
                                COALESCE(SUM(bill_amount),0) AS billed,
                                COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN balance_due ELSE 0 END),0) AS payable
                              FROM vendor_bills WHERE vendor_id IN ($vIdsSql) GROUP BY vendor_id")->getResultArray();

        $idx = function (array $rows, string $key) {
            $out = [];
            foreach ($rows as $r) $out[(int) $r[$key]] = $r;
            return $out;
        };
        $byRfq   = $idx($rfqs,   'vendor_id');
        $byQuote = $idx($quotes, 'vendor_id');
        $byBook  = $idx($books,  'vendor_id');
        $byTrip  = $idx($trips,  'vendor_id');
        $byBill  = $idx($bills,  'vendor_id');

        $rows = [];
        foreach ($vendors as $v) {
            $vid = (int) $v['id'];
            $rows[] = array_merge($v, [
                'rfqs_received'        => (int)   ($byRfq[$vid]['c']         ?? 0),
                'quotes_given'         => (int)   ($byQuote[$vid]['c']       ?? 0),
                'avg_response_minutes' => isset($byQuote[$vid]['avg_resp']) && $byQuote[$vid]['avg_resp'] !== null
                                            ? (float) $byQuote[$vid]['avg_resp'] : null,
                'awarded_count'        => (int)   ($byQuote[$vid]['awarded'] ?? 0),
                'bookings_count'       => (int)   ($byBook[$vid]['c']        ?? 0),
                'cancelled_count'      => (int)   ($byBook[$vid]['cancelled']?? 0),
                'pod_received_count'   => (int)   ($byTrip[$vid]['pod_recv'] ?? 0),
                'delivered_count'      => (int)   ($byTrip[$vid]['delivered']?? 0),
                'billed_total'         => (float) ($byBill[$vid]['billed']   ?? 0),
                'payable_outstanding'  => (float) ($byBill[$vid]['payable']  ?? 0),
            ]);
        }

        foreach ($rows as &$r) {
            $rfqs      = (int) $r['rfqs_received'];
            $quotes    = (int) $r['quotes_given'];
            $bookings  = (int) $r['bookings_count'];
            $delivered = (int) $r['delivered_count'];
            $podRcvd   = (int) $r['pod_received_count'];
            $cancelled = (int) $r['cancelled_count'];

            $r['response_rate']   = $rfqs     > 0 ? round($quotes    * 100 / $rfqs,     1) : null;
            $r['win_rate']        = $quotes   > 0 ? round(((int)$r['awarded_count']) * 100 / $quotes,   1) : null;
            $r['cancel_rate']     = $bookings > 0 ? round($cancelled * 100 / $bookings, 1) : null;
            $r['pod_rate']        = $delivered> 0 ? round($podRcvd   * 100 / $delivered,1) : null;
            $r['avg_resp_mins']   = $r['avg_response_minutes'] !== null ? round((float) $r['avg_response_minutes'], 1) : null;

            // Composite score: higher = better. Caps make it bounded.
            $score = 0;
            $score += ($r['response_rate'] ?? 0) * 0.25;           // up to 25
            $score += (100 - min((float)($r['avg_resp_mins'] ?? 120), 120)) * 0.10; // up to 10
            $score += ($r['pod_rate'] ?? 0) * 0.20;                // up to 20
            $score += (float) $r['rating'] * 4;                    // up to 20
            $score -= ($r['cancel_rate'] ?? 0) * 0.50;             // minus up to 50
            if ((int) $r['is_preferred'])   $score += 5;
            if ((int) $r['is_blacklisted']) $score -= 30;
            $r['composite_score'] = round(max(0, $score), 1);
        }
        unset($r);

        usort($rows, fn($a, $b) => ($b['composite_score'] ?? 0) <=> ($a['composite_score'] ?? 0));
        $cache->save($cacheKey, $rows, 600);   // 10 min cache
        return $rows;
    }

    /** Booking-level profitability with filters. */
    public static function profitability(array $filters = []): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('bookings')
            ->select('bookings.id, bookings.booking_no, bookings.loading_date, bookings.route_text, bookings.booking_status,
                      bookings.final_buy_rate, bookings.final_sell_rate, bookings.margin_amount,
                      clients.company_name AS client_name, vendors.company_name AS vendor_name,
                      trips.trip_no, trips.current_status AS trip_status')
            ->join('clients', 'clients.id = bookings.client_id', 'left')
            ->join('vendors', 'vendors.id = bookings.vendor_id', 'left')
            ->join('trips',   'trips.booking_id = bookings.id',   'left')
            ->where('bookings.deleted_at IS NULL')
            ->orderBy('bookings.loading_date', 'DESC')
            ->orderBy('bookings.id', 'DESC');

        if (!empty($filters['from']))      $builder->where('bookings.loading_date >=', $filters['from']);
        if (!empty($filters['to']))        $builder->where('bookings.loading_date <=', $filters['to']);
        if (!empty($filters['client_id'])) $builder->where('bookings.client_id', (int) $filters['client_id']);
        if (!empty($filters['vendor_id'])) $builder->where('bookings.vendor_id', (int) $filters['vendor_id']);
        if (!empty($filters['status']))    $builder->where('bookings.booking_status', $filters['status']);

        $rows = $builder->get()->getResultArray();

        // Pull expenses for each trip in one query to compute true margin
        $tripIds = array_values(array_filter(array_map(fn($r) => (int) ($r['trip_no'] ? $r['trip_no'] : 0), $rows))); // not useful — use booking.id join map
        // Better: load expenses keyed by booking_id via trip join
        $bookingIds = array_values(array_filter(array_map(fn($r) => (int) $r['id'], $rows)));
        $expensesByBooking = [];
        if (!empty($bookingIds)) {
            $res = $db->table('trip_expenses te')
                ->select('t.booking_id, SUM(CASE WHEN te.is_billable = 0 THEN te.amount ELSE 0 END) AS internal,
                          SUM(CASE WHEN te.is_billable = 1 THEN te.amount ELSE 0 END) AS billable,
                          SUM(CASE WHEN te.is_billable = 1 AND te.billed_on_invoice_id IS NOT NULL THEN te.amount ELSE 0 END) AS billed')
                ->join('trips t', 't.id = te.trip_id')
                ->whereIn('t.booking_id', $bookingIds)
                ->where('te.deleted_at IS NULL')
                ->groupBy('t.booking_id')
                ->get()->getResultArray();
            foreach ($res as $r) {
                $expensesByBooking[(int) $r['booking_id']] = [
                    'internal' => (float) $r['internal'],
                    'billable' => (float) $r['billable'],
                    'billed'   => (float) $r['billed'],
                ];
            }
        }

        $totals = ['buy' => 0, 'sell' => 0, 'margin' => 0, 'internal_exp' => 0, 'billable_recovered' => 0, 'true_margin' => 0];
        foreach ($rows as &$r) {
            $exp = $expensesByBooking[(int) $r['id']] ?? ['internal' => 0, 'billable' => 0, 'billed' => 0];
            $r['internal_expenses']   = $exp['internal'];
            $r['billable_expenses']   = $exp['billable'];
            $r['billed_recovered']    = $exp['billed'];
            $r['true_margin']         = round(((float) $r['margin_amount']) + $r['billed_recovered'] - $r['internal_expenses'], 2);

            $totals['buy']    += (float) $r['final_buy_rate'];
            $totals['sell']   += (float) $r['final_sell_rate'];
            $totals['margin'] += (float) $r['margin_amount'];
            $totals['internal_exp']       += $r['internal_expenses'];
            $totals['billable_recovered'] += $r['billed_recovered'];
            $totals['true_margin']        += $r['true_margin'];
        }
        unset($r);
        $totals['margin_pct']      = $totals['sell'] > 0 ? round($totals['margin']      * 100 / $totals['sell'], 2) : 0;
        $trueRev                   = $totals['sell'] + $totals['billable_recovered'];
        $totals['true_margin_pct'] = $trueRev > 0    ? round($totals['true_margin']     * 100 / $trueRev, 2)       : 0;
        return ['rows' => $rows, 'totals' => $totals];
    }
}
