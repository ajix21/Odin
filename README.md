# Project ODIN

Platform OSINT (Open Source Intelligence) berbasis web yang dibangun dengan Laravel 12. Menyediakan berbagai alat investigasi digital dengan sistem manajemen pengguna berbasis peran.

---

## Fitur

### Tools OSINT
| Tool | Deskripsi |
|------|-----------|
| **Phone Lookup** | Mencari informasi pemilik nomor telepon via GetContact |
| **LeakOSINT** | Menelusuri kebocoran data (email, nomor HP, dll.) dari database breach |
| **Multicheck** | Mengecek keberadaan username di berbagai platform sekaligus |
| **Email OSINT** | Analisis email — validasi, deteksi disposable, gravatar |
| **Phone Info** | Analisis nomor telepon — negara, operator, tipe (mobile/VOIP) |
| **IP Geolocation** | Lookup lokasi dan ISP dari alamat IP |
| **WHOIS** | Pencarian informasi registrasi domain |
| **Toutatis** | OSINT akun Instagram menggunakan session ID |

### Manajemen & Administrasi
- **Dashboard** — statistik pencarian harian/total, tool paling aktif, log aktivitas terbaru
- **Manajemen User** — CRUD user dengan 3 peran: `admin`, `operator`, `viewer`
- **Log Aktivitas** — riwayat pencarian per tool dan log percobaan login
- **Pengaturan API** — konfigurasi token/key untuk setiap layanan eksternal, tersimpan terenkripsi
- **Riwayat Pencarian** — history Phone Lookup dan LeakOSINT per user
- **Export** — hasil dapat diekspor ke PDF dan Excel

### Keamanan
- Autentikasi session-based dengan tracking login attempt
- Role-based access control (RBAC)
- Rate limiting 30 request/menit per tool
- Logging IP address setiap pencarian
- Penyimpanan API key terenkripsi di database

---

## Persyaratan Sistem

- PHP >= 8.2
- Composer
- Node.js & NPM
- SQLite (default) atau MySQL/MariaDB

---

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd project-odin
```

### 2. Install Dependensi

```bash
composer install
npm install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` sesuai kebutuhan:

```env
APP_NAME="Project ODIN"
APP_URL=http://localhost

# Database (SQLite default, tidak perlu konfigurasi tambahan)
DB_CONNECTION=sqlite

# Gunakan MySQL jika diperlukan:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=odin
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4. Migrasi Database

```bash
php artisan migrate
```

### 5. Build Assets

```bash
npm run build
```

### 6. Buat Akun Admin Pertama

Jalankan tinker untuk membuat user admin:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name'      => 'Administrator',
    'username'  => 'admin',
    'email'     => 'admin@example.com',
    'password'  => bcrypt('Password@123'),
    'role'      => 'admin',
    'is_active' => true,
]);
```

### 7. Jalankan Aplikasi

```bash
php artisan serve
```

Akses di `http://localhost:8000`

---

### Instalasi Cepat (Satu Perintah)

```bash
composer run setup
```

Perintah ini akan menjalankan `composer install`, generate key, migrate database, `npm install`, dan build assets secara otomatis.

---

## Konfigurasi API Keys

Setelah login sebagai admin, buka menu **Settings** untuk mengisi API key yang dibutuhkan setiap tool:

| Setting | Tool | Cara Mendapatkan |
|---------|------|-----------------|
| `getcontact_token` | Phone Lookup | Dari aplikasi GetContact (intercept traffic) |
| `getcontact_final_key` | Phone Lookup | Dari aplikasi GetContact |
| `getcontact_client_device_id` | Phone Lookup | Dari aplikasi GetContact |
| `leakosint_api_token` | LeakOSINT | Daftar di leakosint.io |
| `leakosint_api_url` | LeakOSINT | URL endpoint API LeakOSINT |
| `ipinfo_token` | Phone Info & IP Geo | Daftar di ipinfo.io |
| `instagram_session_id` | Toutatis | Cookie `sessionid` dari browser saat login Instagram |

Semua nilai disimpan terenkripsi di database.

---

## Peran Pengguna

| Peran | Dashboard | Tools OSINT | History | Admin Panel | Settings |
|-------|-----------|-------------|---------|-------------|----------|
| `admin` | ✅ | ✅ | ✅ (semua user) | ✅ | ✅ |
| `operator` | ✅ | ✅ | ✅ (milik sendiri) | ❌ | ❌ |
| `viewer` | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Development

Menjalankan semua service sekaligus (server, queue, log, vite):

```bash
composer run dev
```

Menjalankan test:

```bash
composer run test
```

---

## Stack Teknologi

- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Blade, Tailwind CSS 4, Vite
- **Database**: SQLite / MySQL
- **Library**: `giggsey/libphonenumber-for-php`, `io-developer/php-whois`
- **Export**: jsPDF, jspdf-autotable, xlsx
