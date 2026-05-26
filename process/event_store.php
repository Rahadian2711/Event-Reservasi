<?php

session_start();

/* ── Auth guard ── */
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../admin/events.php');
    exit;
}

require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

/* ── Sanitasi input teks ── */
$id_event   = (int)($_POST['id_event'] ?? 0);
$nama_event = mysqli_real_escape_string($conn, $_POST['nama_event']  ?? '');
$kategori   = mysqli_real_escape_string($conn, $_POST['category']    ?? '');
$lokasi     = mysqli_real_escape_string($conn, $_POST['location']    ?? '');
$organizer  = mysqli_real_escape_string($conn, $_POST['organizer']   ?? '');
$deskripsi  = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$status     = mysqli_real_escape_string($conn, $_POST['status']      ?? 'draft');
$tanggal    = mysqli_real_escape_string($conn,
    ($_POST['event_date'] ?? '') . ' ' . ($_POST['event_time'] ?? '00:00') . ':00'
);

/* ── Pastikan folder upload ada ── */
$uploadDir = __DIR__ . '/../uploads/events/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| UPLOAD GAMBAR UTAMA
|--------------------------------------------------------------------------
*/

$gambar = '';

// Ambil gambar lama jika ini mode edit
if ($id_event > 0) {
    $old = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT gambar, detail_gambar FROM events WHERE id_event = '$id_event' LIMIT 1"
    ));
    $gambar        = $old['gambar']        ?? '';
    $detail_gambar = $old['detail_gambar'] ?? '';
}

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {

    $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    $newName = 'event_' . time() . '_' . uniqid() . '.' . $ext;

    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir . $newName)) {

        // Hapus file lama kalau edit
        if (!empty($gambar) && file_exists($uploadDir . $gambar)) {
            unlink($uploadDir . $gambar);
        }

        $gambar = $newName;
    }
}

/*
|--------------------------------------------------------------------------
| UPLOAD DETAIL GAMBAR
|--------------------------------------------------------------------------
*/

$detail_gambar = $detail_gambar ?? '';

if (isset($_FILES['detail_gambar']) && $_FILES['detail_gambar']['error'] === UPLOAD_ERR_OK) {

    $ext     = strtolower(pathinfo($_FILES['detail_gambar']['name'], PATHINFO_EXTENSION));
    $newName = 'detail_' . time() . '_' . uniqid() . '.' . $ext;

    if (move_uploaded_file($_FILES['detail_gambar']['tmp_name'], $uploadDir . $newName)) {

        if (!empty($detail_gambar) && file_exists($uploadDir . $detail_gambar)) {
            unlink($uploadDir . $detail_gambar);
        }

        $detail_gambar = $newName;
    }
}

$gambar_safe        = mysqli_real_escape_string($conn, $gambar);
$detail_gambar_safe = mysqli_real_escape_string($conn, $detail_gambar);

/*
|--------------------------------------------------------------------------
| INSERT ATAU UPDATE EVENT
|--------------------------------------------------------------------------
*/

if ($id_event > 0) {

    /*
    |--------------------------------------------------------------------------
    | UPDATE EVENT — termasuk tanggal & gambar
    |--------------------------------------------------------------------------
    */

    mysqli_query($conn, "
        UPDATE events SET
            nama_event    = '$nama_event',
            tanggal       = '$tanggal',
            lokasi        = '$lokasi',
            organizer     = '$organizer',
            deskripsi     = '$deskripsi',
            gambar        = '$gambar_safe',
            detail_gambar = '$detail_gambar_safe',
            kategori      = '$kategori',
            status        = '$status'
        WHERE id_event = '$id_event'
    ");

    /* Hapus ticket & schedule lama sebelum insert baru */
    mysqli_query($conn, "DELETE FROM ticket_categories WHERE id_event = '$id_event'");
    mysqli_query($conn, "DELETE FROM event_schedule    WHERE id_event = '$id_event'");
    mysqli_query($conn, "DELETE FROM event_tags        WHERE id_event = '$id_event'");

} else {

    /*
    |--------------------------------------------------------------------------
    | INSERT EVENT BARU
    |--------------------------------------------------------------------------
    */

    mysqli_query($conn, "
        INSERT INTO events (
            nama_event, tanggal, lokasi, organizer,
            deskripsi, gambar, detail_gambar, kategori, status
        ) VALUES (
            '$nama_event', '$tanggal', '$lokasi', '$organizer',
            '$deskripsi', '$gambar_safe', '$detail_gambar_safe', '$kategori', '$status'
        )
    ");

    $id_event = mysqli_insert_id($conn);
}

/*
|--------------------------------------------------------------------------
| TICKET CATEGORIES
|--------------------------------------------------------------------------
*/

$ticket_names  = $_POST['ticket_name']  ?? [];
$ticket_prices = $_POST['ticket_price'] ?? [];
$ticket_stocks = $_POST['ticket_stock'] ?? [];

for ($i = 0; $i < count($ticket_names); $i++) {

    $nama  = mysqli_real_escape_string($conn, trim($ticket_names[$i]  ?? ''));
    $harga = (int)($ticket_prices[$i] ?? 0);
    $stok  = (int)($ticket_stocks[$i] ?? 0);

    // Skip row yang kosong
    if ($nama === '') continue;

    mysqli_query($conn, "
        INSERT INTO ticket_categories (id_event, nama_kategori, harga, stok)
        VALUES ('$id_event', '$nama', '$harga', '$stok')
    ");
}

/*
|--------------------------------------------------------------------------
| EVENT TAGS
|--------------------------------------------------------------------------
*/

$rawTags = $_POST['tags'] ?? '';
$tags    = explode(',', $rawTags);

foreach ($tags as $tag) {

    $tag = mysqli_real_escape_string($conn, trim($tag));

    if ($tag === '') continue;

    mysqli_query($conn, "
        INSERT INTO event_tags (id_event, tag_name)
        VALUES ('$id_event', '$tag')
    ");
}

/*
|--------------------------------------------------------------------------
| EVENT SCHEDULE
|--------------------------------------------------------------------------
*/

$schedule_jam      = $_POST['schedule_jam']      ?? [];
$schedule_kegiatan = $_POST['schedule_kegiatan'] ?? [];

for ($i = 0; $i < count($schedule_jam); $i++) {

    $jam      = mysqli_real_escape_string($conn, trim($schedule_jam[$i]      ?? ''));
    $kegiatan = mysqli_real_escape_string($conn, trim($schedule_kegiatan[$i] ?? ''));

    // Skip row kosong
    if ($jam === '' && $kegiatan === '') continue;

    mysqli_query($conn, "
        INSERT INTO event_schedule (id_event, jam, kegiatan)
        VALUES ('$id_event', '$jam', '$kegiatan')
    ");
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header('Location: ../admin/events.php');
exit;
