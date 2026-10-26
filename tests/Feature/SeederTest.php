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

        $this->assertDatabaseHas('classroom', [
            'kelas_id' => 'CLS_SO_B',
        ]);

        $studentUser = User::where('role', 'mahasiswa')->first();
        $this->assertNotNull($studentUser);

        $mahasiswa = Mahasiswa::with('user', 'dosenWali', 'krs.detailKrs.classroom.mataKuliah')->first();
        $this->assertNotNull($mahasiswa);
        $this->assertNotNull($mahasiswa->dosenWali);
        $this->assertCount(1, $mahasiswa->krs);
        $this->assertCount(3, $mahasiswa->krs->first()->detailKrs);
    }
}
