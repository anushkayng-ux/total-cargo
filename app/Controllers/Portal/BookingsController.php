<?php

namespace App\Controllers\Portal;

use App\Models\BookingModel;

class BookingsController extends BaseController
{
    public function index()
    {
        $cid    = (int) $this->clientAuth->clientId();
        $status = (string) $this->request->getGet('status');
        $search = trim((string) $this->request->getGet('q'));
        $model  = new BookingModel();

        $query = $model->select('bookings.id, bookings.booking_no, bookings.route_text, bookings.vehicle_type, bookings.loading_date, bookings.booking_status, bookings.final_sell_rate, bookings.created_at')
            ->where('bookings.client_id', $cid)
            ->orderBy('bookings.id', 'DESC');

        if ($status !== '' && in_array($status, BookingModel::STATUSES, true)) {
            $query->where('bookings.booking_status', $status);
        }
        if ($search !== '') {
            $query->groupStart()
                ->like('bookings.booking_no', $search)
                ->orLike('bookings.route_text', $search)
                ->groupEnd();
        }

        return $this->render('portal/bookings/index', [
            'pageTitle' => 'My Bookings',
            'rows'      => $query->paginate(20),
            'pager'     => $model->pager,
            'status'    => $status,
            'search'    => $search,
            'statuses'  => BookingModel::STATUSES,
        ]);
    }

    /**
     * Rebook: pre-fill the request form using fields from a previous booking.
     * Defense-in-depth ownership check via requireOwn.
     */
    public function rebook(int $id)
    {
        $row = (new BookingModel())->find($id);
        $row = $this->requireOwn($row);

        $params = [
            'pickup_city'           => $this->cityFromRoute($row['route_text'] ?? '', 0),
            'drop_city'             => $this->cityFromRoute($row['route_text'] ?? '', 1),
            'vehicle_type_required' => $row['vehicle_type'] ?? '',
            'vehicle_count'         => max(1, (int) ($row['vehicle_count'] ?? 1)),
            'material_type'         => $row['load_details'] ?? '',
            'remarks'               => 'Repeat of ' . ($row['booking_no'] ?? ''),
        ];

        $this->audit('booking', $id, 'rebook_initiated');

        // Use a flash bag for the prefill so the GET URL stays clean
        $this->session->setFlashdata('rebook_prefill', $params);
        return redirect()->to(site_url('portal/request'));
    }

    /** Best-effort split of a "City A → City B" route string. */
    private function cityFromRoute(string $route, int $idx): string
    {
        $parts = preg_split('/\s*(?:→|->|to|—|-)\s*/u', $route, 2);
        return trim($parts[$idx] ?? '');
    }

    public function show(int $id)
    {
        $row = (new BookingModel())->find($id);
        $row = $this->requireOwn($row);

        $db = \Config\Database::connect();
        $trips = $db->table('trips')
            ->select('id, trip_no, lr_no, current_status, dispatch_datetime, delivery_datetime, vehicle_number')
            ->where('booking_id', $id)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $invoices = $db->table('invoices')
            ->select('id, invoice_no, invoice_date, total_amount, balance_due, invoice_status')
            ->where('booking_id', $id)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $this->audit('booking', $id, 'view');

        return $this->render('portal/bookings/show', [
            'pageTitle' => 'Booking ' . $row['booking_no'],
            'row'       => $row,
            'trips'     => $trips,
            'invoices'  => $invoices,
        ]);
    }
}
