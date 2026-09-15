<?php

namespace App\Controllers;

use App\Models\SupportTicketModel;
use App\Models\SupportTicketReplyModel;
use App\Models\SettingModel;
use App\Models\UserModel;

class SupportController extends BaseController
{
    private function ratingEnabled(): bool
    {
        return (string) ((new SettingModel())->get('support_rating_enabled', '1') ?? '1') === '1';
    }

    /** Visible to: my own tickets always; if I have support.can_approve I see all. */
    public function index()
    {
        $req     = $this->request;
        $status  = (string) $req->getGet('status');
        $type    = (string) $req->getGet('type');
        $search  = trim((string) $req->getGet('q'));
        $mine    = (string) $req->getGet('mine');
        $isAgent = $this->auth->can('support', 'can_approve');

        $model = new SupportTicketModel();
        $q = $model->withJoins()
            ->where('support_tickets.deleted_at', null)
            ->orderBy('support_tickets.id', 'DESC');

        if ($mine === '1' || !$isAgent) {
            $q->where('support_tickets.user_id', $this->auth->id());
        }
        if ($status !== '' && in_array($status, SupportTicketModel::STATUSES, true)) {
            $q->where('support_tickets.status', $status);
        }
        if ($type !== '' && in_array($type, SupportTicketModel::TYPES, true)) {
            $q->where('support_tickets.type', $type);
        }
        if ($search !== '') {
            $q->groupStart()
                ->like('support_tickets.subject', $search)
                ->orLike('support_tickets.ticket_no', $search)
                ->groupEnd();
        }

        return $this->render('support/index', [
            'pageTitle'      => 'Support Tickets [Administration] — List',
            'rows'           => $q->paginate(25),
            'pager'          => $model->pager,
            'isAgent'        => $isAgent,
            'status'         => $status,
            'type'           => $type,
            'search'         => $search,
            'mine'           => $mine,
            'ratingEnabled'  => $this->ratingEnabled(),
            'ratingsSummary' => $isAgent ? $model->ratingsSummary() : null,
        ], retroFixedShell: true);
    }

    public function create()
    {
        return $this->render('support/form', [
            'pageTitle' => 'Support Tickets [Administration] — New',
        ], retroFixedShell: true);
    }

    public function store()
    {
        $rules = [
            'type'     => 'required|in_list[Bug,Improvement,Question,Other]',
            'priority' => 'permit_empty|in_list[Low,Normal,High,Urgent]',
            'subject'  => 'required|min_length[4]|max_length[200]',
            'body'     => 'required|min_length[10]|max_length[5000]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = new SupportTicketModel();
        $id = $model->insert([
            'ticket_no' => $model->nextTicketNo(),
            'user_id'   => $this->auth->id(),
            'type'      => (string) $this->request->getPost('type'),
            'priority'  => $this->request->getPost('priority') ?: 'Normal',
            'subject'   => trim((string) $this->request->getPost('subject')),
            'body'      => trim((string) $this->request->getPost('body')),
            'screen_url'=> $this->request->getPost('screen_url') ?: null,
            'status'    => 'Open',
        ]);
        return redirect()->to(site_url('support/' . $id))->with('success', 'Ticket raised. Our team will respond shortly.');
    }

    public function show(int $id)
    {
        $model = new SupportTicketModel();
        $row   = $model->withJoins()->where('support_tickets.id', $id)->first();
        if (!$row) return redirect()->to(site_url('support'))->with('error', 'Ticket not found.');

        // Reporter or agent can view
        $isAgent = $this->auth->can('support', 'can_approve');
        if (!$isAgent && (int) $row['user_id'] !== (int) $this->auth->id()) {
            return redirect()->to(site_url('support'))->with('error', 'Ticket not found.');
        }

        // Agents (assignable users)
        $agents = [];
        if ($isAgent) {
            $agents = (new UserModel())->where('status', 1)->where('deleted_at', null)
                ->orderBy('name')->find();
        }

        return $this->render('support/show', [
            'pageTitle'     => 'Support Tickets [Administration] — ' . $row['ticket_no'],
            'row'           => $row,
            'replies'       => (new SupportTicketReplyModel())->thread($id),
            'isAgent'       => $isAgent,
            'agents'        => $agents,
            'ratingEnabled' => $this->ratingEnabled(),
        ], retroFixedShell: true);
    }

    public function reply(int $id)
    {
        $row = (new SupportTicketModel())->find($id);
        if (!$row) return redirect()->to(site_url('support'))->with('error', 'Not found.');

        $isAgent = $this->auth->can('support', 'can_approve');
        if (!$isAgent && (int) $row['user_id'] !== (int) $this->auth->id()) {
            return redirect()->to(site_url('support'))->with('error', 'Ticket not found.');
        }

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '') return redirect()->back()->with('error', 'Reply body required.');

        (new SupportTicketReplyModel())->insert([
            'ticket_id'   => $id,
            'user_id'     => $this->auth->id(),
            'body'        => $body,
            'is_internal' => $isAgent && $this->request->getPost('is_internal') ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        // Auto-progress: a customer reply on a Resolved ticket reopens it
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if (!$isAgent && $row['status'] === 'Resolved') {
            $update['status'] = 'Reopened';
        } elseif ($isAgent && $row['status'] === 'Open') {
            $update['status'] = 'In Progress';
        }
        (new SupportTicketModel())->update($id, $update);

        return redirect()->to(site_url('support/' . $id))->with('success', 'Reply posted.');
    }

    /** Agents can change status / assignee. */
    public function update(int $id)
    {
        if (!$this->auth->can('support', 'can_approve')) {
            return redirect()->to(site_url('support'))->with('error', 'Not allowed.');
        }
        $row = (new SupportTicketModel())->find($id);
        if (!$row) return redirect()->to(site_url('support'))->with('error', 'Not found.');

        $action = (string) $this->request->getPost('action');
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        $now = date('Y-m-d H:i:s');

        switch ($action) {
            case 'assign':
                $update['assigned_to'] = $this->request->getPost('assigned_to') ?: null;
                if ($row['status'] === 'Open') $update['status'] = 'In Progress';
                $msg = 'Ticket assigned.';
                break;
            case 'status':
                $newSt = (string) $this->request->getPost('status');
                if (!in_array($newSt, SupportTicketModel::STATUSES, true)) {
                    return redirect()->back()->with('error', 'Invalid status.');
                }
                $update['status'] = $newSt;
                if ($newSt === 'Closed' && empty($row['closed_at']))    $update['closed_at']    = $now;
                if ($newSt === 'Resolved' && empty($row['resolved_at'])) {
                    $update['resolved_at'] = $now;
                    $update['resolved_by'] = $this->auth->id();
                }
                $msg = 'Status set to ' . $newSt . '.';
                break;
            case 'resolve':
                $update['status']      = 'Resolved';
                $update['resolution']  = trim((string) $this->request->getPost('resolution'));
                $update['resolved_at'] = $now;
                $update['resolved_by'] = $this->auth->id();
                $msg = 'Ticket marked Resolved.';
                break;
            case 'close':
                $update['status']    = 'Closed';
                $update['closed_at'] = $now;
                $msg = 'Ticket closed.';
                break;
            case 'reopen':
                $update['status']      = 'Reopened';
                $update['resolved_at'] = null;
                $update['closed_at']   = null;
                $msg = 'Ticket reopened.';
                break;
            default:
                return redirect()->back()->with('error', 'Unknown action.');
        }
        (new SupportTicketModel())->update($id, $update);
        return redirect()->to(site_url('support/' . $id))->with('success', $msg);
    }

    /** Reporter rates a Resolved/Closed ticket — only if rating is enabled. */
    public function rate(int $id)
    {
        if (!$this->ratingEnabled()) {
            return redirect()->back()->with('error', 'Rating is disabled by your administrator.');
        }
        $row = (new SupportTicketModel())->find($id);
        if (!$row) return redirect()->to(site_url('support'))->with('error', 'Not found.');
        if ((int) $row['user_id'] !== (int) $this->auth->id()) {
            return redirect()->to(site_url('support'))->with('error', 'Only the reporter can rate.');
        }
        if (!in_array($row['status'], ['Resolved','Closed'], true)) {
            return redirect()->back()->with('error', 'Rate only after the ticket is resolved.');
        }
        $rating = (int) $this->request->getPost('rating');
        if ($rating < 1 || $rating > 5) return redirect()->back()->with('error', 'Pick 1–5 stars.');

        (new SupportTicketModel())->update($id, [
            'rating'         => $rating,
            'rating_comment' => trim((string) $this->request->getPost('rating_comment')) ?: null,
            'rated_at'       => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to(site_url('support/' . $id))->with('success', 'Thanks for the feedback!');
    }
}
