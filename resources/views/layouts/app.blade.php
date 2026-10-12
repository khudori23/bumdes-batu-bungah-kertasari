@php $set = \App\Models\Setting::semua(); @endphp<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', $set['app_name'])</title>
    @if ($set['favicon_path'])<link rel="icon" href="{{ asset('storage/' . $set['favicon_path']) }}">@endif
    <style>:root { --hdr:{{ $set['warna_header'] }}; --accent:{{ $set['warna_aksen'] }}; }</style>
    <link rel="stylesheet" href="{{ asset('css/ui.css') }}?v={{ @filemtime(public_path('css/ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ @filemtime(public_path('css/responsive.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tema.css') }}?v={{ @filemtime(public_path('css/tema.css')) }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#065325">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
</head>
<body class="{{ request()->routeIs('kasir.create') ? 'no-bn' : '' }}">
    @php
        $u = auth()->user();
        $bnItems = \App\Support\Menu::bawah($u);
        $pegawai = $u->roleName() === 'PEGAWAI';
    @endphp

    <nav class="bn" aria-label="Navigasi utama">
        @foreach ($bnItems as $i)
            @php $on = \App\Support\Menu::aktif($i); @endphp
            <a href="{{ route($i['r']) }}" class="{{ $on ? 'on' : '' }}" @if ($on) aria-current="page" @endif>
                <x-ikon :nama="$i['i']" /><span>{{ $i['bn'] ?? $i['t'] }}</span>
            </a>
        @endforeach
        @unless ($pegawai)
            <a href="{{ route('menu.index') }}" class="{{ request()->routeIs('menu.*') ? 'on' : '' }}">
                <x-ikon nama="squares-four" /><span>Menu</span>
            </a>
        @endunless
    </nav>

    <div class="app-body">
        <header class="topbar">
            <a class="topbar-brand" href="{{ route('dashboard') }}" aria-label="Beranda">
                @if ($set['logo_path'])<img class="topbar-logo" src="{{ asset('storage/' . $set['logo_path']) }}" alt="">@endif
            </a>
            <div class="topbar-title">@yield('title', $set['app_name'])</div>
            <nav class="topnav" aria-label="Menu utama">
                @if ($pegawai)
                    <a href="{{ route('absen.index') }}" class="{{ request()->routeIs('absen.*') ? 'on' : '' }}">Absen</a>
                    <a href="{{ route('akun.password') }}" class="{{ request()->routeIs('akun.*') ? 'on' : '' }}">Akun</a>
                @else
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'on' : '' }}">Beranda</a>
                    <a href="{{ route('menu.index') }}" class="{{ request()->routeIs('menu.*') ? 'on' : '' }}">Semua Menu</a>
                @endif
            </nav>
            <div class="topbar-user">{{ $u->username }}</div>
        </header>
        <main>
            @if (session('status') || session('success'))
                <div class="ok">{{ session('status') ?? session('success') }}</div>
            @endif
            @error('hapus')
                <div class="ok" style="background:#fff1f2;border-color:#fecdd3;color:#9f1239">{{ $message }}</div>
            @enderror
            @yield('content')
        </main>
    </div>

    <script>
    /* Bungkus tiap tabel di dalam main supaya bisa digeser ke samping di layar sempit. */
    (function () {
        document.querySelectorAll('main table').forEach(function (t) {
            var p = t.parentNode;
            if (! p || p.classList.contains('tbl-scroll')) { return; }
            if (/(auto|scroll)/.test(getComputedStyle(p).overflowX)) { return; }
            var w = document.createElement('div');
            w.className = 'tbl-scroll';
            p.insertBefore(w, t);
            w.appendChild(t);
        });
    })();
    </script>
    @include('partials.kembali')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js'); });
        }
    </script>
</body>
</html>