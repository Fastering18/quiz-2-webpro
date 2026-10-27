<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\LaporanAdmin;
use App\Models\MateriKelas;
use App\Models\PengumpulanTugas;
use App\Models\Perpustakaan;
use App\Models\User;
use App\Services\FileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authorized file downloads.
 *   GET /api/files/{kind}/{id}   kind: materials | submissions | library | reports | avatars
 * Returns a 302 redirect to a short-lived presigned URL (or JSON {url,...} when the client asks for JSON).
 * GET /files/local (signed)      streams a file on the local fallback disk.
 */
class FileController extends Controller
{
    public function __construct(private FileStorage $files) {}

    public function show(Request $request, string $kind, string $id): JsonResponse|Response
    {
        $user = $request->user();

        [$key, $name, $allowed] = match ($kind) {
            'materials' => $this->material($user, $id),
            'submissions' => $this->submission($user, $id),
            'library' => $this->library($id),
            'reports' => $this->report($user, $id),
            'avatars' => $this->avatar($id),
            default => abort(404, 'Jenis file tidak dikenal.'),
        };

        if (! $allowed || $key === null || $key === '') {
            return response()->json(['message' => $allowed ? 'File belum tersedia.' : 'Akses ditolak.'], $allowed ? 404 : 403);
        }

        $expires = now()->addMinutes(FileStorage::URL_TTL_MINUTES);
        $url = $this->files->temporaryUrl($key, $kind === 'avatars' ? null : $name, $expires);

        if ($request->wantsJson()) {
            if (! $url) {
                return response()->json(['message' => 'Gagal membuat tautan unduhan.'], 500);
            }

            return response()->json([
                'url' => $url,
                'nama_file' => $name,
                'expires_at' => $expires->toIso8601String(),
            ]);
        }

        return $this->streamStoredFile($key, $name ?: basename($key), $kind === 'avatars');
    }

    /**
     * Stream the object with GET. A HEAD probe against Neon can fail even when the object exists,
     * and that probe was reported to the browser as "file belum tersedia".
     */
    private function streamStoredFile(string $key, string $downloadName, bool $inline): StreamedResponse|JsonResponse
    {
        $disk = Storage::disk($this->files->disk());

        try {
            $stream = $disk->readStream($key);
        } catch (\Throwable $exception) {
            error_log('File read failed for '.$key.': '.$exception->getMessage());

            return response()->json(['message' => 'File belum tersedia.'], 404);
        }

        if (! is_resource($stream)) {
            return response()->json(['message' => 'File belum tersedia.'], 404);
        }

        $safeName = str_replace(['"', '\\', "\r", "\n"], '', $downloadName);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->mimeFromName($safeName),
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$safeName.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mimeFromName(string $name): string
    {
        return match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'txt', 'md' => 'text/plain',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    /** Signed streaming endpoint used only when S3 is not configured. */
    public function local(Request $request): StreamedResponse|JsonResponse
    {
        $key = $this->files->decodeKey((string) $request->query('key'));

        if (! $key || ! $this->files->exists($key)) {
            return response()->json(['message' => 'File belum tersedia.'], 404);
        }

        return Storage::disk($this->files->disk())->download($key, $request->query('name') ?: basename($key));
    }

    /** @return array{0:?string,1:?string,2:bool} */
    private function material(User $user, string $id): array
    {
        $m = MateriKelas::with('bagianKelas')->findOrFail($id);

        return [$m->file_materi_key, $m->nama_file, $this->canAccessClass($user, (string) $m->bagianKelas?->kelas_id)];
    }

    /** @return array{0:?string,1:?string,2:bool} */
    private function submission(User $user, string $id): array
    {
        $s = PengumpulanTugas::with('tugas.bagianKelas')->findOrFail($id);

        $allowed = match ($user->role) {
            'admin' => true,
            'dosen' => $this->canAccessClass($user, (string) $s->tugas?->bagianKelas?->kelas_id),
            'mahasiswa' => $user->mahasiswa?->nrp === $s->nrp,
            default => false,
        };

        return [$s->file_jawaban_key, $s->nama_file, $allowed];
    }

    /** @return array{0:?string,1:?string,2:bool} */
    private function library(string $id): array
    {
        $b = Perpustakaan::findOrFail($id);

        return [$b->file_dokumen_key, $b->nama_file, true];
    }

    /** @return array{0:?string,1:?string,2:bool} */
    private function report(User $user, string $id): array
    {
        $r = LaporanAdmin::findOrFail($id);

        return [$r->lampiran_bukti_key, $r->lampiran_nama_file, $user->role === 'admin' || $r->user_id === $user->user_id];
    }

    /** @return array{0:?string,1:?string,2:bool} */
    private function avatar(string $userId): array
    {
        $u = User::findOrFail($userId);

        return [$u->foto_profil_key, basename((string) $u->foto_profil_key), true];
    }

    /**
     * Admin: any class. Dosen: classes they teach. Mahasiswa: classes in their KRS.
     */
    public static function canAccessClass(User $user, string $kelasId): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($kelasId === '' || ! ($class = Classroom::find($kelasId))) {
            return false;
        }

        if ($user->role === 'dosen') {
            return $user->dosen && $class->nip === $user->dosen->nip;
        }

        if ($user->role === 'mahasiswa' && $user->mahasiswa) {
            return $class->detailKrs()->whereHas('krs', fn ($q) => $q->where('nrp', $user->mahasiswa->nrp))->exists();
        }

        return false;
    }
}
