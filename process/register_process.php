<?php

include "../config/koneksi.php";

$nama = $_POST['nama'];
$email = $_POST['email'];
$password = $_POST['password'];

$check_email = "SELECT *
FROM users
WHERE email='$email'";

$result_check = mysqli_query($conn, $check_email);

if(mysqli_num_rows($result_check) > 0){

    echo "Email sudah digunakan";
    exit;

}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users (nama, email, password)
VALUES ('$nama', '$email', '$password_hash')";

$result = mysqli_query($conn, $query);

if($result){
    echo "Register berhasil";
}else{
    echo "Register gagal";
}

?>