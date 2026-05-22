<?php

include "../middleware/admin_only.php";
include "../config/koneksi.php";
include "../templates/admin_navbar.php";

$query = "SELECT * FROM events";

$result = mysqli_query($conn, $query);

?>

<h1>Generate Seats</h1>

<form action="../process/generate_seats.php"
      method="POST">

<label>Pilih Event</label>
<br>

<select name="id_event" required>

<option value="">
    -- Pilih Event --
</option>

<?php
while($event = mysqli_fetch_assoc($result)){
?>

<option value="<?php echo $event['id_event']; ?>">

    <?php echo $event['nama_event']; ?>

</option>

<?php } ?>

</select>

<br><br>

<label>Jumlah Kursi</label>
<br>

<input type="number"
       name="jumlah_kursi"
       required>

<br><br>

<button type="submit">
    Generate Seats
</button>

</form>