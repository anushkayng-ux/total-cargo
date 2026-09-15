<?php

namespace App\Controllers;

class MeetingsController extends BaseController
{
    public function index()
    {
        $userId = (int) $this->auth->id();
        $isManager = $this->auth->can('meetings', 'can_approve');
        $scope = (string) ($this->request->getGet('scope') ?? 'mine');
        $db = \Config\Database::connect();
        $q = $db->table('meetings m')
            ->select('m.*, u.name AS owner_name,
                      (SELECT COUNT(*) FROM meeting_events e WHERE e.meeting_id = m.id) AS event_count')
            ->join('users u', 'u.id = m.user_id', 'left')
            ->orderBy('m.scheduled_at', 'DESC')
            ->limit(200);
        if ($scope !== 'team' || !$isManager) $q = $q->where('m.user_id', $userId);
        $rows = $q->get()->getResultArray();
        return $this->render('meetings/index', [
            'pageTitle' => 'Meetings [HRMS] — List',
            'rows'      => $rows,
            'isManager' => $isManager,
            'scope'     => $scope === 'team' && $isManager ? 'team' : 'mine',
        ], retroFixedShell: true);
    }

    public function create()
    {
        return $this->render('meetings/create', [
            'pageTitle' => 'Meetings [HRMS] — New',
        ], retroFixedShell: true);
    }

    public function store()
    {
        $userId = (int) $this->auth->id();
        $title  = trim((string) $this->request->getPost('title'));
        $when   = (string) $this->request->getPost('scheduled_at');
        if ($title === '' || $when === '') {
            return redirect()->back()->withInput()->with('error', 'Title and scheduled time are required.');
        }
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $db->table('meetings')->insert([
            'user_id'            => $userId,
            'title'              => $title,
            'with_company'       => trim((string) $this->request->getPost('with_company')) ?: null,
            'with_contact_name'  => trim((string) $this->request->getPost('with_contact_name')) ?: null,
            'with_contact_phone' => trim((string) $this->request->getPost('with_contact_phone')) ?: null,
            'location'           => trim((string) $this->request->getPost('location')) ?: null,
            'scheduled_at'       => date('Y-m-d H:i:s', strtotime($when)),
            'pwa_token'          => bin2hex(random_bytes(32)),
            'status'             => 'Planned',
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
        $id = (int) $db->insertID();
        return redirect()->to(site_url('meetings/' . $id))->with('success', 'Meeting planned. Open it on your phone via the PWA link.');
    }

    public function show(int $id)
    {
        $userId = (int) $this->auth->id();
        $isManager = $this->auth->can('meetings', 'can_approve');
        $db  = \Config\Database::connect();
        $row = $db->table('meetings')->where('id', $id)->get()->getRowArray();
        if (!$row) return redirect()->to(site_url('meetings'))->with('error', 'Not found.');
        if ($row['user_id'] != $userId && !$isManager) {
            return redirect()->to(site_url('meetings'))->with('error', 'Not allowed.');
        }
        $events = $db->table('meeting_events')->where('meeting_id', $id)->orderBy('occurred_at', 'ASC')->get()->getResultArray();
        $owner  = $db->table('users')->where('id', (int) $row['user_id'])->get()->getRowArray();
        return $this->render('meetings/show', [
            'pageTitle' => 'Meetings [HRMS]',
            'row'       => $row,
            'events'    => $events,
            'owner'     => $owner,
            'pwaUrl'    => $row['pwa_token'] ? site_url('m/' . $row['pwa_token']) : null,
        ], retroFixedShell: true);
    }

    public function update(int $id)
    {
        $userId = (int) $this->auth->id();
        $db  = \Config\Database::connect();
        $row = $db->table('meetings')->where('id', $id)->get()->getRowArray();
        if (!$row) return redirect()->to(site_url('meetings'))->with('error', 'Not found.');
        if ($row['user_id'] != $userId && !$this->auth->can('meetings', 'can_approve')) {
            return redirect()->to(site_url('meetings/' . $id))->with('error', 'Not allowed.');
        }
        $upd = [
            'outcome'    => trim((string) $this->request->getPost('outcome')) ?: null,
            'next_steps' => trim((string) $this->request->getPost('next_steps')) ?: null,
            'status'     => in_array((string) $this->request->getPost('status'), ['Planned','InProgress','Completed','Cancelled'], true) ? (string) $this->request->getPost('status') : $row['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db->table('meetings')->where('id', $id)->update($upd);
        return redirect()->to(site_url('meetings/' . $id))->with('success', 'Meeting updated.');
    }
}
