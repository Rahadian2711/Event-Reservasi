<?php

require_once '../config/database.php';

$nama_event = $_POST['nama_event'];
$tanggal    = $_POST['tanggal'];
$lokasi     = $_POST['lokasi'];
$deskripsi  = $_POST['deskripsi'];
$kategori   = $_POST['kategori'];
$harga      = $_POST['harga'];
$slots      = $_POST['slots'];

$gambar = $_FILES['gambar']['name'];

move_uploaded_file(
    $_FILES['gambar']['tmp_name'],
    "../uploads/events/" . $gambar
);

$query = "INSERT INTO events (
    nama_event,
    tanggal,
    lokasi,
    deskripsi,
    gambar,
    kategori,
    harga,
    slots
) VALUES (
    '$nama_event',
    '$tanggal',
    '$lokasi',
    '$deskripsi',
    '$gambar',
    '$kategori',
    '$harga',
    '$slots'
)";

mysqli_query($conn, $query);

header("Location: ../admin/events.php");
exit;