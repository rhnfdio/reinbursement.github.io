<?php
    require "cek_admin.php";

    $id_admin=(int)$_SESSION['id_user'];

    if(isset($_POST['toggle'])){
        $id=(int)$_POST['id'];
        $q=mysqli_query($conn,"SELECT id, nama, aktif FROM users WHERE id=$id");
        if(mysqli_num_rows($q)==0){
            $_SESSION['pesan_error']="Pengguna tidak ditemukan";
        }elseif($id==$id_admin){
            $_SESSION['pesan_error']="Anda tidak bisa menonaktifkan akun sendiri";
        }else{
            $u=mysqli_fetch_assoc($q);
            $jml_bawahan=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users WHERE atasan_id=$id AND aktif=1"))['n'];
            if($u['aktif']==1 && $jml_bawahan>0){
                $_SESSION['pesan_error']="Akun ".$u['nama']." masih menjadi atasan dari $jml_bawahan karyawan aktif. Pindahkan atasan mereka dulu.";
            }else{
                mysqli_query($conn,"UPDATE users SET aktif=1-aktif WHERE id=$id");
                $_SESSION['pesan']="Status akun ".$u['nama']." berhasil diubah";
            }
        }
        header("Location: users.php");
        exit;
    }

    $role_ok=array("karyawan","atasan","admin");
    $f_role="";
    $f_divisi=0;
    $f_cari="";
    if(isset($_GET['role']) && in_array($_GET['role'],$role_ok,true)){
        $f_role=$_GET['role'];
    }
    if(isset($_GET['divisi'])){
        $f_divisi=(int)$_GET['divisi'];
    }
    if(isset($_GET['cari'])){
        $f_cari=trim($_GET['cari']);
    }

    $where="1=1";
    if($f_role!=""){
        $where.=" AND u.role='$f_role'";
    }
    if($f_divisi>0){
        $where.=" AND u.divisi_id=$f_divisi";
    }
    if($f_cari!=""){
        $c=mysqli_real_escape_string($conn,$f_cari);
        $where.=" AND (u.nama LIKE '%$c%' OR u.nik LIKE '%$c%' OR u.email LIKE '%$c%')";
    }
    $qs=http_build_query(array("role"=>$f_role,"divisi"=>($f_divisi>0 ? $f_divisi : ""),"cari"=>$f_cari));

    $sql_divisi=mysqli_query($conn,"SELECT * FROM divisi ORDER BY nama_divisi");

    $per_hal=10;
    $total=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM users u WHERE $where"))['n'];
    $total_hal=max(1,(int)ceil($total/$per_hal));
    $hal=isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    if($hal<1){ $hal=1; }
    if($hal>$total_hal){ $hal=$total_hal; }
    $offset=($hal-1)*$per_hal;

    $sql=mysqli_query($conn,"SELECT u.*, j.nama_jabatan, d.nama_divisi, a.nama AS nama_atasan
        FROM users u
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        LEFT JOIN users a ON u.atasan_id=a.id
        WHERE $where
        ORDER BY u.nama
        LIMIT $per_hal OFFSET $offset");

    $judul="Pengguna";
    $halaman="users";
    include "header.php";
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="page-title">
        <h1>Pengguna</h1>
        <p>Kelola akun karyawan, atasan, dan admin. Akun tidak dihapus agar riwayat klaim tetap utuh.</p>
    </div>
    <a href="user_form.php" class="btn btn-main"><i class="bi bi-plus-lg"></i> Tambah Pengguna</a>
</div>

<div class="panel">

    <form action="users.php" method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label for="cari" class="form-label small">Cari</label>
            <input type="text" name="cari" id="cari" class="form-control form-control-sm" value="<?= htmlspecialchars($f_cari) ?>" placeholder="Nama, NIK, atau email">
        </div>
        <div class="col-md-3">
            <label for="role" class="form-label small">Peran</label>
            <select name="role" id="role" class="form-select form-select-sm">
                <option value="">Semua peran</option>
                <?php foreach($role_ok as $r){ ?>
                    <option value="<?= $r ?>" <?= $f_role==$r?'selected':'' ?>><?= ucfirst($r) ?></option>
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
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-main btn-sm">Cari</button>
            <a href="users.php" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle table-clean">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Divisi / Jabatan</th>
                    <th>Atasan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $ada=false;
                    while($u=mysqli_fetch_assoc($sql)){
                        $ada=true;
                        $kelas_role="chip-grey";
                        if($u['role']=="admin"){
                            $kelas_role="chip-blue";
                        }elseif($u['role']=="atasan"){
                            $kelas_role="chip-amber";
                        }
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($u['nama']) ?></div>
                        <div class="sub-teks"><?= htmlspecialchars($u['email']) ?> &middot; NIK <?= htmlspecialchars($u['nik']) ?></div>
                    </td>
                    <td><span class="chip <?= $kelas_role ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td>
                        <div><?= $u['nama_divisi']!="" ? htmlspecialchars($u['nama_divisi']) : "-" ?></div>
                        <div class="sub-teks"><?= $u['nama_jabatan']!="" ? htmlspecialchars($u['nama_jabatan']) : "-" ?></div>
                    </td>
                    <td><?= $u['nama_atasan']!="" ? htmlspecialchars($u['nama_atasan']) : "-" ?></td>
                    <td>
                        <?php if($u['aktif']==1){ ?>
                            <span class="chip chip-green">Aktif</span>
                        <?php }else{ ?>
                            <span class="chip chip-grey">Nonaktif</span>
                        <?php } ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <?php if($u['id']!=$id_admin){ ?>
                            <form action="users.php" method="POST" class="d-inline" onsubmit="return confirm('Ubah status akun ini?')">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" name="toggle" class="btn btn-ghost btn-sm"><?= $u['aktif']==1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
                <?php
                    }
                    if(!$ada){
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Tidak ada pengguna yang sesuai.</td>
                </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>

    <?php if($total>0){ ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <div class="sub-teks">Menampilkan <?= $offset+1 ?>-<?= min($offset+$per_hal,$total) ?> dari <?= $total ?> pengguna</div>
        <nav aria-label="Halaman pengguna">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $hal<=1?'disabled':'' ?>">
                    <a class="page-link" href="users.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $hal-1 ?>">Sebelumnya</a>
                </li>
                <?php for($i=1;$i<=$total_hal;$i++){ ?>
                    <li class="page-item <?= $i==$hal?'active':'' ?>">
                        <a class="page-link" href="users.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php } ?>
                <li class="page-item <?= $hal>=$total_hal?'disabled':'' ?>">
                    <a class="page-link" href="users.php?<?= htmlspecialchars($qs) ?>&amp;hal=<?= $hal+1 ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
