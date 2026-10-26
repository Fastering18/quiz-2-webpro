<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perpustakaan extends Model
{
    use HasFactory;

    protected $table = 'perpustakaan';

    protected $primaryKey = 'buku_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'buku_id',
        'judul_buku',
        'penulis',
        'publisher_id',
        'tahun',
        'kategori',
        'nama_file',
        'file_dokumen_key',
        'ukuran_file',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'ukuran_file' => 'integer',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id', 'user_id');
    }
}
