<?php
    require "cek_karyawan.php";

    if(isset($_POST['ajukan'])){
        $id=(int)$_POST['id'];
        $q=mysqli_query($conn,"SELECT * FROM klaim WHERE id=$id AND user_id=$id_user AND status='draft'");
        if(mysqli_num_rows($q)==0){
            $_SESSION['pesan_error']="Klaim tidak ditemukan atau sudah diproses";
            header("Location: dashboard.php");
            exit;
        }
        $k=mysqli_fetch_assoc($q);
        $s=sisa_plafon($conn,$id_user,$jabatan_id,(int)$k['kategori_id'],substr($k['tanggal_transaksi'],0,7),$id);

        if($me['atasan_id']==""){
            $_SESSION['pesan_error']="Atasan langsung Anda belum ditentukan. Hubungi admin HR.";
        }elseif($k['tanggal_transaksi']<$tanggal_min){
            $_SESSION['pesan_error']="Draft ini sudah melewati batas pengajuan $batas_hari hari sejak tanggal transaksi.";
        }elseif($s===null){
            $_SESSION['pesan_error']="Tidak ada aturan plafon untuk kategori ini.";
        }elseif((int)$k['nominal']>$s['sisa']){
            $_SESSION['pesan_error']="Nominal melebihi sisa plafon pada bulan transaksi (sisa ".rupiah(max(0,$s['sisa'])).").";
        }else{
            $sekarang=date("Y-m-d H:i:s");
            mysqli_query($conn,"UPDATE klaim SET status='diajukan', tanggal_diajukan='$sekarang', alasan_penolakan=NULL WHERE id=$id");
            catat_log($conn,$id,$id_user,"draft","diajukan","Klaim diajukan");
            $_SESSION['pesan']="Klaim berhasil diajukan dan menunggu persetujuan atasan";
        }
        header("Location: klaim_detail.php?id=$id");
        exit;
    }
    if(isset($_POST['hapus'])){
        $id=(int)$_POST['id'];
        $q=mysqli_query($conn,"SELECT file_nota FROM klaim WHERE id=$id AND user_id=$id_user AND status='draft'");
        if(mysqli_num_rows($q)>0){
            $k=mysqli_fetch_assoc($q);
            if($k['file_nota']!="" && file_exists("../uploads/nota/".$k['file_nota'])){
                unlink("../uploads/nota/".$k['file_nota']);
            }
            mysqli_query($conn,"DELETE FROM klaim WHERE id=$id");
            $_SESSION['pesan']="Draft klaim berhasil dihapus";
        }else{
            $_SESSION['pesan_error']="Hanya draft milik Anda yang bisa dihapus";
        }
    }

    header("Location: dashboard.php");
    exit;
?>
