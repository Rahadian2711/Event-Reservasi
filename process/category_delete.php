<?php

include "../config/koneksi.php";

$id = $_GET['id'];

$query = "DELETE FROM ticket_categories
WHERE id_category='$id'";

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../admin/categories/index.php");
    exit;

}else{

    echo "Gagal delete kategori";

}
?>