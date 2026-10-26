<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perpustakaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PerpustakaanController extends Controller
{
    /**
     * Display a paginated listing of library books with search and filter.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $kategori = $request->query('kategori');

        $query = Perpustakaan::with('publisher');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul_buku', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            });
        }

        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        $books = $query->orderBy('judul_buku')->paginate(12);

        // Also return available categories for dropdown filters
        $categories = Perpustakaan::select('kategori')->distinct()->pluck('kategori');

        return response()->json([
            'books' => $books,
            'categories' => $categories,
        ]);
    }

    /**
     * Display the specified library book.
     */
    public function show(string $bukuId): JsonResponse
    {
        $book = Perpustakaan::with('publisher')->findOrFail($bukuId);

        return response()->json([
            'book' => $book,
        ]);
    }

    /**
     * Store a new book / document into the library.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            return response()->json([
                'message' => 'Hanya dosen atau admin yang dapat menambahkan buku ke perpustakaan.',
            ], 403);
        }

        $validated = $request->validate([
            'judul_buku' => ['required', 'string', 'max:200'],
            'penulis' => ['required', 'string', 'max:160'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'kategori' => ['required', 'string', 'max:40'],
            'file' => ['nullable', 'file', 'max:102400'], // max 100MB
            'nama_file' => ['nullable', 'string'],
            'file_dokumen_key' => ['nullable', 'string'],
            'ukuran_file' => ['nullable', 'integer'],
        ]);

        $bukuId = 'BK_'.strtoupper(Str::random(6));

        $namaFile = $validated['nama_file'] ?? 'book.pdf';
        $fileKey = $validated['file_dokumen_key'] ?? 'library/'.Str::random(20).'.pdf';
        $ukuranFile = (int) ($validated['ukuran_file'] ?? 5242880);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $namaFile = $file->getClientOriginalName();
            $fileKey = $file->store('library', 'public');
            $ukuranFile = $file->getSize();
        }

        $book = Perpustakaan::create([
            'buku_id' => $bukuId,
            'judul_buku' => $validated['judul_buku'],
            'penulis' => $validated['penulis'],
            'publisher_id' => $user->user_id,
            'tahun' => $validated['tahun'] ?? null,
            'kategori' => $validated['kategori'],
            'nama_file' => $namaFile,
            'file_dokumen_key' => $fileKey,
            'ukuran_file' => $ukuranFile,
        ]);

        return response()->json([
            'message' => 'Buku perpustakaan berhasil ditambahkan.',
            'book' => $book,
        ], 201);
    }

    /**
     * Remove the specified book from the library.
     */
    public function destroy(Request $request, string $bukuId): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $book = Perpustakaan::findOrFail($bukuId);
        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus dari perpustakaan.',
        ]);
    }
}
