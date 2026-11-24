<?php
    require "cek_karyawan.php";

    $nama_bulan=array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
    $bulan_ini=date("Y-m");
    $label_bulan=$nama_bulan[(int)date("n")]." ".date("Y");
    $depan=strtotime("first day of next month");
    $label_reset="1 ".$nama_bulan[(int)date("n",$depan)];

    $sql_kat=mysqli_query($conn,"SELECT k.id, k.nama, k.ikon
        FROM kategori_klaim k
        INNER JOIN plafon p ON p.kategori_id=k.id
        WHERE k.aktif=1 AND p.jabatan_id=$jabatan_id
        ORDER BY k.id");

    $per_hal=5;
    $total=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM klaim WHERE user_id=$id_user"))['n'];
    $total_hal=max(1,(int)ceil($total/$per_hal));
    $hal=isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    if($hal<1){ $hal=1; }
    if($hal>$total_hal){ $hal=$total_hal; }
    $offset=($hal-1)*$per_hal;

    $sql=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        WHERE k.user_id=$id_user
        ORDER BY k.id DESC
        LIMIT $per_hal OFFSET $offset");

    $judul="Dashboard";
    $halaman="dashboard";
    include "header.php";
?>

<div class="row g-3 align-items-center mb-4">
    <div class="col-lg">
        <div class="page-title">
            <h1>Dashboard Klaim Karyawan</h1>
            <p>Ringkasan sisa plafon dan riwayat pengajuan reimbursement Anda.</p>
        </div>
    </div>
    <div class="col-lg-auto">
        <div class="user-box">
            <span class="avatar"><?= htmlspecialchars($inisial) ?></span>
            <div>
                <div class="fw-semibold"><?= htmlspecialchars($me['nama']) ?></div>
                <div class="sub-teks">
                    NIK <?= htmlspecialchars($me['nik']) ?>,
                    <?= htmlspecialchars((string)$me['nama_jabatan']) ?>
                    <?php if($me['nama_divisi']!=""){ ?>(<?= htmlspecialchars($me['nama_divisi']) ?>)<?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
    <h2 class="h5 fw-bold mb-0">Sisa Plafon Bulan Ini <span class="text-muted fw-normal fs-6">(<?= $label_bulan ?>)</span></h2>
    <span class="chip chip-grey">Reset otomatis <?= $label_reset ?></span>
</div>

<div class="row g-3 mb-4">
    <?php
        $ada_plafon=false;
        while($k=mysqli_fetch_assoc($sql_kat)){
            $ada_plafon=true;
            $s=sisa_plafon($conn,$id_user,$jabatan_id,(int)$k['id'],$bulan_ini);
            $persen=($s['kuota']>0) ? min(100,$s['terpakai']/$s['kuota']*100) : 0;
            $kelas_bar="";
            if($persen>=100){
                $kelas_bar="full";
            }elseif($persen>=80){
                $kelas_bar="warn";
            }
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="plafon-card">
            <div class="d-flex align-items-center gap-2">
                <span class="kat-ikon"><i class="bi <?= htmlspecialchars((string)$k['ikon']) ?>"></i></span>
                <span class="fw-semibold"><?= htmlspecialchars($k['nama']) ?></span>
            </div>
            <div class="plafon-sisa"><?= rupiah(max(0,$s['sisa'])) ?></div>
            <div class="plafon-dari">Sisa dari <?= rupiah($s['kuota']) ?></div>
            <div class="bar"><span class="<?= $kelas_bar ?>" style="width: <?= number_format($persen,1,".","") ?>%;"></span></div>
            <div class="plafon-pakai">Terpakai <?= number_format($persen,1,",",".") ?>%</div>
        </div>
    </div>
    <?php
        }
        if(!$ada_plafon){
    ?>
    <div class="col-12">
        <div class="info-box peringatan">Belum ada plafon yang berlaku untuk jabatan Anda. Hubungi admin HR.</div>
    </div>
    <?php
        }
    ?>
</div>

<div class="panel mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-0">Daftar Pengajuan</h2>
            <div class="sub-teks">Pantau status persetujuan dan pembayaran klaim Anda.</div>
        </div>
        <a href="klaim_form.php" class="btn btn-main"><i class="bi bi-plus-lg"></i> Buat Klaim Baru</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>No / Tanggal</th>
                    <th>Kategori &amp; Keterangan</th>
                    <th class="text-end">Nominal</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $ada_klaim=false;
                    while($c=mysqli_fetch_assoc($sql)){
                        $ada_klaim=true;
                ?>
                <tr>
                    <td>
                        <div class="kode-klaim">#<?= htmlspecialchars((string)$c['kode_klaim']) ?></div>
                        <div class="sub-teks"><?= tgl_indo($c['tanggal_transaksi']) ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_kategori']) ?></div>
                        <div class="sub-teks ket-singkat"><?= htmlspecialchars($c['keterangan']) ?></div>
                    </td>
                    <td class="text-end fw-semibold text-nowrap"><?= rupiah($c['nominal']) ?></td>
                    <td><?= chip_status($c['status']) ?></td>
                    <td class="text-end">
                        <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Detail</a>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada_klaim){
                ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan. Klik "Buat Klaim Baru" untuk memulai.</td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>

    <?php if($total>0){ ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <div class="sub-teks">
            Menampilkan <?= $offset+1 ?>-<?= min($offset+$per_hal,$total) ?> dari <?= $total ?> klaim
        </div>
        <nav aria-label="Halaman klaim">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $hal<=1?'disabled':'' ?>">
                    <a class="page-link" href="dashboard.php?hal=<?= $hal-1 ?>">Sebelumnya</a>
                </li>
                <?php for($i=1;$i<=$total_hal;$i++){ ?>
                    <li class="page-item <?= $i==$hal?'active':'' ?>">
                        <a class="page-link" href="dashboard.php?hal=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
                <li class="page-item <?= $hal>=$total_hal?'disabled':'' ?>">
                    <a class="page-link" href="dashboard.php?hal=<?= $hal+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php } ?>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="panel h-100">
            <h2>Ketentuan Pengajuan</h2>
            <ul class="mb-0 ps-3 small">
                <li>Klaim diajukan maksimal <?= $batas_hari ?> hari setelah tanggal transaksi.</li>
                <li>Nota wajib diunggah (JPG, PNG, atau PDF, maksimal 2 MB).</li>
                <li>Klaim yang melebihi sisa plafon pada bulan transaksi tidak bisa diajukan.</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel h-100">
            <h2>Alur Klaim</h2>
            <ul class="mb-0 ps-3 small">
                <li>Anda mengajukan klaim, lalu atasan memeriksa dan menyetujui.</li>
                <li>Setelah disetujui atasan, HR/Finance memverifikasi nota dan melakukan pembayaran.</li>
                <li>Klaim yang ditolak bisa direvisi dan diajukan ulang.</li>
            </ul>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
