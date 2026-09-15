<?php

namespace App\Controllers;

class AuditLogsController extends BaseController
{
    public function index()
    {
        $req    = $this->request;
        $search = trim((string) $req->getGet('q'));
        $module = (string) $req->getGet('module');
        $action = (string) $req->getGet('action');
        $user   = (int) $req->getGet('user');
        $from   = (string) $req->getGet('from');
        $to     = (string) $req->getGet('to');

        $db = \Config\Database::connect();
        $builder = $db->table('activity_logs')
            ->select('activity_logs.*, u.name AS user_name')
            ->join('users u', 'u.id = activity_logs.user_id', 'left')
            ->orderBy('activity_logs.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('activity_logs.action_description', $search)
                ->orLike('activity_logs.module_name', $search)
                ->orLike('activity_logs.action_type', $search)
                ->groupEnd();
        }
        if ($module !== '') $builder->where('activity_logs.module_name', $module);
        if ($action !== '') $builder->where('activity_logs.action_type',  $action);
        if ($user > 0)      $builder->where('activity_logs.user_id',      $user);
        if ($from !== '')   $builder->where('DATE(activity_logs.created_at) >=', $from);
        if ($to   !== '')   $builder->where('DATE(activity_logs.created_at) <=', $to);

        // Pagination (manual, since we're on the query-builder level not Model)
        $perPage = 40;
        $page    = max(1, (int) $req->getGet('page'));
        $total   = (clone $builder)->countAllResults(false);
        $rows    = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        $modules = array_column($db->table('activity_logs')->select('module_name')->distinct()->get()->getResultArray(), 'module_name');
        $actions = array_column($db->table('activity_logs')->select('action_type') ->distinct()->get()->getResultArray(), 'action_type');
        $users   = $db->table('users')->select('id, name')->orderBy('name')->get()->getResultArray();

        return $this->render('audit_logs/index', [
            'pageTitle' => 'Audit Logs [Administration] — List',
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'search'    => $search, 'module' => $module, 'action' => $action, 'user' => $user, 'from' => $from, 'to' => $to,
            'modules'   => array_values(array_filter($modules)),
            'actions'   => array_values(array_filter($actions)),
            'users'     => $users,
        ], retroFixedShell: true);
    }

    public function show(int $id)
    {
        $db  = \Config\Database::connect();
        $row = $db->table('activity_logs')
            ->select('activity_logs.*, u.name AS user_name')
            ->join('users u', 'u.id = activity_logs.user_id', 'left')
            ->where('activity_logs.id', $id)
            ->get()->getRowArray();
        if (!$row) return redirect()->to(site_url('audit-logs'))->with('error', 'Not found.');

        return $this->render('audit_logs/show', [
            'pageTitle' => 'Audit Logs [Administration]',
            'row'       => $row,
        ], retroFixedShell: true);
    }
}
