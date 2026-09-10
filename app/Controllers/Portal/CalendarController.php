<?php

namespace App\Controllers\Portal;

class CalendarController extends BaseController
{
    public function index()
    {
        return $this->render('portal/calendar/index', [
            'pageTitle' => 'Calendar',
        ]);
    }

    /**
     * JSON feed scoped to the logged-in client. Shows leads, bookings, trips on the grid.
     * Querystring: start, end (ISO from FullCalendar).
     */
    public function feed()
    {
        $cid   = (int) $this->clientAuth->clientId();
        $start = (string) $this->request->getGet('start');
        $end   = (string) $this->request->getGet('end');
        if ($start === '' || $end === '') return $this->response->setJSON([]);

        $startDate = substr($start, 0, 10);
        $endDate   = substr($end,   0, 10);
        $db = \Config\Database::connect();
        $events = [];

        // Leads (pending requests)
        $leads = $db->table('leads')
            ->select('id, lead_no, current_status, pickup_city, drop_city, vehicle_type_required, vehicle_count, expected_dispatch_date, priority')
            ->where('client_id', $cid)
            ->where('deleted_at', null)
            ->where('expected_dispatch_date >=', $startDate)
            ->where('expected_dispatch_date <',  $endDate)
            ->whereNotIn('current_status', ['Won','Lost','Closed'])
            ->get()->getResultArray();

        foreach ($leads as $l) {
            $count = (int) ($l['vehicle_count'] ?: 1);
            $events[] = [
                'id'    => 'L' . $l['id'],
                'title' => '🟡 Request ' . $l['lead_no']
                            . ' · ' . ($l['pickup_city'] ?? '') . '→' . ($l['drop_city'] ?? '')
                            . ' · ' . ($l['vehicle_type_required'] ?: '—')
                            . ($count > 1 ? " ×$count" : ''),
                'start' => $l['expected_dispatch_date'],
                'allDay'=> true,
                'color' => $l['priority'] === 'Urgent' ? '#b00020' : '#d4a017',
                'extendedProps' => ['type' => 'lead', 'status' => $l['current_status']],
            ];
        }

        // Bookings
        $bookings = $db->table('bookings')
            ->select('id, booking_no, route_text, vehicle_type, vehicle_count, loading_date, booking_status')
            ->where('client_id', $cid)
            ->where('deleted_at', null)
            ->where('loading_date >=', $startDate)
            ->where('loading_date <',  $endDate)
            ->whereNotIn('booking_status', ['Cancelled'])
            ->get()->getResultArray();

        foreach ($bookings as $b) {
            $count = (int) ($b['vehicle_count'] ?: 1);
            $events[] = [
                'id'    => 'B' . $b['id'],
                'title' => '🔵 Booking ' . $b['booking_no'] . ' · ' . $b['route_text']
                            . ($count > 1 ? " ×$count" : ''),
                'start' => $b['loading_date'],
                'allDay'=> true,
                'url'   => site_url('portal/bookings/' . $b['id']),
                'color' => $b['booking_status'] === 'Pending' ? '#888' : '#1d6cb1',
                'extendedProps' => ['type' => 'booking', 'status' => $b['booking_status']],
            ];
        }

        // Trips
        $trips = $db->table('trips t')
            ->select('t.id, t.trip_no, t.lr_no, t.vehicle_number, t.dispatch_datetime, t.current_status, b.route_text')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('b.client_id', $cid)
            ->where('t.deleted_at', null)
            ->where('t.dispatch_datetime >=', $startDate . ' 00:00:00')
            ->where('t.dispatch_datetime <',  $endDate   . ' 00:00:00')
            ->get()->getResultArray();

        foreach ($trips as $t) {
            $events[] = [
                'id'    => 'T' . $t['id'],
                'title' => '🟢 Trip ' . $t['trip_no'] . ' · ' . ($t['vehicle_number'] ?: '—') . ' · ' . $t['current_status'],
                'start' => $t['dispatch_datetime'],
                'allDay'=> false,
                'url'   => site_url('portal/trips/' . $t['id']),
                'color' => $t['current_status'] === 'Delivered' ? '#166c3b'
                          : ($t['current_status'] === 'In Transit' ? '#0f7e4f' : '#5a8c6a'),
                'extendedProps' => ['type' => 'trip', 'status' => $t['current_status']],
            ];
        }

        return $this->response->setJSON($events);
    }
}
