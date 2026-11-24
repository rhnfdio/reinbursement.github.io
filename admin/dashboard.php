<?php
    require "cek_admin.php";

    $nama_bulan=array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
    $label_bulan=$nama_bulan[(int)date("n")]." ".date("Y");
    $awal_bulan=date("Y-m-01");
    $akhir_bulan=date("Y-m-t");

    $proses=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n, COALESCE(SUM(nominal),0) AS total
        FROM klaim WHERE status='menunggu_pembayaran'"));

    $dibayar_bulan=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(nominal),0) AS n FROM klaim
        WHERE status='dibayar' AND tanggal_bayar BETWEEN '$awal_bulan' AND '$akhir_bulan'"))['n'];

    $tahun=(int)date("Y");
    $kuartal=(int)ceil(date("n")/3);
    $anggaran=info_anggaran($conn,$tahun,$kuartal);

    $total_klaim=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM klaim WHERE status!='draft'"))['n'];

    $bulan_pendek=array(1=>"Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des");
    $grafik_label=array();
    $grafik_nilai=array();
    for($i=5;$i>=0;$i--){
        $t=strtotime("first day of -$i month");
        $g_awal=date("Y-m-01",$t);
        $g_akhir=date("Y-m-t",$t);
        $n=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(nominal),0) AS n FROM klaim
            WHERE status='dibayar' AND tanggal_bayar BETWEEN '$g_awal' AND '$g_akhir'"))['n'];
        $grafik_label[]=$bulan_pendek[(int)date("n",$t)]." ".date("y",$t);
        $grafik_nilai[]=$n;
    }

    $kat_label=array();
    $kat_nilai=array();
    $sql_kat=mysqli_query($conn,"SELECT kt.nama, SUM(k.nominal) AS total
        FROM klaim k INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        WHERE k.status IN ('diajukan','menunggu_pembayaran','dibayar')
        AND k.tanggal_transaksi BETWEEN '$awal_bulan' AND '$akhir_bulan'
        GROUP BY kt.id, kt.nama
        ORDER BY total DESC");
    while($g=mysqli_fetch_assoc($sql_kat)){
        $kat_label[]=$g['nama'];
        $kat_nilai[]=(int)$g['total'];
    }

    $sql=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, u.nama AS nama_karyawan, d.nama_divisi
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        INNER JOIN users u ON k.user_id=u.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        WHERE k.status='menunggu_pembayaran'
        ORDER BY k.id ASC
        LIMIT 5");

    $judul="Dashboard";
    $halaman="dashboard";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Dashboard HR &amp; Finance</h1>
    <p>Ringkasan klaim reimbursement, periode <?= $label_bulan ?>.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="stat-num"><?= $proses['n'] ?> <span class="fs-6 fw-normal text-muted">klaim</span></div>
            <div class="stat-label">Menunggu pembayaran (<?= rupiah($proses['total']) ?>)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-cash-coin"></i></span>
            <div class="stat-num" style="font-size: 1.4rem;"><?= rupiah($dibayar_bulan) ?></div>
            <div class="stat-label">Total dibayar bulan ini</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-piggy-bank"></i></span>
            <?php if($anggaran===null){ ?>
                <div class="stat-num" style="font-size: 1.4rem;">Belum diatur</div>
                <div class="stat-label">Anggaran Q<?= $kuartal ?> (<a href="anggaran.php">atur sekarang</a>)</div>
            <?php }else{ ?>
                <div class="stat-num"><?= number_format(max(0,$anggaran['persen_sisa']),1,",",".") ?>%</div>
                <div class="stat-label">Sisa anggaran Q<?= $kuartal ?> (<?= rupiah(max(0,$anggaran['sisa'])) ?>)</div>
            <?php } ?>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat">
            <span class="stat-icon"><i class="bi bi-receipt"></i></span>
            <div class="stat-num"><?= $total_klaim ?></div>
            <div class="stat-label">Total klaim diajukan</div>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-0">Perlu diproses</h2>
            <div class="sub-teks">Klaim yang sudah disetujui atasan dan menunggu pembayaran.</div>
        </div>
        <a href="klaim.php?status=menunggu_pembayaran" class="btn btn-ghost btn-sm">Lihat semua</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>Kode / Tanggal</th>
                    <th>Karyawan</th>
                    <th>Kategori</th>
                    <th class="text-end">Nominal</th>
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
                        <div class="sub-teks"><?= htmlspecialchars((string)$c['nama_divisi']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($c['nama_kategori']) ?></td>
                    <td class="text-end fw-semibold text-nowrap"><?= rupiah($c['nominal']) ?></td>
                    <td class="text-end">
                        <a href="klaim_detail.php?id=<?= $c['id'] ?>" class="btn btn-main btn-sm">Proses</a>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada){
                ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada klaim yang menunggu pembayaran.</td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="panel h-100">
            <h2>Pembayaran 6 bulan terakhir</h2>
            <div style="position: relative; height: 260px;"><canvas id="grafikBulan"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel h-100">
            <h2>Klaim per kategori, <?= $label_bulan ?></h2>
            <?php if(count($kat_nilai)>0){ ?>
                <div style="position: relative; height: 260px;"><canvas id="grafikKategori"></canvas></div>
            <?php }else{ ?>
                <p class="text-muted mb-0">Belum ada klaim pada bulan ini.</p>
            <?php } ?>
        </div>
    </div>
</div>

<div class="panel">
    <h2>Pengaturan</h2>
    <p class="text-muted">Atur jenis pengeluaran, batas nominal per jabatan, dan anggaran per kuartal.</p>
    <a href="kategori.php" class="btn btn-main">Kelola Kategori</a>
    <a href="plafon.php" class="btn btn-ghost ms-2">Kelola Plafon</a>
    <a href="anggaran.php" class="btn btn-ghost ms-2">Kelola Anggaran</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') { return; }

    var rp = function (n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };

    new Chart(document.getElementById('grafikBulan'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($grafik_label) ?>,
            datasets: [{ label: 'Total dibayar', data: <?= json_encode($grafik_nilai) ?>, backgroundColor: '#2459d8', borderRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return rp(c.parsed.y); } } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString('id-ID'); } } } }
        }
    });

    var elKat = document.getElementById('grafikKategori');
    if (elKat) {
        new Chart(elKat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($kat_label) ?>,
                datasets: [{ data: <?= json_encode($kat_nilai) ?>, backgroundColor: ['#2459d8', '#5b8def', '#9db8f5', '#e29a00', '#1b6338', '#a3261c'] }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (c) { return c.label + ': ' + rp(c.parsed); } } }
                }
            }
        });
    }
})();
</script>

<?php include "footer.php"; ?>
