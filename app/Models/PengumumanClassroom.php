<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengumumanClassroom extends Model
{
    use HasFactory;

    protected $table = 'pengumuman_classroom';

    protected $primaryKey = 'pengumuman_id';

    public $timestamps = false;

    protected $fillable = [
        'kelas_id',
        'konten',
        'tanggal_unggah',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_unggah' => 'date',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'kelas_id', 'kelas_id');
    }
}
