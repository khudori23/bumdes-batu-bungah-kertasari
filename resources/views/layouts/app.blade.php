<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BUMDes Batu Bungah Kertasari')</title>
    <style>
        :root { --navy:#0f172a; --teal:#0d9488; --line:#e2e8f0; --bg:#f8fafc; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",sans-serif; background:var(--bg); color:var(--navy); }
        header { background:var(--navy); color:#fff; padding:.75rem 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
        header nav { display:flex; flex-wrap:wrap; align-items:center; gap:.2rem 0; }
        header a { color:#cbd5e1; margin-right:1rem; text-decoration:none; font-size:.9rem; }
        header form { display:inline; }
        header button { background:none; border:1px solid #475569; color:#e2e8f0; padding:.3rem .8rem; border-radius:6px; cursor:pointer; }
        main { max-width:960px; margin:2rem auto; padding:0 1.5rem; }
        .card { background:#fff; border:1px solid var(--line); border-radius:10px; padding:1.5rem; margin-bottom:1rem; }
        .ok { background:#ccfbf1; border:1px solid #99f6e4; padding:.6rem .9rem; border-radius:8px; margin-bottom:1rem; }
        label { display:block; font-size:.85rem; margin:.9rem 0 .3rem; }
        input[type=text], input[type=password], input[type=date] { width:100%; padding:.6rem .7rem; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; }
        .err { color:#e11d48; font-size:.85rem; margin-top:.25rem; }
        .btn { margin-top:1.2rem; background:var(--teal); color:#fff; border:0; padding:.65rem 1.2rem; border-radius:8px; font-size:1rem; cursor:pointer; display:inline-block; }

        /* Kasir */
        table { width:100%; border-collapse:collapse; }
        th, td { padding:.5rem .6rem; border-bottom:1px solid var(--line); text-align:left; }
        th { font-size:.8rem; color:#64748b; }
        select, input[type=number] { padding:.5rem; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem; width:100%; }
        .right { text-align:right; }

        /* Kandang & stok */
        .fgrid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0 .9rem; }
        .muted { color:#64748b; font-size:.85rem; }
        .chip { display:inline-flex; font-size:.74rem; font-weight:600; padding:.1rem .5rem; border-radius:99px; background:#f1f5f9; color:#64748b; }
        .chip.up { background:#ccfbf1; color:#0f766e; }
        .chip.down { background:#ffe4e6; color:#be123c; }
        .chip.warn { background:#fef3c7; color:#b45309; }
    </style>
</head>
<body>
    @php $u = auth()->user(); @endphp
    <header>
        <strong>BUMDes Batu Bungah Kertasari</strong>
        <nav>
            <a href="{{ route('dashboard') }}">Dashboard</a>

            @if ($u->canWrite('kasir'))
                <a href="{{ route('kasir.create') }}">Kasir</a>
            @endif
            @if ($u->canView('kasir'))
                <a href="{{ route('kasir.index') }}">Riwayat</a>
                <a href="{{ route('piutang.index') }}">Piutang</a>
            @endif

            @if ($u->canView('kandang'))
                <a href="{{ route('pembelian.index') }}">Pembelian</a>
                <a href="{{ route('pemasok.index') }}">Pemasok</a>
                <a href="{{ route('mortalitas.index') }}">Ayam Mati</a>
                <a href="{{ route('opnameayam.index') }}">Opname Ayam</a>
            @endif
            @if ($u->canView('produksi_input'))
                <a href="{{ route('produksi.index') }}">Produksi</a>
            @endif
            @if ($u->canView('opname_hasil'))
                <a href="{{ route('opnameproduk.index') }}">Opname Produk</a>
            @endif

            <a href="{{ route('akun.password') }}">Ganti Password</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar ({{ $u->username }})</button>
            </form>
        </nav>
    </header>
    <main>
        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>