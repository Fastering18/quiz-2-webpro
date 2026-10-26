<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Krs;
use App\Models\PengumumanClassroom;
use App\Models\TugasKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Provide summarized dashboard data for student or role.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            $mahasiswa = $user->mahasiswa()->with('dosenWali.user')->first();

            if (! $mahasiswa) {
                return response()->json([
                    'message' => 'Data mahasiswa tidak ditemukan.',
                ], 404);
            }

            // Get latest KRS
            $activeKrs = Krs::with(['detailKrs.classroom.mataKuliah', 'detailKrs.classroom.dosen.user'])
                ->where('nrp', $mahasiswa->nrp)
                ->orderByDesc('semester_tahun')
                ->first();

            $enrolledClassrooms = collect();
            $totalSks = 0;

            if ($activeKrs) {
                foreach ($activeKrs->detailKrs as $detail) {
                    if ($detail->classroom) {
                        $enrolledClassrooms->push($detail->classroom);
                        $totalSks += (int) ($detail->classroom->mataKuliah->sks ?? 0);
                    }
                }
            }

            $enrolledClassIds = $enrolledClassrooms->pluck('kelas_id');

            // Pending tasks (tasks where student hasn't submitted yet and deadline >= now)
            $pendingTasks = TugasKelas::with(['bagianKelas.classroom.mataKuliah'])
                ->whereHas('bagianKelas', function ($q) use ($enrolledClassIds) {
                    $q->whereIn('kelas_id', $enrolledClassIds);
                })
                ->whereDoesntHave('pengumpulan', function ($q) use ($mahasiswa) {
                    $q->where('nrp', $mahasiswa->nrp);
                })
                ->where('tenggat_waktu', '>=', now())
                ->orderBy('tenggat_waktu')
                ->take(5)
                ->get();

            // Recent announcements across enrolled classes
            $recentAnnouncements = PengumumanClassroom::with(['classroom.mataKuliah'])
                ->whereIn('kelas_id', $enrolledClassIds)
                ->orderByDesc('tanggal_unggah')
                ->take(5)
                ->get();

            return response()->json([
                'user' => [
                    'nama_lengkap' => $user->nama_lengkap,
                    'nrp' => $mahasiswa->nrp,
                    'departemen' => $mahasiswa->departemen,
                    'semester_aktif' => $mahasiswa->semester_aktif,
                    'status' => $mahasiswa->status,
                    'dosen_wali' => $mahasiswa->dosenWali?->user?->nama_lengkap ?? '-',
                ],
                'summary' => [
                    'total_sks' => $totalSks,
                    'total_kelas' => $enrolledClassrooms->count(),
                    'tugas_belum_selesai' => $pendingTasks->count(),
                    'semester_tahun' => $activeKrs?->semester_tahun ?? '2024/Ganjil',
                ],
                'enrolled_classrooms' => $enrolledClassrooms->values(),
                'upcoming_tasks' => $pendingTasks,
                'announcements' => $recentAnnouncements,
            ]);
        }

        // Dosen Dashboard
        if ($user->role === 'dosen') {
            $dosen = $user->dosen;
            $classes = Classroom::with('mataKuliah')
                ->where('nip', $dosen?->nip)
                ->get();

            return response()->json([
                'user' => $user,
                'dosen' => $dosen,
                'total_kelas_ajar' => $classes->count(),
                'classrooms' => $classes,
            ]);
        }

        // Admin Dashboard
        return response()->json([
            'user' => $user,
            'role' => 'admin',
            'message' => 'Dashboard Administrator',
            'stats' => StatsController::summary(),
        ]);
    }
}
