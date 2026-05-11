-- Phase 10.4 — timetable on each class–subject assignment (day of week + lesson period).
-- 0 = not set (legacy rows). Day: 1=Monday … 7=Sunday. Slot: lesson period 1–12.
ALTER TABLE class_subject_assignments
  ADD COLUMN timetable_day TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 unset; 1=Mon .. 7=Sun' AFTER teacher_user_id,
  ADD COLUMN timetable_slot TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 unset; 1–12 period' AFTER timetable_day;
