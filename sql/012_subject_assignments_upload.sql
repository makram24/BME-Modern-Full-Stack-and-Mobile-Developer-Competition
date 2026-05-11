-- Phase 10.5 — Teacher-created assignments with student file uploads
--
-- Tables:
-- - subject_assignments: teacher creates an assignment for a class–subject offering
-- - subject_assignment_submissions: students upload one file per assignment (replace allowed)
--
-- Storage: uploads/subject_assignments/<subject_assignment_id>/<student_user_id>/<stored_filename>

CREATE TABLE IF NOT EXISTS subject_assignments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  class_subject_assignment_id INT UNSIGNED NOT NULL COMMENT 'FK -> class_subject_assignments(id)',
  title VARCHAR(255) NOT NULL,
  instructions TEXT NULL,
  due_at DATETIME NULL,
  created_by_user_id INT UNSIGNED NOT NULL COMMENT 'teacher who created it',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sa_csa (class_subject_assignment_id),
  KEY idx_sa_due (due_at)
  -- If fk creation fails on your host, rerun without this constraint line.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key (optional; remove this CONSTRAINT line if your host cannot create it)
ALTER TABLE subject_assignments
  ADD CONSTRAINT fk_sa_csa
  FOREIGN KEY (class_subject_assignment_id) REFERENCES class_subject_assignments (id)
  ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS subject_assignment_submissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subject_assignment_id INT UNSIGNED NOT NULL,
  student_user_id INT UNSIGNED NOT NULL,
  file_original_name VARCHAR(255) NOT NULL,
  file_stored_path VARCHAR(255) NOT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_submission_one (subject_assignment_id, student_user_id),
  KEY idx_sas_assignment (subject_assignment_id),
  KEY idx_sas_student (student_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key (optional; remove this CONSTRAINT line if your host cannot create it)
ALTER TABLE subject_assignment_submissions
  ADD CONSTRAINT fk_sas_sa
  FOREIGN KEY (subject_assignment_id) REFERENCES subject_assignments (id)
  ON DELETE CASCADE ON UPDATE CASCADE;

