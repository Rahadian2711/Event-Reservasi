<?php

include "../../templates/admin_navbar.php";
include "../../middleware/admin_only.php";
include "../../config/koneksi.php";

$id = $_GET['id'];

$query = "SELECT * FROM ticket_categories
WHERE id_category='$id'";

$result = mysqli_query($conn, $query);

$category = mysqli_fetch_assoc($result);

?>

<h1>Edit Category</h1>

<form action="../../process/category_update.php" method="POST">

<input type="hidden"
       name="id_category"
       value="<?php echo $category['id_category']; ?>">

<label>Nama Kategori</label>
<br>

<input type="text"
       name="nama_kategori"
       value="<?php echo $category['nama_kategori']; ?>">

<br><br>

<label>Harga</label>
<br>

<input type="number"
       name="harga"
       value="<?php echo $category['harga']; ?>">

<br><br>

<label>Stok</label>
<br>

<input type="number"
       name="stok"
       value="<?php echo $category['stok']; ?>">

<br><br>

<button type="submit">
    Update
</button>

</form>

<?php
include "../../templates/footer.php";
?>