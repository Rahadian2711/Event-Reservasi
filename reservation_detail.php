<?php

session_start();

if(!isset($_SESSION['id_user'])){

    header("Location: login.php");
    exit;

}

include "config/koneksi.php";
include "templates/navbar.php";

$id_reservation = $_GET['id'];
$query = "SELECT

reservations.*,
events.nama_event

FROM reservations

JOIN events
ON reservations.id_event = events.id_event

WHERE reservations.id_reservation='$id_reservation'
";

$result = mysqli_query($conn, $query);

$reservation = mysqli_fetch_assoc($result);
if($reservation['id_user'] != $_SESSION['id_user']){

    echo "Akses ditolak";
    exit;

}

?>

<h1>Detail Reservation</h1>
<a href="my_reservations.php">
    ← Kembali ke My Reservations
</a>

<hr>

<hr>

<h2>
    <?php echo $reservation['nama_event']; ?>
</h2>

<p>
    Total Harga:
    Rp <?php echo number_format($reservation['total_harga']); ?>
</p>

<p>
    Status:

    <?php

    if($reservation['status'] == 'pending'){

        echo "<span style='color:orange;'>Pending</span>";

    }elseif($reservation['status'] == 'confirmed'){

        echo "<span style='color:green;'>Confirmed</span>";

    }else{

        echo "<span style='color:red;'>Cancelled</span>";

    }

    ?>

</p>

<p>
    Tanggal Booking:
    <?php

echo date(
    'd F Y H:i',
    strtotime($reservation['tanggal_booking'])
);

?>
</p>

<hr>

<h2>Detail Tiket</h2>

<?php

$query_detail = "SELECT

reservation_details.*,
ticket_categories.nama_kategori

FROM reservation_details

JOIN ticket_categories
ON reservation_details.id_category = ticket_categories.id_category

WHERE reservation_details.id_reservation='$id_reservation'
";

$result_detail = mysqli_query($conn, $query_detail);

?>

<table border="1" cellpadding="10">

<tr>

    <th>Kategori</th>
    <th>Jumlah Tiket</th>
    <th>Harga</th>
    <th>Subtotal</th>

</tr>

<?php
while($detail = mysqli_fetch_assoc($result_detail)){
?>

<tr>

<td>
    <?php echo $detail['nama_kategori']; ?>
</td>

<td>
    <?php echo $detail['jumlah_tiket']; ?>
</td>

<td>
    Rp <?php echo number_format($detail['harga_saat_booking']); ?>
</td>

<td>
    Rp <?php echo number_format($detail['subtotal']); ?>
</td>

</tr>

<?php } ?>

</table>

<hr>

<h2>Kursi Dipilih</h2>
<?php

$query_seats = "SELECT *
FROM seats
WHERE id_reservation='$id_reservation'
";

$result_seats = mysqli_query($conn, $query_seats);

if(mysqli_num_rows($result_seats) > 0){

    while($seat = mysqli_fetch_assoc($result_seats)){

        echo "<button>";

echo $seat['nomor_kursi'];

echo "</button> ";

    }

}else{

    echo "Belum memilih kursi";

}

?>

<hr>

<h2>Payment</h2>
<?php

$query_payment = "SELECT *
FROM payments
WHERE id_reservation='$id_reservation'
";

$result_payment = mysqli_query($conn, $query_payment);

$payment = mysqli_fetch_assoc($result_payment);

if($payment){
?>
<p>
    Metode:
    <?php echo $payment['metode']; ?>
</p>

<p>
    Status Payment:
    <?php

if($payment['status'] == 'pending'){

    echo "<span style='color:orange;'>Pending</span>";

}elseif($payment['status'] == 'paid'){

    echo "<span style='color:green;'>Paid</span>";

}else{

    echo "<span style='color:red;'>Failed</span>";

}

?>
</p>

<?php

if(file_exists("uploads/payments/" . $payment['bukti_bayar'])){
?>

<img src="uploads/payments/<?php echo $payment['bukti_bayar']; ?>"
     width="300">

<?php
}else{

    echo "Bukti pembayaran tidak ditemukan";

}
?>

<?php
}else{

    echo "Belum upload payment";

}
?>