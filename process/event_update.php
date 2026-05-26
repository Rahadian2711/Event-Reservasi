<?php

require_once '../config/koneksi.php';

$id_event   = $_POST['id_event'];

$nama_event = $_POST['nama_event'];
$kategori   = $_POST['category'];

$tanggal = $_POST['event_date']
          . ' ' .
            $_POST['event_time'];

$lokasi     = $_POST['location'];
$organizer  = $_POST['organizer'];

$deskripsi  = $_POST['description'];

$status     = $_POST['status'];

/*
|--------------------------------------------------------------------------
| AMBIL GAMBAR LAMA (fallback jika tidak upload baru)
|--------------------------------------------------------------------------
*/

$existing = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT gambar, detail_gambar
    FROM events
    WHERE id_event = '$id_event'
    LIMIT 1
"));

$gambar        = $existing['gambar'];
$detail_gambar = $existing['detail_gambar'];

$uploadDir = __DIR__ . '/../uploads/events/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/*
|--------------------------------------------------------------------------
| PROSES UPLOAD GAMBAR UTAMA
|--------------------------------------------------------------------------
*/

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    $newName = 'event_' . $id_event . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $uploadDir . $newName)) {
        if (!empty($existing['gambar']) && file_exists($uploadDir . $existing['gambar'])) {
            unlink($uploadDir . $existing['gambar']);
        }
        $gambar = $newName;
    }
}

/*
|--------------------------------------------------------------------------
| PROSES UPLOAD DETAIL GAMBAR
|--------------------------------------------------------------------------
*/

if (isset($_FILES['detail_gambar']) && $_FILES['detail_gambar']['error'] === UPLOAD_ERR_OK) {
    $ext     = strtolower(pathinfo($_FILES['detail_gambar']['name'], PATHINFO_EXTENSION));
    $newName = 'detail_' . $id_event . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['detail_gambar']['tmp_name'], $uploadDir . $newName)) {
        if (!empty($existing['detail_gambar']) && file_exists($uploadDir . $existing['detail_gambar'])) {
            unlink($uploadDir . $existing['detail_gambar']);
        }
        $detail_gambar = $newName;
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE EVENTS
|--------------------------------------------------------------------------
*/

$gambar_safe        = mysqli_real_escape_string($conn, $gambar);
$detail_gambar_safe = mysqli_real_escape_string($conn, $detail_gambar);

mysqli_query($conn, "

UPDATE events SET

    nama_event    = '$nama_event',
    kategori      = '$kategori',
    tanggal       = '$tanggal',
    lokasi        = '$lokasi',
    organizer     = '$organizer',
    deskripsi     = '$deskripsi',
    status        = '$status',
    gambar        = '$gambar_safe',
    detail_gambar = '$detail_gambar_safe'

WHERE id_event = '$id_event'

");

/*
|--------------------------------------------------------------------------
| HAPUS TICKET LAMA
|--------------------------------------------------------------------------
*/

mysqli_query($conn, "
DELETE FROM ticket_categories
WHERE id_event = '$id_event'
");

/*
|--------------------------------------------------------------------------
| INSERT TICKET BARU
|--------------------------------------------------------------------------
*/

$ticket_names  = $_POST['ticket_name'];
$ticket_prices = $_POST['ticket_price'];
$ticket_stocks = $_POST['ticket_stock'];

for($i = 0; $i < count($ticket_names); $i++){

    $nama  = $ticket_names[$i];
    $harga = $ticket_prices[$i];
    $stok  = $ticket_stocks[$i];

    mysqli_query($conn, "

    INSERT INTO ticket_categories (

        id_event,
        nama_kategori,
        harga,
        stok

    )

    VALUES (

        '$id_event',
        '$nama',
        '$harga',
        '$stok'

    )

    ");
}

/*
|--------------------------------------------------------------------------
| HAPUS SCHEDULE LAMA
|--------------------------------------------------------------------------
*/

mysqli_query($conn, "
DELETE FROM event_schedule
WHERE id_event = '$id_event'
");

/*
|--------------------------------------------------------------------------
| INSERT SCHEDULE BARU
|--------------------------------------------------------------------------
*/

$schedule_jam      = $_POST['schedule_jam'];
$schedule_kegiatan = $_POST['schedule_kegiatan'];

for($i = 0; $i < count($schedule_jam); $i++){

    $jam       = $schedule_jam[$i];
    $kegiatan  = $schedule_kegiatan[$i];

    mysqli_query($conn, "

    INSERT INTO event_schedule (

        id_event,
        jam,
        kegiatan

    )

    VALUES (

        '$id_event',
        '$jam',
        '$kegiatan'

    )

    ");
}

header('Location: ../admin/events.php');

exit;