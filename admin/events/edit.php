<?php

include "../../middleware/admin_only.php";
include "../../config/koneksi.php";
include "../../templates/admin_navbar.php";

$id = $_GET['id'];

$query = "SELECT * FROM events
WHERE id_event='$id'";

$result = mysqli_query($conn, $query);

$event = mysqli_fetch_assoc($result);

?>

<h1>Edit Event</h1>

<form action="../../process/event_update.php"
      method="POST"
      enctype="multipart/form-data">

    <input type="hidden"
           name="id_event"
           value="<?php echo $event['id_event']; ?>">

    <label>Nama Event</label>
    <br>
    <input type="text"
           name="nama_event"
           value="<?php echo $event['nama_event']; ?>">
    <br><br>

    <label>Tanggal</label>
    <br>
    <input type="datetime-local"
           name="tanggal"
           value="<?php echo date('Y-m-d\TH:i', strtotime($event['tanggal'])); ?>">
    <br><br>

    <label>Lokasi</label>
    <br>
    <input type="text"
           name="lokasi"
           value="<?php echo $event['lokasi']; ?>">
    <br><br>

    <label>Deskripsi</label>
    <br>
    <textarea name="deskripsi"><?php echo $event['deskripsi']; ?></textarea>
    <br><br>

       <p>Gambar Saat Ini</p>

<img src="../../uploads/events/<?php echo $event['gambar']; ?>"
     width="200">

<br><br>

<label>Ganti Gambar</label>
<br>

<input type="file"
       name="gambar">

<br><br>

    <button type="submit">
        Update Event
    </button>

</form>

<?php
include "../../templates/footer.php";
?>