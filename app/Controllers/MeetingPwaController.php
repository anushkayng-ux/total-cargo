<?php

namespace App\Controllers;

/**
 * Auth + token-gated meeting/travel PWA for staff. The token is created
 * when a meeting is planned in the staff app (MeetingsController::store).
 *
 * Visitors must be logged in AND own the meeting (or have meetings.can_approve).
 * AuthFilter handles the login redirect with intended-URL stash, so a deep
 * link to /m/<token> survives the round trip through /login.
 *
 * Once on the PWA, the staffer taps four punch buttons:
 *   1. Depart office  →  meeting_events.event_type = depart_office
 *   2. Arrive meeting →  arrive_meeting
 *   3. Leave meeting  →  leave_meeting
 *   4. Back at office →  return_office
 *
 * Each tap records GPS lat/lng + timestamp. Status auto-progresses from
 * Planned → InProgress → Completed.
 */
class MeetingPwaController extends BaseController
{
    /** Resolve token → meeting, or null. */
    private function meeting(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $db  = \Config\Database::connect();
        $row = $db->table('meetings')->where('pwa_token', $token)->get()->getRowArray();
        if (!$row || in_array($row['status'], ['Cancelled'], true)) return null;
        return $row;
    }

    /** Owner-or-manager check. Returns true on success, false if access denied. */
    private function canAccess(array $meeting): bool
    {
        $uid = (int) ($this->auth->id() ?? 0);
        if ($uid && (int) $meeting['user_id'] === $uid) return true;
        return $this->auth->can('meetings', 'can_approve');
    }

    public function index(string $token)
    {
        $meeting = $this->meeting($token);
        if (!$meeting) return $this->response->setStatusCode(404)->setBody(view('meeting_pwa/invalid'));
        if (!$this->canAccess($meeting)) {
            return $this->response->setStatusCode(403)->setBody(view('meeting_pwa/invalid', [
                'message' => 'You don\'t have access to this meeting. Only the owner or a manager can open it.',
            ]));
        }
        $db = \Config\Database::connect();
        $events = $db->table('meeting_events')->where('meeting_id', (int) $meeting['id'])
            ->orderBy('occurred_at', 'ASC')->get()->getResultArray();
        $owner = $db->table('users')->where('id', (int) $meeting['user_id'])->get()->getRowArray();
        $me    = $this->auth->user();
        return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('meeting_pwa/track', [
                'meeting' => $meeting,
                'owner'   => $owner,
                'events'  => $events,
                'token'   => $token,
                'appName' => env('tpt.appName', 'TPT Aggregator'),
                'me'      => $me,
                'isOwner' => (int) $meeting['user_id'] === (int) ($me['id'] ?? 0),
            ]));
    }

    public function punch(string $token, string $eventType)
    {
        $meeting = $this->meeting($token);
        if (!$meeting) return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'reason' => 'invalid']);
        if (!$this->canAccess($meeting)) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'reason' => 'forbidden']);

        $valid = ['depart_office','arrive_meeting','leave_meeting','return_office'];
        if (!in_array($eventType, $valid, true)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'reason' => 'bad_event']);
        }

        $body = json_decode((string) $this->request->getBody(), true) ?? [];
        $lat  = isset($body['lat']) ? (float) $body['lat'] : null;
        $lng  = isset($body['lng']) ? (float) $body['lng'] : null;
        $acc  = isset($body['acc']) ? (float) $body['acc'] : null;

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $tripId = (int) $meeting['id'];

        // Idempotent: if this event_type already exists, just return existing
        $existing = $db->table('meeting_events')
            ->where(['meeting_id' => $tripId, 'event_type' => $eventType])
            ->orderBy('id','DESC')->limit(1)->get()->getRowArray();
        if ($existing) {
            return $this->response->setJSON(['ok' => true, 'already' => true, 'event' => $existing]);
        }

        $db->table('meeting_events')->insert([
            'meeting_id'  => $tripId,
            'event_type'  => $eventType,
            'occurred_at' => $now,
            'latitude'    => $lat !== null && $lat >= -90 && $lat <= 90 ? round($lat, 7) : null,
            'longitude'   => $lng !== null && $lng >= -180 && $lng <= 180 ? round($lng, 7) : null,
            'accuracy_m'  => $acc !== null ? round($acc, 1) : null,
            'source'      => 'pwa',
            'created_at'  => $now,
        ]);

        // Auto-progress meeting status
        $statusUpd = null;
        if ($eventType === 'depart_office'   && $meeting['status'] === 'Planned')   $statusUpd = 'InProgress';
        if ($eventType === 'return_office'   && $meeting['status'] !== 'Cancelled') $statusUpd = 'Completed';
        if ($statusUpd) {
            $db->table('meetings')->where('id', $tripId)->update(['status' => $statusUpd, 'updated_at' => $now]);
        }

        // Return refreshed event list + status
        $events = $db->table('meeting_events')->where('meeting_id', $tripId)
            ->orderBy('occurred_at','ASC')->get()->getResultArray();
        $refresh = $db->table('meetings')->where('id', $tripId)->get()->getRowArray();
        return $this->response->setJSON(['ok' => true, 'status' => $refresh['status'], 'events' => $events]);
    }

    public function notes(string $token)
    {
        $meeting = $this->meeting($token);
        if (!$meeting) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        if (!$this->canAccess($meeting)) return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'reason' => 'forbidden']);
        $body = json_decode((string) $this->request->getBody(), true) ?? [];
        $outcome    = isset($body['outcome'])    ? trim((string) $body['outcome'])    : null;
        $next_steps = isset($body['next_steps']) ? trim((string) $body['next_steps']) : null;
        \Config\Database::connect()->table('meetings')->where('id', (int) $meeting['id'])->update([
            'outcome'    => $outcome,
            'next_steps' => $next_steps,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->response->setJSON(['ok' => true]);
    }
}
