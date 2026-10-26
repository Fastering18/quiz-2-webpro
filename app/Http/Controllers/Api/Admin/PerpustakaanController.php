<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perpustakaan;
use App\Services\FileStorage;
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

    /**
     * Update metadata and/or replace the document. To replace the file send multipart/form-data
     * with POST (or POST + _method=PUT), since PHP does not parse multipart PUT bodies.
     */
    public function update(Request $request, string $bukuId, FileStorage $files): JsonResponse
    {
        $book = Perpustakaan::findOrFail($bukuId);

        $validated = $request->validate([
            'judul_buku' => ['sometimes', 'string', 'max:200'],
            'penulis' => ['sometimes', 'string', 'max:160'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'kategori' => ['sometimes', 'string', 'max:40'],
            'file' => ['nullable', ...$files->rules('library')],
        ]);

        $oldKey = $book->file_dokumen_key;
        unset($validated['file']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['nama_file'] = $file->getClientOriginalName();
            $validated['ukuran_file'] = $file->getSize();
            $validated['file_dokumen_key'] = $files->store($file, 'library/'.$book->buku_id);
        }

        $book->update($validated);

        if (isset($validated['file_dokumen_key']) && $validated['file_dokumen_key'] !== $oldKey) {
            $files->delete($oldKey);
        }

        return response()->json([
            'message' => 'Data buku berhasil diperbarui.',
            'book' => $book->fresh('publisher'),
        ]);
    }

    public function destroy(string $bukuId, FileStorage $files): JsonResponse
    {
        $book = Perpustakaan::findOrFail($bukuId);
        $key = $book->file_dokumen_key;
        $book->delete();
        $files->delete($key);

        return response()->json([
            'message' => 'Buku berhasil dihapus dari perpustakaan.',
        ]);
    }
}
