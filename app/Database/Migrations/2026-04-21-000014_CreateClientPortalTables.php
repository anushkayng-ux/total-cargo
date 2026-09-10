<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientPortalTables extends Migration
{
    public function up(): void
    {
        // client_users — login accounts that belong to a client
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'            => ['type' => 'INT', 'unsigned' => true],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'mobile'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'password_hash'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // Owner = full client-side rights, Booker = can submit booking requests, Accounts = invoices/ledger only, Viewer = read only
            'portal_role'          => ['type' => 'ENUM', 'constraint' => ['Owner','Booker','Accounts','Viewer'], 'default' => 'Owner'],
            'status'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'failed_attempts'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'locked_until'         => ['type' => 'DATETIME', 'null' => true],
            'last_login_at'        => ['type' => 'DATETIME', 'null' => true],
            'last_login_ip'        => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'notify_whatsapp'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notify_email'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('client_id');
        $this->forge->addKey('mobile');
        $this->forge->addForeignKey('client_id', 'clients', 'id', '', 'CASCADE');
        $this->forge->createTable('client_users', true);

        // client_user_invites — token-based invites issued by staff
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'client_id'   => ['type' => 'INT', 'unsigned' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'mobile'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'portal_role' => ['type' => 'ENUM', 'constraint' => ['Owner','Booker','Accounts','Viewer'], 'default' => 'Owner'],
            'token'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'expires_at'  => ['type' => 'DATETIME', 'null' => true],
            'accepted_at' => ['type' => 'DATETIME', 'null' => true],
            'invited_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('client_id');
        $this->forge->addForeignKey('client_id', 'clients', 'id', '', 'CASCADE');
        $this->forge->createTable('client_user_invites', true);

        // client_user_login_logs — every login attempt, success or failure
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'client_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'email_tried'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'success'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reason'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('client_user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('client_user_login_logs', true);

        // ALTER clients — add portal flags + KYC
        $this->forge->addColumn('clients', [
            'portal_enabled' => [
                'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'status',
            ],
            'kyc_status' => [
                'type' => 'ENUM', 'constraint' => ['Pending','Verified','Rejected'],
                'default' => 'Pending', 'after' => 'portal_enabled',
            ],
            'portal_notes' => [
                'type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'kyc_status',
            ],
        ]);

        // ALTER activity_logs — distinguish staff vs client actor
        $this->forge->addColumn('activity_logs', [
            'actor_type' => [
                'type' => 'VARCHAR', 'constraint' => 20, 'default' => 'staff', 'after' => 'user_id',
            ],
            'client_id' => [
                'type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'actor_type',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('activity_logs', ['actor_type', 'client_id']);
        $this->forge->dropColumn('clients', ['portal_enabled', 'kyc_status', 'portal_notes']);
        $this->forge->dropTable('client_user_login_logs', true);
        $this->forge->dropTable('client_user_invites', true);
        $this->forge->dropTable('client_users', true);
    }
}
