<?php

include "../../middleware/admin_only.php";
include "../../config/koneksi.php";
include "../../templates/admin_navbar.php";

$search = "";

if(isset($_GET['search'])){

    $search = $_GET['search'];

}

$query = "SELECT

reservations.*,
users.nama,
events.nama_event

FROM reservations

JOIN users
ON reservations.id_user = users.id_user

JOIN events
ON reservations.id_event = events.id_event

WHERE

users.nama LIKE '%$search%'

OR

events.nama_event LIKE '%$search%'

ORDER BY reservations.id_reservation DESC
";

$result = mysqli_query($conn, $query);


?>

<h1>Data Reservations</h1>

<form method="GET">

    <input type="text"
       name="search"
       placeholder="Cari user / event"
       value="<?php echo $search; ?>">

    <button type="submit">
        Search
    </button>

</form>

<br>

<table border="1" cellpadding="10">

<tr>

    <th>No</th>
    <th>User</th>
    <th>Event</th>
    <th>Total</th>
    <th>Status</th>
    <th>Tanggal Booking</th>
    <th>Detail</th>

</tr>

<?php
$no = 1;
if(mysqli_num_rows($result) == 0){

    if($search != ""){

        $message = "Data reservation tidak ditemukan";

    }else{

        $message = "Belum ada reservasi";

    }

    echo "
    <tr>
        <td colspan='7'>
            $message
        </td>
    </tr>
    ";

}else{

    while($reservation = mysqli_fetch_assoc($result)){
?>

<tr>

<td>
    <?php echo $no++; ?>
</td>

<td>
    <?php echo $reservation['nama']; ?>
</td>

<td>
    <?php echo $reservation['nama_event']; ?>
</td>

<td>
    Rp <?php echo number_format($reservation['total_harga']); ?>
</td>

<td>
    <?php echo $reservation['status']; ?>
</td>

<td>
    <?php echo $reservation['tanggal_booking']; ?>
</td>

<td>

<a href="detail.php?id=<?php echo $reservation['id_reservation']; ?>">
    Detail
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