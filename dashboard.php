

<?php
include "templates/navbar.php";


if(!isset($_SESSION['id_user'])){
    header("Location: login.php");
    exit;
}

?>

<h1>Dashboard User</h1>

<p>
    Selamat datang:
    <?php echo $_SESSION['nama']; ?>
</p>

<hr>

<ul>

    <li>
        <a href="index.php">
            Lihat Event
        </a>
    </li>

    <li>
        <a href="my_reservations.php">
            My Reservations
        </a>
    </li>

    <li>
        <a href="logout.php">
            Logout
        </a>
    </li>

</ul>