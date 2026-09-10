<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStaffPasswordResetTemplate extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        if ($db->table('email_templates')->where('template_key', 'staff_password_reset')->countAllResults() > 0) return;

        $btn = '<p style="margin:24px 0;"><a class="btn" href="{{reset_link}}" style="display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;">Reset password</a></p>';

        $db->table('email_templates')->insert([
            'template_key'   => 'staff_password_reset',
            'audience_type'  => 'internal',
            'subject'        => 'Reset your TPT password',
            'variables_json' => json_encode(['name','reset_link','expires']),
            'body_html'      =>
'<h2>Password reset</h2>
<p>Hi {{name}},</p>
<p>We received a request to reset your TPT password. Click the button below to set a new one — the link expires in <strong>{{expires}}</strong>.</p>'
. $btn .
'<p style="color:#555;font-size:13px;">If the button doesn\'t work, paste this link into your browser:<br>{{reset_link}}</p>
<p style="color:#888;font-size:13px;">If you didn\'t ask for this, ignore the email and your password stays the same.</p>',
            'body_text'      => "Password reset\n\nHi {{name}},\nReset your TPT password (expires in {{expires}}):\n{{reset_link}}\n\nIf you didn't ask for this, ignore this message.",
            'status'         => 1,
            'created_at'     => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        \Config\Database::connect()->table('email_templates')->where('template_key', 'staff_password_reset')->delete();
    }
}
