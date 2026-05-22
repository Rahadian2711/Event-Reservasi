# EventRes — Frontend Redesign
**Premium Blue SaaS UI · PHP Native · Pure CSS + JS**

---

## Struktur File

```
event-reservation/
│
├── index.php                   ← Homepage (Hero + Search + Event Grid)
├── login.php                   ← Halaman Login (Split Layout)
├── register.php                ← Halaman Register (Split Layout)
├── detail_event.php            ← Detail Event + Form Reservasi
├── my_reservations.php         ← Daftar Reservasi User
├── logout.php                  ← Session destroy + redirect
│
├── admin/
│   ├── dashboard.php           ← Dashboard Admin (Stats + Table + Sidebar)
│   └── events.php              ← CRUD Events (Table + Modal Form)
│
├── templates/
│   ├── head.php                ← <head> reusable (CSS includes)
│   ├── navbar.php              ← Navbar floating role-based
│   └── footer.php              ← Footer + JS includes
│
└── assets/
    ├── css/
    │   ├── design-system.css   ← Tokens, reset, komponen global
    │   ├── navbar.css          ← Navbar + mobile drawer
    │   ├── home.css            ← Hero, search, event cards, pagination
    │   ├── admin.css           ← Sidebar, stat cards, table admin
    │   ├── auth.css            ← Login & register split layout
    │   └── reservations.css    ← My Reservations page
    └── js/
        └── app.js              ← Navbar, drawer, tabs, wishlist, dll
```

---

## Cara Pakai di Halaman Baru

```php
<?php
session_start();
define('BASE_URL', ''); // '' jika di root, '/event-reservation' jika subfolder

$page_title = 'Judul Halaman';
$extra_css  = [BASE_URL . '/assets/css/home.css']; // CSS tambahan (opsional)
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

---

## CSS yang Perlu Di-include Per Halaman

| Halaman             | CSS tambahan          |
|---------------------|----------------------|
| index.php           | `home.css`           |
| detail_event.php    | `home.css`           |
| login.php           | `auth.css`           |
| register.php        | `auth.css`           |
| my_reservations.php | `reservations.css`   |
| admin/dashboard.php | `admin.css`          |
| admin/events.php    | `admin.css`          |

`design-system.css` dan `navbar.css` selalu diload via `head.php`.

---

## Session Variables yang Digunakan Navbar

```php
$_SESSION['user_id']    // int — ID user login
$_SESSION['user_name']  // string — "Budi Santoso"
$_SESSION['user_role']  // 'admin' | 'user'
```

---

## Komponen CSS Siap Pakai

### Buttons
```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-outline">Outline</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-lg">Large</button>
```

### Badges
```html
<span class="badge badge-blue">Technology</span>
<span class="badge badge-green">Aktif</span>
<span class="badge badge-yellow">Pending</span>
<span class="badge badge-red">Batal</span>
```

### Cards
```html
<div class="card">
  <div class="card-body">Konten</div>
</div>
```

### Alerts
```html
<div class="alert alert-success">Berhasil!</div>
<div class="alert alert-danger">Error!</div>
<div class="alert alert-info">Info</div>
```

### Grid
```html
<div class="grid grid-3">       <!-- 3 kolom fixed -->
<div class="grid grid-auto">    <!-- auto-fill 300px -->
<div class="grid grid-auto-sm"> <!-- auto-fill 260px -->
```

### Form
```html
<div class="form-group">
  <label class="form-label">Email</label>
  <div class="input-wrap">
    <span class="input-icon"><!-- SVG --></span>
    <input class="form-input" type="email" placeholder="...">
  </div>
  <div class="form-hint">Hint text</div>
</div>
```

---

## Palette Warna

| Variable             | Nilai     | Kegunaan              |
|----------------------|-----------|-----------------------|
| `--color-primary`    | `#2563EB` | CTA, link aktif       |
| `--color-bg`         | `#EFF6FF` | Background halaman    |
| `--blue-100`         | `#DBEAFE` | Surface alt, border   |
| `--blue-200`         | `#BFDBFE` | Border hover          |
| `--blue-400`         | `#60A5FA` | Icon, accent          |
| `--grad-brand`       | Blue→Cyan | Button, header        |
| `--grad-sidebar`     | Deep Blue | Admin sidebar         |

---

## BASE_URL

- Root domain (`http://localhost/`)  → `define('BASE_URL', '');`
- Subfolder (`http://localhost/event-reservation/`) → `define('BASE_URL', '/event-reservation');`

Sebaiknya definisikan di file `config.php` dan include di semua halaman:

```php
// config.php
define('BASE_URL', '/event-reservation');
define('DB_HOST', 'localhost');
define('DB_NAME', 'event_reservation');
// ...

// Di setiap halaman:
require_once __DIR__ . '/config.php';
```
