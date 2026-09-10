<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Generic per-user preference key/value store. First use is dashboard widget
 * visibility; future uses (notification cadence, saved filters, etc.) reuse
 * the same table with different pref_key namespaces. PRIMARY KEY across
 * (user_id, pref_key) so REPLACE/UPSERT just works.
 */
class UserPrefs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'pref_key'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'pref_value' => ['type' => 'TEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['user_id', 'pref_key']);
        $this->forge->createTable('user_prefs');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_prefs', true);
    }
}
