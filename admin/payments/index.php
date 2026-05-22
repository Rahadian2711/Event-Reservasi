<?php

include "../../middleware/admin_only.php";
include "../../templates/admin_navbar.php";
include "../../config/koneksi.php";

$query = "SELECT

payments.*,
users.nama,
events.nama_event,
reservations.total_harga

FROM payments

JOIN reservations
ON payments.id_reservation = reservations.id_reservation

JOIN users
ON reservations.id_user = users.id_user

JOIN events
ON reservations.id_event = events.id_event

ORDER BY payments.id_payment DESC
";

$result = mysqli_query($conn, $query);

?>

<h1>Data Payments</h1>

<table border="1" cellpadding="10">

<tr>
    <th>User</th>
    <th>Event</th>
    <th>Total</th>
    <th>Metode</th>
    <th>Bukti</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>

<?php

if(mysqli_num_rows($result) == 0){

    echo "
    <tr>
        <td colspan='7'>
            Belum ada payment
        </td>
    </tr>
    ";

}else{

    while($payment = mysqli_fetch_assoc($result)){
?>

<tr>

<td>
    <?php echo $payment['nama']; ?>
</td>

<td>
    <?php echo $payment['nama_event']; ?>
</td>

<td>
    Rp <?php echo number_format($payment['total_harga']); ?>
</td>

<td>
    <?php echo $payment['metode']; ?>
</td>

<td>

<img src="../../uploads/payments/<?php echo $payment['bukti_bayar']; ?>"
     width="150">

</td>

<td>
    <?php echo $payment['status']; ?>
</td>

<td>

<a href="../../process/payment_approve.php?id=<?php echo $payment['id_payment']; ?>">
    Approve
</a>

</td>

</tr>

<?php
    }
}
?>

</table>

<?php
include "../../templates/footer.php";
?>