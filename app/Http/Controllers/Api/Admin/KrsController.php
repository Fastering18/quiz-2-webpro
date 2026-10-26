<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailKrs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KrsController extends Controller
{
    /**
     * List all KRS (filters: nrp, semester_tahun, status_acc).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Krs::with(['mahasiswa.user', 'detailKrs.classroom.mataKuliah']);

        foreach (['nrp', 'semester_tahun', 'status_acc'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        return response()->json(
            $query->orderByDesc('semester_tahun')->orderBy('nrp')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    /**
     * Create a KRS for a student (override), optionally with classes.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nrp' => ['required', 'string', 'exists:mahasiswa,nrp'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'semester_tahun' => ['required', 'string', 'max:12'],
            'status_acc' => ['nullable', 'in:Disetujui,Ditolak,Menunggu'],
            'kelas_ids' => ['nullable', 'array'],
            'kelas_ids.*' => ['string', 'exists:classroom,kelas_id'],
        ]);

        if (Krs::where('nrp', $validated['nrp'])->where('semester_tahun', $validated['semester_tahun'])->exists()) {
            return response()->json([
                'message' => 'KRS untuk mahasiswa dan semester tersebut sudah ada.',
            ], 409);
        }

        $status = $validated['status_acc'] ?? 'Disetujui';

        $krs = DB::transaction(function () use ($validated, $status) {
            $krs = Krs::create([
                'krs_id' => 'KRS_'.str_replace('/', '', $validated['semester_tahun']).'_'.$validated['nrp'],
                'nrp' => $validated['nrp'],
                'semester' => $validated['semester'],
                'semester_tahun' => $validated['semester_tahun'],
                'status_acc' => $status,
                'tanggal_cetak' => $status === 'Disetujui' ? now()->toDateString() : null,
            ]);

            foreach (array_unique($validated['kelas_ids'] ?? []) as $kelasId) {
                DetailKrs::create(['krs_id' => $krs->krs_id, 'kelas_id' => $kelasId, 'nilai_akhir' => null]);
            }

            return $krs;
        });

        return response()->json([
            'message' => 'KRS berhasil dibuat.',
            'krs' => $krs->load(['mahasiswa.user', 'detailKrs.classroom.mataKuliah']),
        ], 201);
    }

    public function show(string $krsId): JsonResponse
    {
        return response()->json([
            'krs' => Krs::with(['mahasiswa.user', 'detailKrs.classroom.mataKuliah'])->findOrFail($krsId),
        ]);
    }

    public function updateStatus(Request $request, string $krsId): JsonResponse
    {
        $krs = Krs::findOrFail($krsId);

        $validated = $request->validate([
            'status_acc' => ['required', 'string', 'in:Disetujui,Ditolak,Menunggu'],
        ]);

        $krs->update([
            'status_acc' => $validated['status_acc'],
            'tanggal_cetak' => $validated['status_acc'] === 'Disetujui' ? now()->toDateString() : null,
        ]);

        return response()->json([
            'message' => 'Status KRS berhasil diperbarui.',
            'krs' => $krs,
        ]);
    }

    /**
     * Enroll a student's KRS in a class (admin override: quota is not enforced).
     */
    public function addKelas(Request $request, string $krsId): JsonResponse
    {
        $krs = Krs::findOrFail($krsId);

        $validated = $request->validate([
            'kelas_id' => ['required', 'string', 'exists:classroom,kelas_id'],
        ]);

        if (DetailKrs::where('krs_id', $krs->krs_id)->where('kelas_id', $validated['kelas_id'])->exists()) {
            return response()->json([
                'message' => 'Mahasiswa sudah terdaftar di kelas tersebut.',
            ], 409);
        }

        DetailKrs::create(['krs_id' => $krs->krs_id, 'kelas_id' => $validated['kelas_id'], 'nilai_akhir' => null]);

        return response()->json([
            'message' => 'Kelas berhasil ditambahkan ke KRS.',
            'krs' => $krs->load(['detailKrs.classroom.mataKuliah']),
        ], 201);
    }

    public function updateNilai(Request $request, string $krsId, string $kelasId): JsonResponse
    {
        $detail = DetailKrs::where('krs_id', $krsId)->where('kelas_id', $kelasId)->firstOrFail();

        $validated = $request->validate([
            'nilai_akhir' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $detail->update(['nilai_akhir' => $validated['nilai_akhir'] ?? null]);

        return response()->json([
            'message' => 'Nilai akhir berhasil diperbarui.',
            'detail' => $detail,
        ]);
    }

    public function removeKelas(string $krsId, string $kelasId): JsonResponse
    {
        DetailKrs::where('krs_id', $krsId)->where('kelas_id', $kelasId)->firstOrFail()->delete();

        return response()->json([
            'message' => 'Kelas berhasil dihapus dari KRS.',
        ]);
    }

    public function destroy(string $krsId): JsonResponse
    {
        Krs::findOrFail($krsId)->delete(); // detail_krs rows cascade

        return response()->json([
            'message' => 'KRS berhasil dihapus.',
        ]);
    }
}
