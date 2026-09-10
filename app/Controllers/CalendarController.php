<?php

namespace App\Controllers;

/**
 * Operational calendar — leads (pending), bookings, and trips on one grid.
 * Default scope: items assigned to the current staff user, with filters to widen.
 */
class CalendarController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $users = $db->table('users')->select('id, name')->where('status', 1)->where('deleted_at', null)->orderBy('name', 'ASC')->get()->getResultArray();
        $clients = $db->table('clients')->select('id, company_name')->where('status', 1)->where('deleted_at', null)->orderBy('company_name', 'ASC')->get()->getResultArray();

        return $this->render('calendar/index', [
            'pageTitle' => 'Calendar',
            'users'     => $users,
            'clients'   => $clients,
            'me'        => $this->auth->id(),
        ]);
    }

    /**
     * FullCalendar feed. Returns array of events for [start, end).
     * Filters:
     *   user_id   = staff user id (assigned_crm_user_id on lead OR client's account manager)
     *   client_id = client id (scope to that client)
     *   types     = comma list of "lead,booking,trip" (default = all)
     */
    public function feed()
    {
        $db    = \Config\Database::connect();
        $start = (string) $this->request->getGet('start');
        $end   = (string) $this->request->getGet('end');
        if ($start === '' || $end === '') {
            return $this->response->setJSON([]);
        }
        // FullCalendar sends ISO timestamps; trim to date.
        $startDate = substr($start, 0, 10);
        $endDate   = substr($end, 0, 10);

        $userId   = (int) ($this->request->getGet('user_id') ?: 0);
        $clientId = (int) ($this->request->getGet('client_id') ?: 0);
        $types    = array_filter(array_map('trim', explode(',', (string) $this->request->getGet('types'))));
        if (empty($types)) $types = ['lead','booking','trip'];

        $events = [];

        // ── Leads (pending requests with expected_dispatch_date)
        if (in_array('lead', $types, true)) {
            $q = $db->table('leads l')
                ->select('l.id, l.lead_no, l.pickup_city, l.drop_city, l.vehicle_type_required, l.vehicle_count, l.priority, l.current_status, l.assigned_crm_user_id, l.client_id, l.expected_dispatch_date, c.company_name, c.account_manager_user_id')
                ->join('clients c', 'c.id = l.client_id', 'left')
                ->where('l.deleted_at', null)
                ->where('l.expected_dispatch_date >=', $startDate)
                ->where('l.expected_dispatch_date <',  $endDate)
                ->whereNotIn('l.current_status', ['Won','Lost','Closed']);

            if ($userId)   $q->groupStart()->where('l.assigned_crm_user_id', $userId)->orWhere('c.account_manager_user_id', $userId)->groupEnd();
            if ($clientId) $q->where('l.client_id', $clientId);

            foreach ($q->get()->getResultArray() as $r) {
                $count = (int) ($r['vehicle_count'] ?: 1);
                $events[] = [
                    'id'    => 'L' . $r['id'],
                    'title' => '🟡 ' . $r['lead_no'] . ' · ' . ($r['company_name'] ?? '—')
                                . ' · ' . ($r['pickup_city'] ?? '') . ' - ' . ($r['drop_city'] ?? '')
                                . ' · ' . ($r['vehicle_type_required'] ?: '—') . ($count > 1 ? " ×$count" : ''),
                    'start' => $r['expected_dispatch_date'],
                    'allDay'=> true,
                    'url'   => site_url('leads/' . $r['id']),
                    'color' => $r['priority'] === 'Urgent' ? '#b00020' : '#d4a017',
                    'extendedProps' => ['type' => 'lead', 'status' => $r['current_status']],
                ];
            }
        }

        // ── Bookings (loading_date)
        if (in_array('booking', $types, true)) {
            $q = $db->table('bookings b')
                ->select('b.id, b.booking_no, b.route_text, b.vehicle_type, b.vehicle_count, b.loading_date, b.booking_status, b.client_id, c.company_name, c.account_manager_user_id, l.assigned_crm_user_id')
                ->join('clients c', 'c.id = b.client_id', 'left')
                ->join('leads l',   'l.id = b.lead_id',   'left')
                ->where('b.deleted_at', null)
                ->where('b.loading_date >=', $startDate)
                ->where('b.loading_date <',  $endDate)
                ->whereNotIn('b.booking_status', ['Cancelled']);

            if ($userId)   $q->groupStart()->where('l.assigned_crm_user_id', $userId)->orWhere('c.account_manager_user_id', $userId)->groupEnd();
            if ($clientId) $q->where('b.client_id', $clientId);

            foreach ($q->get()->getResultArray() as $r) {
                $count = (int) ($r['vehicle_count'] ?: 1);
                $events[] = [
                    'id'    => 'B' . $r['id'],
                    'title' => '🔵 ' . $r['booking_no'] . ' · ' . ($r['company_name'] ?? '—')
                                . ' · ' . $r['route_text']
                                . ($count > 1 ? " ×$count" : ''),
                    'start' => $r['loading_date'],
                    'allDay'=> true,
                    'url'   => site_url('bookings/' . $r['id']),
                    'color' => $r['booking_status'] === 'Pending' ? '#888' : '#1d6cb1',
                    'extendedProps' => ['type' => 'booking', 'status' => $r['booking_status']],
                ];
            }
        }

        // ── Trips (dispatch_datetime)
        if (in_array('trip', $types, true)) {
            $q = $db->table('trips t')
                ->select('t.id, t.trip_no, t.lr_no, t.vehicle_number, t.dispatch_datetime, t.current_status, b.client_id, b.route_text, c.company_name, c.account_manager_user_id, l.assigned_crm_user_id')
                ->join('bookings b', 'b.id = t.booking_id', 'left')
                ->join('clients c',  'c.id = b.client_id',  'left')
                ->join('leads l',    'l.id = b.lead_id',    'left')
                ->where('t.deleted_at', null)
                ->where('t.dispatch_datetime >=', $startDate . ' 00:00:00')
                ->where('t.dispatch_datetime <',  $endDate   . ' 00:00:00');

            if ($userId)   $q->groupStart()->where('l.assigned_crm_user_id', $userId)->orWhere('c.account_manager_user_id', $userId)->groupEnd();
            if ($clientId) $q->where('b.client_id', $clientId);

            foreach ($q->get()->getResultArray() as $r) {
                $events[] = [
                    'id'    => 'T' . $r['id'],
                    'title' => '🟢 ' . $r['trip_no'] . ' · ' . ($r['company_name'] ?? '—')
                                . ' · ' . ($r['vehicle_number'] ?: '—')
                                . ' · ' . $r['current_status'],
                    'start' => $r['dispatch_datetime'],
                    'allDay'=> false,
                    'url'   => site_url('trips/' . $r['id']),
                    'color' => $r['current_status'] === 'Delivered' ? '#166c3b'
                              : ($r['current_status'] === 'In Transit' ? '#0f7e4f' : '#5a8c6a'),
                    'extendedProps' => ['type' => 'trip', 'status' => $r['current_status']],
                ];
            }
        }

        return $this->response->setJSON($events);
    }
}
