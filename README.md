# Web Programming - Quiz 2

| NRP | Name | Class |
| :--- | :--- | :--- |
| 5025251128 | Budiman Setiono | Webpro (D) |
| 5025251129 | Muhammad Brahmana Priambudi | Webpro (D) |
| 5025251131 | Muhammad Azka Asyrfany | Webpro (D) |

---

## 1. Project Overview

Web classroom ITS smol reverse engineered 

---

## 2. Database Models

### CDM
![cdm](/docs/cdm.png)  
### PDM
![pdm](/docs/pdm.png)

## 3. Features
1. Login (role: mahasiswa, dosen, admin)  
2. Dosen setujui frs  
3. Mahasiswa membaca materi kelas  
4. Mahasiswa mengerjakan tugas  
5. Dosen menilai dan mengomentari pengerjaan tugas mahasiswa  
6. Dosen menambah materi, tugas, atau milestone kelas  
7. Mahasiswa cetak krs  
8. Mahasiswa membaca buku di e-perpustakaan  
9. Dosen/Admin mengelola buku di e-perpustakaan  
10. Mahasiswa/Dosen melaporkan bug  
11. Admin membalas laporan bug  
12. Pengguna(admin/dosen/mahasiswa) dapat menyunting profil  
13. Admin manajemen website (/admin-panel)

---

## 3. Web Pages & Screenshots

### A. Homepage (`/`)

---



### Conclusion
e

---

## Docker / Deploy

The image is based on `php:8.4-apache` (dependencies require PHP >= 8.4.1).

**Local (Docker + Postgres):**

```bash
export APP_KEY=$(php artisan key:generate --show)
docker compose up --build   # API at http://localhost:8080/api, health at /up
```

The container runs `migrate --force` on start and seeds the database only when the `pengguna` table is empty.
Example login: `POST /api/login` with `{"username": "198501012010121001", "password": "admin"}`.

**Render (free Docker web service + Neon Postgres):** the `render.yaml` blueprint defines the service.
Set these env vars in the Render dashboard: `APP_KEY`, `APP_URL`, and `DB_URL`
(Neon connection string, e.g. `postgresql://user:pass@host/db?sslmode=require`). The health check path is `/up`.

---

## Admin API

All endpoints live under `/api/admin/*`, require `Authorization: Bearer <token>` (from `POST /api/login`) and the `admin` role.
Non-admin users get `403 {"message": "Akses ditolak. Hanya administrator yang dapat mengakses endpoint ini."}`; unauthenticated requests get `401`.
`POST /api/login`, `GET /api/me` and `GET /api/dashboard` include `role` (`mahasiswa`, `dosen`, `admin`) so the frontend can show the **Admin** tab instead of Classroom. For admins `/api/dashboard` returns `stats` instead of a classroom list.
Lists are paginated (Laravel paginator, `?page=`, `?per_page=` up to 100) and validation errors return `422` with Indonesian messages.

| Method | Path | Description |
|---|---|---|
| GET | `/admin/stats` | Counters: users per role, mata kuliah, kelas, KRS (pending), tugas, buku, tiket |
| GET | `/admin/users?role=&q=&per_page=` | List users (search username/nama/email) with profile + dosen wali |
| POST | `/admin/users` | Create `mahasiswa`, `dosen` or `admin` (account + profile in one transaction) |
| GET / PUT / DELETE | `/admin/users/{user_id}` | Show / update account and profile / delete |
| PATCH | `/admin/users/{user_id}/password` | Reset password (given, or generated and returned once) |
| GET | `/admin/mahasiswa?q=&nip_wali=&status=&departemen=` | List mahasiswa with their dosen wali |
| GET / PUT | `/admin/mahasiswa/{nrp}` | Show / set `nip_wali`, `departemen`, `semester_aktif`, `status` |
| GET | `/admin/dosen?q=&status=` | List dosen with `mahasiswa_bimbingan_count` and `classroom_count` |
| GET | `/admin/dosen/{nip}/mahasiswa` | Students under a dosen wali |
| GET / POST | `/admin/mata-kuliah` | List (`q`, `semester`) / create |
| GET / PUT / DELETE | `/admin/mata-kuliah/{kode_mk}` | Show / update / delete (409 if used by a kelas) |
| GET / POST | `/admin/kelas` | List (`q`, `kode_mk`, `nip`, `semester_tahun`) / create (`kelas_id` max 12 chars) |
| GET / PUT / DELETE | `/admin/kelas/{kelas_id}` | Show / update (dosen pengampu, mata kuliah, kuota) / delete (409 if students enrolled) |
| GET / POST | `/admin/krs` | List (`nrp`, `semester_tahun`, `status_acc`) / create a KRS for a student |
| GET / DELETE | `/admin/krs/{krs_id}` | Show / delete KRS |
| PATCH | `/admin/krs/{krs_id}/status` | `status_acc`: `Disetujui`, `Ditolak`, `Menunggu` |
| POST | `/admin/krs/{krs_id}/kelas` | Enroll in a kelas (override, quota not enforced) |
| PATCH / DELETE | `/admin/krs/{krs_id}/kelas/{kelas_id}` | Set `nilai_akhir` / remove the enrollment |
| GET | `/admin/library?q=&kategori=&publisher_id=` | List all library items |
| PUT / DELETE | `/admin/library/{buku_id}` | Edit metadata / remove item |

Admins can already see every report via `GET /api/reports` and reply with `PATCH /api/reports/{id}/reply`.

Safety rules: an admin cannot delete their own account (`403`); deleting a user, dosen, mata kuliah, or kelas that still has dependent data
(KRS, submissions, perwalian students, classes, tickets) returns `409` with an explanatory `message` instead of failing.

### Create an account

`POST /api/admin/users`

```json
{
  "role": "mahasiswa",
  "username": "budi",
  "nama_lengkap": "Budi Santoso",
  "email": "budi@student.its.ac.id",
  "nrp": "5025250001",
  "nip_wali": "1011011",
  "departemen": "Teknik Informatika",
  "semester_aktif": 1
}
```

`role=dosen` needs `nip` and `departemen`; `role=admin` needs only the common fields. `password` is optional (min 8). Response `201`:

```json
{
  "message": "Akun berhasil dibuat.",
  "user": { "user_id": "MAH_K3X9Q2ZA", "username": "budi", "role": "mahasiswa", "mahasiswa": { "nrp": "5025250001", "nip_wali": "1011011" } },
  "generated_password": "Hk3nQ8vTz2Lp"
}
```

`generated_password` is only present when no password was supplied and is shown once.

### Link a mahasiswa to a dosen wali

`PUT /api/admin/mahasiswa/5025250001` with `{"nip_wali": "1011011", "status": "Aktif"}` returns `200`:

```json
{ "message": "Data mahasiswa berhasil diperbarui.", "mahasiswa": { "nrp": "5025250001", "nip_wali": "1011011", "dosen_wali": { "nip": "1011011" } } }
```

### Stats

`GET /api/admin/stats` returns `200`:

```json
{ "stats": { "pengguna": { "total": 3, "mahasiswa": 1, "dosen": 1, "admin": 1 }, "mata_kuliah": 5, "kelas": 3,
  "krs": { "total": 1, "menunggu": 0 }, "tugas": 3, "pengumpulan_tugas": 2, "buku": 3, "tiket": { "total": 2, "terbuka": 1 } } }
```

### Deletion conflict

`DELETE /api/admin/users/{user_id}` for a dosen who still advises students returns `409`:

```json
{ "message": "Dosen tidak dapat dihapus karena masih menjadi dosen wali mahasiswa. Pindahkan perwalian terlebih dahulu." }
```

## File uploads

Uploads are stored in the private S3-compatible bucket (Neon object storage) through `App\Services\FileStorage`.
When `AWS_BUCKET`/`AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` are not set (local dev, tests) it falls back to the private `local` disk automatically.
Env: `FILESYSTEM_DISK=s3`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT=true`.
The S3 client uses `request_checksum_calculation=when_required` (override with `AWS_REQUEST_CHECKSUM_CALCULATION`) because non-AWS providers reject the SDK's default checksums.

| Kind | Endpoint (multipart/form-data) | Field | Object key | Max | Types |
| --- | --- | --- | --- | --- | --- |
| Material | `POST /api/sections/{id}/materials` (dosen of the class / admin) | `file` | `materials/{kelas}/…` | 10 MB | pdf, doc(x), ppt(x), xls(x), txt, md, zip, rar, 7z, png, jpg, mp4, c, cpp, java, py |
| Submission | `POST /api/tasks/{id}/submit` (mahasiswa in the class) | `file` | `submissions/{tugas}/{nrp}/…` | 10 MB | pdf, doc(x), ppt(x), xls(x), txt, md, zip, rar, 7z, png, jpg, c, cpp, java, py, sql — further limited to the task's `format_file` |
| Library | `POST /api/library` (dosen/admin); `POST /api/admin/library/{id}` replaces the file | `file` | `library/{buku_id}/…` | 10 MB | pdf, epub, doc(x), ppt(x), txt, zip |
| Report | `POST /api/reports` | `lampiran` | `reports/{tiket}/…` | 5 MB | pdf, png, jpg, txt, zip, doc(x) |
| Avatar | `POST /api/profile` (or `PUT` without a file) | `foto` | `avatars/{user_id}/…` | 2 MB | png, jpg, webp |

- The old string fields (`file_materi_key`, `file_jawaban_key`, `file_dokumen_key`, `lampiran_bukti_key`, `foto_profil_key`) still work, but a real file is preferred and a legacy key may not point into the structured folders above.
- PHP does not parse multipart `PUT` bodies, so send multipart as `POST` (a `POST /api/profile` alias exists; for admin library use `POST` or `POST` + `_method=PUT`).
- Replacing a submission, library document or avatar deletes the previous object; deleting a library item deletes its file. A submission past the task deadline is accepted with `terlambat: true` (a date-only deadline counts until the end of that day).
- JSON for materials, submissions, library items, reports and users includes `file_url` / `foto_url`: a presigned URL valid for 10 minutes (on the local fallback, a signed `/files/local` URL).
- `GET /api/files/{kind}/{id}` (`materials|submissions|library|reports|avatars`) re-checks access and returns `302` to a fresh presigned URL, or JSON `{url, nama_file, expires_at}` with `Accept: application/json`.
  Access: materials — students enrolled in the class, the class's dosen, admin; submissions — the owning student, the class's dosen, admin; reports — owner and admin; library and avatars — any authenticated user.
- Viewing a class (`GET /api/classrooms/{id}`) and a task now requires membership (enrolled student, teaching dosen, or admin); changing class content requires the teaching dosen or admin.
- Tests use `Storage::fake('s3')`; run them on Postgres with `DB_CONNECTION=pgsql … php artisan test`.
- Cleanup endpoints (remove the stored files too): `DELETE /api/materials/{id}`, `DELETE /api/tasks/{id}`, `DELETE /api/sections/{id}` (teaching dosen/admin) and `DELETE /api/submissions/{id}` (owner while ungraded, class dosen, admin).

## Forgot password (SMTP)

`POST /api/forgot-password` `{ "email": "…" }` (or `{ "username": "…" }`) always returns `200` with the same message
(`"Jika akun terdaftar, instruksi pengaturan ulang kata sandi telah dikirim ke email yang terkait."`), whether or not the account exists.
It is throttled to 5 requests/hour per email+IP (`429` after that).
For an existing account a single-use token (stored hashed in `password_reset_tokens`, valid 60 minutes) is generated and an Indonesian HTML+plain-text mail is sent
**synchronously over plain SMTP** (no mail API/SDK). Delivery failures are logged and never change the response.
The mail contains a link `FRONTEND_URL/reset-password?token=…&email=…` (served by a small page in this app) and the raw token for API-only clients.

`POST /api/reset-password` `{ "email", "token", "password", "password_confirmation" }` → `200` and all Sanctum tokens of the user are revoked;
an invalid/expired/used token gives `422` (`errors.token`). Limited to 10 attempts/hour per email+IP.

| Env | Value |
| --- | --- |
| `MAIL_MAILER` | `log` (default; the mail is written to the log) or `smtp` |
| `MAIL_HOST`, `MAIL_PORT` | SMTP server and port (e.g. `587`) |
| `MAIL_SCHEME` | empty/`smtp` = STARTTLS when offered, `smtps` = implicit TLS (port 465) |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP credentials (`sync: false` in `render.yaml`) |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | `support@brahmanapriambudi.my.id`, `Portal Quiz 2 WebPro` |
| `FRONTEND_URL` | base URL of the web app for the reset link (defaults to `APP_URL`) |

## API gaps filled

Audit of `docs/design-elements/*.png` (login, dashboard, class materi/tugas, KRS, library, lapor + states, profile + states) against the API.
Already covered before: login/logout/`me`, dashboard, classes, tasks + submit, grading, KRS list/submit/approve, library search + download, reports + admin reply, profile + photo + password, admin module.

| Design element | Gap | Now |
| --- | --- | --- |
| KRS "Unduh PDF" / "Cetak" | no PDF export | `GET /api/krs/{id}/pdf` (dependency-free PDF; owner, dosen wali, dosen of a class in it, admin). The button downloads it; "Cetak" prints |
| KRS semester selector | no filter | `GET /api/krs?semester_tahun=&status_acc=&nrp=` |
| KRS privacy | any user could read any KRS by id; any dosen could approve any KRS | `show`/`pdf` require owner / wali / teaching dosen / admin; `approve` only the student's dosen wali or admin; a dosen only lists KRS of their students and classes; an approved KRS cannot be overwritten by the student (`409`) |
| Class: edit/delete announcement | missing | `PUT/DELETE /api/announcements/{id}` |
| Class: edit section / material / task | only create + delete existed | `PUT /api/sections/{id}`, `PUT /api/materials/{id}` (`POST` + `file` replaces the document), `PUT /api/tasks/{id}` |
| Mark / grade | any dosen could grade any submission | only the class's dosen or admin |
| Dashboard "n tugas sudah terlambat" | missing | `summary.tugas_terlambat`, `summary.status_krs`; dosen dashboard: `perlu_dinilai`, `krs_menunggu` |
| Lapor: category dropdown, history filter, withdraw | missing | `GET /api/reports/categories` (categories + statuses), `GET /api/reports?kategori=&status=&search=`, `DELETE /api/reports/{id}` (owner while unanswered, or admin; removes the attachment) |
| Profile: "Nama Lengkap" is editable | name was ignored | `PUT/POST /api/profile` accepts `nama_lengkap` (required when sent) |
| Profile: "Ganti Foto" | could not remove a photo | `DELETE /api/profile/photo` |
| Profile: change password | new password could equal the old one | `different:current_password` rule |
| Login: "Lupa kata sandi?" | the form only simulated the request | wired to `POST /api/forgot-password`; `/reset-password` page + `POST /api/reset-password` |
| Browser login → portal | sessions were `array` (lost every request: CSRF `419`, `/portal` redirected back to login); the custom domain was not a Sanctum stateful host; the UI never sent its token | sessions use the `sessions` table (an `array` driver is upgraded to `database` outside tests), the request host is stateful, `public/js/api.js` sends `Authorization: Bearer <auth_token>`, proxies are trusted (HTTPS, client IP) |

Cross-cutting:
- **JSON errors** for `/api/*` (or `Accept: application/json`), in Indonesian: `401` "belum masuk", `403`, `404`, `405`, `413`, `419`, `422` (`message` = first error, plus `errors`), `429`, `500` (generic, no internals). HTML pages keep the normal error pages.
- **CORS** (`config/cors.php`): paths `api/*` and `sanctum/csrf-cookie`, all methods/headers (incl. `Authorization`), `Content-Disposition` exposed. `CORS_ALLOWED_ORIGINS` is a comma separated list, default `*`; with explicit origins credentials are allowed.
- **Rate limiting**: `POST /api/login` 10/min per username+IP and 30/min per IP (`429`); all other `/api` routes 120/min per user or IP; forgot/reset password limits as above.
- Not implied by any design and therefore not built: a notifications feed (the dashboard's announcements/tasks cover it).
- Report statuses are `Terkirim`, `Diproses`, `Selesai`, `Ditolak`; the design's "Baru" badge is `Terkirim`.

## Demo data and accounts

`php artisan migrate:fresh --seed` (or `RESET_DB=1` on the container) produces a complete, idempotent demo dataset
(the seeder runs in one transaction and does nothing when the data already exists, except making sure `admin` exists).
**All demo accounts use the password `admin`.** Mahasiswa and dosen log in with their NRP / NIP.

| Role | Username | Name | Notes |
| --- | --- | --- | --- |
| admin | `admin` | Administrator Akademik | admin API, answers reports |
| dosen | `198501012010121001` | Dr. Sarwosri, S.Kom., M.T. | wali of 5025251131, 5025251169, 5025251132; teaches Sistem Operasi (B), PBO (A), Basis Data (C) |
| dosen | `197803152005011002` | Prof. Dr. Ir. Bambang Nugroho, M.Sc. | wali of 5025251128, 5025251129, 5025251172; teaches Jaringan Komputer (A), Kecerdasan Buatan (A), Dasar Pemrograman (A, 2025/Genap) |
| dosen | `198208202008012003` | Dr. Rina Kartika, S.T., M.Sc. | wali of 5025251180, 5025251195; teaches Struktur Data (A), Pemrograman Web (B) |
| mahasiswa | `5025251131` | Muhammad Azka Asyrafany | KRS 2026/Ganjil **Disetujui** (SO, PBO, BD, JK = 13 SKS) + graded 2025/Genap; graded, late and on-time submissions; 3 reports (Diproses/Terkirim/Selesai) |
| mahasiswa | `5025251169` | Kevin Lontong | KRS **Menunggu**; overdue task without submission; open+answered report |
| mahasiswa | `5025251132` | Siti Nurhaliza | KRS Disetujui; graded + ungraded submissions |
| mahasiswa | `5025251128` | Budiman Setiono | KRS Disetujui; late graded submission |
| mahasiswa | `5025251129` | Muhammad Brahmana Priambudi | KRS **Menunggu**; open report |
| mahasiswa | `5025251172` | Rizky Pratama | KRS **Ditolak** (7 classes); answered/rejected report |
| mahasiswa | `5025251180` | Anisa Putri | KRS Disetujui |
| mahasiswa | `5025251195` | Fajar Nugroho | KRS **Menunggu** |

Also seeded: 8 mata kuliah, 8 classes (7 in 2026/Ganjil + 1 past), 8 announcements, 12 sections (Sistem Operasi (B) has the 4 sections of the design),
10 materials, 11 tasks (overdue, due soon and later), 9 submissions (graded/ungraded, on time/late), 9 library books and 7 reports
(`Terkirim`, `Diproses`, `Selesai`, `Ditolak`; open and answered).

Placeholder files: small real PDF/TXT documents are uploaded for materials, submissions and library books to the configured disk (S3 on Render, otherwise the local disk).
Seeding never fails because of storage: if the disk cannot be written (or `SEED_FILES=false`, or in tests) the rows are created with `file_*_key = null` (`file_url` is `null`).

---

## Documentation

- [docs/FEATURES.md](docs/FEATURES.md): list of implemented features per role and area, with known gaps.
- [docs/cdm.ddr](docs/cdm.ddr): conceptual data model (ddr `CDM` diagram).
- [docs/pdm.ddr](docs/pdm.ddr): physical PostgreSQL data model (ddr `PDM` diagram), including the framework system tables.
