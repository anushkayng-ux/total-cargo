<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkflowEmailTemplates extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();
        $b   = $db->table('email_templates');

        $btn = function (string $url, string $label) {
            return '<p style="margin:24px 0;"><a class="btn" href="' . $url . '" style="display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;">' . $label . '</a></p>';
        };

        $templates = [
            [
                'template_key'  => 'vendor_rfq_invite',
                'audience_type' => 'vendor',
                'subject'       => 'New RFQ {{rfq_no}} — {{route}} — submit your rate',
                'variables_json'=> json_encode(['rfq_no','route','vehicle_type','material','weight','loading_date','quote_link','vendor_name']),
                'body_html'     =>
'<h2>New Request for Quotation</h2>
<p>Hi {{vendor_name}},</p>
<p>We have a new load that matches your fleet. Please submit your best rate by clicking the button below — no login required.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>RFQ:</strong> {{rfq_no}}</div>
  <div><strong>Route:</strong> {{route}}</div>
  <div><strong>Vehicle:</strong> {{vehicle_type}}</div>
  <div><strong>Material:</strong> {{material}}</div>
  <div><strong>Weight:</strong> {{weight}}</div>
  <div><strong>Loading date:</strong> {{loading_date}}</div>
</div>'
                . $btn('{{quote_link}}', 'Submit your rate')
                . '<p style="color:#555;font-size:13px;">The link is unique to your firm. Quote anytime — we close RFQs after the loading date.</p>',
                'body_text'     =>
"New RFQ — {{rfq_no}}\n\nRoute: {{route}}\nVehicle: {{vehicle_type}}\nMaterial: {{material}}\nWeight: {{weight}}\nLoading: {{loading_date}}\n\nSubmit your rate: {{quote_link}}",
            ],
            [
                'template_key'  => 'vendor_quote_received',
                'audience_type' => 'internal',
                'subject'       => 'New quote on RFQ {{rfq_no}} from {{vendor_name}} — ₹{{quote_amount}}',
                'variables_json'=> json_encode(['rfq_no','route','vendor_name','quote_amount','transit_days','remarks','rfq_link']),
                'body_html'     =>
'<h2>Vendor quoted on RFQ {{rfq_no}}</h2>
<p>A vendor just submitted a rate. Open the RFQ to compare quotes and decide.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>RFQ:</strong> {{rfq_no}} — {{route}}</div>
  <div><strong>Vendor:</strong> {{vendor_name}}</div>
  <div><strong>Rate:</strong> ₹{{quote_amount}}</div>
  <div><strong>Transit:</strong> {{transit_days}} days</div>
  <div><strong>Remarks:</strong> {{remarks}}</div>
</div>'
                . $btn('{{rfq_link}}', 'Open RFQ'),
                'body_text'     =>
"Vendor quoted on RFQ {{rfq_no}}\n\n{{vendor_name}} — ₹{{quote_amount}}\nTransit: {{transit_days}} days\nRemarks: {{remarks}}\n\nOpen RFQ: {{rfq_link}}",
            ],
            [
                'template_key'  => 'vendor_won_assignment',
                'audience_type' => 'vendor',
                'subject'       => 'Awarded: Trip {{trip_no}} — {{route}}',
                'variables_json'=> json_encode(['trip_no','rfq_no','vendor_name','route','vehicle_type','loading_date','rate','consignee_name','consignee_address','consignee_mobile','contact_phone']),
                'body_html'     =>
'<h2>Congratulations — you are awarded this trip</h2>
<p>Hi {{vendor_name}}, your rate has been accepted. Please confirm vehicle placement at the loading point on the scheduled date.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Trip:</strong> {{trip_no}} (from {{rfq_no}})</div>
  <div><strong>Route:</strong> {{route}}</div>
  <div><strong>Vehicle:</strong> {{vehicle_type}}</div>
  <div><strong>Loading date:</strong> {{loading_date}}</div>
  <div><strong>Agreed rate:</strong> ₹{{rate}}</div>
  <div style="margin-top:8px;padding-top:8px;border-top:1px solid #e2e5e9;"><strong>Consignee:</strong> {{consignee_name}}</div>
  <div><strong>Address:</strong> {{consignee_address}}</div>
  <div><strong>Consignee phone:</strong> {{consignee_mobile}}</div>
</div>
<p>For assistance call us on {{contact_phone}}.</p>',
                'body_text'     =>
"Awarded: Trip {{trip_no}}\n\nVendor: {{vendor_name}}\nRoute: {{route}}\nVehicle: {{vehicle_type}}\nLoading: {{loading_date}}\nRate: ₹{{rate}}\n\nConsignee: {{consignee_name}}\n{{consignee_address}}\nPhone: {{consignee_mobile}}\n\nQueries: {{contact_phone}}",
            ],
            [
                'template_key'  => 'client_truck_assigned',
                'audience_type' => 'client',
                'subject'       => 'Truck assigned for booking {{booking_no}} — {{vehicle_number}}',
                'variables_json'=> json_encode(['client_name','booking_no','trip_no','vehicle_number','vehicle_type','driver_name','driver_mobile','route','loading_date','vendor_company']),
                'body_html'     =>
'<h2>Your truck is assigned</h2>
<p>Dear {{client_name}},</p>
<p>We have placed a vehicle for your booking <strong>{{booking_no}}</strong>. Driver details below — you can reach the driver directly if needed.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Route:</strong> {{route}}</div>
  <div><strong>Loading date:</strong> {{loading_date}}</div>
  <div><strong>Vehicle:</strong> {{vehicle_number}} ({{vehicle_type}})</div>
  <div><strong>Driver:</strong> {{driver_name}}</div>
  <div><strong>Driver phone:</strong> {{driver_mobile}}</div>
  <div><strong>Transporter:</strong> {{vendor_company}}</div>
  <div><strong>Trip:</strong> {{trip_no}}</div>
</div>',
                'body_text'     =>
"Truck assigned — booking {{booking_no}}\n\nVehicle: {{vehicle_number}} ({{vehicle_type}})\nDriver: {{driver_name}} — {{driver_mobile}}\nTransporter: {{vendor_company}}\nRoute: {{route}}\nLoading: {{loading_date}}\nTrip: {{trip_no}}",
            ],
            [
                'template_key'  => 'driver_dispatch_link',
                'audience_type' => 'driver',
                'subject'       => 'Loading today — open your driver app for {{trip_no}}',
                'variables_json'=> json_encode(['driver_name','trip_no','lr_no','route','vehicle_number','loading_point','driver_link']),
                'body_html'     =>
'<h2>Loading today — open the driver tracker</h2>
<p>Hi {{driver_name}}, please open the link below and tap "Start sharing location" before you begin loading. Keep the page open during the trip.</p>
<div class="meta-card" style="background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;">
  <div><strong>Trip:</strong> {{trip_no}}</div>
  <div><strong>LR:</strong> {{lr_no}}</div>
  <div><strong>Vehicle:</strong> {{vehicle_number}}</div>
  <div><strong>Route:</strong> {{route}}</div>
  <div><strong>Loading point:</strong> {{loading_point}}</div>
</div>'
                . $btn('{{driver_link}}', 'Open driver app')
                . '<p style="color:#555;font-size:13px;">On Android Chrome you can tap "Install app" to add the tracker to your home screen.</p>',
                'body_text'     =>
"Loading today — open the driver tracker\n\nTrip: {{trip_no}} (LR {{lr_no}})\nVehicle: {{vehicle_number}}\nRoute: {{route}}\nLoading point: {{loading_point}}\n\nOpen: {{driver_link}}",
            ],
            [
                'template_key'  => 'trip_arrived_destination',
                'audience_type' => 'client',
                'subject'       => 'Trip {{trip_no}} arrived at destination',
                'variables_json'=> json_encode(['trip_no','route','vehicle_number','driver_name','arrived_at']),
                'body_html'     =>
'<h2>Truck has arrived at the destination</h2>
<p>Trip <strong>{{trip_no}}</strong> ({{route}}) reached the unloading point at {{arrived_at}}.</p>
<p>Vehicle <strong>{{vehicle_number}}</strong> · Driver {{driver_name}}.</p>
<p>We will share POD once unloading is complete.</p>',
                'body_text'     =>
"Trip {{trip_no}} arrived at destination\n\nRoute: {{route}}\nVehicle: {{vehicle_number}}\nDriver: {{driver_name}}\nArrived: {{arrived_at}}\n\nPOD will follow once unloading is complete.",
            ],
        ];

        foreach ($templates as $tpl) {
            $exists = $b->where('template_key', $tpl['template_key'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $tpl['status']     = 1;
            $tpl['created_at'] = $now;
            $tpl['updated_at'] = $now;
            $b->insert($tpl);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->table('email_templates')->whereIn('template_key', [
            'vendor_rfq_invite',
            'vendor_quote_received',
            'vendor_won_assignment',
            'client_truck_assigned',
            'driver_dispatch_link',
            'trip_arrived_destination',
        ])->delete();
    }
}
