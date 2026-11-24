<?php
    $status_ok=array("diajukan","menunggu_pembayaran","dibayar","ditolak");

    $f_status="";
    $f_divisi=0;
    $f_kategori=0;
    $f_bulan="";

    if(isset($_GET['status']) && in_array($_GET['status'],$status_ok,true)){
        $f_status=$_GET['status'];
    }
    if(isset($_GET['divisi'])){
        $f_divisi=(int)$_GET['divisi'];
    }
    if(isset($_GET['kategori'])){
        $f_kategori=(int)$_GET['kategori'];
    }
    if(isset($_GET['bulan']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$_GET['bulan'])){
        $f_bulan=$_GET['bulan'];
    }

    $where="k.status!='draft'";
    if($f_status!=""){
        $where.=" AND k.status='$f_status'";
    }
    if($f_divisi>0){
        $where.=" AND u.divisi_id=$f_divisi";
    }
    if($f_kategori>0){
        $where.=" AND k.kategori_id=$f_kategori";
    }
    if($f_bulan!=""){
        $awal_bln=$f_bulan."-01";
        $akhir_bln=date("Y-m-t",strtotime($awal_bln));
        $where.=" AND k.tanggal_transaksi BETWEEN '$awal_bln' AND '$akhir_bln'";
    }

    $qs=http_build_query(array(
        "status"=>$f_status,
        "divisi"=>($f_divisi>0 ? $f_divisi : ""),
        "kategori"=>($f_kategori>0 ? $f_kategori : ""),
        "bulan"=>$f_bulan
    ));
?>
