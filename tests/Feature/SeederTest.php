<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeds_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('pengguna', [
            'username' => '5025251131',
            'role' => 'mahasiswa',
        ]);

        $this->assertDatabaseHas('mahasiswa', [
            'nrp' => '5025251131',
        ]);

        $this->assertDatabaseHas('classroom', [
            'kelas_id' => 'CLS_SO_B',
        ]);

        $user = User::where('username', '5025251131')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Muhammad Azka Asyrafany', $user->nama_lengkap);

        $mahasiswa = Mahasiswa::with('user', 'dosenWali', 'krs.detailKrs.classroom.mataKuliah')->first();
        $this->assertNotNull($mahasiswa);
        $this->assertEquals('198501012010121001', $mahasiswa->dosenWali->nip);
        $this->assertCount(1, $mahasiswa->krs);
        $this->assertCount(3, $mahasiswa->krs->first()->detailKrs);
    }
}
