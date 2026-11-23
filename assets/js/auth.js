document.querySelectorAll('.toggle-pw').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        var input = document.getElementById(tombol.dataset.target);
        var ikon = tombol.querySelector('.bi');
        var tampil = input.type === 'password';

        input.type = tampil ? 'text' : 'password';
        ikon.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
        tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    });
});
