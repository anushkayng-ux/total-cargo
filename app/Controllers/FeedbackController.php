<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Public, token-gated trip-feedback collection.
 *
 *   GET  /feedback/{token}   — render the form (or thank-you if already submitted)
 *   POST /feedback/{token}   — save ratings + comments
 *
 * No auth filter: the unguessable 64-char token IS the credential.
 * A token is generated automatically when a trip moves to "Delivered"
 * (via NotificationService::onTripStatusChanged), and the client gets the
 * link in the `trip_feedback_request` email.
 */
class FeedbackController extends Controller
{
    protected $helpers = ['url', 'form'];

    private function context(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32,128}$/i', $token)) return null;
        $db = \Config\Database::connect();
        $fb = $db->table('trip_feedback')->where('feedback_token', $token)->get()->getRowArray();
        if (!$fb) return null;
        $trip = $db->table('trips t')
            ->select('t.id, t.trip_no, t.lr_no, t.current_status, t.driver_name, t.vehicle_number, t.delivery_datetime,
                      b.booking_no, b.route_text, b.client_id')
            ->join('bookings b', 'b.id = t.booking_id', 'left')
            ->where('t.id', (int) $fb['trip_id'])
            ->where('t.deleted_at', null)
            ->get()->getRowArray();
        if (!$trip) return null;
        $client = !empty($trip['client_id'])
            ? $db->table('clients')->where('id', (int) $trip['client_id'])->get()->getRowArray()
            : null;
        return ['fb' => $fb, 'trip' => $trip, 'client' => $client];
    }

    public function form(string $token)
    {
        $ctx = $this->context($token);
        if (!$ctx) return $this->response->setStatusCode(404)->setBody(view('feedback/invalid'));

        $expired = !empty($ctx['fb']['token_expires_at']) && strtotime($ctx['fb']['token_expires_at']) < time();
        $submitted = !empty($ctx['fb']['submitted_at']);

        return view('feedback/form', [
            'token'      => $token,
            'fb'         => $ctx['fb'],
            'trip'       => $ctx['trip'],
            'client'     => $ctx['client'],
            'expired'    => $expired,
            'submitted'  => $submitted,
            'company'    => (new \App\Models\SettingModel())->get('company_name', 'TPT Logistics') ?: 'TPT Logistics',
            'error'      => session()->getFlashdata('error'),
            'success'    => session()->getFlashdata('success'),
        ]);
    }

    public function submit(string $token)
    {
        $ctx = $this->context($token);
        if (!$ctx) return $this->response->setStatusCode(404)->setBody(view('feedback/invalid'));

        if (!empty($ctx['fb']['token_expires_at']) && strtotime($ctx['fb']['token_expires_at']) < time()) {
            return redirect()->to(site_url('feedback/' . $token))->with('error', 'This feedback link has expired. Please contact us if you would still like to share your experience.');
        }
        if (!empty($ctx['fb']['submitted_at'])) {
            return redirect()->to(site_url('feedback/' . $token))->with('error', 'Feedback already submitted for this trip. Thank you!');
        }

        $clamp = fn($v, $min, $max) => max($min, min($max, (int) $v));
        $overall = (int) $this->request->getPost('rating_overall');
        if ($overall < 1) {
            return redirect()->back()->withInput()->with('error', 'Please give an overall rating before submitting.');
        }

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $db->table('trip_feedback')->where('id', (int) $ctx['fb']['id'])->update([
            'rating_overall'         => $clamp($overall, 1, 5),
            'rating_on_time'         => $this->request->getPost('rating_on_time')         ? $clamp($this->request->getPost('rating_on_time'),         1, 5) : null,
            'rating_goods_condition' => $this->request->getPost('rating_goods_condition') ? $clamp($this->request->getPost('rating_goods_condition'), 1, 5) : null,
            'rating_driver'          => $this->request->getPost('rating_driver')          ? $clamp($this->request->getPost('rating_driver'),          1, 5) : null,
            'rating_communication'   => $this->request->getPost('rating_communication')   ? $clamp($this->request->getPost('rating_communication'),   1, 5) : null,
            'nps_score'              => $this->request->getPost('nps_score') !== null && $this->request->getPost('nps_score') !== ''
                                            ? $clamp($this->request->getPost('nps_score'), 0, 10) : null,
            'would_recommend'        => $this->request->getPost('would_recommend') !== null && $this->request->getPost('would_recommend') !== ''
                                            ? (int) (!empty($this->request->getPost('would_recommend'))) : null,
            'comments'               => trim((string) $this->request->getPost('comments')) ?: null,
            'submitter_name'         => trim((string) $this->request->getPost('submitter_name'))  ?: null,
            'submitter_email'        => trim((string) $this->request->getPost('submitter_email')) ?: null,
            'submitter_phone'        => trim((string) $this->request->getPost('submitter_phone')) ?: null,
            'submitted_at'           => $now,
            'ip_address'             => (string) ($this->request->getIPAddress() ?: ''),
            'updated_at'             => $now,
        ]);

        // Notify internal team
        try {
            $this->notifyStaff($ctx, $overall, trim((string) $this->request->getPost('comments')));
        } catch (\Throwable $e) {
            log_message('warning', 'Feedback notify failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('feedback/' . $token))->with('success', 'Thank you — your feedback has been recorded.');
    }

    private function notifyStaff(array $ctx, int $overall, string $comments): void
    {
        $setting = new \App\Models\SettingModel();
        $to = (string) $setting->get('company_email', '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;
        // Respect alert_rules
        $db  = \Config\Database::connect();
        if ($db->tableExists('alert_rules')) {
            $rule = $db->table('alert_rules')
                ->where('event_key', 'trip_feedback_submitted')
                ->where('audience',  'internal')
                ->where('channel',   'email')
                ->get()->getRowArray();
            if ($rule && (int) $rule['enabled'] === 0) return;
        }
        $stars = str_repeat('★', $overall) . str_repeat('☆', 5 - $overall);
        (new \App\Libraries\EmailService())->sendTemplate('trip_feedback_submitted', $to, [
            'trip_no'        => $ctx['trip']['trip_no'] ?? '',
            'client_company' => $ctx['client']['company_name'] ?? '',
            'stars'          => $stars,
            'overall'        => (string) $overall,
            'comments'       => $comments !== '' ? $comments : '(no comments)',
            'trip_link'      => site_url('trips/' . (int) $ctx['trip']['id']),
        ], [
            'related_module'    => 'trip',
            'related_id'        => (int) $ctx['trip']['id'],
            'related_client_id' => (int) ($ctx['client']['id'] ?? 0),
        ]);
    }
}
