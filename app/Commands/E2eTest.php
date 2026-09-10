<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\NotificationService;

/**
 * End-to-end workflow simulation. Walks every step from client raising a
 * truck request through invoice settlement, exercising every notification
 * trigger and DB transition. Prints a step-by-step timeline.
 *
 * Usage:  php spark tpt:e2e-test [--cleanup]
 *   --cleanup   delete the e2e test records after running (keep DB tidy)
 */
class E2eTest extends BaseCommand
{
    protected $group       = 'tpt';
    protected $name        = 'tpt:e2e-test';
    protected $description = 'Run the full client→aggregator→vendor→driver workflow end-to-end and report.';

    private $db;
    private $notif;
    private int $step = 0;
    private array $records = [];   // for cleanup
    private array $emailCount = []; // template_key => count

    public function run(array $params)
    {
        $this->db    = \Config\Database::connect();
        $this->notif = new NotificationService();

        $cleanup = in_array('--cleanup', $params, true);

        CLI::write(str_repeat('═', 78), 'cyan');
        CLI::write(' END-TO-END WORKFLOW SIMULATION  ·  ' . date('Y-m-d H:i:s'), 'cyan');
        CLI::write(str_repeat('═', 78), 'cyan');

        // Clear email queue so the report is scoped to this run
        $this->db->table('email_events')->where('1=1')->delete();
        $this->db->table('email_logs')->where('1=1')->delete();
        $this->db->query("ALTER TABLE email_logs AUTO_INCREMENT = 1");

        try {
            $clientId  = $this->setupClient();
            $vendorIds = $this->setupVendors();
            $driverId  = $this->setupDriver();
            $vehicleId = $this->setupVehicle();

            $leadId    = $this->stepCreateLead($clientId);
            $rfqId     = $this->stepCreateRfq($leadId);
            $this->stepInviteVendors($rfqId, $vendorIds);
            $this->stepDispatchRfq($rfqId);
            $this->stepVendorQuotes($rfqId, $vendorIds);
            $winnerQuoteId = $this->stepSelectWinner($rfqId, $vendorIds[0]);
            $bookingId = $this->stepCreateBooking($leadId, $clientId, $vendorIds[0], $rfqId);
            $tripId    = $this->stepCreateTrip($bookingId, $vendorIds[0]);
            $this->stepAssignDriver($tripId, $driverId, $vehicleId);
            $this->stepGenerateDriverLink($tripId);
            $this->stepDriverMilestones($tripId);
            $this->stepDriverChat($tripId);
            $this->stepDriverPod($tripId);
            $this->stepCloseTrip($tripId);
            $invoiceId = $this->stepInvoice($bookingId, $clientId, $tripId);
            $this->stepReceipt($invoiceId, $clientId);
        } catch (\Throwable $e) {
            CLI::error('Failed at step ' . $this->step . ': ' . $e->getMessage());
            CLI::write($e->getTraceAsString());
            return;
        }

        $this->printSummary();

        if ($cleanup) {
            CLI::write("\n" . str_repeat('─', 78), 'yellow');
            CLI::write(' CLEANUP — removing e2e test records', 'yellow');
            $this->cleanup();
        } else {
            CLI::write("\nTip: re-run with --cleanup to remove the test records.");
            CLI::write('Records left in DB so you can inspect via the UI.');
            CLI::write('Trip URL (staff): ' . site_url('trips/' . ($this->records['trip'] ?? '?')));
            if (!empty($this->records['driver_token'])) {
                CLI::write('Driver app URL: ' . site_url('d/' . $this->records['driver_token']));
            }
        }
    }

    /* ────────────────────────── Setup ────────────────────────── */

    private function setupClient(): int
    {
        $this->header('SETUP', 'Test client');
        $email = 'e2e_client@example.com';
        $row = $this->db->table('clients')->where('email', $email)->get()->getRowArray();
        if ($row) {
            $this->ok("Reusing existing e2e client #{$row['id']} — {$row['company_name']}");
            $this->records['client'] = (int) $row['id'];
            return (int) $row['id'];
        }
        $this->db->table('clients')->insert([
            'client_code'  => 'E2E' . substr((string) time(), -6),
            'company_name' => 'E2E Acme Logistics Pvt Ltd',
            'contact_name' => 'Test Client',
            'mobile'       => '9000000001',
            'email'        => $email,
            'address'      => 'Plot 1, Andheri East',
            'city'         => 'Mumbai',
            'state'        => 'Maharashtra',
            'pincode'      => '400069',
            'kyc_status'   => 'Verified',
            'status'       => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['client'] = $id;
        $this->ok("Created client #$id — E2E Acme Logistics Pvt Ltd ($email)");
        return $id;
    }

    private function setupVendors(): array
    {
        $this->header('SETUP', 'Three test vendors');
        $defs = [
            ['code' => 'E2EV01', 'company' => 'E2E Speedy Carriers Pvt Ltd',  'email' => 'e2e_vendor_a@example.com', 'mobile' => '9000000010'],
            ['code' => 'E2EV02', 'company' => 'E2E Reliable Transport Co.',   'email' => 'e2e_vendor_b@example.com', 'mobile' => '9000000020'],
            ['code' => 'E2EV03', 'company' => 'E2E Mountain Logistics LLP',   'email' => 'e2e_vendor_c@example.com', 'mobile' => '9000000030'],
        ];
        $ids = [];
        foreach ($defs as $d) {
            $exists = $this->db->table('vendors')->where('email', $d['email'])->get()->getRowArray();
            if ($exists) { $ids[] = (int) $exists['id']; continue; }
            $this->db->table('vendors')->insert([
                'vendor_code'  => $d['code'],
                'company_name' => $d['company'],
                'owner_name'   => 'Owner of ' . $d['company'],
                'mobile'       => $d['mobile'],
                'whatsapp_no'  => $d['mobile'],
                'email'        => $d['email'],
                'rating'       => 4.0,
                'is_preferred' => 1,
                'status'       => 1,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            $ids[] = (int) $this->db->insertID();
        }
        $this->records['vendors'] = $ids;
        $this->ok('Vendors ready: A=' . $ids[0] . ', B=' . $ids[1] . ', C=' . $ids[2]);
        return $ids;
    }

    private function setupDriver(): int
    {
        $this->header('SETUP', 'Test driver');
        $existing = $this->db->table('drivers')->where('mobile', '9000000099')->get()->getRowArray();
        if ($existing) { $this->records['driver'] = (int) $existing['id']; return (int) $existing['id']; }
        $this->db->table('drivers')->insert([
            'driver_name'    => 'E2E Test Driver Suresh',
            'mobile'         => '9000000099',
            'license_no'     => 'MH00E2E99999',
            'kyc_status'     => 'Verified',
            'license_expiry' => date('Y-m-d', strtotime('+2 years')),
            'status'         => 1,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['driver'] = $id;
        $this->ok("Created driver #$id — Suresh (KYC verified, licence valid)");
        return $id;
    }

    private function setupVehicle(): int
    {
        $this->header('SETUP', 'Test vehicle');
        $existing = $this->db->table('vehicles')->where('vehicle_number', 'MH12E2E1234')->get()->getRowArray();
        if ($existing) { $this->records['vehicle'] = (int) $existing['id']; return (int) $existing['id']; }
        $this->db->table('vehicles')->insert([
            'vehicle_number'   => 'MH12E2E1234',
            'vehicle_type'     => '32ft SXL',
            'insurance_expiry' => date('Y-m-d', strtotime('+1 year')),
            'fitness_expiry'   => date('Y-m-d', strtotime('+1 year')),
            'status'           => 1,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['vehicle'] = $id;
        $this->ok("Created vehicle #$id — MH12E2E1234 (compliance valid)");
        return $id;
    }

    /* ────────────────────────── Steps ────────────────────────── */

    private function stepCreateLead(int $clientId): int
    {
        $this->header('STEP 1', '👤 Client raises a truck request → Lead created');
        $leadNo = 'E2E_LD' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $this->db->table('leads')->insert([
            'lead_no'                => $leadNo,
            'lead_datetime'          => date('Y-m-d H:i:s'),
            'client_id'              => $clientId,
            'client_name'            => 'Test Client',
            'company_name'           => 'E2E Acme Logistics Pvt Ltd',
            'mobile'                 => '9000000001',
            'email'                  => 'e2e_client@example.com',
            'pickup_city'            => 'Mumbai',
            'pickup_state'           => 'Maharashtra',
            'drop_city'              => 'Chennai',
            'drop_state'             => 'Tamil Nadu',
            'material_type'          => 'General cargo',
            'vehicle_type_required'  => '32ft SXL',
            'vehicle_count'          => 1,
            'weight'                 => 12.0,
            'weight_unit'            => 'TON',
            'expected_dispatch_date' => date('Y-m-d', strtotime('+3 days')),
            'priority'               => 'Normal',
            'current_status'         => 'New',
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['lead'] = $id;
        $this->ok("Lead #$id ($leadNo) — Mumbai → Chennai, 12 TON, 32ft SXL");
        $this->detail("Channel: portal · Audience tag: client → internal CRM");
        return $id;
    }

    private function stepCreateRfq(int $leadId): int
    {
        $this->header('STEP 2', '📝 Staff (CRM) converts lead → RFQ');
        $rfqNo = 'E2E_RFQ' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $this->db->table('rfq_master')->insert([
            'rfq_no'             => $rfqNo,
            'lead_id'            => $leadId,
            'masked_reference'   => 'TPT-' . substr(md5($rfqNo), 0, 8),
            'pickup_city'        => 'Mumbai',
            'drop_city'          => 'Chennai',
            'vehicle_type'       => '32ft SXL',
            'material_category'  => 'General cargo',
            'weight'             => 12.0,
            'weight_unit'        => 'TON',
            'loading_date'       => date('Y-m-d', strtotime('+3 days')),
            'status'             => 'Open',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['rfq'] = $id;
        $this->ok("RFQ #$id ($rfqNo) — masked ref so vendors don't see client identity");
        return $id;
    }

    private function stepInviteVendors(int $rfqId, array $vendorIds): void
    {
        $this->header('STEP 3', '📋 Staff adds vendors to the RFQ shortlist');
        foreach ($vendorIds as $vid) {
            $this->db->table('rfq_vendors')->insert([
                'rfq_id'           => $rfqId,
                'vendor_id'        => $vid,
                'response_status'  => 'Pending',
            ]);
        }
        $this->ok("Added 3 vendors to RFQ #$rfqId (status=Pending)");
    }

    private function stepDispatchRfq(int $rfqId): void
    {
        $this->header('STEP 4', '📨 Aggregator dispatches RFQ → 3 vendors receive email');
        $r = $this->notif->onRfqDispatched($rfqId);
        $this->db->table('rfq_master')->where('id', $rfqId)->update(['status' => 'In Progress']);
        $this->ok("RFQ dispatched · sent={$r['sent']} skipped={$r['skipped']}");
        $this->detail('Each vendor got a unique tokenized link to /quote/<token>');
        $this->detail('Trigger: NotificationService::onRfqDispatched → template `vendor_rfq_invite`');
    }

    private function stepVendorQuotes(int $rfqId, array $vendorIds): void
    {
        $this->header('STEP 5', '💰 Two of three vendors submit quotes (one ignores)');
        // Vendor A quotes ₹38,000 (lowest)
        $this->submitQuote($rfqId, $vendorIds[0], 38000, 3, 'Can place vehicle by 7 AM. All-inclusive.');
        // Vendor B quotes ₹42,500
        $this->submitQuote($rfqId, $vendorIds[1], 42500, 3, 'Standard rate, no detention charges if loading <4h.');
        // Vendor C does not respond
        $this->ok('Vendor C did NOT respond (response_status stays Pending)');
        $this->detail('Each submitted quote fires `vendor_quote_received` to the internal mailbox');
    }

    private function submitQuote(int $rfqId, int $vendorId, float $amount, int $transit, string $remarks): void
    {
        $rv = $this->db->table('rfq_vendors')
            ->where('rfq_id', $rfqId)->where('vendor_id', $vendorId)
            ->get()->getRowArray();
        $now = date('Y-m-d H:i:s');
        $this->db->table('quotations')->insert([
            'rfq_id'           => $rfqId,
            'vendor_id'        => $vendorId,
            'quote_amount'     => $amount,
            'transit_days'     => $transit,
            'response_source'  => 'email',
            'remarks'          => $remarks,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $this->db->table('rfq_vendors')->where('id', $rv['id'])->update([
            'response_status'    => 'Quoted',
            'responded_at'       => $now,
            'quote_submitted_at' => $now,
        ]);
        $vendor = $this->db->table('vendors')->where('id', $vendorId)->get()->getRowArray();
        $rfq    = $this->db->table('rfq_master')->where('id', $rfqId)->get()->getRowArray();
        // Mimic the PublicQuoteController's "notify agency" step
        (new \App\Libraries\EmailService())->sendTemplate('vendor_quote_received',
            (string) (new \App\Models\SettingModel())->get('company_email', ''),
            [
                'rfq_no'        => $rfq['rfq_no'],
                'route'         => $rfq['pickup_city'] . ' → ' . $rfq['drop_city'],
                'vendor_name'   => $vendor['company_name'],
                'quote_amount'  => number_format($amount, 0),
                'transit_days'  => $transit,
                'remarks'       => $remarks,
                'rfq_link'      => site_url('rfq/' . $rfqId),
            ],
            ['related_module' => 'rfq', 'related_id' => $rfqId]
        );
        $this->ok("$vendor[company_name] quoted ₹" . number_format($amount, 0) . " · transit $transit days");
    }

    private function stepSelectWinner(int $rfqId, int $winnerVendorId): int
    {
        $this->header('STEP 6', '🏆 Aggregator (Ops) compares quotes and selects winner');
        $quote = $this->db->table('quotations')
            ->where('rfq_id', $rfqId)->where('vendor_id', $winnerVendorId)
            ->orderBy('id', 'DESC')->limit(1)
            ->get()->getRowArray();
        $this->db->table('quotations')->where('rfq_id', $rfqId)->update(['is_final_selected' => 0]);
        $this->db->table('quotations')->where('id', $quote['id'])->update([
            'is_final_selected' => 1, 'is_shortlisted' => 1,
        ]);
        $this->db->table('rfq_master')->where('id', $rfqId)->update(['status' => 'Awarded']);
        // Notify winning vendor
        $r = $this->notif->onQuoteSelected($rfqId, (int) $quote['id']);
        $this->ok("Quote #{$quote['id']} selected · vendor notified (sent={$r['sent']})");
        $this->detail('Trigger: NotificationService::onQuoteSelected → template `vendor_won_assignment`');
        return (int) $quote['id'];
    }

    private function stepCreateBooking(int $leadId, int $clientId, int $vendorId, int $rfqId): int
    {
        $this->header('STEP 7', '📦 Booking created from the awarded RFQ');
        $quote = $this->db->table('quotations')
            ->where('rfq_id', $rfqId)->where('is_final_selected', 1)
            ->orderBy('id','DESC')->limit(1)->get()->getRowArray();
        $bookingNo = 'E2E_BK' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $sell = (float) $quote['quote_amount'] * 1.15; // 15% margin
        $this->db->table('bookings')->insert([
            'booking_no'        => $bookingNo,
            'lead_id'           => $leadId,
            'client_id'         => $clientId,
            'vendor_id'         => $vendorId,
            'final_buy_rate'    => $quote['quote_amount'],
            'final_sell_rate'   => $sell,
            'margin_amount'     => $sell - $quote['quote_amount'],
            'route_text'        => 'Mumbai → Chennai',
            'vehicle_type'      => '32ft SXL',
            'load_details'      => 'General cargo, 12 TON',
            'loading_date'      => date('Y-m-d', strtotime('+3 days')),
            'consignee_name'    => 'E2E Receiver — South Metro Agencies',
            'consignee_mobile'  => '9200000777',
            'consignee_address' => 'Chennai wholesale market, near transport nagar',
            'booking_status'    => 'Approved',
            'approved_at'       => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['booking'] = $id;
        $this->ok("Booking #$id ($bookingNo) · buy=₹" . number_format((float) $quote['quote_amount'], 0) . " · sell=₹" . number_format($sell, 0));
        return $id;
    }

    private function stepCreateTrip(int $bookingId, int $vendorId): int
    {
        $this->header('STEP 8', '🛣 Trip created from booking');
        $tripNo = 'E2E_TR' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $this->db->table('trips')->insert([
            'trip_no'         => $tripNo,
            'booking_id'      => $bookingId,
            'vendor_id'       => $vendorId,
            'loading_point'   => 'Bhiwandi warehouse',
            'unloading_point' => 'Chennai wholesale market',
            'current_status'  => 'Booking Created',
            'pod_status'      => 'Pending',
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['trip'] = $id;
        $this->ok("Trip #$id ($tripNo) · status=Booking Created");
        return $id;
    }

    private function stepAssignDriver(int $tripId, int $driverId, int $vehicleId): void
    {
        $this->header('STEP 9', '🚚 Aggregator assigns driver + vehicle → client notified');
        $d = $this->db->table('drivers')->where('id', $driverId)->get()->getRowArray();
        $v = $this->db->table('vehicles')->where('id', $vehicleId)->get()->getRowArray();
        $this->db->table('trips')->where('id', $tripId)->update([
            'driver_id'      => $driverId,
            'vehicle_id'     => $vehicleId,
            'driver_name'    => $d['driver_name'],
            'driver_mobile'  => $d['mobile'],
            'vehicle_number' => $v['vehicle_number'],
            'current_status' => 'Vehicle Placed',
        ]);
        $this->db->table('trip_status_history')->insert([
            'trip_id' => $tripId, 'old_status' => 'Booking Created', 'new_status' => 'Vehicle Placed',
            'notes' => 'E2E test — auto-assigned', 'changed_at' => date('Y-m-d H:i:s'),
        ]);
        $r = $this->notif->onTripAssigned($tripId);
        $this->ok("Driver Suresh + MH12E2E1234 assigned · client notified (sent={$r['sent']})");
        $this->detail('Trigger: NotificationService::onTripAssigned → template `client_truck_assigned`');
    }

    private function stepGenerateDriverLink(int $tripId): void
    {
        $this->header('STEP 10', '🔗 Aggregator generates driver-tracker link');
        $token = bin2hex(random_bytes(32));
        $this->db->table('trips')->where('id', $tripId)->update([
            'driver_track_token'      => $token,
            'driver_track_started_at' => date('Y-m-d H:i:s'),
        ]);
        $this->records['driver_token'] = $token;
        $r = $this->notif->onDriverDispatchStarted($tripId);
        $this->ok('Token generated · internal copy emailed to ops (sent=' . $r['sent'] . ')');
        $this->detail('Driver opens: ' . site_url('d/' . $token));
        $this->detail('Trigger: NotificationService::onDriverDispatchStarted → template `driver_dispatch_link`');
    }

    private function stepDriverMilestones(int $tripId): void
    {
        $milestones = [
            ['stage' => 'started_to_pickup',   'status' => null,         'note' => 'Driver started toward pickup',  'icon' => '🚛'],
            ['stage' => 'reached_pickup',      'status' => 'Loading',    'note' => 'Driver reached pickup',         'icon' => '📍'],
            ['stage' => 'goods_loaded',        'status' => 'In Transit', 'note' => 'Goods loaded · trip in transit','icon' => '📦'],
            ['stage' => 'reached_destination', 'status' => 'Arrived',    'note' => 'Driver reached destination',    'icon' => '🎯'],
            ['stage' => 'unloaded',            'status' => 'Delivered',  'note' => 'Unloaded at destination',       'icon' => '✅'],
        ];
        $this->header('STEP 11', '📱 Driver taps milestones in the PWA');
        $current = (string) $this->db->table('trips')->where('id', $tripId)->get()->getRow('current_status');
        foreach ($milestones as $i => $m) {
            $oldStatus = $current;
            if ($m['status']) {
                $this->db->table('trips')->where('id', $tripId)->update(['current_status' => $m['status']]);
                $current = $m['status'];
            }
            $this->db->table('trip_status_history')->insert([
                'trip_id' => $tripId, 'old_status' => $oldStatus, 'new_status' => $current,
                'notes' => $m['note'], 'changed_at' => date('Y-m-d H:i:s'),
            ]);
            if ($m['status']) {
                $r = $this->notif->onTripStatusChanged($tripId, $m['status']);
                $emailNote = $r['sent'] > 0 ? "→ {$r['sent']} email(s)" : '(no email rule)';
            } else {
                $emailNote = '(history log only — no status change)';
            }
            $this->ok(sprintf('  %d. %s %s %s', $i + 1, $m['icon'], str_pad($m['stage'] . ($m['status'] ? " → $m[status]" : ''), 38), $emailNote));
        }
    }

    private function stepDriverChat(int $tripId): void
    {
        $this->header('STEP 12', '💬 Driver ↔ dispatch chat via the PWA');
        $this->db->table('driver_messages')->insert([
            'trip_id' => $tripId, 'direction' => 'driver', 'user_id' => null,
            'body' => 'Reached the state border, expected to cross in 30 min.',
            'is_read_by_driver' => 1, 'is_read_by_staff' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->ok('Driver: "Reached the state border, expected to cross in 30 min."');
        $this->db->table('driver_messages')->insert([
            'trip_id' => $tripId, 'direction' => 'staff', 'user_id' => 1,
            'body' => 'Noted. Please WhatsApp once you cross.',
            'is_read_by_driver' => 0, 'is_read_by_staff' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->ok('Staff: "Noted. Please WhatsApp once you cross."');
        $this->detail('PWA polls /d/<token>/messages every 15 s → driver sees the reply');
    }

    private function stepDriverPod(int $tripId): void
    {
        $this->header('STEP 13', '📷 Driver uploads POD photo from the PWA');
        $dir = WRITEPATH . 'uploads/trips/' . $tripId;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $stored = 'e2e_pod_' . bin2hex(random_bytes(4)) . '.jpg';
        file_put_contents($dir . '/' . $stored, "demo POD photo bytes");
        $this->db->table('documents')->insert([
            'module_name'         => 'trip',
            'module_ref_id'       => $tripId,
            'document_type'       => 'POD',
            'original_file_name'  => 'POD_signed_consignee.jpg',
            'stored_file_name'    => $stored,
            'file_path'           => 'trips/' . $tripId . '/' . $stored,
            'file_size'           => 21,
            'mime_type'           => 'image/jpeg',
            'source_channel'      => 'upload',
            'verification_status' => 'Pending',
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('trips')->where('id', $tripId)->update([
            'pod_status'      => 'Received',
            'pod_received_at' => date('Y-m-d H:i:s'),
            'current_status'  => 'POD Received',
        ]);
        $this->db->table('trip_status_history')->insert([
            'trip_id' => $tripId, 'old_status' => 'Delivered', 'new_status' => 'POD Received',
            'notes' => 'POD uploaded by driver', 'changed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->ok('POD doc inserted · pod_status=Received · current_status=POD Received');
        $this->detail('No email rule for POD Received by default — POD presence visible on staff trip page');
    }

    private function stepCloseTrip(int $tripId): void
    {
        $this->header('STEP 14', '🔒 Staff closes the trip');
        $this->db->table('trips')->where('id', $tripId)->update(['current_status' => 'Closed']);
        $this->db->table('trip_status_history')->insert([
            'trip_id' => $tripId, 'old_status' => 'POD Received', 'new_status' => 'Closed',
            'notes' => 'Closed by staff', 'changed_at' => date('Y-m-d H:i:s'),
        ]);
        // Mark booking Completed
        $bookingId = (int) $this->db->table('trips')->where('id', $tripId)->get()->getRow('booking_id');
        $this->db->table('bookings')->where('id', $bookingId)->update(['booking_status' => 'Completed']);
        $this->ok("Trip closed · booking marked Completed");
    }

    private function stepInvoice(int $bookingId, int $clientId, int $tripId): int
    {
        $this->header('STEP 15', '🧾 Invoice generated and shared with client');
        $b = $this->db->table('bookings')->where('id', $bookingId)->get()->getRowArray();
        $taxable = (float) $b['final_sell_rate'];
        $gst = $taxable * 0.05;
        $total = round($taxable + $gst, 2);
        $invNo = 'E2E_INV' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $this->db->table('invoices')->insert([
            'invoice_no'       => $invNo,
            'invoice_date'     => date('Y-m-d'),
            'client_id'        => $clientId,
            'booking_id'       => $bookingId,
            'trip_id'          => $tripId,
            'taxable_amount'   => $taxable,
            'igst_amount'      => $gst,
            'gst_treatment'    => 'fcm5',
            'total_amount'     => $total,
            'amount_received'  => 0,
            'balance_due'      => $total,
            'due_date'         => date('Y-m-d', strtotime('+15 days')),
            'invoice_status'   => 'Issued',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        $this->records['invoice'] = $id;
        // Fire invoice_share to the client
        $client = $this->db->table('clients')->where('id', $clientId)->get()->getRowArray();
        (new \App\Libraries\EmailService())->sendTemplate('invoice_share', $client['email'], [
            'company'    => $client['company_name'],
            'invoice_no' => $invNo,
            'total'      => number_format($total, 0),
            'due_date'   => date('d M Y', strtotime('+15 days')),
        ], ['related_module' => 'invoice', 'related_id' => $id, 'related_client_id' => $clientId]);
        $this->ok("Invoice #$id ($invNo) · total ₹" . number_format($total, 0) . " · email sent");
        $this->detail('Trigger: InvoicesController::shareEmail → template `invoice_share`');
        return $id;
    }

    private function stepReceipt(int $invoiceId, int $clientId): void
    {
        $this->header('STEP 16', '💵 Client pays — receipt recorded');
        $inv = $this->db->table('invoices')->where('id', $invoiceId)->get()->getRowArray();
        $this->db->table('receipts')->insert([
            'receipt_date'    => date('Y-m-d'),
            'client_id'       => $clientId,
            'invoice_id'      => $invoiceId,
            'amount_received' => $inv['total_amount'],
            'payment_mode'    => 'NEFT',
            'reference_no'    => 'NEFT' . random_int(100000, 999999),
            'notes'           => 'E2E test payment',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('invoices')->where('id', $invoiceId)->update([
            'amount_received' => $inv['total_amount'],
            'balance_due'     => 0,
            'invoice_status'  => 'Paid',
        ]);
        $this->ok('Receipt logged · invoice marked Paid · balance ₹0');
    }

    /* ────────────────────────── Reporting ────────────────────────── */

    private function printSummary(): void
    {
        CLI::write("\n" . str_repeat('═', 78), 'cyan');
        CLI::write(' SUMMARY · email_logs scoped to this run', 'cyan');
        CLI::write(str_repeat('═', 78), 'cyan');

        $rows = $this->db->table('email_logs')->orderBy('id', 'ASC')->get()->getResultArray();
        if (empty($rows)) {
            CLI::write('  (no emails fired)', 'yellow');
            return;
        }
        $byTpl = $byStatus = [];
        foreach ($rows as $r) {
            $k = $r['template_key'] ?: '(raw)';
            $byTpl[$k] = ($byTpl[$k] ?? 0) + 1;
            $byStatus[$r['status']] = ($byStatus[$r['status']] ?? 0) + 1;
            CLI::write(sprintf('  #%-3d %-26s → %-30s status=%s',
                $r['id'], $r['template_key'] ?: '(raw)', $r['to_email'], $r['status']));
        }
        CLI::write("\n  By template:", 'yellow');
        foreach ($byTpl as $k => $n) CLI::write(sprintf('    %-30s %d', $k, $n));
        CLI::write("\n  By status:", 'yellow');
        foreach ($byStatus as $k => $n) CLI::write(sprintf('    %-30s %d', $k, $n));
        CLI::write(sprintf("\n  Total: %d emails", count($rows)), 'green');
    }

    /* ────────────────────────── Helpers ────────────────────────── */

    private function header(string $tag, string $title): void
    {
        $this->step++;
        CLI::write('');
        CLI::write(str_repeat('─', 78), 'cyan');
        CLI::write(sprintf('  %s · %s', str_pad($tag, 8), $title), 'cyan');
        CLI::write(str_repeat('─', 78), 'cyan');
    }
    private function ok(string $m): void     { CLI::write('  ✓ ' . $m, 'green'); }
    private function detail(string $m): void { CLI::write('    ↳ ' . $m); }

    private function cleanup(): void
    {
        if (!empty($this->records['invoice'])) {
            $this->db->table('receipts')->where('invoice_id', $this->records['invoice'])->delete();
            $this->db->table('invoices')->where('id', $this->records['invoice'])->delete();
        }
        if (!empty($this->records['trip'])) {
            $this->db->table('driver_messages')->where('trip_id', $this->records['trip'])->delete();
            $this->db->table('documents')->where('module_name','trip')->where('module_ref_id', $this->records['trip'])->delete();
            $this->db->table('trip_status_history')->where('trip_id', $this->records['trip'])->delete();
            $this->db->table('trips')->where('id', $this->records['trip'])->delete();
        }
        if (!empty($this->records['booking'])) {
            $this->db->table('bookings')->where('id', $this->records['booking'])->delete();
        }
        if (!empty($this->records['rfq'])) {
            $this->db->table('quotations')->where('rfq_id', $this->records['rfq'])->delete();
            $this->db->table('rfq_vendors')->where('rfq_id', $this->records['rfq'])->delete();
            $this->db->table('rfq_master')->where('id', $this->records['rfq'])->delete();
        }
        if (!empty($this->records['lead'])) $this->db->table('leads')->where('id', $this->records['lead'])->delete();
        if (!empty($this->records['vendors'])) $this->db->table('vendors')->whereIn('id', $this->records['vendors'])->delete();
        if (!empty($this->records['client']))  $this->db->table('clients')->where('id', $this->records['client'])->delete();
        if (!empty($this->records['driver']))  $this->db->table('drivers')->where('id', $this->records['driver'])->delete();
        if (!empty($this->records['vehicle'])) $this->db->table('vehicles')->where('id', $this->records['vehicle'])->delete();
        CLI::write('  cleanup done', 'yellow');
    }
}
