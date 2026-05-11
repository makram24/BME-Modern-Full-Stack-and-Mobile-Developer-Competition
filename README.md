# BME Educational Portal

PHP + MySQL school portal: students view subjects and grades; teachers manage rosters and grades; administrators configure the school year; super-administrators manage privileged accounts. Responsive UI (Bootstrap + custom CSS).

**Live demo (jury):** replace with your hosted URL, for example `https://your-school-portal.example/`

---

## Run locally

1. **PHP:** 8.1 or newer recommended (mysqli, `password_hash` / `password_verify`, sessions, JSON).
2. **MySQL:** create an empty database, e.g. `bme_comp`.
3. **Import schema + seed data:** import `sql/bme_comp.sql` into that database (phpMyAdmin or `mysql` CLI).
4. **Jury passwords (optional but recommended):** run `sql/012_jury_demo_passwords.sql` so demo accounts use a known password (see *Jury test accounts* below).
5. **Database credentials:** copy `config.local.php.example` to `config.local.php` and set `db_host`, `db_user`, `db_pass`, `db_name`. This file is gitignored. Alternatively set environment variables `PORTAL_DB_HOST`, `PORTAL_DB_USER`, `PORTAL_DB_PASS`, `PORTAL_DB_NAME` (values from `config.local.php` override env after merge order in `dbConnect.php` — local file wins for keys you set).
6. **Web server:** point the document root at this project folder (the directory that contains `index.php`).
7. Open `index.php` in the browser, sign in, and you should be redirected to `dashboard.php` → role-specific home.

### Debug mode

Set environment variable `PORTAL_DEBUG=1` to show raw MySQL connection errors in the browser (development only). In production, omit it or set to `0` so users see a generic “service unavailable” page and details go to the server log via `portal_log()`.

### HTTPS and sessions

On **HTTPS** (or when `HTTP_X_FORWARDED_PROTO` is `https` behind a reverse proxy), the app enables `session.cookie_secure` and keeps `HttpOnly` + `SameSite=Lax` cookies (`includes/session_bootstrap.php`). Terminate TLS at your host; do not commit production DB passwords.

---

## Repository layout

| Path | Purpose |
|------|---------|
| `index.php`, `register.php`, `auth.php`, `logout.php`, `dashboard.php` | Public auth and entry |
| `*_dashboard.php`, `student_*.php`, `teacher_*.php`, `admin_*.php`, `superadmin_*.php`, `events.php` | Role pages |
| `includes/` | Shared PHP: authz, data access, layout shell, security, session bootstrap |
| `assets/` | CSS, vendor Bootstrap, images |
| `sql/` | `bme_comp.sql` full dump; `012_jury_demo_passwords.sql` optional jury password patch |
| `api/` | Example JSON endpoint (`role_ping.php`) |
| `config.local.php.example` | Template for local DB config (copy to `config.local.php`) |

---

## Jury test accounts

After importing `sql/bme_comp.sql`, run **`sql/012_jury_demo_passwords.sql`** so these accounts share one known password:

| Username | Role | Password (after running `012_jury_demo_passwords.sql`) |
|----------|------|------------------------------------------------------------|
| `student_demo` | student | `JuryPass2026!` |
| `teacher_demo` | teacher | `JuryPass2026!` |
| `admin_demo` | administrator | `JuryPass2026!` |
| `superadmin_demo` | super_administrator | `JuryPass2026!` |

The dump may also contain other users (for example `Makram2410`); their passwords are **not** reset by `012_jury_demo_passwords.sql`. Use an administrator to reset passwords or issue new SQL updates as needed.

---

## Mandatory feature checklist (competition scope)

- [x] Login identifies the user (database + `password_verify`).
- [x] Student: subjects list, subject detail, results / grades view, timetable (when slots exist).
- [x] Teacher: assignments for year, roster, periodic / semester / year-end grades (see `GRADE-CALCULATION.md`).
- [x] Administrator: users (students/teachers), classes, subjects, enrolments, class–subject assignments, events, timetable slots per assignment.
- [x] Super-administrator: privileged user management.
- [x] Class with start date + code; subject catalog fields; yearly class–subject–teacher assignment.
- [x] CSRF on POST forms; `session_regenerate_id` on login.
- [x] Responsive layout (`meta viewport`, Bootstrap, collapsible portal nav).

Grade rules and optional weighted averages: **`GRADE-CALCULATION.md`**.

---

## Deployment (short checklist)

1. Create MySQL database and user; grant only required privileges.
2. Import `sql/bme_comp.sql`; run `sql/012_jury_demo_passwords.sql` if you need the tabled jury password.
3. Upload project files; set document root; configure `config.local.php` **on the server only** (not in Git).
4. Enable HTTPS; confirm cookies and sessions work through your load balancer (forward `X-Forwarded-Proto` if needed).
5. Set `PORTAL_DEBUG` off in production.

---

## AI-assisted development

See **`AI_DECLARATION.md`** for tools used and how AI supported this repository (competition transparency).
