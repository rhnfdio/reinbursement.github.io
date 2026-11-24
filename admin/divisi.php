<?php
    require "cek_admin.php";

    $error="";
    $edit_id="";
    $nama="";

    if(isset($_POST['hapus'])){
        $id=(int)$_POST['id'];
        $jml=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users WHERE divisi_id=$id"))['n'];
        if($jml>0){
            $_SESSION['pesan_error']="Divisi masih dipakai oleh $jml pengguna, tidak bisa dihapus";
        }else{
            mysqli_query($conn,"DELETE FROM divisi WHERE id=$id");
            if(mysqli_affected_rows($conn)>0){
                $_SESSION['pesan']="Divisi berhasil dihapus";
            }else{
                $_SESSION['pesan_error']="Divisi tidak ditemukan";
            }
        }
        header("Location: divisi.php");
        exit;
    }

    if(isset($_GET['edit'])){
        $id=(int)$_GET['edit'];
        $cek=mysqli_query($conn,"SELECT * FROM divisi WHERE id=$id");
        if(mysqli_num_rows($cek)>0){
            $data=mysqli_fetch_assoc($cek);
            $edit_id=$data['id'];
            $nama=$data['nama_divisi'];
        }
    }

    if(isset($_POST['simpan'])){
        $edit_id=$_POST['edit_id'];
        $nama=trim($_POST['nama']);

        if($nama==""){
            $error="Nama divisi wajib diisi";
        }else{
            $nama_db=mysqli_real_escape_string($conn,$nama);
            $id_edit=(int)$edit_id;

            $cek=mysqli_query($conn,"SELECT id FROM divisi WHERE nama_divisi='$nama_db' AND id!=$id_edit");
            if(mysqli_num_rows($cek)>0){
                $error="Nama divisi sudah ada";
            }else{
                if($edit_id==""){
                    $simpan=mysqli_query($conn,"INSERT INTO divisi (nama_divisi) VALUES ('$nama_db')");
                    $pesan_ok="Divisi berhasil ditambahkan";
                }else{
                    $simpan=mysqli_query($conn,"UPDATE divisi SET nama_divisi='$nama_db' WHERE id=$id_edit");
                    $pesan_ok="Divisi berhasil diperbarui";
                }
                if($simpan){
                    $_SESSION['pesan']=$pesan_ok;
                    header("Location: divisi.php");
                    exit;
                }else{
                    $error="Gagal menyimpan data";
                }
            }
        }
    }

    $sql=mysqli_query($conn,"SELECT d.*, (SELECT COUNT(*) FROM users WHERE divisi_id=d.id) AS jml_user
        FROM divisi d ORDER BY d.nama_divisi");

    $judul="Divisi";
    $halaman="divisi";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Divisi</h1>
    <p>Daftar divisi perusahaan untuk pengelompokan karyawan dan filter laporan.</p>
</div>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="panel">
            <h2><?= $edit_id=="" ? "Tambah divisi" : "Edit divisi" ?></h2>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form action="divisi.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_id) ?>">
                <div class="mb-4">
                    <label for="nama" class="form-label">Nama divisi</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" maxlength="80" placeholder="contoh: Operasional" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
                    <?php if($edit_id!=""){ ?>
                        <a href="divisi.php" class="btn btn-ghost">Batal</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel">
            <h2>Daftar divisi</h2>
            <div class="table-responsive">
                <table class="table align-middle table-clean">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Divisi</th>
                            <th>Jumlah pengguna</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $no=1;
                            while($d=mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($d['nama_divisi']) ?></td>
                            <td><?= $d['jml_user'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="divisi.php?edit=<?= $d['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="divisi.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus divisi ini?')">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" name="hapus" class="btn btn-ghost-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                            }
                            if($no==1){
                        ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada divisi.</td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="form-text mt-3 mb-0">Divisi yang masih dipakai pengguna tidak bisa dihapus.</p>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>
