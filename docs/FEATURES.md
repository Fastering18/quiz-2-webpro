# Implemented features

Status legend: **UI+API** = usable in the web pages and the API; **API only** = endpoint implemented and tested, no screen in the web UI yet; **Partial** = works with a stated limit.
Everything below is taken from the code (`routes/api.php`, `routes/web.php`, controllers, services, `public/js/*`, seeder). Roles: `mahasiswa`, `dosen`, `admin`.

## 1. Platform and cross-cutting

| Area | What exists | Status |
| --- | --- | --- |
| Stack | Laravel 13 JSON API + Blade pages with plain JS (`public/js/*.js`); Sanctum bearer tokens; Postgres (Neon) | UI+API |
| Authentication | `POST /api/login` (username = NRP / NIP / `admin`), `GET /api/me`, `POST /api/logout`; the UI stores the token and sends `Authorization: Bearer` | UI+API |
| Forgot / reset password | `POST /api/forgot-password` (always the same 200 reply, no account enumeration), 60-minute single-use hashed token in `password_reset_tokens`, Indonesian HTML + text mail sent synchronously over plain SMTP, `/reset-password` page + `POST /api/reset-password` (revokes all tokens). Login page "Lupa kata sandi?" is wired. With `MAIL_MAILER=log` (default) the mail only goes to the log | UI+API |
| Rate limits | login 10/min per username+IP and 30/min per IP; other API 120/min per user or IP; forgot-password 5/h and reset 10/h per email+IP | API |
| CORS | `config/cors.php`: `api/*` and `sanctum/csrf-cookie`, `CORS_ALLOWED_ORIGINS` list | API |
| JSON errors | Indonesian messages for 401/403/404/405/413/419/422/429/500 on `/api/*`, no internals leaked | API |
| Browser sessions | `sessions` table, proxies trusted (HTTPS, client IP), protected pages `/portal /classroom /library /krs /lapor-admin /profile` redirect to `/` when not logged in | UI |
| File storage | `FileStorage` service: S3-compatible bucket (Neon) with local-disk fallback; per-kind folders, size and extension limits (library 10 MB, reports 5 MB, avatars 2 MB; materials and submissions see the README table); presigned URLs valid 10 min; `GET /api/files/{kind}/{id}` re-checks access and redirects; old objects deleted on replace/delete | API (download links in UI) |
| Deployment | `Dockerfile` (php:8.4-apache), `docker/entrypoint.sh` (migrate, seed when `pengguna` is empty, `RESET_DB=1` runs `migrate:fresh`, config/route cache), `docker-compose.yml` (app + Postgres 16), `render.yaml` (Render free web service, `/up` health check) | - |
| Tests | PHPUnit feature tests: academic, admin, gaps, file upload, operations, password reset, seeder, web routes | - |

## 2. Mahasiswa

| Area | Features | Status |
| --- | --- | --- |
| Dashboard (portal) | name, NRP, departemen, semester, status, dosen wali; total SKS, number of classes, open tasks, **overdue tasks**, KRS status, upcoming tasks, recent announcements, enrolled classes | UI+API |
| Classroom | list of enrolled classes; per class: announcements, sections (bagian), materials with presigned download, tasks; only enrolled classes are viewable | UI+API |
| Tasks | task detail with deadline and status; submit an answer file (drag and drop) + comment; resubmit replaces the previous file; submission after the deadline is accepted and flagged `terlambat`; withdraw own submission while ungraded; see grade | UI+API (withdraw: API only) |
| KRS | `GET /api/krs`, detail with classes, SKS and grade; submit/replace a KRS for a semester (`POST /api/krs`, status becomes `Menunggu`); an approved KRS cannot be overwritten (409); download **KRS PDF** (dependency-free PDF generator) and print from the page | UI shows latest KRS, PDF, print; submitting a KRS is API only |
| Library | search by title/author, filter by category, paginated list, download | UI+API |
| Reports (Lapor Admin) | create a ticket with category and optional attachment, list own history with filters (category, status, search), withdraw a ticket while unanswered | UI (create, list) + API (filters, withdraw) |
| Profile | edit name, phone, address; upload/replace/remove photo (`POST /api/profile`, `DELETE /api/profile/photo`); change password (must differ from the current one) | UI+API (remove photo: API only) |

## 3. Dosen

| Area | Features | Status |
| --- | --- | --- |
| Dashboard | classes taught, submissions waiting for grading (`perlu_dinilai`), KRS waiting for approval (`krs_menunggu`) | API; portal banner in UI |
| Classroom authoring | post announcements; create sections; add material (title + file upload); create tasks (title, description, allowed format, deadline); edit/delete announcements, sections, materials (file replace via `POST`), tasks. Only the teaching dosen (or admin) may change a class | UI: create announcement/section/material/task; edit and delete are API only |
| Grading | list submissions per task, grade 0-100 with comment, only for the class's dosen | UI+API |
| KRS / perwalian | list KRS of own wali students and of own classes (filters `semester_tahun`, `status_acc`, `nrp`); approve / reject / set pending only as the student's dosen wali (`PATCH /api/krs/{id}/approve`); sets `tanggal_cetak` on approval; PDF download | API only (the sidebar item is labelled "Perwalian" but the page only shows the first KRS returned and its PDF; no approval screen) |
| Library | upload books (`POST /api/library`), delete books | API only |
| Reports | create own tickets, same as mahasiswa | UI+API |

## 4. Admin

The admin role is implemented in the **API** (`/api/admin/*`, middleware `admin`). The web UI has **no admin screens** and no admin tab yet; an admin only gets the shared pages (reports, profile) and `role` in `/api/me` for a future tab. Marked honestly as partial.

| Area | Features | Status |
| --- | --- | --- |
| Dashboard / stats | `GET /api/dashboard` returns `stats` for admin; `GET /api/admin/stats`: users per role, mata kuliah, kelas, KRS (pending), tugas, buku, tiket | API only |
| Accounts | list/search users, create mahasiswa / dosen / admin (account + profile in one transaction, optional password, generated password returned once), show, update, delete, reset password | API only |
| Controlled data | link a mahasiswa to a dosen wali (`PUT /admin/mahasiswa/{nrp}` sets `nip_wali`, departemen, semester, status); list dosen with student and class counts; students of a dosen | API only |
| Master data | CRUD mata kuliah (409 when used by a kelas); CRUD kelas (`kelas_id` max 12 chars, 409 when students are enrolled) | API only |
| KRS administration | list/filter, create, change status, delete, enroll/remove classes (quota not enforced), set `nilai_akhir` | API only |
| Library | list all, edit metadata or replace file, delete | API only |
| Reports | see all tickets (unanswered first), reply with status `Diproses` / `Selesai` / `Ditolak` and a response, delete tickets | UI+API |
| Safety | admin cannot delete self (403); deleting rows with dependent data returns 409 with a message; admin role cannot be changed after creation | API |

## 5. Demo data (seeder)

`DatabaseSeeder` is idempotent and transactional: 1 admin, 3 dosen, 8 mahasiswa (every KRS state: Disetujui, Menunggu, Ditolak), 8 mata kuliah, 8 classes, announcements, 12 sections, 10 materials, 11 tasks (overdue, due soon, later), 9 submissions (graded/ungraded, on time/late), 9 library books, 7 reports in all four statuses, placeholder PDF/TXT files uploaded to the configured disk (skipped when storage is unavailable). All demo passwords are `password`; accounts are listed in the README.

## 6. Known gaps (not implemented)

- No admin pages in the web UI (admin API only), no UI for KRS submission, KRS approval by the dosen wali, library upload, or edit/delete of class content.
- No notifications feed; the dashboard's announcements and tasks cover it.
- Multipart `PUT` is not supported by PHP, so file uploads that replace content use `POST`.
- Mahasiswa must have a dosen wali (`nip_wali` is NOT NULL); the admin role cannot be changed after the account is created.
- Forgot-password mail needs SMTP credentials in the environment; otherwise it is only logged.
