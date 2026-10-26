<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function actAsAdmin(): User
    {
        $admin = User::where('role', 'admin')->orderBy('user_id')->first();
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function newDosenPayload(array $over = []): array
    {
        return array_merge([
            'role' => 'dosen',
            'username' => 'dosen2',
            'nama_lengkap' => 'Dosen Dua',
            'email' => 'dosen2@its.ac.id',
            'nip' => '2022022',
            'departemen' => 'Sistem Informasi',
        ], $over);
    }

    public function test_requires_authentication_and_admin_role(): void
    {
        $this->getJson('/api/admin/stats')->assertStatus(401);

        Sanctum::actingAs(User::where('role', 'mahasiswa')->orderBy('user_id')->first());
        $this->getJson('/api/admin/stats')->assertStatus(403)->assertJsonStructure(['message']);

        Sanctum::actingAs(User::where('role', 'dosen')->orderBy('user_id')->first());
        $this->getJson('/api/admin/users')->assertStatus(403);
        $this->postJson('/api/admin/users', [])->assertStatus(403);
    }

    public function test_login_and_me_include_role(): void
    {
        $this->postJson('/api/login', ['username' => 'admin', 'password' => 'admin'])
            ->assertStatus(200)
            ->assertJsonPath('role', 'admin');

        $this->actAsAdmin();
        $this->getJson('/api/me')->assertJsonPath('role', 'admin');
    }

    public function test_stats_and_admin_dashboard(): void
    {
        $this->actAsAdmin();

        $this->getJson('/api/admin/stats')->assertStatus(200)
            ->assertJsonPath('stats.pengguna.admin', 1)
            ->assertJsonPath('stats.pengguna.dosen', 3)
            ->assertJsonStructure(['stats' => ['pengguna' => ['total'], 'kelas', 'buku', 'tiket' => ['terbuka']]]);

        $this->getJson('/api/dashboard')->assertStatus(200)
            ->assertJsonPath('role', 'admin')
            ->assertJsonStructure(['stats' => ['pengguna']]);

        $this->getJson('/api/classrooms')->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_create_each_role(): void
    {
        $this->actAsAdmin();

        // dosen with generated password
        $res = $this->postJson('/api/admin/users', $this->newDosenPayload())->assertStatus(201);
        $res->assertJsonPath('user.role', 'dosen')->assertJsonPath('user.dosen.nip', '2022022');
        $generated = $res->json('generated_password');
        $this->assertNotEmpty($generated);
        $this->assertDatabaseHas('dosen', ['nip' => '2022022']);

        // the generated password works
        $this->postJson('/api/login', ['username' => 'dosen2', 'password' => $generated])->assertStatus(200);
        Sanctum::actingAs(User::where('role', 'admin')->orderBy('user_id')->first());

        // mahasiswa linked to the new wali, with given password
        $this->postJson('/api/admin/users', [
            'role' => 'mahasiswa',
            'username' => 'mhs2',
            'nama_lengkap' => 'Mahasiswa Dua',
            'email' => 'mhs2@student.its.ac.id',
            'password' => 'rahasia123',
            'nrp' => '5025250001',
            'nip_wali' => '2022022',
            'departemen' => 'Sistem Informasi',
            'semester_aktif' => 2,
        ])->assertStatus(201)
            ->assertJsonPath('user.mahasiswa.nip_wali', '2022022')
            ->assertJsonMissing(['generated_password']);

        // admin
        $this->postJson('/api/admin/users', [
            'role' => 'admin',
            'username' => 'admin2',
            'nama_lengkap' => 'Admin Dua',
            'email' => 'admin2@its.ac.id',
        ])->assertStatus(201)->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('pengguna', ['username' => 'admin2', 'role' => 'admin']);
        $this->assertDatabaseCount('mahasiswa', 9);
    }

    public function test_create_user_validation(): void
    {
        $this->actAsAdmin();

        $this->postJson('/api/admin/users', ['role' => 'mahasiswa', 'username' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['nama_lengkap', 'email', 'nrp', 'nip_wali']);

        $this->postJson('/api/admin/users', $this->newDosenPayload(['username' => 'admin']))
            ->assertStatus(422)->assertJsonValidationErrors(['username']);

        $this->postJson('/api/admin/users', $this->newDosenPayload(['role' => 'rektor']))
            ->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_list_filter_show_update_and_reset_password(): void
    {
        $this->actAsAdmin();

        $this->getJson('/api/admin/users?role=dosen')->assertStatus(200)
            ->assertJsonCount(3, 'data');
        $this->getJson('/api/admin/users?q=lontong')->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/users?role=bogus')->assertStatus(422);

        $mhs = User::where('role', 'mahasiswa')->orderBy('user_id')->first();
        $this->getJson('/api/admin/users/'.$mhs->user_id)->assertStatus(200)
            ->assertJsonPath('user.mahasiswa.nrp', $mhs->mahasiswa->nrp);
        $this->getJson('/api/admin/users/nope')->assertStatus(404);

        $this->putJson('/api/admin/users/'.$mhs->user_id, ['nama_lengkap' => 'Nama Baru', 'semester_aktif' => 5])
            ->assertStatus(200)->assertJsonPath('user.nama_lengkap', 'Nama Baru')
            ->assertJsonPath('user.mahasiswa.semester_aktif', 5);

        $res = $this->patchJson('/api/admin/users/'.$mhs->user_id.'/password', ['password' => 'baru12345'])
            ->assertStatus(200);
        $res->assertJsonMissing(['generated_password']);
        $this->postJson('/api/login', ['username' => $mhs->username, 'password' => 'baru12345'])->assertStatus(200);

        $gen = $this->patchJson('/api/admin/users/'.$mhs->user_id.'/password')->assertStatus(200);
        $this->assertNotEmpty($gen->json('generated_password'));
    }

    public function test_link_mahasiswa_to_dosen_wali(): void
    {
        $this->actAsAdmin();
        $this->postJson('/api/admin/users', $this->newDosenPayload())->assertStatus(201);

        $mhs = Mahasiswa::orderBy('nrp')->first();

        $this->putJson('/api/admin/mahasiswa/'.$mhs->nrp, ['nip_wali' => '2022022', 'status' => 'Cuti'])
            ->assertStatus(200)
            ->assertJsonPath('mahasiswa.nip_wali', '2022022')
            ->assertJsonPath('mahasiswa.status', 'Cuti')
            ->assertJsonPath('mahasiswa.dosen_wali.nip', '2022022');

        $this->putJson('/api/admin/mahasiswa/'.$mhs->nrp, ['nip_wali' => 'tidak-ada'])
            ->assertStatus(422)->assertJsonValidationErrors(['nip_wali']);

        $this->getJson('/api/admin/mahasiswa?nip_wali=2022022')->assertJsonCount(1, 'data');

        $this->getJson('/api/admin/dosen')->assertStatus(200)
            ->assertJsonFragment(['nip' => '2022022', 'mahasiswa_bimbingan_count' => 1]);
        $this->getJson('/api/admin/dosen/2022022/mahasiswa')->assertStatus(200)
            ->assertJsonPath('mahasiswa.data.0.nrp', $mhs->nrp);
    }

    public function test_cannot_delete_self_and_deletion_safety(): void
    {
        $admin = $this->actAsAdmin();

        $this->deleteJson('/api/admin/users/'.$admin->user_id)->assertStatus(403);
        $this->assertDatabaseHas('pengguna', ['user_id' => $admin->user_id]);

        // dosen wali with students and classes -> 409
        $dosen = Dosen::orderBy('nip')->first();
        $this->deleteJson('/api/admin/users/'.$dosen->user_id)->assertStatus(409)->assertJsonStructure(['message']);

        // mahasiswa with KRS -> 409
        $mhs = Mahasiswa::orderBy('nrp')->first();
        $this->deleteJson('/api/admin/users/'.$mhs->user_id)->assertStatus(409);

        // fresh mahasiswa without dependents -> deleted
        $this->postJson('/api/admin/users', [
            'role' => 'mahasiswa', 'username' => 'tmp1', 'nama_lengkap' => 'Tmp',
            'email' => 'tmp1@x.id', 'nrp' => '5025259999', 'nip_wali' => $dosen->nip, 'departemen' => 'TI',
        ])->assertStatus(201);
        $tmp = User::where('username', 'tmp1')->first();
        $this->deleteJson('/api/admin/users/'.$tmp->user_id)->assertStatus(200);
        $this->assertDatabaseMissing('pengguna', ['username' => 'tmp1']);
        $this->assertDatabaseMissing('mahasiswa', ['nrp' => '5025259999']);
    }

    public function test_mata_kuliah_and_kelas_crud(): void
    {
        $this->actAsAdmin();
        $nip = Dosen::orderBy('nip')->first()->nip;

        $this->postJson('/api/admin/mata-kuliah', ['kode_mk' => 'IF999', 'nama_mk' => 'Kecerdasan Buatan', 'sks' => 3, 'semester' => 5])
            ->assertStatus(201);
        $this->postJson('/api/admin/mata-kuliah', ['kode_mk' => 'IF999', 'nama_mk' => 'Dup', 'sks' => 3, 'semester' => 5])
            ->assertStatus(422);
        $this->putJson('/api/admin/mata-kuliah/IF999', ['sks' => 4])->assertJsonPath('mata_kuliah.sks', 4);

        $this->postJson('/api/admin/kelas', [
            'kelas_id' => 'CLS_AI_A', 'kode_mk' => 'IF999', 'nip' => $nip,
            'nama_kelas' => 'Kecerdasan Buatan (A)', 'semester_tahun' => '2025/Ganjil', 'kuota_kelas' => 30,
        ])->assertStatus(201)->assertJsonPath('classroom.dosen.nip', $nip);

        // mata kuliah in use -> 409
        $this->deleteJson('/api/admin/mata-kuliah/IF999')->assertStatus(409);

        $this->putJson('/api/admin/kelas/CLS_AI_A', ['kuota_kelas' => 45])->assertJsonPath('classroom.kuota_kelas', 45);
        $this->getJson('/api/admin/kelas?kode_mk=IF999')->assertJsonCount(1, 'data');

        // class with enrolled students -> 409
        $this->deleteJson('/api/admin/kelas/CLS_SO_B')->assertStatus(409);

        $this->deleteJson('/api/admin/kelas/CLS_AI_A')->assertStatus(200);
        $this->deleteJson('/api/admin/mata-kuliah/IF999')->assertStatus(200);
    }

    public function test_krs_override_and_library_moderation(): void
    {
        $this->actAsAdmin();
        $mhs = Mahasiswa::orderBy('nrp')->first();

        $krs = $this->postJson('/api/admin/krs', [
            'nrp' => $mhs->nrp, 'semester' => 4, 'semester_tahun' => '2026/Genap', 'kelas_ids' => ['CLS_SO_B'],
        ])->assertStatus(201)->assertJsonPath('krs.status_acc', 'Disetujui');
        $krsId = $krs->json('krs.krs_id');

        $this->postJson('/api/admin/krs', [
            'nrp' => $mhs->nrp, 'semester' => 4, 'semester_tahun' => '2026/Genap',
        ])->assertStatus(409);

        $this->postJson("/api/admin/krs/{$krsId}/kelas", ['kelas_id' => 'CLS_SO_B'])->assertStatus(409);
        $this->postJson("/api/admin/krs/{$krsId}/kelas", ['kelas_id' => 'CLS_PBO_A'])->assertStatus(201);
        $this->patchJson("/api/admin/krs/{$krsId}/kelas/CLS_PBO_A", ['nilai_akhir' => 90])->assertStatus(200);
        $this->patchJson("/api/admin/krs/{$krsId}/status", ['status_acc' => 'Ditolak'])
            ->assertJsonPath('krs.status_acc', 'Ditolak');
        $this->deleteJson("/api/admin/krs/{$krsId}/kelas/CLS_PBO_A")->assertStatus(200);
        $this->deleteJson("/api/admin/krs/{$krsId}")->assertStatus(200);
        $this->assertDatabaseMissing('krs', ['krs_id' => $krsId]);

        $this->getJson('/api/admin/krs')->assertStatus(200)->assertJsonStructure(['data']);

        $book = $this->getJson('/api/admin/library')->assertStatus(200)->json('data.0');
        $this->putJson('/api/admin/library/'.$book['buku_id'], ['kategori' => 'Moderasi'])
            ->assertJsonPath('book.kategori', 'Moderasi');
        $this->deleteJson('/api/admin/library/'.$book['buku_id'])->assertStatus(200);
    }

    public function test_seeder_is_idempotent_and_keeps_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('username', 'admin')->count());
        $this->assertSame(3, User::where('role', 'dosen')->count());
    }
}
