<?php

namespace App\Support;

use App\Models\User;

/**
 * Satu-satunya daftar menu aplikasi. Dipakai halaman Semua Menu, grid beranda,
 * dan navigasi bawah. Aturan akses memakai canView/canWrite di model User,
 * jadi tidak ada aturan akses baru di sini.
 *
 * Kunci item: t = label, bn = label pendek (navigasi bawah), r = nama route,
 * i = nama ikon (resources/svg/ikon), on = pola route penanda aktif,
 * not = pola yang dikecualikan, ok = boleh tampil untuk pengguna ini.
 */
class Menu
{
    public static function semua(User $u): array
    {
        $kasir = $u->canWrite('kasir');
        $lihatKasir = $u->canView('kasir');
        $lihatKandang = $u->canView('kandang');
        $admin = $u->isAdmin();
        $manajemen = $admin || $u->isManager();

        return [
            'Penjualan' => [
                ['t' => 'Kasir', 'r' => 'kasir.create', 'i' => 'shopping-cart', 'on' => ['kasir.create'], 'ok' => $kasir],
                ['t' => 'Ayam Ecer', 'r' => 'kasir.ayamhidup.index', 'i' => 'bird', 'on' => ['kasir.ayamhidup.*'], 'ok' => $kasir],
                ['t' => 'Pesanan Dapur', 'bn' => 'Dapur', 'r' => 'kasir.dapur.index', 'i' => 'cooking-pot', 'on' => ['kasir.dapur.*'], 'ok' => $lihatKasir],
                ['t' => 'Riwayat', 'r' => 'kasir.index', 'i' => 'clock-counter-clockwise', 'on' => ['kasir.*'], 'not' => ['kasir.create', 'kasir.dapur.*', 'kasir.ayamhidup.*'], 'ok' => $lihatKasir],
                ['t' => 'Piutang', 'r' => 'piutang.index', 'i' => 'hand-coins', 'on' => ['piutang.*'], 'ok' => $lihatKasir],
                ['t' => 'Pelanggan', 'r' => 'pelanggan.index', 'i' => 'users', 'on' => ['pelanggan.*'], 'ok' => $lihatKasir],
            ],
            'Kandang & Produksi' => [
                ['t' => 'Pembelian', 'r' => 'pembelian.index', 'i' => 'package', 'on' => ['pembelian.*'], 'ok' => $lihatKandang],
                ['t' => 'Pemasok', 'r' => 'pemasok.index', 'i' => 'storefront', 'on' => ['pemasok.*'], 'ok' => $lihatKandang],
                ['t' => 'Opname Ayam', 'r' => 'opnameayam.index', 'i' => 'clipboard-text', 'on' => ['opnameayam.*', 'mortalitas.*'], 'ok' => $lihatKandang],
                ['t' => 'Ayam Keluar', 'bn' => 'Keluar', 'r' => 'ayamkeluar.index', 'i' => 'truck', 'on' => ['ayamkeluar.*'], 'ok' => $lihatKandang],
                ['t' => 'Riwayat Ayam', 'r' => 'riwayatayam.index', 'i' => 'list-checks', 'on' => ['riwayatayam.*'], 'ok' => $lihatKandang],
                ['t' => 'Produksi', 'r' => 'produksi.index', 'i' => 'factory', 'on' => ['produksi.*'], 'ok' => $u->canView('produksi_input')],
                ['t' => 'Opname Produk', 'r' => 'opnameproduk.index', 'i' => 'scales', 'on' => ['opnameproduk.*'], 'ok' => $u->canView('opname_hasil')],
            ],
            'Data & Laporan' => [
                ['t' => 'Produk', 'r' => 'produk.index', 'i' => 'tag', 'on' => ['produk.*'], 'ok' => $manajemen || $u->roleName() === 'KASIR'],
                ['t' => 'Pengeluaran', 'r' => 'pengeluaran.index', 'i' => 'wallet', 'on' => ['pengeluaran.*'], 'ok' => $u->canView('pengeluaran')],
                ['t' => 'Laporan', 'r' => 'laporan.index', 'i' => 'chart-line-up', 'on' => ['laporan.*'], 'ok' => $manajemen],
                ['t' => 'Laporan Kandang', 'bn' => 'Laporan', 'r' => 'laporankandang.index', 'i' => 'chart-bar', 'on' => ['laporankandang.*'], 'ok' => $u->isPengawasKandang()],
                ['t' => 'Hapus Data', 'r' => 'hapus-data.index', 'i' => 'trash', 'on' => ['hapus-data.*'], 'ok' => $admin],
            ],
            'Administrasi' => [
                ['t' => 'Pengguna', 'r' => 'pengguna.index', 'i' => 'user-gear', 'on' => ['pengguna.*'], 'ok' => $admin],
                ['t' => 'SDM & Gaji', 'r' => 'gaji.index', 'i' => 'money', 'on' => ['gaji.*', 'karyawan.*', 'presensi.*', 'kasbon.*'], 'ok' => $admin],
                ['t' => 'Pengaturan', 'r' => 'pengaturan.edit', 'i' => 'gear', 'on' => ['pengaturan.*'], 'ok' => $admin],
            ],
        ];
    }

    /** Kelompok yang sudah disaring sesuai hak akses; kelompok kosong dibuang. */
    public static function kelompok(User $u): array
    {
        $hasil = [];
        foreach (self::semua($u) as $judul => $items) {
            $tampil = array_values(array_filter($items, fn ($i) => $i['ok']));
            if ($tampil) {
                $hasil[$judul] = $tampil;
            }
        }

        return $hasil;
    }

    /** Item navigasi bawah (tanpa tombol Menu), urutannya menurut peran. */
    public static function bawah(User $u): array
    {
        $peta = [];
        foreach (self::semua($u) as $items) {
            foreach ($items as $i) {
                if ($i['ok']) {
                    $peta[$i['r']] = $i;
                }
            }
        }
        $peta['dashboard'] = ['t' => 'Beranda', 'r' => 'dashboard', 'i' => 'house', 'on' => ['dashboard']];
        $peta['absen.index'] = ['t' => 'Absen', 'r' => 'absen.index', 'i' => 'calendar-check', 'on' => ['absen.*']];
        $peta['akun.password'] = ['t' => 'Akun', 'r' => 'akun.password', 'i' => 'key', 'on' => ['akun.*']];

        $urut = match ($u->roleName()) {
            'KASIR' => ['dashboard', 'kasir.create', 'kasir.dapur.index', 'kasir.index'],
            'PENGAWAS_KANDANG' => ['dashboard', 'pembelian.index', 'ayamkeluar.index', 'laporankandang.index'],
            'MANAGER' => ['dashboard', 'laporan.index', 'kasir.index', 'piutang.index'],
            'PEGAWAI' => ['absen.index', 'akun.password'],
            default => ['dashboard', 'kasir.create', 'laporan.index', 'piutang.index'],
        };

        $hasil = [];
        foreach ($urut as $r) {
            if (isset($peta[$r])) {
                $hasil[] = $peta[$r];
            }
        }

        return $hasil;
    }

    public static function aktif(array $i): bool
    {
        $a = request()->routeIs(...$i['on']);
        if ($a && ! empty($i['not']) && request()->routeIs(...$i['not'])) {
            $a = false;
        }

        return $a;
    }
}