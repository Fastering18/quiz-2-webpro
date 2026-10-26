<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MataKuliahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MataKuliah::withCount('classroom');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('kode_mk', 'like', "%{$q}%")->orWhere('nama_mk', 'like', "%{$q}%");
            });
        }

        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        return response()->json(
            $query->orderBy('kode_mk')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_mk' => ['required', 'string', 'max:20', 'unique:mata_kuliah,kode_mk'],
            'nama_mk' => ['required', 'string', 'max:120'],
            'sks' => ['required', 'integer', 'min:1', 'max:8'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
        ]);

        return response()->json([
            'message' => 'Mata kuliah berhasil ditambahkan.',
            'mata_kuliah' => MataKuliah::create($validated),
        ], 201);
    }

    public function show(string $kodeMk): JsonResponse
    {
        return response()->json([
            'mata_kuliah' => MataKuliah::withCount('classroom')->findOrFail($kodeMk),
        ]);
    }

    public function update(Request $request, string $kodeMk): JsonResponse
    {
        $mk = MataKuliah::findOrFail($kodeMk);

        $validated = $request->validate([
            'nama_mk' => ['sometimes', 'string', 'max:120'],
            'sks' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:14'],
        ]);

        $mk->update($validated);

        return response()->json([
            'message' => 'Mata kuliah berhasil diperbarui.',
            'mata_kuliah' => $mk,
        ]);
    }

    public function destroy(string $kodeMk): JsonResponse
    {
        $mk = MataKuliah::findOrFail($kodeMk);

        if ($mk->classroom()->exists()) {
            return response()->json([
                'message' => 'Mata kuliah tidak dapat dihapus karena masih digunakan oleh kelas.',
            ], 409);
        }

        $mk->delete();

        return response()->json([
            'message' => 'Mata kuliah berhasil dihapus.',
        ]);
    }
}
