<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHrmsTables extends Migration
{
    public function up(): void
    {
        // ── employee_profiles : one-to-one with users ──
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'                 => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'employee_code'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'date_of_birth'           => ['type' => 'DATE',    'null' => true],
            'date_of_joining'         => ['type' => 'DATE',    'null' => true],
            'gender'                  => ['type' => 'ENUM',    'constraint' => ['Male','Female','Other'], 'null' => true],
            'marital_status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'blood_group'             => ['type' => 'VARCHAR', 'constraint' => 5,  'null' => true],
            'designation'             => ['type' => 'VARCHAR', 'constraint' => 120,'null' => true],
            'department'              => ['type' => 'VARCHAR', 'constraint' => 120,'null' => true],
            'reporting_manager_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'permanent_address'       => ['type' => 'VARCHAR', 'constraint' => 400,'null' => true],
            'current_address'         => ['type' => 'VARCHAR', 'constraint' => 400,'null' => true],
            'emergency_contact_name'  => ['type' => 'VARCHAR', 'constraint' => 120,'null' => true],
            'emergency_contact_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'pan_no'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aadhaar_last_4'          => ['type' => 'VARCHAR', 'constraint' => 4,  'null' => true],
            'uan_no'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'bank_name'               => ['type' => 'VARCHAR', 'constraint' => 120,'null' => true],
            'bank_account_no'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'bank_ifsc'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'profile_completed'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('employee_code');
        $this->forge->addKey('reporting_manager_id');
        $this->forge->createTable('employee_profiles');

        // ── leave_types ──
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'                 => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'default_annual_quota' => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'is_paid'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'carries_forward'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('leave_types');

        // ── leave_balances : per user × leave_type × year ──
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'leave_type_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'year'          => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false],
            'allocated'     => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'used'          => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'balance'       => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'leave_type_id', 'year']);
        $this->forge->createTable('leave_balances');

        // ── leaves : applications ──
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'leave_type_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'from_date'         => ['type' => 'DATE', 'null' => false],
            'to_date'           => ['type' => 'DATE', 'null' => false],
            'days'              => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'is_half_day'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'reason'            => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['Pending','Approved','Rejected','Cancelled'], 'default' => 'Pending'],
            'approver_user_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approver_notes'    => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'approved_at'       => ['type' => 'DATETIME', 'null' => true],
            'applied_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey(['from_date','to_date']);
        $this->forge->createTable('leaves');

        // ── attendance : one row per user × date ──
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'attendance_date'  => ['type' => 'DATE', 'null' => false],
            'punch_in_at'      => ['type' => 'DATETIME', 'null' => true],
            'punch_in_lat'     => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'punch_in_lng'     => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'punch_in_source'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'punch_out_at'     => ['type' => 'DATETIME', 'null' => true],
            'punch_out_lat'    => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'punch_out_lng'    => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'hours_worked'     => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 0],
            'status'           => ['type' => 'ENUM', 'constraint' => ['Present','Absent','HalfDay','Leave','Holiday','Weekend'], 'default' => 'Present'],
            'notes'            => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'attendance_date']);
        $this->forge->addKey('attendance_date');
        $this->forge->createTable('attendance');
    }

    public function down(): void
    {
        $this->forge->dropTable('attendance', true);
        $this->forge->dropTable('leaves', true);
        $this->forge->dropTable('leave_balances', true);
        $this->forge->dropTable('leave_types', true);
        $this->forge->dropTable('employee_profiles', true);
    }
}
