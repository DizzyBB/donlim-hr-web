<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartemenController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KaryawanImportController;
use App\Http\Controllers\KaryawanPasswordController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\PesertaBaruController;
use App\Http\Controllers\PublicTrainingController;
use App\Http\Controllers\RiwayatUjianController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TrainingRequestController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\UjianSayaController;
use App\Http\Controllers\UjianTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/', [PublicTrainingController::class, 'index'])->name('welcome');
    Route::post('/training-requests', [PublicTrainingController::class, 'storeTraining'])->name('public.training.store');
    Route::post('/room-bookings', [PublicTrainingController::class, 'storeRoom'])->name('public.room.store');
});

// ==================== UJIAN KARYAWAN BARU (TOKEN, TANPA LOGIN) ====================
// Alur: masukkan token -> isi Nama + Departemen -> langsung mulai ujian.
Route::prefix('ujian-baru')->name('peserta.')->group(function () {
    Route::get('/', [PesertaBaruController::class, 'tokenForm'])->name('token');
    Route::post('/token', [PesertaBaruController::class, 'verifikasiToken'])->middleware('throttle:10,1')->name('token.verifikasi');
    Route::get('/identitas', [PesertaBaruController::class, 'identitasForm'])->name('identitas');
    Route::post('/mulai', [PesertaBaruController::class, 'mulai'])->middleware('throttle:10,1')->name('mulai');

    Route::prefix('pengerjaan/{hasilUjian}')->name('ujian.')->group(function () {
        Route::get('/', [UjianSayaController::class, 'kerjakan'])->name('kerjakan');
        Route::post('/jawab', [UjianSayaController::class, 'jawab'])->name('jawab');
        Route::post('/selesai', [UjianSayaController::class, 'selesai'])->name('selesai');
        Route::get('/hasil', [UjianSayaController::class, 'hasil'])->name('hasil');
        Route::get('/pdf', [UjianSayaController::class, 'pdf'])->name('pdf');
    });
});

// ==================== AUTHENTICATION ROUTES ====================
Route::middleware('guest:web,karyawan')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Portal karyawan aktif. (Route::redirect('/') dihapus: URI-nya sama dengan /karyawan milik admin
// sehingga menutupi halaman Manajemen Karyawan.)
Route::middleware('auth:karyawan')->prefix('karyawan')->name('karyawan.')->group(function () {
    // Ganti password (wajib untuk akun hasil import) - di luar middleware password.changed
    Route::get('/password', [KaryawanPasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [KaryawanPasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/ujian', [UjianSayaController::class, 'index'])->name('ujian.index');
        // Ujian lanjutan: masuk memakai token yang terikat ke akun ini.
        Route::post('/ujian/token', [UjianSayaController::class, 'mulaiDenganToken'])->middleware('throttle:10,1')->name('ujian.token');

        Route::get('/pengerjaan/{hasilUjian}', [UjianSayaController::class, 'kerjakan'])->name('ujian.kerjakan');
        Route::post('/pengerjaan/{hasilUjian}/jawab', [UjianSayaController::class, 'jawab'])->name('ujian.jawab');
        Route::post('/pengerjaan/{hasilUjian}/selesai', [UjianSayaController::class, 'selesai'])->name('ujian.selesai');
        Route::get('/pengerjaan/{hasilUjian}/hasil', [UjianSayaController::class, 'hasil'])->name('ujian.hasil');
        Route::get('/pengerjaan/{hasilUjian}/pdf', [UjianSayaController::class, 'pdf'])->name('ujian.pdf');

        Route::prefix('training')->name('training.')->group(function () {
            Route::get('/', [TrainingRequestController::class, 'index'])->name('index');
            Route::get('/ajukan', [TrainingRequestController::class, 'create'])->name('create');
            Route::post('/', [TrainingRequestController::class, 'store'])->name('store');
        });
    });
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:web,karyawan')
    ->name('logout');
// END AUTHENTICATION ROUTES

// ==================== AREA ADMIN / HRD (WAJIB LOGIN GUARD "web") ====================
Route::middleware('auth:web')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    // Karyawan (Admin management)
    Route::prefix('karyawan')->name('karyawan.')->group(function () {
        Route::get('/', [KaryawanController::class, 'index'])->name('index');
        Route::get('/create', [KaryawanController::class, 'create'])->name('create');

        // Bulk import karyawan aktif (2 langkah: upload -> preview -> konfirmasi)
        Route::get('/import', [KaryawanImportController::class, 'form'])->name('import');
        Route::get('/import/template', [KaryawanImportController::class, 'template'])->name('import.template');
        Route::post('/import/preview', [KaryawanImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/konfirmasi', [KaryawanImportController::class, 'konfirmasi'])->name('import.konfirmasi');

        Route::post('/', [KaryawanController::class, 'store'])->name('store');
        Route::get('/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('edit');
        Route::put('/{karyawan}', [KaryawanController::class, 'update'])->name('update');
        Route::delete('/{karyawan}', [KaryawanController::class, 'destroy'])->name('destroy');
    });

    // Master departemen (sumber dropdown departemen)
    Route::prefix('departemen')->name('departemen.')->group(function () {
        Route::get('/', [DepartemenController::class, 'index'])->name('index');
        Route::post('/', [DepartemenController::class, 'store'])->name('store');
        Route::delete('/{departemen}', [DepartemenController::class, 'destroy'])->name('destroy');
    });

    // examination
    Route::prefix('examination')->name('examination.')->group(function () {

        Route::prefix('question-bank')->name('question-bank.')->group(function () {
            Route::get('/', [ExamController::class, 'bankSoal'])->name('index');
            Route::get('/template/{type}', [ExamController::class, 'template'])->name('template');
            Route::post('/import', [ExamController::class, 'import'])->name('import');
            Route::delete('/{soal}', [ExamController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('input-soal')->name('input-soal.')->group(function () {
            Route::get('/', [ExamController::class, 'index'])->name('index');
            Route::get('/data', [ExamController::class, 'data'])->name('data');
            Route::post('/', [ExamController::class, 'store'])->name('store');
            Route::put('/{soal}', [ExamController::class, 'update'])->name('update');
            Route::delete('/{soal}', [ExamController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ujian')->name('ujian.')->group(function () {
            Route::get('/', [UjianController::class, 'index'])->name('index');
            Route::get('/create', [UjianController::class, 'create'])->name('create');
            Route::post('/', [UjianController::class, 'store'])->name('store');
            Route::get('/karyawan-search', [UjianController::class, 'karyawanSearch'])->name('karyawan-search');
            Route::get('/soal-data', [UjianController::class, 'soalData'])->name('soal-data');
            Route::get('/{ujian}/edit', [UjianController::class, 'edit'])->name('edit');
            Route::put('/{ujian}', [UjianController::class, 'update'])->name('update');
            Route::post('/{ujian}/status', [UjianController::class, 'toggleStatus'])->name('status');
            Route::delete('/{ujian}', [UjianController::class, 'destroy'])->name('destroy');

            // Token ujian
            Route::get('/{ujian}/token', [UjianTokenController::class, 'index'])->name('token.index');
            Route::post('/{ujian}/token', [UjianTokenController::class, 'store'])->name('token.store');
            Route::post('/{ujian}/token/{token}/kedaluwarsa', [UjianTokenController::class, 'kedaluwarsa'])->name('token.kedaluwarsa');
        });

        Route::prefix('penilaian')->name('penilaian.')->group(function () {
            Route::get('/', [PenilaianController::class, 'index'])->name('index');
            Route::get('/{hasilUjian}', [PenilaianController::class, 'show'])->name('show');
            Route::put('/{hasilUjian}', [PenilaianController::class, 'update'])->name('update');
            Route::post('/{hasilUjian}/hitung-ulang', [PenilaianController::class, 'hitungUlang'])->name('hitung-ulang');
            Route::post('/{hasilUjian}/token-pengganti', [UjianTokenController::class, 'pengganti'])->name('token-pengganti');
        });

        Route::prefix('riwayat-ujian')->name('riwayat-ujian.')->group(function () {
            Route::get('/', [RiwayatUjianController::class, 'index'])->name('index');
            Route::get('/export', [RiwayatUjianController::class, 'export'])->name('export');
            Route::get('/{hasilUjian}', [RiwayatUjianController::class, 'show'])->name('show');
            Route::get('/{hasilUjian}/pdf', [RiwayatUjianController::class, 'pdf'])->name('pdf');
        });
        // menu exam lain tinggal ditambahkan di sini, otomatis kena prefix 'examination/'
    });

    // ==================== TRAINING MANAGEMENT (MVC) ====================

    Route::prefix('training')->name('training.')->group(function () {

        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [TrainingRequestController::class, 'index'])->name('index');
            Route::post('/{trainingRequest}/approve', [TrainingRequestController::class, 'approve'])->name('approve');
            Route::post('/{trainingRequest}/reject', [TrainingRequestController::class, 'reject'])->name('reject');
        });

        Route::prefix('rooms')->name('rooms.')->group(function () {
            Route::get('/', [RoomController::class, 'index'])->name('index');
            Route::post('/', [RoomController::class, 'store'])->name('store');
            Route::put('/{room}', [RoomController::class, 'update'])->name('update');
            Route::delete('/{room}', [RoomController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [CalendarController::class, 'index'])->name('index');
            Route::get('/events', [CalendarController::class, 'events'])->name('events');
        });

    });
});
