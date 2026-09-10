<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * City master: canonical names for every city referenced elsewhere in the
 * app (clients.city, vendors.city, leads.pickup/drop_city, rfq_master.*,
 * vendor_routes.*). Soft master — existing varchar columns stay; the UI
 * runs through an autocomplete that normalises typos to the canonical name.
 * Optional migration to FK columns can come later.
 *
 * Also seeds ~150 top Indian cities + adds a `cities.access` permission so
 * the master can be managed from the admin sidebar.
 */
class AddCitiesMaster extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'state'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'gst_state_code' => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
            'region'     => ['type' => 'ENUM', 'constraint' => ['North','South','East','West','Central','North-East'], 'null' => true],
            'tier'       => ['type' => 'ENUM', 'constraint' => ['1','2','3'], 'null' => true],
            'aliases'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pincode_prefix' => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => true],
            'latitude'   => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'  => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['name','state']);
        $this->forge->addKey('name');
        $this->forge->addKey('state');
        $this->forge->createTable('cities');

        $now = date('Y-m-d H:i:s');
        $db  = \Config\Database::connect();

        // ── Permission entry ──
        $permId = (int) ($db->table('permissions')->where(['module_key' => 'cities', 'action_key' => 'access'])->get()->getRow('id') ?? 0);
        if (!$permId) {
            $db->table('permissions')->insert(['module_key' => 'cities', 'action_key' => 'access', 'label' => 'Cities Master — Access', 'status' => 1]);
            $permId = (int) $db->insertID();
        }
        $adminId = (int) ($db->table('roles')->where('role_key', 'admin')->get()->getRow('id') ?? 0);
        if ($adminId) {
            $rp = $db->table('role_permissions');
            if ($rp->where(['role_id' => $adminId, 'permission_id' => $permId])->countAllResults() === 0) {
                $rp->insert(['role_id' => $adminId, 'permission_id' => $permId,
                    'can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1, 'can_approve' => 1, 'can_export' => 1]);
            }
        }
        // Read-only view for management + ops managers
        foreach (['management','ops_mgr','crm_mgr','pur_mgr','accounts'] as $rk) {
            $rid = (int) ($db->table('roles')->where('role_key', $rk)->get()->getRow('id') ?? 0);
            if (!$rid) continue;
            $exists = $db->table('role_permissions')->where(['role_id' => $rid, 'permission_id' => $permId])->countAllResults();
            if ($exists === 0) {
                $db->table('role_permissions')->insert(['role_id' => $rid, 'permission_id' => $permId,
                    'can_view' => 1, 'can_add' => 1, 'can_edit' => 0, 'can_delete' => 0, 'can_approve' => 0, 'can_export' => 1]);
            }
        }

        // ── Seed: ~150 top Indian cities. Format: [name, state, gst_code, region, tier] ──
        $seed = [
            // ── Tier 1 metros ──
            ['Mumbai',           'Maharashtra',     '27','West','1'],
            ['Delhi',            'Delhi',           '07','North','1'],
            ['Bengaluru',        'Karnataka',       '29','South','1'],
            ['Hyderabad',        'Telangana',       '36','South','1'],
            ['Chennai',          'Tamil Nadu',      '33','South','1'],
            ['Kolkata',          'West Bengal',     '19','East','1'],
            ['Ahmedabad',        'Gujarat',         '24','West','1'],
            ['Pune',             'Maharashtra',     '27','West','1'],
            ['Surat',            'Gujarat',         '24','West','1'],
            ['Jaipur',           'Rajasthan',       '08','North','1'],
            // ── Tier 2 metros ──
            ['Lucknow',          'Uttar Pradesh',   '09','North','2'],
            ['Kanpur',           'Uttar Pradesh',   '09','North','2'],
            ['Nagpur',           'Maharashtra',     '27','West','2'],
            ['Indore',           'Madhya Pradesh',  '23','Central','2'],
            ['Thane',            'Maharashtra',     '27','West','2'],
            ['Bhopal',           'Madhya Pradesh',  '23','Central','2'],
            ['Visakhapatnam',    'Andhra Pradesh',  '37','South','2'],
            ['Patna',            'Bihar',           '10','East','2'],
            ['Vadodara',         'Gujarat',         '24','West','2'],
            ['Ghaziabad',        'Uttar Pradesh',   '09','North','2'],
            ['Ludhiana',         'Punjab',          '03','North','2'],
            ['Agra',             'Uttar Pradesh',   '09','North','2'],
            ['Nashik',           'Maharashtra',     '27','West','2'],
            ['Faridabad',        'Haryana',         '06','North','2'],
            ['Meerut',           'Uttar Pradesh',   '09','North','2'],
            ['Rajkot',           'Gujarat',         '24','West','2'],
            ['Kalyan',           'Maharashtra',     '27','West','2'],
            ['Varanasi',         'Uttar Pradesh',   '09','North','2'],
            ['Aurangabad',       'Maharashtra',     '27','West','2'],
            ['Dhanbad',          'Jharkhand',       '20','East','2'],
            ['Amritsar',         'Punjab',          '03','North','2'],
            ['Navi Mumbai',      'Maharashtra',     '27','West','2'],
            ['Prayagraj',        'Uttar Pradesh',   '09','North','2'],
            ['Howrah',           'West Bengal',     '19','East','2'],
            ['Ranchi',           'Jharkhand',       '20','East','2'],
            ['Gwalior',          'Madhya Pradesh',  '23','Central','2'],
            ['Jabalpur',         'Madhya Pradesh',  '23','Central','2'],
            ['Coimbatore',       'Tamil Nadu',      '33','South','2'],
            ['Vijayawada',       'Andhra Pradesh',  '37','South','2'],
            ['Jodhpur',          'Rajasthan',       '08','North','2'],
            ['Madurai',          'Tamil Nadu',      '33','South','2'],
            ['Raipur',           'Chhattisgarh',    '22','Central','2'],
            ['Kota',             'Rajasthan',       '08','North','2'],
            ['Chandigarh',       'Chandigarh',      '04','North','2'],
            ['Guwahati',         'Assam',           '18','North-East','2'],
            ['Solapur',          'Maharashtra',     '27','West','2'],
            ['Hubballi',         'Karnataka',       '29','South','2'],
            ['Bareilly',         'Uttar Pradesh',   '09','North','2'],
            ['Mysuru',           'Karnataka',       '29','South','2'],
            ['Tiruchirappalli',  'Tamil Nadu',      '33','South','2'],
            ['Tiruppur',         'Tamil Nadu',      '33','South','2'],
            ['Salem',            'Tamil Nadu',      '33','South','2'],
            ['Bhubaneswar',      'Odisha',          '21','East','2'],
            ['Aligarh',          'Uttar Pradesh',   '09','North','2'],
            ['Bhiwandi',         'Maharashtra',     '27','West','2'],
            ['Saharanpur',       'Uttar Pradesh',   '09','North','2'],
            ['Gorakhpur',        'Uttar Pradesh',   '09','North','2'],
            ['Bikaner',          'Rajasthan',       '08','North','2'],
            ['Amravati',         'Maharashtra',     '27','West','2'],
            ['Kochi',            'Kerala',          '32','South','2'],
            ['Thiruvananthapuram','Kerala',         '32','South','2'],
            ['Kannur',           'Kerala',          '32','South','2'],
            ['Kozhikode',        'Kerala',          '32','South','2'],
            ['Thrissur',         'Kerala',          '32','South','2'],
            ['Mangalore',        'Karnataka',       '29','South','2'],
            ['Belgaum',          'Karnataka',       '29','South','2'],
            ['Davanagere',       'Karnataka',       '29','South','2'],
            ['Tumakuru',         'Karnataka',       '29','South','2'],
            ['Tirupati',         'Andhra Pradesh',  '37','South','2'],
            ['Tirunelveli',      'Tamil Nadu',      '33','South','2'],
            ['Erode',            'Tamil Nadu',      '33','South','2'],
            ['Vellore',          'Tamil Nadu',      '33','South','2'],
            ['Warangal',         'Telangana',       '36','South','2'],
            ['Karimnagar',       'Telangana',       '36','South','2'],
            ['Khammam',          'Telangana',       '36','South','2'],
            ['Nizamabad',        'Telangana',       '36','South','2'],
            ['Rajahmundry',      'Andhra Pradesh',  '37','South','2'],
            ['Dehradun',         'Uttarakhand',     '05','North','2'],
            ['Haridwar',         'Uttarakhand',     '05','North','2'],
            ['Shimla',           'Himachal Pradesh','02','North','2'],
            ['Jammu',            'Jammu and Kashmir','01','North','2'],
            ['Srinagar',         'Jammu and Kashmir','01','North','2'],
            ['Imphal',           'Manipur',         '14','North-East','2'],
            ['Aizawl',           'Mizoram',         '15','North-East','2'],
            ['Itanagar',         'Arunachal Pradesh','12','North-East','2'],
            ['Kohima',           'Nagaland',        '13','North-East','2'],
            ['Dimapur',          'Nagaland',        '13','North-East','2'],
            ['Gangtok',          'Sikkim',          '11','North-East','2'],
            ['Agartala',         'Tripura',         '16','North-East','2'],
            ['Shillong',         'Meghalaya',       '17','North-East','2'],
            ['Panaji',           'Goa',             '30','West','2'],
            ['Margao',           'Goa',             '30','West','2'],
            // ── Logistics + tier-3 hubs ──
            ['Gurugram',         'Haryana',         '06','North','2'],
            ['Noida',            'Uttar Pradesh',   '09','North','2'],
            ['Greater Noida',    'Uttar Pradesh',   '09','North','2'],
            ['Sonipat',          'Haryana',         '06','North','3'],
            ['Panipat',          'Haryana',         '06','North','3'],
            ['Karnal',           'Haryana',         '06','North','3'],
            ['Rohtak',           'Haryana',         '06','North','3'],
            ['Hisar',            'Haryana',         '06','North','3'],
            ['Ambala',           'Haryana',         '06','North','3'],
            ['Rewari',           'Haryana',         '06','North','3'],
            ['Bhiwadi',          'Rajasthan',       '08','North','3'],
            ['Neemrana',         'Rajasthan',       '08','North','3'],
            ['Pithampur',        'Madhya Pradesh',  '23','Central','3'],
            ['Manesar',          'Haryana',         '06','North','3'],
            ['Vasai-Virar',      'Maharashtra',     '27','West','2'],
            ['Pimpri-Chinchwad', 'Maharashtra',     '27','West','2'],
            ['Aurangabad',       'Bihar',           '10','East','3'],
            ['Asansol',          'West Bengal',     '19','East','3'],
            ['Siliguri',         'West Bengal',     '19','East','3'],
            ['Durgapur',         'West Bengal',     '19','East','3'],
            ['Cuttack',          'Odisha',          '21','East','3'],
            ['Rourkela',         'Odisha',          '21','East','3'],
            ['Berhampur',        'Odisha',          '21','East','3'],
            ['Sambalpur',        'Odisha',          '21','East','3'],
            ['Bilaspur',         'Chhattisgarh',    '22','Central','3'],
            ['Korba',            'Chhattisgarh',    '22','Central','3'],
            ['Jamshedpur',       'Jharkhand',       '20','East','3'],
            ['Bokaro',           'Jharkhand',       '20','East','3'],
            ['Hazaribagh',       'Jharkhand',       '20','East','3'],
            ['Bhagalpur',        'Bihar',           '10','East','3'],
            ['Muzaffarpur',      'Bihar',           '10','East','3'],
            ['Gaya',             'Bihar',           '10','East','3'],
            ['Darbhanga',        'Bihar',           '10','East','3'],
            ['Ujjain',           'Madhya Pradesh',  '23','Central','3'],
            ['Sagar',            'Madhya Pradesh',  '23','Central','3'],
            ['Satna',            'Madhya Pradesh',  '23','Central','3'],
            ['Rewa',             'Madhya Pradesh',  '23','Central','3'],
            ['Ratlam',           'Madhya Pradesh',  '23','Central','3'],
            ['Jhansi',           'Uttar Pradesh',   '09','North','3'],
            ['Moradabad',        'Uttar Pradesh',   '09','North','3'],
            ['Firozabad',        'Uttar Pradesh',   '09','North','3'],
            ['Mathura',          'Uttar Pradesh',   '09','North','3'],
            ['Muzaffarnagar',    'Uttar Pradesh',   '09','North','3'],
            ['Rampur',           'Uttar Pradesh',   '09','North','3'],
            ['Shahjahanpur',     'Uttar Pradesh',   '09','North','3'],
            ['Hapur',            'Uttar Pradesh',   '09','North','3'],
            ['Sangli',           'Maharashtra',     '27','West','3'],
            ['Latur',            'Maharashtra',     '27','West','3'],
            ['Akola',            'Maharashtra',     '27','West','3'],
            ['Nanded',           'Maharashtra',     '27','West','3'],
            ['Kolhapur',         'Maharashtra',     '27','West','3'],
            ['Jalgaon',          'Maharashtra',     '27','West','3'],
            ['Ahmednagar',       'Maharashtra',     '27','West','3'],
            ['Chandrapur',       'Maharashtra',     '27','West','3'],
            ['Gandhinagar',      'Gujarat',         '24','West','2'],
            ['Anand',            'Gujarat',         '24','West','3'],
            ['Bhavnagar',        'Gujarat',         '24','West','3'],
            ['Jamnagar',         'Gujarat',         '24','West','3'],
            ['Junagadh',         'Gujarat',         '24','West','3'],
            ['Mehsana',          'Gujarat',         '24','West','3'],
            ['Morbi',            'Gujarat',         '24','West','3'],
            ['Nadiad',           'Gujarat',         '24','West','3'],
            ['Bharuch',          'Gujarat',         '24','West','3'],
            ['Vapi',             'Gujarat',         '24','West','3'],
            ['Mundra',           'Gujarat',         '24','West','3'],
            ['Kandla',           'Gujarat',         '24','West','3'],
            ['Pondicherry',      'Puducherry',      '34','South','3'],
            ['Hosur',            'Tamil Nadu',      '33','South','3'],
            ['Vellore',          'Tamil Nadu',      '33','South','3'],
            ['Thanjavur',        'Tamil Nadu',      '33','South','3'],
            ['Dindigul',         'Tamil Nadu',      '33','South','3'],
            ['Nellore',          'Andhra Pradesh',  '37','South','3'],
            ['Kurnool',          'Andhra Pradesh',  '37','South','3'],
            ['Anantapur',        'Andhra Pradesh',  '37','South','3'],
            ['Guntur',           'Andhra Pradesh',  '37','South','3'],
        ];

        $rows = [];
        $seen = [];
        foreach ($seed as [$n, $s, $g, $r, $t]) {
            $k = strtolower($n . '|' . $s);
            if (isset($seen[$k])) continue;
            $seen[$k] = 1;
            $rows[] = [
                'name'           => $n,
                'state'          => $s,
                'gst_state_code' => $g,
                'region'         => $r,
                'tier'           => $t,
                'status'         => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        $db->table('cities')->insertBatch($rows);

        // ── Also harvest any city values already in use that aren't in the seed ──
        // (clients.city, vendors.city, leads.pickup/drop_city, rfq_master.pickup/drop_city)
        $existing = [];
        foreach (['clients','vendors'] as $tbl) {
            $rows = $db->table($tbl)->select('city, state')->where('city IS NOT NULL', null, false)->where('city !=', '')->groupBy('city, state')->get()->getResultArray();
            foreach ($rows as $r) $existing[strtolower(trim($r['city']) . '|' . trim((string) ($r['state'] ?? '')))] = ['name' => trim($r['city']), 'state' => trim((string) ($r['state'] ?? '— Unknown —'))];
        }
        foreach (['leads' => ['pickup_city' => 'pickup_state', 'drop_city' => 'drop_state']] as $tbl => $cols) {
            foreach ($cols as $cityCol => $stateCol) {
                $rows = $db->table($tbl)->select("$cityCol AS city, $stateCol AS state")->where("$cityCol IS NOT NULL", null, false)->where("$cityCol !=", '')->groupBy("$cityCol, $stateCol")->get()->getResultArray();
                foreach ($rows as $r) $existing[strtolower(trim($r['city']) . '|' . trim((string) ($r['state'] ?? '')))] = ['name' => trim($r['city']), 'state' => trim((string) ($r['state'] ?? '— Unknown —'))];
            }
        }
        foreach (['rfq_master'] as $tbl) {
            foreach (['pickup_city','drop_city'] as $col) {
                $rows = $db->table($tbl)->select("$col AS city")->where("$col IS NOT NULL", null, false)->where("$col !=", '')->groupBy($col)->get()->getResultArray();
                foreach ($rows as $r) {
                    $existing[strtolower(trim($r['city']) . '|')] = ['name' => trim($r['city']), 'state' => '— Unknown —'];
                }
            }
        }
        // Backfill: any rows not in seed get added with "— Unknown —" state — admins can correct later
        $newRows = [];
        foreach ($existing as $k => $row) {
            $name  = $row['name'];
            $state = $row['state'] ?: '— Unknown —';
            $exists = $db->table('cities')->where('LOWER(name)', strtolower($name))->countAllResults();
            if ($exists === 0) {
                $newRows[] = [
                    'name' => $name, 'state' => $state,
                    'status' => 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        if ($newRows) $db->table('cities')->insertBatch($newRows);
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $permIds = array_column($db->table('permissions')->where('module_key', 'cities')->get()->getResultArray(), 'id');
        if (!empty($permIds)) {
            $db->table('role_permissions')->whereIn('permission_id', $permIds)->delete();
            $db->table('permissions')->whereIn('id', $permIds)->delete();
        }
        $this->forge->dropTable('cities', true);
    }
}
