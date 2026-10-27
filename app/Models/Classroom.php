<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $table = 'classroom';

    protected $primaryKey = 'kelas_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'kelas_id',
        'kode_mk',
        'nip',
        'nama_kelas',
        'semester_tahun',
        'kuota_kelas',
    ];

    protected $appends = ['display_name'];

    /**
     * "Struktur Data dan Algoritma" + "Struktur Data dan Algoritma (A)"
     * becomes "Struktur Data dan Algoritma (A)", not the name twice.
     */
    public static function formatName(string $courseName, string $className): string
    {
        $course = trim((string) preg_replace('/\s+/u', ' ', $courseName));
        $class = self::peelRepeatedTitle(trim((string) preg_replace('/\s+/u', ' ', $className)));

        if ($course === '' && $class === '') {
            return '-';
        }
        if ($class === '') {
            return $course;
        }
        if ($course === '') {
            return $class;
        }
        if (mb_strtolower($class) === mb_strtolower($course)) {
            return $course;
        }
        if (str_starts_with(mb_strtolower($class), mb_strtolower($course))) {
            return $class;
        }
        if (preg_match('/^\(?([A-Za-z0-9]{1,3})\)?$/u', $class, $section) === 1) {
            return $course.' ('.mb_strtoupper($section[1]).')';
        }

        return $course.' ('.$class.')';
    }

    public function getDisplayNameAttribute(): string
    {
        $course = $this->relationLoaded('mataKuliah')
            ? (string) ($this->mataKuliah?->nama_mk ?? '')
            : '';

        return self::formatName($course, (string) $this->nama_kelas);
    }

    /**
     * The parallel-class marker, such as "(A)", after the course title is removed.
     */
    public static function sectionLabel(string $courseName, string $className): string
    {
        $full = self::formatName($courseName, $className);
        $course = trim($courseName);
        if ($course !== '' && str_starts_with(mb_strtolower($full), mb_strtolower($course))) {
            $rest = trim(mb_substr($full, mb_strlen($course)));

            return $rest !== '' ? $rest : '-';
        }

        return $full;
    }

    private static function peelRepeatedTitle(string $value): string
    {
        $current = $value;
        for ($i = 0; $i < 4; $i++) {
            if (preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $current, $match) !== 1) {
                return trim($current);
            }
            $left = trim($match[1]);
            $inner = trim($match[2]);
            if ($left !== '' && str_starts_with(mb_strtolower($inner), mb_strtolower($left))) {
                $current = $inner;

                continue;
            }

            return trim($current);
        }

        return trim($current);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'kode_mk', 'kode_mk');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'nip', 'nip');
    }

    public function detailKrs(): HasMany
    {
        return $this->hasMany(DetailKrs::class, 'kelas_id', 'kelas_id');
    }

    public function bagianKelas(): HasMany
    {
        return $this->hasMany(BagianKelas::class, 'kelas_id', 'kelas_id')->orderBy('urutan');
    }

    public function pengumuman(): HasMany
    {
        return $this->hasMany(PengumumanClassroom::class, 'kelas_id', 'kelas_id')->orderByDesc('tanggal_unggah');
    }
}
