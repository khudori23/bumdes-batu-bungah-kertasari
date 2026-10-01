@php
    $tabs = [
        ['Gaji', route('gaji.index'), request()->routeIs('gaji.index', 'gaji.show')],
        ['Presensi', route('presensi.index'), request()->routeIs('presensi.*')],
        ['Karyawan', route('karyawan.index'), request()->routeIs('karyawan.*')],
        ['Kasbon', route('kasbon.index'), request()->routeIs('kasbon.*')],
        ['Pengaturan Slip', route('gaji.pengaturan'), request()->routeIs('gaji.pengaturan')],
        ['Pengaturan Bonus', route('gaji.bonus'), request()->routeIs('gaji.bonus*')],
        ['Laba Rugi', route('gaji.labarugi'), request()->routeIs('gaji.labarugi')],
    ];
@endphp
<div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem">
    @foreach ($tabs as [$label, $url, $on])
        <a href="{{ $url }}" style="padding:.4rem .9rem;border-radius:99px;text-decoration:none;font-size:.88rem;border:1px solid var(--line);{{ $on ? 'background:var(--hdr);color:#fff;border-color:var(--hdr)' : 'background:#fff;color:#334155' }}">{{ $label }}</a>
    @endforeach
</div>
@if ($errors->any())
    <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
        @foreach ($errors->all() as $e)
            <div class="err" style="margin:0">{{ $e }}</div>
        @endforeach
    </div>
@endif