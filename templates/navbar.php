<?php
/**
 * templates/navbar.php — Floating modern navbar
 * Baca dari session: user_id, user_name, user_role
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$is_logged = isset($_SESSION['user_id']);
$is_admin  = $is_logged && ($_SESSION['user_role'] ?? '') === 'admin';
$uname     = $_SESSION['user_name'] ?? '';
$initials  = '';
if ($uname) {
  $parts    = explode(' ', trim($uname));
  $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

/* SVG icon helpers */
function nav_icon($path, $size = 15) {
  return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$path.'</svg>';
}
$ico_home   = nav_icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>');
$ico_ticket = nav_icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/>');
$ico_grid   = nav_icon('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>');
$ico_logout = nav_icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>');
$ico_cal    = nav_icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>');
?>
<header id="navbar" class="navbar" role="banner">
  <div class="navbar__pill">

    <!-- Brand -->
    <a href="<?= $base ?>/index.php" class="navbar__brand" aria-label="EventRes Home">
      <div class="brand-logo">
        <?= nav_icon('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', 18) ?>
      </div>
      <div>
        <div class="brand-name">EventRes</div>
        <div class="brand-sub">Book Your Moment</div>
      </div>
    </a>

    <!-- Desktop nav -->
    <nav class="navbar__nav" aria-label="Main navigation">
      <a class="nav-item" href="<?= $base ?>/index.php" data-page="index.php">
        <?= $ico_home ?> Home
      </a>

      <?php if ($is_logged): ?>
        <a class="nav-item" href="<?= $base ?>/my_reservations.php" data-page="my_reservations.php">
          <?= $ico_ticket ?> My Reservations
        </a>
      <?php endif; ?>

      <?php if ($is_admin): ?>
        <span class="nav-sep" aria-hidden="true"></span>
        <a class="nav-item nav-item--admin" href="<?= $base ?>/admin/dashboard.php" data-page="dashboard.php">
          <?= $ico_grid ?> Admin Dashboard
        </a>
      <?php endif; ?>
    </nav>

    <!-- Desktop actions -->
    <div class="navbar__actions">
      <?php if ($is_logged): ?>
        <div class="nav-user" title="<?= htmlspecialchars($uname) ?>">
          <div class="nav-avatar"><?= htmlspecialchars($initials) ?: '?' ?></div>
          <span><?= htmlspecialchars(explode(' ', $uname)[0]) ?></span>
        </div>
        <a class="nav-item nav-item--logout btn btn-sm" href="<?= $base ?>/logout.php"
           onclick="return confirm('Yakin logout?')">
          <?= $ico_logout ?> Logout
        </a>
      <?php else: ?>
        <a class="btn btn-outline btn-sm" href="<?= $base ?>/login.php">Login</a>
        <a class="btn btn-primary btn-sm" href="<?= $base ?>/register.php">Daftar Gratis</a>
      <?php endif; ?>
    </div>

    <!-- Hamburger -->
    <button id="navBurger" class="navbar__burger" aria-expanded="false" aria-label="Open menu" aria-controls="navDrawer">
      <span class="burger-line"></span>
      <span class="burger-line"></span>
      <span class="burger-line"></span>
    </button>

  </div><!-- /.navbar__pill -->
</header>

<!-- Mobile Drawer -->
<div id="navDrawer" class="navbar__drawer" role="dialog" aria-label="Menu navigasi" aria-modal="true">

  <?php if ($is_logged): ?>
    <div class="drawer-user">
      <div class="drawer-avatar"><?= htmlspecialchars($initials) ?: '?' ?></div>
      <div>
        <div class="drawer-user-name"><?= htmlspecialchars($uname) ?></div>
        <div class="drawer-user-role"><?= $is_admin ? 'Administrator' : 'Member' ?></div>
      </div>
    </div>
  <?php endif; ?>

  <a class="drawer-link" href="<?= $base ?>/index.php" data-page="index.php">
    <?= nav_icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>', 18) ?> Home
  </a>

  <?php if ($is_logged): ?>
    <a class="drawer-link" href="<?= $base ?>/my_reservations.php" data-page="my_reservations.php">
      <?= nav_icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/>', 18) ?> My Reservations
    </a>
  <?php endif; ?>

  <?php if ($is_admin): ?>
    <hr class="drawer-sep">
    <a class="drawer-link drawer-link--admin" href="<?= $base ?>/admin/dashboard.php" data-page="dashboard.php">
      <?= nav_icon('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>', 18) ?> Dashboard Admin
    </a>
  <?php endif; ?>

  <hr class="drawer-sep">

  <?php if ($is_logged): ?>
    <a class="drawer-link drawer-link--logout" href="<?= $base ?>/logout.php" onclick="return confirm('Yakin logout?')">
      <?= $ico_logout ?> Logout
    </a>
  <?php else: ?>
    <div class="drawer-auth">
      <a class="btn btn-outline w-full" href="<?= $base ?>/login.php">Login</a>
      <a class="btn btn-primary w-full" href="<?= $base ?>/register.php">Daftar Gratis</a>
    </div>
  <?php endif; ?>

</div>

<div id="navOverlay" class="nav-overlay" aria-hidden="true"></div>
