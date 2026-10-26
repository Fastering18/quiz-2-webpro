<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\TugasKelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TugasController extends Controller
{
    /**
     * Display the task detail and student's submission status.
     */
    public function show(Request $request, int $tugasId): JsonResponse
    {
        $user = $request->user();
        $task = TugasKelas::with(['bagianKelas.classroom.mataKuliah', 'bagianKelas.classroom.dosen.user'])
            ->findOrFail($tugasId);

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
    public function submit(Request $request, int $tugasId): JsonResponse
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

        $task = TugasKelas::findOrFail($tugasId);

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'max:51200'],
            'nama_file' => ['nullable', 'string'],
            'file_jawaban_key' => ['nullable', 'string'],
            'ukuran_file' => ['nullable', 'integer'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ]);

        $namaFile = $validated['nama_file'] ?? 'tugas_'.$mahasiswa->nrp.'.zip';
        $fileKey = $validated['file_jawaban_key'] ?? 'submissions/'.Str::random(20).'.zip';
        $ukuranFile = (int) ($validated['ukuran_file'] ?? 1024000);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $namaFile = $file->getClientOriginalName();
            $fileKey = $file->store('submissions', 'public');
            $ukuranFile = $file->getSize();
        }

        // Upsert submission
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
                'tanggal_kirim' => now(),
            ]
        );

        return response()->json([
            'message' => 'Tugas berhasil dikumpulkan.',
            'submission' => $submission,
        ], 200);
    }

    /**
     * Grade student submission (Dosen / Admin).
     */
    public function grade(Request $request, int $submitId): JsonResponse
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
