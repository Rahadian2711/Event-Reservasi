<?php
session_start();
define('BASE_URL', '/event-reservation');
require_once __DIR__ . '/config/koneksi.php';

if (isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

$page_title = 'Login';
$extra_css  = [BASE_URL . '/assets/css/auth.css'];

// Handle POST (ganti dengan logika DB kamu)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $query = mysqli_query($conn,
        "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        if (password_verify($pass, $user['password'])) {

            $_SESSION['user_id']   = $user['id_user'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];

            header("Location: index.php");
            exit;

        } else {

            $error = "Password salah!";
        }

    } else {

        $error = "Email tidak ditemukan!";
    }
}
?>
<?php require_once __DIR__ . '/templates/head.php'; ?>

<div class="auth-page">

  <!-- Left panel -->
  <div class="auth-panel">
    <div class="auth-shape auth-shape-1"></div>
    <div class="auth-shape auth-shape-2"></div>
    <div class="auth-shape auth-shape-3"></div>

    <div class="auth-panel__brand">
      <div class="auth-panel__logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="auth-panel__brand-name">EventRes</div>
    </div>

    <div class="auth-panel__content">
      <h2 class="auth-panel__heading">
        Selamat datang<br>kembali <span>👋</span>
      </h2>
      <p class="auth-panel__text">
        Masuk dan lanjutkan perjalanan event-mu. Ribuan event seru menunggumu di platform kami.
      </p>
      <div class="auth-features">
        <?php
        $features = [
          ['icon'=>'🎟', 'text'=>'Kelola semua reservasi dalam satu tempat'],
          ['icon'=>'🔔', 'text'=>'Notifikasi otomatis sebelum event dimulai'],
          ['icon'=>'🔒', 'text'=>'Transaksi aman dan terjamin'],
        ];
        foreach ($features as $f):
        ?>
          <div class="auth-feature">
            <div class="auth-feature__icon"><?= $f['icon'] ?></div>
            <div class="auth-feature__text"><?= $f['text'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="auth-panel__footer">© <?= date('Y') ?> EventRes · Semua hak dilindungi</div>
  </div>

  <!-- Right form -->
  <div class="auth-form-side">
    <div class="auth-form-wrap fade-up">

      <div class="auth-form-header">
        <h3 class="auth-form-title">Masuk ke Akun</h3>
        <p class="auth-form-sub">
          Belum punya akun?
          <a href="<?= BASE_URL ?>/register.php">Daftar sekarang</a>
        </p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:1.5rem">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Social login -->
      <div class="auth-social">
        <a href="#" class="auth-social-btn">
          <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
          Google
        </a>
        <a href="#" class="auth-social-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Facebook
        </a>
      </div>

      <div class="auth-divider"><span class="auth-divider-text">atau masuk dengan email</span></div>

      <form class="auth-form" method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <input class="form-input" type="email" id="email" name="email"
              placeholder="nama@email.com" required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-wrap">
            <input class="form-input" type="password" id="password" name="password"
              placeholder="Masukkan password" required>
            <button type="button" class="password-toggle" aria-label="Toggle password">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <a href="#" class="auth-forgot">Lupa password?</a>
        </div>

        <label class="form-checkbox">
          <input type="checkbox" name="remember">
          <span class="form-checkbox-label">Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn btn-primary w-full btn-lg">
          Masuk ke Akun
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </form>

    </div>
  </div>

</div>

<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
