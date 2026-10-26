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
