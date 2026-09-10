<?php

namespace App\Controllers;

use App\Models\RecordCommentModel;
use App\Models\BookingModel;
use App\Models\TripModel;
use App\Models\LeadModel;

/**
 * Internal staff team-chat — comments threaded on bookings, trips, leads.
 * Shown only to staff (auth filter); never exposed via the client portal.
 */
class CommentsController extends BaseController
{
    public function store(string $type, int $id)
    {
        if (!in_array($type, RecordCommentModel::TYPES, true)) {
            return redirect()->back()->with('error', 'Invalid thread.');
        }
        // Verify the parent record exists (defense-in-depth)
        if (!$this->parentExists($type, $id)) {
            return redirect()->back()->with('error', 'Parent record not found.');
        }

        $body = trim((string) $this->request->getPost('body'));
        if ($body === '' || mb_strlen($body) > 4000) {
            return redirect()->back()->with('error', 'Comment must be 1–4000 characters.');
        }

        // Parse @mentions of the form @username (matches users.name lowercased)
        $mentions = [];
        if (preg_match_all('/@([\w.-]{2,30})/', $body, $m)) {
            $names = array_unique(array_map('strtolower', $m[1]));
            $rows = \Config\Database::connect()->table('users')
                ->select('id, name')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()->getResultArray();
            foreach ($rows as $u) {
                $token = strtolower(preg_replace('/\s+/', '.', $u['name']));
                if (in_array($token, $names, true)) $mentions[] = (int) $u['id'];
            }
        }

        (new RecordCommentModel())->insert([
            'record_type'   => $type,
            'record_id'     => $id,
            'user_id'       => $this->auth->id(),
            'body'          => $body,
            'mentions_json' => $mentions ? json_encode($mentions) : null,
        ]);

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function delete(string $type, int $id, int $commentId)
    {
        $model = new RecordCommentModel();
        $c = $model->find($commentId);
        if ($c && $c['record_type'] === $type && (int) $c['record_id'] === $id) {
            $isAuthor = (int) $c['user_id'] === (int) $this->auth->id();
            // Authors delete their own; users with comments.can_delete (e.g. admin)
            // can moderate any comment.
            if ($isAuthor || $this->auth->can('comments', 'can_delete')) {
                $model->delete($commentId);
            }
        }
        return redirect()->back()->with('success', 'Comment removed.');
    }

    private function parentExists(string $type, int $id): bool
    {
        return match ($type) {
            'booking' => (bool) (new BookingModel())->find($id),
            'trip'    => (bool) (new TripModel())->find($id),
            'lead'    => (bool) (new LeadModel())->find($id),
            default   => false,
        };
    }
}
