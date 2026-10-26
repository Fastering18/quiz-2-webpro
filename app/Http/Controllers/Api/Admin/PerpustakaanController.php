<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perpustakaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerpustakaanController extends Controller
{
    /**
     * List every library item (filters: q, kategori, publisher_id).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Perpustakaan::with('publisher');

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('judul_buku', 'like', "%{$q}%")->orWhere('penulis', 'like', "%{$q}%");
            });
        }

        foreach (['kategori', 'publisher_id'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        return response()->json(
            $query->orderBy('judul_buku')->paginate(min((int) $request->query('per_page', 15), 100) ?: 15)
        );
    }

    public function update(Request $request, string $bukuId): JsonResponse
    {
        $book = Perpustakaan::findOrFail($bukuId);

        $validated = $request->validate([
            'judul_buku' => ['sometimes', 'string', 'max:200'],
            'penulis' => ['sometimes', 'string', 'max:160'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'kategori' => ['sometimes', 'string', 'max:40'],
        ]);

        $book->update($validated);

        return response()->json([
            'message' => 'Data buku berhasil diperbarui.',
            'book' => $book->fresh('publisher'),
        ]);
    }

    public function destroy(string $bukuId): JsonResponse
    {
        Perpustakaan::findOrFail($bukuId)->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus dari perpustakaan.',
        ]);
    }
}
