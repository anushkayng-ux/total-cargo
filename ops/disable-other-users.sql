-- ============================================================================
--  DISABLE ALL LOGINS EXCEPT 3: admin@tpt.local, Adarsh, Rahul
-- ============================================================================
--  Non-destructive: this uses SOFT DELETE (sets `deleted_at`) so:
--    • blocked users CANNOT log in any more
--    • any records they created (created_by / updated_by) still point to a
--      real row — no orphaned references, no FK errors
--    • fully reversible by setting `deleted_at = NULL` for that user
--
--  Keeps EXACTLY these 3 users able to log in:
--    • admin@tpt.local           (the admin login)
--    • bd@totalcargo.co.in       (Adarsh Thakur — role unchanged)
--    • sales@totalcargo.co.in    (Rahul Pandey  — role unchanged)
--
--  Everything else — including super@tpt.local and any other test users —
--  gets soft-deleted.
--
--  Take a quick DB backup first (cPanel → Backups) out of habit.
--  Run in phpMyAdmin → SQL tab, or `mysql -u USER -p DBNAME < this.sql`.
-- ============================================================================

-- 1. Preview which users WILL be disabled (run this first, check the list):
SELECT id, name, email, role_id, is_super_admin, status, deleted_at
FROM `users`
WHERE `deleted_at` IS NULL
  AND `email` NOT IN (
    'admin@tpt.local',
    'bd@totalcargo.co.in',
    'sales@totalcargo.co.in'
  );

-- 2. If the preview looks right, run this to disable them:
UPDATE `users`
SET `status` = 0,
    `deleted_at` = NOW(),
    `updated_at` = NOW()
WHERE `deleted_at` IS NULL
  AND `email` NOT IN (
    'admin@tpt.local',
    'bd@totalcargo.co.in',
    'sales@totalcargo.co.in'
  );

-- 3. Confirm — exactly these 3 users should remain active:
SELECT id, name, email, role_id, is_super_admin, status
FROM `users`
WHERE `deleted_at` IS NULL;

-- ── To undo (if you change your mind) ───────────────────────────────────────
-- UPDATE `users` SET deleted_at = NULL, status = 1 WHERE email = 'someone@example.com';
-- ============================================================================
