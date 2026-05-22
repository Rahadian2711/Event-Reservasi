<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "event_reservation"
);

if(!$conn){
    die("Koneksi gagal");
}
?>