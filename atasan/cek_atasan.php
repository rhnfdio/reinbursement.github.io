<?php
    session_start();
    require "../connection.php";
    require "../fungsi.php";

    if(!isset($_SESSION['login'])){
        header("Location: ../login.php");
        exit;
    }
    if($_SESSION['role']!="atasan"){
        echo "Akses ditolak. Halaman ini khusus atasan.";
        exit;
    }

    $id_user=(int)$_SESSION['id_user'];
    $me=mysqli_fetch_assoc(mysqli_query($conn,"SELECT u.*, j.nama_jabatan, d.nama_divisi
        FROM users u
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        WHERE u.id=$id_user"));
    if(!$me || $me['aktif']!=1){
        header("Location: ../logout.php");
        exit;
    }

    $jml_menunggu=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n
        FROM klaim k INNER JOIN users u ON k.user_id=u.id
        WHERE u.atasan_id=$id_user AND k.status='diajukan'"))['n'];

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
