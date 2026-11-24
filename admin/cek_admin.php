<?php
    session_start();
    require "../connection.php";
    require "../fungsi.php";

    if(!isset($_SESSION['login'])){
        header("Location: ../login.php");
        exit;
    }
    if($_SESSION['role']!="admin"){
        echo "Akses ditolak. Halaman ini khusus admin.";
        exit;
    }

    $jml_proses=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM klaim WHERE status='menunggu_pembayaran'"))['n'];

    $pesan="";
    $pesan_error="";
    if(isset($_SESSION['pesan'])){
        $pesan=$_SESSION['pesan'];
        unset($_SESSION['pesan']);
    }
    if(isset($_SESSION['pesan_error'])){
        $pesan_error=$_SESSION['pesan_error'];
        unset($_SESSION['pesan_error']);
    }
?>
