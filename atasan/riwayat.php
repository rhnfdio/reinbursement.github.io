<?php
    require "cek_atasan.php";

    $status_ok=array("diajukan","menunggu_pembayaran","dibayar","ditolak");
    $filter="";
    if(isset($_GET['status']) && in_array($_GET['status'],$status_ok,true)){
        $filter=$_GET['status'];
    }
    $where="u.atasan_id=$id_user AND k.status!='draft'";
    if($filter!=""){
        $where.=" AND k.status='$filter'";
    }

    $per_hal=10;
    $total=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n
        FROM klaim k INNER JOIN users u ON k.user_id=u.id WHERE $where"))['n'];
    $total_hal=max(1,(int)ceil($total/$per_hal));
    $hal=isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    if($hal<1){ $hal=1; }
    if($hal>$total_hal){ $hal=$total_hal; }
    $offset=($hal-1)*$per_hal;

    $sql=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, u.nama AS nama_karyawan, j.nama_jabatan
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        INNER JOIN users u ON k.user_id=u.id
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        WHERE $where
        ORDER BY k.id DESC
        LIMIT $per_hal OFFSET $offset");

    $judul="Riwayat Klaim";
    $halaman="riwayat";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Riwayat Klaim</h1>
    <p>Semua klaim bawahan Anda yang sudah diajukan.</p>
</div>

<div class="panel">

    <form action="riwayat.php" method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-auto">
            <label for="status" class="col-form-label fw-semibold small">Status</label>
        </div>
        <div class="col-auto">
            <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <?php foreach($status_ok as $st){ ?>
                    <option value="<?= $st ?>" <?= $filter==$st?'selected':'' ?>><?= chip_status($st,true) ?></option>
                <?php } ?>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>No / Tanggal</th>
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
                        <div class="sub-teks"><?= htmlspecialchars((string)$c['nama_jabatan']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($c['nama_kategori']) ?></td>
                    <td class="text-end fw-semibold text-nowrap"><?= rupiah($c['nominal']) ?></td>
                    <td><?= chip_status($c['status']) ?></td>
                    <td class="text-end">
                        <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Detail</a>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada){
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada klaim.</td>
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
        <nav aria-label="Halaman riwayat">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $hal<=1?'disabled':'' ?>">
                    <a class="page-link" href="riwayat.php?status=<?= $filter ?>&hal=<?= $hal-1 ?>">Sebelumnya</a>
                </li>
                <?php for($i=1;$i<=$total_hal;$i++){ ?>
                    <li class="page-item <?= $i==$hal?'active':'' ?>">
                        <a class="page-link" href="riwayat.php?status=<?= $filter ?>&hal=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
                <li class="page-item <?= $hal>=$total_hal?'disabled':'' ?>">
                    <a class="page-link" href="riwayat.php?status=<?= $filter ?>&hal=<?= $hal+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
