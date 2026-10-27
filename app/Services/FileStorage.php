<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores user uploads on the S3-compatible disk (Neon object storage) with structured keys.
 * Falls back to the private local disk automatically when S3 is not configured.
 */
class FileStorage
{
    public const URL_TTL_MINUTES = 10;

    /** kind => [max size in KB, allowed extensions] */
    public const KINDS = [
        'materials' => [20480, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'md', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg', 'mp4', 'c', 'cpp', 'java', 'py']],
        'submissions' => [20480, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'md', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg', 'c', 'cpp', 'java', 'py', 'sql']],
        'library' => [51200, ['pdf', 'epub', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip']],
        'reports' => [5120, ['pdf', 'png', 'jpg', 'jpeg', 'txt', 'zip', 'doc', 'docx']],
        'avatars' => [2048, ['png', 'jpg', 'jpeg', 'webp']],
    ];

    /** Extensions whose real content is detected as a container format. */
    private const CONTAINER_EXT = ['docx' => 'zip', 'pptx' => 'zip', 'xlsx' => 'zip', 'epub' => 'zip'];

    /** Detected extensions that are acceptable for plain-text-like uploads. */
    private const TEXTLIKE = ['txt', 'md', 'c', 'cpp', 'java', 'py', 'sql', 'csv'];

    public function disk(): string
    {
        $cfg = config('filesystems.disks.s3', []);

        if (! empty($cfg['bucket']) && ! empty($cfg['key']) && ! empty($cfg['secret'])) {
            return 's3';
        }

        return 'local'; // private disk (storage/app/private), served only via signed routes
    }

    public function usesS3(): bool
    {
        return $this->disk() === 's3';
    }

    /**
     * Validation rules for an upload field of the given kind.
     *
     * @param  list<string>|null  $onlyExtensions  further restrict allowed extensions (e.g. a task's format_file)
     * @return array<int, mixed>
     */
    public function rules(string $kind, ?array $onlyExtensions = null): array
    {
        [$maxKb, $allowed] = self::KINDS[$kind];

        if ($onlyExtensions) {
            $allowed = array_values(array_intersect($allowed, array_map('strtolower', $onlyExtensions))) ?: $allowed;
        }

        return [
            'file',
            'max:'.$maxKb,
            function (string $attribute, mixed $value, \Closure $fail) use ($allowed) {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());
                if (! in_array($ext, $allowed, true)) {
                    $fail('Tipe file tidak diizinkan. Diizinkan: '.implode(', ', $allowed).'.');

                    return;
                }

                $detected = $value->guessExtension();
                if ($detected === null || $detected === 'bin') {
                    return;
                }

                $ok = $detected === $ext
                    || $detected === (self::CONTAINER_EXT[$ext] ?? null)
                    || (in_array($ext, self::TEXTLIKE, true) && in_array($detected, self::TEXTLIKE, true))
                    || ($ext === 'jpg' && $detected === 'jpeg') || ($ext === 'jpeg' && $detected === 'jpg')
                    // legacy Office binaries are sniffed as generic OLE containers
                    || (in_array($ext, ['doc', 'ppt', 'xls'], true) && in_array($detected, ['doc', 'ppt', 'xls', 'msi'], true));

                if (! $ok) {
                    $fail('Isi file tidak sesuai dengan ekstensinya ('.$ext.').');
                }
            },
        ];
    }

    /** Max upload size (KB) for a kind. */
    public function maxKb(string $kind): int
    {
        return self::KINDS[$kind][0];
    }

    /**
     * Store an uploaded file under "{prefix}/{random}-{safe-name}.{ext}" and return the object key.
     * Prefix example: "materials/CLS_SO_B", "submissions/12/5025251131".
     */
    public function store(UploadedFile $file, string $prefix): string
    {
        $key = trim($prefix, '/').'/'.Str::lower(Str::random(10)).'-'.$this->safeName($file->getClientOriginalName());
        $stored = Storage::disk($this->disk())->putFileAs(
            trim($prefix, '/'),
            $file,
            basename($key),
        );

        if ($stored === false) {
            throw ValidationException::withMessages([
                'file' => ['Gagal menyimpan berkas. Coba lagi.'],
            ]);
        }

        return $key;
    }

    public function delete(?string $key): void
    {
        if ($key === null || $key === '') {
            return;
        }

        Storage::disk($this->disk())->delete($key);
    }

    public function exists(?string $key): bool
    {
        return $key !== null && $key !== '' && Storage::disk($this->disk())->exists($key);
    }

    /**
     * Time-limited URL: presigned URL on S3, signed route to a streaming endpoint on the local fallback.
     */
    public function temporaryUrl(?string $key, ?string $downloadName = null, ?CarbonInterface $expires = null): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $expires ??= now()->addMinutes(self::URL_TTL_MINUTES);

        if ($this->usesS3()) {
            $options = [];
            if ($downloadName) {
                $options['ResponseContentDisposition'] = 'attachment; filename="'.$this->headerName($downloadName).'"';
            }

            try {
                return Storage::disk('s3')->temporaryUrl($key, $expires, $options);
            } catch (\Throwable) {
                return null;
            }
        }

        return URL::temporarySignedRoute('files.local', $expires, [
            'key' => rtrim(strtr(base64_encode($key), '+/', '-_'), '='),
            'name' => $downloadName,
        ]);
    }

    /** Resolve a key from the signed local route token. */
    public function decodeKey(string $token): ?string
    {
        $key = base64_decode(strtr($token, '-_', '+/'), true);

        return $key === false || str_contains($key, '..') ? null : $key;
    }

    public function safeName(string $original): string
    {
        $ext = Str::lower(pathinfo($original, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
        $base = Str::limit($base, 80, '');

        return $ext !== '' ? $base.'.'.preg_replace('/[^a-z0-9]/', '', $ext) : $base;
    }

    private function headerName(string $name): string
    {
        return str_replace(['"', "\r", "\n", '\\'], '', $name);
    }
}
