<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BagianKelas;
use App\Models\Classroom;
use App\Models\MateriKelas;
use App\Models\PengumpulanTugas;
use App\Models\PengumumanClassroom;
use App\Models\TugasKelas;
use App\Rules\LegacyFileKey;
use App\Services\FileStorage;
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

        if (! FileController::canAccessClass($user, $classroom->kelas_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

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
        $this->authorizeTeaching($request, $classroom->kelas_id);

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
        $this->authorizeTeaching($request, $classroom->kelas_id);

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
    public function storeMaterial(Request $request, string|int $bagianId, FileStorage $files): JsonResponse
    {
        $section = BagianKelas::findOrFail($bagianId);
        $this->authorizeTeaching($request, $section->kelas_id);

        $validated = $request->validate([
            'judul_materi' => ['required', 'string', 'max:160'],
            'file' => ['required_without:file_materi_key', ...$files->rules('materials')],
            // legacy fields (prefer sending a real "file")
            'nama_file' => ['nullable', 'string', 'max:255'],
            'file_materi_key' => ['nullable', 'string', new LegacyFileKey],
            'ukuran_file' => ['nullable', 'integer'],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $namaFile = $file->getClientOriginalName();
            $ukuranFile = $file->getSize();
            $fileKey = $files->store($file, 'materials/'.$section->kelas_id);
        } else {
            $namaFile = $validated['nama_file'] ?? 'document.pdf';
            $fileKey = $validated['file_materi_key'] ?? 'materials/'.Str::random(20).'.pdf';
            $ukuranFile = (int) ($validated['ukuran_file'] ?? 1024000);
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

    /** Delete a material (and its stored file). */
    public function destroyMaterial(Request $request, string|int $materiId, FileStorage $files): JsonResponse
    {
        $material = MateriKelas::with('bagianKelas')->findOrFail($materiId);
        $this->authorizeTeaching($request, (string) $material->bagianKelas?->kelas_id);

        $key = $material->file_materi_key;
        $material->delete();
        $files->delete($key);

        return response()->json(['message' => 'Materi berhasil dihapus.']);
    }

    /** Delete a task together with all submissions and their stored files. */
    public function destroyTask(Request $request, string|int $tugasId, FileStorage $files): JsonResponse
    {
        $task = TugasKelas::with('bagianKelas')->findOrFail($tugasId);
        $this->authorizeTeaching($request, (string) $task->bagianKelas?->kelas_id);

        $this->purgeTask($task, $files);

        return response()->json(['message' => 'Tugas berhasil dihapus.']);
    }

    /** Delete a section with its materials, tasks, submissions and stored files. */
    public function destroySection(Request $request, string|int $bagianId, FileStorage $files): JsonResponse
    {
        $section = BagianKelas::with(['materi', 'tugas'])->findOrFail($bagianId);
        $this->authorizeTeaching($request, $section->kelas_id);

        foreach ($section->materi as $m) {
            $files->delete($m->file_materi_key);
        }
        foreach ($section->tugas as $t) {
            PengumpulanTugas::where('tugas_id', $t->tugas_id)->get()->each(fn ($s) => $files->delete($s->file_jawaban_key));
        }
        $section->delete(); // DB rows cascade

        return response()->json(['message' => 'Bagian kelas berhasil dihapus.']);
    }

    private function purgeTask(TugasKelas $task, FileStorage $files): void
    {
        PengumpulanTugas::where('tugas_id', $task->tugas_id)->get()->each(fn ($s) => $files->delete($s->file_jawaban_key));
        $task->delete();
    }

    /** Only the class's lecturer or an admin may modify class content. */
    private function authorizeTeaching(Request $request, string $kelasId): void
    {
        $user = $request->user();
        $classroom = Classroom::find($kelasId);

        $ok = $user->role === 'admin'
            || ($user->role === 'dosen' && $user->dosen && $classroom?->nip === $user->dosen->nip);

        abort_unless($ok, 403, 'Hanya dosen pengampu atau admin yang dapat mengubah kelas ini.');
    }

    /**
     * Create a task/assignment inside a section.
     */
    public function storeTask(Request $request, string|int $bagianId): JsonResponse
    {
        $section = BagianKelas::findOrFail($bagianId);
        $this->authorizeTeaching($request, $section->kelas_id);

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

    /** Edit an announcement. */
    public function updateAnnouncement(Request $request, string|int $id): JsonResponse
    {
        $announcement = PengumumanClassroom::findOrFail($id);
        $this->authorizeTeaching($request, (string) $announcement->kelas_id);

        $validated = $request->validate(['konten' => ['required', 'string', 'max:2000']]);
        $announcement->update(['konten' => $validated['konten']]);

        return response()->json(['message' => 'Pengumuman berhasil diperbarui.', 'announcement' => $announcement->fresh()]);
    }

    /** Delete an announcement. */
    public function destroyAnnouncement(Request $request, string|int $id): JsonResponse
    {
        $announcement = PengumumanClassroom::findOrFail($id);
        $this->authorizeTeaching($request, (string) $announcement->kelas_id);
        $announcement->delete();

        return response()->json(['message' => 'Pengumuman berhasil dihapus.']);
    }

    /** Edit a section (title, summary, order). */
    public function updateSection(Request $request, string|int $bagianId): JsonResponse
    {
        $section = BagianKelas::findOrFail($bagianId);
        $this->authorizeTeaching($request, $section->kelas_id);

        $validated = $request->validate([
            'judul_bagian' => ['sometimes', 'required', 'string', 'max:160'],
            'judul_ringkasan' => ['nullable', 'string', 'max:160'],
            'konten_ringkasan' => ['nullable', 'string', 'max:5000'],
            'urutan' => ['nullable', 'integer', 'min:1'],
        ]);
        $section->update(array_filter($validated, fn ($v, $k) => $v !== null || in_array($k, ['judul_ringkasan', 'konten_ringkasan'], true), ARRAY_FILTER_USE_BOTH));

        return response()->json(['message' => 'Bagian kelas berhasil diperbarui.', 'section' => $section->fresh()]);
    }

    /** Edit a material; send `file` (multipart POST) to replace the stored document. */
    public function updateMaterial(Request $request, string|int $materiId, FileStorage $files): JsonResponse
    {
        $material = MateriKelas::with('bagianKelas')->findOrFail($materiId);
        $this->authorizeTeaching($request, (string) $material->bagianKelas?->kelas_id);

        $validated = $request->validate([
            'judul_materi' => ['sometimes', 'required', 'string', 'max:160'],
            'file' => ['nullable', ...$files->rules('materials')],
        ]);

        $data = [];
        if (isset($validated['judul_materi'])) {
            $data['judul_materi'] = $validated['judul_materi'];
        }
        $oldKey = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $oldKey = $material->file_materi_key;
            $data['nama_file'] = $file->getClientOriginalName();
            $data['ukuran_file'] = $file->getSize();
            $data['file_materi_key'] = $files->store($file, 'materials/'.$material->bagianKelas->kelas_id);
        }
        $material->update($data);
        if ($oldKey) {
            $files->delete($oldKey);
        }

        return response()->json(['message' => 'Materi berhasil diperbarui.', 'material' => $material->fresh()]);
    }

    /** Edit a task (title, description, formats, deadline, order). */
    public function updateTask(Request $request, string|int $tugasId): JsonResponse
    {
        $task = TugasKelas::with('bagianKelas')->findOrFail($tugasId);
        $this->authorizeTeaching($request, (string) $task->bagianKelas?->kelas_id);

        $validated = $request->validate([
            'judul_tugas' => ['sometimes', 'required', 'string', 'max:160'],
            'deskripsi_tugas' => ['nullable', 'string', 'max:2000'],
            'format_file' => ['sometimes', 'required', 'string', 'max:50'],
            'tenggat_waktu' => ['sometimes', 'required', 'date'],
            'urutan' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);
        $task->update($validated);

        return response()->json(['message' => 'Tugas berhasil diperbarui.', 'task' => $task->fresh()]);
    }
}

