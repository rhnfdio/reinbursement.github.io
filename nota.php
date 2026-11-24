<?php
    session_start();
    require "connection.php";

    if(!isset($_SESSION['login'])){
        http_response_code(403);
        exit("Akses ditolak");
    }

    $id=isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $q=mysqli_query($conn,"SELECT k.file_nota, k.user_id, k.status, u.atasan_id
        FROM klaim k INNER JOIN users u ON k.user_id=u.id
        WHERE k.id=$id");
    if(mysqli_num_rows($q)==0){
        http_response_code(404);
        exit("Nota tidak ditemukan");
    }
    $k=mysqli_fetch_assoc($q);
    
    $boleh=false;
    if($_SESSION['role']=="admin"){
        $boleh=true;
    }elseif($k['user_id']==$_SESSION['id_user']){
        $boleh=true;
    }elseif($_SESSION['role']=="atasan" && $k['atasan_id']==$_SESSION['id_user'] && $k['status']!="draft"){
        $boleh=true;
    }
    if(!$boleh){
        http_response_code(403);
        exit("Akses ditolak");
    }

    $path=__DIR__."/uploads/nota/".basename((string)$k['file_nota']);
    if($k['file_nota']=="" || !file_exists($path)){
        http_response_code(404);
        exit("File nota tidak ditemukan");
    }

    $tipe=array("jpg"=>"image/jpeg","png"=>"image/png","pdf"=>"application/pdf");
    $ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));
    header("Content-Type: ".(isset($tipe[$ext]) ? $tipe[$ext] : "application/octet-stream"));
    header("Content-Length: ".filesize($path));
    header("X-Content-Type-Options: nosniff");
    readfile($path);
?>
