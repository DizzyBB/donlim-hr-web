<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\RiwayatUjianController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\UjianSayaController;
use App\Http\Controllers\TrainingRequestController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\KaryawanTrainingController;
use App\Http\Controllers\PublicTrainingController;


Route::middleware('throttle:20,1')->group(function () {
    Route::get('/', [PublicTrainingController::class, 'index'])->name('welcome');
    Route::post('/training-requests', [PublicTrainingController::class, 'storeTraining'])->name('public.training.store');
    Route::post('/room-bookings', [PublicTrainingController::class, 'storeRoom'])->name('public.room.store');
});

// AUTHENTICATION ROUTES
Route::middleware('guest:web,karyawan')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:karyawan')->prefix('karyawan')->name('karyawan.')->group(function () {
    Route::redirect('/', '/karyawan/ujian');

    Route::get('/ujian', [UjianSayaController::class, 'index'])->name('ujian.index');
    Route::post('/ujian/{ujian}/mulai', [UjianSayaController::class, 'mulai'])->name('ujian.mulai');

    Route::get('/pengerjaan/{hasilUjian}', [UjianSayaController::class, 'kerjakan'])->name('ujian.kerjakan');
    Route::post('/pengerjaan/{hasilUjian}/jawab', [UjianSayaController::class, 'jawab'])->name('ujian.jawab');
    Route::post('/pengerjaan/{hasilUjian}/selesai', [UjianSayaController::class, 'selesai'])->name('ujian.selesai');
    Route::get('/pengerjaan/{hasilUjian}/hasil', [UjianSayaController::class, 'hasil'])->name('ujian.hasil');

    Route::prefix('training')->name('training.')->group(function () {
    Route::get('/', [TrainingRequestController::class, 'index'])->name('index');
    Route::get('/ajukan', [TrainingRequestController::class, 'create'])->name('create');
    Route::post('/', [TrainingRequestController::class, 'store'])->name('store');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:web,karyawan')
    ->name('logout');
// END AUTHENTICATION ROUTES

Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

// Karyawan (Admin management)
Route::prefix('karyawan')->name('karyawan.')->group(function () {
    Route::get('/', [KaryawanController::class, 'index'])->name('index');
    Route::get('/create', [KaryawanController::class, 'create'])->name('create');
    Route::post('/', [KaryawanController::class, 'store'])->name('store');
    Route::get('/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('edit');
    Route::put('/{karyawan}', [KaryawanController::class, 'update'])->name('update');
    Route::delete('/{karyawan}', [KaryawanController::class, 'destroy'])->name('destroy');
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
        Route::get('/{ujian}/edit', [UjianController::class, 'edit'])->name('edit');
        Route::put('/{ujian}', [UjianController::class, 'update'])->name('update');
        Route::delete('/{ujian}', [UjianController::class, 'destroy'])->name('destroy');
        Route::get('/karyawan-search', [UjianController::class, 'karyawanSearch'])->name('karyawan-search');
        Route::get('/soal-data', [UjianController::class, 'soalData'])->name('soal-data');
    });

    Route::prefix('penilaian')->name('penilaian.')->group(function () {
        Route::get('/', [PenilaianController::class, 'index'])->name('index');
        Route::get('/{hasilUjian}', [PenilaianController::class, 'show'])->name('show');
        Route::put('/{hasilUjian}', [PenilaianController::class, 'update'])->name('update');
    });

    Route::prefix('riwayat-ujian')->name('riwayat-ujian.')->group(function () {
        Route::get('/', [RiwayatUjianController::class, 'index'])->name('index');
        Route::get('/{hasilUjian}', [RiwayatUjianController::class, 'show'])->name('show');
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

