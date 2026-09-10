<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;

class RolesController extends BaseController
{
    public function index()
    {
        $roles = (new RoleModel())->orderBy('role_name')->findAll();
        return $this->render('roles/index', [
            'pageTitle' => 'Roles',
            'roles'     => $roles,
        ]);
    }

    public function create()
    {
        return $this->render('roles/form', [
            'pageTitle' => 'Add Role',
            'role'      => null,
        ]);
    }

    public function store()
    {
        $rules = [
            'role_name' => 'required|min_length[2]|max_length[80]',
            'role_key'  => 'permit_empty|max_length[60]|is_unique[roles.role_key]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $model = new RoleModel();
        $newId = $model->insert([
            'role_name' => $this->request->getPost('role_name'),
            'role_key'  => $this->request->getPost('role_key') ?: null,
            'status'    => $this->request->getPost('status') ? 1 : 0,
        ]);
        if (!$newId) {
            return redirect()->back()->withInput()->with('error',
                'Create failed: ' . implode(' ', $model->errors() ?: ['unknown DB error']));
        }
        return redirect()->to(site_url('roles'))->with('success', 'Role created.');
    }

    public function edit(int $id)
    {
        $role = (new RoleModel())->find($id);
        if (!$role) return redirect()->to(site_url('roles'))->with('error', 'Role not found.');
        return $this->render('roles/form', [
            'pageTitle' => 'Edit Role',
            'role'      => $role,
        ]);
    }

    public function update(int $id)
    {
        $rules = [
            'role_name' => 'required|min_length[2]|max_length[80]',
            'role_key'  => "permit_empty|max_length[60]|is_unique[roles.role_key,id,$id]",
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $model = new RoleModel();
        $ok = $model->update($id, [
            'role_name' => $this->request->getPost('role_name'),
            'role_key'  => $this->request->getPost('role_key') ?: null,
            'status'    => $this->request->getPost('status') ? 1 : 0,
        ]);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error',
                'Save failed: ' . implode(' ', $model->errors() ?: ['unknown DB error']));
        }
        return redirect()->to(site_url('roles'))->with('success', 'Role updated.');
    }

    public function permissions(int $id)
    {
        $role = (new RoleModel())->find($id);
        if (!$role) return redirect()->to(site_url('roles'))->with('error', 'Role not found.');

        $perms = (new PermissionModel())->orderBy('module_key')->orderBy('action_key')->findAll();
        $current = (new RolePermissionModel())->where('role_id', $id)->findAll();
        $map = [];
        foreach ($current as $c) {
            $map[(int) $c['permission_id']] = $c;
        }
        return $this->render('roles/permissions', [
            'pageTitle'   => 'Permissions · ' . $role['role_name'],
            'role'        => $role,
            'permissions' => $perms,
            'map'         => $map,
        ]);
    }

    public function savePermissions(int $id)
    {
        $role = (new RoleModel())->find($id);
        if (!$role) return redirect()->to(site_url('roles'))->with('error', 'Role not found.');

        // Capture the old permission set so the audit log records the diff
        $db = \Config\Database::connect();
        $before = $db->table('role_permissions')->where('role_id', $id)->get()->getResultArray();

        $posted = $this->request->getPost('p') ?? [];
        $rp = new RolePermissionModel();
        $db->table('role_permissions')->where('role_id', $id)->delete();

        $afterRows = [];
        foreach ($posted as $permissionId => $flags) {
            $permissionId = (int) $permissionId;
            if ($permissionId <= 0) continue;
            $rec = [
                'role_id'       => $id,
                'permission_id' => $permissionId,
                'can_view'      => !empty($flags['can_view'])    ? 1 : 0,
                'can_add'       => !empty($flags['can_add'])     ? 1 : 0,
                'can_edit'      => !empty($flags['can_edit'])    ? 1 : 0,
                'can_delete'    => !empty($flags['can_delete'])  ? 1 : 0,
                'can_approve'   => !empty($flags['can_approve']) ? 1 : 0,
                'can_export'    => !empty($flags['can_export'])  ? 1 : 0,
            ];
            $rp->insert($rec);
            $afterRows[] = $rec;
        }

        if ((int) $this->auth->user()['role_id'] === $id) {
            $this->auth->refreshPermissions();
        }

        \App\Libraries\AuditLogger::log('roles', $id, 'permissions_updated',
            "Role '" . ($role['role_name'] ?? '') . "' permissions changed",
            ['rows' => $before], ['rows' => $afterRows]);

        return redirect()->to(site_url('roles/' . $id . '/permissions'))->with('success', 'Permissions updated.');
    }
}
