<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TugasKelas extends Model
{
    use HasFactory;

    protected $table = 'tugas_kelas';

    protected $primaryKey = 'tugas_id';

    public $timestamps = false;

    protected $fillable = [
        'bagian_id',
        'urutan',
        'judul_tugas',
        'deskripsi_tugas',
        'format_file',
        'tenggat_waktu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenggat_waktu' => 'datetime',
            'urutan' => 'integer',
        ];
    }

    public function bagianKelas(): BelongsTo
    {
        return $this->belongsTo(BagianKelas::class, 'bagian_id', 'bagian_id');
    }

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'tugas_id', 'tugas_id');
    }
}
