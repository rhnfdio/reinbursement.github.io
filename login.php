<?php
    session_start();
    require "connection.php";

    if(isset($_SESSION['login'])){
        header("Location: index.php");
        exit;
    }

    $error=$sukses="";

    if(isset($_GET['status'])&&$_GET['status']=="sukses"){
        $sukses="Registrasi berhasil, Silahkan Login";
    }

    $email="";
    $ingat=false;

    if(isset($_COOKIE['remember_email'])){
        $email=$_COOKIE['remember_email'];
        $ingat=true;
    }

    if(isset($_POST['login'])){
        $email=mysqli_real_escape_string($conn, trim($_POST['email']));
        $password=$_POST['password'];

        $cek=mysqli_query($conn,"SELECT * FROM users WHERE email='$email' AND aktif=1;");
        if(mysqli_num_rows($cek)==0){
            $error="Email tidak ditemukan atau akun tidak aktif";
        }else{
            $user=mysqli_fetch_assoc($cek);
            if(password_verify($password, $user['password'])){
                session_regenerate_id(true);
                $_SESSION['login']=true;
                $_SESSION['id_user']=$user['id'];
                $_SESSION['nama']=$user['nama'];
                $_SESSION['role']=$user['role'];
                $_SESSION['jabatan_id']=$user['jabatan_id'];

                if(isset($_POST['ingat'])){
                    setcookie('remember_email', $user['email'], time()+(7*24*60*60), "/");
                }else{
                    setcookie('remember_email', '', time()-3600, "/");
                }
                header("Location: index.php");
                exit;
            }else{
                $error="Password salah";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Masuk - Sistem Reimbursement</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="assets/css/auth.css" rel="stylesheet">
    </head>
    <body>
        <div class="auth-wrap">


            <main class="auth-main">
                <div class="auth-card">

                    <div class="brand">
                        <span class="brand-mark"><i class="bi bi-receipt"></i></span>
                        <span class="brand-name">Reimbursement</span>
                    </div>

                    <h1>Masuk</h1>
                    <p class="auth-sub">Gunakan email dan password akun Anda.</p>

                    <?php
                        if($sukses!=""){?>
                            <div class="alert alert-success"><?=$sukses ?></div>
                    <?php
                        }
                    ?>
                    <?php
                        if($error!=""){?>
                            <div class="alert alert-danger"><?=$error ?></div>
                    <?php
                        }
                    ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="field">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" value="<?=htmlspecialchars($email) ?>" placeholder="nama@perusahaan.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="field has-toggle">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="toggle-pw" data-target="password" aria-label="Tampilkan password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="ingat" name="ingat" <?=$ingat?'checked':''?>>
                            <label class="form-check-label" for="ingat">Ingat saya</label>
                        </div>

                        <button type="submit" name="login" class="btn-auth">Masuk</button>
                    </form>

                    <p class="auth-alt">Belum punya akun? <a href="register.php">Daftar</a></p>
                </div>
            </main>
        </div>

        <script src="assets/js/auth.js"></script>
    </body>
</html>
