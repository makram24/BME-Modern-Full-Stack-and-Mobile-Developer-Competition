-- Phase 8 — optional indexes for roster / grades lookups (safe to run once; ignore duplicate-name errors if already applied).
CREATE INDEX idx_enrollments_class_year ON class_enrollments (class_id, academic_year_id);
CREATE INDEX idx_grades_assignment ON grades (class_subject_assignment_id);
CREATE INDEX idx_grades_assignment_student ON grades (class_subject_assignment_id, student_user_id);
