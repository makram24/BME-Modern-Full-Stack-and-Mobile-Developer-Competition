# BME Educational Portal — Development Phases (Local Planning)

> **Note:** This file is intentionally excluded from GitHub (see `.gitignore`).  
> It is for internal planning only; jury-facing documentation belongs in `README.md` in the repository.

---

## Current baseline (as of this plan)

- PHP with sessions; `dbConnect.php` targets MySQL database `bme_comp`.
- `auth.php` uses **hard-coded** admin credentials and sets `admin_logged_in` — suitable only as a stub; must be replaced with **database users + roles + password hashing** for the competition.
- Entry/login UI exists (`index.php` / login flow toward `dashboard.php`).

Use the phases below to evolve this into the full mandatory scope, then optional extras if time allows.

---

## Phase 0 — Repository, environment, and safety net

**Goals:** Repeatable local work and a clean path to deployment without leaking secrets.

| Step | Detail |
|------|--------|
| 0.1 | Confirm PHP version and extensions on WAMP (`mysqli`, `session`, `password_hash` / `password_verify`). |
| 0.2 | Keep **non-secret** defaults in repo; use a **local-only** config pattern (e.g. `config.local.php` gitignored) for DB password and base URL — *implement when you split config from code*. |
| 0.3 | Decide URL structure: single entry `index.php` vs folder per role; avoid duplicate session/bootstrap logic (one `bootstrap.php` or `init.php` included everywhere). |
| 0.4 | Remove or gate **debug scripts** (e.g. `auth_debug.php`) before any public deploy; never commit real production passwords. |

**Exit criteria:** You can clone/open the project, set DB credentials once, and load the app without errors.

---

## Phase 1 — Data model and database (foundation)

**Goals:** Persistent schema that matches the **mandatory** domain: users, roles, classes, subjects, yearly assignments, grades.

### 1.1 Core entities (tables — adjust names to your naming style)

| Area | Tables / concepts | Rules to enforce in DB + app |
|------|-------------------|------------------------------|
| Identity | `users` (id, email or username, password_hash, is_active, created_at, …) | No plaintext passwords. |
| Roles | `roles` (student, teacher, administrator, super_administrator) + `user_roles` (user_id, role_id) | A user may have one primary role for simplicity, or multiple if you design carefully. |
| Class | `classes` (id, **start_date**, **class_code** e.g. `2009/C`, display name optional) | Unique constraint on business key you choose (e.g. start_date + class_code). |
| Membership | `class_enrollments` (user_id student, class_id, valid from/to or academic_year_id) | Student belongs to a class. |
| Subject | `subjects` (title, description, required_books text, lessons outline text/json, …) | General subject catalog. |
| Academic year | `academic_years` (label, start, end) | Anchors “given year” assignments. |
| Assignment | `class_subject_assignments` (academic_year_id, class_id, subject_id, **teacher_user_id**) | Which teacher teaches which subject to which class in which year. |
| Grades | `grades` (assignment_id or tuple of year/class/subject/student, grade_value, type: periodic / year_end, entered_by, entered_at) | Teacher only for subjects they teach that year; year-end row mandatory workflow. |

### 1.2 Indexes and integrity

- Foreign keys where the DB engine supports them (InnoDB).
- Indexes on: login lookup (username/email), class_subject_assignments (teacher + year), grades (student + assignment).

### 1.3 Seed data

- SQL or PHP seeder: at least one academic year, one class, sample subjects, assignments, and **four test users** (student, teacher, admin, super-admin) with **known passwords for README**.

**Exit criteria:** Schema is documented (ER description or `schema.sql` in repo); you can insert/query all mandatory relationships without ambiguity.

---

## Phase 2 — Authentication and session security

**Goals:** Competition requirement: **secure credential storage**; identify user for every request.

| Step | Detail |
|------|--------|
| 2.1 | Replace hard-coded login with **DB lookup**; `password_verify()` against `password_hash()` (PASSWORD_DEFAULT). |
| 2.2 | On success: store in session `user_id`, `role` (or roles), **not** the password. |
| 2.3 | Central `require_login.php` (or similar): if not authenticated, redirect to login with return URL. |
| 2.4 | Optional but good: CSRF tokens on POST forms; `session_regenerate_id()` on login. |
| 2.5 | Logout destroys session (`logout.php` alignment). |

**Exit criteria:** Invalid credentials never reveal whether username exists (same error message); passwords never echoed or logged.

---

## Phase 3 — Authorization (role model)

**Goals:** **5 points** area in rubric: each role sees only allowed actions; **server-side** enforcement is mandatory (UI hiding is not enough).

| Role | Server-side checks (examples) |
|------|------------------------------|
| Student | Read own class’s subjects for current year; read own grades only. |
| Teacher | List assignments where `teacher_user_id = self`; CRUD grades only for students in those assignments; year-end grade allowed per rules. |
| Administrator | CRUD students/teachers (not super-admins); CRUD subjects; assign subjects to classes for a year; pick teacher per assignment. |
| Super-administrator | CRUD admins and super-admins; optionally restrict admin from touching super-admin rows. |

Implementation pattern:

- Small functions: `current_user()`, `require_role('administrator')`, `can_edit_grade($teacherId, $assignmentId)`.
- Every `.php` action endpoint checks role before DB writes.

**Exit criteria:** Attempting to open another role’s URL as a student returns **403** or redirect to safe page; no IDOR on grade URLs (e.g. changing `student_id` in query fails).

---

## Phase 4 — Student interface

**Goals:** Mandatory: **view subjects** and **subject results**.

| Step | Detail |
|------|--------|
| 4.1 | Dashboard: welcome, class identifier, current academic year context. |
| 4.2 | Subject list for **their class** in **selected/current year** (from assignments). |
| 4.3 | Subject detail: description/books/lessons from catalog + teacher name + schedule placeholder if you add timetable later. |
| 4.4 | Results view: list grades (and year-end when present); simple table is enough. |

**Exit criteria:** Student never sees another student’s grades or unrelated classes’ subjects.

---

## Phase 5 — Teacher interface

**Goals:** Mandatory: **manage taught subjects** (interpret as: view subjects they teach), **enter grades**, **year-end grade**.

| Step | Detail |
|------|--------|
| 5.1 | List assignments for logged-in teacher for chosen year. |
| 5.2 | Per assignment: roster of students in that class. |
| 5.3 | Grade entry: save periodic grades (even one type is enough); validate scale (1–5, A–F, etc. — pick one and document). |
| 5.4 | Year-end grade: dedicated field or grade type `year_end`; enforce “end of year” in UI (e.g. button or academic year flag). |

**Exit criteria:** Teacher cannot enter grades for a class/subject/year they are not assigned to.

---

## Phase 6 — Administrator interface

**Goals:** Mandatory: **manage student/teacher accounts**, **create subjects**, **assign subjects to classes** (with teacher for that year).

| Step | Detail |
|------|--------|
| 6.1 | User management: create/edit/deactivate students and teachers (hashed passwords). |
| 6.2 | Class management: CRUD classes with **start_date** + **identifier** (`2009/C`). |
| 6.3 | Enroll students in classes (with year if you use yearly enrollment). |
| 6.4 | Subject catalog CRUD. |
| 6.5 | **Assignment UI:** pick year, class, subject, teacher → create/update `class_subject_assignments`. |

**Exit criteria:** Admin can set up a full mini scenario: year + class + 2 subjects + 2 teachers + students + grades flow works end-to-end.

---

## Phase 7 — Super-administrator interface

**Goals:** Mandatory: **manage administrators and super-administrators**.

| Step | Detail |
|------|--------|
| 7.1 | Only super-admin role accesses user-create for admin/super-admin. |
| 7.2 | Prevent regular admin from elevating themselves to super-admin (server-side). |
| 7.3 | Optional: audit log table for sensitive actions. |

**Exit criteria:** Clear separation tested with three accounts: admin blocked from super-admin routes; super-admin succeeds.

---

## Phase 8 — Cross-cutting quality (rubric alignment)

**Goals:** Polish for **communication**, **error handling**, **logging**, **UX**, **responsive** layout.

| Area | Actions |
|------|---------|
| Responsive | Bootstrap (already partially used): readable tables on mobile; collapsible nav. |
| Errors | Friendly messages; log details server-side only (`error_log` or simple file logger). |
| Concurrency | Use transactions for multi-row grade saves; avoid obvious race on duplicate grade inserts (unique key + upsert). |
| API style | If using jQuery/AJAX: JSON endpoints with consistent `{ ok, error }` shape; still enforce auth on each AJAX script. |
| Performance | Pagination on large rosters; indexes verified with `EXPLAIN` if needed. |

**Exit criteria:** No blank pages on SQL errors in production mode; mobile viewport meta present on all layouts.

---

## Phase 9 — Deployment, jury handoff, and GitHub

**Goals:** Cloud link + **README.md** + organized repo (scored).

| Step | Detail |
|------|--------|
| 9.1 | Deploy PHP + MySQL host (DB credentials in hosting panel, not in Git). |
| 9.2 | HTTPS enabled; `session.cookie_secure` if appropriate for HTTPS-only. |
| 9.3 | **README.md:** app description, live URL, **how to run locally** (PHP version, import `schema.sql`, env vars), **jury test accounts** per role, feature checklist vs mandatory spec. |
| 9.4 | Repo structure: `/public` or web root clear, `/sql` or `/migrations`, `/includes`, `/assets`; no junk in root. |
| 9.5 | **AI declaration** (separate file in repo): list tools used and extent — competition requirement if you used AI. |

**Exit criteria:** A juror with only README + URL can log in as each role and verify mandatory flows.

---

## Phase 10 — Optional features (pick only if core is stable)

Order by **impact vs time** for a short deadline:

1. **Grade averages** with weights (optional spec) — high demo value.  
2. **Semester grade** as extra grade type.  
3. **Events** (admin CRUD, read-only for others).  
4. **Timetable** (day + lesson slot on assignment) — more schema + UI.  
5. **Messaging / assignments upload / questionnaires / map / chatbot** — larger; only if time remains.

**Rule:** Ship one optional feature **fully** rather than several **half-working**.

---

## Suggested order of execution (summary)

1. Phase **1** schema + seed → **2** auth → **3** authorization  
2. Phase **6** admin setup flows (so you can create data) *or* heavy seed first — your choice; often **admin + seed** in parallel  
3. Phase **5** teacher grades → **4** student views → **7** super-admin  
4. Phase **8** polish → **9** deploy + README  
5. Phase **10** only if stable  

---

## Checklist vs mandatory competition bullets

- [ ] Login identifies user  
- [ ] Student: subjects + results  
- [ ] Teacher: taught subjects, grades, year-end grade  
- [ ] Admin: users (student/teacher), subjects, assign to class with teacher per year  
- [ ] Super-admin: manage admins  
- [ ] Class = start date + identifier  
- [ ] Subject general info (description, books, lessons)  
- [ ] Yearly class–subject–teacher assignment  
- [ ] DB persistence, multi-user server, secure passwords  
- [ ] Responsive web UI  
- [ ] Hosted link + README + GitHub repo  

---

*End of planning document.*
