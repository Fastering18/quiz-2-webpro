<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\DetailKrs;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\LaporanAdmin;
use App\Models\Mahasiswa;
use App\Models\MateriKelas;
use App\Models\PengumpulanTugas;
use App\Models\Perpustakaan;
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

        $this->assertDatabaseHas('classroom', ['kelas_id' => 'CLS_SO_B']);

        $mahasiswa = Mahasiswa::with('user', 'dosenWali', 'krs.detailKrs.classroom.mataKuliah')->orderBy('nrp')->first();
        $this->assertSame('5025251131', $mahasiswa->nrp);
        $this->assertNotNull($mahasiswa->dosenWali);
        $this->assertCount(2, $mahasiswa->krs);
        $this->assertCount(4, $mahasiswa->krs->firstWhere('semester_tahun', '2026/Ganjil')->detailKrs);
    }

    public function test_demo_dataset_exercises_all_features(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(3, Dosen::count());
        $this->assertSame(8, Mahasiswa::count());
        $this->assertSame(8, Classroom::count());
        foreach (Mahasiswa::all() as $m) {
            $this->assertNotNull($m->nip_wali);
        }
        // every dosen is wali of at least one student and teaches at least one class
        foreach (Dosen::all() as $d) {
            $this->assertGreaterThan(0, Mahasiswa::where('nip_wali', $d->nip)->count());
            $this->assertGreaterThan(0, Classroom::where('nip', $d->nip)->count());
        }

        // KRS in every state, with a graded past semester
        $this->assertEqualsCanonicalizing(['Disetujui', 'Menunggu', 'Ditolak'], Krs::distinct()->pluck('status_acc')->all());
        $this->assertTrue(DetailKrs::whereNotNull('nilai_akhir')->exists());

        // submissions: graded, ungraded, late
        $this->assertTrue(PengumpulanTugas::whereNotNull('nilai_tugas')->exists());
        $this->assertTrue(PengumpulanTugas::whereNull('nilai_tugas')->exists());
        $this->assertTrue(PengumpulanTugas::where('terlambat', true)->exists());
        $this->assertTrue(PengumpulanTugas::where('terlambat', false)->exists());

        // reports: open and answered
        $this->assertTrue(LaporanAdmin::whereNull('tanggapan_admin')->exists());
        $this->assertTrue(LaporanAdmin::whereNotNull('tanggapan_admin')->exists());
        $this->assertEqualsCanonicalizing(['Terkirim', 'Diproses', 'Selesai', 'Ditolak'], LaporanAdmin::distinct()->pluck('status_tiket')->all());

        $this->assertGreaterThanOrEqual(8, Perpustakaan::count());
        $this->assertGreaterThanOrEqual(8, MateriKelas::count());
        $this->assertDatabaseHas('pengumuman_classroom', ['kelas_id' => 'CLS_SO_B']);
    }

    public function test_documented_demo_logins_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([['admin', 'admin'], ['198501012010121001', 'dosen'], ['5025251131', 'mahasiswa'], ['5025251169', 'mahasiswa'], ['197803152005011002', 'dosen']] as [$u, $role]) {
            $this->postJson('/api/login', ['username' => $u, 'password' => 'password'])
                ->assertOk()->assertJsonPath('role', $role);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $users = User::count();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($users, User::count());
        $this->assertSame(8, Classroom::count());
    }
}
