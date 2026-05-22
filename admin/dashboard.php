<?php
session_start();
define('BASE_URL', '..');

// Guard: only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$page_title = 'Dashboard Admin';
$extra_css  = [BASE_URL . '/assets/css/admin.css'];

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$initials   = strtoupper(substr($admin_name, 0, 2));

// Simulasi data (ganti dengan query DB)
$stats = [
  ['label'=>'Total Event',       'value'=>'48',    'change'=>'+12%', 'up'=>true,  'type'=>'blue',   'icon'=>'calendar'],
  ['label'=>'Reservasi Aktif',   'value'=>'1.24K', 'change'=>'+8%',  'up'=>true,  'type'=>'green',  'icon'=>'ticket'],
  ['label'=>'Pendapatan Bulan',  'value'=>'48.2M', 'change'=>'+23%', 'up'=>true,  'type'=>'yellow', 'icon'=>'money'],
  ['label'=>'Users Terdaftar',   'value'=>'3.8K',  'change'=>'+5%',  'up'=>true,  'type'=>'blue',   'icon'=>'users'],
];

$recent_reservations = [
  ['id'=>'RES-001','user'=>'Budi Santoso',   'event'=>'Tech Summit 2025',      'seats'=>2,'total'=>300000,'status'=>'active'],
  ['id'=>'RES-002','user'=>'Sari Dewi',      'event'=>'Music Festival',        'seats'=>1,'total'=>200000,'status'=>'pending'],
  ['id'=>'RES-003','user'=>'Ahmad Rizki',    'event'=>'Design Workshop',       'seats'=>3,'total'=>225000,'status'=>'active'],
  ['id'=>'RES-004','user'=>'Linda Kusuma',   'event'=>'Startup Pitch Night',   'seats'=>2,'total'=>0,     'status'=>'active'],
  ['id'=>'RES-005','user'=>'Deni Pratama',   'event'=>'Photography Class',     'seats'=>1,'total'=>120000,'status'=>'cancel'],
];

$recent_events = [
  ['title'=>'Tech Summit Jakarta', 'date'=>'20 Jul','slots'=>45, 'sold'=>155,'price'=>150000],
  ['title'=>'Music Festival',      'date'=>'02 Aug','slots'=>12, 'sold'=>988,'price'=>200000],
  ['title'=>'Design Workshop',     'date'=>'15 Aug','slots'=>30, 'sold'=>70, 'price'=>75000],
];

function fmt_price($p) {
  return $p > 0 ? 'Rp ' . number_format($p, 0, ',', '.') : 'Gratis';
}

function svg_icon($name) {
  $icons = [
    'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    'ticket'   => '<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
    'money'    => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'home'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'list'     => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
    'grid'     => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    'chart'    => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    'plus'     => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
    'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'bell'     => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
  ];
  $path = $icons[$name] ?? '';
  return '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$path.'</svg>';
}

$status_badge = [
  'active'  => ['class'=>'badge-green',  'label'=>'Aktif'],
  'pending' => ['class'=>'badge-yellow', 'label'=>'Pending'],
  'cancel'  => ['class'=>'badge-red',    'label'=>'Batal'],
  'done'    => ['class'=>'badge-blue',   'label'=>'Selesai'],
];
?>
<?php require_once __DIR__ . '/../templates/head.php'; ?>

<div class="admin-layout">

  <!-- ════════════════ SIDEBAR ════════════════ -->
  <aside class="admin-sidebar" id="adminSidebar" role="navigation" aria-label="Admin Navigation">

    <div class="sidebar-brand">
      <div class="sidebar-brand-name">EventRes</div>
      <div class="sidebar-brand-sub">Admin Panel</div>
    </div>

    <nav class="sidebar-nav">

      <!-- Main -->
      <div>
        <div class="sidebar-section-label">Main</div>
        <ul class="sidebar-menu">
          <li>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link active" data-page="dashboard.php">
              <?= svg_icon('grid') ?> Dashboard
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-link" data-page="index.php">
              <?= svg_icon('home') ?> Lihat Website
            </a>
          </li>
        </ul>
      </div>

      <!-- Management -->
      <div>
        <div class="sidebar-section-label">Manajemen</div>
        <ul class="sidebar-menu">
          <li>
            <a href="<?= BASE_URL ?>/admin/events.php" class="sidebar-link" data-page="events.php">
              <?= svg_icon('calendar') ?> Events
              <span class="sidebar-link__badge">48</span>
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/reservations.php" class="sidebar-link" data-page="reservations.php">
              <?= svg_icon('list') ?> Reservasi
              <span class="sidebar-link__badge sidebar-link__badge--red">12</span>
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link" data-page="users.php">
              <?= svg_icon('user') ?> Users
              <span class="sidebar-link__badge">3.8K</span>
            </a>
          </li>
        </ul>
      </div>

      <!-- Reports -->
      <div>
        <div class="sidebar-section-label">Laporan</div>
        <ul class="sidebar-menu">
          <li>
            <a href="<?= BASE_URL ?>/admin/analytics.php" class="sidebar-link" data-page="analytics.php">
              <?= svg_icon('chart') ?> Analytics
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/notifications.php" class="sidebar-link" data-page="notifications.php">
              <?= svg_icon('bell') ?> Notifikasi
              <span class="sidebar-link__badge sidebar-link__badge--red">3</span>
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="sidebar-link" data-page="settings.php">
              <?= svg_icon('settings') ?> Pengaturan
            </a>
          </li>
        </ul>
      </div>

    </nav>

    <!-- Sidebar footer: user info -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div style="min-width:0;flex:1;">
          <div class="sidebar-user-name"><?= htmlspecialchars($admin_name) ?></div>
          <div class="sidebar-user-role">Super Admin</div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="sidebar-logout" title="Logout"
           onclick="return confirm('Yakin logout?')">
          <?= svg_icon('logout') ?>
        </a>
      </div>
    </div>

  </aside>

  <!-- ════════════════ MAIN CONTENT ════════════════ -->
  <div class="admin-main">

    <!-- Page header -->
    <div class="admin-page-header fade-up">
      <div>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-sub">Selamat datang kembali, <?= htmlspecialchars(explode(' ',$admin_name)[0]) ?> 👋 — Ini ringkasan hari ini.</p>
      </div>
      <div class="admin-page-actions">
        <span style="font-size:var(--text-xs);color:var(--color-text-muted);">
          <?= date('l, d F Y') ?>
        </span>
        <a href="<?= BASE_URL ?>/admin/events.php?action=new" class="btn btn-primary btn-sm">
          <?= svg_icon('plus') ?> Tambah Event
        </a>
      </div>
    </div>

    <!-- ── Stat cards ── -->
    <div class="stats-grid fade-up fade-up-d1">
      <?php foreach ($stats as $stat): ?>
        <div class="stat-card stat-card--<?= $stat['type'] ?>">
          <div class="stat-card__icon">
            <?php
            $si = ['calendar'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','ticket'=>'<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>','money'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>','users'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'];
            echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.($si[$stat['icon']]??'').'</svg>';
            ?>
          </div>
          <div class="stat-card__body">
            <div class="stat-card__label"><?= htmlspecialchars($stat['label']) ?></div>
            <div class="stat-card__value"><?= $stat['value'] ?></div>
            <div class="stat-card__change stat-card__change--<?= $stat['up'] ? 'up' : 'down' ?>">
              <?= $stat['up'] ? '↑' : '↓' ?>
              <?= $stat['change'] ?> dari bulan lalu
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Content grid ── -->
    <div class="admin-content-grid fade-up fade-up-d2">

      <!-- Recent Reservations table -->
      <div class="table-card">
        <div class="table-card__header">
          <div>
            <div class="table-card__title">Reservasi Terbaru</div>
          </div>
          <div style="display:flex;gap:0.75rem;align-items:center;">
            <div class="table-search">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Cari reservasi...">
            </div>
            <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-outline btn-sm">Lihat Semua</a>
          </div>
        </div>

        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User / ID</th>
                <th>Event</th>
                <th>Kursi</th>
                <th>Total</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_reservations as $res):
                $st = $status_badge[$res['status']] ?? ['class'=>'badge-gray','label'=>$res['status']];
                $ini = strtoupper(substr($res['user'],0,1));
              ?>
                <tr>
                  <td>
                    <div class="table-avatar">
                      <div class="table-avatar-img"><?= $ini ?></div>
                      <div>
                        <div class="table-name"><?= htmlspecialchars($res['user']) ?></div>
                        <div class="table-sub"><?= $res['id'] ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style="font-size:var(--text-sm);font-weight:500;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      <?= htmlspecialchars($res['event']) ?>
                    </div>
                  </td>
                  <td><strong><?= $res['seats'] ?></strong></td>
                  <td><strong style="color:var(--color-primary)"><?= fmt_price($res['total']) ?></strong></td>
                  <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                  <td>
                    <div class="table-actions">
                      <button class="btn btn-ghost btn-xs" title="Edit">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="btn btn-ghost btn-xs" style="color:var(--color-danger)" title="Hapus"
                        onclick="return confirm('Hapus reservasi ini?')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right widgets -->
      <div style="display:flex;flex-direction:column;gap:1.25rem;">

        <!-- Top Events widget -->
        <div class="widget-card">
          <div class="widget-header">🎟 Top Events</div>
          <div class="widget-body">
            <?php foreach ($recent_events as $ev): ?>
              <div style="display:flex;flex-direction:column;gap:0.35rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:0.5rem;">
                  <span style="font-size:var(--text-sm);font-weight:600;color:var(--color-text);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($ev['title']) ?>
                  </span>
                  <span style="font-size:var(--text-xs);color:var(--color-text-muted);flex-shrink:0;"><?= $ev['date'] ?></span>
                </div>
                <!-- Progress bar -->
                <?php $pct = min(100, round($ev['sold'] / max(1, $ev['sold'] + $ev['slots']) * 100)); ?>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                  <div style="flex:1;height:6px;background:var(--blue-100);border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?= $pct ?>%;background:var(--grad-brand);border-radius:99px;transition:width 0.6s ease;"></div>
                  </div>
                  <span style="font-size:var(--text-xs);font-weight:700;color:var(--color-primary);min-width:30px;text-align:right;"><?= $pct ?>%</span>
                </div>
                <div style="font-size:var(--text-xs);color:var(--color-text-muted);">
                  <?= $ev['sold'] ?> terjual · <?= $ev['slots'] ?> sisa
                </div>
              </div>
              <hr style="border:none;border-top:1px solid var(--color-border);">
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Activity widget -->
        <div class="widget-card">
          <div class="widget-header">🔔 Aktivitas Terbaru</div>
          <div class="widget-body">
            <?php
            $activities = [
              ['icon'=>'🎟','title'=>'Reservasi baru oleh Budi S.',  'time'=>'5 menit lalu',  'bg'=>'var(--blue-50)'],
              ['icon'=>'👤','title'=>'User baru: sari@email.com',    'time'=>'12 menit lalu', 'bg'=>'#ECFDF5'],
              ['icon'=>'✅','title'=>'Event Tech Summit disetujui',   'time'=>'1 jam lalu',    'bg'=>'#ECFDF5'],
              ['icon'=>'❌','title'=>'Reservasi RES-004 dibatalkan', 'time'=>'2 jam lalu',    'bg'=>'#FEF2F2'],
            ];
            foreach ($activities as $act):
            ?>
              <div class="activity-item">
                <div class="activity-icon" style="background:<?= $act['bg'] ?>"><?= $act['icon'] ?></div>
                <div class="activity-text">
                  <div class="activity-title"><?= htmlspecialchars($act['title']) ?></div>
                  <div class="activity-time"><?= $act['time'] ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

    </div><!-- /.admin-content-grid -->

  </div><!-- /.admin-main -->

  <!-- Mobile sidebar toggle -->
  <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle sidebar">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

</div><!-- /.admin-layout -->

<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
