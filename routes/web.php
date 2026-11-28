<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\Admin\RiwayatController;

#User Routes
Route::get('/', BerandaController::class)->name('beranda');
Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/produk/{produk:slug}', [ProdukController::class, 'show'])->name('produk.show');

Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
Route::patch('/keranjang/{produk}', [KeranjangController::class, 'update'])->name('keranjang.update');
Route::delete('/keranjang/{produk}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1,checkout')->name('checkout.store');

Route::get('/status-pesanan', [PesananController::class, 'cari'])->name('status.cek');
Route::post('/status-pesanan', [PesananController::class, 'temukan'])->middleware('throttle:10,1,cek-status')->name('status.temukan');
Route::get('/pesanan/{order:public_token}', [PesananController::class, 'show'])->name('pesanan.show');
Route::post('/pesanan/{order:public_token}/batal', [PesananController::class, 'batal'])->name('pesanan.batal');

Route::view('/tentang', 'pelanggan.tentang')->name('tentang');

#Admin Routes
Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('pesanan', [AdminPesananController::class, 'index'])->name('pesanan.index');
        Route::get('pesanan/{order}', [AdminPesananController::class, 'show'])->name('pesanan.show');
        Route::patch('pesanan/{order}/status', [AdminPesananController::class, 'ubahStatus'])->name('pesanan.status');
        Route::patch('pesanan/{order}/pembayaran', [AdminPesananController::class, 'ubahPembayaran'])->name('pesanan.pembayaran');
        Route::post('pesanan/{order}/minta-pembayaran', [AdminPesananController::class, 'mintaPembayaran'])->name('pesanan.minta-pembayaran');
        Route::patch('pesanan/{order}/catatan', [AdminPesananController::class, 'ubahCatatan'])->name('pesanan.catatan');
        Route::get('pesanan/{order}/item', [AdminPesananController::class, 'editItem'])->name('pesanan.item');
        Route::put('pesanan/{order}/item', [AdminPesananController::class, 'updateItem'])->name('pesanan.item.update');

        // Phase 3B: pengelolaan
        Route::resource('produk', AdminProdukController::class)->except('show')->parameters(['produk' => 'produk']);
        Route::patch('produk/{produk}/aktif', [AdminProdukController::class, 'ubahAktif'])->name('produk.aktif');
        Route::resource('kategori', KategoriController::class)->except('show')->parameters(['kategori' => 'kategori']);
        Route::get('riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
        Route::get('hari-libur', [HariLiburController::class, 'index'])->name('hari-libur.index');
        Route::post('hari-libur', [HariLiburController::class, 'store'])->name('hari-libur.store');
        Route::delete('hari-libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('hari-libur.destroy');
        Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    });
});