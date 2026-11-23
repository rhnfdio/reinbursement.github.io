<?php
    session_start();
    require "../connection.php";

    if(!isset($_SESSION['login'])){
        header("Location: ../login.php");
        exit;
    }
    if($_SESSION['role']!="admin"){
        echo "Akses ditolak. Halaman ini bukan untuk role Anda.";
        exit;
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin HR/Finance</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    </head>
    <body class="bg-light">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="../index.php">Sistem Reimbursement</a>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text text-light">
                        Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> (<?= $_SESSION['role'] ?>)
                    </span>
                    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </nav>
        <div class="container py-5">
            <div class="card shadow-sm" style="padding: 20px;">
                <div class="card-body">
                    <h3 class="card-title">Dashboard Admin HR/Finance</h3>
                    <p class="text-muted mb-0">Login berhasil. daftar semua klaim, pengaturan plafon, dan master data.</p>
                </div>
            </div>
        </div>
    </body>
</html>
