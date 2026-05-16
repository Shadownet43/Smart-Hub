# Smart-Hub Management System

Sistem manajemen peminjaman peralatan (Smart Hub) berbasis API REST. Pengguna dapat mendaftar sebagai anggota, administrator mengelola inventaris peralatan, dan anggota dapat membuat peminjaman serta melakukan check-in/check-out sesuai alur bisnis.

## Tech stack

| Layer | Teknologi |
|--------|------------|
| Bahasa | PHP 8.3+ |
| Framework | [Laravel](https://laravel.com) 13 |
| Auth API | [Laravel Sanctum](https://laravel.com/docs/sanctum) |
| Database | MySQL (disarankan 8.x) |
| Testing | PHPUnit 12 |

## Cara install dan setup

### Prasyarat

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `json`, `ctype`, `fileinfo`
- Composer 2.x
- MySQL server
- Node.js & npm (opsional, untuk asset front-end bila dikembangkan)

### Langkah

1. **Clone repositori** dan masuk ke folder proyek:
   ```bash
   cd Smart-Hub
   ```

2. **Dependensi PHP:**
   ```bash
   composer install
   ```

3. **Environment:**
   ```bash
   copy .env.example .env   # Windows
   php artisan key:generate
   ```
   Sesuaikan `.env`, minimal:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smarthub
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Migrasi dan seeder:**
   ```bash
   php artisan migrate --seed
   ```

5. **Link storage** (jika menggunakan upload gambar peralatan):
   ```bash
   php artisan storage:link
   ```

6. **Jalankan aplikasi:**
   ```bash
   php artisan serve
   ```
   API berada di bawah prefix `http://127.0.0.1:8000/api/v1` (sesuaikan host/port).

### Akun uji (dari seeder)

Setelah `migrate --seed`, contoh login:

| Peran | Email | Sandi |
|--------|--------|--------|
| Admin | `admin@smarthub.com` | `password` |
| Anggota | `member1@smarthub.com` … `member5@smarthub.com` | `password` |

### Pengujian otomatis

PHPUnit memakai database MySQL terpisah **`smarthub_testing`** (lihat `phpunit.xml`). Buat database kosong tersebut sebelum menjalankan tes, atau sesuaikan konfigurasi jika memakai SQLite (`pdo_sqlite`).

```bash
php artisan test
```

## Daftar endpoint API

Semua rute JSON di bawah memakai prefix **`/api/v1`**. Header umum:

- `Accept: application/json`
- `Content-Type: application/json` (untuk body JSON)
- Untuk rute terproteksi: `Authorization: Bearer {token_sanctum}`

### Autentikasi

#### `POST /api/v1/auth/register`

**Request (contoh):**
```json
{
  "name": "Anggota Baru",
  "email": "baru@example.com",
  "password": "rahasia123",
  "password_confirmation": "rahasia123",
  "phone": "081234567890"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registrasi berhasil. Selamat datang di Smart-Hub.",
  "data": {
    "user": {
      "id": 1,
      "name": "Anggota Baru",
      "email": "baru@example.com",
      "role": "Anggota",
      "phone": "081234567890",
      "created_at": "16 Mei 2026 10:30"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

#### `POST /api/v1/auth/login`

**Request:**
```json
{
  "email": "admin@smarthub.com",
  "password": "password"
}
```

**Response (200):** struktur sama seperti register (`success`, `message`, `data.user`, `data.token`, `data.token_type`).

#### `POST /api/v1/auth/logout` — butuh token

**Response (200):**
```json
{
  "success": true,
  "message": "Anda telah keluar dari sesi.",
  "data": {
    "user": null,
    "token": null,
    "token_type": "Bearer"
  }
}
```

#### `GET /api/v1/auth/me` — butuh token

**Response (200):** data profil pengguna (dan riwayat peminjaman singkat bila ada).

---

### Peralatan (`equipment`)

#### `GET /api/v1/equipment` — butuh token

Query opsional: `search`, `category`, `status` (`available` | `borrowed` | `maintenance`).

**Response (200):** body paginasi + `success`, `message`, `meta.summary` (ringkasan jumlah per status).

#### `GET /api/v1/equipment/{id}` — butuh token

**Response (200):** detail peralatan (termasuk agregat relasi jika dimuat).

#### `POST /api/v1/equipment` — butuh token **admin**

**Request (multipart/json sesuai validasi):** `name`, `category`, `description`, `status`, `stock`, `image` (file opsional).

**Response (201):** `success`, `message`, `data` (objek peralatan).

#### `PUT` / `PATCH /api/v1/equipment/{id}` — butuh token **admin**

**Response (200):** `success`, `message`, `data` (peralatan terbaru).

#### `DELETE /api/v1/equipment/{id}` — butuh token **admin**

**Response (200):** `success`, `message`, `data` (biasanya `null` jika sukses soft delete).

---

### Peminjaman (`bookings`)

#### `GET /api/v1/bookings` — butuh token

Anggota melihat peminjaman sendiri; admin dapat melihat lebih luas. Query opsional: `status`.

#### `POST /api/v1/bookings` — butuh token

**Request (contoh):**
```json
{
  "equipment_id": 1,
  "start_time": "2026-05-20 09:00:00",
  "end_time": "2026-05-20 12:00:00",
  "notes": "Untuk tugas kuliah"
}
```

**Response (201):** detail peminjaman (status awal biasanya menunggu persetujuan).

#### `GET /api/v1/bookings/{id}` — butuh token

**Response (200):** detail peminjaman + relasi `user` / `equipment`.

#### `PUT /api/v1/bookings/{id}` — butuh token **admin**

Memperbarui status (mis. `approved`, `rejected`). Body contoh: `{ "status": "approved" }`.

#### `POST /api/v1/bookings/{id}/checkin` — butuh token

**Response (200):** peminjaman terbaru setelah check-in (jika memenuhi syarat).

#### `POST /api/v1/bookings/{id}/checkout` — butuh token

**Response (200):** peminjaman selesai / dikembalikan.

#### `DELETE /api/v1/bookings/{id}` — butuh token

Membatalkan peminjaman (biasanya hanya saat status masih menunggu).

---

## Git workflow

Alur yang dipakai dalam pengembangan proyek ini:

1. **`main` / `master`** — cabang stabil referensi (commit awal / rilis).
2. **`develop`** — cabang integrasi fitur; hasil gabungan pekerjaan fitur.
3. **Cabang fitur** — satu topik per cabang, misalnya:
   - `feature/api-authentication`
   - `feature/equipment-crud`
   - `feature/booking-management`
   - `feature/email-notification`
4. **Merge ke `develop`** memakai **`git merge --no-ff`** dengan pesan merge yang jelas, sehingga riwayat fitur tetap terbaca di graf Git.
5. Pekerjaan paralel tidak mengubah `master` sampai sengaja di-merge atau di-tag rilis.

Sebelum submit, disarankan membersihkan cache artisan, migrasi + seeder, dan menjalankan seluruh tes (lihat bagian final check).

## Final check (sebelum submit)

Urutan perintah yang dapat dijalankan:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```

> **Peringatan:** `migrate:fresh --seed` akan **menghapus semua tabel** pada database yang dikonfigurasi di `.env`. Gunakan hanya di lingkungan pengembangan.

## Mahasiswa

| Nama | NIM |
|------|-----|
| M. Zakiyudin Al-Muyasar | 411212079 |

---

## Lisensi

Proyek ini untuk keperluan akademik. Framework Laravel dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
