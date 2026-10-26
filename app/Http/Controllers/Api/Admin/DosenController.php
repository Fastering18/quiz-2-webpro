<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    /**
     * List dosen with number of perwalian students and classes taught.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dosen::with('user')->withCount(['mahasiswaBimbingan', 'classroom']);

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('nip', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama_lengkap', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(
            $query->orderBy('nip')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    /**
     * Students under this dosen wali.
     */
    public function mahasiswa(Request $request, string $nip): JsonResponse
    {
        $dosen = Dosen::with('user')->findOrFail($nip);

        $students = $dosen->mahasiswaBimbingan()
            ->with('user')
            ->orderBy('nrp')
            ->paginate(min((int) $request->query('per_page', 15), 100) ?: 15);

        return response()->json([
            'dosen' => $dosen,
            'mahasiswa' => $students,
        ]);
    }
}
