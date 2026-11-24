<?php
    require "cek_admin.php";
    $daftar_ikon=array(
        "bi-truck"=>"Transport",
        "bi-cup-hot"=>"Makan & minum",
        "bi-heart-pulse"=>"Kesehatan",
        "bi-box-seam"=>"Perlengkapan",
        "bi-building"=>"Akomodasi",
        "bi-telephone"=>"Komunikasi",
        "bi-mortarboard"=>"Pelatihan",
        "bi-tag"=>"Lainnya"
    );

    $error="";
    $edit_id="";
    $nama="";
    $ikon="bi-tag";

    if(isset($_POST['toggle'])){
        $id=(int)$_POST['id'];
        mysqli_query($conn,"UPDATE kategori_klaim SET aktif=1-aktif WHERE id=$id");
        $_SESSION['pesan']="Status kategori berhasil diubah";
        header("Location: kategori.php");
        exit;
    }

    if(isset($_GET['edit'])){
        $id=(int)$_GET['edit'];
        $cek=mysqli_query($conn,"SELECT * FROM kategori_klaim WHERE id=$id");
        if(mysqli_num_rows($cek)>0){
            $data=mysqli_fetch_assoc($cek);
            $edit_id=$data['id'];
            $nama=$data['nama'];
            $ikon=$data['ikon'];
        }
    }

    if(isset($_POST['simpan'])){
        $edit_id=$_POST['edit_id'];
        $nama=trim($_POST['nama']);
        $ikon=$_POST['ikon'];

        if(!isset($daftar_ikon[$ikon])){
            $ikon="bi-tag";
        }

        if($nama==""){
            $error="Nama kategori wajib diisi";
        }else{
            $nama_db=mysqli_real_escape_string($conn,$nama);
            $id_edit=(int)$edit_id;

            $cek=mysqli_query($conn,"SELECT id FROM kategori_klaim WHERE nama='$nama_db' AND id!=$id_edit");
            if(mysqli_num_rows($cek)>0){
                $error="Nama kategori sudah ada";
            }else{
                if($edit_id==""){
                    $simpan=mysqli_query($conn,"INSERT INTO kategori_klaim (nama,ikon) VALUES ('$nama_db','$ikon')");
                    $pesan_ok="Kategori berhasil ditambahkan";
                }else{
                    $simpan=mysqli_query($conn,"UPDATE kategori_klaim SET nama='$nama_db', ikon='$ikon' WHERE id=$id_edit");
                    $pesan_ok="Kategori berhasil diperbarui";
                }

                if($simpan){
                    $_SESSION['pesan']=$pesan_ok;
                    header("Location: kategori.php");
                    exit;
                }else{
                    $error="Gagal menyimpan data";
                }
            }
        }
    }

    $sql=mysqli_query($conn,"SELECT k.*, (SELECT COUNT(*) FROM klaim WHERE kategori_id=k.id) AS jml_klaim FROM kategori_klaim k ORDER BY k.id");

    $judul="Kategori Klaim";
    $halaman="kategori";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Kategori Klaim</h1>
    <p>Jenis pengeluaran yang bisa diajukan karyawan.</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel">
            <h2><?= $edit_id=="" ? "Tambah kategori" : "Edit kategori" ?></h2>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form action="kategori.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_id) ?>">

                <div class="mb-3">
                    <label for="nama" class="form-label">Nama kategori</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" maxlength="50" placeholder="contoh: Akomodasi" required>
                </div>

                <div class="mb-4">
                    <label for="ikon" class="form-label">Ikon</label>
                    <select class="form-select" id="ikon" name="ikon">
                        <?php foreach($daftar_ikon as $kode=>$label){ ?>
                            <option value="<?= $kode ?>" <?= $ikon==$kode?'selected':'' ?>><?= $label ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
                    <?php if($edit_id!=""){ ?>
                        <a href="kategori.php" class="btn btn-ghost">Batal</a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <h2>Daftar kategori</h2>
            <div class="table-responsive">
                <table class="table align-middle table-clean">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kategori</th>
                            <th>Jumlah klaim</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $no=1;
                            while($k=mysqli_fetch_assoc($sql)){
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <span class="kat-ikon"><i class="bi <?= htmlspecialchars((string)$k['ikon']) ?>"></i></span>
                                <span class="ms-2 fw-semibold"><?= htmlspecialchars($k['nama']) ?></span>
                            </td>
                            <td><?= $k['jml_klaim'] ?></td>
                            <td>
                                <?php if($k['aktif']==1){ ?>
                                    <span class="chip chip-green">Aktif</span>
                                <?php }else{ ?>
                                    <span class="chip chip-grey">Nonaktif</span>
                                <?php } ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="kategori.php?edit=<?= $k['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                                <form action="kategori.php" method="POST" class="d-inline" <?php if($k['aktif']==1){ ?>onsubmit="return confirm('Nonaktifkan kategori ini? Kategori tidak akan muncul di form klaim.')"<?php } ?>>
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" name="toggle" class="btn btn-ghost btn-sm"><?= $k['aktif']==1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="form-text mt-3 mb-0">Kategori tidak dihapus agar klaim lama tetap utuh. Kategori nonaktif tidak muncul di form klaim.</p>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>
