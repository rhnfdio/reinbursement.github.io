<?php
    date_default_timezone_set("Asia/Jakarta");

    function rupiah($angka){
        return "Rp ".number_format($angka,0,",",".");
    }

    function tgl_indo($tanggal,$dengan_jam=false){
        $bulan=array("","Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des");
        $t=strtotime($tanggal);
        $hasil=date("j",$t)." ".$bulan[(int)date("n",$t)]." ".date("Y",$t);
        if($dengan_jam){
            $hasil.=", ".date("H:i",$t);
        }
        return $hasil;
    }

    function chip_status($status,$teks_saja=false){
        $peta=array(
            "draft"=>array("Draft","chip-grey"),
            "diajukan"=>array("Menunggu Persetujuan","chip-amber"),
            "menunggu_pembayaran"=>array("Menunggu Pembayaran","chip-blue"),
            "dibayar"=>array("Dibayar","chip-green"),
            "ditolak"=>array("Ditolak","chip-red")
        );
        if(!isset($peta[$status])){
            return htmlspecialchars($status);
        }
        if($teks_saja){
            return $peta[$status][0];
        }
        return '<span class="chip '.$peta[$status][1].'">'.$peta[$status][0].'</span>';
    }

    function sisa_plafon($conn,$user_id,$jabatan_id,$kategori_id,$bulan,$kecuali_id=0){
        $q=mysqli_query($conn,"SELECT kuota_bulanan FROM plafon WHERE jabatan_id=$jabatan_id AND kategori_id=$kategori_id");
        if(mysqli_num_rows($q)==0){
            return null;
        }
        $kuota=(int)mysqli_fetch_assoc($q)['kuota_bulanan'];

        $awal=$bulan."-01";
        $akhir=date("Y-m-t",strtotime($awal));

        $q2=mysqli_query($conn,"SELECT COALESCE(SUM(nominal),0) AS total FROM klaim
            WHERE user_id=$user_id AND kategori_id=$kategori_id
            AND status IN ('diajukan','menunggu_pembayaran','dibayar')
            AND tanggal_transaksi BETWEEN '$awal' AND '$akhir'
            AND id!=$kecuali_id");
        $terpakai=(int)mysqli_fetch_assoc($q2)['total'];

        return array("kuota"=>$kuota,"terpakai"=>$terpakai,"sisa"=>$kuota-$terpakai);
    }

    function catat_log($conn,$klaim_id,$aktor_id,$lama,$baru,$catatan){
        if($lama===null){
            $lama_sql="NULL";
        }else{
            $lama_sql="'".mysqli_real_escape_string($conn,$lama)."'";
        }
        $baru_db=mysqli_real_escape_string($conn,$baru);
        $catatan_db=mysqli_real_escape_string($conn,$catatan);
        $sekarang=date("Y-m-d H:i:s");
        mysqli_query($conn,"INSERT INTO klaim_log (klaim_id,aktor_id,status_lama,status_baru,catatan,created_at)
            VALUES ($klaim_id,$aktor_id,$lama_sql,'$baru_db','$catatan_db','$sekarang')");
    }

    function rentang_kuartal($tahun,$kuartal){
        $awal=date("Y-m-d",mktime(0,0,0,($kuartal-1)*3+1,1,$tahun));
        $akhir=date("Y-m-t",mktime(0,0,0,$kuartal*3,1,$tahun));
        return array($awal,$akhir);
    }

    function info_anggaran($conn,$tahun,$kuartal){
        $q=mysqli_query($conn,"SELECT total_anggaran FROM anggaran WHERE tahun=$tahun AND kuartal=$kuartal");
        if(mysqli_num_rows($q)==0){
            return null;
        }
        $total=(int)mysqli_fetch_assoc($q)['total_anggaran'];
        list($awal,$akhir)=rentang_kuartal($tahun,$kuartal);
        $terpakai=(int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(nominal),0) AS n FROM klaim
            WHERE status='dibayar' AND tanggal_bayar BETWEEN '$awal' AND '$akhir'"))['n'];
        $sisa=$total-$terpakai;
        $persen_sisa=($total>0) ? ($sisa/$total*100) : 0;
        return array("total"=>$total,"terpakai"=>$terpakai,"sisa"=>$sisa,"persen_sisa"=>$persen_sisa);
    }
?>
