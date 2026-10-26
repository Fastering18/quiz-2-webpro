<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\DetailKrs;
use App\Models\Krs;
use App\Models\User;
use App\Services\SimplePdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class KrsController extends Controller
{
    /**
     * List KRS for the authenticated student or for a specific student (dosen/admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            $nrp = $user->mahasiswa?->nrp;

            $krsList = Krs::with(['detailKrs.classroom.mataKuliah', 'detailKrs.classroom.dosen.user'])
                ->where('nrp', $nrp)
                ->when($request->query('semester_tahun'), fn ($q, $v) => $q->where('semester_tahun', $v))
                ->orderByDesc('semester_tahun')
                ->get();

            return response()->json([
                'krs_list' => $krsList,
            ]);
        }

        // For Dosen / Admin: query by nrp parameter (dosen only see their perwalian students and their own classes)
        $targetNrp = $request->query('nrp');
        $query = Krs::with(['mahasiswa.user', 'detailKrs.classroom.mataKuliah']);

        if ($user->role === 'dosen') {
            $nip = $user->dosen?->nip;
            $query->where(function ($q) use ($nip) {
                $q->whereHas('mahasiswa', fn ($m) => $m->where('nip_wali', $nip))
                    ->orWhereHas('detailKrs.classroom', fn ($c) => $c->where('nip', $nip));
            });
        }

        if ($targetNrp) {
            $query->where('nrp', $targetNrp);
        }

        if ($status = $request->query('status_acc')) {
            $query->where('status_acc', $status);
        }

        if ($tahun = $request->query('semester_tahun')) {
            $query->where('semester_tahun', $tahun);
        }

        return response()->json([
            'krs_list' => $query->orderByDesc('semester_tahun')->get(),
        ]);
    }

    /**
     * Show a specific KRS with all enrolled course rows, SKS sum, and status.
     */
    public function show(Request $request, string $krsId): JsonResponse
    {
        $krs = $this->loadKrs($krsId);

        if (! $this->canView($request->user(), $krs)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $totalSks = $krs->detailKrs->sum(function ($detail) {
            return $detail->classroom?->mataKuliah?->sks ?? 0;
        });

        return response()->json([
            'krs' => $krs,
            'total_sks' => $totalSks,
        ]);
    }

    /**
     * Submit a new KRS with selected classroom IDs.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'mahasiswa') {
            return response()->json([
                'message' => 'Hanya mahasiswa yang dapat mengisi KRS.',
            ], 403);
        }

        $mahasiswa = $user->mahasiswa;

        $validated = $request->validate([
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'semester_tahun' => ['required', 'string', 'max:12'],
            'kelas_ids' => ['required', 'array', 'min:1'],
            'kelas_ids.*' => ['required', 'string', 'exists:classroom,kelas_id'],
        ]);

        $existing = Krs::where('nrp', $mahasiswa->nrp)->where('semester_tahun', $validated['semester_tahun'])->first();
        if ($existing && $existing->status_acc === 'Disetujui') {
            return response()->json([
                'message' => 'KRS semester ini sudah disetujui dan tidak dapat diubah. Hubungi dosen wali atau admin.',
            ], 409);
        }

        $krsId = 'KRS_'.str_replace('/', '', $validated['semester_tahun']).'_'.$mahasiswa->nrp;

        $krs = DB::transaction(function () use ($krsId, $mahasiswa, $validated) {
            $krs = Krs::updateOrCreate(
                [
                    'nrp' => $mahasiswa->nrp,
                    'semester_tahun' => $validated['semester_tahun'],
                ],
                [
                    'krs_id' => $krsId,
                    'semester' => $validated['semester'],
                    'status_acc' => 'Menunggu',
                    'tanggal_cetak' => null,
                ]
            );

            // Sync classroom details
            DetailKrs::where('krs_id', $krs->krs_id)->delete();

            foreach ($validated['kelas_ids'] as $kelasId) {
                DetailKrs::create([
                    'krs_id' => $krs->krs_id,
                    'kelas_id' => $kelasId,
                    'nilai_akhir' => null,
                ]);
            }

            return $krs;
        });

        return response()->json([
            'message' => 'KRS berhasil diajukan dan menunggu persetujuan Dosen Wali.',
            'krs' => $krs->load(['detailKrs.classroom.mataKuliah', 'detailKrs.classroom.dosen.user']),
        ], 201);
    }

    /**
     * Approve student KRS (Dosen Wali / Admin).
     */
    public function approve(Request $request, string $krsId): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'mahasiswa') {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $krs = Krs::with('mahasiswa')->findOrFail($krsId);

        // only the student's dosen wali (or an admin) may decide on a KRS
        if ($user->role === 'dosen' && $krs->mahasiswa?->nip_wali !== $user->dosen?->nip) {
            return response()->json(['message' => 'Hanya dosen wali mahasiswa ini atau admin yang dapat menyetujui KRS.'], 403);
        }

        $validated = $request->validate([
            'status_acc' => ['required', 'string', 'in:Disetujui,Ditolak,Menunggu'],
        ]);

        $krs->update([
            'status_acc' => $validated['status_acc'],
            'tanggal_cetak' => $validated['status_acc'] === 'Disetujui' ? now()->toDateString() : null,
        ]);

        return response()->json([
            'message' => 'Status persetujuan KRS berhasil diperbarui.',
            'krs' => $krs,
        ]);
    }

    /**
     * Download the KRS as a printable PDF (the "Unduh PDF" button).
     */
    public function pdf(Request $request, string $krsId): Response|JsonResponse
    {
        $krs = $this->loadKrs($krsId);

        if (! $this->canView($request->user(), $krs)) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $pdf = new SimplePdf;
        $user = $krs->mahasiswa?->user;
        $wali = $krs->mahasiswa?->dosenWali?->user?->nama_lengkap ?? '-';

        $pdf->text(50, 60, 'KARTU RENCANA STUDI (KRS)', 16, true);
        $pdf->text(50, 76, 'Portal Quiz 2 WebPro - myITS Classroom', 9);
        $pdf->line(50, 86, 545, 86, 1);

        $pdf->text(50, 108, 'Nama Mahasiswa', 9);
        $pdf->text(50, 122, (string) $user?->nama_lengkap, 11, true);
        $pdf->text(300, 108, 'NRP', 9);
        $pdf->text(300, 122, (string) $krs->nrp, 11, true);
        $pdf->text(50, 144, 'Jurusan', 9);
        $pdf->text(50, 158, (string) $krs->mahasiswa?->departemen, 11, true);
        $pdf->text(300, 144, 'Semester', 9);
        $pdf->text(300, 158, 'Semester '.$krs->semester.' ('.$krs->semester_tahun.')', 11, true);
        $pdf->text(50, 180, 'Status: '.$krs->status_acc, 9);

        $y = 210;
        $pdf->text(50, $y, 'Daftar Mata Kuliah', 12, true);
        $y += 12;
        $header = function (float $y) use ($pdf) {
            $pdf->rect(50, $y, 495, 20);
            $pdf->text(58, $y + 14, 'Kode MK', 9, true);
            $pdf->text(130, $y + 14, 'Nama Mata Kuliah', 9, true);
            $pdf->text(380, $y + 14, 'Kelas', 9, true);
            $pdf->text(510, $y + 14, 'SKS', 9, true);
        };
        $header($y);
        $y += 20;

        $total = 0;
        foreach ($krs->detailKrs as $d) {
            if ($y > 760) {
                $pdf->addPage();
                $y = 60;
                $header($y);
                $y += 20;
            }
            $mk = $d->classroom?->mataKuliah;
            $total += (int) ($mk?->sks ?? 0);
            $pdf->rect(50, $y, 495, 22);
            $pdf->text(58, $y + 15, (string) $mk?->kode_mk, 9);
            $pdf->text(130, $y + 15, mb_strimwidth((string) $mk?->nama_mk, 0, 48, '...'), 9);
            $pdf->text(380, $y + 15, mb_strimwidth((string) $d->classroom?->nama_kelas, 0, 24, '...'), 9);
            $pdf->text(515, $y + 15, (string) ($mk?->sks ?? 0), 9);
            $y += 22;
        }

        if ($y > 700) {
            $pdf->addPage();
            $y = 60;
        }
        $y += 22;
        $pdf->text(50, $y, 'Total SKS terdaftar', 10);
        $pdf->text(480, $y, $total.' SKS', 10, true);

        $y += 50;
        $pdf->text(50, $y, 'Mahasiswa', 9);
        $pdf->text(320, $y, 'Wali / Pembimbing Akademik', 9);
        $pdf->text(50, $y + 70, (string) $user?->nama_lengkap, 10, true);
        $pdf->text(320, $y + 70, $wali, 10, true);
        $pdf->line(50, $y + 76, 240, $y + 76);
        $pdf->line(320, $y + 76, 520, $y + 76);

        $pdf->text(50, 810, 'Dicetak: '.now()->format('d-m-Y H:i'), 8);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="KRS_'.$krs->nrp.'_'.str_replace('/', '-', $krs->semester_tahun).'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function loadKrs(string $krsId): Krs
    {
        return Krs::with([
            'mahasiswa.user',
            'mahasiswa.dosenWali.user',
            'detailKrs.classroom.mataKuliah',
            'detailKrs.classroom.dosen.user',
        ])->findOrFail($krsId);
    }

    /** Owner, the student's dosen wali, a dosen teaching one of its classes, or an admin. */
    private function canView(User $user, Krs $krs): bool
    {
        return match ($user->role) {
            'admin' => true,
            'mahasiswa' => $user->mahasiswa?->nrp === $krs->nrp,
            'dosen' => $user->dosen && (
                $krs->mahasiswa?->nip_wali === $user->dosen->nip
                || $krs->detailKrs->contains(fn ($d) => $d->classroom?->nip === $user->dosen->nip)
            ),
            default => false,
        };
    }
}
