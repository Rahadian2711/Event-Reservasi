<?php
session_start();
define('BASE_URL', '/event-reservation');
require_once 'config/koneksi.php';
// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$page_title = 'My Reservations';
$extra_css  = [BASE_URL . '/assets/css/reservations.css'];

$id_user = $_SESSION['user_id'];
if (isset($_GET['cancel'])) {

    $id_cancel = $_GET['cancel'];

    mysqli_query($conn, "
        UPDATE reservations
        SET status = 'cancelled'
        WHERE id_reservation = '$id_cancel'
        AND id_user = '$id_user'
    ");

   $qReservation = mysqli_query($conn, "
    SELECT *
    FROM reservations
    WHERE id_reservation = '$id_cancel'
");

$reservation = mysqli_fetch_assoc($qReservation);

mysqli_query($conn, "
    UPDATE ticket_categories
    SET stok = stok + {$reservation['quantity']}
    WHERE id_category = {$reservation['id_category']}
");   
    header("Location: my_reservations.php");
    exit;
}
$query = mysqli_query($conn, "
SELECT
    reservations.*,
    events.nama_event,
    events.lokasi,
    events.tanggal,
    events.detail_gambar,
    ticket_categories.nama_kategori

FROM reservations

JOIN events
ON reservations.id_event = events.id_event

JOIN ticket_categories
ON reservations.id_category = ticket_categories.id_category

WHERE reservations.id_user = '$id_user'

ORDER BY reservations.id_reservation DESC

");

$reservations = [];

while($row = mysqli_fetch_assoc($query)) {

    $reservations[] = [

        'id' => $row['id_reservation'],
        'id_event' => $row['id_event'],
        'event' => $row['nama_event'],
        'location' => $row['lokasi'],
        'date' => date('d', strtotime($row['tanggal'])),
        'month' => date('M', strtotime($row['tanggal'])),
        'year' => date('Y', strtotime($row['tanggal'])),
        'time' => date('H:i', strtotime($row['tanggal'])),
        'seats' => $row['quantity'],
        'category' => $row['nama_kategori'],
        'image' => $row['detail_gambar'],
        'price' => $row['total_harga'],
        'status' => $row['status'],
        'emoji' => '🎟'
    ];

}

$status_labels = [
  'confirmed' => [
      'label' => 'Confirmed',
      'badge' => 'badge-green',
      'class' => 'res-card--active'
  ],

  'pending' => [
      'label' => 'Pending',
      'badge' => 'badge-yellow',
      'class' => 'res-card--pending'
  ],

  'cancelled' => [
      'label' => 'Dibatalkan',
      'badge' => 'badge-red',
      'class' => 'res-card--cancel'
  ],
];

$counts = ['all' => count($reservations)];
foreach ($reservations as $r) {
  $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
}

function fmt_price($p) {
  return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}

$user_name = $_SESSION['user_name'] ?? 'User';
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<div class="page-wrap">
  <?php require_once __DIR__ . '/templates/navbar.php'; ?>

  <main class="page-main">

    <!-- Page Header -->
    <div class="res-page-header">
      <div class="container">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
          <div>
            <h1 class="res-page-title">My Reservations</h1>
            <p class="res-page-sub">Halo, <strong><?= htmlspecialchars($user_name) ?></strong> — berikut semua reservasi eventmu</p>
          </div>
          <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Reservasi Baru
          </a>
        </div>

        <!-- Stats -->
        <div class="res-stats">
          <div class="res-stat">
            <span class="res-stat-dot res-stat-dot--all"></span>
            <strong><?= $counts['all'] ?></strong> Total
          </div>
          <div class="res-stat">
            <span class="res-stat-dot res-stat-dot--active"></span>
            <strong><?= $counts['active'] ?? 0 ?></strong> Aktif
          </div>
          <div class="res-stat">
            <span class="res-stat-dot res-stat-dot--pending"></span>
            <strong><?= $counts['pending'] ?? 0 ?></strong> Pending
          </div>
          <div class="res-stat">
            <span class="res-stat-dot res-stat-dot--cancel"></span>
            <strong><?= $counts['cancelled'] ?? 0 ?></strong> Dibatalkan
          </div>
        </div>
      </div>
    </div>

    <div class="container" style="padding-block: 2rem;">

      <!-- Filter Tabs -->
      <div class="res-tabs">
        <?php
        $tabs = [
          'all'     => 'Semua',
          'active'  => 'Aktif',
          'pending' => 'Pending',
          'done'    => 'Selesai',
          'cancelled'  => 'Dibatalkan',
        ];
        foreach ($tabs as $key => $label):
          $cnt = $counts[$key] ?? 0;
        ?>
          <button class="res-tab <?= $key === 'all' ? 'active' : '' ?>" data-tab="<?= $key ?>">
            <?= $label ?>
            <?php if ($cnt > 0): ?>
              <span class="res-tab__count"><?= $cnt ?></span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Reservations List -->
      <div style="display:flex;flex-direction:column;gap:1rem;margin-top:1.5rem;">

        <?php if (empty($reservations)): ?>
          <!-- Empty State -->
          <div class="res-empty">
            <div class="res-empty__icon">🎟</div>
            <h3 class="res-empty__title">Belum Ada Reservasi</h3>
            <p class="res-empty__text">Kamu belum memesan event apapun. Yuk jelajahi event seru di sekitarmu!</p>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg">
              Jelajahi Event Sekarang
            </a>
          </div>
        <?php else: ?>
          <?php foreach ($reservations as $i => $res):
            $st = $status_labels[$res['status']];
            $total = $res['price'] * $res['seats'];
          ?>
            <article class="res-card <?= $st['class'] ?> fade-up" style="animation-delay:<?= $i * 60 ?>ms"
                     data-status="<?= $res['status'] ?>">

              <!-- Image / date area -->
              <div class="res-card__img">
                <div class="res-card__img-placeholder">
                  <img
                      src="<?= BASE_URL ?>/uploads/events/<?= htmlspecialchars($res['image']) ?>"
                      alt="<?= htmlspecialchars($res['event']) ?>"
                      style="
                          width:100%;
                          height:100%;
                          object-fit:cover;
                      "
                  >
                </div>
                <div class="res-card__img-date">
                  <div class="res-card__img-day"><?= $res['date'] ?></div>
                  <div class="res-card__img-mon"><?= $res['month'] ?> <?= $res['year'] ?></div>
                </div>
              </div>

              <!-- Body -->
              <div class="res-card__body">
                <div class="res-card__header">
                  <div>
                    <h3 class="res-card__title"><?= htmlspecialchars($res['event']) ?></h3>
                    <div class="res-card__id">ID: <?= htmlspecialchars($res['id']) ?></div>
                  </div>
                  <span class="badge <?= $st['badge'] ?>"><?= $st['label'] ?></span>
                </div>

                <div class="res-card__meta">
                  <div class="res-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= htmlspecialchars($res['location']) ?>
                  </div>
                  <div class="res-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= htmlspecialchars($res['time']) ?>
                  </div>
                  <div class="res-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <strong><?= htmlspecialchars($res['category']) ?></strong><?= $res['seats'] ?> tiket
                  </div>
                </div>

                <div class="res-card__footer">
                  <div class="res-card__price">
                    <?= fmt_price($total) ?>
                  </div>
                  <div class="res-card__actions">
                        <?php

$paymentQuery = mysqli_query($conn, "
    SELECT * FROM payments
    WHERE id_reservation = {$res['id']}
    ORDER BY id_payment DESC
    LIMIT 1
");

$payment = mysqli_fetch_assoc($paymentQuery);

?>

<?php if ($res['status'] === 'confirmed'): ?>

    <div class="payment-success">
        ✅ Pembayaran Berhasil
    </div>

<?php elseif ($res['status'] === 'cancelled'): ?>

<?php elseif (!empty($payment['bukti_bayar'])): ?>

    <div class="waiting-payment">
        ⏳ Menunggu Verifikasi Admin
    </div>

<?php else: ?>

    <a href="<?= BASE_URL ?>/payment.php?id=<?= $res['id'] ?>"
      class="btn btn-primary btn-sm">

        <svg width="13" height="13"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2">

            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>

        </svg>

        Bayar

    </a>

<?php endif; ?>
                        <a href="<?= BASE_URL ?>/detail_event.php?id=<?= $res['id_event'] ?>"
                          class="btn btn-outline btn-sm">
                            <svg width="13" height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                            Detail Event
                        </a>

                        <?php if (
    $res['status'] !== 'cancelled' &&
    $res['status'] !== 'confirmed' &&
    empty($payment['bukti_bayar'])
): ?>
                            <a
    href="<?= BASE_URL ?>/my_reservations.php?cancel=<?= $res['id'] ?>"
    class="btn btn-danger btn-sm"

    onclick="return confirm('Yakin ingin membatalkan reservasi ini?')"
>

    <svg width="13"
         height="13"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2">

        <polyline points="3 6 5 6 21 6"/>
        <path d="M19 6l-1 14H6L5 6"/>
        <path d="M10 11v6"/>
        <path d="M14 11v6"/>

    </svg>

    Batalkan

</a>
                        <?php endif; ?>
                    </div>
                </div>
              </div>

            </article>
          <?php endforeach; ?>
        <?php endif; ?>

      </div><!-- /.reservations list -->

    </div><!-- /.container -->

  </main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
