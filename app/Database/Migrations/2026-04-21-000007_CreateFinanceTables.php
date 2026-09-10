<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceTables extends Migration
{
    public function up(): void
    {
        // invoices
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_no'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'invoice_date'    => ['type' => 'DATE'],
            'client_id'       => ['type' => 'INT', 'unsigned' => true],
            'booking_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'trip_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'taxable_amount'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'cgst_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'sgst_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'igst_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'round_off'       => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0],
            'total_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'amount_received' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'balance_due'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'due_date'        => ['type' => 'DATE', 'null' => true],
            'invoice_status'  => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Draft'],
            'pdf_path'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'irn_no'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'ack_no'          => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'ack_date'        => ['type' => 'DATETIME', 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('invoice_no');
        $this->forge->addKey('client_id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('invoice_status');
        $this->forge->createTable('invoices', true);

        // invoice_items
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id'     => ['type' => 'INT', 'unsigned' => true],
            'description'    => ['type' => 'VARCHAR', 'constraint' => 400],
            'hsn_sac'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'qty'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'rate'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'taxable_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'gst_percent'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'gst_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'total_amount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('invoice_id');
        $this->forge->addForeignKey('invoice_id', 'invoices', 'id', '', 'CASCADE');
        $this->forge->createTable('invoice_items', true);

        // einvoice_logs
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'invoice_id'       => ['type' => 'INT', 'unsigned' => true],
            'request_payload'  => ['type' => 'MEDIUMTEXT', 'null' => true],
            'response_payload' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'irn_status'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('invoice_id');
        $this->forge->addForeignKey('invoice_id', 'invoices', 'id', '', 'CASCADE');
        $this->forge->createTable('einvoice_logs', true);

        // receipts (client payments received)
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'       => ['type' => 'INT', 'unsigned' => true],
            'invoice_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'receipt_date'    => ['type' => 'DATE'],
            'payment_mode'    => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'amount_received' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'reference_no'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('client_id');
        $this->forge->addKey('invoice_id');
        $this->forge->createTable('receipts', true);

        // vendor_bills
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'     => ['type' => 'INT', 'unsigned' => true],
            'trip_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'bill_no'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'bill_date'     => ['type' => 'DATE', 'null' => true],
            'bill_amount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'amount_paid'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'balance_due'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'due_date'      => ['type' => 'DATE', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Open'],
            'document_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('trip_id');
        $this->forge->addKey('status');
        $this->forge->createTable('vendor_bills', true);

        // vendor_payments
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'       => ['type' => 'INT', 'unsigned' => true],
            'vendor_bill_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_date'    => ['type' => 'DATE'],
            'payment_mode'    => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'amount_paid'     => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'reference_no'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('vendor_id');
        $this->forge->addKey('vendor_bill_id');
        $this->forge->createTable('vendor_payments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('vendor_payments', true);
        $this->forge->dropTable('vendor_bills', true);
        $this->forge->dropTable('receipts', true);
        $this->forge->dropTable('einvoice_logs', true);
        $this->forge->dropTable('invoice_items', true);
        $this->forge->dropTable('invoices', true);
    }
}
