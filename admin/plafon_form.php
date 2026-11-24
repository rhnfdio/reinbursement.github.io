<?php
    require "cek_admin.php";

    $error="";
    $id=0;              
    $jabatan_id=0;
    $kategori_id=0;
    $kuota="";
    $akumulasi="0";

    if(isset($_GET['id'])){
        $id=(int)$_GET['id'];
        $cek=mysqli_query($conn,"SELECT * FROM plafon WHERE id=$id");
        if(mysqli_num_rows($cek)==0){
            header("Location: plafon.php");
            exit;
        }
        $data=mysqli_fetch_assoc($cek);
        $jabatan_id=$data['jabatan_id'];
        $kategori_id=$data['kategori_id'];
        $kuota=$data['kuota_bulanan'];
        $akumulasi=(string)$data['akumulasi'];
    }

    if(isset($_POST['simpan'])){
        $id=(int)$_POST['id'];
        $kuota=trim($_POST['kuota']);
        $akumulasi=($_POST['akumulasi']=="1") ? "1" : "0";

        if($id>0){
            $cek=mysqli_query($conn,"SELECT jabatan_id, kategori_id FROM plafon WHERE id=$id");
            if(mysqli_num_rows($cek)==0){
                header("Location: plafon.php");
                exit;
            }
            $data=mysqli_fetch_assoc($cek);
            $jabatan_id=(int)$data['jabatan_id'];
            $kategori_id=(int)$data['kategori_id'];
        }else{
            $jabatan_id=(int)$_POST['jabatan_id'];
            $kategori_id=(int)$_POST['kategori_id'];
        }

        if($id==0 && ($jabatan_id==0 || $kategori_id==0)){
            $error="Pilih jabatan dan kategori";
        }elseif(!ctype_digit($kuota) || (int)$kuota<=0){
            $error="Kuota harus berupa angka bulat lebih dari 0 (tanpa titik atau koma)";
        }elseif((int)$kuota>1000000000){
            $error="Kuota terlalu besar";
        }else{
            $kuota_db=(int)$kuota;

            if($id==0){
                $cek=mysqli_query($conn,"SELECT id FROM plafon WHERE jabatan_id=$jabatan_id AND kategori_id=$kategori_id");
                if(mysqli_num_rows($cek)>0){
                    $error="Aturan untuk jabatan dan kategori ini sudah ada, silakan edit aturan yang lama";
                }else{
                    $simpan=mysqli_query($conn,"INSERT INTO plafon (jabatan_id,kategori_id,kuota_bulanan,akumulasi) VALUES ($jabatan_id,$kategori_id,$kuota_db,$akumulasi)");
                    $pesan_ok="Aturan plafon berhasil ditambahkan";
                }
            }else{
                $simpan=mysqli_query($conn,"UPDATE plafon SET kuota_bulanan=$kuota_db, akumulasi=$akumulasi WHERE id=$id");
                $pesan_ok="Aturan plafon berhasil diperbarui";
            }

            if($error==""){
                if($simpan){
                    $_SESSION['pesan']=$pesan_ok;
                    header("Location: plafon.php");
                    exit;
                }else{
                    $error="Gagal menyimpan data";
                }
            }
        }
    }

    $info=array();
    if($id>0){
        $info=mysqli_fetch_assoc(mysqli_query($conn,"SELECT j.nama_jabatan, k.nama AS nama_kategori FROM plafon p INNER JOIN jabatan j ON p.jabatan_id=j.id INNER JOIN kategori_klaim k ON p.kategori_id=k.id WHERE p.id=$id"));
    }else{
        $sql_jabatan=mysqli_query($conn,"SELECT * FROM jabatan ORDER BY id");
        $sql_kategori=mysqli_query($conn,"SELECT * FROM kategori_klaim WHERE aktif=1 ORDER BY id");
    }

    $judul=$id>0 ? "Edit Plafon" : "Tambah Plafon";
    $halaman="plafon";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1><?= $id>0 ? "Edit Aturan Plafon" : "Tambah Aturan Plafon" ?></h1>
    <p>Tentukan batas klaim per bulan untuk satu jabatan pada satu kategori.</p>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="panel">

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form action="plafon_form.php" method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <?php if($id>0){ ?>
                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($info['nama_jabatan']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($info['nama_kategori']) ?>" disabled>
                        <div class="form-text">Jabatan dan kategori tidak bisa diubah. Hapus aturan ini lalu buat baru jika perlu.</div>
                    </div>
                <?php }else{ ?>
                    <div class="mb-3">
                        <label for="jabatan_id" class="form-label">Jabatan</label>
                        <select class="form-select" id="jabatan_id" name="jabatan_id" required>
                            <option value="">Pilih jabatan</option>
                            <?php while($j=mysqli_fetch_assoc($sql_jabatan)){ ?>
                                <option value="<?= $j['id'] ?>" <?= $jabatan_id==$j['id']?'selected':'' ?>><?= htmlspecialchars($j['nama_jabatan']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_id" name="kategori_id" required>
                            <option value="">Pilih kategori</option>
                            <?php while($k=mysqli_fetch_assoc($sql_kategori)){ ?>
                                <option value="<?= $k['id'] ?>" <?= $kategori_id==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>

                <div class="mb-3">
                    <label for="kuota" class="form-label">Kuota per bulan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="kuota" name="kuota" value="<?= htmlspecialchars($kuota) ?>" min="1" step="1" placeholder="contoh: 750000" required>
                    </div>
                    <div class="form-text">Isi angka saja, tanpa titik atau koma.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Sisa kuota di akhir bulan</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="akumulasi" id="akum0" value="0" <?= $akumulasi=="0"?'checked':'' ?>>
                        <label class="form-check-label" for="akum0">Hangus tiap akhir bulan</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="akumulasi" id="akum1" value="1" <?= $akumulasi=="1"?'checked':'' ?>>
                        <label class="form-check-label" for="akum1">Terbawa ke bulan berikutnya</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
                    <a href="plafon.php" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
