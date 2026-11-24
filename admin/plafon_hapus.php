<?php
    require "cek_admin.php";

    if(isset($_POST['hapus'])){
        $id=(int)$_POST['id'];
        $hapus=mysqli_query($conn,"DELETE FROM plafon WHERE id=$id");

        if($hapus && mysqli_affected_rows($conn)>0){
            $_SESSION['pesan']="Aturan plafon berhasil dihapus";
        }else{
            $_SESSION['pesan']="Aturan plafon tidak ditemukan";
        }
    }

    header("Location: plafon.php");
    exit;
?>
