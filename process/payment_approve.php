<?php

include "../config/koneksi.php";

$id_payment = $_GET['id'];

$query_payment = "SELECT *
FROM payments
WHERE id_payment='$id_payment'";

$result_payment = mysqli_query($conn, $query_payment);

$payment = mysqli_fetch_assoc($result_payment);

$id_reservation = $payment['id_reservation'];

$query_update_payment = "UPDATE payments
SET status='paid'
WHERE id_payment='$id_payment'";

mysqli_query($conn, $query_update_payment);

$query_update_reservation = "UPDATE reservations
SET status='confirmed'
WHERE id_reservation='$id_reservation'";

mysqli_query($conn, $query_update_reservation);

header("Location: ../admin/payments/index.php");

exit;

?>