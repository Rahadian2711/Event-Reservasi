<?php

include "../../middleware/admin_only.php";
include "../../templates/admin_navbar.php";
?>

<h1>Tambah Event</h1>

<form action="../../process/event_store.php"
      method="POST"
      enctype="multipart/form-data">

    <label>Nama Event</label>
    <br>
    <input type="text" name="nama_event" required>
    <br><br>

    <label>Tanggal</label>
    <br>
    <input type="datetime-local" name="tanggal" required>
    <br><br>

    <label>Lokasi</label>
    <br>
    <input type="text" name="lokasi" required>
    <br><br>

    <label>Deskripsi</label>
    <br>
    <textarea name="deskripsi"></textarea>
    <br><br>

    <label>Gambar Event</label>
<br>

<input type="file"
       name="gambar"
       required>

<br><br>

    <button type="submit">
        Simpan Event
    </button>

</form>

<?php
include "../../templates/footer.php";
?>