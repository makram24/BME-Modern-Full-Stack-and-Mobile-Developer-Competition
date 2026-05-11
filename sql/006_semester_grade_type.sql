-- Phase 10.2 — add `semester` grade type (one upsertable row per student per assignment, same pattern as year_end).
-- If your `grades.grade_type` is VARCHAR, skip this file: the app will store the literal value `semester` without an ENUM change.
ALTER TABLE grades
  MODIFY COLUMN grade_type ENUM('periodic', 'year_end', 'semester') NOT NULL;
