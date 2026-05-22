<?php

include "../../middleware/admin_only.php";
include "../../config/koneksi.php";
include "../../templates/admin_navbar.php";

$id = $_GET['id'];

$query = "SELECT

reservations.*,
users.nama,
events.nama_event

FROM reservations

JOIN users
ON reservations.id_user = users.id_user

JOIN events
ON reservations.id_event = events.id_event

WHERE reservations.id_reservation='$id'
";

$result = mysqli_query($conn, $query);

$reservation = mysqli_fetch_assoc($result);

?>

<h1>Detail Reservation</h1>

<p>
    User:
    <?php echo $reservation['nama']; ?>
</p>

<p>
    Event:
    <?php echo $reservation['nama_event']; ?>
</p>

<p>
    Total:
    Rp <?php echo number_format($reservation['total_harga']); ?>
</p>

<p>
    Status:
    <?php echo $reservation['status']; ?>
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

WHERE reservation_details.id_reservation='$id'
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

<?php

$query_payment = "SELECT *
FROM payments
WHERE id_reservation='$id'";

$result_payment = mysqli_query($conn, $query_payment);

$payment = mysqli_fetch_assoc($result_payment);

if($payment){
?>

<hr>

<h2>Payment</h2>

<p>
    Metode:
    <?php echo $payment['metode']; ?>
</p>

<p>
    Status Payment:
    <?php echo $payment['status']; ?>
</p>

<img src="../../uploads/payments/<?php echo $payment['bukti_bayar']; ?>"
     width="300">

<?php } ?>

<?php
include "../../templates/footer.php";
?>