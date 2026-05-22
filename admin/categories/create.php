<?php

include "../../templates/admin_navbar.php";
include "../../middleware/admin_only.php";
include "../../config/koneksi.php";

$query = "SELECT * FROM events";

$result = mysqli_query($conn, $query);

?>

<h1>Tambah Kategori Tiket</h1>

<form action="../../process/category_store.php" method="POST">

    <label>Event</label>
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

    <label>Nama Kategori</label>
    <br>
    <input type="text"
           name="nama_kategori"
           required>

    <br><br>

    <label>Harga</label>
    <br>
    <input type="number"
           name="harga"
           required>

    <br><br>

    <label>Stok</label>
    <br>
    <input type="number"
           name="stok"
           required>

    <br><br>

    <button type="submit">
        Simpan
    </button>

</form>

<?php
include "../../templates/footer.php";
?>