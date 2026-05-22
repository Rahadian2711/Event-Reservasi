<?php

include "../config/koneksi.php";

$id_event = $_POST['id_event'];
$nama_kategori = $_POST['nama_kategori'];
$harga = $_POST['harga'];
$stok = $_POST['stok'];

$query = "INSERT INTO ticket_categories
(
    id_event,
    nama_kategori,
    harga,
    stok
)

VALUES
(
    '$id_event',
    '$nama_kategori',
    '$harga',
    '$stok'
)";

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../admin/categories/index.php");
    exit;

}else{

    echo "Gagal tambah kategori";

}
?>