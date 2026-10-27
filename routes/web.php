<?php

use App\Http\Controllers\Api\Admin\StatsController as AdminStatsController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\KrsController;
use App\Http\Controllers\Api\LaporanAdminController;
use App\Http\Controllers\Api\PerpustakaanController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
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

Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        .'<url><loc>'.url('/').'</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>'
        .'<url><loc>'.url('/reset-password').'</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>'
        .'</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::post('/logout', function (Request $request) {
    $user = $request->user();
    if ($user) {
        $user->tokens()->delete();
    }

    Auth::guard('web')->logout();

    if ($request->hasSession()) {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    $response = $request->expectsJson()
        ? response()->json(['message' => 'Logout berhasil. Sesi telah diakhiri.'])
        : redirect()->route('login');

    return $response->header('Clear-Site-Data', '"cookies", "storage"');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/portal', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);
        $initialData = app(DashboardController::class)->index($request)->getData(true);

        return view('portal', [
            'authUser' => $authUser,
            'initialData' => $initialData,
        ]);
    })->name('portal');

    Route::get('/classroom', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);
        $classroomsRes = app(ClassroomController::class)->index($request)->getData(true);
        $classrooms = $classroomsRes['classrooms'] ?? ($classroomsRes['data'] ?? []);

        $firstClass = null;
        if (! empty($classrooms[0]['kelas_id'])) {
            $showRes = app(ClassroomController::class)->show($request, $classrooms[0]['kelas_id'])->getData(true);
            $firstClass = $showRes['classroom'] ?? null;
        }

        return view('classroom', [
            'authUser' => $authUser,
            'initialData' => [
                'classrooms' => $classrooms,
                'firstClass' => $firstClass,
            ],
        ]);
    })->name('classroom');

    Route::get('/library', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);
        $libraryData = app(PerpustakaanController::class)->index($request)->getData(true);

        return view('library', [
            'authUser' => $authUser,
            'initialData' => $libraryData,
        ]);
    })->name('library');

    Route::get('/krs', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);

        return view('krs', [
            'authUser' => $authUser,
            'initialData' => app(KrsController::class)->index($request)->getData(true),
        ]);
    })->name('krs');

    Route::get('/lapor-admin', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);

        return view('lapor-admin', [
            'authUser' => $authUser,
            'initialData' => app(LaporanAdminController::class)->index($request)->getData(true),
        ]);
    })->name('lapor-admin');

    Route::get('/profile', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);

        return view('profile', [
            'authUser' => $authUser,
            'initialData' => app(ProfileController::class)->show($request)->getData(true),
        ]);
    })->name('profile');

    Route::get('/admin-panel', function (Request $request) {
        $authUser = Auth::user()?->loadMissing(['mahasiswa.dosenWali.user', 'dosen']);
        $initialData = null;

        if ($authUser?->role === 'admin') {
            $initialData = [
                'stats' => app(AdminStatsController::class)->index()->getData(true)['stats'] ?? [],
                'users' => app(AdminUserController::class)->index($request)->getData(true),
            ];
        }

        return view('admin-panel', [
            'authUser' => $authUser,
            'initialData' => $initialData,
        ]);
    })->name('admin-panel');

    Route::get('/files/{kind}/{id}', [FileController::class, 'show'])
        ->whereIn('kind', ['materials', 'submissions', 'library', 'reports', 'avatars'])
        ->name('files.show');
});
