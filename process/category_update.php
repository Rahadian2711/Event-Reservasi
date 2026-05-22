<?php

include "../config/koneksi.php";

$id_category = $_POST['id_category'];
$nama_kategori = $_POST['nama_kategori'];
$harga = $_POST['harga'];
$stok = $_POST['stok'];

$query = "UPDATE ticket_categories SET

nama_kategori='$nama_kategori',
harga='$harga',
stok='$stok'

WHERE id_category='$id_category'
";

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../admin/categories/index.php");
    exit;

}else{

    echo "Gagal update kategori";

}
?>