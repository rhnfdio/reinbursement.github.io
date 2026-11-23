<?php
    require "connection.php";

    if($_SERVER['REMOTE_ADDR']!="127.0.0.1" && $_SERVER['REMOTE_ADDR']!="::1"){
        die("Hanya bisa dijalankan dari localhost.");
    }

    $akun=array(
        array("ADM-001","Admin HR","admin@perusahaan.test","admin12345","admin"),
        array("MGR-001","Manajer IT","atasan@perusahaan.test","atasan12345","atasan")
    );

    header("Content-Type: text/plain; charset=utf-8");

    foreach($akun as $a){
        $cek=mysqli_query($conn,"SELECT id FROM users WHERE email='$a[2]'");
        if(mysqli_num_rows($cek)>0){
            echo "SKIP  $a[2] (sudah ada)\n";
        }else{
            $hash=password_hash($a[3], PASSWORD_DEFAULT);
            $simpan=mysqli_query($conn,"INSERT INTO users (nik,nama,email,password,role) VALUES ('$a[0]','$a[1]','$a[2]','$hash','$a[4]')");
            if($simpan){
                echo "OK    $a[2] | password: $a[3] | role: $a[4]\n";
            }else{
                echo "GAGAL $a[2] : ".mysqli_error($conn)."\n";
            }
        }
    }

    echo "\nSelesai. Hapus file ini sekarang.\n";
?>
