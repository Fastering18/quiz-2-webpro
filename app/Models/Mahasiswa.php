<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'nrp';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nrp',
        'user_id',
        'nip_wali',
        'departemen',
        'semester_aktif',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function dosenWali(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'nip_wali', 'nip');
    }

    public function krs(): HasMany
    {
        return $this->hasMany(Krs::class, 'nrp', 'nrp');
    }

    public function pengumpulanTugas(): HasMany
    {
        return $this->hasMany(PengumpulanTugas::class, 'nrp', 'nrp');
    }
}
