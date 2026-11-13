# BlogVerse Pro — PABWE 2026 Praktikum 8 (Studi Kasus: Aplikasi Blog)

Platform publikasi artikel **multi-tier** dengan arsitektur **Clean Architecture & Feature-First**, dibangun dengan **Laravel 13**, **Inertia.js**, **React**, **PostgreSQL** (`pabwe_p8_blog`), **PhpOffice/PhpSpreadsheet**, dan integrasi **OpenAI**.

## Tiga Tingkatan Akses

| Tingkat | URL | Hak akses |
|---|---|---|
| Umum (Guest) | `/`, `/articles`, `/articles/{slug}`, `/login`, `/register` | Membaca artikel publik, cari judul/isi, filter kategori, pembaca Markdown + cover. Tanpa sidebar: Sticky Top Navbar, tata letak baca lebar, footer editorial, tautan Login/Register di navbar, hero, akhir artikel, dan footer. |
| Anggota (Penulis) | `/dashboard/posts`, `/dashboard/posts/{post}` | CRUD artikel sendiri, toggle Draf/Publikasi, foto sampul, ekspor/impor Excel, generate artikel via OpenAI. |
| Admin | `/admin/users`, `/admin/posts`, `/admin/users/{user}/posts` | Daftar pengguna, monitoring seluruh artikel (read-only), ekspor Excel seluruh sistem / per-user. |

## Menjalankan Lokal

```bash
psql -U postgres -c "CREATE DATABASE pabwe_p8_blog;"
composer setup            # install, .env, key, migrate, npm install, build
php artisan db:seed       # akun demo + artikel contoh
php artisan storage:link
composer dev              # atau: php artisan serve
```

Akun demo: `admin` / `admin` (Admin) dan `member` / `member` (Penulis).

Konfigurasi `.env` penting:

```
DB_CONNECTION=pgsql
DB_DATABASE=pabwe_p8_blog
DB_USERNAME=postgres
DB_PASSWORD=postgres
OPENAI_ENDPOINT=
OPENAI_KEY=
OPENAI_MODEL=
```

## Struktur Feature-First

```
app/Features/{Admin,Ai,Auth,Post,Profile}/
  Post/ Contracts · Controllers · Enums · Models · Policies · Repositories · Services
  Ai/   Contracts (AiClientInterface) · DTO (GeneratedPostItem) · Services (OpenAiPostGeneratorService)
app/Http/Middleware/ EnsurePostOwnership · EnsureRole · HandleInertiaRequests
resources/js/ Layouts/AppLayout.jsx · Pages/{Admin,Auth,Post,Profile,Public} · Components/
```

Prinsip: controller tipis → service (logika bisnis) → repository berbasis interface (DIP) → model Eloquent. Otorisasi berlapis: middleware `role`, `owns.post`, dan `PostPolicy`.

## Model Data `posts`

`id`, `user_id` (cascade), `title`, `slug` (unik), `category` (enum `PostCategory`), `excerpt`, `content` (longText Markdown), `cover_image_path`, `is_published`, `published_at`, `timestamps`.

Kategori: Teknologi, Pendidikan, Gaya Hidup, Bisnis, Opini, Umum.

## Keputusan Teknis Penting

- **Slug** dibuat unik otomatis dari judul dan dibiarkan stabil saat judul diubah (tautan publik tidak putus).
- **Pencarian** memakai `LOWER(...) LIKE` sehingga case-insensitive di PostgreSQL maupun SQLite.
- **Excel**: seluruh sel ditulis sebagai string eksplisit (mencegah *formula injection*); impor dibatasi 200 baris per berkas.
- **AI**: hasil generate disimpan sebagai **draf**; jumlah artikel dibatasi 1–5 (`type="number"` + validasi klien dan server).
- **Performa/Aksesibilitas/SEO**: halaman di-*code-split* per rute, TOAST UI dimuat dinamis (Viewer saja untuk publik), font sistem, kontras warna WCAG AA, landmark HTML, label form, `lang="id"`, meta description per halaman.

## Pengujian

```bash
composer test     # phpunit --coverage-text
```

129 test (Unit + Feature) dengan **100% coverage** pada `app/`. Pipeline `Jenkinsfile` menjalankan composer audit, build, test + gerbang coverage 100%, Trivy, package, dan deploy.
