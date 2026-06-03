    # EventRes — Event Reservation Platform

**Premium Blue SaaS UI · PHP Native · Pure CSS + JS**

> Platform reservasi event modern berbasis PHP Native dengan desain clean blue-white gradient, glassmorphism ringan, dan UI premium tanpa framework CSS/JS apapun.

---

## Daftar Isi

- [Fitur](#fitur)
- [Tech Stack](#tech-stack)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Setup Database](#setup-database)
- [Konfigurasi](#konfigurasi)
- [Menjalankan Program](#menjalankan-program)
- [Struktur File](#struktur-file)
- [Akun Default](#akun-default)
- [Alur Program](#alur-program)
- [CSS & Komponen](#css--komponen)
- [Referensi Developer](#referensi-developer)
- [Troubleshooting](#troubleshooting)

---

## Fitur

**User**

- Registrasi & login akun
- Browse dan search event dengan filter kategori
- Pagination 6 event per halaman
- Reservasi tiket event dengan pilihan kategori tiket
- Upload bukti pembayaran (Bank Transfer / E-Wallet)
- Lihat riwayat & status reservasi (pending / confirmed / cancelled)
- Batalkan reservasi yang belum dibayar

**Admin**

- Dashboard statistik real-time (total event, reservasi pending, pendapatan, user)
- CRUD event lengkap: nama, gambar, detail gambar, tiket, jadwal, tags
- Verifikasi pembayaran user
- Kelola data reservasi & user
- Sidebar navigasi modern dengan badge notifikasi

---

## Tech Stack

| Layer      | Teknologi                          |
| ---------- | ---------------------------------- |
| Backend    | PHP 8.x Native                     |
| Database   | MySQL / MariaDB                    |
| Frontend   | HTML5, CSS3 Native, JavaScript ES6 |
| Web Server | Apache (Laragon / XAMPP / WAMP)    |
| Font       | Manrope + Poppins (Google Fonts)   |

> Tidak menggunakan framework apapun — tidak Bootstrap, tidak Tailwind, tidak Laravel, tidak jQuery.

---

## Persyaratan Sistem

- **PHP** versi 8.0 atau lebih baru
- **MySQL** 5.7+ atau **MariaDB** 10.4+
- **Apache** web server
- **Laragon** (direkomendasikan) / XAMPP / WAMP
- Browser modern (Chrome, Firefox, Edge)

---

## Instalasi

### 1. Download / Clone Project

```bash
git clone https://github.com/Rahadian2711/Event-Reservasi.git
```

Atau download ZIP lalu extract.

### 2. Letakkan di folder web server

**Laragon:**

```
C:\laragon\www\event-reservation\
```

**XAMPP:**

```
C:\xampp\htdocs\event-reservation\
```

**WAMP:**

```
C:\wamp64\www\event-reservation\
```

### 3. Buat folder uploads

Buat folder berikut secara manual jika belum ada:

```
event-reservation/
└── uploads/
    ├── events/       ← gambar event (thumbnail & detail)
    └── payments/     ← bukti pembayaran user
```

Atau via terminal dari root project:

```bash
mkdir -p uploads/events uploads/payments
```

---

## Setup Database

### 1. Buat database baru

Buka **phpMyAdmin** → klik **New** → beri nama `event_reservation` → klik **Create**.

Atau via MySQL CLI:

```sql
CREATE DATABASE event_reservation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE event_reservation;
```

### 2. Import semua tabel

Jalankan SQL berikut di phpMyAdmin (tab **SQL**):

```sql
-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE users (
    id_user    INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100)  NOT NULL,
    email      VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin','user') DEFAULT 'user'
);

-- ============================================================
-- EVENTS
-- ============================================================
CREATE TABLE events (
    id_event      INT AUTO_INCREMENT PRIMARY KEY,
    nama_event    VARCHAR(200)  NOT NULL,
    tanggal       DATETIME      NOT NULL,
    lokasi        VARCHAR(200),
    organizer     VARCHAR(100),
    deskripsi     TEXT,
    gambar        VARCHAR(255),
    detail_gambar VARCHAR(255),
    kategori      VARCHAR(50),
    status        ENUM('published','draft','archived') DEFAULT 'draft',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TICKET CATEGORIES
-- ============================================================
CREATE TABLE ticket_categories (
    id_category   INT AUTO_INCREMENT PRIMARY KEY,
    id_event      INT NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    harga         INT NOT NULL DEFAULT 0,
    stok          INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_event)
        REFERENCES events(id_event) ON DELETE CASCADE
);

-- ============================================================
-- EVENT TAGS
-- ============================================================
CREATE TABLE event_tags (
    id_tag   INT AUTO_INCREMENT PRIMARY KEY,
    id_event INT NOT NULL,
    tag_name VARCHAR(50),
    FOREIGN KEY (id_event)
        REFERENCES events(id_event) ON DELETE CASCADE
);

-- ============================================================
-- EVENT SCHEDULE
-- ============================================================
CREATE TABLE event_schedule (
    id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
    id_event  INT NOT NULL,
    jam       VARCHAR(10),
    kegiatan  VARCHAR(200),
    FOREIGN KEY (id_event)
        REFERENCES events(id_event) ON DELETE CASCADE
);

-- ============================================================
-- RESERVATIONS
-- ============================================================
CREATE TABLE reservations (
    id_reservation INT AUTO_INCREMENT PRIMARY KEY,
    id_user        INT NOT NULL,
    id_event       INT NOT NULL,
    id_category    INT NOT NULL,
    quantity       INT NOT NULL DEFAULT 1,
    total_harga    INT NOT NULL DEFAULT 0,
    status         ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user)
        REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_event)
        REFERENCES events(id_event) ON DELETE CASCADE,
    FOREIGN KEY (id_category)
        REFERENCES ticket_categories(id_category) ON DELETE CASCADE
);

-- ============================================================
-- PAYMENTS
-- ============================================================
CREATE TABLE payments (
    id_payment         INT AUTO_INCREMENT PRIMARY KEY,
    id_reservation     INT NOT NULL,
    metode  VARCHAR(50),
    bukti_bayar        VARCHAR(255),
    status         ENUM('pending','paid','failed') DEFAULT 'pending',
    FOREIGN KEY (id_reservation)
        REFERENCES reservations(id_reservation) ON DELETE CASCADE
);
```

### 3. Insert data awal (seeder)

```sql
-- Akun admin default (password: admin123)
INSERT INTO users (nama, email, password, role) VALUES (
    'Administrator',
    'admin@eventres.com',
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77bqiW',
    'admin'
);

-- Contoh event pertama
INSERT INTO events (nama_event, tanggal, lokasi, organizer, deskripsi, kategori, status)
VALUES (
    'Tech Summit Jakarta 2025',
    '2025-07-20 09:00:00',
    'Jakarta Convention Center',
    'TechID Community',
    'Konferensi teknologi terbesar di Indonesia.',
    'Technology',
    'published'
);

-- Ticket untuk event di atas (id_event = 1)
INSERT INTO ticket_categories (id_event, nama_kategori, harga, stok)
VALUES
    (1, 'Regular', 150000, 100),
    (1, 'VIP',     350000, 50);
```

> **Catatan:** Hash password di atas adalah untuk `admin123`. Ganti dengan hash baru menggunakan:
>
> ```php
> echo password_hash('passwordmu', PASSWORD_BCRYPT);
> ```

---

## Konfigurasi

### `config/koneksi.php`

```php
<?php
$host = 'localhost';
$user = 'root';   // username MySQL (default Laragon/XAMPP: root)
$pass = '';       // password MySQL (default Laragon/XAMPP: kosong)
$db   = 'event_reservation';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
```

### BASE_URL

Sesuaikan di tiap file PHP utama:

```php
// Project di subfolder (paling umum — Laragon/XAMPP):
define('BASE_URL', '/event-reservation');

// Project di root domain:
define('BASE_URL', '');
```

---

## Menjalankan Program

### Langkah-langkah

1. Buka **Laragon** (atau XAMPP), klik **Start All**
2. Pastikan **Apache** dan **MySQL** berjalan (indikator hijau)
3. Buka browser, akses salah satu URL berikut:

### URL yang tersedia

| URL                                        | Halaman         | Akses          |
| ------------------------------------------ | --------------- | -------------- |
| `/event-reservation/`                      | Homepage        | Publik         |
| `/event-reservation/login.php`             | Login           | Publik         |
| `/event-reservation/register.php`          | Registrasi      | Publik         |
| `/event-reservation/detail_event.php?id=1` | Detail Event    | Publik         |
| `/event-reservation/my_reservations.php`   | Reservasi Saya  | Login required |
| `/event-reservation/payment.php?id=1`      | Pembayaran      | Login required |
| `/event-reservation/admin/dashboard.php`   | Dashboard Admin | Admin only     |
| `/event-reservation/admin/events.php`      | Kelola Event    | Admin only     |

---

## Struktur File

```
event-reservation/
│
├── index.php                    ← Homepage (Hero + Search + Grid + Pagination)
├── login.php                    ← Login (Split Layout)
├── register.php                 ← Registrasi
├── detail_event.php             ← Detail Event + Form Reservasi
├── my_reservations.php          ← Riwayat Reservasi User
├── payment.php                  ← Pembayaran + Upload Bukti
├── logout.php                   ← Hancurkan session + redirect
│
├── admin/
│   ├── dashboard.php            ← Dashboard Admin
│   └── events.php               ← CRUD Events
│
├── process/                     ← Handler POST (tidak diakses langsung)
│   ├── event_store.php          ← Tambah event baru
│   ├── event_update.php         ← Update event
│   └── event_delete.php         ← Hapus event + relasi + file
│
├── config/
│   └── koneksi.php              ← Koneksi database
│
├── templates/
│   ├── head.php                 ← <head> reusable
│   ├── navbar.php               ← Navbar role-based
│   └── footer.php               ← Footer + JS loader
│
├── uploads/
│   ├── events/                  ← Gambar event
│   └── payments/                ← Bukti pembayaran
│
└── assets/
    ├── css/
    │   ├── design-system.css    ← Token, reset, komponen global
    │   ├── navbar.css           ← Navbar + mobile drawer
    │   ├── home.css             ← Hero, search, cards, pagination
    │   ├── admin.css            ← Sidebar, stat cards, tabel
    │   ├── auth.css             ← Login & register
    │   └── reservations.css     ← My Reservations + Payment
    └── js/
        └── app.js               ← Semua interaksi UI
```

---

## Akun Default

| Role  | Email              | Password   |
| ----- | ------------------ | ---------- |
| Admin | admin@eventres.com | `admin123` |

> Ganti password setelah pertama login melalui phpMyAdmin atau buat halaman profile.

---

## Alur Program

### Alur User

```
Buka website
    ↓
Register akun baru / Login
    ↓
Browse event di Homepage
(search, filter kategori, pagination)
    ↓
Klik "Pesan Sekarang" → Detail Event
    ↓
Pilih kategori tiket + jumlah kursi
    ↓
Klik "Reservasi Sekarang"
    ↓
My Reservations → status: PENDING
    ↓
Klik tombol "Bayar"
    ↓
Pilih metode pembayaran
(BCA / Mandiri / BNI / DANA / OVO / GoPay)
    ↓
Upload foto bukti transfer → Klik "Kirim Pembayaran"
    ↓
Status: PENDING (menunggu verifikasi admin)
    ↓
Admin verifikasi → Status: CONFIRMED ✅
```

### Alur Admin

```
Login sebagai admin
    ↓
Dashboard → lihat statistik
(event aktif, reservasi pending, pendapatan, total user)
    ↓
Kelola Events
(tambah / edit tiket+jadwal+gambar / hapus)
    ↓
Kelola Reservasi
(lihat bukti bayar, verifikasi, update status)
```

---

## CSS & Komponen

### CSS per halaman

| Halaman               | `$extra_css`       |
| --------------------- | ------------------ |
| `index.php`           | `home.css`         |
| `detail_event.php`    | `home.css`         |
| `login.php`           | `auth.css`         |
| `register.php`        | `auth.css`         |
| `my_reservations.php` | `reservations.css` |
| `payment.php`         | `reservations.css` |
| `admin/dashboard.php` | `admin.css`        |
| `admin/events.php`    | `admin.css`        |

`design-system.css` + `navbar.css` otomatis diload via `templates/head.php`.

### Template halaman baru

```php
<?php
session_start();
define('BASE_URL', '/event-reservation');
require_once 'config/koneksi.php';

$page_title = 'Judul Halaman';
$extra_css  = [BASE_URL . '/assets/css/home.css'];
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<div class="page-wrap">
  <?php require_once __DIR__ . '/templates/navbar.php'; ?>

  <main class="page-main">
    <div class="container">
      <!-- Konten halaman -->
    </div>
  </main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
```

### Komponen siap pakai

```html
<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-lg">Large</button>

<!-- Badges -->
<span class="badge badge-blue">Technology</span>
<span class="badge badge-green">Confirmed</span>
<span class="badge badge-yellow">Pending</span>
<span class="badge badge-red">Cancelled</span>
<span class="badge badge-gray">Draft</span>

<!-- Card -->
<div class="card">
  <div class="card-body">Konten</div>
</div>

<!-- Alert -->
<div class="alert alert-success">Berhasil!</div>
<div class="alert alert-danger">Gagal!</div>
<div class="alert alert-info">Info</div>
<div class="alert alert-warn">Peringatan</div>

<!-- Grid -->
<div class="grid grid-2">
  <!-- 2 kolom -->
  <div class="grid grid-3">
    <!-- 3 kolom -->
    <div class="grid grid-auto">
      <!-- auto-fill 300px -->
      <div class="grid grid-auto-sm">
        <!-- auto-fill 260px -->

        <!-- Form dengan icon -->
        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-wrap">
            <span class="input-icon"><!-- SVG --></span>
            <input class="form-input" type="email" placeholder="..." />
          </div>
          <div class="form-hint">Teks bantuan</div>
          <div class="form-error">Pesan error</div>
        </div>
      </div>
    </div>
  </div>
</div>
```

---

## Referensi Developer

### Session Variables

```php
$_SESSION['user_id']    // int    — ID user yang sedang login
$_SESSION['user_name']  // string — Nama lengkap user
$_SESSION['user_role']  // string — 'admin' | 'user'
```

### Palette Warna

| Variable          | Nilai     | Kegunaan                 |
| ----------------- | --------- | ------------------------ |
| `--color-primary` | `#2563EB` | Tombol utama, link aktif |
| `--color-bg`      | `#EFF6FF` | Background halaman       |
| `--blue-50`       | `#EFF6FF` | Surface ringan           |
| `--blue-100`      | `#DBEAFE` | Surface alt, border      |
| `--blue-200`      | `#BFDBFE` | Border hover             |
| `--blue-400`      | `#60A5FA` | Icon, accent             |
| `--grad-brand`    | Blue→Cyan | Tombol gradient          |
| `--grad-sidebar`  | Deep Blue | Sidebar admin            |

### Icon Kategori Event

```php
$catIcons = [
    'Music'      => '🎵',
    'Technology' => '💻',
    'Design'     => '🎨',
    'Business'   => '🚀',
    'Art'        => '🖼️',
    'Culinary'   => '🍜',
    'Sport'      => '⚽',
    'Education'  => '📚',
    'Film'       => '🎬',
    'Gaming'     => '🎮',
    'Health'     => '❤️',
    'Fashion'    => '👗',
];
$icon = $catIcons[$kategori] ?? '🎟';
```

---

## Troubleshooting

| Error                            | Penyebab                               | Solusi                                               |
| -------------------------------- | -------------------------------------- | ---------------------------------------------------- |
| `mysqli_connect failed`          | Kredensial DB salah                    | Cek `config/koneksi.php` — host, user, pass, db      |
| Gambar tidak muncul              | Folder `uploads/` belum ada            | Buat `uploads/events/` dan `uploads/payments/`       |
| `Unknown column 'xxx'`           | Nama kolom tidak cocok                 | Jalankan `DESCRIBE nama_tabel` di phpMyAdmin         |
| CSS tidak load                   | `BASE_URL` salah                       | Sesuaikan `define('BASE_URL', '/event-reservation')` |
| Session tidak terbaca            | `session_start()` tidak dipanggil      | Pastikan ada di baris pertama setiap file PHP        |
| Upload gagal diam-diam           | Permission folder                      | Beri permission write pada folder `uploads/`         |
| Password tidak cocok             | Hash tidak pakai `password_verify()`   | Gunakan `password_verify($input, $hash)` di login    |
| Admin tidak bisa akses dashboard | `user_role` tidak tersimpan di session | Pastikan login menyimpan `$_SESSION['user_role']`    |
| Badge Gratis tidak muncul        | Strict compare `=== 0` dengan string   | Gunakan `(int)$harga === 0`                          |

---

## Lisensi

Project ini dibuat untuk keperluan pembelajaran.
Bebas digunakan, dimodifikasi, dan didistribusikan.
