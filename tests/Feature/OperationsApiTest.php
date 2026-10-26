<?php

namespace Tests\Feature;

use App\Models\LaporanAdmin;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_library_index_and_search(): void
    {
        $student = User::where('role', 'mahasiswa')->first();
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/library?search=Operating');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'books' => ['data'],
                'categories',
            ])
            ->assertJsonFragment(['judul_buku' => 'Operating System Concepts 10th Edition']);
    }

    public function test_admin_can_add_and_delete_library_book(): void
    {
        $admin = User::where('role', 'admin')->first();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/library', [
            'judul_buku' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
            'penulis' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
            'tahun' => 1994,
            'kategori' => 'Software Engineering',
            'nama_file' => 'gang_of_four_design_patterns.pdf',
            'file_dokumen_key' => 'library/gof_patterns.pdf',
            'ukuran_file' => 7500000,
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('book.judul_buku', 'Design Patterns: Elements of Reusable Object-Oriented Software');

        $bukuId = $createResponse->json('book.buku_id');

        $this->assertDatabaseHas('perpustakaan', [
            'buku_id' => $bukuId,
        ]);

        $deleteResponse = $this->deleteJson('/api/library/'.$bukuId);
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('perpustakaan', [
            'buku_id' => $bukuId,
        ]);
    }

    public function test_student_can_create_report_ticket(): void
    {
        $student = User::where('role', 'mahasiswa')->first();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/reports', [
            'kategori_modul' => 'Classroom',
            'judul_laporan' => 'File materi tidak dapat diunduh',
            'deskripsi_kendala' => 'Terjadi error 404 saat mengunduh slide materi pada pertemuan 2.',
            'lampiran_nama_file' => 'error404.png',
            'lampiran_bukti_key' => 'reports/error404.png',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('report.kategori_modul', 'Classroom')
            ->assertJsonPath('report.status_tiket', 'Terkirim');

        $this->assertDatabaseHas('laporan_admin', [
            'judul_laporan' => 'File materi tidak dapat diunduh',
            'user_id' => $student->user_id,
        ]);
    }

    public function test_admin_can_reply_to_ticket(): void
    {
        $admin = User::where('role', 'admin')->first();
        Sanctum::actingAs($admin);

        $report = LaporanAdmin::first();

        $response = $this->patchJson('/api/reports/'.$report->tiket_id.'/reply', [
            'tanggapan_admin' => 'Kendala telah diselesaikan oleh tim IT, silakan dicoba kembali.',
            'status_tiket' => 'Selesai',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('report.status_tiket', 'Selesai')
            ->assertJsonPath('report.admin_id', $admin->user_id);

        $this->assertDatabaseHas('laporan_admin', [
            'tiket_id' => $report->tiket_id,
            'status_tiket' => 'Selesai',
            'admin_id' => $admin->user_id,
        ]);
    }

    public function test_user_can_view_and_update_profile(): void
    {
        $student = User::where('role', 'mahasiswa')->first();
        Sanctum::actingAs($student);

        $showResponse = $this->getJson('/api/profile');
        $showResponse->assertStatus(200)
            ->assertJsonPath('profile.user_id', $student->user_id);

        $updateResponse = $this->putJson('/api/profile', [
            'nomor_telepon' => '089911223344',
            'alamat_rumah' => 'Jl. Mulyorejo No. 45, Surabaya',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('profile.nomor_telepon', '089911223344')
            ->assertJsonPath('profile.alamat_rumah', 'Jl. Mulyorejo No. 45, Surabaya');

        $this->assertDatabaseHas('pengguna', [
            'user_id' => $student->user_id,
            'nomor_telepon' => '089911223344',
        ]);
    }

    public function test_user_can_change_password(): void
    {
        $student = User::where('role', 'mahasiswa')->first();
        Sanctum::actingAs($student);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $student->refresh();
        $this->assertTrue(Hash::check('newpassword123', $student->password_hash));
    }
}
