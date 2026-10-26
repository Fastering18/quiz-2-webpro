<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\LaporanAdmin;
use App\Models\MateriKelas;
use App\Models\PengumpulanTugas;
use App\Models\PengumumanClassroom;
use App\Models\TugasKelas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiGapsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['filesystems.disks.s3.bucket' => 'b', 'filesystems.disks.s3.key' => 'k', 'filesystems.disks.s3.secret' => 's']);
        Storage::fake('s3')->buildTemporaryUrlsUsing(fn (string $path) => 'https://s3.test/'.$path);
    }

    private function userOf(string $role): User
    {
        if ($role === 'mahasiswa') {
            return User::where('user_id', 'mhs_5025251131')->first();
        }

        return User::where('role', $role)->orderBy('user_id')->first();
    }

    /** A dosen that neither teaches CLS_SO_B nor is wali of the seeded student. */
    private function otherDosen(): User
    {
        $u = User::create([
            'user_id' => 'zz_dosen_other', 'username' => 'dosenlain', 'nama_lengkap' => 'Dosen Lain', 'email' => 'lain@its.ac.id',
            'password_hash' => 'password', 'role' => 'dosen',
        ]);
        Dosen::create(['nip' => '9999001', 'user_id' => $u->user_id, 'departemen' => 'Teknik Informatika', 'status' => 'Aktif']);

        return $u;
    }

    // ---- error handling / CORS / throttling

    public function test_api_errors_are_json_with_indonesian_messages(): void
    {
        $this->getJson('/api/tidak-ada')->assertStatus(404)->assertJson(['message' => 'Data atau halaman yang diminta tidak ditemukan.']);
        $this->getJson('/api/me')->assertStatus(401)->assertJsonPath('message', fn ($m) => str_contains($m, 'belum masuk'));
        $this->putJson('/api/login')->assertStatus(405)->assertJsonPath('message', fn ($m) => str_contains($m, 'tidak diizinkan'));

        Sanctum::actingAs($this->userOf('mahasiswa'));
        $this->getJson('/api/classrooms/TIDAK_ADA')->assertStatus(404)->assertJsonStructure(['message']);
        $this->postJson('/api/reports', [])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['kategori_modul']]);

        // non-API HTML requests are untouched
        $this->get('/halaman-tidak-ada')->assertStatus(404);
    }

    public function test_unexpected_exception_returns_generic_json_500(): void
    {
        Route::get('/api/_boom', fn () => throw new \RuntimeException('secret detail'));
        config(['app.debug' => false]);

        $this->getJson('/api/_boom')->assertStatus(500)
            ->assertExactJson(['message' => 'Terjadi kesalahan pada server. Silakan coba lagi nanti.']);
    }

    public function test_cors_preflight_allows_authorization_header(): void
    {
        $r = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://frontend.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type',
        ]);

        $r->assertSuccessful();
        $this->assertSame('*', $r->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsStringIgnoringCase('authorization', (string) $r->headers->get('Access-Control-Allow-Headers'));
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['username' => 'x', 'password' => 'y'])->assertStatus(422);
        }
        $this->postJson('/api/login', ['username' => 'x', 'password' => 'y'])->assertStatus(429)
            ->assertJsonStructure(['message']);
    }

    // ---- KRS

    public function test_krs_pdf_download_and_authorization(): void
    {
        $student = $this->userOf('mahasiswa');
        $krs = Krs::where('nrp', $student->mahasiswa->nrp)->where('semester_tahun', '2026/Ganjil')->first();

        Sanctum::actingAs($student);
        $r = $this->get("/api/krs/{$krs->krs_id}/pdf");
        $r->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-1.4', $r->getContent());
        $this->assertStringContainsString('attachment; filename="KRS_', $r->headers->get('Content-Disposition'));

        Sanctum::actingAs($this->userOf('dosen'));
        $this->get("/api/krs/{$krs->krs_id}/pdf")->assertOk();

        Sanctum::actingAs($this->otherDosen());
        $this->get("/api/krs/{$krs->krs_id}/pdf")->assertStatus(403);
        $this->getJson("/api/krs/{$krs->krs_id}")->assertStatus(403);
        $this->getJson('/api/krs')->assertOk()->assertJsonCount(0, 'krs_list');
    }

    public function test_student_cannot_read_other_students_krs_and_krs_filter_works(): void
    {
        $mine = $this->userOf('mahasiswa');
        $other = User::create(['user_id' => 'zz_m2', 'username' => 'mhs2', 'nama_lengkap' => 'M Dua', 'email' => 'm2@s.its.ac.id', 'password_hash' => 'password', 'role' => 'mahasiswa']);
        $other->mahasiswa()->create(['nrp' => '5025000002', 'nip_wali' => $this->userOf('dosen')->dosen->nip, 'departemen' => 'TI', 'semester_aktif' => 1, 'status' => 'Aktif']);
        $krs = Krs::where('nrp', $mine->mahasiswa->nrp)->where('semester_tahun', '2026/Ganjil')->first();

        Sanctum::actingAs($other);
        $this->getJson("/api/krs/{$krs->krs_id}")->assertStatus(403);

        Sanctum::actingAs($mine);
        $this->getJson('/api/krs?semester_tahun=1900/Genap')->assertOk()->assertJsonCount(0, 'krs_list');
        $this->getJson('/api/krs?semester_tahun='.urlencode($krs->semester_tahun))->assertOk()->assertJsonCount(1, 'krs_list');
    }

    public function test_only_dosen_wali_or_admin_can_approve_and_approved_krs_is_locked(): void
    {
        $student = $this->userOf('mahasiswa');
        $krs = Krs::where('nrp', $student->mahasiswa->nrp)->where('semester_tahun', '2026/Ganjil')->first();

        Sanctum::actingAs($this->otherDosen());
        $this->patchJson("/api/krs/{$krs->krs_id}/approve", ['status_acc' => 'Disetujui'])->assertStatus(403);

        Sanctum::actingAs($this->userOf('dosen'));
        $this->patchJson("/api/krs/{$krs->krs_id}/approve", ['status_acc' => 'Disetujui'])->assertOk();

        Sanctum::actingAs($student);
        $this->postJson('/api/krs', [
            'semester' => $krs->semester, 'semester_tahun' => $krs->semester_tahun, 'kelas_ids' => ['CLS_SO_B'],
        ])->assertStatus(409);

        Sanctum::actingAs($this->userOf('admin'));
        $this->patchJson("/api/krs/{$krs->krs_id}/approve", ['status_acc' => 'Menunggu'])->assertOk();
    }

    // ---- classroom content editing

    public function test_dosen_can_edit_and_delete_announcement_section_material_task(): void
    {
        $dosen = $this->userOf('dosen');
        Sanctum::actingAs($dosen);

        $ann = PengumumanClassroom::where('kelas_id', 'CLS_SO_B')->orderBy('pengumuman_id')->first();
        $this->putJson("/api/announcements/{$ann->pengumuman_id}", ['konten' => 'Diubah'])->assertOk()->assertJsonPath('announcement.konten', 'Diubah');
        $this->putJson("/api/announcements/{$ann->pengumuman_id}", ['konten' => ''])->assertStatus(422);

        $sectionId = $this->postJson('/api/classrooms/CLS_SO_B/sections', ['judul_bagian' => 'Baru'])->json('section.bagian_id');
        $this->putJson("/api/sections/{$sectionId}", ['judul_bagian' => 'Judul baru', 'konten_ringkasan' => 'Ringkas'])
            ->assertOk()->assertJsonPath('section.judul_bagian', 'Judul baru')->assertJsonPath('section.konten_ringkasan', 'Ringkas');

        $matId = $this->postJson("/api/sections/{$sectionId}/materials", [
            'judul_materi' => 'M1', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ])->assertCreated()->json('material.materi_id');
        $old = MateriKelas::find($matId)->file_materi_key;
        $this->putJson("/api/materials/{$matId}", ['judul_materi' => 'M1b'])->assertOk()->assertJsonPath('material.judul_materi', 'M1b');
        $this->post("/api/materials/{$matId}", ['file' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $new = MateriKelas::find($matId);
        $this->assertNotSame($old, $new->file_materi_key);
        $this->assertSame('b.pdf', $new->nama_file);
        Storage::disk('s3')->assertMissing($old);
        Storage::disk('s3')->assertExists($new->file_materi_key);

        $taskId = $this->postJson("/api/sections/{$sectionId}/tasks", ['judul_tugas' => 'T', 'tenggat_waktu' => now()->addDay()->toDateTimeString()])->json('task.tugas_id');
        $this->putJson("/api/tasks/{$taskId}", ['judul_tugas' => 'T2', 'tenggat_waktu' => now()->addDays(3)->toDateTimeString()])
            ->assertOk()->assertJsonPath('task.judul_tugas', 'T2');
        $this->putJson("/api/tasks/{$taskId}", ['tenggat_waktu' => 'bukan tanggal'])->assertStatus(422);

        // outsiders cannot
        Sanctum::actingAs($this->otherDosen());
        $this->putJson("/api/tasks/{$taskId}", ['judul_tugas' => 'X'])->assertStatus(403);
        $this->putJson("/api/sections/{$sectionId}", ['judul_bagian' => 'X'])->assertStatus(403);
        $this->putJson("/api/materials/{$matId}", ['judul_materi' => 'X'])->assertStatus(403);
        $this->deleteJson("/api/announcements/{$ann->pengumuman_id}")->assertStatus(403);
        Sanctum::actingAs($this->userOf('mahasiswa'));
        $this->putJson("/api/announcements/{$ann->pengumuman_id}", ['konten' => 'X'])->assertStatus(403);

        Sanctum::actingAs($dosen);
        $this->deleteJson("/api/announcements/{$ann->pengumuman_id}")->assertOk();
        $this->assertNull(PengumumanClassroom::find($ann->pengumuman_id));
    }

    public function test_only_class_dosen_can_grade(): void
    {
        $sub = PengumpulanTugas::orderBy('submit_id')->first();
        $this->assertNotNull($sub);

        Sanctum::actingAs($this->otherDosen());
        $this->postJson("/api/submissions/{$sub->submit_id}/grade", ['nilai_tugas' => 10])->assertStatus(403);

        Sanctum::actingAs($this->userOf('dosen'));
        $this->postJson("/api/submissions/{$sub->submit_id}/grade", ['nilai_tugas' => 88])->assertOk();
        $this->postJson("/api/submissions/{$sub->submit_id}/grade", ['nilai_tugas' => 101])->assertStatus(422);
    }

    // ---- profile

    public function test_profile_name_update_and_photo_removal_and_password_rules(): void
    {
        $student = $this->userOf('mahasiswa');
        Sanctum::actingAs($student);

        $this->putJson('/api/profile', ['nama_lengkap' => ''])->assertStatus(422)->assertJsonValidationErrors('nama_lengkap');
        $this->putJson('/api/profile', ['nama_lengkap' => 'Nama Baru'])->assertOk()->assertJsonPath('profile.nama_lengkap', 'Nama Baru');
        $this->putJson('/api/profile', ['nomor_telepon' => '0812'])->assertOk()->assertJsonPath('profile.nama_lengkap', 'Nama Baru');

        $this->post('/api/profile', ['foto' => UploadedFile::fake()->create('f.png', 20, 'image/png')], ['Accept' => 'application/json'])->assertOk();
        $key = $student->fresh()->foto_profil_key;
        $this->assertNotNull($key);
        $this->deleteJson('/api/profile/photo')->assertOk();
        $this->assertNull($student->fresh()->foto_profil_key);
        Storage::disk('s3')->assertMissing($key);

        $this->putJson('/api/profile/password', ['current_password' => 'x', 'password' => 'Abcdefg#123', 'password_confirmation' => 'Abcdefg#123'])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
        $student->update(['password_hash' => 'Lama#12345']);
        $this->putJson('/api/profile/password', ['current_password' => 'Lama#12345', 'password' => 'Lama#12345', 'password_confirmation' => 'Lama#12345'])
            ->assertStatus(422)->assertJsonValidationErrors('password');
        $this->putJson('/api/profile/password', ['current_password' => 'Lama#12345', 'password' => 'Baru#123456', 'password_confirmation' => 'Baru#123456'])->assertOk();
        $this->assertTrue(Hash::check('Baru#123456', $student->fresh()->password_hash));
    }

    // ---- reports

    public function test_report_categories_filters_and_withdraw(): void
    {
        $student = $this->userOf('mahasiswa');
        Sanctum::actingAs($student);

        $this->getJson('/api/reports/categories')->assertOk()->assertJsonFragment(['Profil dan Akun']);
        $id = $this->postJson('/api/reports', ['kategori_modul' => 'KRS', 'judul_laporan' => 'PDF KRS gagal', 'deskripsi_kendala' => 'Tidak bisa diunduh'])->json('report.tiket_id');

        $this->getJson('/api/reports?kategori=KRS&search='.urlencode('PDF KRS gagal'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/reports?search=zzzz')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->otherDosen());
        $this->deleteJson("/api/reports/{$id}")->assertStatus(403);

        Sanctum::actingAs($student);
        $this->deleteJson("/api/reports/{$id}")->assertOk();
        $this->assertNull(LaporanAdmin::find($id));

        // answered tickets cannot be withdrawn by the owner
        $answered = LaporanAdmin::whereNotNull('tanggapan_admin')->where('user_id', $student->user_id)->first();
        $this->assertNotNull($answered);
        $this->deleteJson("/api/reports/{$answered->tiket_id}")->assertStatus(409);
        Sanctum::actingAs($this->userOf('admin'));
        $this->deleteJson("/api/reports/{$answered->tiket_id}")->assertOk();
    }

    // ---- dashboard + web

    public function test_dashboard_overdue_and_dosen_counters(): void
    {
        $student = $this->userOf('mahasiswa');
        $task = TugasKelas::whereHas('bagianKelas', fn ($q) => $q->where('kelas_id', 'CLS_SO_B'))->orderBy('tugas_id')->get()->last();
        $task->pengumpulan()->delete();
        $task->update(['tenggat_waktu' => now()->subDays(2)]);

        Sanctum::actingAs($student);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('summary.tugas_terlambat', fn ($n) => $n >= 1)
            ->assertJsonStructure(['summary' => ['tugas_belum_selesai', 'tugas_terlambat', 'status_krs', 'total_sks']]);

        Sanctum::actingAs($this->userOf('dosen'));
        $this->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['perlu_dinilai', 'krs_menunggu', 'total_kelas_ajar']);
    }

    public function test_public_web_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get('/reset-password')->assertOk();
    }
}
