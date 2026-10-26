<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Krs;
use App\Models\LaporanAdmin;
use App\Models\MataKuliah;
use App\Models\PengumpulanTugas;
use App\Models\Perpustakaan;
use App\Models\TugasKelas;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['stats' => self::summary()]);
    }

    /**
     * Aggregate counters shared by /admin/stats and the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        $perRole = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return [
            'pengguna' => [
                'total' => (int) $perRole->sum(),
                'mahasiswa' => (int) ($perRole['mahasiswa'] ?? 0),
                'dosen' => (int) ($perRole['dosen'] ?? 0),
                'admin' => (int) ($perRole['admin'] ?? 0),
            ],
            'mata_kuliah' => MataKuliah::count(),
            'kelas' => Classroom::count(),
            'krs' => [
                'total' => Krs::count(),
                'menunggu' => Krs::where('status_acc', 'Menunggu')->count(),
            ],
            'tugas' => TugasKelas::count(),
            'pengumpulan_tugas' => PengumpulanTugas::count(),
            'buku' => Perpustakaan::count(),
            'tiket' => [
                'total' => LaporanAdmin::count(),
                'terbuka' => LaporanAdmin::whereIn('status_tiket', ['Terkirim', 'Diproses'])->count(),
            ],
        ];
    }
}
