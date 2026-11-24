<?php
    require "cek_admin.php";

    $id_admin=(int)$_SESSION['id_user'];

    $error="";
    $id=0;
    if(isset($_POST['id'])){
        $id=(int)$_POST['id'];
    }elseif(isset($_GET['id'])){
        $id=(int)$_GET['id'];
    }

    $nik="";
    $nama="";
    $email="";
    $role="karyawan";
    $divisi_id="";
    $jabatan_id="";
    $atasan_id="";
    $aktif=1;
    $role_lama="";

    if($id>0){
        $q=mysqli_query($conn,"SELECT * FROM users WHERE id=$id");
        if(mysqli_num_rows($q)==0){
            $_SESSION['pesan_error']="Pengguna tidak ditemukan";
            header("Location: users.php");
            exit;
        }
        $lama=mysqli_fetch_assoc($q);
        $nik=$lama['nik'];
        $nama=$lama['nama'];
        $email=$lama['email'];
        $role=$lama['role'];
        $role_lama=$lama['role'];
        $divisi_id=(string)$lama['divisi_id'];
        $jabatan_id=(string)$lama['jabatan_id'];
        $atasan_id=(string)$lama['atasan_id'];
        $aktif=(int)$lama['aktif'];
    }
    $diri_sendiri=($id>0 && $id==$id_admin);

    $daftar_divisi=array();
    $q=mysqli_query($conn,"SELECT id, nama_divisi FROM divisi ORDER BY nama_divisi");
    while($r=mysqli_fetch_assoc($q)){ $daftar_divisi[(int)$r['id']]=$r['nama_divisi']; }

    $daftar_jabatan=array();
    $q=mysqli_query($conn,"SELECT id, nama_jabatan FROM jabatan ORDER BY id");
    while($r=mysqli_fetch_assoc($q)){ $daftar_jabatan[(int)$r['id']]=$r['nama_jabatan']; }

    $daftar_atasan=array();
    $q=mysqli_query($conn,"SELECT id, nama FROM users WHERE role='atasan' AND aktif=1 AND id!=$id ORDER BY nama");
    while($r=mysqli_fetch_assoc($q)){ $daftar_atasan[(int)$r['id']]=$r['nama']; }

    $role_ok=array("karyawan"=>"Karyawan","atasan"=>"Atasan","admin"=>"Admin HR/Finance");

    if(isset($_POST['simpan'])){
        $nik=trim($_POST['nik']);
        $nama=trim($_POST['nama']);
        $email=trim($_POST['email']);
        $role=isset($_POST['role']) ? $_POST['role'] : $role;
        $divisi_id=$_POST['divisi_id'];
        $jabatan_id=$_POST['jabatan_id'];
        $atasan_id=$_POST['atasan_id'];
        $aktif=isset($_POST['aktif']) ? 1 : 0;
        $password=$_POST['password'];

        if($diri_sendiri){
            $role="admin";
            $aktif=1;
        }

        if($role=="admin"){
            $atasan_id="";
        }

        if($nik=="" || $nama==""){
            $error="NIK dan nama wajib diisi";
        }elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error="Format email tidak valid";
        }elseif(!isset($role_ok[$role])){
            $error="Pilih peran yang valid";
        }elseif($id==0 && strlen($password)<8){
            $error="Password minimal 8 karakter";
        }elseif($id>0 && $password!="" && strlen($password)<8){
            $error="Password baru minimal 8 karakter";
        }elseif($role=="karyawan" && ($divisi_id=="" || $jabatan_id=="")){
            $error="Karyawan wajib memiliki divisi dan jabatan (jabatan menentukan plafon)";
        }elseif($divisi_id!="" && !isset($daftar_divisi[(int)$divisi_id])){
            $error="Divisi tidak valid";
        }elseif($jabatan_id!="" && !isset($daftar_jabatan[(int)$jabatan_id])){
            $error="Jabatan tidak valid";
        }elseif($atasan_id!="" && !isset($daftar_atasan[(int)$atasan_id])){
            $error="Atasan harus pengguna berperan atasan yang aktif";
        }

        if($error==""){
            $nik_db=mysqli_real_escape_string($conn,$nik);
            $email_db=mysqli_real_escape_string($conn,$email);
            $cek=mysqli_query($conn,"SELECT nik, email FROM users WHERE (nik='$nik_db' OR email='$email_db') AND id!=$id");
            while($r=mysqli_fetch_assoc($cek)){
                if(strcasecmp($r['nik'],$nik)==0){
                    $error="NIK sudah dipakai pengguna lain";
                }elseif(strcasecmp($r['email'],$email)==0){
                    $error="Email sudah dipakai pengguna lain";
                }
            }
        }

        if($error=="" && $id>0 && $role_lama=="atasan" && ($role!="atasan" || $aktif==0)){
            $jml=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users WHERE atasan_id=$id AND aktif=1"))['n'];
            if($jml>0){
                $error="Pengguna ini masih menjadi atasan dari $jml karyawan aktif. Pindahkan atasan mereka dulu sebelum mengubah peran atau menonaktifkan akun.";
            }
        }

        if($error==""){
            $nama_db=mysqli_real_escape_string($conn,$nama);
            $divisi_sql=($divisi_id!="") ? (int)$divisi_id : "NULL";
            $jabatan_sql=($jabatan_id!="") ? (int)$jabatan_id : "NULL";
            $atasan_sql=($atasan_id!="") ? (int)$atasan_id : "NULL";

            if($id==0){
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $simpan=mysqli_query($conn,"INSERT INTO users (nik,nama,email,password,role,divisi_id,jabatan_id,atasan_id,aktif)
                    VALUES ('$nik_db','$nama_db','$email_db','$hash','$role',$divisi_sql,$jabatan_sql,$atasan_sql,$aktif)");
                $pesan_ok="Pengguna $nama berhasil ditambahkan";
            }else{
                $pw_sql="";
                if($password!=""){
                    $pw_sql=", password='".password_hash($password,PASSWORD_DEFAULT)."'";
                }
                $simpan=mysqli_query($conn,"UPDATE users SET nik='$nik_db', nama='$nama_db', email='$email_db', role='$role',
                    divisi_id=$divisi_sql, jabatan_id=$jabatan_sql, atasan_id=$atasan_sql, aktif=$aktif $pw_sql
                    WHERE id=$id");
                $pesan_ok="Data $nama berhasil diperbarui";
                if($simpan && $diri_sendiri){
                    $_SESSION['nama']=$nama;
                }
            }

            if($simpan){
                $_SESSION['pesan']=$pesan_ok;
                header("Location: users.php");
                exit;
            }else{
                $error="Gagal menyimpan data";
            }
        }
    }

    $judul=$id>0 ? "Edit Pengguna" : "Tambah Pengguna";
    $halaman="users";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1><?= $id>0 ? "Edit Pengguna" : "Tambah Pengguna" ?></h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="panel">

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <?php if($diri_sendiri){ ?>
                <div class="info-box mb-3">Ini akun Anda sendiri. Peran dan status dikunci agar Anda tidak kehilangan akses admin.</div>
            <?php } ?>

            <form action="user_form.php" method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-3">
                    <div class="col-sm-5">
                        <label for="nik" class="form-label">NIK</label>
                        <input type="text" class="form-control" id="nik" name="nik" value="<?= htmlspecialchars($nik) ?>" maxlength="20" required>
                    </div>
                    <div class="col-sm-7">
                        <label for="nama" class="form-label">Nama lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" maxlength="100" required>
                    </div>

                    <div class="col-12">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" maxlength="100" required>
                    </div>

                    <div class="col-sm-6">
                        <label for="role" class="form-label">Peran</label>
                        <select class="form-select" id="role" name="role" <?= $diri_sendiri?'disabled':'' ?>>
                            <?php foreach($role_ok as $kode=>$label){ ?>
                                <option value="<?= $kode ?>" <?= $role==$kode?'selected':'' ?>><?= $label ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="atasan_id" class="form-label">Atasan langsung <span class="opsional">(opsional)</span></label>
                        <select class="form-select" id="atasan_id" name="atasan_id">
                            <option value="">Belum ditentukan</option>
                            <?php foreach($daftar_atasan as $aid=>$anama){ ?>
                                <option value="<?= $aid ?>" <?= $atasan_id==$aid?'selected':'' ?>><?= htmlspecialchars($anama) ?></option>
                            <?php } ?>
                        </select>
                        <div class="form-text">Tidak dipakai untuk peran admin.</div>
                    </div>

                    <div class="col-sm-6">
                        <label for="divisi_id" class="form-label">Divisi</label>
                        <select class="form-select" id="divisi_id" name="divisi_id">
                            <option value="">Belum ditentukan</option>
                            <?php foreach($daftar_divisi as $did=>$dnama){ ?>
                                <option value="<?= $did ?>" <?= $divisi_id==$did?'selected':'' ?>><?= htmlspecialchars($dnama) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="jabatan_id" class="form-label">Jabatan</label>
                        <select class="form-select" id="jabatan_id" name="jabatan_id">
                            <option value="">Belum ditentukan</option>
                            <?php foreach($daftar_jabatan as $jid=>$jnama){ ?>
                                <option value="<?= $jid ?>" <?= $jabatan_id==$jid?'selected':'' ?>><?= htmlspecialchars($jnama) ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label">
                            <?= $id>0 ? "Password baru" : "Password" ?>
                            <?php if($id>0){ ?><span class="opsional">(kosongkan jika tidak diubah)</span><?php } ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" <?= $id==0?'required':'' ?>>
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" <?= $aktif==1?'checked':'' ?> <?= $diri_sendiri?'disabled':'' ?>>
                            <label class="form-check-label" for="aktif">Akun aktif (bisa login)</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 mt-3">
                        <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
                        <a href="users.php" class="btn btn-ghost">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
