<?php
    session_start();

    if(!isset($_SESSION['login'])){
        header("Location: login.php");
        exit;
    }

    if($_SESSION['role']=="admin"){
        header("Location: admin/dashboard.php");
    }elseif($_SESSION['role']=="atasan"){
        header("Location: atasan/antrean.php");
    }else{
        header("Location: karyawan/dashboard.php");
    }
    exit;
?>
