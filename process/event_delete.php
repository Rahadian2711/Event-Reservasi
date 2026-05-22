<?php

include "../config/koneksi.php";

$id = $_GET['id'];

$query = "DELETE FROM events
WHERE id_event='$id'";

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../admin/events/index.php");
    exit;

}else{

    echo "Gagal delete event";

}
?>