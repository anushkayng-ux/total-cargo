<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTripFeedback extends Migration
{
    public function up(): void
    {
        // ── trip_feedback : one feedback record per trip ──
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'                => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'feedback_token'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'token_expires_at'       => ['type' => 'DATETIME', 'null' => true],
            'requested_at'           => ['type' => 'DATETIME', 'null' => true],
            'reminded_at'            => ['type' => 'DATETIME', 'null' => true],
            'rating_overall'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_on_time'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_goods_condition' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_driver'          => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'rating_communication'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'nps_score'              => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'would_recommend'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'comments'               => ['type' => 'TEXT', 'null' => true],
            'submitter_name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'submitter_email'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'submitter_phone'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'submitted_at'           => ['type' => 'DATETIME', 'null' => true],
            'ip_address'             => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('trip_id');
        $this->forge->addUniqueKey('feedback_token');
        $this->forge->addKey('submitted_at');
        $this->forge->createTable('trip_feedback');

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        // ── Email template ──
        $btn = '<p style="margin:24px 0;"><a class="btn" href="{{feedback_link}}" style="display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;">Rate your experience</a></p>';
        $exists = $db->table('email_templates')->where('template_key', 'trip_feedback_request')->countAllResults();
        if ($exists === 0) {
            $db->table('email_templates')->insert([
                'template_key'  => 'trip_feedback_request',
                'audience_type' => 'client',
                'subject'       => 'How was trip {{trip_no}}? Share your feedback',
                'variables_json'=> json_encode(['client_name','trip_no','route','feedback_link']),
                'body_html'     =>
'<h2>How did we do?</h2>
<p>Dear {{client_name}},</p>
<p>Your trip <strong>{{trip_no}}</strong> ({{route}}) has been delivered. We would love your honest feedback — it takes 30 seconds and helps us serve you better.</p>'
. $btn .
'<p style="color:#555;font-size:13px;">If you cannot click the button, copy and paste this link into your browser:<br>{{feedback_link}}</p>',
                'body_text'     => "How did we do?\n\nDear {{client_name}},\nYour trip {{trip_no}} ({{route}}) has been delivered.\n\nRate your experience: {{feedback_link}}",
                'status'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // ── alert_rules entries ──
        $rules = [
            ['trip_feedback_request', 'client',   'email', 1],
            ['trip_feedback_request', 'internal', 'email', 0],
            ['trip_feedback_submitted','internal','email', 1],
        ];
        $rb = $db->table('alert_rules');
        foreach ($rules as $r) {
            $exists = $rb->where(['event_key' => $r[0], 'audience' => $r[1], 'channel' => $r[2]])->countAllResults();
            if ($exists === 0) {
                $rb->insert([
                    'event_key' => $r[0], 'audience' => $r[1], 'channel' => $r[2],
                    'enabled' => $r[3], 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // ── Email template for staff notification on submission ──
        $exists = $db->table('email_templates')->where('template_key', 'trip_feedback_submitted')->countAllResults();
        if ($exists === 0) {
            $db->table('email_templates')->insert([
                'template_key'  => 'trip_feedback_submitted',
                'audience_type' => 'internal',
                'subject'       => '{{stars}} feedback received on trip {{trip_no}}',
                'variables_json'=> json_encode(['trip_no','client_company','stars','overall','comments','trip_link']),
                'body_html'     =>
'<h2>New trip feedback</h2>
<p>Trip <strong>{{trip_no}}</strong> ({{client_company}}) just received a {{stars}} rating from the client.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Overall:</strong> {{overall}}/5</div>
  <div><strong>Comments:</strong> {{comments}}</div>
</div>
<p><a href="{{trip_link}}">Open the trip in the staff app</a> to see all ratings.</p>',
                'body_text'     => "Feedback on {{trip_no}} ({{client_company}}): {{overall}}/5\n\nComments: {{comments}}\n\nTrip: {{trip_link}}",
                'status'        => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->table('email_templates')->whereIn('template_key', ['trip_feedback_request','trip_feedback_submitted'])->delete();
        $db->table('alert_rules')->whereIn('event_key', ['trip_feedback_request','trip_feedback_submitted'])->delete();
        $this->forge->dropTable('trip_feedback', true);
    }
}
