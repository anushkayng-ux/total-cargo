<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRfqVendorQuoteToken extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('rfq_vendors', [
            'quote_token' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'response_status',
            ],
            'quote_token_expires_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'quote_token',
            ],
            'quote_submitted_at' => [
                'type' => 'DATETIME', 'null' => true, 'after' => 'quote_token_expires_at',
            ],
        ]);
        $db = \Config\Database::connect();
        $row = $db->query("SHOW INDEX FROM rfq_vendors WHERE Key_name = 'idx_rfq_vendor_quote_token'")->getRow();
        if ($row === null) {
            $db->query("CREATE INDEX idx_rfq_vendor_quote_token ON rfq_vendors (quote_token)");
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        $db->query("DROP INDEX IF EXISTS idx_rfq_vendor_quote_token ON rfq_vendors");
        $this->forge->dropColumn('rfq_vendors', ['quote_token','quote_token_expires_at','quote_submitted_at']);
    }
}
