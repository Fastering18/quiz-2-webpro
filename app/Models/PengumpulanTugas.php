<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumpulanTugas extends Model
{
    use HasFactory;

    protected $table = 'pengumpulan_tugas';

    protected $primaryKey = 'submit_id';

    public $timestamps = false;

    protected $appends = ['file_url'];

    protected $fillable = [
        'tugas_id',
        'nrp',
        'nama_file',
        'file_jawaban_key',
        'ukuran_file',
        'komentar',
        'tanggal_kirim',
        'nilai_tugas',
        'terlambat',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kirim' => 'datetime',
            'ukuran_file' => 'integer',
            'nilai_tugas' => 'integer',
            'terlambat' => 'boolean',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(TugasKelas::class, 'tugas_id', 'tugas_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'nrp', 'nrp');
    }

    public function getFileUrlAttribute(): ?string
    {
        return app(\App\Services\FileStorage::class)->temporaryUrl($this->file_jawaban_key, $this->nama_file);
    }
}
