<?php
    require "cek_admin.php";

    $id=isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $q=mysqli_query($conn,"SELECT id, kode_klaim, tanggal_diajukan FROM klaim WHERE id=$id AND status='menunggu_pembayaran'");
    if(mysqli_num_rows($q)==0){
        $_SESSION['pesan_error']="Klaim tidak ditemukan atau sudah diproses";
        header("Location: klaim.php");
        exit;
    }
    $k=mysqli_fetch_assoc($q);
    $id_admin=(int)$_SESSION['id_user'];

    if(isset($_POST['bayar'])){
        $tanggal=trim($_POST['tanggal_bayar']);
        $catatan=trim($_POST['catatan']);

        $obj=DateTime::createFromFormat("Y-m-d",$tanggal);
        $tgl_ok=($obj && $obj->format("Y-m-d")==$tanggal);

        $error="";
        if(!$tgl_ok){
            $error="Tanggal pembayaran tidak valid";
        }elseif($tanggal>date("Y-m-d")){
            $error="Tanggal pembayaran tidak boleh di masa depan";
        }elseif($k['tanggal_diajukan'] && $tanggal<substr($k['tanggal_diajukan'],0,10)){
            $error="Tanggal pembayaran tidak boleh sebelum tanggal pengajuan";
        }elseif(mb_strlen($catatan)>255){
            $error="Catatan maksimal 255 karakter";
        }

        if($error!=""){
            $_SESSION['pesan_error']=$error;
            header("Location: klaim_detail.php?id=$id");
            exit;
        }

        if($catatan==""){
            $catatan_sql="NULL";
        }else{
            $catatan_sql="'".mysqli_real_escape_string($conn,$catatan)."'";
        }

        $update=mysqli_query($conn,"UPDATE klaim SET status='dibayar', tanggal_bayar='$tanggal', catatan_bayar=$catatan_sql
            WHERE id=$id AND status='menunggu_pembayaran'");
        if($update && mysqli_affected_rows($conn)>0){
            $isi_log=($catatan=="") ? "Pembayaran selesai" : "Pembayaran selesai. ".$catatan;
            catat_log($conn,$id,$id_admin,"menunggu_pembayaran","dibayar",$isi_log);
            $_SESSION['pesan']="Klaim #".$k['kode_klaim']." ditandai sudah dibayar";
        }else{
            $_SESSION['pesan_error']="Klaim sudah diproses sebelumnya";
        }
        header("Location: klaim.php?status=menunggu_pembayaran");
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
        $update=mysqli_query($conn,"UPDATE klaim SET status='ditolak', alasan_penolakan='$alasan_db'
            WHERE id=$id AND status='menunggu_pembayaran'");
        if($update && mysqli_affected_rows($conn)>0){
            catat_log($conn,$id,$id_admin,"menunggu_pembayaran","ditolak",$alasan);
            $_SESSION['pesan']="Klaim #".$k['kode_klaim']." ditolak";
        }else{
            $_SESSION['pesan_error']="Klaim sudah diproses sebelumnya";
        }
        header("Location: klaim.php?status=menunggu_pembayaran");
        exit;
    }

    header("Location: klaim.php");
    exit;
?>
