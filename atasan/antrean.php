<?php
    require "cek_atasan.php";

    $awal_bulan=date("Y-m-01 00:00:00");
    $akhir_bulan=date("Y-m-t 23:59:59");

    $disetujui=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM klaim_log
        WHERE aktor_id=$id_user AND status_baru='menunggu_pembayaran'
        AND created_at BETWEEN '$awal_bulan' AND '$akhir_bulan'"))['n'];
    $ditolak=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM klaim_log
        WHERE aktor_id=$id_user AND status_baru='ditolak'
        AND created_at BETWEEN '$awal_bulan' AND '$akhir_bulan'"))['n'];
    $jml_bawahan=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users
        WHERE atasan_id=$id_user AND aktif=1"))['n'];

    $per_hal=8;
    $total=$jml_menunggu;
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
        WHERE u.atasan_id=$id_user AND k.status='diajukan'
        ORDER BY k.tanggal_diajukan ASC, k.id ASC
        LIMIT $per_hal OFFSET $offset");

    $judul="Antrean Persetujuan";
    $halaman="antrean";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Antrean Persetujuan</h1>
    <p>Klaim bawahan Anda yang menunggu diperiksa.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="stat-num"><?= $jml_menunggu ?></div>
            <div class="stat-label">Menunggu persetujuan</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
            <div class="stat-num"><?= $disetujui ?></div>
            <div class="stat-label">Disetujui bulan ini</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
            <div class="stat-num"><?= $ditolak ?></div>
            <div class="stat-label">Ditolak bulan ini</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-people"></i></span>
            <div class="stat-num"><?= $jml_bawahan ?></div>
            <div class="stat-label">Karyawan bawahan</div>
        </div>
    </div>
</div>

<div class="panel">
    <h2>Klaim menunggu persetujuan</h2>

    <?php if($jml_bawahan==0){ ?>
        <div class="info-box peringatan mb-3">Belum ada karyawan yang menjadikan Anda atasan langsung. Karyawan bisa memilih atasan saat mendaftar, atau diatur oleh admin HR.</div>
    <?php } ?>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>No / Diajukan</th>
                    <th>Karyawan</th>
                    <th>Kategori &amp; Keterangan</th>
                    <th class="text-end">Nominal</th>
                    <th>Menunggu</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $ada=false;
                    while($c=mysqli_fetch_assoc($sql)){
                        $ada=true;
                        $hari_tunggu=(int)floor((time()-strtotime($c['tanggal_diajukan']))/86400);
                ?>
                <tr>
                    <td>
                        <div class="kode-klaim">#<?= htmlspecialchars((string)$c['kode_klaim']) ?></div>
                        <div class="sub-teks"><?= tgl_indo($c['tanggal_diajukan']) ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_karyawan']) ?></div>
                        <div class="sub-teks"><?= htmlspecialchars((string)$c['nama_jabatan']) ?><?php if($c['nama_divisi']!=""){ ?>, <?= htmlspecialchars($c['nama_divisi']) ?><?php } ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_kategori']) ?></div>
                        <div class="sub-teks ket-singkat"><?= htmlspecialchars($c['keterangan']) ?></div>
                    </td>
                    <td class="text-end fw-semibold text-nowrap"><?= rupiah($c['nominal']) ?></td>
                    <td>
                        <?php if($hari_tunggu>=2){ ?>
                            <span class="chip chip-red"><?= $hari_tunggu ?> hari</span>
                        <?php }elseif($hari_tunggu==1){ ?>
                            <span class="chip chip-amber">1 hari</span>
                        <?php }else{ ?>
                            <span class="chip chip-grey">Hari ini</span>
                        <?php } ?>
                    </td>
                    <td class="text-end">
                        <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-main btn-sm">Tinjau</a>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada){
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Tidak ada klaim yang menunggu persetujuan.</td>
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
        <nav aria-label="Halaman antrean">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $hal<=1?'disabled':'' ?>">
                    <a class="page-link" href="antrean.php?hal=<?= $hal-1 ?>">Sebelumnya</a>
                </li>
                <?php for($i=1;$i<=$total_hal;$i++){ ?>
                    <li class="page-item <?= $i==$hal?'active':'' ?>">
                        <a class="page-link" href="antrean.php?hal=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
                <li class="page-item <?= $hal>=$total_hal?'disabled':'' ?>">
                    <a class="page-link" href="antrean.php?hal=<?= $hal+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
