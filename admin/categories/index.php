<?php

include "../../middleware/admin_only.php";
include "../../templates/admin_navbar.php";
include "../../config/koneksi.php";

$query = "SELECT
ticket_categories.*,
events.nama_event

FROM ticket_categories

JOIN events
ON ticket_categories.id_event = events.id_event

ORDER BY id_category DESC
";

$result = mysqli_query($conn, $query);

?>

<h1>Ticket Categories</h1>

<a href="create.php">
    Tambah Kategori Tiket
</a>

<br><br>

<table border="1" cellpadding="10">

<tr>
    <th>No</th>
    <th>Event</th>
    <th>Kategori</th>
    <th>Harga</th>
    <th>Stok</th>
    <th>Aksi</th>
</tr>

<?php
$no = 1;
if(mysqli_num_rows($result) == 0){

    echo "
    <tr>
        <td colspan='6'>
            Belum ada kategori tiket
        </td>
    </tr>
    ";

}else{

    while($category = mysqli_fetch_assoc($result)){
?>

<tr>

    <td>
        <?php echo $no++; ?>
    </td>

    <td>
        <?php echo $category['nama_event']; ?>
    </td>

    <td>
        <?php echo $category['nama_kategori']; ?>
    </td>

    <td>
        <?php echo $category['harga']; ?>
    </td>

    <td>
        <?php echo $category['stok']; ?>
    </td>

    <td>

        <a href="edit.php?id=<?php echo $category['id_category']; ?>">
            Edit
        </a>

        <a href="../../process/event_delete.php?id=<?php echo $event['id_event']; ?>"
               onclick="return confirm('Yakin hapus event ini?')">

                Delete

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