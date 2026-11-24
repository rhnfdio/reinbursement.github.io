<?php
    require "cek_admin.php";

    $id=isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $q=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, kt.ikon,
            u.nama AS nama_karyawan, u.nik, u.jabatan_id AS jabatan_karyawan,
            j.nama_jabatan, d.nama_divisi, a.nama AS nama_atasan
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        INNER JOIN users u ON k.user_id=u.id
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        LEFT JOIN users a ON u.atasan_id=a.id
        WHERE k.id=$id AND k.status!='draft'");
    if(mysqli_num_rows($q)==0){
        $_SESSION['pesan_error']="Klaim tidak ditemukan";
        header("Location: klaim.php");
        exit;
    }
    $k=mysqli_fetch_assoc($q);

    $nama_bulan=array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
    $bulan_trx=substr($k['tanggal_transaksi'],0,7);
    $label_bulan=$nama_bulan[(int)substr($bulan_trx,5,2)]." ".substr($bulan_trx,0,4);
    $s=sisa_plafon($conn,(int)$k['user_id'],(int)$k['jabatan_karyawan'],(int)$k['kategori_id'],$bulan_trx);
    $persen=0;
    if($s!==null && $s['kuota']>0){
        $persen=min(100,$s['terpakai']/$s['kuota']*100);
    }
    $kelas_bar="";
    if($persen>=100){
        $kelas_bar="full";
    }elseif($persen>=80){
        $kelas_bar="warn";
    }

    $sql_log=mysqli_query($conn,"SELECT l.*, u.nama AS nama_aktor
        FROM klaim_log l
        INNER JOIN users u ON l.aktor_id=u.id
        WHERE l.klaim_id=$id
        ORDER BY l.id");

    $ekstensi=strtolower(pathinfo((string)$k['file_nota'],PATHINFO_EXTENSION));

    $judul="Detail Klaim";
    $halaman="klaim";
    include "header.php";
?>

<a href="klaim.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Kembali ke daftar klaim</a>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-4">
    <div class="page-title">
        <h1>#<?= htmlspecialchars((string)$k['kode_klaim']) ?></h1>
        <p>Diajukan oleh <?= htmlspecialchars($k['nama_karyawan']) ?>.</p>
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
                <div class="label">Karyawan</div>
                <div class="nilai">
                    <?= htmlspecialchars($k['nama_karyawan']) ?>
                    <div class="sub-teks">
                        NIK <?= htmlspecialchars($k['nik']) ?>, <?= htmlspecialchars((string)$k['nama_jabatan']) ?><?php if($k['nama_divisi']!=""){ ?> (<?= htmlspecialchars($k['nama_divisi']) ?>)<?php } ?>
                    </div>
                </div>
            </div>
            <div class="detail-row">
                <div class="label">Atasan</div>
                <div class="nilai"><?= $k['nama_atasan']!="" ? htmlspecialchars($k['nama_atasan']) : "-" ?></div>
            </div>
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
                <p class="text-muted mb-0">Karyawan belum melampirkan nota.</p>
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
        <div class="panel mb-4">
            <h2>Plafon <?= htmlspecialchars($k['nama_kategori']) ?>, <?= $label_bulan ?></h2>
            <?php if($s===null){ ?>
                <p class="text-muted mb-0">Tidak ada aturan plafon untuk jabatan karyawan ini.</p>
            <?php }else{ ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span>Terpakai <?= rupiah($s['terpakai']) ?></span>
                    <span class="text-muted">dari <?= rupiah($s['kuota']) ?></span>
                </div>
                <div class="bar"><span class="<?= $kelas_bar ?>" style="width: <?= number_format($persen,1,".","") ?>%;"></span></div>
                <div class="sub-teks mt-2">Sisa <?= rupiah(max(0,$s['sisa'])) ?>.</div>
            <?php } ?>
        </div>

        <?php if($k['status']=="menunggu_pembayaran"){ ?>
        <div class="panel mb-4">
            <h2>Verifikasi dan pembayaran</h2>

            <form action="klaim_proses.php" method="POST" class="mb-4" onsubmit="return confirm('Tandai klaim ini sudah dibayar?')">
                <input type="hidden" name="id" value="<?= $k['id'] ?>">

                <label for="tanggal_bayar" class="form-label">Tanggal pembayaran</label>
                <input type="date" class="form-control mb-3" id="tanggal_bayar" name="tanggal_bayar" value="<?= date("Y-m-d") ?>" max="<?= date("Y-m-d") ?>" required>

                <label for="catatan" class="form-label">Catatan <span class="opsional">(opsional)</span></label>
                <input type="text" class="form-control mb-3" id="catatan" name="catatan" maxlength="255" placeholder="contoh: Transfer BCA, ref 8841230">

                <button type="submit" name="bayar" class="btn btn-main w-100">Tandai sudah dibayar</button>
            </form>

            <hr class="my-3">

            <form action="klaim_proses.php" method="POST" onsubmit="return confirm('Tolak klaim ini? Karyawan akan diminta merevisi.')">
                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                <label for="alasan" class="form-label">Nota tidak valid? Alasan penolakan <span class="text-danger">*</span></label>
                <textarea class="form-control mb-3" id="alasan" name="alasan" rows="2" maxlength="255" placeholder="contoh: Struk tidak mencantumkan cap resmi" required></textarea>
                <button type="submit" name="tolak" class="btn btn-ghost-danger w-100">Tolak klaim</button>
            </form>
        </div>
        <?php }elseif($k['status']=="diajukan"){ ?>
        <div class="info-box mb-4">
            Klaim ini masih menunggu persetujuan atasan<?php if($k['nama_atasan']!=""){ ?> (<strong><?= htmlspecialchars($k['nama_atasan']) ?></strong>)<?php } ?>.
            HR/Finance dapat memprosesnya setelah disetujui.
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
