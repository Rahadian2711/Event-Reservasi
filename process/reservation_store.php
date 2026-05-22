<?php

session_start();

include "../config/koneksi.php";

$id_user = $_SESSION['id_user'];

$id_category = $_POST['id_category'];
$id_event = $_POST['id_event'];
$jumlah_tiket = $_POST['jumlah_tiket'];

$query_category = "SELECT *
FROM ticket_categories
WHERE id_category='$id_category'";

$result_category = mysqli_query($conn, $query_category);

$category = mysqli_fetch_assoc($result_category);

$stok = $category['stok'];
$harga = $category['harga'];

if($stok <= 0){

    echo "Tiket habis";
    exit;

}

if($jumlah_tiket > $stok){

    echo "Stok tiket tidak cukup";
    exit;

}

$subtotal = $harga * $jumlah_tiket;

$query_reservation = "INSERT INTO reservations
(
    id_user,
    id_event,
    total_harga,
    status
)

VALUES
(
    '$id_user',
    '$id_event',
    '$subtotal',
    'pending'
)";

$result_reservation = mysqli_query($conn, $query_reservation);

$id_reservation = mysqli_insert_id($conn);

$query_detail = "INSERT INTO reservation_details
(
    id_reservation,
    id_category,
    jumlah_tiket,
    harga_saat_booking,
    subtotal
)

VALUES
(
    '$id_reservation',
    '$id_category',
    '$jumlah_tiket',
    '$harga',
    '$subtotal'
)";

mysqli_query($conn, $query_detail);

$sisa_stok = $stok - $jumlah_tiket;

$query_update_stok = "UPDATE ticket_categories
SET stok='$sisa_stok'
WHERE id_category='$id_category'";

mysqli_query($conn, $query_update_stok);

header("Location: ../my_reservations.php");

exit;

?>