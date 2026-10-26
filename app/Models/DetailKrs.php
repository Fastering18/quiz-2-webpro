<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailKrs extends Model
{
    use HasFactory;

    protected $table = 'detail_krs';

    protected $primaryKey = 'detail_id';

    public $timestamps = false;

    protected $fillable = [
        'krs_id',
        'kelas_id',
        'nilai_akhir',
    ];

    public function krs(): BelongsTo
    {
        return $this->belongsTo(Krs::class, 'krs_id', 'krs_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'kelas_id', 'kelas_id');
    }
}
