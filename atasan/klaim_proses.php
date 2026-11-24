<?php
    require "cek_atasan.php";

    $id=isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $q=mysqli_query($conn,"SELECT k.id, k.kode_klaim
        FROM klaim k INNER JOIN users u ON k.user_id=u.id
        WHERE k.id=$id AND u.atasan_id=$id_user AND k.status='diajukan'");
    if(mysqli_num_rows($q)==0){
        $_SESSION['pesan_error']="Klaim tidak ditemukan atau sudah diproses";
        header("Location: antrean.php");
        exit;
    }
    $k=mysqli_fetch_assoc($q);

    if(isset($_POST['setujui'])){
        $catatan=trim($_POST['catatan']);
        if(mb_strlen($catatan)>255){
            $_SESSION['pesan_error']="Catatan maksimal 255 karakter";
            header("Location: klaim_detail.php?id=$id");
            exit;
        }
        if($catatan==""){
            $catatan="Disetujui atasan";
        }

        $update=mysqli_query($conn,"UPDATE klaim SET status='menunggu_pembayaran' WHERE id=$id AND status='diajukan'");
        if($update && mysqli_affected_rows($conn)>0){
            catat_log($conn,$id,$id_user,"diajukan","menunggu_pembayaran",$catatan);
            $_SESSION['pesan']="Klaim #".$k['kode_klaim']." disetujui dan diteruskan ke HR/Finance";
        }else{
            $_SESSION['pesan_error']="Klaim sudah diproses sebelumnya";
        }
        header("Location: antrean.php");
        exit;
    }

    if(isset($_POST['tolak'])){
        $alasan=trim($_POST['alasan']);
        if($alasan==""){
            $_SESSION['pesan_error']="Alasan penolakan wajib diisi";
            header("Location: klaim_detail.php?id=$id");
            exit;
        }
        if(mb_strlen($alasan)>255){
            $_SESSION['pesan_error']="Alasan maksimal 255 karakter";
            header("Location: klaim_detail.php?id=$id");
            exit;
        }

        $alasan_db=mysqli_real_escape_string($conn,$alasan);
        $update=mysqli_query($conn,"UPDATE klaim SET status='ditolak', alasan_penolakan='$alasan_db' WHERE id=$id AND status='diajukan'");
        if($update && mysqli_affected_rows($conn)>0){
            catat_log($conn,$id,$id_user,"diajukan","ditolak",$alasan);
            $_SESSION['pesan']="Klaim #".$k['kode_klaim']." ditolak";
        }else{
            $_SESSION['pesan_error']="Klaim sudah diproses sebelumnya";
        }
        header("Location: antrean.php");
        exit;
    }

    header("Location: antrean.php");
    exit;
?>
