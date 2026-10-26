<?php

use App\Http\Controllers\Api\Admin\DosenController as AdminDosenController;
use App\Http\Controllers\Api\Admin\KelasController as AdminKelasController;
use App\Http\Controllers\Api\Admin\KrsController as AdminKrsController;
use App\Http\Controllers\Api\Admin\MahasiswaController as AdminMahasiswaController;
use App\Http\Controllers\Api\Admin\MataKuliahController as AdminMataKuliahController;
use App\Http\Controllers\Api\Admin\PerpustakaanController as AdminPerpustakaanController;
use App\Http\Controllers\Api\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\KrsController;
use App\Http\Controllers\Api\LaporanAdminController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PerpustakaanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Route
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('api.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->name('api.password.forgot');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('api.password.reset');

// Protected Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Current User
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // Classroom & Materials
    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('api.classrooms.index');
    Route::get('/classrooms/{id}', [ClassroomController::class, 'show'])->name('api.classrooms.show');
    Route::post('/classrooms/{id}/announcements', [ClassroomController::class, 'storeAnnouncement'])->name('api.classrooms.announcements.store');
    Route::post('/classrooms/{id}/sections', [ClassroomController::class, 'storeSection'])->name('api.classrooms.sections.store');
    Route::post('/sections/{id}/materials', [ClassroomController::class, 'storeMaterial'])->name('api.sections.materials.store');
    Route::post('/sections/{id}/tasks', [ClassroomController::class, 'storeTask'])->name('api.sections.tasks.store');
    Route::put('/announcements/{id}', [ClassroomController::class, 'updateAnnouncement'])->name('api.announcements.update');
    Route::delete('/announcements/{id}', [ClassroomController::class, 'destroyAnnouncement'])->name('api.announcements.destroy');
    Route::put('/sections/{id}', [ClassroomController::class, 'updateSection'])->name('api.sections.update');
    Route::put('/materials/{id}', [ClassroomController::class, 'updateMaterial'])->name('api.materials.update');
    Route::post('/materials/{id}', [ClassroomController::class, 'updateMaterial'])->name('api.materials.update.multipart'); // multipart replace
    Route::put('/tasks/{id}', [ClassroomController::class, 'updateTask'])->name('api.tasks.update');
    Route::delete('/sections/{id}', [ClassroomController::class, 'destroySection'])->name('api.sections.destroy');
    Route::delete('/materials/{id}', [ClassroomController::class, 'destroyMaterial'])->name('api.materials.destroy');
    Route::delete('/tasks/{id}', [ClassroomController::class, 'destroyTask'])->name('api.tasks.destroy');

    // Tasks & Submissions
    Route::get('/tasks/{id}', [TugasController::class, 'show'])->name('api.tasks.show');
    Route::post('/tasks/{id}/submit', [TugasController::class, 'submit'])->name('api.tasks.submit');
    Route::delete('/submissions/{id}', [TugasController::class, 'destroySubmission'])->name('api.submissions.destroy');
    Route::post('/submissions/{id}/grade', [TugasController::class, 'grade'])->name('api.submissions.grade');

    // KRS (Kartu Rencana Studi)
    Route::get('/krs', [KrsController::class, 'index'])->name('api.krs.index');
    Route::get('/krs/{id}', [KrsController::class, 'show'])->name('api.krs.show');
    Route::get('/krs/{id}/pdf', [KrsController::class, 'pdf'])->name('api.krs.pdf');
    Route::post('/krs', [KrsController::class, 'store'])->name('api.krs.store');
    Route::patch('/krs/{id}/approve', [KrsController::class, 'approve'])->name('api.krs.approve');

    // Perpustakaan (Library)
    Route::get('/library', [PerpustakaanController::class, 'index'])->name('api.library.index');
    Route::get('/library/{id}', [PerpustakaanController::class, 'show'])->name('api.library.show');
    Route::post('/library', [PerpustakaanController::class, 'store'])->name('api.library.store');
    Route::delete('/library/{id}', [PerpustakaanController::class, 'destroy'])->name('api.library.destroy');

    // Laporan Admin (Support Tickets)
    Route::get('/reports', [LaporanAdminController::class, 'index'])->name('api.reports.index');
    Route::get('/reports/categories', [LaporanAdminController::class, 'categories'])->name('api.reports.categories');
    Route::get('/reports/{id}', [LaporanAdminController::class, 'show'])->name('api.reports.show');
    Route::post('/reports', [LaporanAdminController::class, 'store'])->name('api.reports.store');
    Route::delete('/reports/{id}', [LaporanAdminController::class, 'destroy'])->name('api.reports.destroy');
    Route::patch('/reports/{id}/reply', [LaporanAdminController::class, 'reply'])->name('api.reports.reply');

    // Authorized file downloads (presigned URL redirect / JSON)
    Route::get('/files/{kind}/{id}', [FileController::class, 'show'])->name('api.files.show');

    // User Profile & Settings
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::post('/profile', [ProfileController::class, 'update'])->name('api.profile.update.multipart'); // multipart (PHP can't parse PUT bodies)
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('api.profile.photo.destroy');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password');

    // Admin module (role: admin)
    Route::middleware('admin')->prefix('admin')->name('api.admin.')->group(function () {
        Route::get('/stats', [AdminStatsController::class, 'index'])->name('stats');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/password', [AdminUserController::class, 'resetPassword'])->name('users.password');

        Route::get('/mahasiswa', [AdminMahasiswaController::class, 'index'])->name('mahasiswa.index');
        Route::get('/mahasiswa/{nrp}', [AdminMahasiswaController::class, 'show'])->name('mahasiswa.show');
        Route::put('/mahasiswa/{nrp}', [AdminMahasiswaController::class, 'update'])->name('mahasiswa.update');

        Route::get('/dosen', [AdminDosenController::class, 'index'])->name('dosen.index');
        Route::get('/dosen/{nip}/mahasiswa', [AdminDosenController::class, 'mahasiswa'])->name('dosen.mahasiswa');

        Route::get('/mata-kuliah', [AdminMataKuliahController::class, 'index'])->name('mata-kuliah.index');
        Route::post('/mata-kuliah', [AdminMataKuliahController::class, 'store'])->name('mata-kuliah.store');
        Route::get('/mata-kuliah/{kodeMk}', [AdminMataKuliahController::class, 'show'])->name('mata-kuliah.show');
        Route::put('/mata-kuliah/{kodeMk}', [AdminMataKuliahController::class, 'update'])->name('mata-kuliah.update');
        Route::delete('/mata-kuliah/{kodeMk}', [AdminMataKuliahController::class, 'destroy'])->name('mata-kuliah.destroy');

        Route::get('/kelas', [AdminKelasController::class, 'index'])->name('kelas.index');
        Route::post('/kelas', [AdminKelasController::class, 'store'])->name('kelas.store');
        Route::get('/kelas/{kelasId}', [AdminKelasController::class, 'show'])->name('kelas.show');
        Route::put('/kelas/{kelasId}', [AdminKelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{kelasId}', [AdminKelasController::class, 'destroy'])->name('kelas.destroy');

        Route::get('/krs', [AdminKrsController::class, 'index'])->name('krs.index');
        Route::post('/krs', [AdminKrsController::class, 'store'])->name('krs.store');
        Route::get('/krs/{krsId}', [AdminKrsController::class, 'show'])->name('krs.show');
        Route::patch('/krs/{krsId}/status', [AdminKrsController::class, 'updateStatus'])->name('krs.status');
        Route::delete('/krs/{krsId}', [AdminKrsController::class, 'destroy'])->name('krs.destroy');
        Route::post('/krs/{krsId}/kelas', [AdminKrsController::class, 'addKelas'])->name('krs.kelas.add');
        Route::patch('/krs/{krsId}/kelas/{kelasId}', [AdminKrsController::class, 'updateNilai'])->name('krs.kelas.nilai');
        Route::delete('/krs/{krsId}/kelas/{kelasId}', [AdminKrsController::class, 'removeKelas'])->name('krs.kelas.remove');

        Route::get('/library', [AdminPerpustakaanController::class, 'index'])->name('library.index');
        Route::put('/library/{bukuId}', [AdminPerpustakaanController::class, 'update'])->name('library.update');
        Route::post('/library/{bukuId}', [AdminPerpustakaanController::class, 'update'])->name('library.update.multipart');
        Route::delete('/library/{bukuId}', [AdminPerpustakaanController::class, 'destroy'])->name('library.destroy');
    });
});
