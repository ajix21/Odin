# Project ODIN — Design Specification
**Date:** 2026-05-17  
**Status:** Approved  
**Stack:** Laravel 11, PHP 8.2+, MySQL, Tailwind CSS 4.0, Vite

---

## 1. Overview

Project ODIN adalah platform Open Source Intelligence (OSINT) terpadu yang menggabungkan tiga project:

| Sumber | Kontribusi |
|---|---|
| **Getcontact-App** | Phone Lookup tool (GetContact API), design system |
| **Osint** | Auth system (RBAC), LeakOSINT search, admin panel, audit logs |
| **OsintPlus** | 6 OSINT tools diport ke PHP: Multicheck, Email OSINT, Phone Info, IP Geo, WHOIS, Toutatis |

**Icon/Brand:** `icon.png` — mata di atas, dua elang, globe dengan kaca pembesar, warna biru deep. Digunakan di navbar, favicon, dan halaman login.

---

## 2. Authentication & Authorization

- **Sistem:** Full login, semua halaman membutuhkan autentikasi
- **RBAC:** 3 role — `admin`, `operator`, `viewer`
- **Brute-force protection:** Max 5 attempt per 15 menit per IP/username
- **Rate limiting:** Search 30 request/menit (operator + admin)
- **Security headers:** X-Frame-Options DENY, CSP, nosniff, Referrer-Policy

| Fitur | Admin | Operator | Viewer |
|---|:---:|:---:|:---:|
| Akses semua tools | ✓ | ✓ | ✗ |
| Lihat history sendiri | ✓ | ✓ | ✓ |
| Lihat semua history | ✓ | ✗ | ✗ |
| Admin panel + users | ✓ | ✗ | ✗ |
| Settings/credentials | ✓ | ✗ | ✗ |

---

## 3. Database Schema (4 Tabel)

### `users`
```sql
id, name, username (unique), email (unique), password
role ENUM('admin','operator','viewer')
is_active BOOLEAN DEFAULT true
api_token TEXT (encrypted)
last_login_at TIMESTAMP NULL
last_login_ip VARCHAR(45) NULL
timestamps, softDeletes
INDEX: role, is_active
```

### `search_logs`
```sql
id, user_id FK → users.id
tool ENUM('getcontact','leakosint')
query TEXT
result_json JSON NULL
status ENUM('success','failed')
error_message TEXT NULL
ip_address VARCHAR(45)
timestamps
INDEX: user_id, tool, created_at
```

### `login_attempts`
```sql
id, username VARCHAR(100)
ip_address VARCHAR(45)
user_agent TEXT
success BOOLEAN
created_at TIMESTAMP
INDEX: [username, ip_address], created_at
```

### `settings`
```sql
id, key VARCHAR(100) UNIQUE
value TEXT (encrypted jika is_secret = true)
is_secret BOOLEAN DEFAULT false
timestamps
```

**Keys yang disimpan di `settings`:**

| Key | Tool | Secret |
|---|---|:---:|
| `getcontact_token` | GetContact | ✓ |
| `getcontact_final_key` | GetContact | ✓ |
| `getcontact_client_device_id` | GetContact | ✓ |
| `instagram_session_id` | Toutatis | ✓ |
| `ipinfo_token` | Phone OSINT | ✓ |
| `leakosint_api_token` | LeakOSINT | ✓ |
| `leakosint_api_url` | LeakOSINT | ✗ |

---

## 4. Navigasi & Routing

### Sidebar Structure
```
├─ Dashboard
├─ 📱 Telepon
│   ├─ Phone Lookup (GetContact)
│   └─ Phone OSINT (IPInfo)
├─ 👤 Identitas
│   ├─ Email OSINT
│   ├─ Username Check
│   └─ Instagram (Toutatis)
├─ 🌐 Jaringan
│   ├─ IP Geolocation
│   └─ WHOIS Domain
├─ 🔍 Data Breach
│   └─ LeakOSINT
└─ ⚙️ Admin
    ├─ Users
    ├─ Audit Logs
    └─ Settings
```

### Routes
```
// Public
GET  /login
POST /login
POST /logout

// Authenticated — operator + admin
GET  /dashboard
GET  /phone-lookup          POST /phone-lookup
GET  /phone-info            POST /phone-info
GET  /email-osint           POST /email-osint
GET  /multicheck            POST /multicheck
GET  /toutatis              POST /toutatis
GET  /ip-geo                POST /ip-geo
GET  /whois                 POST /whois
GET  /leakosint             POST /leakosint

// History — operator + admin
GET  /history/phone
GET  /history/leakosint

// Admin only
GET    /admin/users
POST   /admin/users
GET    /admin/users/{id}/edit
PUT    /admin/users/{id}
DELETE /admin/users/{id}
GET    /admin/logs
GET    /settings
POST   /settings
```

---

## 5. Controllers

| Controller | Tanggung Jawab |
|---|---|
| `AuthController` | Login, logout, brute-force throttle |
| `DashboardController` | Stats harian, recent activity, quick links |
| `AdminController` | CRUD users, audit log viewer |
| `SettingsController` | Tampil + simpan semua settings/credential (admin only) |
| `PhoneLookupController` | GetContact search + history log |
| `LeakOsintController` | LeakOSINT search + history log |
| `MulticheckController` | Username multicheck (15 platform) |
| `EmailOsintController` | Email OSINT (Disify + Gravatar) |
| `PhoneInfoController` | Phone info (libphonenumber + ipinfo.io) |
| `IpGeoController` | IP geolocation (ip-api.com) |
| `WhoisController` | WHOIS + DNS + SSL cert |
| `ToutatisController` | Instagram deep OSINT |

---

## 6. Services

| Service | Sumber | Implementasi |
|---|---|---|
| `GetContactService` | Getcontact-App | HMAC-SHA256 signing, AES-256-ECB decrypt, search |
| `LeakOsintService` | Osint | Guzzle POST ke leakosintapi.com, flatten nested JSON |
| `MulticheckService` | OsintPlus → PHP | Guzzle\Pool parallel checks, 15 platform, UA spoofing |
| `EmailOsintService` | OsintPlus → PHP | Disify API + MD5 Gravatar lookup |
| `PhoneInfoService` | OsintPlus → PHP | libphonenumber-for-php + ipinfo.io Bearer token |
| `IpGeolocationService` | OsintPlus → PHP | ip-api.com GET, 27 field response |
| `WhoisService` | OsintPlus → PHP | PHP WHOIS library + socket DNS + stream_context SSL cert |
| `ToutatisService` | OsintPlus → PHP | Instagram Mobile API Guzzle, LRU cache (500 entries, 5 min TTL), exponential backoff |

---

## 7. UI/UX Design System

### Warna
```css
--c-blue-900: #0C1B33
--c-blue-800: #0F2857
--c-blue-700: #1B3F8A
--c-blue-600: #1D53CC
--c-blue-500: #2563EB   /* primary */
--c-blue-400: #3B82F6   /* hover */
--c-blue-50:  #EFF6FF   /* light bg */
--c-cyan:     #0EA5E9   /* accent */
--c-surface:  #FFFFFF
--c-bg:       #F0F5FF
--c-text:     #0F172A
--c-text-2:   #334155
--c-text-3:   #64748B
--c-border:   #E2E8F0
--c-success:  #10B981
--c-danger:   #EF4444
--c-warning:  #F59E0B
```

### Typography
- **Heading/Brand:** Sora
- **Body:** Plus Jakarta Sans
- **Data/Output:** JetBrains Mono

### Layout
```
┌──────────────────────────────────────────────────┐
│  NAVBAR — glassmorphism blur, logo ODIN, user    │
├──────────────┬───────────────────────────────────┤
│  SIDEBAR     │  CONTENT AREA                    │
│  (240px)     │                                  │
│  kategori    │  Page header (gradient biru)     │
│  + items     │  Tool input card                 │
│              │  Result panel (conditional)      │
└──────────────┴───────────────────────────────────┘
```

### Pola UI Tool (Konsisten di semua tool)
```
[Icon] Nama Tool                         [Role badge]
Deskripsi singkat

┌─── Input Card ───────────────────────────────────┐
│  Label + field input                            │
│  [Tombol Submit — biru solid, full width]       │
└─────────────────────────────────────────────────┘

┌─── Result Card (muncul setelah submit) ──────────┐
│  Status badge (success/error)                   │
│  Data dalam format sesuai tool                  │
└─────────────────────────────────────────────────┘
```

### Format Result per Tool
| Tool | Format |
|---|---|
| Phone Lookup (GC) | Profile card + tags grid + spam badge |
| LeakOSINT | Table per database source + export Excel/PDF |
| Multicheck | Grid card 15 platform (found=hijau, not=merah) |
| Email OSINT | Info card + MX records list + investigation links |
| Phone OSINT | Detail card (carrier, timezone, region) |
| IP Geolocation | Google Maps embed + detail card (ISP, ASN, proxy flag) |
| WHOIS | Accordion (registrar, DNS records, SSL cert) |
| Toutatis | Instagram profile card + obfuscated contact |

### Dashboard
- Stats card: total search hari ini, tool terpopuler, user aktif
- Recent activity feed (search_logs + login_attempts)
- Quick-access shortcut ke semua 8 tool

---

## 8. Dependencies (Tambahan)

**Composer (PHP):**
```json
"giggsey/libphonenumber-for-php": "^8.13",
"io-developer/php-whois": "^4.0"
```

**NPM (client-side, untuk LeakOSINT export):**
```json
"xlsx": "^0.18.5",
"jspdf": "^2.5.1",
"jspdf-autotable": "^3.8.2"
```

> LeakOSINT export Excel/PDF dilakukan client-side (SheetJS + jsPDF) mengikuti implementasi project asal, bukan server-side.

---

## 9. Struktur Folder Project

```
Project ODIN/
├── app/
│   ├── Http/
│   │   ├── Controllers/     (12 controllers)
│   │   └── Middleware/      (Authenticate, CheckRole, SecurityHeaders)
│   ├── Models/              (User, SearchLog, LoginAttempt, Setting)
│   └── Services/            (8 services)
├── database/
│   ├── migrations/          (4 tabel)
│   └── seeders/             (AdminSeeder — default admin user)
├── resources/
│   ├── views/
│   │   ├── layouts/         (app.blade.php — sidebar layout)
│   │   ├── auth/            (login.blade.php)
│   │   ├── dashboard/
│   │   ├── tools/           (8 halaman tool)
│   │   ├── history/         (phone, leakosint)
│   │   ├── admin/           (users, logs)
│   │   └── settings/
│   └── css/ js/
├── routes/web.php
├── public/icon.png           (logo ODIN)
└── docs/superpowers/specs/
```

---

## 10. Catatan Implementasi

- **OsintPlus tools** diport ke PHP murni — semua adalah HTTP calls ke public API via Guzzle
- **ToutatisService** menggunakan LRU cache (Laravel Cache driver) dengan TTL 5 menit untuk menghindari rate limit Instagram
- **Settings** yang bertanda `is_secret=true` dienkripsi menggunakan Laravel `encrypt()`/`decrypt()`
- **Multicheck** menggunakan `GuzzleHttp\Pool` untuk concurrent requests ke 15 platform
- **History/logging** hanya untuk `getcontact` dan `leakosint` sesuai keputusan design
- **Viewer role** bisa login dan melihat history miliknya, tidak bisa menggunakan tools
- **Docker** akan ditambahkan sebagai tahap terpisah setelah project selesai
