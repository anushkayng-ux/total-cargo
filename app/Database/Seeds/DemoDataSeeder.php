<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds a realistic 5-month window of transactions so dashboards, charts and
 * reports have something meaningful to show. Idempotent: wipes its own scope
 * first (but NEVER touches users, roles, permissions, settings, templates).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();

        // ---- 1. Wipe everything that belongs to the demo scope ----
        $db->disableForeignKeyChecks();
        foreach ([
            'gps_logs', 'latest_vehicle_status',
            'trip_expenses', 'ewb_logs',
            'trip_status_history', 'trips',
            'einvoice_logs', 'invoice_items', 'receipts', 'invoices',
            'vendor_payments', 'vendor_bills',
            'documents',
            'quotation_comparison_logs', 'quotations', 'rfq_vendors', 'rfq_master',
            'bookings',
            'lead_status_history', 'lead_followups', 'leads',
            'vehicles', 'drivers', 'vendor_vehicle_types', 'vendor_routes', 'vendors',
            'clients',
            'whatsapp_incoming_messages', 'whatsapp_logs',
            'activity_logs',
        ] as $t) {
            if ($db->tableExists($t)) $db->query("TRUNCATE TABLE `$t`");
        }
        $db->enableForeignKeyChecks();

        // ---- 2. Clients ----
        $clients = [
            ['Acme Traders Pvt Ltd',        'Ravi Kumar',   '9876500001', 'ravi@acme.in',      '27AACCA1234C1ZV', 'Pune',      'Maharashtra'],
            ['Blue Horizon Textiles',       'Priya Shah',   '9876500002', 'priya@bluehz.in',   '27AADCB5678D1Z8', 'Mumbai',    'Maharashtra'],
            ['Coromandel Agro Ltd',         'S. Ramanujam', '9876500003', 'finance@corom.in',  '33AADCC9876E1ZK', 'Chennai',   'Tamil Nadu'],
            ['Delhi Chem Industries',       'Amit Verma',   '9876500004', 'ops@dchem.in',      '07AADCD4321F1ZJ', 'New Delhi', 'Delhi'],
            ['Eastern Steel Works',         'Subrata Bose', '9876500005', 'logistics@est.in',  '19AADCE5555G1ZP', 'Kolkata',   'West Bengal'],
            ['Fortune Foods Ltd',           'Neha Gupta',   '9876500006', 'neha@fortunef.in',  '24AADCF7777H1ZQ', 'Ahmedabad', 'Gujarat'],
            ['Green Valley Packaging',      'Joseph Mathew','9876500007', 'joseph@gvp.in',     '32AADCG3333I1ZM', 'Kochi',     'Kerala'],
            ['Harsha Cements Pvt Ltd',      'Raghav Reddy', '9876500008', 'raghav@harsha.in',  '36AADCH2222J1ZN', 'Hyderabad', 'Telangana'],
        ];
        $clientIds = [];
        foreach ($clients as $i => $c) {
            $db->table('clients')->insert([
                'client_code'  => 'CL' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'company_name' => $c[0], 'contact_name' => $c[1], 'mobile' => $c[2], 'email' => $c[3],
                'gst_no' => $c[4], 'city' => $c[5], 'state' => $c[6],
                'address' => $c[5] . ' industrial area', 'pincode' => '400001',
                'credit_limit' => 500000 + $i * 50000, 'credit_days' => 30, 'status' => 1,
                'created_by' => 1, 'created_at' => $this->daysAgo(150), 'updated_at' => $this->daysAgo(5),
            ]);
            $clientIds[] = (int) $db->insertID();
        }

        // ---- 3. Vendors ----
        $vendors = [
            ['Sharma Roadlines',            'Suresh Sharma',  '9990001111', 'Delhi',     'Delhi',         1, 4.7, 0],
            ['Delhi Truckers LLP',          'Vikas Arora',    '9990001112', 'New Delhi', 'Delhi',         0, 4.0, 0],
            ['Bharat Express Logistics',    'Harpreet Singh', '9990001113', 'Ludhiana',  'Punjab',        1, 4.5, 0],
            ['Maharashtra Cargo Pvt Ltd',   'Ganesh Patil',   '9990001114', 'Pune',      'Maharashtra',   1, 4.6, 0],
            ['Chennai Movers & Packers',    'M. Kumaravel',   '9990001115', 'Chennai',   'Tamil Nadu',    0, 3.8, 0],
            ['Gujarat Freight Carriers',    'Kiran Desai',    '9990001116', 'Surat',     'Gujarat',       1, 4.4, 0],
            ['South India Transports',      'Balaji Iyer',    '9990001117', 'Bangalore', 'Karnataka',    0, 4.2, 0],
            ['East Coast Roadways',         'Dipu Das',       '9990001118', 'Kolkata',   'West Bengal',   0, 3.5, 0],
            ['Royal Haulage Corp',          'Ashok Malik',    '9990001119', 'Jaipur',    'Rajasthan',     0, 4.0, 0],
            ['Jet Speed Logistics',         'Ravinder Yadav', '9990001120', 'Gurgaon',   'Haryana',       0, 2.9, 1],
        ];
        $vendorIds = [];
        foreach ($vendors as $i => $v) {
            $db->table('vendors')->insert([
                'vendor_code' => 'VN' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'company_name' => $v[0], 'owner_name' => $v[1],
                'mobile' => $v[2], 'whatsapp_no' => $v[2],
                'city' => $v[3], 'state' => $v[4], 'pincode' => '110001',
                'gst_no' => '0' . ($i + 1) . 'AAACV' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT) . 'K1ZX',
                'pan_no' => 'AAACV' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT) . 'K',
                'bank_name' => 'HDFC Bank', 'account_no' => '5000' . str_pad((string) (1111 + $i), 8, '0', STR_PAD_LEFT), 'ifsc_code' => 'HDFC0001234',
                'rating' => $v[6], 'is_preferred' => $v[5], 'is_blacklisted' => $v[7], 'status' => 1,
                'created_by' => 1, 'created_at' => $this->daysAgo(150), 'updated_at' => $this->daysAgo(10),
            ]);
            $vendorIds[] = (int) $db->insertID();
        }

        // Vendor route + vehicle matrices
        $routes = [
            ['Pune', 'Delhi'], ['Mumbai', 'Chennai'], ['Chennai', 'Hyderabad'], ['Delhi', 'Kolkata'],
            ['Ahmedabad', 'Mumbai'], ['Bangalore', 'Kochi'], ['Jaipur', 'Mumbai'], ['Ludhiana', 'Mumbai'],
        ];
        $vehicleTypes = ['32ft SXL', '32ft MXL', '20ft Container', '40ft Container', '14ft Closed Body', '22ft Open Body'];
        foreach ($vendorIds as $vid) {
            $pick = array_rand($routes, 3);
            foreach ($pick as $p) {
                $db->table('vendor_routes')->insert([
                    'vendor_id' => $vid, 'pickup_city' => $routes[$p][0], 'drop_city' => $routes[$p][1], 'status' => 1,
                ]);
            }
            $pickv = array_rand($vehicleTypes, 3);
            foreach ($pickv as $p) {
                $db->table('vendor_vehicle_types')->insert([
                    'vendor_id' => $vid, 'vehicle_type' => $vehicleTypes[$p], 'status' => 1,
                ]);
            }
        }

        // ---- 4. Drivers and vehicles ----
        $driverNames = ['Rakesh Yadav', 'Santosh Pawar', 'Karan Singh', 'Balwinder Kaur', 'Ramu Naidu',
                        'Sunil Sharma', 'Mohammed Ishaq', 'Prakash Gowda', 'Ranjit Das', 'Iqbal Khan'];
        $driverIds = [];
        foreach ($driverNames as $i => $n) {
            $db->table('drivers')->insert([
                'vendor_id'      => $vendorIds[$i % count($vendorIds)],
                'driver_name'    => $n,
                'mobile'         => '98700' . str_pad((string) (10000 + $i), 5, '0', STR_PAD_LEFT),
                'license_no'     => 'DL-' . strtoupper(bin2hex(random_bytes(3))),
                'license_expiry' => date('Y-m-d', strtotime('+' . rand(60, 730) . ' days')),
                'status'         => 1,
                'created_at'     => $this->daysAgo(120), 'updated_at' => $this->daysAgo(10),
            ]);
            $driverIds[] = (int) $db->insertID();
        }

        $vehicleIds = [];
        $stateCodes = ['MH12', 'DL01', 'KA05', 'TN09', 'GJ01', 'HR26', 'WB12', 'UP32', 'RJ14', 'TS09'];
        for ($i = 0; $i < 12; $i++) {
            $reg = $stateCodes[$i % count($stateCodes)] . chr(65 + ($i % 26)) . chr(66 + ($i % 25))
                 . str_pad((string) (1000 + $i * 123 % 9000), 4, '0', STR_PAD_LEFT);
            $db->table('vehicles')->insert([
                'vendor_id'        => $vendorIds[$i % count($vendorIds)],
                'vehicle_number'   => $reg,
                'vehicle_type'     => $vehicleTypes[$i % count($vehicleTypes)],
                'rc_no'            => 'RC-' . strtoupper(bin2hex(random_bytes(3))),
                'permit_no'        => 'PER-' . strtoupper(bin2hex(random_bytes(3))),
                'insurance_no'     => 'INS-' . strtoupper(bin2hex(random_bytes(4))),
                'insurance_expiry' => date('Y-m-d', strtotime('+' . rand(30, 365) . ' days')),
                'fitness_expiry'   => date('Y-m-d', strtotime('+' . rand(60, 400) . ' days')),
                'status'           => 1,
                'created_at'       => $this->daysAgo(120), 'updated_at' => $this->daysAgo(5),
            ]);
            $vehicleIds[] = (int) $db->insertID();
        }

        // Map a source id
        $sourceIds = [];
        foreach ($db->table('lead_sources')->get()->getResultArray() as $r) $sourceIds[$r['source_key']] = (int) $r['id'];

        // ---- 5. Leads — spread across 5 months with varied statuses ----
        $statusSpread = [
            'Won'              => 8,   // convert to bookings
            'Lost'             => 4,
            'Closed'           => 2,
            'Sent to Client'   => 3,
            'Negotiation'      => 2,
            'Quote Received'   => 3,
            'Sent to Purchase' => 2,
            'Under Review'     => 2,
            'New'              => 4,
        ];
        $leadRoutes = [
            ['Pune', 'Maharashtra', 'Delhi', 'Delhi', '32ft SXL', 'Textiles', 15],
            ['Mumbai', 'Maharashtra', 'Chennai', 'Tamil Nadu', '32ft SXL', 'FMCG', 18],
            ['Chennai', 'Tamil Nadu', 'Hyderabad', 'Telangana', '20ft Container', 'Electronics', 10],
            ['Delhi', 'Delhi', 'Kolkata', 'West Bengal', '32ft MXL', 'Machinery', 20],
            ['Ahmedabad', 'Gujarat', 'Mumbai', 'Maharashtra', '14ft Closed Body', 'Packaged food', 6],
            ['Bangalore', 'Karnataka', 'Kochi', 'Kerala', '22ft Open Body', 'Auto parts', 12],
            ['Jaipur', 'Rajasthan', 'Mumbai', 'Maharashtra', '32ft SXL', 'Marble slabs', 22],
            ['Ludhiana', 'Punjab', 'Mumbai', 'Maharashtra', '32ft MXL', 'Knitwear', 17],
            ['Hyderabad', 'Telangana', 'Delhi', 'Delhi', '40ft Container', 'Pharma', 8],
            ['Surat', 'Gujarat', 'Chennai', 'Tamil Nadu', '32ft SXL', 'Fabric rolls', 14],
        ];
        $leadIds = [];
        $leadStatusIndex = 1;
        $nowFmt = fn(int $daysAgo) => date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
        foreach ($statusSpread as $status => $count) {
            for ($k = 0; $k < $count; $k++) {
                $lr       = $leadRoutes[array_rand($leadRoutes)];
                $clientId = $clientIds[array_rand($clientIds)];
                $ageDays  = rand(1, 150);
                $leadNo   = 'LD' . str_pad((string) $leadStatusIndex, 5, '0', STR_PAD_LEFT);

                $db->table('leads')->insert([
                    'lead_no'                => $leadNo,
                    'lead_datetime'          => $nowFmt($ageDays),
                    'source_id'              => $sourceIds[array_rand($sourceIds)] ?? null,
                    'client_id'              => $clientId,
                    'company_name'           => null,
                    'client_name'            => null,
                    'mobile'                 => null,
                    'pickup_city'            => $lr[0], 'pickup_state' => $lr[1],
                    'drop_city'              => $lr[2], 'drop_state'   => $lr[3],
                    'vehicle_type_required'  => $lr[4],
                    'material_type'          => $lr[5],
                    'weight'                 => $lr[6],
                    'weight_unit'            => 'TON',
                    'expected_dispatch_date' => date('Y-m-d', strtotime("-$ageDays days +3 days")),
                    'priority'               => ['Low','Normal','High','Urgent'][array_rand(['Low','Normal','High','Urgent'])],
                    'assigned_crm_user_id'   => 1,
                    'current_status'         => $status,
                    'lost_reason'            => $status === 'Lost' ? 'Rate too high' : null,
                    'remarks'                => null,
                    'created_by'             => 1, 'created_at' => $nowFmt($ageDays), 'updated_at' => $nowFmt(max(0, $ageDays - 1)),
                ]);
                $leadId = (int) $db->insertID();

                $db->table('lead_status_history')->insert([
                    'lead_id' => $leadId, 'old_status' => null, 'new_status' => 'New',
                    'remarks' => 'Lead created', 'changed_by' => 1, 'changed_at' => $nowFmt($ageDays),
                ]);
                if ($status !== 'New') {
                    $db->table('lead_status_history')->insert([
                        'lead_id' => $leadId, 'old_status' => 'New', 'new_status' => $status,
                        'remarks' => 'Demo workflow', 'changed_by' => 1, 'changed_at' => $nowFmt(max(0, $ageDays - 1)),
                    ]);
                }
                if (rand(0, 1)) {
                    $db->table('lead_followups')->insert([
                        'lead_id' => $leadId, 'followup_datetime' => $nowFmt(max(0, $ageDays - 1)),
                        'followup_type' => 'Call', 'discussion_notes' => 'Client confirmed requirement.',
                        'next_followup_datetime' => null,
                        'created_by' => 1, 'created_at' => $nowFmt(max(0, $ageDays - 1)),
                    ]);
                }
                $leadIds[$status][] = ['id' => $leadId, 'lead_no' => $leadNo, 'route' => $lr, 'age' => $ageDays, 'client_id' => $clientId];
                $leadStatusIndex++;
            }
        }

        // ---- 6. RFQs (for a sample of leads that progressed past New/Under Review) ----
        $rfqSeed = array_merge(
            $leadIds['Won'] ?? [],
            array_slice($leadIds['Sent to Client']    ?? [], 0, 3),
            array_slice($leadIds['Negotiation']       ?? [], 0, 2),
            array_slice($leadIds['Quote Received']    ?? [], 0, 3),
            array_slice($leadIds['Sent to Purchase']  ?? [], 0, 2),
            array_slice($leadIds['Closed']            ?? [], 0, 2),
        );
        $rfqRows = []; // for booking conversion
        $rfqIndex = 1;
        foreach ($rfqSeed as $lead) {
            $lr     = $lead['route'];
            $age    = $lead['age'];
            $rfqNo  = 'RFQ' . str_pad((string) $rfqIndex, 5, '0', STR_PAD_LEFT);
            $masked = 'REF-' . strtoupper(bin2hex(random_bytes(4)));
            $db->table('rfq_master')->insert([
                'rfq_no'            => $rfqNo,
                'lead_id'           => $lead['id'],
                'masked_reference'  => $masked,
                'pickup_city'       => $lr[0],
                'drop_city'         => $lr[2],
                'vehicle_type'      => $lr[4],
                'material_category' => $lr[5],
                'weight'            => $lr[6],
                'weight_unit'       => 'TON',
                'loading_date'      => date('Y-m-d', strtotime("-$age days +3 days")),
                'status'            => 'Awarded',
                'created_by'        => 1,
                'created_at'        => $nowFmt($age),
                'updated_at'        => $nowFmt(max(0, $age - 1)),
            ]);
            $rfqId = (int) $db->insertID();

            // Pick 3 random vendors for the RFQ
            $picks    = (array) array_rand($vendorIds, 3);
            $pickIds  = array_map(fn($i) => $vendorIds[$i], $picks);
            $quoteAmounts = [];
            $finalIdx = array_rand($pickIds);
            foreach ($pickIds as $idx => $vid) {
                $baseRate = 20000 + $lr[6] * 1500;
                $amount   = round($baseRate + rand(-2000, 4000), 2);
                $quoteAmounts[$idx] = $amount;

                $db->table('rfq_vendors')->insert([
                    'rfq_id' => $rfqId, 'vendor_id' => $vid,
                    'whatsapp_message_id' => 'wamid.DEMO' . str_pad((string) ($rfqIndex * 10 + $idx), 6, '0', STR_PAD_LEFT),
                    'sent_at' => $nowFmt($age), 'reminder_count' => 0, 'last_reminder_at' => null,
                    'response_status' => 'Received', 'responded_at' => $nowFmt(max(0, $age - 1)),
                ]);
                $db->table('quotations')->insert([
                    'rfq_id' => $rfqId, 'vendor_id' => $vid,
                    'quote_amount' => $amount,
                    'availability_notes' => 'Available',
                    'transit_days' => rand(2, 6),
                    'quote_valid_till' => date('Y-m-d', strtotime("-$age days +5 days")),
                    'response_source' => 'whatsapp',
                    'is_shortlisted'  => $idx === $finalIdx ? 1 : 0,
                    'is_final_selected' => $idx === $finalIdx ? 1 : 0,
                    'response_time_minutes' => rand(15, 240),
                    'remarks' => 'Demo quote',
                    'created_at' => $nowFmt(max(0, $age - 1)), 'updated_at' => $nowFmt(max(0, $age - 1)),
                ]);
            }
            $rfqRows[] = [
                'rfq_id'          => $rfqId,
                'lead_id'         => $lead['id'],
                'client_id'       => $lead['client_id'],
                'route_text'      => $lr[0] . ' → ' . $lr[2],
                'vehicle_type'    => $lr[4],
                'load_details'    => $lr[5] . ' · ' . $lr[6] . ' TON',
                'loading_date'    => date('Y-m-d', strtotime("-$age days +3 days")),
                'final_buy'       => $quoteAmounts[$finalIdx],
                'final_vendor_id' => $pickIds[$finalIdx],
                'age'             => $age,
                'won'             => in_array($lead, $leadIds['Won'] ?? [], true),
            ];
            $rfqIndex++;
        }

        // ---- 7. Bookings + Trips + Invoices + Receipts + Vendor bills + Payments ----
        $bookingIndex = 1; $tripIndex = 1; $invoiceIndex = 1; $billIndex = 1;
        $activeTripsForGps = [];
        foreach ($rfqRows as $r) {
            if (!$r['won']) continue; // only awarded/Won leads become bookings
            $age         = $r['age'];
            $buy         = (float) $r['final_buy'];
            $markup      = rand(8, 18) / 100;               // 8-18% margin
            $sell        = round($buy * (1 + $markup), -1); // round to 10
            $margin      = round($sell - $buy, 2);
            $bookingNo   = 'BK' . str_pad((string) $bookingIndex, 5, '0', STR_PAD_LEFT);
            $bookingAge  = max(0, $age - 1);

            // Distribute booking states realistically
            $bookingFate = $bookingIndex <= 5 ? 'completed' : ($bookingIndex <= 7 ? 'handed_over' : 'approved');

            // Build a plausible consignee from the drop city
            $dropCity   = explode(' → ', $r['route_text'])[1] ?? '—';
            $consigneeNames = [
                'Pune'      => 'Ashoka Industries', 'Mumbai'    => 'Coastal Distributors',
                'Chennai'   => 'South Metro Agencies', 'Delhi'    => 'NCR Wholesale Depot',
                'Ahmedabad' => 'Gujarat Prime Traders', 'Kolkata' => 'Bay Mercantile',
                'Bangalore' => 'Karnataka Enterprises', 'Kochi'   => 'Malabar Goods',
                'Jaipur'    => 'Pink City Depot', 'Ludhiana'    => 'Punjab Stocks',
                'Hyderabad' => 'Deccan Distributors', 'Surat'    => 'Silk Route Traders',
            ];
            $consigneeName = $consigneeNames[$dropCity] ?? ($dropCity . ' Traders');

            $db->table('bookings')->insert([
                'booking_no'        => $bookingNo,
                'lead_id'           => $r['lead_id'],
                'client_id'         => $r['client_id'],
                'vendor_id'         => $r['final_vendor_id'],
                'final_buy_rate'    => $buy,
                'final_sell_rate'   => $sell,
                'margin_amount'     => $margin,
                'route_text'        => $r['route_text'],
                'vehicle_type'      => $r['vehicle_type'],
                'load_details'      => $r['load_details'],
                'loading_date'      => $r['loading_date'],
                'billing_party'     => 'Same as client',
                'consignee_name'    => $consigneeName,
                'consignee_mobile'  => '9200' . str_pad((string) (100000 + $bookingIndex * 37), 6, '0', STR_PAD_LEFT),
                'consignee_address' => $dropCity . ' wholesale market, near transport nagar',
                'consignee_gstin'   => null,
                'freight_mode'      => ['To Pay', 'Paid', 'To Be Billed'][$bookingIndex % 3],
                'instructions'      => 'Handle with care. Share LR on WhatsApp. Unload with care at gate.',
                'booking_status'    => match ($bookingFate) { 'completed' => 'Completed', 'handed_over' => 'Handed Over', default => 'Approved' },
                'approved_by'       => 1,
                'approved_at'       => $nowFmt($bookingAge),
                'created_by'        => 1,
                'created_at'        => $nowFmt($bookingAge), 'updated_at' => $nowFmt(max(0, $bookingAge - 1)),
            ]);
            $bookingId = (int) $db->insertID();

            // Trip
            $tripNo   = 'TR' . str_pad((string) $tripIndex, 5, '0', STR_PAD_LEFT);
            $parts    = explode(' → ', $r['route_text']);
            $tripStatus = match ($bookingFate) {
                'completed'   => 'Closed',
                'handed_over' => ['In Transit', 'Arrived', 'Unloading', 'Delivered'][array_rand(['In Transit', 'Arrived', 'Unloading', 'Delivered'])],
                default       => 'Vehicle Placed',
            };
            $podStatus = in_array($tripStatus, ['Closed', 'Delivered', 'POD Received'], true) ? 'Received' : 'Pending';
            $dispatch  = $tripStatus !== 'Vehicle Placed' ? $nowFmt(max(0, $bookingAge - 1)) : null;
            $deliveryA = in_array($tripStatus, ['Delivered', 'POD Received', 'Closed'], true) ? $nowFmt(max(0, $bookingAge - 2)) : null;
            $driverIdx = $bookingIndex % count($driverIds);
            $vehicleIdx= $bookingIndex % count($vehicleIds);

            // Fetch driver/vehicle details for denormalized fields
            $driverRow  = $db->table('drivers')->where('id', $driverIds[$driverIdx])->get()->getRowArray();
            $vehicleRow = $db->table('vehicles')->where('id', $vehicleIds[$vehicleIdx])->get()->getRowArray();

            $lrNo = 'LR' . str_pad((string) $tripIndex, 5, '0', STR_PAD_LEFT);
            $db->table('trips')->insert([
                'trip_no'         => $tripNo,
                'lr_no'           => $lrNo,
                'lr_generated_at' => $nowFmt($bookingAge),
                'booking_id'      => $bookingId,
                'vendor_id'       => $r['final_vendor_id'],
                'driver_id'       => $driverIds[$driverIdx],
                'vehicle_id'      => $vehicleIds[$vehicleIdx],
                'driver_name'     => $driverRow['driver_name'],
                'driver_mobile'   => $driverRow['mobile'],
                'vehicle_number'  => $vehicleRow['vehicle_number'],
                'loading_point'   => $parts[0] ?? null,
                'unloading_point' => $parts[1] ?? null,
                'dispatch_datetime' => $dispatch,
                'delivery_datetime' => $deliveryA,
                'current_status'  => $tripStatus,
                'pod_status'      => $podStatus,
                'pod_received_at' => $podStatus === 'Received' ? $nowFmt(max(0, $bookingAge - 2)) : null,
                'remarks'         => null,
                'created_by'      => 1,
                'created_at'      => $nowFmt($bookingAge), 'updated_at' => $nowFmt(max(0, $bookingAge - 1)),
            ]);
            $tripId = (int) $db->insertID();

            $db->table('trip_status_history')->insert([
                'trip_id' => $tripId, 'old_status' => null, 'new_status' => 'Booking Created',
                'notes' => 'Handed over from booking', 'changed_by' => 1, 'changed_at' => $nowFmt($bookingAge),
            ]);
            if ($tripStatus !== 'Booking Created') {
                $db->table('trip_status_history')->insert([
                    'trip_id' => $tripId, 'old_status' => 'Booking Created', 'new_status' => $tripStatus,
                    'notes' => 'Auto-advanced', 'changed_by' => 1, 'changed_at' => $nowFmt(max(0, $bookingAge - 1)),
                ]);
            }

            if (in_array($tripStatus, ['In Transit', 'Arrived', 'Unloading', 'Delivered', 'POD Received'], true)) {
                $activeTripsForGps[] = ['id' => $tripId, 'vehicle' => $vehicleRow['vehicle_number'], 'route' => $parts, 'age' => $bookingAge];
            }

            // Invoice only for handed_over + completed
            if ($bookingFate !== 'approved') {
                $invNo     = 'INV' . str_pad((string) $invoiceIndex, 5, '0', STR_PAD_LEFT);
                $invDate   = $nowFmt(max(0, $bookingAge - 2));
                $dueDate   = date('Y-m-d', strtotime($invDate . ' +15 days'));
                // Pick client's state for GST split
                $client    = $db->table('clients')->where('id', $r['client_id'])->get()->getRowArray();
                $interstate= ($client['state'] !== 'Maharashtra');
                $gstPc     = 5;
                $taxable   = $sell;
                $gstAmt    = round($taxable * $gstPc / 100, 2);
                $cgst      = $interstate ? 0 : round($gstAmt / 2, 2);
                $sgst      = $interstate ? 0 : round($gstAmt / 2, 2);
                $igst      = $interstate ? round($gstAmt, 2) : 0;
                $pre       = round($taxable + $cgst + $sgst + $igst, 2);
                $total     = round($pre);
                $roundOff  = round($total - $pre, 2);

                // Payment pattern: completed -> fully paid; handed_over -> partially (50%)
                $received  = $bookingFate === 'completed' ? $total : round($total * 0.5, 2);
                $balance   = round($total - $received, 2);
                $invStatus = $bookingFate === 'completed' ? 'Paid' : ($received > 0 ? 'Partially Paid' : 'Issued');

                $db->table('invoices')->insert([
                    'invoice_no'      => $invNo,
                    'invoice_date'    => substr($invDate, 0, 10),
                    'client_id'       => $r['client_id'],
                    'booking_id'      => $bookingId,
                    'trip_id'         => $tripId,
                    'taxable_amount'  => $taxable,
                    'cgst_amount'     => $cgst, 'sgst_amount' => $sgst, 'igst_amount' => $igst,
                    'round_off'       => $roundOff, 'total_amount' => $total,
                    'amount_received' => $received, 'balance_due' => $balance,
                    'due_date'        => $dueDate,
                    'invoice_status'  => $invStatus,
                    'notes'           => 'Freight charges as per booking.',
                    'created_by'      => 1, 'created_at' => $invDate, 'updated_at' => $invDate,
                ]);
                $invoiceId = (int) $db->insertID();

                $db->table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'description' => 'Freight ' . $r['route_text'] . ' · ' . $vehicleRow['vehicle_number'] . ' · ' . $r['load_details'],
                    'hsn_sac' => '996791', 'qty' => 1, 'rate' => $sell,
                    'taxable_amount' => $taxable, 'gst_percent' => $gstPc,
                    'gst_amount' => $gstAmt, 'total_amount' => round($taxable + $gstAmt, 2),
                ]);

                if ($received > 0) {
                    $db->table('receipts')->insert([
                        'client_id' => $r['client_id'], 'invoice_id' => $invoiceId,
                        'receipt_date' => date('Y-m-d', strtotime($invDate . ' +7 days')),
                        'payment_mode' => ['UPI','NEFT','RTGS','Bank Transfer'][array_rand(['UPI','NEFT','RTGS','Bank Transfer'])],
                        'amount_received' => $received,
                        'reference_no' => 'TXN-' . strtoupper(bin2hex(random_bytes(3))),
                        'notes' => $bookingFate === 'completed' ? 'Final payment' : 'Part payment',
                        'created_by' => 1,
                        'created_at' => date('Y-m-d H:i:s', strtotime($invDate . ' +7 days')),
                    ]);
                }
                $invoiceIndex++;

                // Vendor bill
                $billNo   = 'VB' . str_pad((string) $billIndex, 5, '0', STR_PAD_LEFT);
                $billDate = $invDate;
                $db->table('vendor_bills')->insert([
                    'vendor_id'   => $r['final_vendor_id'],
                    'trip_id'     => $tripId,
                    'bill_no'     => $billNo,
                    'bill_date'   => substr($billDate, 0, 10),
                    'bill_amount' => $buy,
                    'amount_paid' => $bookingFate === 'completed' ? $buy : round($buy * 0.6, 2),
                    'balance_due' => $bookingFate === 'completed' ? 0 : round($buy * 0.4, 2),
                    'due_date'    => date('Y-m-d', strtotime($billDate . ' +15 days')),
                    'status'      => $bookingFate === 'completed' ? 'Paid' : 'Partially Paid',
                    'notes'       => 'Freight settlement',
                    'created_by'  => 1,
                    'created_at'  => $billDate, 'updated_at' => $billDate,
                ]);
                $billId = (int) $db->insertID();

                $paidAmount = $bookingFate === 'completed' ? $buy : round($buy * 0.6, 2);
                $db->table('vendor_payments')->insert([
                    'vendor_id' => $r['final_vendor_id'],
                    'vendor_bill_id' => $billId,
                    'payment_date' => date('Y-m-d', strtotime($billDate . ' +10 days')),
                    'payment_mode' => 'NEFT',
                    'amount_paid'  => $paidAmount,
                    'reference_no' => 'NEFT-' . strtoupper(bin2hex(random_bytes(3))),
                    'notes' => $bookingFate === 'completed' ? 'Full settlement' : 'Advance',
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime($billDate . ' +10 days')),
                ]);
                $billIndex++;
            }

            $bookingIndex++; $tripIndex++;
        }

        // ---- 8. GPS trails + latest status for active trips ----
        $pts = [
            ['Pune',      [18.5204, 73.8567]],
            ['Mumbai',    [19.0760, 72.8777]],
            ['Ahmedabad', [23.0225, 72.5714]],
            ['Jaipur',    [26.9124, 75.7873]],
            ['Delhi',     [28.6139, 77.2090]],
            ['Kolkata',   [22.5726, 88.3639]],
            ['Chennai',   [13.0827, 80.2707]],
            ['Bangalore', [12.9716, 77.5946]],
            ['Hyderabad', [17.3850, 78.4867]],
            ['Kochi',     [9.9312,  76.2673]],
            ['Surat',     [21.1702, 72.8311]],
            ['Ludhiana',  [30.9010, 75.8573]],
        ];
        $latLook = [];
        foreach ($pts as $p) $latLook[$p[0]] = $p[1];

        foreach ($activeTripsForGps as $t) {
            $from = $latLook[$t['route'][0] ?? ''] ?? [22.9734, 78.6569];
            $to   = $latLook[$t['route'][1] ?? ''] ?? [28.6139, 77.2090];
            // Interpolate 6 points
            for ($i = 0; $i < 6; $i++) {
                $frac = $i / 5;
                $lat  = round($from[0] + ($to[0] - $from[0]) * $frac, 5);
                $lng  = round($from[1] + ($to[1] - $from[1]) * $frac, 5);
                $speed = $i === 0 ? 0 : ($i === 5 ? 15 : rand(45, 70));
                $tsAgo = max(0, $t['age'] - ($i * 0.2));
                $ts    = $nowFmt((int) floor($tsAgo));
                $db->table('gps_logs')->insert([
                    'trip_id' => $t['id'], 'vehicle_number' => $t['vehicle'],
                    'latitude' => $lat, 'longitude' => $lng,
                    'gps_timestamp' => $ts, 'speed' => $speed,
                    'address' => ($i === 5 ? ($t['route'][1] ?? 'Drop') : ($t['route'][0] ?? 'Start')) . ' — demo',
                    'raw_payload' => null, 'created_at' => $ts,
                ]);
            }
            $db->table('latest_vehicle_status')->insert([
                'trip_id' => $t['id'], 'vehicle_number' => $t['vehicle'],
                'latitude' => $to[0], 'longitude' => $to[1],
                'gps_timestamp' => $nowFmt(0),
                'speed' => 15, 'address' => ($t['route'][1] ?? 'Drop') . ' — near unloading',
                'eta_text' => 'Approaching drop point',
                'delay_flag' => rand(0, 4) === 0 ? 1 : 0,
                'updated_at' => $nowFmt(0),
            ]);
        }

        // ---- 9. Sample document records ----
        $existingPod = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'trips' . DIRECTORY_SEPARATOR . '1';
        $samplePdfPath = null;
        if (is_dir($existingPod)) {
            foreach (scandir($existingPod) as $f) {
                if (substr($f, -4) === '.pdf') { $samplePdfPath = 'trips/1/' . $f; break; }
            }
        }

        $completedTrips = $db->table('trips')
            ->select('id, trip_no, pod_received_at')
            ->where('pod_status', 'Received')
            ->get()->getResultArray();

        foreach ($completedTrips as $idx => $ct) {
            $status = ['Pending', 'Verified', 'Verified', 'Pending'][$idx % 4];
            $db->table('documents')->insert([
                'module_name'         => 'trip',
                'module_ref_id'       => (int) $ct['id'],
                'document_type'       => 'POD',
                'original_file_name'  => 'POD-' . $ct['trip_no'] . '.pdf',
                'stored_file_name'    => 'pod-sample.pdf',
                'file_path'           => $samplePdfPath ?: ('trips/' . $ct['id'] . '/pod-sample.pdf'),
                'file_size'           => 24576 + rand(0, 50000),
                'mime_type'           => 'application/pdf',
                'source_channel'      => ($idx % 2 === 0) ? 'upload' : 'whatsapp',
                'verification_status' => $status,
                'remarks'             => $status === 'Verified' ? 'Signed and stamped POD received.' : 'Awaiting verification.',
                'uploaded_by'         => 1,
                'created_at'          => (string) ($ct['pod_received_at'] ?: $nowFmt(2)),
            ]);
        }

        foreach (array_slice($completedTrips, 0, 2) as $ct) {
            $db->table('documents')->insert([
                'module_name'   => 'trip', 'module_ref_id' => (int) $ct['id'],
                'document_type' => 'LR', 'original_file_name' => 'LR-' . $ct['trip_no'] . '.pdf',
                'stored_file_name' => 'lr-sample.pdf',
                'file_path'     => $samplePdfPath ?: ('trips/' . $ct['id'] . '/lr-sample.pdf'),
                'file_size'     => 18000, 'mime_type' => 'application/pdf',
                'source_channel'=> 'upload', 'verification_status' => 'Verified',
                'remarks'       => 'Consignor copy.', 'uploaded_by' => 1,
                'created_at'    => $nowFmt(4),
            ]);
        }

        $firstVendor = $db->table('vendors')->select('id')->orderBy('id', 'ASC')->limit(1)->get()->getRowArray();
        if ($firstVendor) {
            $db->table('documents')->insert([
                'module_name'  => 'vendor', 'module_ref_id' => (int) $firstVendor['id'],
                'document_type'=> 'Insurance', 'original_file_name' => 'vendor-insurance.pdf',
                'stored_file_name' => 'ins-sample.pdf',
                'file_path'    => $samplePdfPath ?: 'vendor/insurance-sample.pdf',
                'file_size'    => 32000, 'mime_type' => 'application/pdf',
                'source_channel' => 'upload', 'verification_status' => 'Verified',
                'remarks'      => 'Valid till next year.', 'uploaded_by' => 1,
                'created_at'   => $nowFmt(30),
            ]);
        }

        // ---- 10. Audit log entries ----
        $audit = [];
        $ua    = 'Mozilla/5.0 TPT Demo Seed';
        $ip    = '127.0.0.1';
        $bucket = function ($module, $refs, $action, $desc, $baseAge) use (&$audit, $ua, $ip) {
            foreach ($refs as $id) {
                $audit[] = [
                    'module_name'        => $module,
                    'module_ref_id'      => (int) $id,
                    'action_type'        => $action,
                    'action_description' => $desc . ' #' . $id,
                    'old_value_json'     => null,
                    'new_value_json'     => null,
                    'user_id'            => 1,
                    'ip_address'         => $ip,
                    'user_agent'         => $ua,
                    'created_at'         => date('Y-m-d H:i:s', strtotime("-" . max(1, $baseAge + rand(-2, 2)) . " days +" . rand(1, 23) . " hours")),
                ];
            }
        };

        $leadIdsAll = array_column($db->table('leads')->select('id')->get()->getResultArray(), 'id');
        $rfqIdsAll  = array_column($db->table('rfq_master')->select('id')->get()->getResultArray(), 'id');
        $bookIdsAll = array_column($db->table('bookings')->select('id')->get()->getResultArray(), 'id');
        $tripIdsAll = array_column($db->table('trips')->select('id')->get()->getResultArray(), 'id');
        $invIdsAll  = array_column($db->table('invoices')->select('id')->get()->getResultArray(), 'id');
        $vbIdsAll   = array_column($db->table('vendor_bills')->select('id')->get()->getResultArray(), 'id');

        $bucket('auth',        [1],                             'login',    'User admin logged in',                         1);
        $bucket('lead',        array_slice($leadIdsAll, 0, 10), 'create',   'Lead captured',                                60);
        $bucket('lead',        array_slice($leadIdsAll, 0, 5),  'status',   'Lead status updated',                          10);
        $bucket('rfq',         $rfqIdsAll,                      'create',   'RFQ raised',                                   45);
        $bucket('rfq',         array_slice($rfqIdsAll, 0, 6),   'dispatch', 'WhatsApp RFQ dispatched to 3 vendors — RFQ',   30);
        $bucket('quotation',   array_slice($rfqIdsAll, 0, 4),   'approve',  'Quotation awarded for RFQ',                    15);
        $bucket('booking',     $bookIdsAll,                     'create',   'Booking created',                              30);
        $bucket('booking',     array_slice($bookIdsAll, 0, 6),  'approve',  'Booking approved',                             15);
        $bucket('trip',        $tripIdsAll,                     'create',   'Trip opened',                                  15);
        $bucket('trip',        array_slice($tripIdsAll, 0, 5),  'status',   'Trip status advanced',                         10);
        $bucket('invoice',     $invIdsAll,                      'create',   'Invoice generated',                            10);
        $bucket('invoice',     array_slice($invIdsAll, 0, 4),   'approve',  'Invoice finalized',                             8);
        $bucket('receipt',     $invIdsAll,                      'create',   'Client receipt recorded against invoice',       5);
        $bucket('vendor_bill', $vbIdsAll,                       'create',   'Vendor bill posted',                            8);
        $bucket('vendor_bill', array_slice($vbIdsAll, 0, 4),    'update',   'Vendor payment captured on bill',               3);

        if (!empty($audit)) {
            $db->table('activity_logs')->insertBatch($audit);
        }

        // ---- 11. EWB numbers on completed / handed-over trips ----
        $completedTripRows = $db->table('trips')
            ->select('id, trip_no, created_at, vehicle_number')
            ->whereIn('current_status', ['Closed', 'Delivered', 'POD Received', 'In Transit', 'Arrived', 'Unloading'])
            ->get()->getResultArray();
        foreach ($completedTripRows as $idx => $ct) {
            // 12-digit numeric EWB, realistic format (EWB numbers are 12 digits in India)
            $ewb     = sprintf('%012d', 181020260000 + (int) $ct['id'] * 17 + $idx);
            $genAt   = $ct['created_at'] ?: $nowFmt(5);
            $valid   = date('Y-m-d H:i:s', strtotime($genAt . ' +72 hours')); // 3-day validity typical for mid-distance
            $db->table('trips')->where('id', (int) $ct['id'])->update([
                'ewb_no'          => $ewb,
                'ewb_date'        => $genAt,
                'ewb_valid_until' => $valid,
                'ewb_status'      => 'Active',
            ]);
            $db->table('ewb_logs')->insert([
                'trip_id' => (int) $ct['id'], 'action' => 'manual',
                'request_payload' => json_encode(['ewbNo' => $ewb, 'source' => 'demo-seed']),
                'response_payload'=> null, 'status' => 'Success',
                'created_at' => $genAt,
            ]);
        }

        // ---- 12. Trip expenses — 3-5 per trip, mix internal/billable ----
        $tripRows = $db->table('trips')->select('id, booking_id, created_at')->get()->getResultArray();
        $catRows  = $db->table('trip_expense_categories')->where('status', 1)->get()->getResultArray();
        $catsByBillable = ['int' => [], 'bil' => []];
        foreach ($catRows as $c) {
            $catsByBillable[(int) $c['default_is_billable'] === 1 ? 'bil' : 'int'][] = $c['name'];
        }

        $expenseMatrix = [
            ['Toll / FASTag',        'Toll expenses en route',               0, 'Driver',  'Cash', [1500, 5500]],
            ['Driver Bhatta',        '3-day trip allowance',                  0, 'Driver',  'Cash', [2000, 6000]],
            ['Diesel / Fuel',        'Diesel top-up',                         0, 'Driver',  'Cash', [4000, 14000]],
            ['Loading Charges',      'Labour charges at loading point',       1, 'Direct',  'Cash', [800, 3500]],
            ['Unloading Charges',    'Hamali at destination',                 1, 'Direct',  'Cash', [1200, 4500]],
            ['Weighbridge',          'Dharam Kanta weighment',                1, 'Direct',  'Cash', [150, 400]],
            ['Detention',            'Detained at unloading over 24h',        1, 'Direct',  'UPI',  [2500, 8000]],
            ['Parking',              'Overnight parking',                     0, 'Driver',  'Cash', [150, 500]],
            ['Escort / Pilot',       'Pilot vehicle for ODC load',            1, 'Vendor',  'NEFT', [3000, 9000]],
        ];

        $bookingInvoices = [];
        foreach ($db->table('invoices')->select('booking_id, id')->get()->getResultArray() as $r) {
            $bookingInvoices[(int) $r['booking_id']] = (int) $r['id'];
        }

        foreach ($tripRows as $i => $trip) {
            $count = rand(3, 5);
            $shuffled = $expenseMatrix;
            shuffle($shuffled);
            for ($k = 0; $k < $count; $k++) {
                $tpl = $shuffled[$k % count($shuffled)];
                $amt = rand($tpl[5][0], $tpl[5][1]);
                $date = date('Y-m-d', strtotime(($trip['created_at'] ?: $nowFmt(5)) . ' +' . rand(0, 2) . ' days'));

                // About 70% of billable expenses get billed onto their invoice
                $billedInvoiceId = null;
                $isBillable = (int) $tpl[2];
                if ($isBillable === 1 && rand(1, 10) <= 7) {
                    $billedInvoiceId = $bookingInvoices[(int) $trip['booking_id']] ?? null;
                }

                $db->table('trip_expenses')->insert([
                    'trip_id'       => (int) $trip['id'],
                    'expense_date'  => $date,
                    'category'      => $tpl[0],
                    'description'   => $tpl[1],
                    'amount'        => $amt,
                    'is_billable'   => $isBillable,
                    'billed_on_invoice_id' => $billedInvoiceId,
                    'paid_to'       => $tpl[3],
                    'payment_mode'  => $tpl[4],
                    'reference_no'  => 'VCH-' . strtoupper(bin2hex(random_bytes(2))),
                    'remarks'       => null,
                    'created_by'    => 1,
                    'created_at'    => $date . ' 10:00:00',
                    'updated_at'    => $date . ' 10:00:00',
                ]);
            }
        }
    }

    private function daysAgo(int $d): string
    {
        return date('Y-m-d H:i:s', strtotime("-$d days"));
    }
}
