<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $primaryKey = 'kode_mk';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'semester',
    ];

    public function classroom(): HasMany
    {
        return $this->hasMany(Classroom::class, 'kode_mk', 'kode_mk');
    }
}
