<?php

namespace App\Controllers\Portal;

class DashboardController extends BaseController
{
    public function index()
    {
        $db  = \Config\Database::connect();
        $cid = (int) $this->clientAuth->clientId();

        $activeTrips = (int) $db->table('trips t')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('b.client_id', $cid)
            ->whereIn('t.current_status', ['Booking Created','Vehicle Placed','Loading','In Transit','Arrived','Unloading'])
            ->countAllResults();

        $deliveredThisMonth = (int) $db->table('trips t')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('b.client_id', $cid)
            ->where('t.current_status', 'Delivered')
            ->where('t.delivery_datetime >=', date('Y-m-01 00:00:00'))
            ->countAllResults();

        $pendingBookings = (int) $db->table('bookings')
            ->where('client_id', $cid)
            ->where('booking_status', 'Pending')
            ->where('deleted_at', null)
            ->countAllResults();

        $outstanding = (float) ($db->table('invoices')
            ->selectSum('balance_due', 'total')
            ->where('client_id', $cid)
            ->whereIn('invoice_status', ['Issued','Partially Paid'])
            ->where('deleted_at', null)
            ->get()->getRow('total') ?? 0);

        $overdueCount = (int) $db->table('invoices')
            ->where('client_id', $cid)
            ->whereIn('invoice_status', ['Issued','Partially Paid'])
            ->where('due_date <', date('Y-m-d'))
            ->where('balance_due >', 0)
            ->where('deleted_at', null)
            ->countAllResults();

        $recentTrips = $db->table('trips t')
            ->select('t.id, t.trip_no, t.lr_no, t.current_status, t.dispatch_datetime, t.delivery_datetime, b.route_text, b.booking_no')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('b.client_id', $cid)
            ->where('t.deleted_at', null)
            ->orderBy('t.id', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $recentInvoices = $db->table('invoices')
            ->select('id, invoice_no, invoice_date, total_amount, balance_due, invoice_status, due_date')
            ->where('client_id', $cid)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return $this->render('portal/dashboard/index', [
            'pageTitle'          => 'Dashboard',
            'activeTrips'        => $activeTrips,
            'deliveredThisMonth' => $deliveredThisMonth,
            'pendingBookings'    => $pendingBookings,
            'outstanding'        => $outstanding,
            'overdueCount'       => $overdueCount,
            'recentTrips'        => $recentTrips,
            'recentInvoices'     => $recentInvoices,
        ]);
    }
}
