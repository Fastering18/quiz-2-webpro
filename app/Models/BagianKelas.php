<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagianKelas extends Model
{
    use HasFactory;

    protected $table = 'bagian_kelas';

    protected $primaryKey = 'bagian_id';

    public $timestamps = false;

    protected $fillable = [
        'kelas_id',
        'urutan',
        'judul_bagian',
        'judul_ringkasan',
        'konten_ringkasan',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'kelas_id', 'kelas_id');
    }

    public function materi(): HasMany
    {
        return $this->hasMany(MateriKelas::class, 'bagian_id', 'bagian_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(TugasKelas::class, 'bagian_id', 'bagian_id')->orderBy('urutan');
    }
}
