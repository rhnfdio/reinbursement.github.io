<?php
    require "cek_admin.php";

    $error="";
    $tahun=(int)date("Y");
    $kuartal=(int)ceil(date("n")/3);
    $total="";

    if(isset($_GET['edit'])){
        $id=(int)$_GET['edit'];
        $cek=mysqli_query($conn,"SELECT * FROM anggaran WHERE id=$id");
        if(mysqli_num_rows($cek)>0){
            $data=mysqli_fetch_assoc($cek);
            $tahun=(int)$data['tahun'];
            $kuartal=(int)$data['kuartal'];
            $total=$data['total_anggaran'];
        }
    }
    if(isset($_POST['simpan'])){
        $tahun=(int)$_POST['tahun'];
        $kuartal=(int)$_POST['kuartal'];
        $total=trim($_POST['total']);

        if($tahun<2020 || $tahun>2100){
            $error="Tahun tidak valid";
        }elseif($kuartal<1 || $kuartal>4){
            $error="Pilih kuartal 1 sampai 4";
        }elseif(!ctype_digit($total) || (int)$total<=0){
            $error="Anggaran harus berupa angka bulat lebih dari 0 (tanpa titik atau koma)";
        }elseif((int)$total>1000000000000){
            $error="Anggaran terlalu besar";
        }else{
            $total_db=(int)$total;
            $simpan=mysqli_query($conn,"INSERT INTO anggaran (tahun,kuartal,total_anggaran) VALUES ($tahun,$kuartal,$total_db)
                ON DUPLICATE KEY UPDATE total_anggaran=$total_db");
            if($simpan){
                $_SESSION['pesan']="Anggaran Q$kuartal $tahun berhasil disimpan";
                header("Location: anggaran.php");
                exit;
            }else{
                $error="Gagal menyimpan data";
            }
        }
    }

    $sql=mysqli_query($conn,"SELECT * FROM anggaran ORDER BY tahun DESC, kuartal DESC");

    $judul="Anggaran";
    $halaman="anggaran";
    include "header.php";
?>

<div class="page-title mb-4">
    <h1>Anggaran Reimbursement</h1>
    <p>Total anggaran per kuartal. Sisa anggaran dihitung dari klaim yang sudah dibayar.</p>
</div>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="panel">
            <h2>Atur anggaran</h2>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form action="anggaran.php" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="number" class="form-control" id="tahun" name="tahun" value="<?= $tahun ?>" min="2020" max="2100" required>
                    </div>
                    <div class="col-6">
                        <label for="kuartal" class="form-label">Kuartal</label>
                        <select class="form-select" id="kuartal" name="kuartal">
                            <?php for($i=1;$i<=4;$i++){ ?>
                                <option value="<?= $i ?>" <?= $kuartal==$i?'selected':'' ?>>Q<?= $i ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="total" class="form-label">Total anggaran</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="total" name="total" value="<?= htmlspecialchars((string)$total) ?>" min="1" step="1" placeholder="contoh: 60000000" required>
                    </div>
                    <div class="form-text">Jika tahun dan kuartal sudah ada, nilainya akan diperbarui.</div>
                </div>

                <button type="submit" name="simpan" class="btn btn-main">Simpan</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel">
            <h2>Daftar anggaran</h2>
            <div class="table-responsive">
                <table class="table align-middle table-clean">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th class="text-end">Anggaran</th>
                            <th class="text-end">Terpakai</th>
                            <th style="min-width: 150px;">Sisa</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $ada=false;
                            while($a=mysqli_fetch_assoc($sql)){
                                $ada=true;
                                $info=info_anggaran($conn,(int)$a['tahun'],(int)$a['kuartal']);
                                list($awal,$akhir)=rentang_kuartal((int)$a['tahun'],(int)$a['kuartal']);
                                $persen=max(0,min(100,$info['persen_sisa']));
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">Q<?= $a['kuartal'] ?> <?= $a['tahun'] ?></div>
                                <div class="sub-teks"><?= tgl_indo($awal) ?> - <?= tgl_indo($akhir) ?></div>
                            </td>
                            <td class="text-end text-nowrap"><?= rupiah($info['total']) ?></td>
                            <td class="text-end text-nowrap"><?= rupiah($info['terpakai']) ?></td>
                            <td>
                                <div class="small fw-semibold"><?= rupiah(max(0,$info['sisa'])) ?> (<?= number_format($persen,1,",",".") ?>%)</div>
                                <div class="bar mt-1"><span style="width: <?= number_format($persen,1,".","") ?>%;"></span></div>
                            </td>
                            <td class="text-end">
                                <a href="anggaran.php?edit=<?= $a['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                            </td>
                        </tr>
                        <?php
                            }
                            if(!$ada){
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada anggaran yang diatur.</td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>
