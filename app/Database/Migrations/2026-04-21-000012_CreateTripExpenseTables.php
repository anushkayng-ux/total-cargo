<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTripExpenseTables extends Migration
{
    public function up(): void
    {
        // trip_expense_categories master
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 80],
            'default_is_billable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'          => ['type' => 'INT', 'default' => 0],
            'status'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('trip_expense_categories', true);

        // trip_expenses
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'trip_id'              => ['type' => 'INT', 'unsigned' => true],
            'expense_date'         => ['type' => 'DATE', 'null' => true],
            'category'             => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'amount'               => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'is_billable'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'billed_on_invoice_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'paid_to'              => ['type' => 'ENUM', 'constraint' => ['Driver','Vendor','Direct','Self'], 'default' => 'Direct'],
            'payment_mode'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'reference_no'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'document_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'remarks'              => ['type' => 'TEXT', 'null' => true],
            'created_by'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('trip_id');
        $this->forge->addKey('is_billable');
        $this->forge->addKey('billed_on_invoice_id');
        $this->forge->addKey('category');
        $this->forge->addForeignKey('trip_id', 'trips', 'id', '', 'CASCADE');
        $this->forge->createTable('trip_expenses', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('trip_expenses', true);
        $this->forge->dropTable('trip_expense_categories', true);
    }
}
