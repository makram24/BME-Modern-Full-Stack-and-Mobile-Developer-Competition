# How grades are calculated

This portal stores numeric grades on a **fixed integer scale from 1 to 5** (whole numbers only). Teachers enter grades; students see only their own rows. The optional **weight** feature affects how a **weighted average** is computed from **periodic** grades only.

---

## Grade types

| Type       | Meaning | How many per student per subject |
|-----------|---------|----------------------------------|
| `periodic` | Ongoing marks (tests, oral work, etc.) | Many rows allowed (each has its own label). |
| `semester` | Official semester mark for that subject | At most **one** row per student per class–subject assignment; saving again **updates** the same row (Phase 10.2). |
| `year_end` | Final mark for the year for that subject | At most **one** row per student per class–subject assignment; saving again **updates** the same row. |

**Semester** and **year-end** are separate from the periodic weighted average: they are **not** included in the formula below.

---

## Weights (periodic entries only)

Each **periodic** grade row has a **weight** (database column `grades.weight`, default `1.00`).

- Allowed range: **0.25** to **10.0** (two decimal places).
- Higher weight means that entry counts **more** in the periodic average.
- **Semester** and **year-end** rows still have a weight in the database (default 1) for consistency, but the application **does not** use them when computing the weighted average.

---

## Weighted average (periodic only)

For one student and one class–subject assignment, let the periodic rows be \((g_1, w_1), (g_2, w_2), \ldots, (g_n, w_n)\), where each \(g_i\) is an integer from 1 to 5 and each \(w_i > 0\).

**Formula:**

\[
\text{weighted average} = \frac{\sum_{i=1}^{n} g_i \cdot w_i}{\sum_{i=1}^{n} w_i}
\]

The result is a **real number** (not forced to an integer). The UI shows it rounded to **two decimal places** (e.g. `3.67`).

**Examples:**

- Grades **4** (weight 1) and **5** (weight 1):  
  \((4 \cdot 1 + 5 \cdot 1) / (1 + 1) = 4.50\)

- Grades **4** (weight 1) and **5** (weight 2):  
  \((4 \cdot 1 + 5 \cdot 2) / (1 + 2) = 14 / 3 \approx 4.67\)  
  The **5** pulls the average up more because its weight is doubled.

If there are **no** periodic rows (or all weights are invalid), the weighted average is shown as **—**.

---

## Where it appears

- **Teacher — Roster & grades** (`teacher_assignment.php`): under each student, **Weighted average (periodic)** uses only that student’s periodic rows for the current subject.
- **Student — Results** (`student_results.php`): the table lists each grade and a **Weight** column for periodic rows; below the table, **Weighted average by subject** repeats the same formula **per subject** (per class–subject assignment) for that student’s periodic grades in the selected year and class.

---

## Implementation reference

- Logic: `includes/grade_weights.php` (`weighted_periodic_grade_average`, `parse_grade_weight`, `format_grade_average`).
- Schema: `sql/005_grade_weights.sql` adds `grades.weight`.
- Semester type: `sql/006_semester_grade_type.sql` extends `grades.grade_type` (or use `VARCHAR` without this file if your schema is not ENUM).

---

## Campus events (Phase 10.3)

Not a grade calculation: school **events** are rows in `campus_events` (see `sql/007_campus_events.sql`). **School administrators** create and edit them under **Administrator → Events**; **students, teachers, super-admins, and admins** can open **Events** in the nav for a **read-only** list (`events.php`). Optional `?year_id=` filters events to that academic year or “all years” rows.

---

## Timetable (Phase 10.4)

Each **class–subject assignment** can have **many** weekday + lesson-period rows (same subject twice on one day is allowed; different subjects use different assignment rows). Stored in **`assignment_timetable_slots`** (`sql/011_assignment_timetable_slots.sql`), linked to `class_subject_assignments`. Legacy columns `class_subject_assignments.timetable_day` / `timetable_slot` (from `sql/009_assignment_timetable.sql`) are migrated to the new table and then cleared to `0`; the app reads slots only from **`assignment_timetable_slots`**. School **administrators** set periods on **Edit assignment** (`admin_assignment_edit.php`); the assignments list shows a joined summary. **Students** see a week grid on `student_timetable.php` and summaries on subject lists; **teachers** see `teacher_timetable.php` and per-subject headers. A **small import** (few slot rows) leaves most grid cells blank on purpose—those are free periods, not missing data.

---

## Summary

| Question | Answer |
|----------|--------|
| What scale? | Integers **1–5** only for `grade_value`. |
| What is weighted? | **Periodic** grades only, using each row’s **weight**. |
| Are semester / year-end in the average? | **No.** |
| How is the number shown? | Two decimal places in the UI. |
