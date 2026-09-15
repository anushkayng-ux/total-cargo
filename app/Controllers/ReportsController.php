<?php

namespace App\Controllers;

use App\Libraries\Analytics;
use App\Models\ClientModel;
use App\Models\VendorModel;
use App\Models\BookingModel;
use App\Models\InvoiceModel;
use App\Models\VendorBillModel;
use App\Models\TripExpenseModel;
use App\Models\TripExpenseCategoryModel;

class ReportsController extends BaseController
{
    public function index()
    {
        return $this->render('reports/index', [
            'pageTitle' => 'Reports Hub',
        ], retroFixedShell: true);
    }

    /**
     * Single-page executive KPI dashboard. Most queries are cheap aggregates;
     * keep this view as the "stop-everything-else" landing for management.
     */
    public function executive()
    {
        $db = \Config\Database::connect();
        $today  = date('Y-m-d');
        $monStart = date('Y-m-01');
        $yrStart  = date('Y-01-01');

        $sumInvoice = function ($from, $to) use ($db) {
            return (float) ($db->table('invoices')
                ->selectSum('total_amount', 'total')
                ->where('invoice_date >=', $from)
                ->where('invoice_date <=', $to)
                ->where('invoice_status !=', 'Cancelled')
                ->where('deleted_at', null)
                ->get()->getRow('total') ?? 0);
        };
        $sumMargin = function ($from, $to) use ($db) {
            return (float) ($db->table('bookings')
                ->selectSum('margin_amount', 'total')
                ->where('created_at >=', $from . ' 00:00:00')
                ->where('created_at <=', $to . ' 23:59:59')
                ->where('booking_status !=', 'Cancelled')
                ->where('deleted_at', null)
                ->get()->getRow('total') ?? 0);
        };

        // Top-line KPIs
        $revMtd  = $sumInvoice($monStart, $today);
        $revYtd  = $sumInvoice($yrStart,  $today);
        $marMtd  = $sumMargin($monStart, $today);
        $marYtd  = $sumMargin($yrStart,  $today);

        $activeTrips = (int) $db->table('trips')
            ->whereIn('current_status', ['Vehicle Placed','Loading','In Transit','Arrived','Unloading','Delivered'])
            ->where('deleted_at', null)->countAllResults();

        $arOutstanding = (float) ($db->table('invoices')
            ->selectSum('balance_due', 'total')
            ->whereIn('invoice_status', ['Issued','Partially Paid'])
            ->where('deleted_at', null)
            ->get()->getRow('total') ?? 0);

        $apOutstanding = (float) ($db->table('vendor_bills')
            ->selectSum('balance_due', 'total')
            ->whereIn('status', ['Issued','Partially Paid'])
            ->get()->getRow('total') ?? 0);

        $overdueAr = (int) $db->table('invoices')
            ->whereIn('invoice_status', ['Issued','Partially Paid'])
            ->where('due_date <', $today)
            ->where('balance_due >', 0)
            ->where('deleted_at', null)
            ->countAllResults();

        // Top 5 clients YTD by revenue
        $topClients = $db->table('invoices i')
            ->select('c.id, c.company_name, SUM(i.total_amount) AS revenue, COUNT(*) AS invoices')
            ->join('clients c', 'c.id = i.client_id', 'left')
            ->where('i.invoice_date >=', $yrStart)
            ->where('i.invoice_status !=', 'Cancelled')
            ->where('i.deleted_at', null)
            ->groupBy('c.id')
            ->orderBy('revenue', 'DESC')->limit(5)
            ->get()->getResultArray();

        // Top 5 vendors YTD by buy spend
        $topVendors = $db->table('bookings b')
            ->select('v.id, v.company_name, SUM(b.final_buy_rate) AS buy, COUNT(*) AS bookings')
            ->join('vendors v', 'v.id = b.vendor_id', 'left')
            ->where('b.created_at >=', $yrStart . ' 00:00:00')
            ->where('b.booking_status !=', 'Cancelled')
            ->where('b.deleted_at', null)
            ->groupBy('v.id')
            ->orderBy('buy', 'DESC')->limit(5)
            ->get()->getResultArray();

        // On-time delivery rate — last 90 days
        $on = $db->table('trips')
            ->select("COUNT(*) AS total, SUM(CASE WHEN delivery_datetime IS NOT NULL THEN 1 ELSE 0 END) AS delivered")
            ->where('current_status', 'Delivered')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-90 days')))
            ->where('deleted_at', null)
            ->get()->getRowArray();
        $deliveredCount = (int) ($on['delivered'] ?? 0);
        $totalCount = (int) ($on['total'] ?? 0);

        // Recent feedback (last 30 days)
        $feedbackRows = $db->table('trip_feedback')
            ->where('submitted_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->where('submitted_at IS NOT NULL', null, false)
            ->get()->getResultArray();
        $feedbackAvg = 0; $feedbackN = count($feedbackRows);
        if ($feedbackN > 0) {
            $sum = 0;
            foreach ($feedbackRows as $f) $sum += (int) $f['rating_overall'];
            $feedbackAvg = round($sum / $feedbackN, 2);
        }

        // Last 5 trips for the activity feed
        $recentTrips = $db->table('trips t')
            ->select('t.id, t.trip_no, t.current_status, t.updated_at, b.route_text, c.company_name AS client_company, v.company_name AS vendor_company')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->join('clients c',  'c.id = b.client_id', 'left')
            ->join('vendors v',  'v.id = t.vendor_id', 'left')
            ->where('t.deleted_at', null)
            ->orderBy('t.updated_at', 'DESC')->limit(8)
            ->get()->getResultArray();

        return $this->render('reports/executive', [
            'pageTitle'      => 'Executive Dashboard [Reports]',
            'revMtd'         => $revMtd,
            'revYtd'         => $revYtd,
            'marMtd'         => $marMtd,
            'marYtd'         => $marYtd,
            'activeTrips'    => $activeTrips,
            'arOutstanding'  => $arOutstanding,
            'apOutstanding'  => $apOutstanding,
            'overdueAr'      => $overdueAr,
            'topClients'     => $topClients,
            'topVendors'     => $topVendors,
            'feedbackAvg'    => $feedbackAvg,
            'feedbackN'      => $feedbackN,
            'recentTrips'    => $recentTrips,
            'monStart'       => $monStart,
            'yrStart'        => $yrStart,
        ], retroFixedShell: true);
    }

    public function profitability()
    {
        $req = $this->request;
        $filters = [
            'from'      => (string) $req->getGet('from'),
            'to'        => (string) $req->getGet('to'),
            'client_id' => (int) $req->getGet('client_id'),
            'vendor_id' => (int) $req->getGet('vendor_id'),
            'status'    => (string) $req->getGet('status'),
        ];
        $result = Analytics::profitability($filters);
        return $this->render('reports/profitability', [
            'pageTitle' => 'Profitability [Reports]',
            'rows'      => $result['rows'],
            'totals'    => $result['totals'],
            'filters'   => $filters,
            'clients'   => (new ClientModel())->orderBy('company_name')->findAll(),
            'vendors'   => (new VendorModel())->orderBy('company_name')->findAll(),
            'statuses'  => BookingModel::STATUSES,
        ], retroFixedShell: true);
    }

    public function receivables()
    {
        $rows = (new InvoiceModel())->withJoins()
            ->where('invoices.balance_due >', 0)
            ->where('invoices.invoice_status !=', 'Cancelled')
            ->where('invoices.deleted_at IS NULL')
            ->orderBy('invoices.due_date', 'ASC')->findAll();
        $aging    = Analytics::aging($rows, 'balance_due', 'due_date');
        $bucket   = (string) $this->request->getGet('bucket');
        $filtered = $bucket ? $this->filterByBucket($rows, 'balance_due', 'due_date', $bucket) : $rows;
        return $this->render('reports/aging', [
            'pageTitle'   => 'Receivables Aging [Reports]',
            'reportLabel' => 'Receivables Aging',
            'kind'        => 'receivables',
            'rows'        => $filtered,
            'aging'       => $aging,
            'bucket'      => $bucket,
        ], retroFixedShell: true);
    }

    public function payables()
    {
        $rows = (new VendorBillModel())->withJoins()
            ->where('vendor_bills.balance_due >', 0)
            ->where('vendor_bills.status !=', 'Cancelled')
            ->orderBy('vendor_bills.due_date', 'ASC')->findAll();
        $aging    = Analytics::aging($rows, 'balance_due', 'due_date');
        $bucket   = (string) $this->request->getGet('bucket');
        $filtered = $bucket ? $this->filterByBucket($rows, 'balance_due', 'due_date', $bucket) : $rows;
        return $this->render('reports/aging', [
            'pageTitle'   => 'Payables Aging [Reports]',
            'reportLabel' => 'Payables Aging',
            'kind'        => 'payables',
            'rows'        => $filtered,
            'aging'       => $aging,
            'bucket'      => $bucket,
        ], retroFixedShell: true);
    }

    /**
     * Subset rows that fall into a given aging bucket (Current / 0-30 / 31-60 /
     * 61-90 / 90+). Boundaries match Analytics::aging() exactly so totals and
     * filtered lists stay consistent.
     */
    private function filterByBucket(array $rows, string $balanceKey, string $dateKey, string $bucket): array
    {
        $today = strtotime(date('Y-m-d'));
        $out   = [];
        foreach ($rows as $r) {
            $bal = (float) ($r[$balanceKey] ?? 0);
            if ($bal <= 0.01) continue;
            $due = !empty($r[$dateKey]) ? strtotime((string) $r[$dateKey]) : null;
            if (!$due) { $b = 'Current'; }
            else {
                $days = (int) floor(($today - $due) / 86400);
                $b = $days <= 0 ? 'Current' : ($days <= 30 ? '0-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+')));
            }
            if ($b === $bucket) $out[] = $r;
        }
        return $out;
    }

    public function leadFunnel()
    {
        return $this->render('reports/lead_funnel', [
            'pageTitle' => 'Lead Funnel [Reports]',
            'funnel'    => Analytics::leadFunnel(),
        ], retroFixedShell: true);
    }

    public function tripExpenses()
    {
        $req = $this->request;
        $filters = [
            'from'     => (string) $req->getGet('from'),
            'to'       => (string) $req->getGet('to'),
            'category' => (string) $req->getGet('category'),
            'type'     => (string) $req->getGet('type'),     // billable | internal | ''
            'vendor_id'=> (int) $req->getGet('vendor_id'),
            'trip_id'  => (int) $req->getGet('trip_id'),
        ];

        $model = new TripExpenseModel();
        $q = $model->withJoins()->where('trip_expenses.deleted_at IS NULL')->orderBy('trip_expenses.expense_date', 'DESC')->orderBy('trip_expenses.id', 'DESC');
        if (!empty($filters['from']))     $q->where('trip_expenses.expense_date >=', $filters['from']);
        if (!empty($filters['to']))       $q->where('trip_expenses.expense_date <=', $filters['to']);
        if (!empty($filters['category']))  $q->where('trip_expenses.category', $filters['category']);
        if ($filters['type'] === 'billable') $q->where('trip_expenses.is_billable', 1);
        if ($filters['type'] === 'internal') $q->where('trip_expenses.is_billable', 0);
        if ($filters['vendor_id'] > 0)    $q->where('v.id', $filters['vendor_id']);
        if ($filters['trip_id'] > 0)      $q->where('trip_expenses.trip_id', $filters['trip_id']);

        $rows = $q->findAll();
        $totals = ['internal' => 0, 'billable' => 0, 'unbilled' => 0];
        foreach ($rows as $r) {
            if ((int) $r['is_billable'] === 1) {
                $totals['billable'] += (float) $r['amount'];
                if (empty($r['billed_on_invoice_id'])) $totals['unbilled'] += (float) $r['amount'];
            } else {
                $totals['internal'] += (float) $r['amount'];
            }
        }

        return $this->render('reports/trip_expenses', [
            'pageTitle'  => 'Trip Expenses [Reports]',
            'rows'       => $rows,
            'totals'     => $totals,
            'filters'    => $filters,
            'categories' => (new TripExpenseCategoryModel())->active(),
            'vendors'    => (new VendorModel())->orderBy('company_name')->findAll(),
        ], retroFixedShell: true);
    }

    public function unbilledBillable()
    {
        $rows = (new TripExpenseModel())->withJoins()
            ->where('trip_expenses.is_billable', 1)
            ->where('trip_expenses.billed_on_invoice_id IS NULL')
            ->where('trip_expenses.deleted_at IS NULL')
            ->orderBy('trip_expenses.expense_date', 'ASC')
            ->findAll();

        $total = 0;
        foreach ($rows as $r) $total += (float) $r['amount'];

        return $this->render('reports/unbilled_billable', [
            'pageTitle' => 'Unbilled Billable Expenses [Reports]',
            'rows'      => $rows,
            'total'     => $total,
        ], retroFixedShell: true);
    }

    public function expenseCategories()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('trip_expenses')
            ->select('category,
                      COUNT(*) AS count,
                      SUM(CASE WHEN is_billable = 0 THEN amount ELSE 0 END) AS internal,
                      SUM(CASE WHEN is_billable = 1 THEN amount ELSE 0 END) AS billable,
                      SUM(CASE WHEN is_billable = 1 AND billed_on_invoice_id IS NULL THEN amount ELSE 0 END) AS unbilled,
                      SUM(amount) AS total')
            ->where('deleted_at IS NULL')
            ->groupBy('category')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        return $this->render('reports/expense_categories', [
            'pageTitle' => 'Expense Categories Summary [Reports]',
            'rows'      => $rows,
        ], retroFixedShell: true);
    }

    /**
     * Email analytics — funnel KPIs + daily volume + per-template performance.
     */
    public function emailAnalytics()
    {
        $req  = $this->request;
        $from = (string) $req->getGet('from');
        $to   = (string) $req->getGet('to');
        if ($from === '') $from = date('Y-m-d', strtotime('-30 days'));
        if ($to === '')   $to   = date('Y-m-d');

        $db = \Config\Database::connect();
        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        $totals = $db->query("
            SELECT
              COUNT(*) AS total,
              SUM(CASE WHEN sent_at IS NOT NULL THEN 1 ELSE 0 END)         AS sent,
              SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END)    AS delivered,
              SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END)       AS opened,
              SUM(CASE WHEN first_clicked_at IS NOT NULL THEN 1 ELSE 0 END)AS clicked,
              SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END)      AS bounced,
              SUM(CASE WHEN complained_at IS NOT NULL THEN 1 ELSE 0 END)   AS complained,
              SUM(CASE WHEN unsubscribed_at IS NOT NULL THEN 1 ELSE 0 END) AS unsubscribed,
              SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END)           AS failed,
              SUM(CASE WHEN status = 'Suppressed' THEN 1 ELSE 0 END)       AS suppressed,
              SUM(CASE WHEN status = 'Queued' THEN 1 ELSE 0 END)           AS queued
              FROM email_logs
             WHERE created_at BETWEEN ? AND ?
        ", [$fromDt, $toDt])->getRowArray() ?? [];

        $daily = $db->query("
            SELECT DATE(created_at) AS d,
                   COUNT(*)                                                 AS sent,
                   SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END)   AS opened,
                   SUM(CASE WHEN first_clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicked,
                   SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END)  AS bounced
              FROM email_logs
             WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY d ASC
        ", [$fromDt, $toDt])->getResultArray();

        $perTemplate = $db->query("
            SELECT COALESCE(template_key, '(adhoc)') AS template_key,
                   COUNT(*) AS sent,
                   SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END)   AS opened,
                   SUM(CASE WHEN first_clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicked,
                   SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END)  AS bounced,
                   SUM(CASE WHEN complained_at IS NOT NULL THEN 1 ELSE 0 END) AS complained
              FROM email_logs
             WHERE created_at BETWEEN ? AND ?
             GROUP BY template_key
             ORDER BY sent DESC
        ", [$fromDt, $toDt])->getResultArray();

        return $this->render('reports/email_analytics', [
            'pageTitle'   => 'Email Analytics [Reports]',
            'from'        => $from,
            'to'          => $to,
            'totals'      => $totals,
            'daily'       => $daily,
            'perTemplate' => $perTemplate,
        ], retroFixedShell: true);
    }

    /**
     * SOP / TAT KPIs — turnaround stats for each SOP step + per-user scorecard.
     * Window is the last 90 days unless ?from / ?to are provided.
     */
    public function sopKpis()
    {
        $req  = $this->request;
        $from = (string) $req->getGet('from');
        $to   = (string) $req->getGet('to');
        if ($from === '') $from = date('Y-m-d', strtotime('-90 days'));
        if ($to === '')   $to   = date('Y-m-d');

        $db   = \Config\Database::connect();
        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        // Helper: compute hour-stats from a list of seconds
        $stats = function (array $secs): array {
            if (empty($secs)) return ['count' => 0, 'avg_h' => null, 'p50_h' => null, 'p90_h' => null];
            sort($secs);
            $n     = count($secs);
            $avg   = array_sum($secs) / $n;
            $p50   = $secs[(int) floor(($n - 1) * 0.5)];
            $p90   = $secs[(int) floor(($n - 1) * 0.9)];
            return [
                'count' => $n,
                'avg_h' => round($avg / 3600, 1),
                'p50_h' => round($p50 / 3600, 1),
                'p90_h' => round($p90 / 3600, 1),
            ];
        };

        // 1) Booking approval TAT — bookings.created_at → approved_at
        $secs = [];
        foreach ($db->table('bookings')
            ->select('created_at, approved_at')
            ->where('approved_at IS NOT NULL')
            ->where('deleted_at IS NULL')
            ->where('created_at >=', $fromDt)
            ->where('created_at <=', $toDt)
            ->get()->getResultArray() as $r) {
            $secs[] = max(0, strtotime($r['approved_at']) - strtotime($r['created_at']));
        }
        $bookingApproval = $stats($secs);

        // 2) Booking → Vehicle Placement TAT
        $secs = [];
        foreach ($db->table('trip_status_history h')
            ->select('h.changed_at, b.approved_at')
            ->join('trips t',     't.id = h.trip_id', 'left')
            ->join('bookings b',  'b.id = t.booking_id', 'left')
            ->where('h.new_status', 'Vehicle Placed')
            ->where('b.approved_at IS NOT NULL')
            ->where('h.changed_at >=', $fromDt)
            ->where('h.changed_at <=', $toDt)
            ->get()->getResultArray() as $r) {
            $secs[] = max(0, strtotime($r['changed_at']) - strtotime($r['approved_at']));
        }
        $vehiclePlacement = $stats($secs);

        // 3) Dispatch → Delivery TAT
        $secs = [];
        foreach ($db->table('trips')
            ->select('dispatch_datetime, delivery_datetime')
            ->where('dispatch_datetime IS NOT NULL')
            ->where('delivery_datetime IS NOT NULL')
            ->where('deleted_at IS NULL')
            ->where('dispatch_datetime >=', $fromDt)
            ->where('dispatch_datetime <=', $toDt)
            ->get()->getResultArray() as $r) {
            $secs[] = max(0, strtotime($r['delivery_datetime']) - strtotime($r['dispatch_datetime']));
        }
        $transit = $stats($secs);

        // 4) Delivery → POD TAT
        $secs = [];
        foreach ($db->table('trips')
            ->select('delivery_datetime, pod_received_at')
            ->where('delivery_datetime IS NOT NULL')
            ->where('pod_received_at IS NOT NULL')
            ->where('deleted_at IS NULL')
            ->where('delivery_datetime >=', $fromDt)
            ->where('delivery_datetime <=', $toDt)
            ->get()->getResultArray() as $r) {
            $secs[] = max(0, strtotime($r['pod_received_at']) - strtotime($r['delivery_datetime']));
        }
        $podCollection = $stats($secs);

        // 5) Invoice issuance TAT — POD → invoice.invoice_date
        $secs = [];
        foreach ($db->table('invoices i')
            ->select('i.invoice_date, t.pod_received_at')
            ->join('trips t', 't.id = i.trip_id', 'left')
            ->where('i.invoice_status !=', 'Draft')
            ->where('i.deleted_at IS NULL')
            ->where('t.pod_received_at IS NOT NULL')
            ->where('i.invoice_date >=', $from)
            ->where('i.invoice_date <=', $to)
            ->get()->getResultArray() as $r) {
            $secs[] = max(0, strtotime($r['invoice_date'] . ' 12:00:00') - strtotime($r['pod_received_at']));
        }
        $invoiceIssuance = $stats($secs);

        // Per-user scorecard (account managers / CRM users)
        $perUser = $db->query("
            SELECT u.id, u.name,
                   COUNT(DISTINCT l.id) AS leads_handled,
                   SUM(CASE WHEN l.current_status = 'Won'  THEN 1 ELSE 0 END) AS leads_won,
                   SUM(CASE WHEN l.current_status = 'Lost' THEN 1 ELSE 0 END) AS leads_lost,
                   COUNT(DISTINCT b.id) AS bookings,
                   AVG(CASE WHEN b.approved_at IS NOT NULL
                            THEN TIMESTAMPDIFF(HOUR, b.created_at, b.approved_at)
                            ELSE NULL END) AS avg_approve_h
              FROM users u
              LEFT JOIN leads l    ON l.assigned_crm_user_id = u.id
                                  AND l.deleted_at IS NULL
                                  AND l.created_at BETWEEN ? AND ?
              LEFT JOIN bookings b ON b.lead_id = l.id AND b.deleted_at IS NULL
             WHERE u.deleted_at IS NULL AND u.status = 1
             GROUP BY u.id, u.name
             HAVING leads_handled > 0 OR bookings > 0
             ORDER BY bookings DESC, leads_handled DESC
        ", [$fromDt, $toDt])->getResultArray();

        return $this->render('reports/sop_kpis', [
            'pageTitle'         => 'SOP / TAT KPIs [Reports]',
            'from'              => $from,
            'to'                => $to,
            'bookingApproval'   => $bookingApproval,
            'vehiclePlacement'  => $vehiclePlacement,
            'transit'           => $transit,
            'podCollection'     => $podCollection,
            'invoiceIssuance'   => $invoiceIssuance,
            'perUser'           => $perUser,
        ], retroFixedShell: true);
    }
}
