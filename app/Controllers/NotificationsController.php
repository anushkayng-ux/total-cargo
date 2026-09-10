<?php

namespace App\Controllers;

class NotificationsController extends BaseController
{
    /**
     * GET /_notifications/counts → JSON of unread counters the topbar bell uses.
     *
     *   {
     *     "total": 7,
     *     "items": [
     *       {"key":"driver_chat",     "n":3, "label":"Driver messages",    "url":"/trips?driver_unread=1"},
     *       {"key":"leave_approvals", "n":2, "label":"Pending leaves",     "url":"/hrms/approvals"},
     *       {"key":"feedback",        "n":1, "label":"New client feedback","url":"/trips?feedback_new=1"},
     *       {"key":"support",         "n":1, "label":"Open support tickets","url":"/support"}
     *     ]
     *   }
     */
    public function counts()
    {
        $db = \Config\Database::connect();
        $isMgr = $this->auth->can('hrms', 'can_approve');
        $myId  = (int) ($this->auth->id() ?? 0);

        $items = [];

        // Personal in-app notifications (targeted, event-driven) — shown first.
        if ($myId > 0 && $db->tableExists('notifications')) {
            $nm     = new \App\Models\NotificationModel();
            $unread = $nm->unreadRecent($myId, 8);
            foreach ($unread as $row) {
                $items[] = [
                    'key'   => 'notif_' . $row['id'],
                    'n'     => 1,
                    'label' => $row['title'],
                    'url'   => site_url('notifications/' . $row['id'] . '/read'),
                    'icon'  => $row['icon'] ?: 'bell',
                ];
            }
        }

        // Driver messages where the staff side hasn't yet read the driver bubble
        $n = (int) $db->table('driver_messages')
            ->where('direction', 'driver')
            ->where('is_read_by_staff', 0)
            ->countAllResults();
        if ($n > 0) {
            $items[] = ['key' => 'driver_chat', 'n' => $n, 'label' => 'Driver messages',
                        'url' => site_url('trips'), 'icon' => 'chat-dots-fill'];
        }

        // Pending leaves — only for users with approval permission
        if ($isMgr) {
            $n = (int) $db->table('leaves')->where('status', 'Pending')->countAllResults();
            if ($n > 0) {
                $items[] = ['key' => 'leave_approvals', 'n' => $n, 'label' => 'Pending leave approvals',
                            'url' => site_url('hrms/approvals'), 'icon' => 'clipboard-check'];
            }
        }

        // New client feedback submitted in last 7 days
        $n = (int) $db->table('trip_feedback')
            ->where('submitted_at IS NOT NULL', null, false)
            ->where('submitted_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->countAllResults();
        if ($n > 0) {
            $items[] = ['key' => 'feedback', 'n' => $n, 'label' => 'Recent client feedback',
                        'url' => site_url('trips'), 'icon' => 'star-fill'];
        }

        // Open support tickets (assigned to me or unassigned)
        if ($db->tableExists('support_tickets')) {
            $myId = (int) ($this->auth->id() ?? 0);
            $q = $db->table('support_tickets')->whereIn('status', ['Open','In Progress','Re-Opened']);
            $n = (int) $q->countAllResults();
            if ($n > 0) {
                $items[] = ['key' => 'support', 'n' => $n, 'label' => 'Open support tickets',
                            'url' => site_url('support'), 'icon' => 'life-preserver'];
            }
        }

        // Driver track tokens with no recent ping (drivers may have closed the page)
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $n = (int) $db->table('trips')
            ->where('driver_track_token IS NOT NULL', null, false)
            ->whereNotIn('current_status', ['Closed','Cancelled'])
            ->groupStart()
                ->where('driver_track_last_ping IS NULL', null, false)
                ->orWhere('driver_track_last_ping <', $cutoff)
            ->groupEnd()
            ->countAllResults();
        if ($n > 0) {
            $items[] = ['key' => 'gps_stale', 'n' => $n, 'label' => 'Trips with stale GPS',
                        'url' => site_url('gps'), 'icon' => 'broadcast'];
        }

        $total = 0;
        foreach ($items as $i) $total += $i['n'];

        return $this->response->setJSON([
            'ok'    => true,
            'total' => $total,
            'items' => $items,
        ]);
    }

    /** GET /notifications — full history for the current user. */
    public function index()
    {
        $myId = (int) ($this->auth->id() ?? 0);
        $nm   = new \App\Models\NotificationModel();

        return $this->render('notifications/index', [
            'pageTitle' => 'Notifications',
            'rows'      => $nm->forUser($myId)->paginate(30),
            'pager'     => $nm->pager,
            'unread'    => $nm->unreadCount($myId),
        ]);
    }

    /** GET /notifications/:id/read — mark one read, then jump to its target. */
    public function read(int $id)
    {
        $myId = (int) ($this->auth->id() ?? 0);
        $nm   = new \App\Models\NotificationModel();
        $row  = $nm->where('id', $id)->where('user_id', $myId)->first();
        if (!$row) return redirect()->to(site_url('notifications'));

        $nm->markRead($id, $myId);
        return redirect()->to($row['link'] ?: site_url('notifications'));
    }

    /** POST /notifications/read-all — clear the badge. */
    public function readAll()
    {
        $myId = (int) ($this->auth->id() ?? 0);
        (new \App\Models\NotificationModel())->markAllRead($myId);
        return redirect()->to(site_url('notifications'))->with('success', 'All notifications marked read.');
    }

    /**
     * GET /notifications/test — pipeline self-test.
     * Verifies the table exists and pushes a notification to the current user
     * (actor-filter disabled so it definitely reaches you). If you see it on the
     * notifications page + bell, the whole system is wired correctly.
     */
    public function test()
    {
        $myId = (int) ($this->auth->id() ?? 0);
        $db   = \Config\Database::connect();

        if (!$db->tableExists('notifications')) {
            return redirect()->to(site_url('notifications'))
                ->with('error', 'FAIL: the `notifications` table does not exist on this server. Run the CREATE TABLE SQL in phpMyAdmin.');
        }

        $n = \App\Libraries\Notify::toUser($myId,
            'Test notification ✓',
            'If you can see this, notifications are working. Created at ' . date('H:i:s') . '.',
            site_url('notifications'),
            ['type' => 'test', 'icon' => 'bell-fill', 'actor_id' => 0]);

        return redirect()->to(site_url('notifications'))
            ->with('success', $n > 0
                ? 'OK: test notification created. You should see it below and in the bell.'
                : 'WARN: table exists but no row was inserted — check writable/logs for "Notify failed".');
    }
}
