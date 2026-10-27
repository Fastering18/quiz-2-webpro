<?php

namespace App\Models;

use App\Services\FileStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perpustakaan extends Model
{
    use HasFactory;

    protected $table = 'perpustakaan';

    protected $primaryKey = 'buku_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $appends = ['file_url'];

    protected $fillable = [
        'buku_id',
        'judul_buku',
        'penulis',
        'publisher_id',
        'tahun',
        'kategori',
        'nama_file',
        'file_dokumen_key',
        'ukuran_file',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'ukuran_file' => 'integer',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id', 'user_id');
    }

    /**
     * Case-insensitive title or author match. PostgreSQL LIKE is case-sensitive.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $needle = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $inner) use ($needle) {
            $inner->whereRaw('LOWER(judul_buku) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(penulis) LIKE ?', [$needle]);
        });
    }

    public function getFileUrlAttribute(): ?string
    {
        return app(FileStorage::class)->temporaryUrl($this->file_dokumen_key, $this->nama_file);
    }
}
