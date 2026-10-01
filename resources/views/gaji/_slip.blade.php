@php
    $p = $d['p']; $e = $p->employee; $set = $d['set']; $c = $p->attendance_counts ?? [];
    $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $adaPot = (float) $p->loan_deduction_amount > 0 || (float) $p->other_deduction_amount > 0;
    $waktu = $p->finalized_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
@endphp
<section class="slip">
    @unless ($d['resmi'])<div class="wm">{{ $p->status === 'VOID' ? 'DIBATALKAN' : 'DRAFT' }}</div>@endunless

    <div class="kop">
        @if ($set['logo_path'])<img src="{{ asset('storage/' . $set['logo_path']) }}" alt="">@endif
        <div>
            <div class="nm">{{ $set['app_name'] }}</div>
            @if ($set['address'])<div class="sm">{{ $set['address'] }}</div>@endif
            @if ($set['phone'])<div class="sm">Telp. {{ $set['phone'] }}</div>@endif
        </div>
    </div>

    <div class="judul">SLIP GAJI</div>
    <div class="sub">Periode {{ $d['labelBulan'] }}@if ($p->payroll_number) &middot; No. {{ $p->payroll_number }}@endif</div>

    <table class="id">
        <tr><td>Nama</td><td>: <strong>{{ $e->name }}</strong></td></tr>
        <tr><td>Kode</td><td>: {{ $e->code }} ({{ $e->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }})</td></tr>
        @if ($e->position)<tr><td>Jabatan</td><td>: {{ $e->position }}</td></tr>@endif
    </table>

    <div class="dua">
        <div>
            <h4>Pendapatan</h4>
            <table class="rinci">
                @if ((float) $p->daily_rate > 0 || (float) $p->base_amount > 0)
                    <tr><td>Gaji harian<small>{{ $fmt($p->days_present) }} hari &times; {{ $rp($p->daily_rate) }}</small></td><td class="r">{{ $rp($p->base_amount) }}</td></tr>
                @endif
                @if ((float) $p->per_kg_rate > 0 || (float) $p->kg_amount > 0)
                    <tr><td>Borongan<small>{{ $fmt($p->kg_total) }} kg &times; {{ $rp($p->per_kg_rate) }}</small></td><td class="r">{{ $rp($p->kg_amount) }}</td></tr>
                @endif
                @if ((float) $p->rendemen_bonus_amount > 0)
                    <tr><td>Bonus rendemen<small>{{ $fmt($p->rendemen_bonus_kg) }} kg &times; {{ $rp((float) $p->rendemen_bonus_kg > 0 ? (float) $p->rendemen_bonus_amount / (float) $p->rendemen_bonus_kg : 0) }}</small></td><td class="r">{{ $rp($p->rendemen_bonus_amount) }}</td></tr>
                @endif
                @if ((float) $p->bonus_amount > 0)
                    <tr><td>Bonus<small>{{ $p->bonus_note }}</small></td><td class="r">{{ $rp($p->bonus_amount) }}</td></tr>
                @endif
            </table>
            <div class="tot"><span>Total</span><span>{{ $rp($p->gross_amount) }}</span></div>
        </div>
        <div>
            <h4>Potongan</h4>
            <table class="rinci">
                @if ((float) $p->loan_deduction_amount > 0)
                    <tr><td>Kasbon</td><td class="r">{{ $rp($p->loan_deduction_amount) }}</td></tr>
                @endif
                @if ((float) $p->other_deduction_amount > 0)
                    <tr><td>Potongan lain<small>{{ $p->other_deduction_note }}</small></td><td class="r">{{ $rp($p->other_deduction_amount) }}</td></tr>
                @endif
                @unless ($adaPot)
                    <tr><td colspan="2" style="color:#94a3b8">Tidak ada potongan</td></tr>
                @endunless
            </table>
            <div class="tot"><span>Total</span><span>{{ $rp((float) $p->other_deduction_amount + (float) $p->loan_deduction_amount) }}</span></div>
        </div>
    </div>

    <div class="net"><span>GAJI DITERIMA</span><strong>{{ $rp($p->net_amount) }}</strong></div>
    <div class="tb">Terbilang: <em>{{ $d['terbilang'] }} rupiah</em></div>
    <div class="hadir">
        Kehadiran: Hadir {{ $c['hadir'] ?? 0 }} &middot; Setengah {{ $c['setengah'] ?? 0 }} &middot; Izin {{ $c['izin'] ?? 0 }} &middot; Sakit {{ $c['sakit'] ?? 0 }} &middot; Alfa {{ $c['alfa'] ?? 0 }}
        @if ($d['sisaKasbon'] > 0.004) &middot; Sisa kasbon {{ $rp($d['sisaKasbon']) }}@endif
    </div>

    <div class="ttd">
        <div>Penerima,<div class="sp"></div><div class="nama">{{ $e->name }}</div></div>
        <div>Dibuat oleh,<div class="sp"></div><div class="nama">{{ $p->finalizedBy?->name ?? 'Admin' }}</div><div class="jab">Admin</div></div>
        <div>Disetujui oleh,
            <div class="sp">@if ($d['resmi'] && $d['ttd'])<img src="{{ route('gaji.ttd') }}" alt="Tanda tangan">@endif</div>
            <div class="nama">{{ ($set['slip_manager_name'] ?? '') ?: '(.....................)' }}</div>
            <div class="jab">{{ $set['slip_manager_title'] ?? 'Manager' }}</div>
        </div>
    </div>

    <div class="ft">
        @if ($d['resmi'])Diterbitkan secara elektronik{{ $waktu ? ' pada ' . $waktu : '' }} &middot; Kode verifikasi: <strong>{{ $d['kode'] }}</strong>@else Dokumen ini belum sah (belum diterbitkan)@endif
        @if (! empty($set['slip_note']))<br>{{ $set['slip_note'] }}@endif
    </div>
</section>