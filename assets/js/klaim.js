var kategori = document.getElementById('kategori_id');
var tanggal  = document.getElementById('tanggal');
var hint     = document.getElementById('hint-plafon');
var ket      = document.getElementById('keterangan');
var hitung   = document.getElementById('hitung');

function rupiah(angka) {
    return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

function updateHint() {
    var k = kategori.value;
    var bulan = tanggal.value.substring(0, 7);

    if (!k || !bulan || !dataPlafon[k] || !dataPlafon[k][bulan]) {
        hint.textContent = '';
        return;
    }
    var d = dataPlafon[k][bulan];
    var nama = kategori.options[kategori.selectedIndex].text;
    hint.textContent = 'Sisa plafon ' + nama + ' pada bulan transaksi: ' +
                    rupiah(Math.max(0, d.sisa)) + ' dari ' + rupiah(d.kuota);
}

function updateHitung() {
    hitung.textContent = ket.value.length;
}

kategori.addEventListener('change', updateHint);
tanggal.addEventListener('change', updateHint);
ket.addEventListener('input', updateHitung);

updateHint();
updateHitung();
