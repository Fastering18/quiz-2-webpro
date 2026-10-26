<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('portal');
    }

    return view('welcome');
})->name('login');

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
});
