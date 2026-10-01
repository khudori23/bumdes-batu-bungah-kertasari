<script>
(function () {
    var KEY = 'nav_stack';
    function baca() { try { return JSON.parse(sessionStorage.getItem(KEY) || '[]'); } catch (e) { return []; } }
    function simpan(s) { try { sessionStorage.setItem(KEY, JSON.stringify(s)); } catch (e) {} }
    function sekarang() { return location.pathname + location.search; }

    function catat(mundur) {
        var s = baca(), c = sekarang();
        if (mundur && s.indexOf(c) !== -1) {
            s = s.slice(0, s.lastIndexOf(c) + 1);      // pengguna mundur lewat tombol bawaan HP
        } else if (s[s.length - 1] !== c) {
            s.push(c);
            if (s.length > 50) s.shift();
        }
        simpan(s);
    }

    var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    catat(nav && nav.type === 'back_forward');
    window.addEventListener('pageshow', function (e) { if (e.persisted) catat(true); });

    document.addEventListener('click', function (e) {
        var a = e.target.closest('[data-kembali]');
        if (!a) return;
        var s = baca(), c = sekarang();
        while (s.length && s[s.length - 1] === c) s.pop();
        var prev = s[s.length - 1];
        if (prev) {
            e.preventDefault();
            s.pop();
            simpan(s);
            location.href = prev;
        }
        // tanpa riwayat: tautan bawaan (Dashboard) yang dipakai
    });

    document.querySelectorAll('form[action$="/logout"]').forEach(function (f) {
        f.addEventListener('submit', function () { try { sessionStorage.removeItem(KEY); } catch (e) {} });
    });
})();
</script>