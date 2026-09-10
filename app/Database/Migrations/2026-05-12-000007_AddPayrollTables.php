<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayrollTables extends Migration
{
    public function up(): void
    {
        // ── employee_salary_components : current pay structure per employee ──
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'effective_from'     => ['type' => 'DATE', 'null' => true],
            'basic'              => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'hra'                => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'special_allowance'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'conveyance'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'medical'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'other_earnings'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'pf_deduction'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'esi_deduction'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'pt_deduction'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'tds_deduction'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'other_deductions'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('employee_salary_components');

        // ── payroll_runs : one row per month ──
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pay_period_year'    => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false],
            'pay_period_month'   => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
            'run_status'         => ['type' => 'ENUM', 'constraint' => ['Draft','Finalised','Paid'], 'default' => 'Draft'],
            'total_gross'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_deductions'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_net'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'finalised_by'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'finalised_at'       => ['type' => 'DATETIME', 'null' => true],
            'notes'              => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['pay_period_year', 'pay_period_month']);
        $this->forge->createTable('payroll_runs');

        // ── payroll_lines : per employee per run ──
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'payroll_run_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'user_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'days_in_month'     => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 30],
            'days_paid'         => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'days_absent'       => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'days_leave'        => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'lop_days'          => ['type' => 'DECIMAL', 'constraint' => '5,1', 'default' => 0],
            'basic'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'hra'               => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'special_allowance' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'conveyance'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'medical'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'other_earnings'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'gross'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'pf_deduction'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'esi_deduction'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'pt_deduction'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'tds_deduction'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'lop_deduction'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'other_deductions'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'total_deductions'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'net_pay'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'notes'             => ['type' => 'VARCHAR', 'constraint' => 400, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['payroll_run_id','user_id']);
        $this->forge->createTable('payroll_lines');
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll_lines', true);
        $this->forge->dropTable('payroll_runs', true);
        $this->forge->dropTable('employee_salary_components', true);
    }
}
