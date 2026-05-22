<?php
/**
 * logout.php — Hancurkan session dan redirect ke login
 */
session_start();
session_unset();
session_destroy();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 3600, '/');
}

header('Location: /event-reservation/login.php');
exit;
