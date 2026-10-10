<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\AyamKeluarController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreezerController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MortalitasController;
use App\Http\Controllers\OpnameAyamController;
use App\Http\Controllers\OpnameProdukController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\AbsenController;
use App\Http\Middleware\BatasiPegawai;
use App\Http\Controllers\KasbonController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\PengaturanBonusController;
use App\Http\Controllers\LabaRugiController;
use App\Http\Controllers\PenerimaanGajiController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PemasokController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanKandangController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

// Tamu (belum login). Percobaan login dibatasi 5 kali per menit.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware(['auth', BatasiPegawai::class])->group(function () {
    // Semua peran
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Akun
    Route::get('/akun/password', [AkunController::class, 'showPassword'])->name('akun.password');
    Route::put('/akun/password', [AkunController::class, 'updatePassword'])->name('akun.password.update');

    // Kasir dan piutang: ADMIN + KASIR (MANAGER hanya melihat).
    // Parameter {sale} dibatasi UUID, jadi alamat seperti /kasir/riwayat
    // tidak pernah dianggap id nota dan id asal-asalan menghasilkan 404, bukan error database.
    Route::middleware(RoleMiddleware::class . ':KASIR')->group(function () {
        Route::prefix('kasir')->name('kasir.')->group(function () {
            Route::get('/', [KasirController::class, 'create'])->name('create');
            Route::post('/', [KasirController::class, 'store'])->name('store');
            Route::post('/pelanggan', [KasirController::class, 'simpanPelanggan'])->name('pelanggan.store');
            Route::get('/dapur', [\App\Http\Controllers\KitchenOrderController::class, 'index'])->name('dapur.index');
            Route::get('/dapur/baru', [\App\Http\Controllers\KitchenOrderController::class, 'create'])->name('dapur.create');
            Route::post('/dapur', [\App\Http\Controllers\KitchenOrderController::class, 'store'])->name('dapur.store');
            Route::get('/dapur/{order}', [\App\Http\Controllers\KitchenOrderController::class, 'show'])->whereUuid('order')->name('dapur.show');
            Route::post('/dapur/{order}/batal', [\App\Http\Controllers\KitchenOrderController::class, 'void'])->whereUuid('order')->name('dapur.void');
            Route::get('/ayam-hidup', [\App\Http\Controllers\KasirAyamHidupController::class, 'index'])->name('ayamhidup.index');
            Route::get('/ayam-hidup/{rec}', [\App\Http\Controllers\KasirAyamHidupController::class, 'create'])->whereUuid('rec')->name('ayamhidup.create');
            Route::post('/ayam-hidup/{rec}', [\App\Http\Controllers\KasirAyamHidupController::class, 'store'])->whereUuid('rec')->name('ayamhidup.store');
            Route::get('/riwayat', [KasirController::class, 'index'])->name('index');
            Route::get('/{sale}', [KasirController::class, 'show'])->whereUuid('sale')->name('show');
            Route::post('/{sale}/void', [KasirController::class, 'void'])->whereUuid('sale')->name('void'); // khusus admin (dicek di controller)
        });

        // Piutang / hutang pelanggan
        Route::prefix('piutang')->name('piutang.')->group(function () {
            Route::get('/', [PiutangController::class, 'index'])->name('index');
            Route::get('/{customer}', [PiutangController::class, 'show'])->whereUuid('customer')->name('show');
            Route::post('/{customer}/bayar', [PiutangController::class, 'bayar'])->whereUuid('customer')->name('bayar');
        });
    });

    // Kandang: ADMIN + PENGAWAS_KANDANG (MANAGER hanya melihat)
    Route::middleware(RoleMiddleware::class . ':PENGAWAS_KANDANG')->group(function () {
        // Pembelian ayam hidup dari pemasok
        Route::prefix('pembelian')->name('pembelian.')->group(function () {
            Route::get('/', [PembelianController::class, 'index'])->name('index');
            Route::get('/buat', [PembelianController::class, 'create'])->name('create');
            Route::post('/', [PembelianController::class, 'store'])->name('store');
            Route::get('/{purchase}', [PembelianController::class, 'show'])->whereUuid('purchase')->name('show');
        });

        // Pemasok
        Route::prefix('pemasok')->name('pemasok.')->group(function () {
            Route::get('/', [PemasokController::class, 'index'])->name('index');
            Route::get('/buat', [PemasokController::class, 'create'])->name('create');
            Route::post('/', [PemasokController::class, 'store'])->name('store');
            Route::get('/{supplier}', [PemasokController::class, 'show'])->whereUuid('supplier')->name('show');
            Route::get('/{supplier}/ubah', [PemasokController::class, 'edit'])->whereUuid('supplier')->name('edit');
            Route::put('/{supplier}', [PemasokController::class, 'update'])->whereUuid('supplier')->name('update');
        });

        // Ayam mati, opname ayam hidup, input hasil produksi
        Route::get('/mortalitas', [MortalitasController::class, 'index'])->name('mortalitas.index');
        Route::get('/laporan-kandang', [LaporanKandangController::class, 'index'])->name('laporankandang.index');
        Route::get('/ayam-keluar', [AyamKeluarController::class, 'index'])->name('ayamkeluar.index');
        Route::post('/ayam-keluar', [AyamKeluarController::class, 'store'])->name('ayamkeluar.store');
        Route::post('/ayam-keluar/jual', [\App\Http\Controllers\AyamJualController::class, 'store'])->name('ayamkeluar.jual');
        Route::post('/mortalitas', [MortalitasController::class, 'store'])->name('mortalitas.store');

        Route::get('/opname-ayam', [OpnameAyamController::class, 'index'])->name('opnameayam.index');
        Route::post('/opname-ayam', [OpnameAyamController::class, 'store'])->name('opnameayam.store');

    });

    // Opname stok & input hasil produksi: ADMIN + KASIR (MANAGER hanya melihat)
    Route::middleware(RoleMiddleware::class . ':KASIR')->group(function () {
        Route::get('/opname-produk', [OpnameProdukController::class, 'index'])->name('opnameproduk.index');
        Route::post('/opname-produk', [OpnameProdukController::class, 'store'])->name('opnameproduk.store');

        Route::get('/produksi', [ProduksiController::class, 'index'])->name('produksi.index');
        Route::post('/produksi', [ProduksiController::class, 'store'])->name('produksi.store');
    });

    // Produk & harga: ADMIN dan KASIR boleh lihat + ubah, MANAGER hanya lihat. Pengawas ditolak (403).
    // Pembatasan per peran dicek lagi di ProdukController (bolehLihat / bolehUbah).
    Route::middleware(RoleMiddleware::class . ':KASIR')->prefix('produk')->name('produk.')->group(function () {
        Route::get('/', [ProdukController::class, 'index'])->name('index');
        Route::post('/', [ProdukController::class, 'store'])->name('store');
        Route::get('/{id}/ubah', [ProdukController::class, 'edit'])->whereUuid('id')->name('edit');
        Route::put('/{id}', [ProdukController::class, 'update'])->whereUuid('id')->name('update');
        Route::put('/{id}/harga', [ProdukController::class, 'harga'])->whereUuid('id')->name('harga');
    });

    // Freezer & batas simpan produk: ADMIN dan KASIR boleh lihat + ubah, MANAGER hanya lihat.
    // Pembatasan per peran dicek lagi di FreezerController (bolehLihat / bolehUbah).
    Route::middleware(RoleMiddleware::class . ':KASIR')->prefix('freezer')->name('freezer.')->group(function () {
        Route::get('/', [FreezerController::class, 'index'])->name('index');
        Route::post('/', [FreezerController::class, 'store'])->name('store');
        Route::put('/batas', [FreezerController::class, 'batas'])->name('batas');
        Route::put('/{id}', [FreezerController::class, 'update'])->whereUuid('id')->name('update');
    });

    // Pelanggan: ADMIN kelola, KASIR lihat + tambah, MANAGER hanya lihat. Pengawas ditolak.
    // Pembatasan ubah/tambah per peran dicek lagi di PelangganController.
    Route::middleware(RoleMiddleware::class . ':KASIR')->prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('index');
        Route::post('/', [PelangganController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PelangganController::class, 'edit'])->whereUuid('id')->name('edit');
        Route::put('/{id}', [PelangganController::class, 'update'])->whereUuid('id')->name('update');
    });
    // Laporan harian/mingguan/bulanan: ADMIN dan MANAGER (hanya lihat). Peran lain 403.
    Route::middleware(RoleMiddleware::class)->get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    // Pengaturan web (logo, ikon, nota, warna): khusus ADMIN, dicek di controller
    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

    // Pengeluaran operasional: ADMIN mencatat, MANAGER hanya melihat (dicek di controller)
    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('pengeluaran.index');
    Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('pengeluaran.store');

    // Kelola pengguna: khusus ADMIN (dicek di controller). {user} dibatasi UUID.
    Route::prefix('pengguna')->name('pengguna.')->group(function () {
        Route::get('/', [PenggunaController::class, 'index'])->name('index');
        Route::get('/buat', [PenggunaController::class, 'create'])->name('create');
        Route::post('/', [PenggunaController::class, 'store'])->name('store');
        Route::get('/{user}/ubah', [PenggunaController::class, 'edit'])->whereUuid('user')->name('edit');
        Route::put('/{user}', [PenggunaController::class, 'update'])->whereUuid('user')->name('update');
        Route::post('/{user}/password', [PenggunaController::class, 'resetPassword'])->whereUuid('user')->name('password');
        Route::put('/{user}/pegawai', [PenggunaController::class, 'hubungkanPegawai'])->whereUuid('user')->name('pegawai');
    });

    // SDM & Gaji: khusus ADMIN, disembunyikan dari semua peran lain (dicek di controller)
    Route::prefix('karyawan')->name('karyawan.')->group(function () {
        Route::get('/', [KaryawanController::class, 'index'])->name('index');
        Route::get('/buat', [KaryawanController::class, 'create'])->name('create');
        Route::post('/', [KaryawanController::class, 'store'])->name('store');
        Route::get('/{employee}/ubah', [KaryawanController::class, 'edit'])->whereUuid('employee')->name('edit');
        Route::put('/{employee}', [KaryawanController::class, 'update'])->whereUuid('employee')->name('update');
    });

    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::post('/presensi/tutup', [PresensiController::class, 'tutup'])->name('presensi.tutup');

    Route::prefix('kasbon')->name('kasbon.')->group(function () {
        Route::get('/', [KasbonController::class, 'index'])->name('index');
        Route::post('/', [KasbonController::class, 'store'])->name('store');
        Route::delete('/{loan}', [KasbonController::class, 'destroy'])->whereUuid('loan')->name('destroy');
    });

    Route::prefix('gaji')->name('gaji.')->group(function () {
        Route::get('/', [GajiController::class, 'index'])->name('index');
        Route::post('/buat', [GajiController::class, 'generate'])->name('generate');
        Route::get('/pengaturan', [GajiController::class, 'pengaturan'])->name('pengaturan');
        Route::put('/pengaturan', [GajiController::class, 'simpanPengaturan'])->name('pengaturan.simpan');
        Route::get('/pengaturan/ttd', [GajiController::class, 'ttd'])->name('ttd');
        Route::get('/penerimaan', PenerimaanGajiController::class)->name('penerimaan');
        Route::get('/laba-rugi', LabaRugiController::class)->name('labarugi');
        Route::get('/bonus', [PengaturanBonusController::class, 'edit'])->name('bonus');
        Route::put('/bonus', [PengaturanBonusController::class, 'update'])->name('bonus.simpan');
        Route::get('/slip-massal', [GajiController::class, 'slipMassal'])->name('slip.massal');
        Route::get('/{payroll}', [GajiController::class, 'show'])->whereUuid('payroll')->name('show');
        Route::put('/{payroll}', [GajiController::class, 'update'])->whereUuid('payroll')->name('update');
        Route::post('/{payroll}/hitung-ulang', [GajiController::class, 'hitungUlang'])->whereUuid('payroll')->name('hitung');
        Route::post('/{payroll}/terbitkan', [GajiController::class, 'terbitkan'])->whereUuid('payroll')->name('terbitkan');
        Route::post('/{payroll}/bayar', [GajiController::class, 'bayar'])->whereUuid('payroll')->name('bayar');
        Route::post('/{payroll}/batal', [GajiController::class, 'batal'])->whereUuid('payroll')->name('batal');
        Route::delete('/{payroll}', [GajiController::class, 'hapus'])->whereUuid('payroll')->name('hapus');
        Route::get('/{payroll}/slip', [GajiController::class, 'slip'])->whereUuid('payroll')->name('slip');
    });

    Route::middleware(RoleMiddleware::class . ':PEGAWAI')->prefix('absen')->name('absen.')->group(function () {
        Route::get('/', [AbsenController::class, 'index'])->name('index');
        Route::post('/masuk', [AbsenController::class, 'masuk'])->name('masuk');
        Route::post('/pulang', [AbsenController::class, 'pulang'])->name('pulang');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
Route::middleware('auth')->delete('/produksi/{weighingRecord}/hapus', \App\Http\Controllers\DeleteWeighingRecordController::class)->name('produksi.hapus');

Route::middleware('auth')->delete('/produksi/batch/{productionBatch}/hapus', \App\Http\Controllers\DeleteProductionBatchController::class)->name('produksi.batch.hapus');
