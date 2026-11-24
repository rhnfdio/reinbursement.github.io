<?php
    require "cek_karyawan.php";

    $error="";
    $id=0;
    if(isset($_POST['id'])){
        $id=(int)$_POST['id'];
    }elseif(isset($_GET['id'])){
        $id=(int)$_GET['id'];
    }

    $kategori_id=0;
    $tanggal=$hari_ini;
    $nominal="";
    $keterangan="";
    $file_nota="";
    $status_lama="";
    $alasan="";

    if($id>0){
        $q=mysqli_query($conn,"SELECT * FROM klaim WHERE id=$id AND user_id=$id_user AND status IN ('draft','ditolak')");
        if(mysqli_num_rows($q)==0){
            $_SESSION['pesan_error']="Klaim tidak ditemukan atau tidak bisa diedit";
            header("Location: dashboard.php");
            exit;
        }
        $lama=mysqli_fetch_assoc($q);
        $kategori_id=(int)$lama['kategori_id'];
        $tanggal=$lama['tanggal_transaksi'];
        $nominal=$lama['nominal'];
        $keterangan=$lama['keterangan'];
        $file_nota=(string)$lama['file_nota'];
        $status_lama=$lama['status'];
        $alasan=(string)$lama['alasan_penolakan'];
    }

    $daftar_kategori=array();
    $sql_kat=mysqli_query($conn,"SELECT k.id, k.nama
        FROM kategori_klaim k
        INNER JOIN plafon p ON p.kategori_id=k.id
        WHERE k.aktif=1 AND p.jabatan_id=$jabatan_id
        ORDER BY k.id");
    while($k=mysqli_fetch_assoc($sql_kat)){
        $daftar_kategori[(int)$k['id']]=$k['nama'];
    }

    if(isset($_POST['aksi'])){
        $aksi=$_POST['aksi'];
        $kategori_id=(int)$_POST['kategori_id'];
        $tanggal=trim($_POST['tanggal']);
        $nominal=trim($_POST['nominal']);
        $keterangan=trim($_POST['keterangan']);
        $nama_file_baru="";

        $tgl_obj=DateTime::createFromFormat("Y-m-d",$tanggal);
        $tgl_ok=($tgl_obj && $tgl_obj->format("Y-m-d")==$tanggal);
        if(!isset($daftar_kategori[$kategori_id])){
            $error="Pilih kategori pengeluaran";
        }elseif(!$tgl_ok){
            $error="Tanggal transaksi tidak valid";
        }elseif($tanggal>$hari_ini){
            $error="Tanggal transaksi tidak boleh di masa depan";
        }elseif($tanggal<$tanggal_min){
            $error="Tanggal transaksi maksimal $batas_hari hari ke belakang dari hari ini";
        }elseif(!ctype_digit($nominal) || (int)$nominal<=0){
            $error="Nominal harus berupa angka bulat lebih dari 0 (tanpa titik atau koma)";
        }elseif((int)$nominal>1000000000){
            $error="Nominal terlalu besar";
        }elseif($keterangan==""){
            $error="Keterangan pengeluaran wajib diisi";
        }elseif(mb_strlen($keterangan)>200){
            $error="Keterangan maksimal 200 karakter";
        }

        if($error==""){
            $ada_file=(isset($_FILES['nota']) && $_FILES['nota']['error']!=UPLOAD_ERR_NO_FILE);
            if($ada_file){
                $f=$_FILES['nota'];
                if($f['error']==UPLOAD_ERR_INI_SIZE || $f['error']==UPLOAD_ERR_FORM_SIZE || $f['size']>2*1024*1024){
                    $error="Ukuran nota maksimal 2 MB";
                }elseif($f['error']!=UPLOAD_ERR_OK){
                    $error="Upload nota gagal, silakan coba lagi";
                }else{
                    $finfo=finfo_open(FILEINFO_MIME_TYPE);
                    $mime=finfo_file($finfo,$f['tmp_name']);
                    finfo_close($finfo);

                    $tipe_ok=array("image/jpeg"=>"jpg","image/png"=>"png","application/pdf"=>"pdf");
                    if(!isset($tipe_ok[$mime])){
                        $error="Format nota harus JPG, PNG, atau PDF";
                    }else{
                        $nama_file_baru="nota_".$id_user."_".uniqid().".".$tipe_ok[$mime];
                    }
                }
            }elseif($file_nota==""){
                $error="Foto nota wajib diunggah";
            }
        }
        if($error=="" && $aksi=="ajukan"){
            if($me['atasan_id']==""){
                $error="Atasan langsung Anda belum ditentukan, jadi klaim belum bisa diajukan. Hubungi admin HR, atau simpan sebagai draft dulu.";
            }else{
                $s=sisa_plafon($conn,$id_user,$jabatan_id,$kategori_id,substr($tanggal,0,7),$id);
                if($s===null){
                    $error="Tidak ada aturan plafon untuk kategori ini";
                }elseif((int)$nominal>$s['sisa']){
                    $error="Nominal melebihi sisa plafon ".$daftar_kategori[$kategori_id]." pada bulan transaksi (sisa ".rupiah(max(0,$s['sisa'])).")";
                }
            }
        }
        if($error=="" && $nama_file_baru!=""){
            if(move_uploaded_file($f['tmp_name'],"../uploads/nota/".$nama_file_baru)){
                if($file_nota!="" && file_exists("../uploads/nota/".$file_nota)){
                    unlink("../uploads/nota/".$file_nota);
                }
                $file_nota=$nama_file_baru;
            }else{
                $error="Gagal menyimpan file nota";
            }
        }
        if($error==""){
            $ket_db=mysqli_real_escape_string($conn,$keterangan);
            $file_db=mysqli_real_escape_string($conn,$file_nota);
            $nominal_db=(int)$nominal;
            $sekarang=date("Y-m-d H:i:s");

            if($aksi=="ajukan"){
                $status_baru="diajukan";
                $tgl_ajukan="'$sekarang'";
            }else{
                $status_baru="draft";
                $tgl_ajukan="NULL";
            }

            if($id==0){
                $simpan=mysqli_query($conn,"INSERT INTO klaim (user_id,kategori_id,tanggal_transaksi,nominal,keterangan,file_nota,status,tanggal_diajukan)
                    VALUES ($id_user,$kategori_id,'$tanggal',$nominal_db,'$ket_db','$file_db','$status_baru',$tgl_ajukan)");
                if($simpan){
                    $id=mysqli_insert_id($conn);
                    $kode=sprintf("CLM-%s-%03d",date("Y"),$id);
                    mysqli_query($conn,"UPDATE klaim SET kode_klaim='$kode' WHERE id=$id");

                    catat_log($conn,$id,$id_user,null,"draft","Klaim dibuat");
                    if($aksi=="ajukan"){
                        catat_log($conn,$id,$id_user,"draft","diajukan","Klaim diajukan");
                    }
                }
            }else{
                $simpan=mysqli_query($conn,"UPDATE klaim SET kategori_id=$kategori_id, tanggal_transaksi='$tanggal', nominal=$nominal_db,
                    keterangan='$ket_db', file_nota='$file_db', status='$status_baru', alasan_penolakan=NULL, tanggal_diajukan=$tgl_ajukan
                    WHERE id=$id AND user_id=$id_user");
                if($simpan){
                    if($aksi=="ajukan"){
                        if($status_lama=="ditolak"){
                            catat_log($conn,$id,$id_user,"ditolak","diajukan","Diajukan ulang setelah revisi");
                        }else{
                            catat_log($conn,$id,$id_user,"draft","diajukan","Klaim diajukan");
                        }
                    }elseif($status_lama=="ditolak"){
                        catat_log($conn,$id,$id_user,"ditolak","draft","Klaim direvisi, disimpan sebagai draft");
                    }
                }
            }
            if($simpan){
                if($aksi=="ajukan"){
                    $_SESSION['pesan']="Klaim berhasil diajukan dan menunggu persetujuan atasan";
                }else{
                    $_SESSION['pesan']="Draft klaim berhasil disimpan";
                }
                header("Location: klaim_detail.php?id=$id");
                exit;
            }else{
                $error="Gagal menyimpan data klaim";
            }
        }
    }
    $bulan_list=array(
        date("Y-m"),
        date("Y-m",strtotime("first day of -1 month")),
        date("Y-m",strtotime("first day of -2 months"))
    );
    $data_plafon=array();
    foreach($daftar_kategori as $kid=>$knama){
        foreach($bulan_list as $b){
            $s=sisa_plafon($conn,$id_user,$jabatan_id,$kid,$b,$id);
            if($s!==null){
                $data_plafon[$kid][$b]=$s;
            }
        }
    }

    $judul=$id>0 ? "Edit Klaim" : "Ajukan Klaim";
    $halaman="klaim";
    include "header.php";
?>

<a href="dashboard.php" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>

<div class="row justify-content-center mt-3">
    <div class="col-lg-7">
        <div class="panel">

            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <h2 class="mb-0">Form Pengajuan Klaim Reimbursement</h2>
                <?php if($id==0){ ?>
                    <span class="chip chip-blue text-nowrap">Draft Baru</span>
                <?php }elseif($status_lama=="ditolak"){ ?>
                    <span class="chip chip-red text-nowrap">Revisi Klaim</span>
                <?php }else{ ?>
                    <span class="chip chip-grey text-nowrap">Edit Draft</span>
                <?php } ?>
            </div>
            <p class="text-muted small mb-4">Isi data pengeluaran dan lampirkan nota yang jelas.</p>

            <?php if($status_lama=="ditolak"){ ?>
                <div class="alert alert-warning">
                    <strong>Klaim ini ditolak.</strong> Alasan: <?= htmlspecialchars($alasan) ?>
                </div>
            <?php } ?>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <?php if(empty($daftar_kategori)){ ?>

                <div class="info-box peringatan">Belum ada kategori yang bisa Anda klaim. Hubungi admin HR untuk mengatur plafon jabatan Anda.</div>

            <?php }else{ ?>

            <form action="klaim_form.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="mb-3">
                    <label for="kategori_id" class="form-label">Kategori pengeluaran</label>
                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach($daftar_kategori as $kid=>$knama){ ?>
                            <option value="<?= $kid ?>" <?= $kategori_id==$kid?'selected':'' ?>><?= htmlspecialchars($knama) ?></option>
                        <?php } ?>
                    </select>
                    <div class="form-text" id="hint-plafon"></div>
                </div>

                <div class="mb-3">
                    <label for="tanggal" class="form-label">Tanggal transaksi</label>
                    <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" min="<?= $tanggal_min ?>" max="<?= $hari_ini ?>" required>
                    <div class="form-text">Maksimal <?= $batas_hari ?> hari ke belakang dari hari ini, tidak boleh memilih tanggal masa depan.</div>
                </div>

                <div class="mb-3">
                    <label for="nominal" class="form-label">Nominal biaya</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="nominal" name="nominal" value="<?= htmlspecialchars((string)$nominal) ?>" min="1" step="1" placeholder="contoh: 150000" required>
                    </div>
                    <div class="form-text">Masukkan angka tanpa titik atau koma.</div>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan pengeluaran</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3" maxlength="200" placeholder="contoh: Tiket kereta perjalanan dinas ke cabang Bandung" required><?= htmlspecialchars($keterangan) ?></textarea>
                    <div class="form-text text-end"><span id="hitung">0</span>/200</div>
                </div>

                <div class="mb-3">
                    <label for="nota" class="form-label">Foto nota / bukti struk</label>
                    <input type="file" class="form-control" id="nota" name="nota" accept=".jpg,.jpeg,.png,.pdf">
                    <div class="form-text">Format JPG, PNG, atau PDF. Maksimal 2 MB. Jika ada kesalahan pada form, pilih ulang file nota.</div>
                    <?php if($file_nota!=""){ ?>
                        <div class="form-text">Nota saat ini: <a href="../nota.php?id=<?= $id ?>" target="_blank">lihat nota</a>. Pilih file baru hanya jika ingin menggantinya.</div>
                    <?php } ?>
                </div>

                <?php if($me['atasan_id']!=""){ ?>
                    <div class="info-box mb-4">
                        Klaim yang diajukan akan diperiksa oleh <strong><?= htmlspecialchars($me['nama_atasan']) ?></strong>,
                        lalu diteruskan ke HR/Finance untuk pembayaran.
                    </div>
                <?php }else{ ?>
                    <div class="info-box peringatan mb-4">
                        Atasan langsung Anda belum ditentukan. Anda tetap bisa menyimpan draft, tetapi klaim baru bisa diajukan setelah atasan diatur oleh admin HR.
                    </div>
                <?php } ?>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="aksi" value="ajukan" class="btn btn-main">Ajukan Klaim</button>
                    <button type="submit" name="aksi" value="draft" class="btn btn-ghost">Simpan Draft</button>
                    <a href="dashboard.php" class="btn btn-ghost">Batal</a>
                </div>
            </form>

            <?php } ?>
        </div>
    </div>
</div>

<script>var dataPlafon = <?= json_encode($data_plafon) ?>;</script>
<script src="../assets/js/klaim.js"></script>

<?php include "footer.php"; ?>
