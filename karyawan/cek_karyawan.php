<?php
    session_start();
    require "../connection.php";
    require "../fungsi.php";
    date_default_timezone_set("Asia/Jakarta");

    if(!isset($_SESSION['login'])){
        header("Location: ../login.php");
        exit;
    }
    if($_SESSION['role']!="karyawan"){
        echo "Akses ditolak. Halaman ini khusus karyawan.";
        exit;
    }
    $id_user=(int)$_SESSION['id_user'];
    $me=mysqli_fetch_assoc(mysqli_query($conn,"SELECT u.*, j.nama_jabatan, d.nama_divisi, a.nama AS nama_atasan
        FROM users u
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        LEFT JOIN users a ON u.atasan_id=a.id
        WHERE u.id=$id_user"));
    if(!$me || $me['aktif']!=1){
        header("Location: ../logout.php");
        exit;
    }
    $jabatan_id=(int)$me['jabatan_id'];

    $kata=explode(" ",trim($me['nama']));
    $inisial=strtoupper(substr($kata[0],0,1));
    if(count($kata)>1){
        $inisial.=strtoupper(substr($kata[1],0,1));
    }

    $batas_hari=30;
    $hari_ini=date("Y-m-d");
    $tanggal_min=date("Y-m-d",strtotime("-$batas_hari days"));

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
