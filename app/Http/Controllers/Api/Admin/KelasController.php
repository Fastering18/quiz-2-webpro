<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Classroom::with(['mataKuliah', 'dosen.user'])->withCount('detailKrs');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('kelas_id', 'like', "%{$q}%")->orWhere('nama_kelas', 'like', "%{$q}%");
            });
        }

        foreach (['kode_mk', 'nip', 'semester_tahun'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        return response()->json(
            $query->orderBy('kelas_id')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // detail_krs.kelas_id is limited to 12 characters
            'kelas_id' => ['required', 'string', 'max:12', 'unique:classroom,kelas_id'],
            'kode_mk' => ['required', 'string', 'exists:mata_kuliah,kode_mk'],
            'nip' => ['required', 'string', 'exists:dosen,nip'],
            'nama_kelas' => ['required', 'string', 'max:60'],
            'semester_tahun' => ['required', 'string', 'max:12'],
            'kuota_kelas' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        return response()->json([
            'message' => 'Kelas berhasil dibuat.',
            'classroom' => Classroom::create($validated)->load(['mataKuliah', 'dosen.user']),
        ], 201);
    }

    public function show(string $kelasId): JsonResponse
    {
        return response()->json([
            'classroom' => Classroom::with(['mataKuliah', 'dosen.user', 'detailKrs.krs.mahasiswa.user'])
                ->withCount('detailKrs')
                ->findOrFail($kelasId),
        ]);
    }

    /**
     * Update class data, e.g. assign another dosen pengampu or mata kuliah.
     */
    public function update(Request $request, string $kelasId): JsonResponse
    {
        $classroom = Classroom::withCount('detailKrs')->findOrFail($kelasId);

        $validated = $request->validate([
            'kode_mk' => ['sometimes', 'string', 'exists:mata_kuliah,kode_mk'],
            'nip' => ['sometimes', 'string', 'exists:dosen,nip'],
            'nama_kelas' => ['sometimes', 'string', 'max:60'],
            'semester_tahun' => ['sometimes', 'string', 'max:12'],
            'kuota_kelas' => ['sometimes', 'integer', 'min:'.max(1, $classroom->detail_krs_count), 'max:1000'],
        ]);

        $classroom->update($validated);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui.',
            'classroom' => $classroom->fresh(['mataKuliah', 'dosen.user']),
        ]);
    }

    public function destroy(string $kelasId): JsonResponse
    {
        $classroom = Classroom::findOrFail($kelasId);

        if ($classroom->detailKrs()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki mahasiswa terdaftar di KRS.',
            ], 409);
        }

        $classroom->delete();

        return response()->json([
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }
}
