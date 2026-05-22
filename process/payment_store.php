<?php

include "../config/koneksi.php";

$id_reservation = $_POST['id_reservation'];
$metode = $_POST['metode'];

$nama_asli = $_FILES['bukti_bayar']['name'];

$nama_file = time() . "_" . $nama_asli;
$tmp_file = $_FILES['bukti_bayar']['tmp_name'];

$allowed_extensions = ['jpg', 'jpeg', 'png'];

$file_extension = strtolower(
    pathinfo($nama_file, PATHINFO_EXTENSION)
);

if(!in_array($file_extension, $allowed_extensions)){

    echo "File harus jpg, jpeg, atau png";
    exit;

}

$upload_path = "../uploads/payments/" . $nama_file;

move_uploaded_file($tmp_file, $upload_path);

$query = "INSERT INTO payments
(
    id_reservation,
    metode,
    bukti_bayar,
    status
)

VALUES
(
    '$id_reservation',
    '$metode',
    '$nama_file',
    'pending'
)";

$result = mysqli_query($conn, $query);

if($result){

    header("Location: ../my_reservations.php");
    exit;

}else{

    echo "Upload payment gagal";

}
?>