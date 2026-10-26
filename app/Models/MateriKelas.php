<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MateriKelas extends Model
{
    use HasFactory;

    protected $table = 'materi_kelas';

    protected $primaryKey = 'materi_id';

    public $timestamps = false;

    protected $appends = ['file_url'];

    protected $fillable = [
        'bagian_id',
        'judul_materi',
        'nama_file',
        'file_materi_key',
        'ukuran_file',
        'tanggal_unggah',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_unggah' => 'date',
            'ukuran_file' => 'integer',
        ];
    }

    public function bagianKelas(): BelongsTo
    {
        return $this->belongsTo(BagianKelas::class, 'bagian_id', 'bagian_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return app(\App\Services\FileStorage::class)->temporaryUrl($this->file_materi_key, $this->nama_file);
    }
}
