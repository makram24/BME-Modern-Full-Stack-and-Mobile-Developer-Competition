-- Optional demo timetable (run after `009_assignment_timetable.sql`).
-- Updates assignment ids @m and @m+1 only. Lines for @m+2 / @m+3 affect 0 rows if your DB has only two assignments (e.g. bme_comp.sql dump) — empty grid cells are then expected outside those slots.
--
-- Minimal two-subject demo (matches a small import): Monday period 1 and Monday period 2.

SET @m := (SELECT IFNULL(MIN(id), 0) FROM class_subject_assignments);

UPDATE class_subject_assignments SET timetable_day = 1, timetable_slot = 1 WHERE id = @m AND @m > 0;
UPDATE class_subject_assignments SET timetable_day = 1, timetable_slot = 2 WHERE id = @m + 1;
UPDATE class_subject_assignments SET timetable_day = 2, timetable_slot = 1 WHERE id = @m + 2;
UPDATE class_subject_assignments SET timetable_day = 3, timetable_slot = 3 WHERE id = @m + 3;
