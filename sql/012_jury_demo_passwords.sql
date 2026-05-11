-- Phase 9 — set known bcrypt password for jury demo accounts (run after importing sql/bme_comp.sql).
-- Plaintext password for all updated rows: JuryPass2026!
-- Change or remove this file after your competition if you rotate credentials.

UPDATE users
SET password = '$2y$10$0xEpNj4G4d8A97EfQOTcfew6d1GFGogUT1S0sGt5L5DNTRuKnVl7q'
WHERE username IN ('student_demo', 'teacher_demo', 'admin_demo', 'superadmin_demo');
