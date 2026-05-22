<?php

include "../../middleware/admin_only.php";
include "../../templates/admin_navbar.php";
include "../../config/koneksi.php";

$search = "";

if(isset($_GET['search'])){

    $search = $_GET['search'];

}

$query = "SELECT *
FROM events

WHERE nama_event LIKE '%$search%'

ORDER BY id_event DESC";

$result = mysqli_query($conn, $query);

?>


<h1>Data Event</h1>
<form method="GET">

    <input type="text"
           name="search"
           placeholder="Cari event..."
           value="<?php echo $search; ?>">

    <button type="submit">
        Search
    </button>

</form>


<br>
<a href="create.php">
    Tambah Event
</a>
<br>

<table border="1" cellpadding="10">

    <tr>
    <th>No</th>
    <th>Gambar</th>
    <th>Nama Event</th>
    <th>Tanggal</th>
    <th>Lokasi</th>
    <th>Aksi</th>
</tr>

    <?php

$no = 1;

if(mysqli_num_rows($result) == 0){

?>

<tr>

    <td colspan="5">
        Belum ada event
    </td>

</tr>

<?php
$no = 1;
}else{

    while($event = mysqli_fetch_assoc($result)){

?>

    <tr>

        <td>
            <?php echo $no++; ?>
        </td>

        <td>

<img src="../../uploads/events/<?php echo $event['gambar']; ?>"
     width="120">

</td>

        <td>
            <?php echo $event['nama_event']; ?>
        </td>

        <td>
            <?php echo $event['tanggal']; ?>
        </td>

        <td>
            <?php echo $event['lokasi']; ?>
        </td>

        <td>

            <a href="edit.php?id=<?php echo $event['id_event']; ?>">
                Edit
            </a>

            |

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