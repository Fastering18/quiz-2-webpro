<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanAdmin extends Model
{
    use HasFactory;

    protected $table = 'laporan_admin';

    protected $primaryKey = 'tiket_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'tiket_id',
        'user_id',
        'kategori_modul',
        'judul_laporan',
        'deskripsi_kendala',
        'lampiran_nama_file',
        'lampiran_bukti_key',
        'status_tiket',
        'tanggal_lapor',
        'admin_id',
        'tanggapan_admin',
        'tanggal_tanggapan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lapor' => 'datetime',
            'tanggal_tanggapan' => 'datetime',
        ];
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
