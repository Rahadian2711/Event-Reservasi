<?php
session_start();
define('BASE_URL', '/event-reservation');
require_once 'config/koneksi.php';

$page_title = 'Detail Event';
$extra_css  = [BASE_URL . '/assets/css/home.css'];

// Simulasi data event (ganti dengan query DB berdasarkan $_GET['id'])
$id = $_GET['id'] ?? 0;

$query = mysqli_query($conn, "
    SELECT * FROM events
    WHERE id_event = '$id'
");

$event = mysqli_fetch_assoc($query);

if (!$event) {
    die("Event tidak ditemukan");
}

$tags = [];

$qTags = mysqli_query($conn, "
  SELECT tag_name
  FROM event_tags
  WHERE id_event = '$id'
");

while ($row = mysqli_fetch_assoc($qTags)) {
    $tags[] = $row['tag_name'];
}

$schedule = [];

$qSchedule = mysqli_query($conn, "
  SELECT *
  FROM event_schedule
  WHERE id_event = '$id'
");

while ($row = mysqli_fetch_assoc($qSchedule)) {
    $schedule[] = $row;
}

$event['tags'] = $tags;
$event['schedule'] = $schedule;

$queryKategori = mysqli_query($conn, "
    SELECT *
    FROM ticket_categories
    WHERE id_event = '$id'
");

$ticket_categories = [];

while ($row = mysqli_fetch_assoc($queryKategori)) {
    $ticket_categories[] = $row;
}


$is_logged = isset($_SESSION['user_id']);
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged) {

    $seats = (int)($_POST['seats'] ?? 1);
    $id_user = $_SESSION['user_id'];
    $id_event = $event['id_event'];

    // ambil kategori tiket
    $id_category = $_POST['id_category'];

    // ambil data kategori
    $qCategory = mysqli_query($conn, "
        SELECT * FROM ticket_categories
        WHERE id_category = '$id_category'
    ");

    $category = mysqli_fetch_assoc($qCategory);

    if (!$category) {
        $error = "Kategori tiket tidak ditemukan";
    } else {

        $harga = $category['harga'];
        $total_harga = $harga * $seats;

        // simpan reservasi
        $insert = mysqli_query($conn, "
            INSERT INTO reservations
            (
                id_user,
                id_event,
                id_category,
                quantity,
                total_harga,
                status
            )
            VALUES
            (
                '$id_user',
                '$id_event',
                '$id_category',
                '$seats',
                '$total_harga',
                'pending'
            )
        ");

        if ($insert) {

            // kurangi stok tiket
            $new_stock = $category['stok'] - $seats;

            mysqli_query($conn, "
                UPDATE ticket_categories
                SET stok = '$new_stock'
                WHERE id_category = '$id_category'
            ");

            $success = "Reservasi berhasil!";
        } else {
            $error = "Gagal menyimpan reservasi";
        }
    }
}

function fmt_price($p) {
  return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<style>
/* Detail page specific styles */
.detail-hero {
  background: linear-gradient(135deg, var(--white) 0%, var(--blue-50) 100%);
  border-bottom: 1px solid var(--blue-100);
  padding-block: var(--sp-10) var(--sp-8);
}
.detail-grid { display: grid; grid-template-columns: 1fr 380px; gap: var(--sp-8); align-items: start; padding-block: var(--sp-10); }
.detail-img {
  width: 100%; height: 340px; border-radius: var(--r-xl);
  background: linear-gradient(135deg, var(--blue-100), var(--blue-200));
  display: flex; align-items: center; justify-content: center;
  font-size: 5rem; overflow: hidden; margin-bottom: var(--sp-6);
  border: 1px solid var(--blue-100);
}
.detail-section-title {
  font-family: var(--font-display); font-weight: 700; font-size: var(--text-lg);
  color: var(--color-text); margin-bottom: var(--sp-4);
  padding-bottom: var(--sp-3); border-bottom: 2px solid var(--blue-100);
  display: flex; align-items: center; gap: var(--sp-2);
}
.detail-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--sp-4); margin-bottom: var(--sp-6); }
.detail-meta-item { display: flex; flex-direction: column; gap: 3px; }
.detail-meta-label { font-size: var(--text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--color-text-light); }
.detail-meta-value { font-size: var(--text-sm); font-weight: 600; color: var(--color-text); display: flex; align-items: center; gap: var(--sp-2); }
.detail-tags { display: flex; flex-wrap: wrap; gap: var(--sp-2); margin-bottom: var(--sp-6); }

/* Schedule */
.schedule-item { display: flex; gap: var(--sp-4); padding: var(--sp-3) 0; border-bottom: 1px solid var(--color-border); }
.schedule-item:last-child { border-bottom: none; }
.schedule-time { font-size: var(--text-xs); font-weight: 700; color: var(--color-primary); min-width: 52px; padding-top: 1px; }
.schedule-title { font-size: var(--text-sm); font-weight: 500; color: var(--color-text); }

/* Booking card */
.booking-card {
  position: sticky; top: calc(var(--navbar-h) + 1rem);
  background: var(--color-surface); border-radius: var(--r-xl);
  border: 1px solid var(--blue-200); overflow: hidden;
  box-shadow: var(--shadow-lg);
}
.booking-card__header {
  background: var(--grad-brand); padding: var(--sp-5) var(--sp-6); color: #fff;
}
.booking-price-label { font-size: var(--text-xs); font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em; }
.booking-price { font-family: var(--font-display); font-size: var(--text-3xl); font-weight: 800; line-height: 1; }
.booking-price-sub { font-size: var(--text-xs); opacity: 0.75; margin-top: 2px; }
.booking-card__body { padding: var(--sp-6); display: flex; flex-direction: column; gap: var(--sp-4); }
.booking-info-row { display: flex; align-items: center; justify-content: space-between; font-size: var(--text-sm); padding: var(--sp-3) 0; border-bottom: 1px solid var(--color-border); }
.booking-info-row:last-of-type { border-bottom: none; }
.booking-info-label { color: var(--color-text-muted); display: flex; align-items: center; gap: var(--sp-2); }
.booking-info-label svg { width: 14px; height: 14px; color: var(--blue-400); }
.booking-info-val { font-weight: 600; color: var(--color-text); }
.booking-total { background: var(--blue-50); border-radius: var(--r-md); padding: var(--sp-4); display: flex; align-items: center; justify-content: space-between; }
.booking-total-label { font-size: var(--text-sm); font-weight: 600; color: var(--color-text-muted); }
.booking-total-val { font-family: var(--font-display); font-size: var(--text-xl); font-weight: 800; color: var(--color-primary); }

@media (max-width: 900px) {
  .detail-grid { grid-template-columns: 1fr; }
  .booking-card { position: static; }
  .detail-meta-grid { grid-template-columns: 1fr; }
}
</style>

<div class="page-wrap">
  <?php require_once __DIR__ . '/templates/navbar.php'; ?>

  <main class="page-main">

    <!-- Breadcrumb -->
    <div style="background:var(--white);border-bottom:1px solid var(--blue-100);padding:var(--sp-3) 0;">
      <div class="container">
        <nav style="display:flex;align-items:center;gap:0.5rem;font-size:var(--text-xs);color:var(--color-text-muted);">
          <a href="<?= BASE_URL ?>/index.php" style="color:var(--color-primary);font-weight:600;">Home</a>
          <span>›</span>
          <span>Event</span>
          <span>›</span>
          <span style="color:var(--color-text);font-weight:600;"><?= htmlspecialchars($event['nama_event']) ?></span>
        </nav>
      </div>
    </div>

    <div class="container">
      <div class="detail-grid">

        <!-- LEFT: Event info -->
        <div class="fade-up">
          <!-- Event image -->
          <div class="detail-img">
    <img
        src="<?= BASE_URL ?>/uploads/events/<?= htmlspecialchars($event['detail_gambar']) ?>"
        alt="<?= htmlspecialchars($event['nama_event']) ?>"
        style="width:100%;height:100%;object-fit:cover;"
    >
</div>

          <!-- Meta header -->
          <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div>
              <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;flex-wrap:wrap;">
                <span class="badge badge-blue"><?= htmlspecialchars($event['kategori']) ?></span>
                <?php if ($event['slots'] < 20): ?>
                  <span class="badge badge-red">⚡ Hampir habis</span>
                <?php endif; ?>
                <?php if ($ticket_categories[0]['harga'] == 0): ?>
                  <span class="badge badge-green">Gratis</span>
                <?php endif; ?>
              </div>
              <h1 style="font-size:clamp(1.5rem,3vw,2.2rem);letter-spacing:-0.02em;margin-bottom:0.5rem;">
                <?= htmlspecialchars($event['nama_event']) ?>
              </h1>
              <p style="font-size:var(--text-sm);color:var(--color-text-muted);">
                Diselenggarakan oleh <strong style="color:var(--color-primary)"><?= htmlspecialchars($event['organizer']) ?></strong>
              </p>
            </div>
          </div>

          <!-- Meta info grid -->
          <div class="detail-meta-grid card" style="padding:var(--sp-5);margin-bottom:var(--sp-6);">
            <div class="detail-meta-item">
              <span class="detail-meta-label">📅 Tanggal</span>
              <span class="detail-meta-value"><?= date('d F Y', strtotime($event['tanggal'])) ?></span>
            </div>
            <div class="detail-meta-item">
              <span class="detail-meta-label">🕐 Waktu</span>
              <span class="detail-meta-value"><?= htmlspecialchars(date('H:i', strtotime($event['tanggal']))) ?></span>
            </div>
            <div class="detail-meta-item">
              <span class="detail-meta-label">📍 Lokasi</span>
              <span class="detail-meta-value"><?= htmlspecialchars($event['lokasi']) ?></span>
            </div>
            <div class="detail-meta-item">
              <span class="detail-meta-label">🎟 Kursi Tersedia</span>
              <span class="detail-meta-value"><?= $event['slots'] ?> kursi</span>
            </div>
          </div>

          <!-- Tags -->
<div class="detail-tags">
  <?php foreach ($event['tags'] as $tag): ?>
    <span class="chip">
      <?= htmlspecialchars($tag) ?>
    </span>
  <?php endforeach; ?>
</div>

          <!-- Description -->
          <div style="margin-bottom:var(--sp-8);">
            <div class="detail-section-title">
              <span>📋</span> Tentang Event
            </div>
            <p style="line-height:1.85;color:var(--color-text-muted);">
              <?= htmlspecialchars($event['deskripsi']) ?>
            </p>
          </div>

          <!-- Schedule -->
          <div style="margin-bottom:var(--sp-8);">
            <div class="detail-section-title">
              <span>🗓</span> Jadwal Acara
            </div>
            <div class="card card-body">
              <?php foreach ($event['schedule'] as $sch): ?>
                <div class="schedule-item">
                  <span class="schedule-time">
                   <?= htmlspecialchars($sch['jam']) ?>
                  </span>

<span class="schedule-title">
  <?= htmlspecialchars($sch['kegiatan']) ?>
</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div><!-- /left -->

        <!-- RIGHT: Booking card -->
        <div class="fade-up fade-up-d2">
          <div class="booking-card">
            <div class="booking-card__header">
              <div class="booking-price-label">Harga per tiket</div>
              <div class="booking-price" id="ticketPrice">
                <?= fmt_price($ticket_categories[0]['harga']) ?>
              </div>
              <div class="booking-price-sub">
                <span id="ticketStock">
                  <?= $ticket_categories[0]['stok'] ?>
                </span>
                kursi tersisa
              </div>
            </div>

            <div class="booking-card__body">

              <?php if ($success): ?>
                <div class="alert alert-success">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                  <?= htmlspecialchars($success) ?>
                </div>
                <a href="<?= BASE_URL ?>/my_reservations.php" class="btn btn-primary w-full">Lihat Reservasiku</a>
              <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>

              <?php if (!$success): ?>

                <!-- Info rows -->
                <div>
                  <div class="booking-info-row">
                    <span class="booking-info-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                      Tanggal
                    </span>
                    <span class="booking-info-val">
  <?= date('d F Y', strtotime($event['tanggal'])) ?>
</span>
                  </div>
                  <div class="booking-info-row">
                    <span class="booking-info-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                      Waktu
                    </span>
                    <span class="booking-info-val"><?= htmlspecialchars(date('H:i', strtotime($event['tanggal']))) ?></span>
                  </div>
                  <div class="booking-info-row">
                    <span class="booking-info-label">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                      Lokasi
                    </span>
                    <span class="booking-info-val" style="text-align:right;max-width:160px;">
                      <?= htmlspecialchars($event['lokasi']) ?>
                    </span>
                  </div>
                </div>

                <?php if ($is_logged): ?>
                  <form method="POST" action="" style="display:flex;flex-direction:column;gap:1rem;">
                    <div class="form-group">
                      <label class="form-label" for="seats">Jumlah Kursi</label>
                      <select class="form-select" id="seats" name="seats" onchange="updateTotal(this.value)">
                        <?php for ($i = 1; $i <= min(5, $event['slots']); $i++): ?>
                          <option value="<?= $i ?>"><?= $i ?> kursi</option>
                        <?php endfor; ?>
                      </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">
                            Kategori Tiket
                        </label>
                      <select class="form-select"
                            id="category"
                            name="id_category"
                            onchange="updateTotal()">
                        <?php foreach ($ticket_categories as $ticket): ?>
                        <option
                            value="<?= $ticket['id_category'] ?>"
                            data-price="<?= $ticket['harga'] ?>"
                            data-stock="<?= $ticket['stok'] ?>"
                        >
                            <?= $ticket['nama_kategori'] ?>
                            -
                            Rp <?= number_format($ticket['harga'],0,',','.') ?>
                            (<?= $ticket['stok'] ?> kursi)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <div class="booking-total">
                      <span class="booking-total-label">Total Pembayaran</span>
                      <span class="booking-total-val" id="totalPrice">
    <?= fmt_price($ticket_categories[0]['harga']) ?>
</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-full">
                      🎟 Reservasi Sekarang
                    </button>
                    <p style="text-align:center;font-size:var(--text-xs);color:var(--color-text-light);">
                      🔒 Transaksi aman & terenkripsi
                    </p>
                  </form>
                <?php else: ?>
                  <div class="alert alert-info">
                    Silakan <a href="<?= BASE_URL ?>/login.php" style="font-weight:700">login</a> atau
                    <a href="<?= BASE_URL ?>/register.php" style="font-weight:700">daftar</a> untuk memesan tiket.
                  </div>
                  <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-full btn-lg">Login untuk Memesan</a>
                <?php endif; ?>

              <?php endif; ?>

            </div>
          </div>
        </div><!-- /right -->

      </div><!-- /detail-grid -->
    </div><!-- /container -->

  </main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>

<script>

  function updateTotal() {

    const seats =
        document.getElementById('seats').value;

    const category =
        document.getElementById('category');

    const selectedOption =
        category.options[category.selectedIndex];

    const price =
        selectedOption.dataset.price;

    const stock =
        selectedOption.dataset.stock;

    const total = seats * price;

    document.getElementById('totalPrice').innerText =
        'Rp ' + Number(total).toLocaleString('id-ID');

    document.getElementById('ticketPrice').innerText =
        'Rp ' + Number(price).toLocaleString('id-ID');

    document.getElementById('ticketStock').innerText =
        stock;
}

</script>
