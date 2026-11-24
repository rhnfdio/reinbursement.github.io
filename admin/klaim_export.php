<?php
    require "cek_admin.php";
    include "filter_klaim.php";

    function aman_csv($teks){
        $teks=(string)$teks;
        if($teks!="" && in_array($teks[0],array("=","+","-","@"))){
            return "'".$teks;
        }
        return $teks;
    }

    $sql=mysqli_query($conn,"SELECT k.*, kt.nama AS nama_kategori, u.nik, u.nama AS nama_karyawan, j.nama_jabatan, d.nama_divisi
        FROM klaim k
        INNER JOIN kategori_klaim kt ON k.kategori_id=kt.id
        INNER JOIN users u ON k.user_id=u.id
        LEFT JOIN jabatan j ON u.jabatan_id=j.id
        LEFT JOIN divisi d ON u.divisi_id=d.id
        WHERE $where
        ORDER BY k.id");

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=klaim_".date("Ymd_His").".csv");

    $out=fopen("php://output","w");
    fwrite($out,"\xEF\xBB\xBF");

    fputcsv($out,array("Kode","Tanggal Transaksi","Tanggal Diajukan","NIK","Nama","Divisi","Jabatan","Kategori","Keterangan","Nominal","Status","Tanggal Dibayar","Catatan Pembayaran"),";",'"',"");

    while($c=mysqli_fetch_assoc($sql)){
        fputcsv($out,array(
            $c['kode_klaim'],
            $c['tanggal_transaksi'],
            $c['tanggal_diajukan'],
            aman_csv($c['nik']),
            aman_csv($c['nama_karyawan']),
            aman_csv($c['nama_divisi']),
            aman_csv($c['nama_jabatan']),
            aman_csv($c['nama_kategori']),
            aman_csv($c['keterangan']),
            $c['nominal'],
            chip_status($c['status'],true),
            $c['tanggal_bayar'],
            aman_csv($c['catatan_bayar'])
        ),";",'"',"");
    }

    fclose($out);
    exit;
?>
