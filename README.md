# Web Programming - Quiz 2

| NRP | Name | Class |
| :--- | :--- | :--- |
| 5025251128 | Budiman Setiono | Webpro (D) |
| 5025251129 | Muhammad Brahmana Priambudi | Webpro (D) |
| 5025251131 | Muhammad Azka Asyrfany | Webpro (D) |

---

## 1. Project Overview

...

---

## 2. Page Routing & URL Structure

...

---

## 3. Web Pages & Screenshots

### A. Homepage (`/`)

---

## 4. Implementation Details & Architecture
...

### Conclusion


---

## Docker / Deploy

The image is based on `php:8.4-apache` (dependencies require PHP >= 8.4.1).

**Local (Docker + Postgres):**

```bash
export APP_KEY=$(php artisan key:generate --show)
docker compose up --build   # API at http://localhost:8080/api, health at /up
```

The container runs `migrate --force` on start and seeds the database only when the `pengguna` table is empty.
Example login: `POST /api/login` with `{"username": "198501012010121001", "password": "password"}`.

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
