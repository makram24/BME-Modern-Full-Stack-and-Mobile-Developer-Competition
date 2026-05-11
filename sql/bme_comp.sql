-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 11, 2026 at 07:12 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bme_comp`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. 2025-2026',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_academic_years_label` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `label`, `date_start`, `date_end`, `is_current`, `created_at`) VALUES
(1, '2025-2026', '2025-09-01', '2026-06-30', 1, '2026-05-11 17:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_timetable_slots`
--

DROP TABLE IF EXISTS `assignment_timetable_slots`;
CREATE TABLE IF NOT EXISTS `assignment_timetable_slots` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_subject_assignment_id` int UNSIGNED NOT NULL,
  `timetable_day` tinyint UNSIGNED NOT NULL COMMENT '1=Mon .. 7=Sun',
  `timetable_slot` tinyint UNSIGNED NOT NULL COMMENT '1-12',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment_day_slot` (`class_subject_assignment_id`,`timetable_day`,`timetable_slot`),
  KEY `idx_ats_assignment` (`class_subject_assignment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignment_timetable_slots`
--

INSERT INTO `assignment_timetable_slots` (`id`, `class_subject_assignment_id`, `timetable_day`, `timetable_slot`, `created_at`) VALUES
(1, 1, 1, 1, '2026-05-11 18:17:13'),
(2, 2, 2, 2, '2026-05-11 18:17:13'),
(4, 2, 1, 7, '2026-05-11 18:19:48'),
(5, 2, 1, 3, '2026-05-11 18:19:53'),
(6, 2, 3, 5, '2026-05-11 18:20:03'),
(7, 2, 3, 4, '2026-05-11 18:20:09');

-- --------------------------------------------------------

--
-- Table structure for table `campus_events`
--

DROP TABLE IF EXISTS `campus_events`;
CREATE TABLE IF NOT EXISTS `campus_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year_id` int DEFAULT NULL COMMENT 'Optional filter; NULL = visible all years',
  `created_by_user_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campus_events_starts` (`starts_at`),
  KEY `idx_campus_events_year` (`academic_year_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `campus_events`
--

INSERT INTO `campus_events` (`id`, `title`, `description`, `starts_at`, `ends_at`, `location`, `academic_year_id`, `created_by_user_id`, `created_at`) VALUES
(7, 'Demo: Spring parent–teacher meetings', 'Short one-to-one slots with subject teachers. Sign-up sheet on the main notice board; please arrive 5 minutes before your slot.', '2026-05-22 15:00:00', '2026-05-22 19:00:00', 'Rooms 101–105', NULL, 2, '2026-05-11 18:01:47'),
(8, 'Demo: Sports day (house competition)', 'Whole-school event. Wear PE kit, bring a water bottle and sun protection. House colours encouraged.', '2026-06-12 09:00:00', '2026-06-12 16:00:00', 'Athletics track & sports field', NULL, 2, '2026-05-11 18:01:47'),
(9, 'Demo: Science fair — project displays', 'Students present term projects to judges and visitors. Judging 10:00–12:00; public viewing 13:00–15:00.', '2026-06-18 10:00:00', '2026-06-18 15:00:00', 'Science block & foyer', NULL, 2, '2026-05-11 18:01:47'),
(10, 'Demo: Year-end awards assembly', 'Celebration of achievement; families welcome. Doors open 30 minutes before the start.', '2026-06-28 10:00:00', '2026-06-28 12:30:00', 'Main hall', 1, 2, '2026-05-11 18:01:47'),
(11, 'Demo: New academic year — orientation day', 'Timetables, locker allocation, and IT account setup for new joiners.', '2026-09-01 08:30:00', '2026-09-01 15:00:00', 'Various classrooms', 1, 2, '2026-05-11 18:01:47'),
(12, 'Demo: Winter concert (choir & ensemble)', 'Evening performance; free entry; donations welcome for the music department.', '2026-12-10 18:00:00', '2026-12-10 20:30:00', 'Auditorium', NULL, 2, '2026-05-11 18:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL COMMENT 'Class cohort start',
  `class_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. 2009/C',
  `display_name` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_class_identity` (`start_date`,`class_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `start_date`, `class_code`, `display_name`, `created_at`) VALUES
(1, '2009-09-01', '2009/C', 'Graduating class 2009/C', '2026-05-11 17:17:29'),
(2, '2026-05-12', '2010/A', 'Graduating class 2010/A', '2026-05-11 18:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `class_enrollments`
--

DROP TABLE IF EXISTS `class_enrollments`;
CREATE TABLE IF NOT EXISTS `class_enrollments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'Student user — must match users.id type (signed INT)',
  `class_id` int UNSIGNED NOT NULL,
  `academic_year_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment_year` (`user_id`,`academic_year_id`),
  KEY `idx_enrollment_class` (`class_id`,`academic_year_id`),
  KEY `fk_enrollment_year` (`academic_year_id`),
  KEY `idx_enrollments_class_year` (`class_id`,`academic_year_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_enrollments`
--

INSERT INTO `class_enrollments` (`id`, `user_id`, `class_id`, `academic_year_id`, `created_at`) VALUES
(1, 3, 1, 1, '2026-05-11 17:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `class_subject_assignments`
--

DROP TABLE IF EXISTS `class_subject_assignments`;
CREATE TABLE IF NOT EXISTS `class_subject_assignments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `academic_year_id` int UNSIGNED NOT NULL,
  `class_id` int UNSIGNED NOT NULL,
  `subject_id` int UNSIGNED NOT NULL,
  `teacher_user_id` int NOT NULL COMMENT 'Must match users.id type (signed INT)',
  `timetable_day` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 unset; 1=Mon .. 7=Sun',
  `timetable_slot` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 unset; 1ÔÇô12 period',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment` (`academic_year_id`,`class_id`,`subject_id`),
  KEY `idx_assignment_teacher_year` (`teacher_user_id`,`academic_year_id`),
  KEY `idx_assignment_class_year` (`class_id`,`academic_year_id`),
  KEY `fk_csa_subject` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_subject_assignments`
--

INSERT INTO `class_subject_assignments` (`id`, `academic_year_id`, `class_id`, `subject_id`, `teacher_user_id`, `timetable_day`, `timetable_slot`, `created_at`) VALUES
(1, 1, 1, 1, 4, 0, 0, '2026-05-11 17:17:29'),
(2, 1, 1, 2, 4, 0, 0, '2026-05-11 17:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_subject_assignment_id` int UNSIGNED NOT NULL,
  `student_user_id` int NOT NULL COMMENT 'Must match users.id type (signed INT)',
  `grade_value` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) NOT NULL DEFAULT '1.00' COMMENT 'Relative importance in weighted periodic average',
  `grade_type` enum('periodic','year_end','semester') COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. oral, test — for periodic entries',
  `entered_by_user_id` int NOT NULL COMMENT 'Must match users.id type (signed INT)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grades_student_assignment` (`student_user_id`,`class_subject_assignment_id`),
  KEY `idx_grades_assignment` (`class_subject_assignment_id`),
  KEY `fk_grade_entered_by` (`entered_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `class_subject_assignment_id`, `student_user_id`, `grade_value`, `weight`, `grade_type`, `label`, `entered_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '4', 1.00, 'periodic', 'Topic test 1', 4, '2026-05-11 17:17:29', NULL),
(2, 2, 3, '5', 1.00, 'periodic', 'Topic 2 Test', 4, '2026-05-11 19:08:15', NULL),
(3, 2, 3, '3', 1.00, 'semester', NULL, 4, '2026-05-11 19:08:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `required_books` text COLLATE utf8mb4_unicode_ci,
  `lessons_outline` text COLLATE utf8mb4_unicode_ci COMMENT 'Topics / lesson structure',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subjects_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `title`, `description`, `required_books`, `lessons_outline`, `created_at`) VALUES
(1, 'Mathematics', 'Algebra and geometry foundations.', 'Official math workbook', 'Weekly problem sets; two written tests per term.', '2026-05-11 17:17:29'),
(2, 'English', 'Reading and writing.', 'Anthology (see syllabus)', 'Oral participation; essay; final exam.', '2026-05-11 17:17:29'),
(3, 'French', 'French Language', '', '', '2026-05-11 18:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignments`
--

DROP TABLE IF EXISTS `subject_assignments`;
CREATE TABLE IF NOT EXISTS `subject_assignments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_subject_assignment_id` int UNSIGNED NOT NULL COMMENT 'FK -> class_subject_assignments(id)',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `due_at` datetime DEFAULT NULL,
  `created_by_user_id` int UNSIGNED NOT NULL COMMENT 'teacher who created it',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_csa` (`class_subject_assignment_id`),
  KEY `idx_sa_due` (`due_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject_assignment_submissions`
--

DROP TABLE IF EXISTS `subject_assignment_submissions`;
CREATE TABLE IF NOT EXISTS `subject_assignment_submissions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_assignment_id` int UNSIGNED NOT NULL,
  `student_user_id` int UNSIGNED NOT NULL,
  `file_original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_stored_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission_one` (`subject_assignment_id`,`student_user_id`),
  KEY `idx_sas_assignment` (`subject_assignment_id`),
  KEY `idx_sas_student` (`student_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher','administrator','super_administrator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 = blocked from login',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `is_active`) VALUES
(2, 'Makram2410', '$2y$10$p1aUj7hgIA.1Lh3cO9uCbeVosg78DrDee64KJNuSBsQHsMcagGO6u', 'teacher', '2026-05-11 17:00:52', 1),
(3, 'student_demo', '$2y$10$zzQHed0id24cNXUcxlDgjOavsCtr5atIR21DZy7zY04l4Z5TkNhlG', 'student', '2026-05-11 17:17:29', 1),
(4, 'teacher_demo', '$2y$10$zzQHed0id24cNXUcxlDgjOavsCtr5atIR21DZy7zY04l4Z5TkNhlG', 'teacher', '2026-05-11 17:17:29', 1),
(5, 'admin_demo', '$2y$10$zzQHed0id24cNXUcxlDgjOavsCtr5atIR21DZy7zY04l4Z5TkNhlG', 'administrator', '2026-05-11 17:17:29', 1),
(6, 'superadmin_demo', '$2y$10$zzQHed0id24cNXUcxlDgjOavsCtr5atIR21DZy7zY04l4Z5TkNhlG', 'super_administrator', '2026-05-11 17:17:29', 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment_timetable_slots`
--
ALTER TABLE `assignment_timetable_slots`
  ADD CONSTRAINT `fk_ats_csa` FOREIGN KEY (`class_subject_assignment_id`) REFERENCES `class_subject_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_enrollments`
--
ALTER TABLE `class_enrollments`
  ADD CONSTRAINT `fk_enrollment_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollment_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `class_subject_assignments`
--
ALTER TABLE `class_subject_assignments`
  ADD CONSTRAINT `fk_csa_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_csa_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_csa_teacher` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_csa_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grade_assignment` FOREIGN KEY (`class_subject_assignment_id`) REFERENCES `class_subject_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grade_entered_by` FOREIGN KEY (`entered_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grade_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject_assignments`
--
ALTER TABLE `subject_assignments`
  ADD CONSTRAINT `fk_sa_csa` FOREIGN KEY (`class_subject_assignment_id`) REFERENCES `class_subject_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject_assignment_submissions`
--
ALTER TABLE `subject_assignment_submissions`
  ADD CONSTRAINT `fk_sas_sa` FOREIGN KEY (`subject_assignment_id`) REFERENCES `subject_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
