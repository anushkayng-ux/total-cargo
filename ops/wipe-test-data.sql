-- ============================================================================
--  WIPE TEST DATA + KEEP ONLY 2 USERS (Adarsh Thakur, Rahul Pandey)
-- ============================================================================
--  READ BEFORE RUNNING:
--    1. Take a full database backup first (cPanel → Backups → "Download a Full
--       MySQL Database Backup", or `mysqldump -u USER -p DBNAME > backup.sql`).
--    2. Run this in phpMyAdmin (SQL tab) or `mysql -u USER -p DBNAME < this.sql`.
--    3. After running, edit the 2 user rows at the bottom to set the actual
--       email addresses you want — then have each user log in and change
--       their password from `ChangeMe@123` in Settings.
--
--  WHAT IT KEEPS (system configuration):
--    roles, permissions, role_permissions, settings, migrations,
--    feature_flags, email_templates, whatsapp_templates, help_topics,
--    lead_sources, trip_expense_categories, leave_types, cities
--
--  WHAT IT WIPES (transactional / test data):
--    everything else — clients, vendors, drivers, vehicles, leads, RFQs,
--    quotations, bookings, trips, invoices, receipts, vendor bills,
--    payments, GPS logs, EWB/E-invoice logs, documents, comments, HR
--    records, audit logs, sessions, client portal users, …
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Transactional / operational data ────────────────────────────────────────
TRUNCATE TABLE `bookings`;
TRUNCATE TABLE `trip_stops`;
TRUNCATE TABLE `trip_status_history`;
TRUNCATE TABLE `trip_advances`;
TRUNCATE TABLE `trip_expenses`;
TRUNCATE TABLE `trip_feedback`;
TRUNCATE TABLE `trips`;

TRUNCATE TABLE `invoice_items`;
TRUNCATE TABLE `invoices`;
TRUNCATE TABLE `receipts`;

TRUNCATE TABLE `vendor_bills`;
TRUNCATE TABLE `vendor_payments`;
TRUNCATE TABLE `vendor_deposits`;
TRUNCATE TABLE `vendor_routes`;
TRUNCATE TABLE `vendor_vehicle_types`;
TRUNCATE TABLE `vendor_contacts`;
TRUNCATE TABLE `vendors`;

TRUNCATE TABLE `clients`;

TRUNCATE TABLE `drivers`;
TRUNCATE TABLE `driver_messages`;
TRUNCATE TABLE `vehicles`;
TRUNCATE TABLE `latest_vehicle_status`;
TRUNCATE TABLE `gps_logs`;

TRUNCATE TABLE `leads`;
TRUNCATE TABLE `lead_followups`;
TRUNCATE TABLE `lead_status_history`;

TRUNCATE TABLE `quotations`;
TRUNCATE TABLE `quotation_comparison_logs`;
TRUNCATE TABLE `rfq_master`;
TRUNCATE TABLE `rfq_vendors`;

TRUNCATE TABLE `rate_contracts`;
TRUNCATE TABLE `rate_contract_lanes`;

TRUNCATE TABLE `tds_certificates`;
TRUNCATE TABLE `ewb_logs`;
TRUNCATE TABLE `ewb_consolidated`;
TRUNCATE TABLE `einvoice_logs`;
TRUNCATE TABLE `epod_signatures`;
TRUNCATE TABLE `loading_slots`;
TRUNCATE TABLE `insurance_quotes`;

TRUNCATE TABLE `documents`;
TRUNCATE TABLE `record_comments`;

-- ── Communication logs ──────────────────────────────────────────────────────
TRUNCATE TABLE `email_logs`;
TRUNCATE TABLE `email_events`;
TRUNCATE TABLE `email_unsubscribes`;
TRUNCATE TABLE `whatsapp_logs`;
TRUNCATE TABLE `whatsapp_incoming_messages`;

-- ── Audit + activity logs + sessions ────────────────────────────────────────
TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `alert_rules`;
TRUNCATE TABLE `super_admin_audit`;
TRUNCATE TABLE `ci_sessions`;

-- ── Support tickets ─────────────────────────────────────────────────────────
TRUNCATE TABLE `support_ticket_replies`;
TRUNCATE TABLE `support_tickets`;

-- ── Client portal users (separate auth realm) ───────────────────────────────
TRUNCATE TABLE `client_user_invites`;
TRUNCATE TABLE `client_user_resets`;
TRUNCATE TABLE `client_user_login_logs`;
TRUNCATE TABLE `client_users`;

-- ── HR / payroll (test data only) ───────────────────────────────────────────
-- Comment out any of the next block you want to keep.
TRUNCATE TABLE `attendance`;
TRUNCATE TABLE `leaves`;
TRUNCATE TABLE `leave_balances`;
TRUNCATE TABLE `holidays`;
TRUNCATE TABLE `meetings`;
TRUNCATE TABLE `meeting_events`;
TRUNCATE TABLE `payroll_lines`;
TRUNCATE TABLE `payroll_runs`;
TRUNCATE TABLE `employee_salary_components`;
TRUNCATE TABLE `employee_next_of_kin`;
TRUNCATE TABLE `employee_profiles`;

-- ── Background queue + per-user prefs + reset tokens ────────────────────────
TRUNCATE TABLE `jobs`;
TRUNCATE TABLE `user_prefs`;
TRUNCATE TABLE `password_reset_tokens`;

-- ── Users: wipe all, then insert exactly 2 admins ───────────────────────────
TRUNCATE TABLE `users`;

-- role_id = 1 is the Administrator role (created by the install).
-- The password hash below is bcrypt of `ChangeMe@123` — change immediately
-- after first login (Profile → Change Password).
INSERT INTO `users`
  (`role_id`, `is_super_admin`, `name`,           `email`,                `mobile`,    `password_hash`,                                                `status`, `created_at`, `updated_at`)
VALUES
  (1,         1,                'Adarsh Thakur',  'adarsh@osob.in',       NULL,        '$2y$10$jrKVYRuGccngM.esyovQGuZl6jH0zuy1u7DzWJRhovH6.Fd3UWziG', 1,        NOW(),        NOW()),
  (1,         1,                'Rahul Pandey',   'rahul@osob.in',        NULL,        '$2y$10$jrKVYRuGccngM.esyovQGuZl6jH0zuy1u7DzWJRhovH6.Fd3UWziG', 1,        NOW(),        NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ────────────────────────────────────────────────────────────────────
-- Both users have the default password:  ChangeMe@123
-- Make them change it on first login.
-- ============================================================================
