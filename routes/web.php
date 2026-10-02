<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\MejaController as AdminMeja;
use App\Http\Controllers\Kasir\PesananController as KasirPesanan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;

Route::get('/health', [HealthController::class, 'index'])->name('health');
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->role === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('kasir.dashboard');
});

/*
|--------------------------------------------------------------------------
| Rute Pelanggan (tanpa login — akses via scan QR)
|--------------------------------------------------------------------------
*/
Route::prefix('menu/{kode_qr}')->group(function () {
    Route::get('/', [PelangganController::class, 'index'])->name('menu.index');
    Route::post('/checkout', [PelangganController::class, 'checkout'])->name('menu.checkout');
    Route::post('/bayar', [PelangganController::class, 'bayar'])->name('menu.bayar');
    Route::get('/status/{no_pesanan}', [PelangganController::class, 'status'])->name('menu.status');
});

/*
|--------------------------------------------------------------------------
| Rute Login Bersama Admin & Kasir
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rute Admin (middleware: auth, role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/pesanan', [AdminDashboard::class, 'index'])->name('pesanan');
    Route::resource('menu', MenuController::class)->except(['show']);
    Route::get('/meja', [AdminMeja::class, 'index'])->name('meja.index');
    Route::post('/meja', [AdminMeja::class, 'store'])->name('meja.store');
    Route::get('/kategori', [\App\Http\Controllers\Admin\KategoriController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [\App\Http\Controllers\Admin\KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{kategori}', [\App\Http\Controllers\Admin\KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [\App\Http\Controllers\Admin\KategoriController::class, 'destroy'])->name('kategori.destroy');
    Route::get('/staff', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('staff.store');
    Route::patch('/staff/{user}', [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{user}', [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::get('/laporan', [\App\Http\Controllers\Admin\LaporanController::class, 'index'])->name('laporan');
});

/*
|--------------------------------------------------------------------------
| Rute Kasir (middleware: auth, role:admin,kasir — dashboard bisa diakses keduanya)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/dashboard', [KasirPesanan::class, 'index'])->name('dashboard');
    Route::get('/pesanan/{pesanan}', [KasirPesanan::class, 'show'])->name('pesanan.show');
    Route::post('/pesanan/{pesanan}/verifikasi', [KasirPesanan::class, 'verifikasi'])->name('pesanan.verifikasi');
    Route::post('/pesanan/{pesanan}/status', [KasirPesanan::class, 'updateStatus'])->name('pesanan.status');
});
