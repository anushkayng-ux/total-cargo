<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $b   = $db->table('email_templates');

        // CSS-inlined HTML body fragment used by every template (the base layout adds header/footer).
        $btn = function (string $url, string $label) {
            return '<p style="margin:24px 0;"><a class="btn" href="' . $url . '" style="display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;">' . $label . '</a></p>';
        };

        $templates = [
            [
                'template_key'   => 'invoice_share',
                'audience_type'  => 'client',
                'subject'        => 'Invoice {{invoice_no}} — ₹{{total}} due {{due_date}}',
                'variables_json' => json_encode(['INV0001','12,500.00','15 May 2026','Acme Logistics','https://example.com/portal/invoices/1']),
                'body_html'      =>
'<h2>Your invoice is ready</h2>
<p>Dear {{company}},</p>
<p>Please find attached invoice <strong>{{invoice_no}}</strong> for <strong>₹{{total}}</strong> with due date <strong>{{due_date}}</strong>.</p>
<div class="meta-card">
  <span class="meta-row"><span class="meta-label">Invoice</span> {{invoice_no}}</span>
  <span class="meta-row"><span class="meta-label">Amount</span> ₹{{total}}</span>
  <span class="meta-row"><span class="meta-label">Due</span> {{due_date}}</span>
</div>'
. $btn('{{view_url}}', 'View invoice')
. '<p style="font-size:13px;color:#777;">If you have already paid, please ignore this email.</p>',
                'body_text'      => 'Invoice {{invoice_no}} — ₹{{total}} due {{due_date}}. View at: {{view_url}}',
            ],
            [
                'template_key'   => 'payment_reminder',
                'audience_type'  => 'client',
                'subject'        => 'Friendly reminder · Invoice {{invoice_no}} — ₹{{balance}} pending',
                'variables_json' => json_encode(['INV0001','12,500.00','15 May 2026','https://example.com/portal/invoices/1']),
                'body_html'      =>
'<h2>Payment reminder</h2>
<p>Dear {{company}},</p>
<p>This is a gentle reminder that invoice <strong>{{invoice_no}}</strong> with a balance of <strong>₹{{balance}}</strong> was due on <strong>{{due_date}}</strong>.</p>'
. $btn('{{view_url}}', 'View &amp; pay')
. '<p style="font-size:13px;color:#777;">If payment has already been made, please share the UTR or transaction reference so we can apply it.</p>',
                'body_text'      => 'Reminder: invoice {{invoice_no}} of ₹{{balance}} was due {{due_date}}. View: {{view_url}}',
            ],
            [
                'template_key'   => 'booking_confirmed',
                'audience_type'  => 'client',
                'subject'        => 'Booking {{booking_no}} confirmed — {{route}}',
                'variables_json' => json_encode(['BK00012','Mumbai → Pune','Acme Logistics','https://example.com/portal/bookings/12']),
                'body_html'      =>
'<h2>Your booking is confirmed</h2>
<p>Dear {{company}},</p>
<p>Booking <strong>{{booking_no}}</strong> for <strong>{{route}}</strong> has been approved. Our operations team will share vehicle details shortly.</p>'
. $btn('{{view_url}}', 'Track this booking'),
                'body_text'      => 'Booking {{booking_no}} for {{route}} is confirmed. Track at: {{view_url}}',
            ],
            [
                'template_key'   => 'trip_in_transit',
                'audience_type'  => 'client',
                'subject'        => 'Trip {{trip_no}} is now in transit',
                'variables_json' => json_encode(['TR00045','MH04AB1234','Mumbai → Pune','https://example.com/portal/trips/45']),
                'body_html'      =>
'<h2>On the way</h2>
<p>Vehicle <strong>{{vehicle}}</strong> has dispatched for trip <strong>{{trip_no}}</strong> ({{route}}).</p>'
. $btn('{{view_url}}', 'Live status &amp; GPS'),
                'body_text'      => 'Trip {{trip_no}} ({{route}}) dispatched on vehicle {{vehicle}}. Live: {{view_url}}',
            ],
            [
                'template_key'   => 'trip_delivered',
                'audience_type'  => 'client',
                'subject'        => 'Trip {{trip_no}} delivered — POD requested',
                'variables_json' => json_encode(['TR00045','Pune','https://example.com/portal/trips/45']),
                'body_html'      =>
'<h2>Delivered ✅</h2>
<p>Trip <strong>{{trip_no}}</strong> has been delivered at <strong>{{drop}}</strong>. We will request the POD from the consignee. You can also share a signed POD with us via the portal.</p>'
. $btn('{{view_url}}', 'View trip'),
                'body_text'      => 'Trip {{trip_no}} delivered at {{drop}}. {{view_url}}',
            ],
            [
                'template_key'   => 'portal_invite',
                'audience_type'  => 'client',
                'subject'        => 'You have been invited to the {{company}} portal',
                'variables_json' => json_encode(['Pat Singh','Acme Logistics','https://example.com/portal/invite/abc']),
                'body_html'      =>
'<h2>Welcome aboard</h2>
<p>Hi {{name}},</p>
<p>You have been invited to access the <strong>{{company}}</strong> client portal where you can request trucks, track shipments, view invoices, and more.</p>'
. $btn('{{invite_url}}', 'Activate your account')
. '<p style="font-size:13px;color:#777;">This link is valid for 7 days. If it expires, contact your account manager for a fresh one.</p>',
                'body_text'      => 'Activate your portal account: {{invite_url}}',
            ],
            [
                'template_key'   => 'portal_password_reset',
                'audience_type'  => 'client',
                'subject'        => 'Reset your portal password',
                'variables_json' => json_encode(['Pat Singh','https://example.com/portal/reset/abc','https://example.com/portal/login']),
                'body_html'      =>
'<h2>Reset your password</h2>
<p>Hi {{name}},</p>
<p>Click the button below to choose a new password. This link is valid for 1 hour and can only be used once.</p>'
. $btn('{{reset_url}}', 'Choose new password')
. '<p style="font-size:13px;color:#777;">If you did not request this reset, ignore this email — your existing password remains unchanged.</p>',
                'body_text'      => 'Reset your password: {{reset_url}}',
            ],
            [
                'template_key'   => 'rfq_quote_to_client',
                'audience_type'  => 'client',
                'subject'        => 'Quotation for {{route}} — ₹{{rate}}',
                'variables_json' => json_encode(['Acme Logistics','Mumbai → Pune','45,000','17 May 2026']),
                'body_html'      =>
'<h2>Your quote</h2>
<p>Dear {{company}},</p>
<p>For your enquiry on <strong>{{route}}</strong>, our best rate is <strong>₹{{rate}}</strong>, valid until <strong>{{valid_till}}</strong>.</p>
<p>Reply to this email to confirm and we will dispatch the vehicle.</p>',
                'body_text'      => 'Quote: {{route}} — ₹{{rate}} valid till {{valid_till}}. Reply to confirm.',
            ],
        ];

        foreach ($templates as $t) {
            if ($b->where('template_key', $t['template_key'])->countAllResults() === 0) {
                $t['status']     = 1;
                $t['created_at'] = $now;
                $t['updated_at'] = $now;
                $b->insert($t);
            }
        }
    }
}
