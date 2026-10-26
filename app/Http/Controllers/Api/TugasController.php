<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\TugasKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\LegacyFileKey;
use App\Services\FileStorage;

class TugasController extends Controller
{
    /**
     * Display the task detail and student's submission status.
     */
    public function show(Request $request, string|int $tugasId): JsonResponse
    {
        $user = $request->user();
        $task = TugasKelas::with(['bagianKelas.classroom.mataKuliah', 'bagianKelas.classroom.dosen.user'])
            ->findOrFail($tugasId);

        if (! FileController::canAccessClass($user, (string) $task->bagianKelas?->kelas_id)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $mySubmission = null;
        $allSubmissions = null;

        if ($user->role === 'mahasiswa') {
            $nrp = $user->mahasiswa?->nrp;
            $mySubmission = PengumpulanTugas::where('tugas_id', $task->tugas_id)
                ->where('nrp', $nrp)
                ->first();
        } elseif ($user->role === 'dosen' || $user->role === 'admin') {
            $allSubmissions = PengumpulanTugas::with('mahasiswa.user')
                ->where('tugas_id', $task->tugas_id)
                ->get();
        }

        return response()->json([
            'task' => $task,
            'my_submission' => $mySubmission,
            'all_submissions' => $allSubmissions,
        ]);
    }

    /**
     * Submit or re-submit an assignment for the task.
     */
    public function submit(Request $request, string|int $tugasId, FileStorage $files): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'message' => 'Hanya mahasiswa yang dapat mengumpulkan tugas.',
            ], 403);
        }

        $mahasiswa = $user->mahasiswa;
        if (! $mahasiswa) {
            return response()->json([
                'message' => 'Profil mahasiswa tidak ditemukan.',
            ], 404);
        }

        $task = TugasKelas::with('bagianKelas')->findOrFail($tugasId);

        if (! FileController::canAccessClass($user, (string) $task->bagianKelas?->kelas_id)) {
            return response()->json([
                'message' => 'Anda tidak terdaftar di kelas untuk tugas ini.',
            ], 403);
        }

        $formats = collect(explode(',', (string) $task->format_file))
            ->map(fn ($f) => strtolower(trim($f)))->filter()->values()->all();

        $validated = $request->validate([
            'file' => ['required_without:file_jawaban_key', ...$files->rules('submissions', $formats)],
            // legacy fields (prefer sending a real "file")
            'nama_file' => ['nullable', 'string', 'max:255'],
            'file_jawaban_key' => ['nullable', 'string', new LegacyFileKey],
            'ukuran_file' => ['nullable', 'integer'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = PengumpulanTugas::where('tugas_id', $task->tugas_id)->where('nrp', $mahasiswa->nrp)->first();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $namaFile = $file->getClientOriginalName();
            $ukuranFile = $file->getSize();
            $fileKey = $files->store($file, 'submissions/'.$task->tugas_id.'/'.$mahasiswa->nrp);
        } else {
            $namaFile = $validated['nama_file'] ?? 'tugas_'.$mahasiswa->nrp.'.zip';
            $fileKey = $validated['file_jawaban_key'];
            $ukuranFile = (int) ($validated['ukuran_file'] ?? 0);
        }

        $now = now();
        // a date-only deadline (00:00:00) means "until the end of that day"
        $deadline = $task->tenggat_waktu;
        if ($deadline && $deadline->format('H:i:s') === '00:00:00') {
            $deadline = $deadline->copy()->endOfDay();
        }

        $submission = PengumpulanTugas::updateOrCreate(
            [
                'tugas_id' => $task->tugas_id,
                'nrp' => $mahasiswa->nrp,
            ],
            [
                'nama_file' => $namaFile,
                'file_jawaban_key' => $fileKey,
                'ukuran_file' => $ukuranFile,
                'komentar' => $validated['komentar'] ?? null,
                'tanggal_kirim' => $now,
                'terlambat' => $deadline !== null && $now->gt($deadline),
            ]
        );

        // replacing a submission removes the previous object
        if ($existing && $existing->file_jawaban_key !== $fileKey) {
            $files->delete($existing->file_jawaban_key);
        }

        return response()->json([
            'message' => 'Tugas berhasil dikumpulkan.',
            'submission' => $submission,
        ], 200);
    }

    /**
     * Grade student submission (Dosen / Admin).
     */
    public function grade(Request $request, string|int $submitId): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $submission = PengumpulanTugas::findOrFail($submitId);

        $validated = $request->validate([
            'nilai_tugas' => ['required', 'integer', 'min:0', 'max:100'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->update([
            'nilai_tugas' => $validated['nilai_tugas'],
            'komentar' => $validated['komentar'] ?? $submission->komentar,
        ]);

        return response()->json([
            'message' => 'Nilai tugas berhasil disimpan.',
            'submission' => $submission,
        ]);
    }
}
