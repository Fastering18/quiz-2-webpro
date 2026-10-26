<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\DetailKrs;
use App\Models\Krs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KrsController extends Controller
{
    /**
     * List KRS for the authenticated student or for a specific student (dosen/admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            $nrp = $user->mahasiswa?->nrp;

            $krsList = Krs::with(['detailKrs.classroom.mataKuliah', 'detailKrs.classroom.dosen.user'])
                ->where('nrp', $nrp)
                ->orderByDesc('semester_tahun')
                ->get();

            return response()->json([
                'krs_list' => $krsList,
            ]);
        }

        // For Dosen / Admin: query by nrp parameter
        $targetNrp = $request->query('nrp');
        $query = Krs::with(['mahasiswa.user', 'detailKrs.classroom.mataKuliah']);

        if ($targetNrp) {
            $query->where('nrp', $targetNrp);
        }

        return response()->json([
            'krs_list' => $query->orderByDesc('semester_tahun')->get(),
        ]);
    }

    /**
     * Show a specific KRS with all enrolled course rows, SKS sum, and status.
     */
    public function show(string $krsId): JsonResponse
    {
        $krs = Krs::with([
            'mahasiswa.user',
            'mahasiswa.dosenWali.user',
            'detailKrs.classroom.mataKuliah',
            'detailKrs.classroom.dosen.user',
        ])->findOrFail($krsId);

        $totalSks = $krs->detailKrs->sum(function ($detail) {
            return $detail->classroom?->mataKuliah?->sks ?? 0;
        });

        return response()->json([
            'krs' => $krs,
            'total_sks' => $totalSks,
        ]);
    }

    /**
     * Submit a new KRS with selected classroom IDs.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'message' => 'Hanya mahasiswa yang dapat mengisi KRS.',
            ], 403);
        }

        $mahasiswa = $user->mahasiswa;

        $validated = $request->validate([
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'semester_tahun' => ['required', 'string', 'max:12'],
            'kelas_ids' => ['required', 'array', 'min:1'],
            'kelas_ids.*' => ['required', 'string', 'exists:classroom,kelas_id'],
        ]);

        $krsId = 'KRS_'.str_replace('/', '', $validated['semester_tahun']).'_'.$mahasiswa->nrp;

        $krs = DB::transaction(function () use ($krsId, $mahasiswa, $validated) {
            $krs = Krs::updateOrCreate(
                [
                    'nrp' => $mahasiswa->nrp,
                    'semester_tahun' => $validated['semester_tahun'],
                ],
                [
                    'krs_id' => $krsId,
                    'semester' => $validated['semester'],
                    'status_acc' => 'Menunggu',
                    'tanggal_cetak' => null,
                ]
            );

            // Sync classroom details
            DetailKrs::where('krs_id', $krs->krs_id)->delete();

            foreach ($validated['kelas_ids'] as $kelasId) {
                DetailKrs::create([
                    'krs_id' => $krs->krs_id,
                    'kelas_id' => $kelasId,
                    'nilai_akhir' => null,
                ]);
            }

            return $krs;
        });

        return response()->json([
            'message' => 'KRS berhasil diajukan dan menunggu persetujuan Dosen Wali.',
            'krs' => $krs->load(['detailKrs.classroom.mataKuliah', 'detailKrs.classroom.dosen.user']),
        ], 201);
    }

    /**
     * Approve student KRS (Dosen Wali / Admin).
     */
    public function approve(Request $request, string $krsId): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $krs = Krs::findOrFail($krsId);

        $validated = $request->validate([
            'status_acc' => ['required', 'string', 'in:Disetujui,Ditolak,Menunggu'],
        ]);

        $krs->update([
            'status_acc' => $validated['status_acc'],
            'tanggal_cetak' => $validated['status_acc'] === 'Disetujui' ? now()->toDateString() : null,
        ]);

        return response()->json([
            'message' => 'Status persetujuan KRS berhasil diperbarui.',
            'krs' => $krs,
        ]);
    }
}
