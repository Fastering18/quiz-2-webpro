<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    use HasFactory;

    protected $table = 'dosen';

    protected $primaryKey = 'nip';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'nip',
        'user_id',
        'departemen',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function mahasiswaBimbingan(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'nip_wali', 'nip');
    }

    public function classroom(): HasMany
    {
        return $this->hasMany(Classroom::class, 'nip', 'nip');
    }
}
