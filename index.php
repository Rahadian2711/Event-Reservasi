<?php
session_start();
define('BASE_URL', '/event-reservation'); // Ubah ke '/event-reservation' jika di subfolder
require_once 'config/koneksi.php';
$page_title = 'Home';
$extra_css  = [BASE_URL . '/assets/css/home.css'];

// ── Search & Filter dari GET ──
$search   = mysqli_real_escape_string($conn, trim($_GET['q']        ?? ''));
$category = mysqli_real_escape_string($conn, trim($_GET['category'] ?? ''));

// ── Pagination ──
$per_page   = 6;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $per_page;

// ── WHERE clause dinamis ──
$where = "WHERE events.status = 'published'";
if ($search   !== '') $where .= " AND events.nama_event LIKE '%$search%'";
if ($category !== '') $where .= " AND events.kategori = '$category'";

// ── Total rows untuk pagination ──
$qCount   = mysqli_query($conn, "SELECT COUNT(DISTINCT events.id_event) as total FROM events LEFT JOIN ticket_categories ON events.id_event = ticket_categories.id_event $where");
$total    = (int)(mysqli_fetch_assoc($qCount)['total'] ?? 0);
$total_pages = (int)ceil($total / $per_page);

// ── Query utama ──
$result = mysqli_query($conn, "
    SELECT
        events.*,
        MIN(ticket_categories.harga)      AS harga_mulai,
        COALESCE(SUM(ticket_categories.stok), 0) AS total_stok
    FROM events
    LEFT JOIN ticket_categories ON events.id_event = ticket_categories.id_event
    $where
    GROUP BY events.id_event
    ORDER BY events.created_at DESC
    LIMIT $per_page OFFSET $offset
");

$events = [];

while ($row = mysqli_fetch_assoc($result)) {
    $events[] = [
        'id'       => $row['id_event']   ?? '',
        'title'    => $row['nama_event'] ?? '',
        'date'     => date('d', strtotime($row['tanggal'])),
        'month'    => strtoupper(date('M', strtotime($row['tanggal']))),
        'location' => $row['lokasi']     ?? '',
        'price'    => (int)($row['harga_mulai'] ?? 0),
        'category' => $row['kategori']   ?? '',
        'slots'    => (int)($row['total_stok'] ?? 0),
        'gambar'   => $row['gambar']     ?? '',
    ];
}
$is_logged = isset($_SESSION['user_id']);

function fmt_price($p) {
  return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<div class="page-wrap">
  <?php require_once __DIR__ . '/templates/navbar.php'; ?>

  <main class="page-main">

    <!-- ════════════════════════════════
         HERO
    ════════════════════════════════ -->
    <section class="hero">
      <div class="container">
        <div class="hero__inner">

          <!-- Left copy -->
          <div class="fade-up">
            <div class="hero__tag">
              <span class="hero__tag-dot"></span>
              Platform Event #1 di Indonesia
            </div>
            <h1 class="hero__title">
              Temukan &amp; Pesan<br>
              <span class="hero__title-highlight">Event Terbaik</span><br>
              Untukmu
            </h1>
            <p class="hero__subtitle">
              Ribuan event menanti — konferensi, workshop, festival, dan lebih banyak lagi.
              Reservasi mudah, cepat, dan aman dalam satu platform.
            </p>
            <div class="hero__cta">
              <a href="#events" class="btn btn-primary btn-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Jelajahi Event
              </a>
              <?php if (!$is_logged): ?>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline btn-lg">Daftar Gratis</a>
              <?php endif; ?>
            </div>

            <div class="hero__stats">
              <div class="hero-stat">
                <div class="hero-stat__num">2.4K+</div>
                <div class="hero-stat__label">Event Aktif</div>
              </div>
              <div class="hero-stat">
                <div class="hero-stat__num">48K+</div>
                <div class="hero-stat__label">Pengguna</div>
              </div>
              <div class="hero-stat">
                <div class="hero-stat__num">120+</div>
                <div class="hero-stat__label">Kota</div>
              </div>
            </div>
          </div>

          <!-- Right visual card -->
          <div class="hero__visual fade-up fade-up-d2">
            <div class="hero-card">
              <div class="hero-card__header">
                <div class="hero-card__title">🗓 Event Mendatang</div>
                <span class="badge badge-blue">Live</span>
              </div>
              <div class="hero-mini-events">
                <?php foreach (array_slice($events, 0, 3) as $ev): ?>
                  <a class="hero-mini-event" href="<?= BASE_URL ?>/detail_event.php?id=<?= $ev['id'] ?>">
                    <div class="hero-mini-event__date">
                      <div class="hero-mini-event__day"><?= $ev['date'] ?></div>
                      <div class="hero-mini-event__mon"><?= $ev['month'] ?></div>
                    </div>
                    <div class="hero-mini-event__info">
                      <div class="hero-mini-event__name"><?= htmlspecialchars($ev['title']) ?></div>
                      <div class="hero-mini-event__loc">📍 <?= htmlspecialchars($ev['location']) ?></div>
                    </div>
                    <div class="hero-mini-event__price"><?= fmt_price($ev['price']) ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ════════════════════════════════
         SEARCH & FILTER
    ════════════════════════════════ -->
    <section class="search-section">
      <div class="container">
        <form method="GET" action="">
          <div class="search-box">
            <div class="search-main">
              <span class="search-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              </span>
              <input class="search-input" type="text" name="q"
                placeholder="Cari event, workshop, festival..."
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </div>
            <div class="search-divider"></div>
            <div class="search-filter">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="2"><path d="M21 10H7M21 6H3M21 14H3M21 18H7"/></svg>
              <select name="category">
                <option value="">Semua Kategori</option>
                <option value="Technology"  <?= ($_GET['category']??'')=='Technology'  ?'selected':'' ?>>Technology</option>
                <option value="Music"       <?= ($_GET['category']??'')=='Music'       ?'selected':'' ?>>Music</option>
                <option value="Design"      <?= ($_GET['category']??'')=='Design'      ?'selected':'' ?>>Design</option>
                <option value="Business"    <?= ($_GET['category']??'')=='Business'    ?'selected':'' ?>>Business</option>
                <option value="Art"         <?= ($_GET['category']??'')=='Art'         ?'selected':'' ?>>Art</option>
                <option value="Culinary"    <?= ($_GET['category']??'')=='Culinary'    ?'selected':'' ?>>Culinary</option>
              </select>
            </div>
            <div class="search-divider"></div>
            <button type="submit" class="btn btn-primary btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Cari
            </button>
          </div>
        </form>

        <!-- Filter chips -->
        <div class="filter-chips">
          <span style="font-size:0.78rem;font-weight:600;color:var(--color-text-muted)">Filter:</span>
          <?php
          $cats = ['Semua'=>'', 'Technology'=>'Technology', 'Music'=>'Music', 'Design'=>'Design', 'Business'=>'Business', 'Art'=>'Art', 'Culinary'=>'Culinary'];
          foreach ($cats as $label => $val):
            $active = ($val === ($_GET['category'] ?? '')) ? ' active' : '';
          ?>
            <a href="?category=<?= urlencode($val) ?>" class="chip<?= $active ?>" data-filter="<?= htmlspecialchars($val) ?>">
              <?= htmlspecialchars($label) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ════════════════════════════════
         EVENTS GRID
    ════════════════════════════════ -->
    <section class="events-section" id="events">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-label">Event Tersedia</div>
            <h2 class="section-title">Event Mendatang</h2>
          </div>
          <div style="display:flex;align-items:center;gap:0.75rem;color:var(--color-text-muted);font-size:0.83rem;">
            <?= $total ?> event ditemukan
          </div>
        </div>

        <div class="grid grid-auto">
          <?php foreach ($events as $i => $ev): ?>
            <article class="event-card fade-up" style="animation-delay:<?= $i * 60 ?>ms">
              <div class="event-card__img">
                <div class="event-card__img-placeholder">
    <?php
$gambar = !empty($ev['gambar'])
    ? $ev['gambar']
    : 'default-event.jpg';
?>

<img 
   src="<?= BASE_URL ?>/uploads/events/<?= htmlspecialchars($gambar ?? '') ?>"
   alt="<?= htmlspecialchars($ev['title'] ?? '') ?>"
   class="event-card__image"
>
</div>
                <div class="event-card__overlay"></div>

                <div class="event-card__date-badge">
                  <div class="event-card__date-day"><?= $ev['date'] ?></div>
                  <div class="event-card__date-mon"><?= $ev['month'] ?></div>
                </div>

                <button class="event-card__wish" aria-label="Wishlist">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>

                <?php if ((int)$ev['slots'] < 20): ?>
                  <div class="event-card__slots">
                    <span class="badge badge-red">⚡ Hampir habis</span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="event-card__body">
                <div class="event-card__meta">
                  <span class="badge badge-blue"><?= htmlspecialchars($ev['category'] ?? '') ?></span>
                  <?php if ((int)$ev['price'] === 0): ?>
                    <span class="badge badge-green">Gratis</span>
                  <?php endif; ?>
                </div>

                <h3 class="event-card__title"><?= htmlspecialchars($ev['title'] ?? '') ?></h3>

                <div class="event-card__info">
                  <div class="event-card__info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= htmlspecialchars($ev['location'] ?? '') ?>
                  </div>
                  <div class="event-card__info-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?= $ev['date'] . ' ' . $ev['month'] ?> · <?= $ev['slots'] ?> kursi tersedia
                  </div>
                </div>
              </div>

              <div class="event-card__footer">
                <div class="event-card__price">
                  <div class="event-card__price-label">Harga mulai</div>
                  <div class="event-card__price-val <?= (int)$ev['price']===0?'event-card__price-free':'' ?>">
                    <?= fmt_price($ev['price']) ?>
                  </div>
                </div>
                <a href="<?= BASE_URL ?>/detail_event.php?id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm">
                  Pesan Sekarang
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- ── PAGINATION ── -->
        <?php if ($total_pages > 1): ?>
        <nav class="pagination" aria-label="Halaman event">

          <?php
          $qs = http_build_query(array_filter([
              'q'        => $_GET['q']        ?? '',
              'category' => $_GET['category'] ?? '',
          ]));
          $qs = $qs ? $qs . '&' : '';
          ?>

          <!-- Prev -->
          <?php if ($page > 1): ?>
            <a href="?<?= $qs ?>page=<?= $page - 1 ?>" class="btn btn-outline btn-sm page-btn--nav page-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              Prev
            </a>
          <?php else: ?>
            <span class="btn btn-outline btn-sm page-btn--nav page-btn" style="opacity:0.4;cursor:not-allowed;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              Prev
            </span>
          <?php endif; ?>

          <!-- Page numbers -->
          <?php
          $start = max(1, $page - 2);
          $end   = min($total_pages, $page + 2);
          ?>
          <?php if ($start > 1): ?>
            <a href="?<?= $qs ?>page=1" class="page-btn">1</a>
            <?php if ($start > 2): ?><span class="page-dots">···</span><?php endif; ?>
          <?php endif; ?>

          <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="?<?= $qs ?>page=<?= $p ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"
               <?= $p === $page ? 'aria-current="page"' : '' ?>>
              <?= $p ?>
            </a>
          <?php endfor; ?>

          <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?><span class="page-dots">···</span><?php endif; ?>
            <a href="?<?= $qs ?>page=<?= $total_pages ?>" class="page-btn"><?= $total_pages ?></a>
          <?php endif; ?>

          <!-- Next -->
          <?php if ($page < $total_pages): ?>
            <a href="?<?= $qs ?>page=<?= $page + 1 ?>" class="btn btn-outline btn-sm page-btn--nav page-btn">
              Next
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          <?php else: ?>
            <span class="btn btn-outline btn-sm page-btn--nav page-btn" style="opacity:0.4;cursor:not-allowed;">
              Next
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
          <?php endif; ?>

        </nav>
        <?php endif; ?>

      </div>
    </section>

  </main><!-- close inside footer -->

<?php require_once __DIR__ . '/templates/footer.php'; ?>
