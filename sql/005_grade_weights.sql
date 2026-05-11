-- Phase 10.1 — weights on periodic grades for weighted averages (year_end uses default weight, excluded from average in app).
ALTER TABLE grades
  ADD COLUMN weight DECIMAL(6,2) NOT NULL DEFAULT 1.00
  COMMENT 'Relative importance in weighted periodic average'
  AFTER grade_value;
