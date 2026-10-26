<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MahasiswaController extends Controller
{
    /**
     * List mahasiswa with their dosen wali (filters: q, nip_wali, status, departemen).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mahasiswa::with(['user', 'dosenWali.user']);

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('nrp', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama_lengkap', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            });
        }

        foreach (['nip_wali', 'status', 'departemen'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        return response()->json(
            $query->orderBy('nrp')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    public function show(string $nrp): JsonResponse
    {
        return response()->json([
            'mahasiswa' => Mahasiswa::with(['user', 'dosenWali.user', 'krs'])->findOrFail($nrp),
        ]);
    }

    /**
     * Update controlled data: dosen wali, departemen, semester, status.
     */
    public function update(Request $request, string $nrp): JsonResponse
    {
        $mahasiswa = Mahasiswa::findOrFail($nrp);

        $validated = $request->validate([
            'nip_wali' => ['sometimes', 'string', 'exists:dosen,nip'],
            'departemen' => ['sometimes', 'string', 'max:80'],
            'semester_aktif' => ['sometimes', 'integer', 'min:1', 'max:14'],
            'status' => ['sometimes', 'string', 'max:20'],
        ]);

        $mahasiswa->update($validated);

        return response()->json([
            'message' => 'Data mahasiswa berhasil diperbarui.',
            'mahasiswa' => $mahasiswa->fresh(['user', 'dosenWali.user']),
        ]);
    }
}
