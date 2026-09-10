<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'module_name'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'module_ref_id'      => ['type' => 'INT', 'unsigned' => true],
            'document_type'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'original_file_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_file_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_path'          => ['type' => 'VARCHAR', 'constraint' => 400],
            'file_size'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'mime_type'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'source_channel'     => ['type' => 'ENUM', 'constraint' => ['upload', 'whatsapp', 'email', 'system'], 'default' => 'upload'],
            'verification_status'=> ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'Pending'],
            'remarks'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['module_name', 'module_ref_id']);
        $this->forge->addKey('document_type');
        $this->forge->createTable('documents', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('documents', true);
    }
}
