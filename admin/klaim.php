<?php
    require "cek_admin.php";
    include "filter_klaim.php";

    $sql_divisi=mysqli_query($conn,"SELECT * FROM divisi ORDER BY nama_divisi");
    $sql_kategori=mysqli_query($conn,"SELECT * FROM kategori_klaim ORDER BY id");

    $per_hal=10;
    $total=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n
        FROM klaim k INNER JOIN users u ON k.user_id=u.id
        WHERE $where"))['n'];
    $total_hal=max(1,(int)ceil($total/$per_hal));
    $hal=isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    if($hal<1){ $hal=1; }
    if($hal>$total_hal){ $hal=$total_hal; }
    $offset=($hal-1)*$per_hal;

    $sql=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, u.nama AS nama_karyawan, j.nama_jabatan, d.nama_divisi
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        INNER JOIN users u ON k.user_id=u.id
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        WHERE $where
        ORDER BY k.id DESC
        LIMIT $per_hal OFFSET $offset");

    $judul="Klaim Karyawan";
    $halaman="klaim";
    include "header.php";
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="page-title">
        <h1>Manajemen Klaim</h1>
        <p>Semua klaim karyawan yang sudah diajukan. Klaim draft tidak ditampilkan.</p>
    </div>
    <a href="klaim_export.php?<?= htmlspecialchars($qs) ?>" class="btn btn-ghost"><i class="bi bi-download"></i> Ekspor CSV</a>
</div>

<div class="panel">
    <form action="klaim.php" method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label for="status" class="form-label small">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">Semua status</option>
                <?php foreach($status_ok as $st){ ?>
                    <option value="<?= $st ?>" <?= $f_status==$st?'selected':'' ?>><?= chip_status($st,true) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="divisi" class="form-label small">Divisi</label>
            <select name="divisi" id="divisi" class="form-select form-select-sm">
                <option value="">Semua divisi</option>
                <?php while($d=mysqli_fetch_assoc($sql_divisi)){ ?>
                    <option value="<?= $d['id'] ?>" <?= $f_divisi==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['nama_divisi']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="kategori" class="form-label small">Kategori</label>
            <select name="kategori" id="kategori" class="form-select form-select-sm">
                <option value="">Semua kategori</option>
                <?php while($kt=mysqli_fetch_assoc($sql_kategori)){ ?>
                    <option value="<?= $kt['id'] ?>" <?= $f_kategori==$kt['id']?'selected':'' ?>><?= htmlspecialchars($kt['nama']) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="bulan" class="form-label small">Bulan transaksi</label>
            <input type="month" name="bulan" id="bulan" class="form-control form-control-sm" value="<?= htmlspecialchars($f_bulan) ?>">
        </div>
        <div class="col-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-main btn-sm">Terapkan filter</button>
            <a href="klaim.php" class="btn btn-ghost btn-sm">Reset</a>
            <span class="ms-auto sub-teks align-self-center"><?= $total ?> klaim</span>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>Kode / Tanggal</th>
                    <th>Karyawan</th>
                    <th>Kategori</th>
                    <th class="text-end">Nominal</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $ada=false;
                    while($c=mysqli_fetch_assoc($sql)){
                        $ada=true;
                ?>
                <tr>
                    <td>
                        <div class="kode-klaim">#<?= htmlspecialchars((string)$c['kode_klaim']) ?></div>
                        <div class="sub-teks"><?= tgl_indo($c['tanggal_transaksi']) ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_karyawan']) ?></div>
                        <div class="sub-teks"><?= htmlspecialchars((string)$c['nama_divisi']) ?><?php if($c['nama_jabatan']!=""){ ?>, <?= htmlspecialchars($c['nama_jabatan']) ?><?php } ?></div>
                    </td>
                    <td><?= htmlspecialchars($c['nama_kategori']) ?></td>
                    <td class="text-end fw-semibold text-nowrap"><?= rupiah($c['nominal']) ?></td>
                    <td><?= chip_status($c['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if($c['status']=="menunggu_pembayaran"){ ?>
                            <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-main btn-sm">Proses</a>
                        <?php }else{ ?>
                            <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Detail</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada){
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Tidak ada klaim yang sesuai filter.</td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>

    <?php if($total>0){ ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <div class="sub-teks">Menampilkan <?= $offset+1 ?>-<?= min($offset+$per_hal,$total) ?> dari <?= $total ?> klaim</div>
        <nav aria-label="Halaman klaim">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $hal<=1?'disabled':'' ?>">
                    <a class="page-link" href="klaim.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $hal-1 ?>">Sebelumnya</a>
                </li>
                <?php for($i=1;$i<=$total_hal;$i++){ ?>
                    <li class="page-item <?= $i==$hal?'active':'' ?>">
                        <a class="page-link" href="klaim.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
                <li class="page-item <?= $hal>=$total_hal?'disabled':'' ?>">
                    <a class="page-link" href="klaim.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $hal+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
