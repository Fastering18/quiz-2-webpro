<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanAdmin;
use App\Rules\LegacyFileKey;
use App\Services\FileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaporanAdminController extends Controller
{
    /**
     * Display a listing of tickets for current user or all for admin.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = LaporanAdmin::with(['pelapor', 'admin']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->user_id);
        }

        if ($status) {
            $query->where('status_tiket', $status);
        }

        if ($user->role === 'admin') {
            $query->orderByRaw('CASE WHEN tanggapan_admin IS NULL THEN 0 ELSE 1 END ASC')
                ->orderByDesc('tanggal_lapor');
        } else {
            $query->orderByDesc('tanggal_lapor');
        }

        $reports = $query->paginate(15);

        return response()->json($reports);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, string $tiketId): JsonResponse
    {
        $user = $request->user();
        $report = LaporanAdmin::with(['pelapor', 'admin'])->findOrFail($tiketId);

        // mahasiwa / dosen can only view their own ticket
        if ($user->role !== 'admin' && $report->user_id !== $user->user_id) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        return response()->json([
            'report' => $report,
        ]);
    }

    /**
     * Submit a new support ticket / complaint.
     */
    public function store(Request $request, FileStorage $files): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'kategori_modul' => ['required', 'string', 'max:40'],
            'judul_laporan' => ['required', 'string', 'max:150'],
            'deskripsi_kendala' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', ...$files->rules('reports')],
            // legacy fields (prefer sending a real "lampiran" file)
            'lampiran_nama_file' => ['nullable', 'string', 'max:255'],
            'lampiran_bukti_key' => ['nullable', 'string', new LegacyFileKey],
        ]);

        $tiketId = 'TKT_'.now()->format('Ymd').'_'.strtoupper(Str::random(4));

        $namaFile = $validated['lampiran_nama_file'] ?? null;
        $fileKey = $validated['lampiran_bukti_key'] ?? null;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $namaFile = $file->getClientOriginalName();
            $fileKey = $files->store($file, 'reports/'.$tiketId);
        }

        $report = LaporanAdmin::create([
            'tiket_id' => $tiketId,
            'user_id' => $user->user_id,
            'kategori_modul' => $validated['kategori_modul'],
            'judul_laporan' => $validated['judul_laporan'],
            'deskripsi_kendala' => $validated['deskripsi_kendala'],
            'lampiran_nama_file' => $namaFile,
            'lampiran_bukti_key' => $fileKey,
            'status_tiket' => 'Terkirim',
            'tanggal_lapor' => now(),
            'admin_id' => null,
            'tanggapan_admin' => null,
            'tanggal_tanggapan' => null,
        ]);

        return response()->json([
            'message' => 'Laporan berhasil dikirim ke Administrator.',
            'report' => $report,
        ], 201);
    }

    /**
     * Admin responds to a ticket and updates its status.
     */
    public function reply(Request $request, string $tiketId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Hanya administrator yang dapat merespons tiket laporan.',
            ], 403);
        }

        $report = LaporanAdmin::findOrFail($tiketId);

        $validated = $request->validate([
            'tanggapan_admin' => ['required', 'string', 'max:2000'],
            'status_tiket' => ['required', 'string', 'in:Diproses,Selesai,Ditolak'],
        ]);

        $report->update([
            'admin_id' => $user->user_id,
            'tanggapan_admin' => $validated['tanggapan_admin'],
            'status_tiket' => $validated['status_tiket'],
            'tanggal_tanggapan' => now(),
        ]);

        return response()->json([
            'message' => 'Tanggapan berhasil disimpan.',
            'report' => $report->load(['pelapor', 'admin']),
        ]);
    }
}
