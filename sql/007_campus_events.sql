-- Phase 10.3 — school-wide events (administrator CRUD; other roles read-only on events.php).
CREATE TABLE IF NOT EXISTS campus_events (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location VARCHAR(255) NULL,
  academic_year_id INT NULL COMMENT 'Optional filter; NULL = visible all years',
  created_by_user_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_campus_events_starts (starts_at),
  KEY idx_campus_events_year (academic_year_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
