<?php

include "../config/koneksi.php";

$id_reservation = $_POST['id_reservation'];

$seats = $_POST['seats'];

$query_total_ticket = "SELECT SUM(jumlah_tiket) as total_tiket

FROM reservation_details

WHERE id_reservation='$id_reservation'
";

$result_total_ticket = mysqli_query(
    $conn,
    $query_total_ticket
);

$total_ticket = mysqli_fetch_assoc(
    $result_total_ticket
);

$max_ticket = $total_ticket['total_tiket'];

$query_selected = "SELECT COUNT(*) as total_selected

FROM seats

WHERE id_reservation='$id_reservation'
";

$result_selected = mysqli_query(
    $conn,
    $query_selected
);

$selected = mysqli_fetch_assoc(
    $result_selected
);

$total_selected = $selected['total_selected'];

$total_request = count($seats);

if(($total_selected + $total_request) > $max_ticket){

    echo "Jumlah kursi melebihi tiket";

    exit;

}

foreach($seats as $id_seat){
$check = "SELECT *
FROM seats
WHERE id_seat='$id_seat'
AND status='available'
";

$result_check = mysqli_query($conn, $check);

if(mysqli_num_rows($result_check) == 0){

    echo "Seat sudah dibooking user lain";
    exit;

}
    $query = "UPDATE seats SET

    status='booked',
    id_reservation='$id_reservation'

    WHERE id_seat='$id_seat'
    ";

    mysqli_query($conn, $query);

}

header("Location: ../my_reservations.php");

exit;

?>