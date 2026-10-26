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
