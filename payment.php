<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
define('BASE_URL', '/event-reservation');
require_once 'config/koneksi.php';

/* ── Guard: login required ── */
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_reservation = (int)($_GET['id'] ?? 0);

if (!$id_reservation) {
    header('Location: ' . BASE_URL . '/my_reservations.php');
    exit;
}

/* ── Fetch reservation + event + ticket category ── */
$query = mysqli_query($conn, "
    SELECT
    r.id_reservation,
    r.id_event,
    r.id_user,
    r.quantity,
    r.total_harga,
    r.status,
    r.tanggal_booking,

    e.nama_event,
    e.lokasi,
    e.tanggal,
    e.gambar,
    e.organizer,
    e.kategori,

    tc.nama_kategori AS kategori_tiket,
    tc.harga AS harga_kategori

FROM reservations r

JOIN events e
ON r.id_event = e.id_event

LEFT JOIN ticket_categories tc
ON r.id_category = tc.id_category

WHERE r.id_reservation = '$id_reservation'
AND r.id_user = '$user_id'

LIMIT 1
");

$res = mysqli_fetch_assoc($query);

if (!$res) {
    header('Location: ' . BASE_URL . '/my_reservations.php');
    exit;
}

/* ── Sudah bayar? redirect ── */
if ($res['status'] === 'confirmed') {
    header('Location: ' . BASE_URL . '/my_reservations.php?paid=1');
    exit;
}

/* ── Handle POST ── */
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = trim($_POST['metode_pembayaran'] ?? '');
    $file   = $_FILES['bukti_pembayaran'] ?? null;

    if (!$metode) {
        $error = 'Pilih metode pembayaran terlebih dahulu.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload bukti pembayaran wajib dilampirkan.';
    } else {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $error = 'Format file harus JPG atau PNG.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 5MB.';
        } else {
            /* ── Simpan file ── */
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'PAY-' . $id_reservation . '-' . time() . '.' . strtolower($ext);
            $dir      = __DIR__ . '/uploads/payments/';

            if (!is_dir($dir)) mkdir($dir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $metode_safe   = mysqli_real_escape_string($conn, $metode);
                $filename_safe = mysqli_real_escape_string($conn, $filename);
                $now           = date('Y-m-d H:i:s');

                /* ── Insert payments ── */
                mysqli_query($conn, "
    INSERT INTO payments (
        id_reservation,
        metode,
        bukti_bayar,
        status
    )
    VALUES (
        '$id_reservation',
        '$metode_safe',
        '$filename_safe',
        'pending'
    )
");

                /* ── Update reservation status ── */
                mysqli_query($conn, "
    UPDATE reservations
    SET status = 'pending'
    WHERE id_reservation = '$id_reservation'
");

                $success = true;
            } else {
                $error = 'Gagal mengupload file. Silakan coba lagi.';
            }
        }
    }
}

/* ── Helpers ── */
function fmt_price(int $p): string {
    return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}
function fmt_date(string $d): string {
    return date('d F Y', strtotime($d));
}
function fmt_time(string $d): string {
    return date('H:i', strtotime($d)) . ' WIB';
}

/* ── Countdown: 24 jam dari created_at ── */
$deadline_ts   = strtotime($res['tanggal_booking']) + (2*60*60);
$deadline_disp = date('H:i', $deadline_ts);

/* ── AUTO CANCEL jika waktu habis ── */
if (
    time() > $deadline_ts &&
    $res['status'] !== 'confirmed' &&
    $res['status'] !== 'cancelled'
) {

    // ubah status reservation
    mysqli_query($conn, "
        UPDATE reservations
        SET status = 'cancelled'
        WHERE id_reservation = '$id_reservation'
    ");

    // kembalikan stok tiket
    mysqli_query($conn, "
        UPDATE ticket_categories tc
        JOIN reservations r
        ON tc.id_category = r.id_category
        SET tc.stok = tc.stok + r.quantity
        WHERE r.id_reservation = '$id_reservation'
    ");

    $res['status'] = 'cancelled';
}

$page_title  = 'Pembayaran';
$extra_css   = [];
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<style>
/* ============================================================
   PAYMENT PAGE — Inline styles konsisten dengan design-system
   ============================================================ */

/* ── Page header ── */
.pay-header {
  background: linear-gradient(135deg, #fff 0%, var(--blue-50) 100%);
  border-bottom: 1px solid var(--blue-100);
  padding-block: var(--sp-8) var(--sp-6);
}
.pay-header__inner {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: var(--sp-4); flex-wrap: wrap;
}
.pay-header__title {
  font-family: var(--font-display); font-size: var(--text-3xl);
  font-weight: 800; letter-spacing: -0.02em;
}
.pay-header__sub { color: var(--color-text-muted); font-size: var(--text-sm); margin-top: var(--sp-1); }

/* ── Countdown pill ── */
.countdown-pill {
  display: inline-flex; align-items: center; gap: var(--sp-3);
  background: #FFFBEB; border: 1.5px solid #FDE68A;
  border-radius: var(--r-xl); padding: var(--sp-3) var(--sp-5);
}
.countdown-label { font-size: var(--text-xs); font-weight: 700; color: #92400E; text-transform: uppercase; letter-spacing: 0.05em; }
.countdown-timer {
  font-family: var(--font-display); font-size: var(--text-xl);
  font-weight: 800; color: #B45309; letter-spacing: 0.04em;
  font-variant-numeric: tabular-nums;
}
.countdown-pill.expired { background: #FEF2F2; border-color: #FECACA; }
.countdown-pill.expired .countdown-label,
.countdown-pill.expired .countdown-timer { color: var(--color-danger); }

/* ── Main grid ── */
.pay-grid {
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: var(--sp-8);
  align-items: start;
  padding-block: var(--sp-10);
}

/* ── Event summary card ── */
.event-summary {
  background: var(--color-surface);
  border-radius: var(--r-xl);
  border: 1px solid var(--color-border);
  overflow: hidden;
}
.event-summary__poster {
  width: 100%; height: 220px; overflow: hidden;
  background: linear-gradient(135deg, var(--blue-100), var(--blue-200));
  position: relative;
}
.event-summary__poster img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform 0.5s ease;
}
.event-summary:hover .event-summary__poster img { transform: scale(1.03); }
.event-summary__poster-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(15,23,42,0.5) 0%, transparent 55%);
}
.event-summary__poster-badge {
  position: absolute; bottom: var(--sp-4); left: var(--sp-4);
  display: flex; gap: var(--sp-2);
}
.event-summary__body { padding: var(--sp-5) var(--sp-6); }
.event-summary__name {
  font-family: var(--font-display); font-size: var(--text-xl);
  font-weight: 800; letter-spacing: -0.02em; margin-bottom: var(--sp-4);
  color: var(--color-text);
}
.event-summary__meta { display: flex; flex-direction: column; gap: var(--sp-3); }
.event-meta-row {
  display: flex; align-items: center; gap: var(--sp-3);
  font-size: var(--text-sm); color: var(--color-text-muted);
}
.event-meta-row svg { width: 15px; height: 15px; color: var(--blue-400); flex-shrink: 0; }
.event-meta-row strong { color: var(--color-text); font-weight: 600; }

/* ── Order breakdown ── */
.order-card {
  background: var(--color-surface);
  border-radius: var(--r-xl);
  border: 1px solid var(--color-border);
  overflow: hidden;
  margin-top: var(--sp-5);
}
.order-card__title {
  font-family: var(--font-display); font-weight: 700;
  font-size: var(--text-md); padding: var(--sp-4) var(--sp-5);
  border-bottom: 1px solid var(--color-border);
  display: flex; align-items: center; gap: var(--sp-2);
}
.order-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--sp-3) var(--sp-5); font-size: var(--text-sm);
  border-bottom: 1px solid var(--color-border);
}
.order-row:last-child { border-bottom: none; }
.order-row__label { color: var(--color-text-muted); display: flex; align-items: center; gap: var(--sp-2); }
.order-row__val   { font-weight: 600; color: var(--color-text); }
.order-total {
  background: linear-gradient(135deg, var(--blue-50), #EFF6FF);
  padding: var(--sp-4) var(--sp-5);
  display: flex; align-items: center; justify-content: space-between;
  border-top: 2px solid var(--blue-200);
}
.order-total__label { font-size: var(--text-sm); font-weight: 700; color: var(--color-text-muted); }
.order-total__val {
  font-family: var(--font-display); font-size: var(--text-2xl);
  font-weight: 800; color: var(--color-primary);
  letter-spacing: -0.02em;
}

/* ── Info penting ── */
.info-box {
  background: var(--blue-50); border: 1.5px solid var(--blue-200);
  border-radius: var(--r-lg); padding: var(--sp-4) var(--sp-5);
  margin-top: var(--sp-5);
}
.info-box__title {
  font-size: var(--text-sm); font-weight: 700; color: var(--blue-700);
  display: flex; align-items: center; gap: var(--sp-2); margin-bottom: var(--sp-3);
}
.info-box__list { display: flex; flex-direction: column; gap: var(--sp-2); }
.info-box__item {
  display: flex; align-items: flex-start; gap: var(--sp-2);
  font-size: var(--text-xs); color: var(--blue-700); line-height: 1.5;
}
.info-box__item::before {
  content: '•'; font-weight: 700; color: var(--blue-400); flex-shrink: 0; margin-top: 1px;
}

/* ── Payment card (right) ── */
.pay-card {
  background: var(--color-surface);
  border-radius: var(--r-xl);
  border: 1px solid var(--blue-200);
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  position: sticky;
  top: calc(var(--navbar-h) + 1rem);
}
.pay-card__header {
  background: var(--grad-brand);
  padding: var(--sp-5) var(--sp-6); color: #fff;
  display: flex; align-items: center; gap: var(--sp-3);
}
.pay-card__header-icon {
  width: 42px; height: 42px; border-radius: var(--r-md);
  background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.pay-card__header-title { font-family: var(--font-display); font-size: var(--text-lg); font-weight: 800; }
.pay-card__header-sub   { font-size: var(--text-xs); opacity: 0.8; margin-top: 2px; }
.pay-card__body { padding: var(--sp-6); display: flex; flex-direction: column; gap: var(--sp-6); }

/* ── Payment method section ── */
.pm-section-title {
  font-size: var(--text-xs); font-weight: 700; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--sp-3);
}
.pm-group { display: flex; flex-direction: column; gap: var(--sp-2); }
.pm-group-label {
  font-size: var(--text-xs); font-weight: 600; color: var(--color-text-light);
  text-transform: uppercase; letter-spacing: 0.06em; padding: var(--sp-1) 0;
}
.pm-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--sp-2); }

/* Payment method card */
.pm-card {
  position: relative; cursor: pointer;
}
.pm-card input[type="radio"] {
  position: absolute; opacity: 0; width: 0; height: 0;
}
.pm-card__inner {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: var(--sp-2); padding: var(--sp-3) var(--sp-2);
  border: 1.5px solid var(--color-border-md);
  border-radius: var(--r-lg); background: var(--color-surface);
  transition: all var(--dur-med) var(--ease-out);
  text-align: center;
}
.pm-card__inner:hover {
  border-color: var(--blue-300); background: var(--blue-50);
  transform: translateY(-2px); box-shadow: var(--shadow-sm);
}
.pm-card input:checked + .pm-card__inner {
  border-color: var(--color-primary);
  background: var(--blue-50);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.12), var(--shadow-sm);
}
.pm-card input:checked + .pm-card__inner .pm-card__name {
  color: var(--color-primary);
}
.pm-card__logo {
  width: 36px; height: 36px; border-radius: var(--r-sm);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; line-height: 1;
}
.pm-card__name { font-size: var(--text-xs); font-weight: 700; color: var(--color-text); }

/* Selected payment info */
.pm-info {
  display: none;
  background: var(--blue-50); border: 1.5px solid var(--blue-200);
  border-radius: var(--r-lg); padding: var(--sp-4) var(--sp-5);
  animation: fadeUp 0.25s var(--ease-out) both;
}
.pm-info.visible { display: block; }
.pm-info__bank  { font-size: var(--text-xs); font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: var(--sp-1); }
.pm-info__norek {
  font-family: var(--font-display); font-size: var(--text-xl);
  font-weight: 800; color: var(--color-primary); letter-spacing: 0.06em;
  display: flex; align-items: center; gap: var(--sp-3);
}
.pm-info__copy {
  background: none; border: none; cursor: pointer;
  color: var(--color-text-light); padding: 3px; border-radius: var(--r-sm);
  display: flex; transition: all var(--dur-fast);
}
.pm-info__copy:hover { color: var(--color-primary); background: var(--blue-100); }
.pm-info__name { font-size: var(--text-sm); color: var(--color-text-muted); margin-top: var(--sp-1); }
.pm-info__name strong { color: var(--color-text); }
.pm-info__amount {
  margin-top: var(--sp-3); padding-top: var(--sp-3);
  border-top: 1px solid var(--blue-200);
  display: flex; align-items: center; justify-content: space-between;
}
.pm-info__amount-label { font-size: var(--text-xs); color: var(--color-text-muted); font-weight: 600; }
.pm-info__amount-val { font-family: var(--font-display); font-size: var(--text-lg); font-weight: 800; color: var(--color-primary); }

/* ── Upload area ── */
.upload-area {
  border: 2px dashed var(--blue-300); border-radius: var(--r-lg);
  background: var(--blue-50); padding: var(--sp-6);
  text-align: center; cursor: pointer; position: relative;
  transition: all var(--dur-med) var(--ease);
}
.upload-area:hover, .upload-area.drag-over {
  border-color: var(--color-primary); background: var(--blue-100);
}
.upload-area input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-icon {
  width: 52px; height: 52px; border-radius: var(--r-xl);
  background: rgba(37,99,235,0.10); border: 1.5px solid var(--blue-200);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto var(--sp-3); transition: all var(--dur-med);
}
.upload-area:hover .upload-icon { background: rgba(37,99,235,0.18); transform: translateY(-2px); }
.upload-title  { font-weight: 700; font-size: var(--text-sm); color: var(--color-text); }
.upload-sub    { font-size: var(--text-xs); color: var(--color-text-light); margin-top: 4px; }

/* Preview */
.upload-preview {
  display: none; position: relative;
  border-radius: var(--r-lg); overflow: hidden;
  border: 2px solid var(--blue-200);
}
.upload-preview.visible { display: block; }
.upload-preview img { width: 100%; max-height: 200px; object-fit: cover; display: block; }
.upload-preview__remove {
  position: absolute; top: var(--sp-2); right: var(--sp-2);
  width: 30px; height: 30px; border-radius: var(--r-full);
  background: rgba(239,68,68,0.9); color: #fff; border: none;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; transition: all var(--dur-fast);
}
.upload-preview__remove:hover { background: #DC2626; transform: scale(1.1); }
.upload-preview__label {
  background: var(--blue-50); padding: var(--sp-2) var(--sp-3);
  font-size: var(--text-xs); color: var(--color-text-muted);
  display: flex; align-items: center; gap: var(--sp-2);
}

/* ── Submit button ── */
.pay-submit {
  width: 100%; padding: 16px var(--sp-6);
  background: var(--grad-brand); color: #fff;
  border: none; border-radius: var(--r-full);
  font-family: var(--font-display); font-size: var(--text-md);
  font-weight: 800; letter-spacing: 0.01em;
  cursor: pointer; position: relative; overflow: hidden;
  box-shadow: 0 6px 24px rgba(37,99,235,0.40);
  transition: all var(--dur-med) var(--ease-out);
  display: flex; align-items: center; justify-content: center; gap: var(--sp-3);
}
.pay-submit:hover  { box-shadow: 0 8px 32px rgba(37,99,235,0.55); transform: translateY(-2px); }
.pay-submit:active { transform: scale(0.98); }
.pay-submit::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.12) 50%, transparent 100%);
  transform: translateX(-100%); transition: transform 0.6s ease;
}
.pay-submit:hover::after { transform: translateX(100%); }
.pay-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Success overlay ── */
.success-overlay {
  display: none; position: fixed; inset: 0; z-index: 9999;
  background: rgba(14,30,60,0.55); backdrop-filter: blur(6px);
  align-items: center; justify-content: center; padding: var(--sp-4);
}
.success-overlay.open { display: flex; }
.success-box {
  background: #fff; border-radius: var(--r-2xl);
  max-width: 420px; width: 100%; text-align: center;
  padding: var(--sp-10) var(--sp-8);
  box-shadow: var(--shadow-xl);
  animation: fadeUp 0.4s var(--ease-out) both;
}
.success-icon {
  width: 80px; height: 80px; border-radius: var(--r-full);
  background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto var(--sp-6);
  box-shadow: 0 8px 30px rgba(16,185,129,0.25);
}
.success-title {
  font-family: var(--font-display); font-size: var(--text-2xl);
  font-weight: 800; letter-spacing: -0.02em; margin-bottom: var(--sp-3);
}
.success-text { color: var(--color-text-muted); font-size: var(--text-sm); line-height: 1.7; margin-bottom: var(--sp-6); }

/* ── Responsive ── */
@media (max-width: 900px) {
  .pay-grid { grid-template-columns: 1fr; }
  .pay-card { position: static; }
}
@media (max-width: 480px) {
  .pm-grid { grid-template-columns: 1fr 1fr; }
  .pay-header__inner { flex-direction: column; }
}
</style>

<div class="page-wrap">
  <?php require_once __DIR__ . '/templates/navbar.php'; ?>

  <main class="page-main">

    <?php if ($success): ?>
    <!-- ── Success overlay ── -->
    <div class="success-overlay open" id="successOverlay">
      <div class="success-box">
        <div class="success-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <h2 class="success-title">Pembayaran Terkirim!</h2>
        <p class="success-text">
          Bukti pembayaranmu berhasil diunggah. Tim kami akan memverifikasi dalam 1×24 jam.
          Kamu akan mendapat notifikasi konfirmasi segera setelah diverifikasi.
        </p>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
          <a href="<?= BASE_URL ?>/my_reservations.php" class="btn btn-primary w-full btn-lg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Lihat Reservasiku
          </a>
          <a href="<?= BASE_URL ?>/index.php" class="btn btn-ghost w-full">Kembali ke Beranda</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Cancelled overlay ── -->
     <?php if ($res['status'] === 'cancelled'): ?>

<div class="success-overlay open" id="cancelOverlay">
  <div class="success-box">

    <div class="success-icon"
         style="
            background:linear-gradient(135deg,#FEE2E2,#FECACA);
            box-shadow:0 8px 30px rgba(239,68,68,.25);
         ">

      <svg width="36"
           height="36"
           viewBox="0 0 24 24"
           fill="none"
           stroke="#DC2626"
           stroke-width="2.5">

        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>

      </svg>
    </div>

    <h2 class="success-title">Pembayaran Dibatalkan!</h2>

    <p class="success-text">
      Waktu pembayaran telah habis.
      Reservasi kamu otomatis dibatalkan dan tiket dikembalikan ke stok.
    </p>

    <div style="display:flex;flex-direction:column;gap:.75rem;">

      <a href="<?= BASE_URL ?>/my_reservations.php"
         class="btn btn-primary w-full btn-lg">

        Lihat Reservasi

      </a>

      <a href="<?= BASE_URL ?>/index.php"
         class="btn btn-ghost w-full">

        Kembali ke Beranda

      </a>

    </div>

  </div>
</div>

<?php endif; ?>


    <!-- ── Page header ── -->
    <div class="pay-header">
      <div class="container">
        <!-- Breadcrumb -->
        <nav style="display:flex;align-items:center;gap:0.5rem;font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:var(--sp-4);">
          <a href="<?= BASE_URL ?>/index.php" style="color:var(--color-primary);font-weight:600;">Home</a>
          <span>›</span>
          <a href="<?= BASE_URL ?>/my_reservations.php" style="color:var(--color-primary);font-weight:600;">My Reservations</a>
          <span>›</span>
          <span style="color:var(--color-text);font-weight:600;">Pembayaran</span>
        </nav>

        <div class="pay-header__inner">
          <div>
            <h1 class="pay-header__title">Konfirmasi Pembayaran</h1>
            <p class="pay-header__sub">
              Selesaikan pembayaran untuk reservasi
              <strong style="color:var(--color-text)"><?= htmlspecialchars($res['nama_event']) ?></strong>
            </p>
          </div>

          <!-- Countdown -->
          <div class="countdown-pill" id="countdownPill">
            <div>
              <div class="countdown-label">⏰ Batas Waktu</div>
              <div class="countdown-timer" id="countdownTimer">--:--:--</div>
              <div style="font-size:0.7rem;color:#92400E;margin-top:3px;font-weight:600;">
    Berakhir pukul <?= $deadline_disp ?> WIB
</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Error alert ── -->
    <?php if ($error): ?>
    <div class="container" style="padding-top:var(--sp-5);">
      <div class="alert alert-danger">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Main grid ── -->
    <div class="container">
      <div class="pay-grid">

        <!-- ════════════ LEFT: Event detail ════════════ -->
        <div class="fade-up">

          <!-- Event summary card -->
          <div class="event-summary">
            <div class="event-summary__poster">
              <?php if (!empty($res['gambar'])): ?>
                <img src="<?= BASE_URL ?>/uploads/events/<?= htmlspecialchars($res['gambar']) ?>"
                     alt="<?= htmlspecialchars($res['nama_event']) ?>">
              <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--blue-100),var(--blue-200));font-size:4rem;">🎟</div>
              <?php endif; ?>
              <div class="event-summary__poster-overlay"></div>
              <div class="event-summary__poster-badge">
                <span class="badge badge-blue"><?= htmlspecialchars($res['kategori']) ?></span>
                <span class="badge badge-yellow">⏳ Menunggu Pembayaran</span>
              </div>
            </div>

            <div class="event-summary__body">
              <h2 class="event-summary__name"><?= htmlspecialchars($res['nama_event']) ?></h2>
              <div class="event-summary__meta">
                <div class="event-meta-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <span><strong><?= fmt_date($res['tanggal']) ?></strong></span>
                </div>
                <div class="event-meta-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <span><strong><?= fmt_time($res['tanggal']) ?></strong></span>
                </div>
                <div class="event-meta-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <span><?= htmlspecialchars($res['lokasi']) ?></span>
                </div>
                <div class="event-meta-row">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <span>Oleh <strong><?= htmlspecialchars($res['organizer']) ?></strong></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Order breakdown -->
          <div class="order-card">
            <div class="order-card__title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
              Rincian Pesanan
            </div>

            <div class="order-row">
              <span class="order-row__label">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                ID Reservasi
              </span>
              <span class="order-row__val" style="font-family:var(--font-display);color:var(--color-primary);">
                #<?= str_pad($res['id_reservation'], 6, '0', STR_PAD_LEFT) ?>
              </span>
            </div>

            <?php if (!empty($res['kategori_tiket'])): ?>
            <div class="order-row">
              <span class="order-row__label">Kategori Tiket</span>
              <span class="order-row__val">
                <span class="badge badge-blue"><?= htmlspecialchars($res['kategori_tiket']) ?></span>
              </span>
            </div>
            <?php endif; ?>

            <div class="order-row">
              <span class="order-row__label">Jumlah Tiket</span>
              <span class="order-row__val"><?= (int)$res['quantity'] ?> tiket</span>
            </div>

            <div class="order-row">
              <span class="order-row__label">Harga per Tiket</span>
              <span class="order-row__val"><?= fmt_price((int)$res['harga_kategori']) ?></span>
            </div>

            <div class="order-row">
              <span class="order-row__label">Status</span>
              <span class="order-row__val">
                <span class="badge badge-yellow">⏳ Pending</span>
              </span>
            </div>

            <div class="order-total">
              <span class="order-total__label">Total Pembayaran</span>
              <span class="order-total__val"><?= fmt_price((int)$res['total_harga']) ?></span>
            </div>
          </div>

          <!-- Info penting -->
          <div class="info-box">
            <div class="info-box__title">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Informasi Penting
            </div>
            <div class="info-box__list">
              <div class="info-box__item">Transfer sesuai nominal yang tertera — pembayaran berbeda akan memperlambat verifikasi.</div>
              <div class="info-box__item">Upload bukti transfer yang jelas dan terbaca (min. 5MB).</div>
              <div class="info-box__item">Batas pembayaran <strong><?= $deadline_disp ?></strong>. Lewat batas, reservasi otomatis dibatalkan.</div>
              <div class="info-box__item">Tiket digital dikirim via email setelah pembayaran terverifikasi (maks 24 jam).</div>
              <div class="info-box__item">Hubungi support jika pembayaran belum dikonfirmasi lebih dari 24 jam.</div>
            </div>
          </div>

        </div><!-- /LEFT -->

        <!-- ════════════ RIGHT: Payment form ════════════ -->
        <div class="fade-up fade-up-d2">
          <div class="pay-card">
            <div class="pay-card__header">
              <div class="pay-card__header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              </div>
              <div>
                <div class="pay-card__header-title">Metode Pembayaran</div>
                <div class="pay-card__header-sub">Pilih metode &amp; upload bukti transfer</div>
              </div>
            </div>

            <form class="pay-card__body" method="POST" action="" enctype="multipart/form-data" id="payForm">

              <!-- ── Transfer Bank ── -->
              <div>
                <div class="pm-section-title">🏦 Transfer Bank</div>
                <div class="pm-grid">

                  <?php
                  $banks = [
                    'BCA'    => ['logo'=>'🔵', 'norek'=>'1234-5678-9012', 'atas_nama'=>'EventRes Indonesia', 'color'=>'#005BAC'],
                    'Mandiri'=> ['logo'=>'🟡', 'norek'=>'1170-0123-4567-89', 'atas_nama'=>'EventRes Indonesia', 'color'=>'#003D6B'],
                    'BNI'    => ['logo'=>'🟠', 'norek'=>'0987-6543-210', 'atas_nama'=>'EventRes Indonesia', 'color'=>'#F37021'],
                  ];
                  foreach ($banks as $bank => $info):
                  ?>
                  <label class="pm-card">
                    <input type="radio" name="metode_pembayaran" value="<?= $bank ?>"
                           data-norek="<?= $info['norek'] ?>"
                           data-nama="<?= htmlspecialchars($info['atas_nama']) ?>"
                           data-bank="Bank <?= $bank ?>"
                           class="pm-radio">
                    <div class="pm-card__inner">
                      <div class="pm-card__logo"><?= $info['logo'] ?></div>
                      <div class="pm-card__name"><?= $bank ?></div>
                    </div>
                  </label>
                  <?php endforeach; ?>

                </div>
              </div>

              <!-- ── E-Wallet ── -->
              <div>
                <div class="pm-section-title">📱 E-Wallet</div>
                <div class="pm-grid">

                  <?php
                  $wallets = [
                    'DANA' => ['logo'=>'💙', 'norek'=>'0812-3456-7890', 'atas_nama'=>'EventRes Official'],
                    'OVO'  => ['logo'=>'💜', 'norek'=>'0821-9876-5432', 'atas_nama'=>'EventRes Official'],
                    'GoPay'=> ['logo'=>'💚', 'norek'=>'0857-1234-5678', 'atas_nama'=>'EventRes Official'],
                  ];
                  foreach ($wallets as $wallet => $info):
                  ?>
                  <label class="pm-card">
                    <input type="radio" name="metode_pembayaran" value="<?= $wallet ?>"
                           data-norek="<?= $info['norek'] ?>"
                           data-nama="<?= htmlspecialchars($info['atas_nama']) ?>"
                           data-bank="<?= $wallet ?>"
                           class="pm-radio">
                    <div class="pm-card__inner">
                      <div class="pm-card__logo"><?= $info['logo'] ?></div>
                      <div class="pm-card__name"><?= $wallet ?></div>
                    </div>
                  </label>
                  <?php endforeach; ?>

                </div>
              </div>

              <!-- ── Selected payment info (hidden until selected) ── -->
              <div class="pm-info" id="pmInfo">
                <div class="pm-info__bank" id="pmInfoBank">—</div>
                <div class="pm-info__norek">
                  <span id="pmInfoNorek">—</span>
                  <button type="button" class="pm-info__copy" id="copyBtn" title="Salin nomor">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                  </button>
                </div>
                <div class="pm-info__name">Atas nama: <strong id="pmInfoNama">—</strong></div>
                <div class="pm-info__amount">
                  <span class="pm-info__amount-label">Nominal transfer</span>
                  <span class="pm-info__amount-val"><?= fmt_price((int)$res['total_harga']) ?></span>
                </div>
              </div>

              <!-- ── Upload bukti ── -->
              <div>
                <div class="pm-section-title">📎 Bukti Pembayaran</div>

                <!-- Preview (shown after select file) -->
                <div class="upload-preview" id="uploadPreview">
                  <img id="previewImg" src="" alt="Preview">
                  <div class="upload-preview__label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span id="previewFileName">—</span>
                    <button type="button" id="removeFile" style="margin-left:auto;background:none;border:none;color:var(--color-danger);cursor:pointer;font-size:0.75rem;font-weight:700;">✕ Hapus</button>
                  </div>
                </div>

                <!-- Upload area (hidden after file selected) -->
                <div class="upload-area" id="uploadArea">
                  <input type="file" name="bukti_pembayaran" id="fileInput"
                         accept="image/jpg,image/jpeg,image/png" required>
                  <div class="upload-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  </div>
                  <div class="upload-title">Upload Bukti Pembayaran</div>
                  <div class="upload-sub">Klik atau drag &amp; drop · JPG / PNG · Maks 5MB</div>
                </div>
              </div>

              <!-- ── Submit ── -->
              <div>
                <button type="submit" class="pay-submit" id="submitBtn" disabled>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5a2 2 0 0 1 1.84-2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.09a16 16 0 0 0 6 6l.72-.72a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17.59"/></svg>
                  Kirim Pembayaran
                </button>
                <p style="text-align:center;font-size:var(--text-xs);color:var(--color-text-light);margin-top:var(--sp-3);">
                  🔒 Data kamu aman &amp; terenkripsi SSL
                </p>
              </div>

            </form>
          </div>
        </div><!-- /RIGHT -->

      </div><!-- /.pay-grid -->
    </div><!-- /.container -->

  </main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>

<script>
(function () {
  'use strict';

  /* ── Countdown ── */
  const deadline = <?= $deadline_ts ?> * 1000;
  const timerEl   = document.getElementById('countdownTimer');
  const pillEl    = document.getElementById('countdownPill');

  function updateCountdown() {
    const now  = Date.now();
    const diff = deadline - now;
    if (diff <= 0) {

  timerEl.textContent = 'WAKTU HABIS';
  pillEl.classList.add('expired');

  clearInterval(countdownInterval);

  // disable tombol pembayaran
  submitBtn.disabled = true;

  // reload halaman agar status jadi cancelled
  setTimeout(() => {
    location.reload();
  }, 1500);

  return;
}
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    timerEl.textContent =
      String(h).padStart(2,'0') + ':' +
      String(m).padStart(2,'0') + ':' +
      String(s).padStart(2,'0');
  }
  updateCountdown();
  const countdownInterval = setInterval(updateCountdown, 1000);

  /* ── Payment method selection ── */
  const radios     = document.querySelectorAll('.pm-radio');
  const pmInfo     = document.getElementById('pmInfo');
  const pmBank     = document.getElementById('pmInfoBank');
  const pmNorek    = document.getElementById('pmInfoNorek');
  const pmNama     = document.getElementById('pmInfoNama');
  const submitBtn  = document.getElementById('submitBtn');
  let   fileReady  = false;
  let   methodReady = false;

  function checkReady() {
    submitBtn.disabled = !(fileReady && methodReady);
  }

  radios.forEach(radio => {
    radio.addEventListener('change', () => {
      const norek = radio.dataset.norek;
      const nama  = radio.dataset.nama;
      const bank  = radio.dataset.bank;
      pmBank.textContent  = bank;
      pmNorek.textContent = norek;
      pmNama.textContent  = nama;
      pmInfo.classList.add('visible');
      methodReady = true;
      checkReady();
    });
  });

  /* ── Copy norek ── */
  document.getElementById('copyBtn').addEventListener('click', () => {
    const norek = document.getElementById('pmInfoNorek').textContent;
    navigator.clipboard.writeText(norek.replace(/\s/g, '')).then(() => {
      const btn = document.getElementById('copyBtn');
      btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
      setTimeout(() => {
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
      }, 1800);
    });
  });

  /* ── File upload preview ── */
  const fileInput     = document.getElementById('fileInput');
  const uploadArea    = document.getElementById('uploadArea');
  const uploadPreview = document.getElementById('uploadPreview');
  const previewImg    = document.getElementById('previewImg');
  const previewName   = document.getElementById('previewFileName');
  const removeBtn     = document.getElementById('removeFile');

  function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src = e.target.result;
      previewName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
      uploadArea.style.display    = 'none';
      uploadPreview.classList.add('visible');
      fileReady = true;
      checkReady();
    };
    reader.readAsDataURL(file);
  }

  fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) showPreview(fileInput.files[0]);
  });

  removeBtn.addEventListener('click', () => {
    fileInput.value = '';
    previewImg.src  = '';
    uploadArea.style.display = '';
    uploadPreview.classList.remove('visible');
    fileReady = false;
    checkReady();
  });

  /* ── Drag & drop ── */
  uploadArea.addEventListener('dragover',  e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
      showPreview(file);
    }
  });

  /* ── Form loading state ── */
  document.getElementById('payForm').addEventListener('submit', () => {
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           style="animation:spin 1s linear infinite">
        <line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/>
        <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/>
        <line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/>
        <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>
      </svg>
      Mengirim pembayaran...`;
  });

})();
</script>

<style>
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
