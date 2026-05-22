<?php

session_start();

if(!isset($_SESSION['id_user'])){
    header("Location: login.php");
    exit;
}

include "config/koneksi.php";

$id_reservation = $_GET['id'];

$query_reservation = "SELECT *
FROM reservations
WHERE id_reservation='$id_reservation'";

$result_reservation = mysqli_query($conn, $query_reservation);

$reservation = mysqli_fetch_assoc($result_reservation);

$query_total_ticket = "SELECT SUM(jumlah_tiket) as total_tiket

FROM reservation_details

WHERE id_reservation='$id_reservation'
";

$result_total_ticket = mysqli_query($conn, $query_total_ticket);

$total_ticket = mysqli_fetch_assoc($result_total_ticket);

$max_ticket = $total_ticket['total_tiket'];
$query_selected_seat = "SELECT COUNT(*) as total_selected

FROM seats

WHERE id_reservation='$id_reservation'
";

$result_selected_seat = mysqli_query(
    $conn,
    $query_selected_seat
);

$selected_seat = mysqli_fetch_assoc(
    $result_selected_seat
);

$total_selected = $selected_seat['total_selected'];

$sisa_kursi = $max_ticket - $total_selected;

if($sisa_kursi <= 0){

    echo "<h2>Semua kursi sudah dipilih</h2>";

    exit;

}

$id_event = $reservation['id_event'];
$search_seat = "";

if(isset($_GET['search_seat'])){

    $search_seat = $_GET['search_seat'];

}

$status_filter = "";

if(isset($_GET['status'])){

    $status_filter = $_GET['status'];

}

$query_seats = "SELECT *
FROM seats
WHERE id_event='$id_event'
";

if($search_seat != ""){

    $query_seats .= "
    AND nomor_kursi LIKE '%$search_seat%'
    ";

}

if($status_filter != ""){

    $query_seats .= "
    AND status='$status_filter'
    ";

}

$result_seats = mysqli_query($conn, $query_seats);

?>

<h1>Pilih Kursi</h1>
<form method="GET">

<input type="hidden"
       name="id"
       value="<?php echo $id_reservation; ?>">

<input type="text"
       name="search_seat"
       placeholder="Cari kursi..."
       value="<?php echo $search_seat; ?>">

<select name="status">

<option value="">
    Semua Status
</option>

<option value="available"
<?php

if($status_filter == 'available'){
    echo "selected";
}

?>>

    Available

</option>

<option value="booked"

<?php

if($status_filter == 'booked'){
    echo "selected";
}

?>>

    Booked

</option>

</select>

<button type="submit">
    Search
</button>

</form>

<hr>
<p>
    Sisa kursi yang bisa dipilih:
<?php echo $sisa_kursi; ?>
</p>

<form action="process/choose_seat_process.php"
      method="POST">

<input type="hidden"
       name="id_reservation"
       value="<?php echo $id_reservation; ?>">

<?php
while($seat = mysqli_fetch_assoc($result_seats)){
?>

<div style="margin-bottom:10px;">

<?php

if($seat['status'] == 'booked'){

    echo "<button type='button' disabled>";

    echo $seat['nomor_kursi'];

    echo " (Booked)";

    echo "</button>";

}else{
?>

<label>

<input type="checkbox"
       name="seats[]"
       value="<?php echo $seat['id_seat']; ?>">

<?php echo $seat['nomor_kursi']; ?>

</label>

<?php } ?>

</div>

<?php } ?>

<br>

<button type="submit">
    Simpan Kursi
</button>

</form>

<script>

let maxTicket = <?php echo $sisa_kursi; ?>;

let checkboxes = document.querySelectorAll(
    'input[type="checkbox"]'
);

checkboxes.forEach(function(checkbox){

    checkbox.addEventListener(
        'change',
        function(){

            let checked = document.querySelectorAll(
                'input[type="checkbox"]:checked'
            );

            if(checked.length > maxTicket){

                this.checked = false;

                alert(
                    "Jumlah kursi melebihi jumlah tiket"
                );

            }

        }
    );

});

</script>