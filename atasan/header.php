<?php

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($judul) ?> - Reimbursement</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="../assets/css/admin.css" rel="stylesheet">
        <link href="../assets/css/karyawan.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg topbar">
            <div class="container">
                <a class="brand" href="antrean.php">
                    <span class="brand-mark"><i class="bi bi-receipt"></i></span>
                    Reimbursement
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuAtasan" aria-controls="menuAtasan" aria-expanded="false" aria-label="Buka menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuAtasan">
                    <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link <?= $halaman=="antrean"?"active":"" ?>" href="antrean.php">
                                Antrean Persetujuan
                                <?php if($jml_menunggu>0){ ?>
                                    <span class="chip chip-amber ms-1"><?= $jml_menunggu ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $halaman=="riwayat"?"active":"" ?>" href="riwayat.php">Riwayat Klaim</a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-3">
                        <span class="small">
                            <a href="../profil.php" class="text-decoration-none fw-bold" style="color: inherit;" title="Profil dan ganti password"><?= htmlspecialchars($_SESSION['nama']) ?></a>
                            <span class="chip chip-blue ms-1"><?= htmlspecialchars($_SESSION['role']) ?></span>
                        </span>
                        <a href="../logout.php" class="btn btn-ghost-danger btn-sm">Keluar</a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="container py-4">
            <?php if($pesan!=""){ ?>
                <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
            <?php } ?>
            <?php if($pesan_error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($pesan_error) ?></div>
            <?php } ?>
