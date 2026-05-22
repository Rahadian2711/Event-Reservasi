<?php

include "../config/koneksi.php";

$id_event = $_POST['id_event'];

$jumlah_kursi = $_POST['jumlah_kursi'];

for($i = 1; $i <= $jumlah_kursi; $i++){

    $nomor_kursi = "A" . $i;

    $query = "INSERT INTO seats
    (
        nomor_kursi,
        status,
        id_event
    )

    VALUES
    (
        '$nomor_kursi',
        'available',
        '$id_event'
    )";

    mysqli_query($conn, $query);

}

header("Location: ../admin/dashboard.php");

exit;

?>