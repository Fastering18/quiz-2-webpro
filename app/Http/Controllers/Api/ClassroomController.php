<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BagianKelas;
use App\Models\Classroom;
use App\Models\MateriKelas;
use App\Models\PengumumanClassroom;
use App\Models\TugasKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    /**
     * Display a listing of classrooms for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            $nrp = $user->mahasiswa?->nrp;

            $classrooms = Classroom::with(['mataKuliah', 'dosen.user'])
                ->whereHas('detailKrs.krs', function ($q) use ($nrp) {
                    $q->where('nrp', $nrp);
                })
                ->get();

            return response()->json([
                'classrooms' => $classrooms,
            ]);
        }

        if ($user->role === 'dosen') {
            $nip = $user->dosen?->nip;

            $classrooms = Classroom::with(['mataKuliah', 'dosen.user'])
                ->where('nip', $nip)
                ->get();

            return response()->json([
                'classrooms' => $classrooms,
            ]);
        }

        // Admin sees all classrooms
        $classrooms = Classroom::with(['mataKuliah', 'dosen.user'])
            ->paginate(15);

        return response()->json($classrooms);
    }

    /**
     * Display detailed classroom view with sections, materials, and tasks.
     */
    public function show(Request $request, string $kelasId): JsonResponse
    {
        $user = $request->user();
        $studentNrp = $user->role === 'mahasiswa' ? $user->mahasiswa?->nrp : null;

        $query = Classroom::with([
            'mataKuliah',
            'dosen.user',
            'pengumuman' => fn ($q) => $q->orderByDesc('tanggal_unggah'),
            'bagianKelas' => function ($q) use ($studentNrp) {
                $q->orderBy('urutan')
                    ->with([
                        'materi',
                        'tugas' => function ($tq) use ($studentNrp) {
                            $tq->orderBy('urutan');
                            if ($studentNrp) {
                                $tq->with(['pengumpulan' => function ($sq) use ($studentNrp) {
                                    $sq->where('nrp', $studentNrp);
                                }]);
                            } else {
                                $tq->with(['pengumpulan.mahasiswa.user']);
                            }
                        },
                    ]);
            },
            'detailKrs.krs.mahasiswa.user',
        ]);

        if ($user->role === 'dosen') {
            $query->where('nip', $user->dosen?->nip);
        }

        $classroom = $query->findOrFail($kelasId);

        return response()->json([
            'classroom' => $classroom,
        ]);
    }

    /**
     * Add an announcement to a classroom.
     */
    public function storeAnnouncement(Request $request, string $kelasId): JsonResponse
    {
        $classroom = Classroom::findOrFail($kelasId);

        $validated = $request->validate([
            'konten' => ['required', 'string', 'max:2000'],
        ]);

        $announcement = PengumumanClassroom::create([
            'kelas_id' => $classroom->kelas_id,
            'konten' => $validated['konten'],
            'tanggal_unggah' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Pengumuman berhasil diposting.',
            'announcement' => $announcement,
        ], 201);
    }

    /**
     * Add a new section (pertemuan/bagian) to a classroom.
     */
    public function storeSection(Request $request, string $kelasId): JsonResponse
    {
        $classroom = Classroom::findOrFail($kelasId);

        $validated = $request->validate([
            'judul_bagian' => ['required', 'string', 'max:160'],
            'judul_ringkasan' => ['nullable', 'string', 'max:160'],
            'konten_ringkasan' => ['nullable', 'string', 'max:5000'],
            'urutan' => ['nullable', 'integer'],
        ]);

        $nextUrutan = $validated['urutan'] ?? ((BagianKelas::where('kelas_id', $classroom->kelas_id)->max('urutan') ?? 0) + 1);

        $section = BagianKelas::create([
            'kelas_id' => $classroom->kelas_id,
            'urutan' => $nextUrutan,
            'judul_bagian' => $validated['judul_bagian'],
            'judul_ringkasan' => $validated['judul_ringkasan'] ?? null,
            'konten_ringkasan' => $validated['konten_ringkasan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Bagian kelas berhasil dibuat.',
            'section' => $section,
        ], 201);
    }

    /**
     * Upload/attach material to a section.
     */
    public function storeMaterial(Request $request, string|int $bagianId): JsonResponse
    {
        $section = BagianKelas::findOrFail($bagianId);

        $validated = $request->validate([
            'judul_materi' => ['required', 'string', 'max:160'],
            'file' => ['nullable', 'file', 'max:51200'], // max 50MB
            'nama_file' => ['nullable', 'string'],
            'file_materi_key' => ['nullable', 'string'],
            'ukuran_file' => ['nullable', 'integer'],
        ]);

        $namaFile = $validated['nama_file'] ?? 'document.pdf';
        $fileKey = $validated['file_materi_key'] ?? 'materials/'.Str::random(20).'.pdf';
        $ukuranFile = (int) ($validated['ukuran_file'] ?? 1024000);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $namaFile = $file->getClientOriginalName();
            $fileKey = $file->store('materials', 'public');
            $ukuranFile = $file->getSize();
        }

        $material = MateriKelas::create([
            'bagian_id' => $section->bagian_id,
            'judul_materi' => $validated['judul_materi'],
            'nama_file' => $namaFile,
            'file_materi_key' => $fileKey,
            'ukuran_file' => $ukuranFile,
            'tanggal_unggah' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Materi berhasil diunggah.',
            'material' => $material,
        ], 201);
    }

    /**
     * Create a task/assignment inside a section.
     */
    public function storeTask(Request $request, string|int $bagianId): JsonResponse
    {
        $section = BagianKelas::findOrFail($bagianId);

        $validated = $request->validate([
            'judul_tugas' => ['required', 'string', 'max:160'],
            'deskripsi_tugas' => ['nullable', 'string', 'max:2000'],
            'format_file' => ['nullable', 'string', 'max:50'],
            'tenggat_waktu' => ['required', 'date'],
            'urutan' => ['nullable', 'integer'],
        ]);

        $nextUrutan = $validated['urutan'] ?? ((TugasKelas::where('bagian_id', $section->bagian_id)->max('urutan') ?? 0) + 1);

        $task = TugasKelas::create([
            'bagian_id' => $section->bagian_id,
            'urutan' => $nextUrutan,
            'judul_tugas' => $validated['judul_tugas'],
            'deskripsi_tugas' => $validated['deskripsi_tugas'] ?? null,
            'format_file' => $validated['format_file'] ?? 'zip,pdf',
            'tenggat_waktu' => $validated['tenggat_waktu'],
        ]);

        return response()->json([
            'message' => 'Tugas berhasil dibuat.',
            'task' => $task,
        ], 201);
    }
}
