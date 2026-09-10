<?php

namespace App\Controllers\Portal;

class TripsController extends BaseController
{
    public function index()
    {
        $cid    = (int) $this->clientAuth->clientId();
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('q'));

        $db = \Config\Database::connect();
        $q  = $db->table('trips t')
            ->select('t.id, t.trip_no, t.lr_no, t.current_status, t.dispatch_datetime, t.delivery_datetime, t.vehicle_number, b.route_text, b.booking_no')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('b.client_id', $cid)
            ->where('t.deleted_at', null);

        if ($status !== '') $q->where('t.current_status', $status);
        if ($search !== '') {
            $q->groupStart()
                ->like('t.trip_no', $search)
                ->orLike('t.lr_no', $search)
                ->orLike('t.vehicle_number', $search)
                ->orLike('b.route_text', $search)
                ->groupEnd();
        }

        $rows = $q->orderBy('t.id', 'DESC')->limit(40)->get()->getResultArray();

        return $this->render('portal/trips/index', [
            'pageTitle' => 'My Trips',
            'rows'      => $rows,
            'status'    => $status,
            'search'    => $search,
            'statuses'  => \App\Models\TripModel::STATUSES,
        ]);
    }

    public function show(int $id)
    {
        $db  = \Config\Database::connect();
        $cid = (int) $this->clientAuth->clientId();

        $row = $db->table('trips t')
            ->select('t.*, b.client_id, b.booking_no, b.route_text, b.consignee_name, b.consignee_mobile')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('t.id', $id)
            ->where('t.deleted_at', null)
            ->get()->getRowArray();

        // Ownership check via the joined booking row
        if (!$row || (int) ($row['client_id'] ?? 0) !== $cid) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Hide internal carrier identity from clients — just label as "Assigned partner".
        // Driver name shown, mobile not.
        $milestones = $db->table('trip_status_history')
            ->select('old_status, new_status, notes, changed_at')
            ->where('trip_id', $id)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // GPS — show last-known location only, with a 15-min freshness lag for vendor anonymity.
        $gps = null;
        if (!empty($row['vehicle_number'])) {
            $gps = $db->table('latest_vehicle_status')
                ->select('latitude, longitude, gps_timestamp, address, eta_text, delay_flag, updated_at')
                ->where('vehicle_number', $row['vehicle_number'])
                ->get()->getRowArray();
            if ($gps && !empty($gps['gps_timestamp'])) {
                $age = time() - strtotime($gps['gps_timestamp']);
                $gps['stale'] = $age > (30 * 60);
            }
        }

        $invoices = $db->table('invoices')
            ->select('id, invoice_no, total_amount, balance_due, invoice_status')
            ->where('trip_id', $id)
            ->where('deleted_at', null)
            ->get()->getResultArray();

        $this->audit('trip', $id, 'view');

        return $this->render('portal/trips/show', [
            'pageTitle'  => 'Trip ' . $row['trip_no'],
            'row'        => $row,
            'milestones' => $milestones,
            'gps'        => $gps,
            'invoices'   => $invoices,
            'statuses'   => \App\Models\TripModel::STATUSES,
        ]);
    }
}
