<?php

session_start();

/* ── Auth guard ── */
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../admin/events.php');
    exit;
}

require_once '../config/koneksi.php';

/* ── Validasi ID — cast ke int untuk cegah SQL injection ── */
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ../admin/events.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL NAMA FILE GAMBAR DULU (sebelum dihapus dari DB)
|--------------------------------------------------------------------------
*/

$event = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT gambar, detail_gambar
    FROM events
    WHERE id_event = '$id'
    LIMIT 1
"));

/*
|--------------------------------------------------------------------------
| HAPUS FILE GAMBAR DARI SERVER
|--------------------------------------------------------------------------
*/

$uploadDir = __DIR__ . '/../uploads/events/';

if ($event) {

    if (!empty($event['gambar']) && file_exists($uploadDir . $event['gambar'])) {
        unlink($uploadDir . $event['gambar']);
    }

    if (!empty($event['detail_gambar']) && file_exists($uploadDir . $event['detail_gambar'])) {
        unlink($uploadDir . $event['detail_gambar']);
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS BUKTI PEMBAYARAN DARI SERVER
|--------------------------------------------------------------------------
*/

$payDir     = __DIR__ . '/../uploads/payments/';
$payResults = mysqli_query($conn, "
    SELECT p.bukti_bayar
    FROM payments p
    JOIN reservations r ON p.id_reservation = r.id_reservation
    WHERE r.id_event = '$id'
");

while ($pay = mysqli_fetch_assoc($payResults)) {
    if (!empty($pay['bukti_bayar']) && file_exists($payDir . $pay['bukti_bayar'])) {
        unlink($payDir . $pay['bukti_bayar']);
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS DATA RELASI (urutan penting — FK dulu baru parent)
|--------------------------------------------------------------------------
*/

// Hapus payments dulu (FK ke reservations)
mysqli_query($conn, "
    DELETE p FROM payments p
    JOIN reservations r ON p.id_reservation = r.id_reservation
    WHERE r.id_event = '$id'
");

// Hapus reservations
mysqli_query($conn, "DELETE FROM reservations      WHERE id_event = '$id'");

// Hapus ticket categories
mysqli_query($conn, "DELETE FROM ticket_categories WHERE id_event = '$id'");

// Hapus tags
mysqli_query($conn, "DELETE FROM event_tags        WHERE id_event = '$id'");

// Hapus schedule
mysqli_query($conn, "DELETE FROM event_schedule    WHERE id_event = '$id'");

/*
|--------------------------------------------------------------------------
| HAPUS EVENT
|--------------------------------------------------------------------------
*/

mysqli_query($conn, "DELETE FROM events WHERE id_event = '$id'");

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header('Location: ../admin/events.php');
exit;
