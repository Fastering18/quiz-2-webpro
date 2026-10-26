<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Krs extends Model
{
    use HasFactory;

    protected $table = 'krs';

    protected $primaryKey = 'krs_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'krs_id',
        'nrp',
        'semester',
        'semester_tahun',
        'status_acc',
        'tanggal_cetak',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_cetak' => 'date',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'nrp', 'nrp');
    }

    public function detailKrs(): HasMany
    {
        return $this->hasMany(DetailKrs::class, 'krs_id', 'krs_id');
    }
}
