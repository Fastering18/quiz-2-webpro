<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pengguna';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $appends = ['foto_url'];

    protected $fillable = [
        'user_id',
        'username',
        'nama_lengkap',
        'email',
        'password_hash',
        'role',
        'foto_profil_key',
        'nomor_telepon',
        'alamat_rumah',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the name of the password attribute for auth.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class, 'user_id', 'user_id');
    }

    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class, 'user_id', 'user_id');
    }

    public function bukuPerpustakaan(): HasMany
    {
        return $this->hasMany(Perpustakaan::class, 'publisher_id', 'user_id');
    }

    public function laporanDiajukan(): HasMany
    {
        return $this->hasMany(LaporanAdmin::class, 'user_id', 'user_id');
    }

    public function laporanDitangani(): HasMany
    {
        return $this->hasMany(LaporanAdmin::class, 'admin_id', 'user_id');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return app(\App\Services\FileStorage::class)->temporaryUrl($this->foto_profil_key);
    }
}
