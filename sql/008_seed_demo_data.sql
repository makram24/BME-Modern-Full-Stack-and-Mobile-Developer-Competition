-- Demo rows for UI testing (Phase 10.3 events + optional year link).
-- Prerequisites: `007_campus_events.sql` applied; at least one row in `users` (any role).
-- Safe to re-run: removes previous demo events by title prefix, then inserts fresh rows.

DELETE FROM campus_events WHERE title LIKE 'Demo:%';

SET @demo_uid := (SELECT id FROM users ORDER BY id ASC LIMIT 1);
SET @demo_year := (SELECT id FROM academic_years ORDER BY id DESC LIMIT 1);

INSERT INTO campus_events (title, description, starts_at, ends_at, location, academic_year_id, created_by_user_id) VALUES
(
  'Demo: Spring parent–teacher meetings',
  'Short one-to-one slots with subject teachers. Sign-up sheet on the main notice board; please arrive 5 minutes before your slot.',
  '2026-05-22 15:00:00',
  '2026-05-22 19:00:00',
  'Rooms 101–105',
  NULL,
  @demo_uid
),
(
  'Demo: Sports day (house competition)',
  'Whole-school event. Wear PE kit, bring a water bottle and sun protection. House colours encouraged.',
  '2026-06-12 09:00:00',
  '2026-06-12 16:00:00',
  'Athletics track & sports field',
  NULL,
  @demo_uid
),
(
  'Demo: Science fair — project displays',
  'Students present term projects to judges and visitors. Judging 10:00–12:00; public viewing 13:00–15:00.',
  '2026-06-18 10:00:00',
  '2026-06-18 15:00:00',
  'Science block & foyer',
  NULL,
  @demo_uid
),
(
  'Demo: Year-end awards assembly',
  'Celebration of achievement; families welcome. Doors open 30 minutes before the start.',
  '2026-06-28 10:00:00',
  '2026-06-28 12:30:00',
  'Main hall',
  @demo_year,
  @demo_uid
),
(
  'Demo: New academic year — orientation day',
  'Timetables, locker allocation, and IT account setup for new joiners.',
  '2026-09-01 08:30:00',
  '2026-09-01 15:00:00',
  'Various classrooms',
  @demo_year,
  @demo_uid
),
(
  'Demo: Winter concert (choir & ensemble)',
  'Evening performance; free entry; donations welcome for the music department.',
  '2026-12-10 18:00:00',
  '2026-12-10 20:30:00',
  'Auditorium',
  NULL,
  @demo_uid
);
