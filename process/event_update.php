<?php

include "../config/koneksi.php";

$id_event = $_POST['id_event'];
$nama_event = $_POST['nama_event'];
$tanggal = $_POST['tanggal'];
$lokasi = $_POST['lokasi'];
$deskripsi = $_POST['deskripsi'];

$nama_gambar = $_FILES['gambar']['name'];

if($nama_gambar != ""){

    $tmp_gambar = $_FILES['gambar']['tmp_name'];

    $allowed = ['jpg', 'jpeg', 'png'];

    $extension = strtolower(
        pathinfo($nama_gambar, PATHINFO_EXTENSION)
    );

    if(!in_array($extension, $allowed)){

        echo "File harus jpg, jpeg, png";
        exit;

    }

    $nama_gambar = time() . "_" . $nama_gambar;

    move_uploaded_file(
        $tmp_gambar,
        "../uploads/events/" . $nama_gambar
    );

    $query = "UPDATE events SET

    nama_event='$nama_event',
    tanggal='$tanggal',
    lokasi='$lokasi',
    deskripsi='$deskripsi',
    gambar='$nama_gambar'

    WHERE id_event='$id_event'
    ";

}else{

    $query = "UPDATE events SET

    nama_event='$nama_event',
    tanggal='$tanggal',
    lokasi='$lokasi',
    deskripsi='$deskripsi'

    WHERE id_event='$id_event'
    ";

}

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../admin/events/index.php");
    exit;

}else{

    echo "Gagal update event";

}
?>