<?php
    require "cek_admin.php";

    $error="";
    $edit_id="";
    $nama="";

    if(isset($_POST['hapus'])){
        $id=(int)$_POST['id'];
        $jml_user=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users WHERE jabatan_id=$id"))['n'];
        $jml_plafon=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM plafon WHERE jabatan_id=$id"))['n'];
        if($jml_user>0){
            $_SESSION['pesan_error']="Jabatan masih dipakai oleh $jml_user pengguna, tidak bisa dihapus";
        }elseif($jml_plafon>0){
            $_SESSION['pesan_error']="Jabatan masih memiliki $jml_plafon aturan plafon. Hapus aturan plafonnya dulu.";
        }else{
            mysqli_query($conn,"DELETE FROM jabatan WHERE id=$id");
            if(mysqli_affected_rows($conn)>0){
                $_SESSION['pesan']="Jabatan berhasil dihapus";
            }else{
                $_SESSION['pesan_error']="Jabatan tidak ditemukan";
            }
        }
        header("Location: jabatan.php");
        exit;
    }

    if(isset($_GET['edit'])){
        $id=(int)$_GET['edit'];
        $cek=mysqli_query($conn,"SELECT * FROM jabatan WHERE id=$id");
        if(mysqli_num_rows($cek)>0){
            $data=mysqli_fetch_assoc($cek);
            $edit_id=$data['id'];
            $nama=$data['nama_jabatan'];
        }
    }

    if(isset($_POST['simpan'])){
        $edit_id=$_POST['edit_id'];
        $nama=trim($_POST['nama']);

        if($nama==""){
            $error="Nama jabatan wajib diisi";
        }else{
            $nama_db=mysqli_real_escape_string($conn,$nama);
            $id_edit=(int)$edit_id;

            $cek=mysqli_query($conn,"SELECT id FROM jabatan WHERE nama_jabatan='$nama_db' AND id!=$id_edit");
            if(mysqli_num_rows($cek)>0){
                $error="Nama jabatan sudah ada";
            }else{
                if($edit_id==""){
                    $simpan=mysqli_query($conn,"INSERT INTO jabatan (nama_jabatan) VALUES ('$nama_db')");
                    $pesan_ok="Jabatan berhasil ditambahkan. Atur plafonnya di menu Plafon.";
                }else{
                    $simpan=mysqli_query($conn,"UPDATE jabatan SET nama_jabatan='$nama_db' WHERE id=$id_edit");
                    $pesan_ok="Jabatan berhasil diperbarui";
                }
                if($simpan){
                    $_SESSION['pesan']=$pesan_ok;
                    header("Location: jabatan.php");
                    exit;
                }else{
                    $error="Gagal menyimpan data";
                }
            }
        }
    }

    $sql=mysqli_query($conn,"SELECT j.*,
            (SELECT COUNT(*) FROM users WHERE jabatan_id=j.id) AS jml_user,
            (SELECT COUNT(*) FROM plafon WHERE jabatan_id=j.id) AS jml_plafon
        FROM jabatan j ORDER BY j.id");

    $judul="Jabatan";
    $halaman="jabatan";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Jabatan</h1>
    <p>Jabatan menentukan batas plafon klaim setiap karyawan.</p>
</div>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="panel">
            <h2><?= $edit_id=="" ? "Tambah jabatan" : "Edit jabatan" ?></h2>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form action="jabatan.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_id) ?>">
                <div class="mb-4">
                    <label for="nama" class="form-label">Nama jabatan</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" maxlength="80" placeholder="contoh: Supervisor" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
                    <?php if($edit_id!=""){ ?>
                        <a href="jabatan.php" class="btn btn-ghost">Batal</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel">
            <h2>Daftar jabatan</h2>
            <div class="table-responsive">
                <table class="table align-middle table-clean">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jabatan</th>
                            <th>Pengguna</th>
                            <th>Aturan plafon</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $no=1;
                            while($j=mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($j['nama_jabatan']) ?></td>
                            <td><?= $j['jml_user'] ?></td>
                            <td><?= $j['jml_plafon'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="jabatan.php?edit=<?= $j['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="jabatan.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                    <button type="submit" name="hapus" class="btn btn-ghost-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                            }
                            if($no==1){
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada jabatan.</td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="form-text mt-3 mb-0">Jabatan yang masih dipakai pengguna atau punya aturan plafon tidak bisa dihapus.</p>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>
