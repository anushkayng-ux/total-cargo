<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDispatchPackFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'consignee_name'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'billing_party'],
            'consignee_mobile'  => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'consignee_name'],
            'consignee_address' => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true, 'after' => 'consignee_mobile'],
            'consignee_gstin'   => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'consignee_address'],
            'freight_mode'      => ['type' => 'ENUM', 'constraint' => ['To Pay', 'Paid', 'To Be Billed'], 'default' => 'To Be Billed', 'after' => 'consignee_gstin'],
        ]);

        $this->forge->addColumn('trips', [
            'lr_no'            => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true, 'after' => 'trip_no'],
            'lr_generated_at'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'lr_no'],
        ]);
        $this->db->query("ALTER TABLE `trips` ADD UNIQUE KEY `uniq_lr_no` (`lr_no`)");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `trips` DROP INDEX `uniq_lr_no`");
        $this->forge->dropColumn('trips', ['lr_no', 'lr_generated_at']);
        $this->forge->dropColumn('bookings', ['consignee_name', 'consignee_mobile', 'consignee_address', 'consignee_gstin', 'freight_mode']);
    }
}
