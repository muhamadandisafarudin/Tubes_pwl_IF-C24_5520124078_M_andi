<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\MatakuliahController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\KrsController as AdminKrsController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\KrsController as MahasiswaKrsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────
// Root redirect → login
// ─────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ─────────────────────────────────────────────
// Dashboard umum (redirect sesuai role)
// ─────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// ─────────────────────────────────────────────
// Profile (semua user yang login)
// ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─────────────────────────────────────────────
// Admin routes
// ─────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard admin
    Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

    // Dosen CRUD
    Route::resource('dosen', DosenController::class)->parameters(['dosen' => 'nidn']);

    // Mahasiswa CRUD
    Route::resource('mahasiswa', MahasiswaController::class)->parameters(['mahasiswa' => 'npm']);

    // Mata Kuliah CRUD
    Route::resource('matakuliah', MatakuliahController::class)->parameters(['matakuliah' => 'kode']);

    // Jadwal CRUD
    Route::resource('jadwal', JadwalController::class);

    // KRS (admin hanya lihat & hapus)
    Route::get('/krs', [AdminKrsController::class, 'index'])->name('krs.index');
    Route::delete('/krs/{id}', [AdminKrsController::class, 'destroy'])->name('krs.destroy');
});

// ─────────────────────────────────────────────
// Mahasiswa routes
// ─────────────────────────────────────────────
Route::middleware(['auth', 'role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {

    // Dashboard mahasiswa
    Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])->name('dashboard');

    // KRS mahasiswa
    Route::get('/krs', [MahasiswaKrsController::class, 'index'])->name('krs.index');
    Route::post('/krs', [MahasiswaKrsController::class, 'store'])->name('krs.store');
    Route::delete('/krs/{id}', [MahasiswaKrsController::class, 'destroy'])->name('krs.destroy');

    // Jadwal
    Route::get('/jadwal', [MahasiswaKrsController::class, 'jadwal'])->name('jadwal');
});

// ─────────────────────────────────────────────
// Auth routes (login, register, logout, dll)
// ─────────────────────────────────────────────
require __DIR__.'/auth.php';

// ─────────────────────────────────────────────
// Health-check / ping → untuk auto-ping Render
// agar free tier tidak sleep
// ─────────────────────────────────────────────
Route::get('/ping', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'ok';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'app'    => config('app.name'),
        'db'     => $dbStatus,
        'time'   => now()->toDateTimeString(),
    ]);
})->name('ping');
