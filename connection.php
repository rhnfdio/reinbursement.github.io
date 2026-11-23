<?php
    $hostname="localhost";
    $username="root";
    $pw="";
    $db="db_reimbursement";

    mysqli_report(MYSQLI_REPORT_OFF);

    $conn=mysqli_connect($hostname,$username,$pw,$db);
    if(!$conn){
        die("koneksi gagal".mysqli_connect_error());
    }
    mysqli_set_charset($conn,"utf8mb4");
?>
