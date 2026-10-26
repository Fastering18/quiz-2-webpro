<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KrsController;
use App\Http\Controllers\Api\LaporanAdminController;
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
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Current User & Logout
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // Classroom & Materials
    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('api.classrooms.index');
    Route::get('/classrooms/{id}', [ClassroomController::class, 'show'])->name('api.classrooms.show');
    Route::post('/classrooms/{id}/announcements', [ClassroomController::class, 'storeAnnouncement'])->name('api.classrooms.announcements.store');
    Route::post('/classrooms/{id}/sections', [ClassroomController::class, 'storeSection'])->name('api.classrooms.sections.store');
    Route::post('/sections/{id}/materials', [ClassroomController::class, 'storeMaterial'])->name('api.sections.materials.store');
    Route::post('/sections/{id}/tasks', [ClassroomController::class, 'storeTask'])->name('api.sections.tasks.store');

    // Tasks & Submissions
    Route::get('/tasks/{id}', [TugasController::class, 'show'])->name('api.tasks.show');
    Route::post('/tasks/{id}/submit', [TugasController::class, 'submit'])->name('api.tasks.submit');
    Route::post('/submissions/{id}/grade', [TugasController::class, 'grade'])->name('api.submissions.grade');

    // KRS (Kartu Rencana Studi)
    Route::get('/krs', [KrsController::class, 'index'])->name('api.krs.index');
    Route::get('/krs/{id}', [KrsController::class, 'show'])->name('api.krs.show');
    Route::post('/krs', [KrsController::class, 'store'])->name('api.krs.store');
    Route::patch('/krs/{id}/approve', [KrsController::class, 'approve'])->name('api.krs.approve');

    // Perpustakaan (Library)
    Route::get('/library', [PerpustakaanController::class, 'index'])->name('api.library.index');
    Route::get('/library/{id}', [PerpustakaanController::class, 'show'])->name('api.library.show');
    Route::post('/library', [PerpustakaanController::class, 'store'])->name('api.library.store');
    Route::delete('/library/{id}', [PerpustakaanController::class, 'destroy'])->name('api.library.destroy');

    // Laporan Admin (Support Tickets)
    Route::get('/reports', [LaporanAdminController::class, 'index'])->name('api.reports.index');
    Route::get('/reports/{id}', [LaporanAdminController::class, 'show'])->name('api.reports.show');
    Route::post('/reports', [LaporanAdminController::class, 'store'])->name('api.reports.store');
    Route::patch('/reports/{id}/reply', [LaporanAdminController::class, 'reply'])->name('api.reports.reply');

    // User Profile & Settings
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password');
});
