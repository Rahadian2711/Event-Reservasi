<?php
include "templates/navbar.php";
session_start();

if(!isset($_SESSION['id_user'])){
    header("Location: login.php");
    exit;
}

include "config/koneksi.php";

$id_category = $_GET['id_category'];

$query = "SELECT
ticket_categories.*,
events.nama_event

FROM ticket_categories

JOIN events
ON ticket_categories.id_event = events.id_event

WHERE id_category='$id_category'
";

$result = mysqli_query($conn, $query);

$category = mysqli_fetch_assoc($result);

?>

<h1>Reservasi Tiket</h1>

<h2>
    <?php echo $category['nama_event']; ?>
</h2>

<p>
    Kategori:
    <?php echo $category['nama_kategori']; ?>
</p>

<p>
    Harga:
    Rp <?php echo number_format($category['harga']); ?>
</p>

<p>
    Stok:
    <?php echo $category['stok']; ?>
</p>

<form action="process/reservation_store.php"
      method="POST">

    <input type="hidden"
           name="id_category"
           value="<?php echo $category['id_category']; ?>">

    <input type="hidden"
           name="id_event"
           value="<?php echo $category['id_event']; ?>">

    <label>Jumlah Tiket</label>
    <br>

    <input type="number"
           name="jumlah_tiket"
           min="1"
           required>

    <br><br>

    <button type="submit">
        Reservasi Sekarang
    </button>

</form>