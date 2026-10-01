<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MortalitasController;
use App\Http\Controllers\OpnameAyamController;
use App\Http\Controllers\OpnameProdukController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PemasokController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\ProduksiController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

// Tamu (belum login). Percobaan login dibatasi 5 kali per menit.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function () {
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
            Route::get('/{supplier}/ubah', [PemasokController::class, 'edit'])->whereUuid('supplier')->name('edit');
            Route::put('/{supplier}', [PemasokController::class, 'update'])->whereUuid('supplier')->name('update');
        });

        // Ayam mati, opname ayam hidup, input hasil produksi
        Route::get('/mortalitas', [MortalitasController::class, 'index'])->name('mortalitas.index');
        Route::post('/mortalitas', [MortalitasController::class, 'store'])->name('mortalitas.store');

        Route::get('/opname-ayam', [OpnameAyamController::class, 'index'])->name('opnameayam.index');
        Route::post('/opname-ayam', [OpnameAyamController::class, 'store'])->name('opnameayam.store');

        Route::get('/produksi', [ProduksiController::class, 'index'])->name('produksi.index');
        Route::post('/produksi', [ProduksiController::class, 'store'])->name('produksi.store');
    });

    // Opname stok hasil produksi: ADMIN + PENGAWAS_KANDANG + KASIR (MANAGER hanya melihat)
    Route::middleware(RoleMiddleware::class . ':PENGAWAS_KANDANG,KASIR')->group(function () {
        Route::get('/opname-produk', [OpnameProdukController::class, 'index'])->name('opnameproduk.index');
        Route::post('/opname-produk', [OpnameProdukController::class, 'store'])->name('opnameproduk.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});