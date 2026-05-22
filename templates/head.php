<?php
/**
 * templates/head.php — Reusable <head> untuk semua halaman
 *
 * Set sebelum include:
 *   $page_title  — string, default "Event Reservation"
 *   $extra_css   — array path CSS tambahan
 *   $body_class  — string class untuk <body>
 */
$page_title  = $page_title  ?? 'Event Reservation';
$body_class  = $body_class  ?? '';
$base        = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($page_description ?? 'Book and manage your event reservations.') ?>">
  <title><?= htmlspecialchars($page_title) ?> — EventRes</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Design System -->
  <link rel="stylesheet" href="<?= $base ?>/assets/css/design-system.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/navbar.css">

  <?php if (!empty($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
  <?php endforeach; endif; ?>

  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%232563EB'/><text y='22' x='5' font-size='18'>📅</text></svg>">
</head>
<body class="<?= htmlspecialchars($body_class) ?>">
