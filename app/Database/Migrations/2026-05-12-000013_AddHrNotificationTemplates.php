<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHrNotificationTemplates extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        $templates = [
            [
                'template_key'  => 'leave_applied',
                'audience_type' => 'internal',
                'subject'       => '{{employee_name}} applied for {{leave_type}} ({{days}} day{{plural}}) — approval needed',
                'variables_json'=> json_encode(['employee_name','leave_type','from_date','to_date','days','plural','reason','approve_link']),
                'body_html'     =>
'<h2>Leave application pending</h2>
<p><strong>{{employee_name}}</strong> has applied for <strong>{{leave_type}}</strong>:</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Dates:</strong> {{from_date}} to {{to_date}} ({{days}} day{{plural}})</div>
  <div><strong>Reason:</strong> {{reason}}</div>
</div>
<p><a href="{{approve_link}}">Open the approval queue</a> to review and decide.</p>',
                'body_text'     => "Leave application: {{employee_name}} — {{leave_type}}\nDates: {{from_date}} to {{to_date}} ({{days}} day{{plural}})\nReason: {{reason}}\n\nDecide: {{approve_link}}",
            ],
            [
                'template_key'  => 'leave_decided',
                'audience_type' => 'internal',
                'subject'       => 'Your leave application has been {{decision}}',
                'variables_json'=> json_encode(['employee_name','leave_type','from_date','to_date','days','decision','approver_notes','my_leaves_link']),
                'body_html'     =>
'<h2>Leave {{decision}}</h2>
<p>Hi {{employee_name}},</p>
<p>Your {{leave_type}} application for <strong>{{from_date}} to {{to_date}}</strong> ({{days}} days) has been <strong>{{decision}}</strong>.</p>
<?php /* approver notes are shown only when present */ ?>
<div style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <strong>Notes from approver:</strong><br>{{approver_notes}}
</div>
<p><a href="{{my_leaves_link}}">View all my leaves</a></p>',
                'body_text'     => "Hi {{employee_name}},\nYour {{leave_type}} application ({{from_date}} to {{to_date}}, {{days}} days) has been {{decision}}.\nNotes: {{approver_notes}}\n\n{{my_leaves_link}}",
            ],
            [
                'template_key'  => 'payslip_released',
                'audience_type' => 'internal',
                'subject'       => 'Your payslip for {{period}} — net ₹{{net_pay}}',
                'variables_json'=> json_encode(['employee_name','period','net_pay','gross','deductions','payslip_link']),
                'body_html'     =>
'<h2>Your payslip is ready</h2>
<p>Hi {{employee_name}},</p>
<p>Your payslip for <strong>{{period}}</strong> is now available:</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Gross:</strong> ₹{{gross}}</div>
  <div><strong>Deductions:</strong> ₹{{deductions}}</div>
  <div style="font-size:18px;margin-top:8px;"><strong>Net pay:</strong> ₹{{net_pay}}</div>
</div>
<p><a href="{{payslip_link}}">Open / download payslip</a></p>',
                'body_text'     => "Hi {{employee_name}},\nYour payslip for {{period}} is available.\nGross ₹{{gross}}, deductions ₹{{deductions}}, net ₹{{net_pay}}.\n\n{{payslip_link}}",
            ],
        ];
        foreach ($templates as $t) {
            if ($db->table('email_templates')->where('template_key', $t['template_key'])->countAllResults() === 0) {
                $t['status'] = 1; $t['created_at'] = $now; $t['updated_at'] = $now;
                $db->table('email_templates')->insert($t);
            }
        }

        $rules = [
            ['leave_applied',    'internal', 'email', 1],
            ['leave_decided',    'internal', 'email', 1],
            ['payslip_released', 'internal', 'email', 1],
        ];
        foreach ($rules as $r) {
            $exists = $db->table('alert_rules')->where(['event_key' => $r[0], 'audience' => $r[1], 'channel' => $r[2]])->countAllResults();
            if ($exists === 0) {
                $db->table('alert_rules')->insert([
                    'event_key' => $r[0], 'audience' => $r[1], 'channel' => $r[2],
                    'enabled' => $r[3], 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->table('email_templates')->whereIn('template_key', ['leave_applied','leave_decided','payslip_released'])->delete();
        $db->table('alert_rules')->whereIn('event_key', ['leave_applied','leave_decided','payslip_released'])->delete();
    }
}
