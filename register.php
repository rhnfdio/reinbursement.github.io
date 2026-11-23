<?php
    session_start();
    require "connection.php";

    if(isset($_SESSION['login'])){
        header("Location: index.php");
        exit;
    }

    $error="";
    $nik=$nama=$email=$divisi_id=$jabatan_id=$atasan_id="";

    if(isset($_POST['register'])){
        $nik=trim($_POST['nik']);
        $nama=trim($_POST['nama']);
        $email=trim($_POST['email']);
        $divisi_id=$_POST['divisi_id'];
        $jabatan_id=$_POST['jabatan_id'];
        $atasan_id=$_POST['atasan_id'];
        $password=$_POST['password'];
        $konfirmasi=$_POST['konfirmasi'];

        if($nik=="" || $nama=="" || $divisi_id=="" || $jabatan_id==""){
            $error="Semua data wajib diisi";
        }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $error="Format email tidak valid";
        }elseif(strlen($password)<8){
            $error="Password minimal 8 karakter";
        }elseif($password!=$konfirmasi){
            $error="Konfirmasi password tidak cocok";
        }else{
            $nik_db=mysqli_real_escape_string($conn, $nik);
            $nama_db=mysqli_real_escape_string($conn, $nama);
            $email_db=mysqli_real_escape_string($conn, $email);
            $divisi_db=mysqli_real_escape_string($conn, $divisi_id);
            $jabatan_db=mysqli_real_escape_string($conn, $jabatan_id);
            $atasan_db=mysqli_real_escape_string($conn, $atasan_id);

            $cek=mysqli_query($conn,"SELECT * FROM users WHERE email='$email_db' OR nik='$nik_db'");
            if(mysqli_num_rows($cek)>0){
                $error="Email atau NIK sudah digunakan";
            }else{
                $password_hash=password_hash($password, PASSWORD_DEFAULT);

                if($atasan_id==""){
                    $atasan_sql="NULL";
                }else{
                    $atasan_sql="'$atasan_db'";
                }

                $simpan=mysqli_query($conn, "INSERT INTO users (nik,nama,email,password,role,divisi_id,jabatan_id,atasan_id) VALUES ('$nik_db','$nama_db','$email_db','$password_hash','karyawan','$divisi_db','$jabatan_db',$atasan_sql)");
                if($simpan){
                    header("Location: login.php?status=sukses");
                    exit;
                }else{
                    $error="Registrasi Gagal";
                }
            }
        }
    }

    $sql_divisi=mysqli_query($conn,"SELECT * FROM divisi ORDER BY nama_divisi");
    $sql_jabatan=mysqli_query($conn,"SELECT * FROM jabatan ORDER BY id");
    $sql_atasan=mysqli_query($conn,"SELECT id, nama FROM users WHERE role='atasan' AND aktif=1 ORDER BY nama");
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Daftar - Sistem Reimbursement</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="assets/css/auth.css" rel="stylesheet">
    </head>
    <body>
        <div class="auth-wrap">


            <main class="auth-main">
                <div class="auth-card wide">

                    <div class="brand">
                        <span class="brand-mark"><i class="bi bi-receipt"></i></span>
                        <span class="brand-name">Reimbursement</span>
                    </div>

                    <h1>Buat akun</h1>
                    <p class="auth-sub">Lengkapi data karyawan di bawah ini.</p>

                    <?php
                        if($error!=""){?>
                            <div class="alert alert-danger"><?=$error ?></div>
                    <?php
                        }
                    ?>

                    <form action="register.php" method="POST">
                        <div class="row g-3">

                            <div class="col-sm-5">
                                <label for="nik" class="form-label">NIK</label>
                                <div class="field">
                                    <i class="bi bi-person-badge"></i>
                                    <input type="text" id="nik" name="nik" class="form-control" value="<?=htmlspecialchars($nik) ?>" required maxlength="20">
                                </div>
                            </div>
                            <div class="col-sm-7">
                                <label for="nama" class="form-label">Nama lengkap</label>
                                <div class="field">
                                    <i class="bi bi-person"></i>
                                    <input type="text" id="nama" name="nama" class="form-control" value="<?=htmlspecialchars($nama) ?>" required maxlength="100">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <div class="field">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" id="email" name="email" class="form-control" value="<?=htmlspecialchars($email) ?>" placeholder="nama@perusahaan.com" required maxlength="100">
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label for="divisi_id" class="form-label">Divisi</label>
                                <div class="field">
                                    <i class="bi bi-diagram-3"></i>
                                    <select id="divisi_id" name="divisi_id" class="form-select" required>
                                        <option value="">Pilih divisi</option>
                                        <?php while($d=mysqli_fetch_assoc($sql_divisi)){ ?>
                                            <option value="<?=$d['id'] ?>" <?=$divisi_id==$d['id']?'selected':'' ?>><?=htmlspecialchars($d['nama_divisi']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="jabatan_id" class="form-label">Jabatan</label>
                                <div class="field">
                                    <i class="bi bi-briefcase"></i>
                                    <select id="jabatan_id" name="jabatan_id" class="form-select" required>
                                        <option value="">Pilih jabatan</option>
                                        <?php while($j=mysqli_fetch_assoc($sql_jabatan)){ ?>
                                            <option value="<?=$j['id'] ?>" <?=$jabatan_id==$j['id']?'selected':'' ?>><?=htmlspecialchars($j['nama_jabatan']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="atasan_id" class="form-label">Atasan langsung <span class="opsional">(opsional)</span></label>
                                <div class="field">
                                    <i class="bi bi-people"></i>
                                    <select id="atasan_id" name="atasan_id" class="form-select">
                                        <option value="">Belum ditentukan</option>
                                        <?php while($a=mysqli_fetch_assoc($sql_atasan)){ ?>
                                            <option value="<?=$a['id'] ?>" <?=$atasan_id==$a['id']?'selected':'' ?>><?=htmlspecialchars($a['nama']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="field has-toggle">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required minlength="8">
                                    <button type="button" class="toggle-pw" data-target="password" aria-label="Tampilkan password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="konfirmasi" class="form-label">Ulangi password</label>
                                <div class="field has-toggle">
                                    <i class="bi bi-lock-fill"></i>
                                    <input type="password" id="konfirmasi" name="konfirmasi" class="form-control" required>
                                    <button type="button" class="toggle-pw" data-target="konfirmasi" aria-label="Tampilkan password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" name="register" class="btn-auth">Daftar</button>
                            </div>
                        </div>
                    </form>

                    <p class="auth-alt">Sudah punya akun? <a href="login.php">Masuk</a></p>
                </div>
            </main>
        </div>

        <script src="assets/js/auth.js"></script>
    </body>
</html>
