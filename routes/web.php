<?php

use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/files/local', [FileController::class, 'local'])
    ->middleware('signed')
    ->name('files.local');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('portal');
    }

    return view('welcome');
})->name('login');

Route::get('/reset-password', fn () => view('reset-password'))->name('password.reset');

Route::middleware('auth')->group(function () {
    Route::get('/portal', function () {
        return view('portal');
    })->name('portal');

    Route::get('/classroom', function () {
        return view('classroom');
    })->name('classroom');

    Route::get('/library', function () {
        return view('library');
    })->name('library');

    Route::get('/krs', function () {
        return view('krs');
    })->name('krs');

    Route::get('/lapor-admin', function () {
        return view('lapor-admin');
    })->name('lapor-admin');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/admin-panel', function () {
        return view('admin-panel');
    })->name('admin-panel');
});
