<?php

namespace App\Controllers;

class PayrollController extends BaseController
{
    public function runs()
    {
        if (!$this->auth->can('payroll', 'can_view')) return redirect()->to(site_url('dashboard'))->with('error', 'No access to payroll.');

        $perPage = max(10, min(100, (int) ($this->request->getGet('per_page') ?: 20)));
        $page    = max(1, (int) ($this->request->getGet('page') ?: 1));

        $db    = \Config\Database::connect();
        $total = (int) $db->table('payroll_runs')->countAllResults();
        $rows  = $db->table('payroll_runs')
            ->orderBy('pay_period_year', 'DESC')
            ->orderBy('pay_period_month', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        $pagerHtml = service('pager')->setPath('payroll')->makeLinks($page, $perPage, $total, 'default_full');

        return $this->render('payroll/runs', [
            'pageTitle' => 'Payroll [HRMS] — List',
            'rows'      => $rows,
            'pagerHtml' => $pagerHtml,
            'perPage'   => $perPage,
            'total'     => $total,
        ], retroFixedShell: true);
    }

    /**
     * Create a new draft payroll run for the given period and compute every
     * eligible employee's line. Re-runnable: deletes any existing Draft run for
     * that period and recomputes.
     */
    public function newRun()
    {
        if (!$this->auth->can('payroll', 'can_add')) return redirect()->back()->with('error', 'No access.');
        $year  = (int) ($this->request->getPost('year')  ?? date('Y'));
        $month = (int) ($this->request->getPost('month') ?? date('n'));
        if ($month < 1 || $month > 12) return redirect()->back()->with('error', 'Invalid month.');

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $existing = $db->table('payroll_runs')
            ->where(['pay_period_year' => $year, 'pay_period_month' => $month])
            ->get()->getRowArray();
        if ($existing && $existing['run_status'] !== 'Draft') {
            return redirect()->to(site_url('payroll/' . (int) $existing['id']))->with('error', 'A finalised run already exists for that period.');
        }
        if ($existing) {
            $db->table('payroll_lines')->where('payroll_run_id', (int) $existing['id'])->delete();
            $runId = (int) $existing['id'];
            $db->table('payroll_runs')->where('id', $runId)->update(['notes' => 'Recomputed', 'updated_at' => $now]);
        } else {
            $db->table('payroll_runs')->insert([
                'pay_period_year' => $year, 'pay_period_month' => $month,
                'run_status' => 'Draft', 'notes' => null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $runId = (int) $db->insertID();
        }

        // Period bounds
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $daysInMonth = (int) date('t', strtotime($from));

        // Public holidays in this pay period: by default paid + auto-applied to "all"
        $holidayRows = $db->table('holidays')
            ->where('holiday_date >=', $from)
            ->where('holiday_date <=', $to)
            ->where('status', 1)
            ->where('applies_to', 'all')
            ->whereIn('pay_status', ['paid','optional'])
            ->get()->getResultArray();
        $paidHolidayDates = [];
        foreach ($holidayRows as $h) $paidHolidayDates[$h['holiday_date']] = true;

        $users = $db->table('users')->where('status', 1)->where('deleted_at', null)->get()->getResultArray();
        $totalGross = 0; $totalDed = 0; $totalNet = 0;

        foreach ($users as $u) {
            $userId = (int) $u['id'];
            $comp = $db->table('employee_salary_components')->where('user_id', $userId)->get()->getRowArray();
            if (!$comp) continue; // no salary structure yet → skip

            // Attendance tally
            $att = $db->table('attendance')
                ->where('user_id', $userId)
                ->where('attendance_date >=', $from)
                ->where('attendance_date <=', $to)
                ->get()->getResultArray();
            $present = $half = $leave = 0; $weekendsAndHolidays = 0;
            foreach ($att as $a) {
                if ($a['status'] === 'Present')   $present++;
                elseif ($a['status'] === 'HalfDay') $half++;
                elseif ($a['status'] === 'Leave') $leave++;
                elseif (in_array($a['status'], ['Weekend','Holiday'], true)) $weekendsAndHolidays++;
            }
            // Count weekend AND holiday dates in the period that have no attendance row
            // (treat as paid days when the holiday is paid/optional or when it's a weekend)
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dow = (int) date('N', strtotime($date)); // 6,7 = Sat,Sun
                $isHoliday = isset($paidHolidayDates[$date]);
                if ($dow >= 6 || $isHoliday) {
                    $has = false;
                    foreach ($att as $a) if ($a['attendance_date'] === $date) { $has = true; break; }
                    if (!$has) $weekendsAndHolidays++;
                }
            }

            $workingDays = $daysInMonth - $weekendsAndHolidays;
            $paidDays    = $present + ($half * 0.5) + $leave + $weekendsAndHolidays;
            $absent      = max(0, $workingDays - $present - $half - $leave);
            $lopDays     = $absent + ($half * 0.5);

            // Earnings = component * paidDays / daysInMonth (banking calendar)
            $factor = $daysInMonth > 0 ? ($paidDays / $daysInMonth) : 0;
            $basic = round((float) $comp['basic']             * $factor, 2);
            $hra   = round((float) $comp['hra']               * $factor, 2);
            $spec  = round((float) $comp['special_allowance'] * $factor, 2);
            $conv  = round((float) $comp['conveyance']        * $factor, 2);
            $med   = round((float) $comp['medical']           * $factor, 2);
            $other = round((float) $comp['other_earnings']    * $factor, 2);
            $gross = round($basic + $hra + $spec + $conv + $med + $other, 2);

            // LOP deduction = (basic + hra + special) * lopDays / daysInMonth
            $lopDed = $daysInMonth > 0 ? round((((float) $comp['basic'] + (float) $comp['hra'] + (float) $comp['special_allowance']) * $lopDays / $daysInMonth), 2) : 0;

            $pf  = (float) $comp['pf_deduction'];
            $esi = (float) $comp['esi_deduction'];
            $pt  = (float) $comp['pt_deduction'];
            $tds = (float) $comp['tds_deduction'];
            $otd = (float) $comp['other_deductions'];
            $totalDeductions = round($pf + $esi + $pt + $tds + $lopDed + $otd, 2);
            $net = round($gross - $totalDeductions, 2);

            $db->table('payroll_lines')->insert([
                'payroll_run_id'    => $runId,
                'user_id'           => $userId,
                'days_in_month'     => $daysInMonth,
                'days_paid'         => $paidDays,
                'days_absent'       => $absent,
                'days_leave'        => $leave,
                'lop_days'          => $lopDays,
                'basic'             => $basic,
                'hra'               => $hra,
                'special_allowance' => $spec,
                'conveyance'        => $conv,
                'medical'           => $med,
                'other_earnings'    => $other,
                'gross'             => $gross,
                'pf_deduction'      => $pf,
                'esi_deduction'     => $esi,
                'pt_deduction'      => $pt,
                'tds_deduction'     => $tds,
                'lop_deduction'     => $lopDed,
                'other_deductions'  => $otd,
                'total_deductions'  => $totalDeductions,
                'net_pay'           => $net,
                'created_at'        => $now,
            ]);
            $totalGross += $gross; $totalDed += $totalDeductions; $totalNet += $net;
        }

        $db->table('payroll_runs')->where('id', $runId)->update([
            'total_gross'      => $totalGross,
            'total_deductions' => $totalDed,
            'total_net'        => $totalNet,
            'updated_at'       => $now,
        ]);

        return redirect()->to(site_url('payroll/' . $runId))->with('success', 'Payroll computed for ' . date('F Y', strtotime($from)) . '.');
    }

    public function runDetail(int $id)
    {
        if (!$this->auth->can('payroll', 'can_view')) return redirect()->to(site_url('dashboard'))->with('error', 'No access.');
        $db = \Config\Database::connect();
        $run = $db->table('payroll_runs')->where('id', $id)->get()->getRowArray();
        if (!$run) return redirect()->to(site_url('payroll'))->with('error', 'Not found.');
        $lines = $db->table('payroll_lines pl')
            ->select('pl.*, u.name AS employee_name, u.email')
            ->join('users u', 'u.id = pl.user_id', 'left')
            ->where('pl.payroll_run_id', $id)
            ->orderBy('u.name', 'ASC')
            ->get()->getResultArray();
        return $this->render('payroll/run_detail', [
            'pageTitle' => 'Payroll [HRMS]',
            'run'       => $run,
            'lines'     => $lines,
        ], retroFixedShell: true);
    }

    public function finaliseRun(int $id)
    {
        if (!$this->auth->can('payroll', 'can_approve')) return redirect()->back()->with('error', 'No access.');
        $db = \Config\Database::connect();
        $run = $db->table('payroll_runs')->where('id', $id)->get()->getRowArray();
        if (!$run) return redirect()->to(site_url('payroll'))->with('error', 'Not found.');
        if ($run['run_status'] !== 'Draft') return redirect()->back()->with('error', 'Already finalised.');
        $db->table('payroll_runs')->where('id', $id)->update([
            'run_status'   => 'Finalised',
            'finalised_by' => (int) $this->auth->id(),
            'finalised_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        // Email each employee their payslip link
        try {
            $period = date('F Y', mktime(0, 0, 0, (int) $run['pay_period_month'], 1, (int) $run['pay_period_year']));
            $lines  = $db->table('payroll_lines pl')
                ->select('pl.id, pl.gross, pl.total_deductions, pl.net_pay, u.email, u.name')
                ->join('users u', 'u.id = pl.user_id', 'left')
                ->where('pl.payroll_run_id', $id)
                ->get()->getResultArray();
            $sent = 0;
            $alertOn = true;
            $rule = $db->table('alert_rules')
                ->where('event_key', 'payslip_released')->where('audience', 'internal')->where('channel', 'email')
                ->get()->getRowArray();
            if ($rule && (int) $rule['enabled'] === 0) $alertOn = false;
            if ($alertOn) {
                foreach ($lines as $l) {
                    $email = trim((string) ($l['email'] ?? ''));
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
                    (new \App\Libraries\EmailService())->sendTemplate('payslip_released', $email, [
                        'employee_name' => $l['name'] ?? 'colleague',
                        'period'        => $period,
                        'net_pay'       => number_format((float) $l['net_pay'], 0),
                        'gross'         => number_format((float) $l['gross'], 0),
                        'deductions'    => number_format((float) $l['total_deductions'], 0),
                        'payslip_link'  => site_url('payroll/payslip/' . (int) $l['id']),
                    ], ['related_module' => 'payroll', 'related_id' => (int) $id]);
                    $sent++;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'payslip_released email failed: ' . $e->getMessage());
        }

        return redirect()->to(site_url('payroll/' . $id))->with('success', 'Run finalised — payslips emailed.');
    }

    public function payslip(int $lineId)
    {
        if (!$this->auth->can('payroll', 'can_view')) return redirect()->to(site_url('dashboard'))->with('error', 'No access.');
        $db = \Config\Database::connect();
        $line = $db->table('payroll_lines')->where('id', $lineId)->get()->getRowArray();
        if (!$line) return redirect()->to(site_url('payroll'))->with('error', 'Not found.');
        $run  = $db->table('payroll_runs')->where('id', (int) $line['payroll_run_id'])->get()->getRowArray();
        $user = $db->table('users')->where('id', (int) $line['user_id'])->get()->getRowArray();
        $prof = $db->table('employee_profiles')->where('user_id', (int) $line['user_id'])->get()->getRowArray() ?: [];
        $prof = \App\Libraries\PiiCrypto::decryptRow($prof, \App\Libraries\PiiCrypto::EMPLOYEE_PROFILE);
        $company = [
            'name'  => (string) (new \App\Models\SettingModel())->get('company_name', 'TPT Logistics'),
            'gstin' => (string) (new \App\Models\SettingModel())->get('company_gstin', ''),
            'addr'  => (string) (new \App\Models\SettingModel())->get('company_address', ''),
        ];
        return $this->render('payroll/payslip', [
            'pageTitle' => 'Payslip · ' . $user['name'],
            'line'      => $line,
            'run'       => $run,
            'user'      => $user,
            'profile'   => $prof,
            'company'   => $company,
        ]);
    }
}
