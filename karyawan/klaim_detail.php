<?php
    require "cek_karyawan.php";

    $id=isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $q=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, kt.ikon
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        WHERE k.id=$id AND k.user_id=$id_user");
    if(mysqli_num_rows($q)==0){
        $_SESSION['pesan_error']="Klaim tidak ditemukan";
        header("Location: dashboard.php");
        exit;
    }
    $k=mysqli_fetch_assoc($q);

    $sql_log=mysqli_query($conn,"SELECT l.*, u.nama AS nama_aktor
        FROM klaim_log l
        INNER JOIN users u ON l.aktor_id=u.id
        WHERE l.klaim_id=$id
        ORDER BY l.id");

    $ekstensi=strtolower(pathinfo((string)$k['file_nota'],PATHINFO_EXTENSION));

    $judul="Detail Klaim";
    $halaman="dashboard";
    include "header.php";
?>

<a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-4">
    <div class="page-title">
        <h1>#<?= htmlspecialchars((string)$k['kode_klaim']) ?></h1>
        <p>Detail pengajuan reimbursement.</p>
    </div>
    <div><?= chip_status($k['status']) ?></div>
</div>

<div class="row g-4">

    <div class="col-lg-7">
        <div class="panel mb-4">
            <h2>Data klaim</h2>

            <?php if($k['status']=="ditolak"){ ?>
                <div class="alert alert-warning">
                    <strong>Klaim ditolak.</strong> Alasan: <?= htmlspecialchars((string)$k['alasan_penolakan']) ?>
                </div>
            <?php } ?>

            <div class="detail-row">
                <div class="label">Kategori</div>
                <div class="nilai">
                    <span class="kat-ikon"><i class="bi <?= htmlspecialchars((string)$k['ikon']) ?>"></i></span>
                    <span class="ms-2"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="label">Tanggal transaksi</div>
                <div class="nilai"><?= tgl_indo($k['tanggal_transaksi']) ?></div>
            </div>
            <div class="detail-row">
                <div class="label">Nominal</div>
                <div class="nilai fw-bold"><?= rupiah($k['nominal']) ?></div>
            </div>
            <div class="detail-row">
                <div class="label">Keterangan</div>
                <div class="nilai"><?= nl2br(htmlspecialchars($k['keterangan'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="label">Tanggal diajukan</div>
                <div class="nilai"><?= $k['tanggal_diajukan'] ? tgl_indo($k['tanggal_diajukan'],true) : "-" ?></div>
            </div>
            <?php if($k['status']=="dibayar"){ ?>
                <div class="detail-row">
                    <div class="label">Tanggal dibayar</div>
                    <div class="nilai"><?= $k['tanggal_bayar'] ? tgl_indo($k['tanggal_bayar']) : "-" ?></div>
                </div>
                <?php if($k['catatan_bayar']!=""){ ?>
                    <div class="detail-row">
                        <div class="label">Catatan pembayaran</div>
                        <div class="nilai"><?= htmlspecialchars($k['catatan_bayar']) ?></div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>

        <div class="panel">
            <h2>Bukti nota</h2>
            <?php if($k['file_nota']==""){ ?>
                <p class="text-muted mb-0">Belum ada nota.</p>
            <?php }elseif($ekstensi=="pdf"){ ?>
                <a href="../nota.php?id=<?= $k['id'] ?>" target="_blank" class="btn btn-ghost"><i class="bi bi-file-earmark-pdf"></i> Buka nota (PDF)</a>
            <?php }else{ ?>
                <div class="nota-preview">
                    <a href="../nota.php?id=<?= $k['id'] ?>" target="_blank">
                        <img src="../nota.php?id=<?= $k['id'] ?>" alt="Nota klaim">
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="col-lg-5">

        <?php if($k['status']=="draft" || $k['status']=="ditolak"){ ?>
        <div class="panel mb-4">
            <h2>Tindakan</h2>
            <div class="d-flex flex-wrap gap-2">
                <?php if($k['status']=="draft"){ ?>
                    <form action="klaim_aksi.php" method="POST" class="d-inline">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit" name="ajukan" class="btn btn-main">Ajukan</button>
                    </form>
                    <a href="klaim_form.php?id=<?= $k['id'] ?>" class="btn btn-ghost">Edit</a>
                    <form action="klaim_aksi.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus draft klaim ini?')">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit" name="hapus" class="btn btn-ghost-danger">Hapus</button>
                    </form>
                <?php }else{ ?>
                    <a href="klaim_form.php?id=<?= $k['id'] ?>" class="btn btn-main">Revisi &amp; Ajukan Ulang</a>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <div class="panel">
            <h2>Riwayat status</h2>
            <ul class="timeline">
                <?php while($l=mysqli_fetch_assoc($sql_log)){ ?>
                    <li>
                        <div class="t-judul"><?= chip_status($l['status_baru'],true) ?></div>
                        <div class="t-meta">oleh <?= htmlspecialchars($l['nama_aktor']) ?>, <?= tgl_indo($l['created_at'],true) ?></div>
                        <?php if($l['catatan']!=""){ ?>
                            <div class="t-catatan"><?= htmlspecialchars($l['catatan']) ?></div>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>
