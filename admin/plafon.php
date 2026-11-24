<?php
    require "cek_admin.php";

    $filter="";
    $where="";
    if(isset($_GET['jabatan']) && $_GET['jabatan']!=""){
        $filter=(int)$_GET['jabatan'];
        $where="WHERE p.jabatan_id=$filter";
    }

    $sql=mysqli_query($conn,"SELECT p.*, j.nama_jabatan, k.nama AS nama_kategori, k.ikon, k.aktif AS kategori_aktif
        FROM plafon p
        INNER JOIN jabatan j ON p.jabatan_id=j.id
        INNER JOIN kategori_klaim k ON p.kategori_id=k.id
        $where
        ORDER BY j.id, k.id");
    $sql_jabatan=mysqli_query($conn,"SELECT * FROM jabatan ORDER BY id");

    $judul="Plafon";
    $halaman="plafon";
    include "header.php";
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="page-title">
        <h1>Plafon Klaim</h1>
        <p>Batas nominal klaim per bulan untuk setiap jabatan dan kategori.</p>
    </div>
    <a href="plafon_form.php" class="btn btn-main"><i class="bi bi-plus-lg"></i> Tambah Aturan</a>
</div>

<div class="panel">

    <form action="plafon.php" method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-auto">
            <label for="jabatan" class="col-form-label fw-semibold small">Jabatan</label>
        </div>
        <div class="col-auto">
            <select name="jabatan" id="jabatan" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua jabatan</option>
                <?php while($j=mysqli_fetch_assoc($sql_jabatan)){ ?>
                    <option value="<?= $j['id'] ?>" <?= $filter===(int)$j['id']?'selected':'' ?>><?= htmlspecialchars($j['nama_jabatan']) ?></option>
                <?php } ?>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jabatan</th>
                    <th>Kategori</th>
                    <th>Kuota per bulan</th>
                    <th>Sisa kuota</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $no=1;
                    while($p=mysqli_fetch_assoc($sql)){
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($p['nama_jabatan']) ?></td>
                    <td>
                        <span class="kat-ikon"><i class="bi <?= htmlspecialchars((string)$p['ikon']) ?>"></i></span>
                        <span class="ms-2"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                        <?php if($p['kategori_aktif']==0){ ?>
                            <span class="chip chip-grey ms-1">Nonaktif</span>
                        <?php } ?>
                    </td>
                    <td class="fw-semibold"><?= rupiah($p['kuota_bulanan']) ?></td>
                    <td>
                        <?php if($p['akumulasi']==1){ ?>
                            <span class="chip chip-blue">Terbawa ke bulan depan</span>
                        <?php }else{ ?>
                            <span class="chip chip-grey">Hangus tiap akhir bulan</span>
                        <?php } ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="plafon_form.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <form action="plafon_hapus.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus aturan plafon ini?')">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" name="hapus" class="btn btn-ghost-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php
                    }
                    if($no==1){
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada aturan plafon.</td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>

    <p class="form-text mt-3 mb-0">Kuota berlaku per karyawan per bulan, sesuai jabatannya. Jabatan tanpa aturan untuk suatu kategori tidak bisa mengajukan klaim kategori itu.</p>
</div>

<?php include "footer.php"; ?>
