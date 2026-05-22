<?php

session_start();

include "../config/koneksi.php";

$email = $_POST['email'];
$password = $_POST['password'];

if(empty($email) || empty($password)){

    echo "Email dan password wajib diisi";
    exit;

}

$query = "SELECT * FROM users WHERE email='$email'";

$result = mysqli_query($conn, $query);

$user = mysqli_fetch_assoc($result);

if($user){

    if(password_verify($password, $user['password'])){

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        if($user['role'] == 'admin'){
          header("Location: ../admin/dashboard.php");
        }else{
          header("Location: ../dashboard.php");
        }
        exit;

    }else{
        echo "Password salah";
    }

}else{
    echo "Email tidak ditemukan";
}

?>