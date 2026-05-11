-- Phase 10.4b — multiple timetable slots per class–subject assignment (same or different subjects; same subject can appear Mon P1 + Mon P5).
-- Run after sql/009_assignment_timetable.sql (and optional sql/010_seed_timetable_demo.sql). If `fk_ats_csa` fails on your host, create the table without the CONSTRAINT line, then run the INSERT IGNORE and UPDATE below.
CREATE TABLE IF NOT EXISTS assignment_timetable_slots (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  class_subject_assignment_id INT UNSIGNED NOT NULL,
  timetable_day TINYINT UNSIGNED NOT NULL COMMENT '1=Mon .. 7=Sun',
  timetable_slot TINYINT UNSIGNED NOT NULL COMMENT '1-12',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_assignment_day_slot (class_subject_assignment_id, timetable_day, timetable_slot),
  KEY idx_ats_assignment (class_subject_assignment_id),
  CONSTRAINT fk_ats_csa FOREIGN KEY (class_subject_assignment_id) REFERENCES class_subject_assignments (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copy legacy single slot from class_subject_assignments into the new table (if not already migrated).
INSERT IGNORE INTO assignment_timetable_slots (class_subject_assignment_id, timetable_day, timetable_slot)
SELECT id, timetable_day, timetable_slot
FROM class_subject_assignments
WHERE timetable_day >= 1 AND timetable_day <= 7 AND timetable_slot >= 1 AND timetable_slot <= 12;

-- Legacy columns are no longer authoritative; keep them at 0 so old code paths that read them show "unset".
UPDATE class_subject_assignments SET timetable_day = 0, timetable_slot = 0;
