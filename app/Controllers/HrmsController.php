<?php

namespace App\Controllers;

class HrmsController extends BaseController
{
    /* ─────────────────── Self-service ─────────────────── */

    public function myProfile()
    {
        $userId = (int) $this->auth->id();
        $db = \Config\Database::connect();
        $user    = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $profile = $db->table('employee_profiles')->where('user_id', $userId)->get()->getRowArray() ?: [];
        $profile = \App\Libraries\PiiCrypto::decryptRow($profile, \App\Libraries\PiiCrypto::EMPLOYEE_PROFILE);
        $kin     = $db->table('employee_next_of_kin')
            ->where('user_id', $userId)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $this->render('hrms/my_profile', [
            'pageTitle' => 'My Profile',
            'user'      => $user,
            'profile'   => $profile,
            'kin'       => $kin,
        ]);
    }

    public function saveProfile()
    {
        $userId = (int) $this->auth->id();
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Capture fields the employee is allowed to edit themselves
        $editable = [
            'date_of_birth','gender','marital_status','blood_group',
            'permanent_address','current_address','emergency_contact_name','emergency_contact_phone',
            'pan_no','aadhaar_last_4','uan_no','bank_name','bank_account_no','bank_ifsc',
        ];
        $data = ['updated_at' => $now];
        foreach ($editable as $f) {
            $v = $this->request->getPost($f);
            if ($v !== null) $data[$f] = trim((string) $v);
        }
        // Mark profile_completed once the basics are set
        $required = ['date_of_birth','permanent_address','emergency_contact_name','emergency_contact_phone'];
        $allSet = true;
        foreach ($required as $f) if (empty($data[$f])) $allSet = false;
        $data['profile_completed'] = $allSet ? 1 : 0;

        // Encrypt PII fields at rest
        $data = \App\Libraries\PiiCrypto::encryptRow($data, \App\Libraries\PiiCrypto::EMPLOYEE_PROFILE);

        $existing = $db->table('employee_profiles')->where('user_id', $userId)->get()->getRowArray();
        if ($existing) {
            $db->table('employee_profiles')->where('id', (int) $existing['id'])->update($data);
        } else {
            $data['user_id']    = $userId;
            $data['created_at'] = $now;
            $db->table('employee_profiles')->insert($data);
        }

        $this->saveKin($userId);

        return redirect()->to(site_url('hrms/profile'))->with('success', 'Profile saved.');
    }

    /**
     * Replace the user's next-of-kin list with whatever is in POST['kin'][].
     * Empty rows (no name + no phone) are pruned. Idempotent.
     */
    private function saveKin(int $userId): void
    {
        $rel   = (array) ($this->request->getPost('kin_relation')   ?? []);
        $name  = (array) ($this->request->getPost('kin_name')       ?? []);
        $phone = (array) ($this->request->getPost('kin_phone')      ?? []);
        $alt   = (array) ($this->request->getPost('kin_alt_phone')  ?? []);
        $email = (array) ($this->request->getPost('kin_email')      ?? []);
        $addr  = (array) ($this->request->getPost('kin_address')    ?? []);
        $emerg = (array) ($this->request->getPost('kin_is_emergency') ?? []);

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $db->table('employee_next_of_kin')->where('user_id', $userId)->delete();

        $sort = 0;
        foreach ($name as $i => $n) {
            $n = trim((string) $n);
            $p = trim((string) ($phone[$i] ?? ''));
            $r = trim((string) ($rel[$i] ?? ''));
            // Skip empty rows
            if ($n === '' && $p === '') continue;
            if ($n === '' || $p === '' || $r === '') continue; // need at least name+phone+relation
            $db->table('employee_next_of_kin')->insert([
                'user_id'              => $userId,
                'relation'             => mb_substr($r, 0, 40),
                'name'                 => mb_substr($n, 0, 150),
                'phone'                => mb_substr($p, 0, 30),
                'alt_phone'            => trim((string) ($alt[$i]   ?? '')) ?: null,
                'email'                => trim((string) ($email[$i] ?? '')) ?: null,
                'address'              => trim((string) ($addr[$i]  ?? '')) ?: null,
                'is_emergency_contact' => !empty($emerg[$i]) ? 1 : 0,
                'sort_order'           => $sort++,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }

    public function myAttendance()
    {
        $userId = (int) $this->auth->id();
        $year   = (int) ($this->request->getGet('y') ?? date('Y'));
        $month  = (int) ($this->request->getGet('m') ?? date('n'));
        if ($month < 1 || $month > 12) $month = (int) date('n');

        $db = \Config\Database::connect();
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));

        $rows = $db->table('attendance')
            ->where('user_id', $userId)
            ->where('attendance_date >=', $from)
            ->where('attendance_date <=', $to)
            ->orderBy('attendance_date', 'ASC')
            ->get()->getResultArray();

        $today = $db->table('attendance')
            ->where('user_id', $userId)->where('attendance_date', date('Y-m-d'))
            ->get()->getRowArray();

        return $this->render('hrms/my_attendance', [
            'pageTitle'  => 'My Attendance',
            'rows'       => $rows,
            'today'      => $today,
            'year'       => $year,
            'month'      => $month,
            'from'       => $from,
            'to'         => $to,
        ]);
    }

    public function punchIn()
    {
        $userId = (int) $this->auth->id();
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        $lat   = $this->request->getPost('lat');
        $lng   = $this->request->getPost('lng');

        $db = \Config\Database::connect();
        $row = $db->table('attendance')->where(['user_id' => $userId, 'attendance_date' => $today])->get()->getRowArray();

        if ($row && !empty($row['punch_in_at'])) {
            return redirect()->to(site_url('hrms/attendance'))->with('error', 'Already punched in today at ' . esc($row['punch_in_at']) . '.');
        }
        $data = [
            'punch_in_at'     => $now,
            'punch_in_lat'    => is_numeric($lat) ? round((float) $lat, 7) : null,
            'punch_in_lng'    => is_numeric($lng) ? round((float) $lng, 7) : null,
            'punch_in_source' => 'web',
            'status'          => 'Present',
            'updated_at'      => $now,
        ];
        if ($row) {
            $db->table('attendance')->where('id', (int) $row['id'])->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['attendance_date'] = $today;
            $data['created_at'] = $now;
            $db->table('attendance')->insert($data);
        }
        return redirect()->to(site_url('hrms/attendance'))->with('success', 'Punched in at ' . date('H:i') . '.');
    }

    public function punchOut()
    {
        $userId = (int) $this->auth->id();
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        $lat   = $this->request->getPost('lat');
        $lng   = $this->request->getPost('lng');

        $db = \Config\Database::connect();
        $row = $db->table('attendance')->where(['user_id' => $userId, 'attendance_date' => $today])->get()->getRowArray();
        if (!$row || empty($row['punch_in_at'])) {
            return redirect()->to(site_url('hrms/attendance'))->with('error', 'Punch in first.');
        }
        $hours = round((strtotime($now) - strtotime((string) $row['punch_in_at'])) / 3600, 2);
        $db->table('attendance')->where('id', (int) $row['id'])->update([
            'punch_out_at'  => $now,
            'punch_out_lat' => is_numeric($lat) ? round((float) $lat, 7) : null,
            'punch_out_lng' => is_numeric($lng) ? round((float) $lng, 7) : null,
            'hours_worked'  => $hours,
            'status'        => $hours < 4 ? 'HalfDay' : 'Present',
            'updated_at'    => $now,
        ]);
        return redirect()->to(site_url('hrms/attendance'))->with('success', 'Punched out at ' . date('H:i') . ' (' . $hours . ' h worked).');
    }

    public function myLeaves()
    {
        $userId = (int) $this->auth->id();
        $db = \Config\Database::connect();
        $rows = $db->table('leaves l')
            ->select('l.*, lt.code AS type_code, lt.name AS type_name')
            ->join('leave_types lt', 'lt.id = l.leave_type_id', 'left')
            ->where('l.user_id', $userId)
            ->orderBy('l.id', 'DESC')->limit(100)
            ->get()->getResultArray();
        $balances = $db->table('leave_balances lb')
            ->select('lb.*, lt.code, lt.name')
            ->join('leave_types lt', 'lt.id = lb.leave_type_id', 'left')
            ->where('lb.user_id', $userId)
            ->where('lb.year', (int) date('Y'))
            ->orderBy('lt.id', 'ASC')->get()->getResultArray();
        return $this->render('hrms/my_leaves', [
            'pageTitle' => 'My Leaves',
            'leaves'    => $rows,
            'balances'  => $balances,
        ]);
    }

    public function applyLeaveForm()
    {
        $types = \Config\Database::connect()
            ->table('leave_types')->where('status', 1)->orderBy('id', 'ASC')->get()->getResultArray();
        return $this->render('hrms/apply_leave', [
            'pageTitle' => 'Apply for Leave',
            'types'     => $types,
        ]);
    }

    public function applyLeave()
    {
        $userId = (int) $this->auth->id();
        $typeId = (int) $this->request->getPost('leave_type_id');
        $from   = (string) $this->request->getPost('from_date');
        $to     = (string) $this->request->getPost('to_date');
        $reason = trim((string) $this->request->getPost('reason'));
        $half   = !empty($this->request->getPost('is_half_day')) ? 1 : 0;

        if (!$typeId || !$from || !$to) {
            return redirect()->back()->with('error', 'All required fields must be filled.');
        }
        if (strtotime($to) < strtotime($from)) {
            return redirect()->back()->with('error', 'End date is before start date.');
        }
        $days = $half ? 0.5 : (round((strtotime($to) - strtotime($from)) / 86400) + 1);

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $db->table('leaves')->insert([
            'user_id'       => $userId,
            'leave_type_id' => $typeId,
            'from_date'     => date('Y-m-d', strtotime($from)),
            'to_date'       => date('Y-m-d', strtotime($to)),
            'days'          => $days,
            'is_half_day'   => $half,
            'reason'        => $reason ?: null,
            'status'        => 'Pending',
            'applied_at'    => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // Notify HR / managers
        try {
            $type = $db->table('leave_types')->where('id', $typeId)->get()->getRowArray();
            $emp  = $db->table('users')->where('id', $userId)->get()->getRowArray();
            $to_  = (string) (new \App\Models\SettingModel())->get('company_email', '');
            if ($to_ !== '' && filter_var($to_, FILTER_VALIDATE_EMAIL)) {
                if ($this->alertEnabled('leave_applied', 'internal', 'email')) {
                    (new \App\Libraries\EmailService())->sendTemplate('leave_applied', $to_, [
                        'employee_name' => $emp['name']     ?? 'An employee',
                        'leave_type'    => $type['name']    ?? '—',
                        'from_date'     => date('d M Y', strtotime((string) $from)),
                        'to_date'       => date('d M Y', strtotime((string) $to)),
                        'days'          => (string) $days,
                        'plural'        => $days != 1 ? 's' : '',
                        'reason'        => $reason ?: '—',
                        'approve_link'  => site_url('hrms/approvals'),
                    ], ['related_module' => 'hrms', 'related_id' => $userId]);
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'leave_applied email failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('hrms/leaves'))->with('success', 'Leave application submitted (' . $days . ' day' . ($days != 1 ? 's' : '') . ').');
    }

    /** Check the alert_rules matrix; default to enabled when no row. */
    private function alertEnabled(string $eventKey, string $audience, string $channel = 'email'): bool
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('alert_rules')) return true;
        $row = $db->table('alert_rules')
            ->where('event_key', $eventKey)->where('audience', $audience)->where('channel', $channel)
            ->get()->getRowArray();
        if (!$row) return true;
        return (int) ($row['enabled'] ?? 1) === 1;
    }

    /* ─────────────────── Admin / approver ─────────────────── */

    public function team()
    {
        if (!$this->auth->can('hrms', 'can_approve')) {
            return redirect()->to(site_url('hrms/profile'))->with('error', 'You don\'t have access to the team view.');
        }
        $db = \Config\Database::connect();
        $rows = $db->table('users u')
            ->select('u.id, u.name, u.email, u.mobile, r.role_name, ep.employee_code, ep.designation, ep.department, ep.profile_completed, ep.date_of_joining')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('employee_profiles ep', 'ep.user_id = u.id', 'left')
            ->where('u.status', 1)
            ->where('u.deleted_at', null)
            ->orderBy('u.name', 'ASC')
            ->get()->getResultArray();
        return $this->render('hrms/team', [
            'pageTitle' => 'Team',
            'rows'      => $rows,
        ]);
    }

    public function employeeEdit(int $id)
    {
        if (!$this->auth->can('hrms', 'can_approve')) {
            return redirect()->to(site_url('hrms/profile'))->with('error', 'No access.');
        }
        $db = \Config\Database::connect();
        $user    = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (!$user) return redirect()->to(site_url('hrms/team'))->with('error', 'Not found.');
        $profile = $db->table('employee_profiles')->where('user_id', $id)->get()->getRowArray() ?: [];
        $profile = \App\Libraries\PiiCrypto::decryptRow($profile, \App\Libraries\PiiCrypto::EMPLOYEE_PROFILE);
        $components = $db->table('employee_salary_components')->where('user_id', $id)->get()->getRowArray() ?: [];
        $kin = $db->table('employee_next_of_kin')->where('user_id', $id)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $balances = $db->table('leave_balances lb')
            ->select('lb.*, lt.code, lt.name')
            ->join('leave_types lt', 'lt.id = lb.leave_type_id')
            ->where('lb.user_id', $id)
            ->where('lb.year', (int) date('Y'))
            ->orderBy('lt.id', 'ASC')->get()->getResultArray();
        return $this->render('hrms/employee_edit', [
            'pageTitle' => 'Employee — ' . $user['name'],
            'user'      => $user,
            'profile'   => $profile,
            'components'=> $components,
            'balances'  => $balances,
            'kin'       => $kin,
        ]);
    }

    public function employeeSave(int $id)
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Admin-editable fields
        $adminFields = [
            'employee_code','date_of_birth','date_of_joining','gender','marital_status','blood_group',
            'designation','department','reporting_manager_id',
            'permanent_address','current_address','emergency_contact_name','emergency_contact_phone',
            'pan_no','aadhaar_last_4','uan_no','bank_name','bank_account_no','bank_ifsc',
        ];
        $data = ['updated_at' => $now];
        foreach ($adminFields as $f) {
            $v = $this->request->getPost($f);
            if ($v !== null) $data[$f] = ($v === '' ? null : trim((string) $v));
        }
        if (isset($data['reporting_manager_id']) && $data['reporting_manager_id'] === null) {
            // keep as null
        } elseif (isset($data['reporting_manager_id'])) {
            $data['reporting_manager_id'] = (int) $data['reporting_manager_id'] ?: null;
        }
        // Encrypt PII at rest
        $data = \App\Libraries\PiiCrypto::encryptRow($data, \App\Libraries\PiiCrypto::EMPLOYEE_PROFILE);
        $exists = $db->table('employee_profiles')->where('user_id', $id)->get()->getRowArray();
        if ($exists) {
            $db->table('employee_profiles')->where('id', (int) $exists['id'])->update($data);
        } else {
            $data['user_id']    = $id;
            $data['created_at'] = $now;
            $db->table('employee_profiles')->insert($data);
        }
        $this->saveKin($id);
        return redirect()->to(site_url('hrms/team/' . $id))->with('success', 'Employee profile updated.');
    }

    public function saveSalaryComponents(int $id)
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $fields = ['basic','hra','special_allowance','conveyance','medical','other_earnings',
                   'pf_deduction','esi_deduction','pt_deduction','tds_deduction','other_deductions'];
        $data = ['user_id' => $id, 'updated_at' => $now, 'status' => 1];
        foreach ($fields as $f) $data[$f] = (float) $this->request->getPost($f);
        $data['effective_from'] = $this->request->getPost('effective_from') ?: date('Y-m-d');

        $existing = $db->table('employee_salary_components')->where('user_id', $id)->get()->getRowArray();
        if ($existing) {
            $db->table('employee_salary_components')->where('id', (int) $existing['id'])->update($data);
        } else {
            $data['created_at'] = $now;
            $db->table('employee_salary_components')->insert($data);
        }
        return redirect()->to(site_url('hrms/team/' . $id))->with('success', 'Salary components saved.');
    }

    public function leaveApprovals()
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->to(site_url('hrms/profile'))->with('error', 'No access.');
        $db = \Config\Database::connect();
        $rows = $db->table('leaves l')
            ->select('l.*, u.name AS employee_name, lt.code AS type_code, lt.name AS type_name')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->join('leave_types lt', 'lt.id = l.leave_type_id', 'left')
            ->orderBy('FIELD(l.status, "Pending","Approved","Rejected","Cancelled"), l.id DESC', '', false)
            ->limit(200)
            ->get()->getResultArray();
        return $this->render('hrms/leave_approvals', [
            'pageTitle' => 'Leave Approvals',
            'rows'      => $rows,
        ]);
    }

    public function approveLeave(int $id)
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $action = (string) $this->request->getPost('action');
        $notes  = trim((string) $this->request->getPost('approver_notes'));
        if (!in_array($action, ['approve','reject'], true)) {
            return redirect()->back()->with('error', 'Unknown action.');
        }
        $db  = \Config\Database::connect();
        $row = $db->table('leaves')->where('id', $id)->get()->getRowArray();
        if (!$row) return redirect()->back()->with('error', 'Not found.');
        if ($row['status'] !== 'Pending') return redirect()->back()->with('error', 'Already decided.');

        $now = date('Y-m-d H:i:s');
        $db->table('leaves')->where('id', $id)->update([
            'status'           => $action === 'approve' ? 'Approved' : 'Rejected',
            'approver_user_id' => (int) $this->auth->id(),
            'approver_notes'   => $notes ?: null,
            'approved_at'      => $now,
            'updated_at'       => $now,
        ]);

        if ($action === 'approve') {
            // Deduct from balance
            $year = (int) date('Y', strtotime((string) $row['from_date']));
            $bal = $db->table('leave_balances')
                ->where(['user_id' => (int) $row['user_id'], 'leave_type_id' => (int) $row['leave_type_id'], 'year' => $year])
                ->get()->getRowArray();
            if ($bal) {
                $used    = (float) $bal['used']    + (float) $row['days'];
                $balance = (float) $bal['allocated'] - $used;
                $db->table('leave_balances')->where('id', (int) $bal['id'])->update([
                    'used' => $used, 'balance' => $balance, 'updated_at' => $now,
                ]);
            }
            // Mark attendance Leave for each day in range
            $cur = strtotime((string) $row['from_date']);
            $end = strtotime((string) $row['to_date']);
            while ($cur <= $end) {
                $d = date('Y-m-d', $cur);
                $att = $db->table('attendance')->where(['user_id' => (int) $row['user_id'], 'attendance_date' => $d])->get()->getRowArray();
                if ($att) {
                    $db->table('attendance')->where('id', (int) $att['id'])->update(['status' => 'Leave', 'updated_at' => $now]);
                } else {
                    $db->table('attendance')->insert([
                        'user_id' => (int) $row['user_id'], 'attendance_date' => $d, 'status' => 'Leave',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $cur = strtotime('+1 day', $cur);
            }
        }
        // Notify the employee of the decision
        try {
            $emp  = $db->table('users')->where('id', (int) $row['user_id'])->get()->getRowArray();
            $type = $db->table('leave_types')->where('id', (int) $row['leave_type_id'])->get()->getRowArray();
            $email = trim((string) ($emp['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)
                && $this->alertEnabled('leave_decided', 'internal', 'email')) {
                (new \App\Libraries\EmailService())->sendTemplate('leave_decided', $email, [
                    'employee_name'   => $emp['name'] ?? 'colleague',
                    'leave_type'      => $type['name'] ?? '—',
                    'from_date'       => date('d M Y', strtotime((string) $row['from_date'])),
                    'to_date'         => date('d M Y', strtotime((string) $row['to_date'])),
                    'days'            => (string) $row['days'],
                    'decision'        => $action === 'approve' ? 'approved' : 'rejected',
                    'approver_notes'  => $notes ?: '(none)',
                    'my_leaves_link'  => site_url('hrms/leaves'),
                ], ['related_module' => 'hrms', 'related_id' => (int) $row['user_id']]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'leave_decided email failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('hrms/approvals'))->with('success', 'Leave ' . ($action === 'approve' ? 'approved' : 'rejected') . '.');
    }

    /* ─────────────────── Holiday calendar (admin) ─────────────────── */

    public function holidays()
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->to(site_url('hrms/profile'))->with('error', 'No access.');
        $year = (int) ($this->request->getGet('y') ?? date('Y'));
        $db = \Config\Database::connect();
        $rows = $db->table('holidays')
            ->where('YEAR(holiday_date)', $year)
            ->orderBy('holiday_date', 'ASC')->get()->getResultArray();
        return $this->render('hrms/holidays', [
            'pageTitle' => 'Holiday Calendar · ' . $year,
            'rows'      => $rows,
            'year'      => $year,
        ]);
    }

    public function holidayStore()
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $date = (string) $this->request->getPost('holiday_date');
        $name = trim((string) $this->request->getPost('name'));
        $pay  = (string) ($this->request->getPost('pay_status') ?: 'paid');
        if ($date === '' || $name === '') {
            return redirect()->back()->with('error', 'Date and name are required.');
        }
        $db = \Config\Database::connect();
        $exists = $db->table('holidays')->where('holiday_date', $date)->where('applies_to', 'all')->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->with('error', 'A holiday already exists on that date.');
        }
        $now = date('Y-m-d H:i:s');
        $db->table('holidays')->insert([
            'holiday_date' => date('Y-m-d', strtotime($date)),
            'name'         => mb_substr($name, 0, 120),
            'pay_status'   => in_array($pay, ['paid','unpaid','optional'], true) ? $pay : 'paid',
            'applies_to'   => 'all', 'applies_value' => null,
            'status'       => 1,
            'created_at'   => $now, 'updated_at' => $now,
        ]);
        return redirect()->to(site_url('hrms/holidays?y=' . (int) date('Y', strtotime($date))))
            ->with('success', 'Holiday added.');
    }

    public function holidayDelete(int $id)
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $db = \Config\Database::connect();
        $row = $db->table('holidays')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $db->table('holidays')->where('id', $id)->delete();
            return redirect()->to(site_url('hrms/holidays?y=' . (int) date('Y', strtotime($row['holiday_date']))))
                ->with('success', 'Holiday removed.');
        }
        return redirect()->back()->with('error', 'Not found.');
    }

    public function teamAttendance()
    {
        if (!$this->auth->can('hrms', 'can_approve')) return redirect()->to(site_url('hrms/profile'))->with('error', 'No access.');
        $date = (string) ($this->request->getGet('d') ?? date('Y-m-d'));
        $db = \Config\Database::connect();
        $rows = $db->table('users u')
            ->select('u.id, u.name, a.punch_in_at, a.punch_out_at, a.hours_worked, a.status AS att_status')
            ->join('attendance a', "a.user_id = u.id AND a.attendance_date = " . $db->escape($date), 'left')
            ->where('u.status', 1)->where('u.deleted_at', null)
            ->orderBy('u.name', 'ASC')->get()->getResultArray();
        return $this->render('hrms/team_attendance', [
            'pageTitle' => 'Team Attendance · ' . $date,
            'rows'      => $rows,
            'date'      => $date,
        ]);
    }
}
