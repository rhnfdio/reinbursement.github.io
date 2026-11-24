<?php
    session_start();
    require "connection.php";
    require "fungsi.php";

    if(!isset($_SESSION['login'])){
        header("Location: login.php");
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
        header("Location: logout.php");
        exit;
    }

    $pesan="";
    $error_nama="";
    $error_pw="";

    if(isset($_POST['simpan_nama'])){
        $nama=trim($_POST['nama']);
        if($nama==""){
            $error_nama="Nama tidak boleh kosong";
        }elseif(mb_strlen($nama)>100){
            $error_nama="Nama maksimal 100 karakter";
        }else{
            $nama_db=mysqli_real_escape_string($conn,$nama);
            mysqli_query($conn,"UPDATE users SET nama='$nama_db' WHERE id=$id_user");
            $_SESSION['nama']=$nama;
            $me['nama']=$nama;
            $pesan="Nama berhasil diperbarui";
        }
    }

    if(isset($_POST['ganti_password'])){
        $lama=$_POST['password_lama'];
        $baru=$_POST['password_baru'];
        $konfirmasi=$_POST['konfirmasi'];

        if(!password_verify($lama,$me['password'])){
            $error_pw="Password lama salah";
        }elseif(strlen($baru)<8){
            $error_pw="Password baru minimal 8 karakter";
        }elseif($baru!=$konfirmasi){
            $error_pw="Konfirmasi password tidak cocok";
        }elseif($baru==$lama){
            $error_pw="Password baru harus berbeda dari password lama";
        }else{
            $hash=password_hash($baru,PASSWORD_DEFAULT);
            mysqli_query($conn,"UPDATE users SET password='$hash' WHERE id=$id_user");
            $pesan="Password berhasil diubah";
        }
    }

    $nama_role=array("karyawan"=>"Karyawan","atasan"=>"Atasan","admin"=>"Admin HR/Finance");
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profil - Reimbursement</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="assets/css/admin.css" rel="stylesheet">
        <link href="assets/css/karyawan.css" rel="stylesheet">
    </head>
    <body>
        <nav class="topbar">
            <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a class="brand" href="index.php">
                    <span class="brand-mark"><i class="bi bi-receipt"></i></span>
                    Reimbursement
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke dashboard</a>
                    <a href="logout.php" class="btn btn-ghost-danger btn-sm">Keluar</a>
                </div>
            </div>
        </nav>

        <main class="container py-4">

            <div class="page-title mb-4">
                <h1>Profil Saya</h1>
                <p>Data akun dan keamanan.</p>
            </div>

            <?php if($pesan!=""){ ?>
                <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
            <?php } ?>

            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="panel mb-4">
                        <h2>Data akun</h2>
                        <div class="detail-row">
                            <div class="label">Email</div>
                            <div class="nilai"><?= htmlspecialchars($me['email']) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="label">NIK</div>
                            <div class="nilai"><?= htmlspecialchars($me['nik']) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="label">Peran</div>
                            <div class="nilai"><span class="chip chip-blue"><?= htmlspecialchars($nama_role[$me['role']]) ?></span></div>
                        </div>
                        <div class="detail-row">
                            <div class="label">Divisi</div>
                            <div class="nilai"><?= $me['nama_divisi']!="" ? htmlspecialchars($me['nama_divisi']) : "-" ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="label">Jabatan</div>
                            <div class="nilai"><?= $me['nama_jabatan']!="" ? htmlspecialchars($me['nama_jabatan']) : "-" ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="label">Atasan langsung</div>
                            <div class="nilai"><?= $me['nama_atasan']!="" ? htmlspecialchars($me['nama_atasan']) : "-" ?></div>
                        </div>
                        <p class="form-text mt-3 mb-0">Email, NIK, jabatan, dan atasan diubah oleh admin HR.</p>
                    </div>

                    <div class="panel">
                        <h2>Ubah nama</h2>
                        <?php if($error_nama!=""){ ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error_nama) ?></div>
                        <?php } ?>
                        <form action="profil.php" method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($me['nama']) ?>" maxlength="100" required>
                            </div>
                            <button type="submit" name="simpan_nama" class="btn btn-main">Simpan nama</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="panel">
                        <h2>Ganti password</h2>
                        <?php if($error_pw!=""){ ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error_pw) ?></div>
                        <?php } ?>
                        <form action="profil.php" method="POST">
                            <div class="mb-3">
                                <label for="password_lama" class="form-label">Password lama</label>
                                <input type="password" class="form-control" id="password_lama" name="password_lama" autocomplete="current-password" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_baru" class="form-label">Password baru</label>
                                <input type="password" class="form-control" id="password_baru" name="password_baru" minlength="8" autocomplete="new-password" required>
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                            <div class="mb-4">
                                <label for="konfirmasi" class="form-label">Ulangi password baru</label>
                                <input type="password" class="form-control" id="konfirmasi" name="konfirmasi" autocomplete="new-password" required>
                            </div>
                            <button type="submit" name="ganti_password" class="btn btn-main">Ganti password</button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </body>
</html>
