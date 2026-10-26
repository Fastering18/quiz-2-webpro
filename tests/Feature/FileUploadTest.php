<?php

namespace Tests\Feature;

use App\Models\BagianKelas;
use App\Models\Dosen;
use App\Models\LaporanAdmin;
use App\Models\Mahasiswa;
use App\Models\MateriKelas;
use App\Models\Perpustakaan;
use App\Models\PengumpulanTugas;
use App\Models\TugasKelas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // pretend S3 is configured; the disk itself is faked
        config([
            'filesystems.disks.s3.bucket' => 'test-bucket',
            'filesystems.disks.s3.key' => 'k',
            'filesystems.disks.s3.secret' => 's',
        ]);
        Storage::fake('s3')->buildTemporaryUrlsUsing(
            fn (string $path, $expiration, array $options) => 'https://s3.test/'.$path.'?X-Amz-Signature=sig'
        );
    }

    private function pdf(string $name = 'Slide Bab 1.pdf', int $kb = 50): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function png(string $name = 'x.png'): UploadedFile
    {
        // 1x1 transparent PNG (GD is not required)
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));
    }

    private function student(): User
    {
        return User::where('role', 'mahasiswa')->first();
    }

    private function outsider(): User
    {
        $u = User::create([
            'user_id' => 'id_out', 'username' => 'outsider', 'nama_lengkap' => 'Luar Kelas',
            'email' => 'out@student.its.ac.id', 'password_hash' => Hash::make('x'), 'role' => 'mahasiswa',
        ]);
        Mahasiswa::create([
            'nrp' => '5025259999', 'user_id' => $u->user_id, 'nip_wali' => Dosen::first()->nip,
            'departemen' => 'TI', 'semester_aktif' => 1, 'status' => 'Aktif',
        ]);

        return $u;
    }

    private function otherDosen(): User
    {
        $u = User::create([
            'user_id' => 'id_d2', 'username' => 'dosen2', 'nama_lengkap' => 'Dosen Lain',
            'email' => 'd2@its.ac.id', 'password_hash' => Hash::make('x'), 'role' => 'dosen',
        ]);
        Dosen::create(['nip' => '2022099', 'user_id' => $u->user_id, 'departemen' => 'TI', 'status' => 'Aktif']);

        return $u;
    }

    public function test_dosen_uploads_material_and_class_members_download_it(): void
    {
        $section = BagianKelas::where('kelas_id', 'CLS_SO_B')->first();
        Sanctum::actingAs(User::where('role', 'dosen')->first());

        $res = $this->post('/api/sections/'.$section->bagian_id.'/materials', [
            'judul_materi' => 'Slide Nyata',
            'file' => $this->pdf(),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $key = $res->json('material.file_materi_key');
        $this->assertStringStartsWith('materials/CLS_SO_B/', $key);
        $this->assertStringEndsWith('-slide-bab-1.pdf', $key);
        Storage::disk('s3')->assertExists($key);
        $res->assertJsonPath('material.nama_file', 'Slide Bab 1.pdf');
        $this->assertStringContainsString('https://s3.test/'.$key, $res->json('material.file_url'));

        $id = $res->json('material.materi_id');

        // enrolled student -> presigned url
        Sanctum::actingAs($this->student());
        $this->getJson('/api/files/materials/'.$id)->assertStatus(200)
            ->assertJsonStructure(['url', 'nama_file', 'expires_at'])
            ->assertJsonPath('nama_file', 'Slide Bab 1.pdf');
        // plain request -> redirect
        $this->get('/api/files/materials/'.$id)->assertRedirectContains('https://s3.test/'.$key);

        // classroom view includes file_url for materials
        $this->getJson('/api/classrooms/CLS_SO_B')->assertStatus(200)
            ->assertJsonPath('classroom.bagian_kelas.0.materi.0.file_url', fn ($v) => is_string($v) || $v === null);

        // student not in class: denied (file + classroom view)
        Sanctum::actingAs($this->outsider());
        $this->getJson('/api/files/materials/'.$id)->assertStatus(403);
        $this->getJson('/api/classrooms/CLS_SO_B')->assertStatus(403);

        // another dosen: denied
        Sanctum::actingAs($this->otherDosen());
        $this->getJson('/api/files/materials/'.$id)->assertStatus(403);
        $this->post('/api/sections/'.$section->bagian_id.'/materials', ['judul_materi' => 'x', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertStatus(403);

        // admin: allowed
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $this->getJson('/api/files/materials/'.$id)->assertStatus(200);
    }

    public function test_student_cannot_upload_material(): void
    {
        $section = BagianKelas::where('kelas_id', 'CLS_SO_B')->first();
        Sanctum::actingAs($this->student());

        $this->post('/api/sections/'.$section->bagian_id.'/materials', ['judul_materi' => 'x', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_material_validation_for_size_and_type(): void
    {
        $section = BagianKelas::where('kelas_id', 'CLS_SO_B')->first();
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $url = '/api/sections/'.$section->bagian_id.'/materials';
        $h = ['Accept' => 'application/json'];

        $this->post($url, ['judul_materi' => 'Besar', 'file' => $this->pdf('big.pdf', 10241)], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->post($url, ['judul_materi' => 'Exe', 'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['file']);

        // extension says pdf but content is a PNG image
        $tmp = tempnam(sys_get_temp_dir(), 'png');
        file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $png = new UploadedFile($tmp, 'fake.pdf', null, null, true); // real content sniffing
        $this->post($url, ['judul_materi' => 'Palsu', 'file' => $png], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['file']);

        // neither a file nor a legacy key
        $this->post($url, ['judul_materi' => 'Kosong'], $h)->assertStatus(422)->assertJsonValidationErrors(['file']);

        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    public function test_legacy_key_string_still_accepted_but_not_into_structured_namespaces(): void
    {
        $section = BagianKelas::where('kelas_id', 'CLS_SO_B')->first();
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $url = '/api/sections/'.$section->bagian_id.'/materials';

        $this->postJson($url, ['judul_materi' => 'Lama', 'nama_file' => 'a.pdf', 'file_materi_key' => 'materials/legacy.pdf'])
            ->assertStatus(201)->assertJsonPath('material.file_materi_key', 'materials/legacy.pdf');

        $this->postJson($url, ['judul_materi' => 'Curang', 'file_materi_key' => 'submissions/1/5025251169/secret.zip'])
            ->assertStatus(422)->assertJsonValidationErrors(['file_materi_key']);
        $this->postJson($url, ['judul_materi' => 'Curang', 'file_materi_key' => '../../etc/passwd'])
            ->assertStatus(422)->assertJsonValidationErrors(['file_materi_key']);
    }

    public function test_student_submits_replaces_and_downloads_submission(): void
    {
        $task = TugasKelas::first(); // zip,pdf
        $student = $this->student();
        Sanctum::actingAs($student);
        $h = ['Accept' => 'application/json'];

        $first = $this->post('/api/tasks/'.$task->tugas_id.'/submit', [
            'file' => UploadedFile::fake()->create('jawaban.zip', 200, 'application/zip'),
            'komentar' => 'v1',
        ], $h)->assertStatus(200);

        $key1 = $first->json('submission.file_jawaban_key');
        $this->assertStringStartsWith('submissions/'.$task->tugas_id.'/'.$student->mahasiswa->nrp.'/', $key1);
        Storage::disk('s3')->assertExists($key1);
        $first->assertJsonPath('submission.terlambat', false);

        // replacement deletes the old object
        $second = $this->post('/api/tasks/'.$task->tugas_id.'/submit', [
            'file' => $this->pdf('laporan.pdf'),
        ], $h)->assertStatus(200);
        $key2 = $second->json('submission.file_jawaban_key');
        $this->assertNotSame($key1, $key2);
        Storage::disk('s3')->assertMissing($key1);
        Storage::disk('s3')->assertExists($key2);
        $this->assertSame(1, PengumpulanTugas::where('tugas_id', $task->tugas_id)->where('nrp', $student->mahasiswa->nrp)->count());

        $id = $second->json('submission.submit_id');
        $this->getJson('/api/files/submissions/'.$id)->assertStatus(200)->assertJsonStructure(['url']);

        // task view shows file_url on my submission
        $this->getJson('/api/tasks/'.$task->tugas_id)->assertJsonPath('my_submission.file_url', fn ($v) => str_contains((string) $v, $key2));

        // other student cannot download; dosen of class and admin can; other dosen cannot
        Sanctum::actingAs($this->outsider());
        $this->getJson('/api/files/submissions/'.$id)->assertStatus(403);
        Sanctum::actingAs($this->otherDosen());
        $this->getJson('/api/files/submissions/'.$id)->assertStatus(403);
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $this->getJson('/api/files/submissions/'.$id)->assertStatus(200);
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $this->getJson('/api/files/submissions/'.$id)->assertStatus(200);
    }

    public function test_submission_rules_format_membership_and_late_flag(): void
    {
        $task = TugasKelas::first();
        $h = ['Accept' => 'application/json'];

        // outsider (not in KRS of the class) cannot submit
        Sanctum::actingAs($this->outsider());
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', ['file' => $this->pdf()], $h)->assertStatus(403);
        $this->getJson('/api/tasks/'.$task->tugas_id)->assertStatus(403);

        Sanctum::actingAs($this->student());

        // format restricted to the task's format_file (zip,pdf)
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', [
            'file' => UploadedFile::fake()->create('x.png', 10, 'image/png'),
        ], $h)->assertStatus(422)->assertJsonValidationErrors(['file']);

        // too big
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', ['file' => $this->pdf('b.pdf', 10241)], $h)
            ->assertStatus(422);

        // past deadline -> flagged late (still accepted)
        $task->update(['tenggat_waktu' => now()->subDays(2)]);
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', ['file' => $this->pdf('telat.pdf')], $h)
            ->assertStatus(200)->assertJsonPath('submission.terlambat', true);

        // date-only deadline today is still on time
        $task->update(['tenggat_waktu' => now()->startOfDay()]);
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', ['file' => $this->pdf('ontime.pdf')], $h)
            ->assertStatus(200)->assertJsonPath('submission.terlambat', false);

        // dosen cannot submit
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $this->post('/api/tasks/'.$task->tugas_id.'/submit', ['file' => $this->pdf()], $h)->assertStatus(403);
    }

    public function test_library_upload_download_replace_and_delete(): void
    {
        $h = ['Accept' => 'application/json'];
        Sanctum::actingAs($this->student());
        $this->post('/api/library', ['judul_buku' => 'x', 'penulis' => 'y', 'kategori' => 'z', 'file' => $this->pdf()], $h)->assertStatus(403);

        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $res = $this->post('/api/library', [
            'judul_buku' => 'Buku Nyata', 'penulis' => 'Penulis', 'kategori' => 'Test', 'tahun' => 2024,
            'file' => $this->pdf('Buku Nyata.pdf', 300),
        ], $h)->assertStatus(201);

        $id = $res->json('book.buku_id');
        $key = $res->json('book.file_dokumen_key');
        $this->assertStringStartsWith('library/'.$id.'/', $key);
        Storage::disk('s3')->assertExists($key);
        $this->assertNotEmpty($res->json('book.file_url'));

        // any authenticated user (student) can fetch the link
        Sanctum::actingAs($this->student());
        $this->getJson('/api/files/library/'.$id)->assertStatus(200)->assertJsonStructure(['url']);
        $this->getJson('/api/library')->assertJsonStructure(['books' => ['data' => [['file_url']]]]);

        // admin replaces the file (multipart POST) -> old object removed
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $upd = $this->post('/api/admin/library/'.$id, ['judul_buku' => 'Buku Baru', 'file' => $this->pdf('v2.pdf')], $h)->assertStatus(200);
        $key2 = $upd->json('book.file_dokumen_key');
        $this->assertNotSame($key, $key2);
        Storage::disk('s3')->assertMissing($key);
        Storage::disk('s3')->assertExists($key2);

        // delete removes the object (dosen route)
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $this->deleteJson('/api/library/'.$id)->assertStatus(200);
        Storage::disk('s3')->assertMissing($key2);

        // admin delete route too
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $id2 = $this->post('/api/library', ['judul_buku' => 'B2', 'penulis' => 'p', 'kategori' => 'k', 'file' => $this->pdf()], $h)->json('book.buku_id');
        $k = Perpustakaan::find($id2)->file_dokumen_key;
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $this->deleteJson('/api/admin/library/'.$id2)->assertStatus(200);
        Storage::disk('s3')->assertMissing($k);
    }

    public function test_library_validation(): void
    {
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $h = ['Accept' => 'application/json'];

        $this->post('/api/library', ['judul_buku' => 'x', 'penulis' => 'y', 'kategori' => 'z', 'file' => UploadedFile::fake()->create('a.exe', 5)], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->post('/api/library', ['judul_buku' => 'x', 'penulis' => 'y', 'kategori' => 'z', 'file' => $this->pdf('big.pdf', 10241)], $h)
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_report_attachment_owner_and_admin_only(): void
    {
        $h = ['Accept' => 'application/json'];
        $student = $this->student();
        Sanctum::actingAs($student);

        $res = $this->post('/api/reports', [
            'kategori_modul' => 'Classroom', 'judul_laporan' => 'Error', 'deskripsi_kendala' => 'Tidak bisa upload',
            'lampiran' => $this->png('bukti.png'),
        ], $h)->assertStatus(201);

        $id = $res->json('report.tiket_id');
        $key = $res->json('report.lampiran_bukti_key');
        $this->assertStringStartsWith('reports/'.$id.'/', $key);
        Storage::disk('s3')->assertExists($key);
        $this->assertNotEmpty($res->json('report.file_url'));

        $this->getJson('/api/files/reports/'.$id)->assertStatus(200)->assertJsonPath('nama_file', 'bukti.png');

        Sanctum::actingAs($this->outsider());
        $this->getJson('/api/files/reports/'.$id)->assertStatus(403);
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $this->getJson('/api/files/reports/'.$id)->assertStatus(403);
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $this->getJson('/api/files/reports/'.$id)->assertStatus(200);

        // too large / wrong type
        Sanctum::actingAs($student);
        $this->post('/api/reports', ['kategori_modul' => 'a', 'judul_laporan' => 'b', 'deskripsi_kendala' => 'c',
            'lampiran' => UploadedFile::fake()->create('x.pdf', 5121, 'application/pdf')], $h)->assertStatus(422);
        $this->post('/api/reports', ['kategori_modul' => 'a', 'judul_laporan' => 'b', 'deskripsi_kendala' => 'c',
            'lampiran' => UploadedFile::fake()->create('x.exe', 5)], $h)->assertStatus(422);
    }

    public function test_avatar_upload_replace_and_fetch(): void
    {
        $h = ['Accept' => 'application/json'];
        $student = $this->student();
        Sanctum::actingAs($student);

        $res = $this->post('/api/profile', ['nomor_telepon' => '0811', 'foto' => $this->png('Me.PNG')], $h)
            ->assertStatus(200);
        $key = $res->json('profile.foto_profil_key');
        $this->assertStringStartsWith('avatars/'.$student->user_id.'/', $key);
        Storage::disk('s3')->assertExists($key);
        $this->assertStringContainsString($key, $res->json('profile.foto_url'));
        $this->getJson('/api/profile')->assertJsonPath('profile.foto_url', fn ($v) => str_contains((string) $v, $key));

        // replacing deletes the old avatar
        $res2 = $this->post('/api/profile', ['foto' => $this->png('b.png')], $h)->assertStatus(200);
        $key2 = $res2->json('profile.foto_profil_key');
        Storage::disk('s3')->assertMissing($key);
        Storage::disk('s3')->assertExists($key2);

        // any authenticated user can fetch an avatar
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $this->getJson('/api/files/avatars/'.$student->user_id)->assertStatus(200)->assertJsonStructure(['url']);

        // size (2 MB) and type
        Sanctum::actingAs($student);
        $this->post('/api/profile', ['foto' => UploadedFile::fake()->create('big.png', 2049, 'image/png')], $h)->assertStatus(422);
        $this->post('/api/profile', ['foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], $h)->assertStatus(422);

        // unauthenticated
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/files/avatars/'.$student->user_id)->assertStatus(401);
    }

    public function test_unknown_kind_and_missing_object(): void
    {
        Sanctum::actingAs(User::where('role', 'admin')->first());
        $this->getJson('/api/files/secrets/1')->assertStatus(404);

        // seeded materials have fake keys without a real object
        $m = MateriKelas::first();
        $this->getJson('/api/files/materials/'.$m->materi_id)->assertStatus(404)->assertJsonPath('message', 'File belum tersedia.');
    }

    public function test_local_fallback_when_s3_not_configured(): void
    {
        config(['filesystems.disks.s3.bucket' => null]);
        Storage::fake('local');

        $section = BagianKelas::where('kelas_id', 'CLS_SO_B')->first();
        Sanctum::actingAs(User::where('role', 'dosen')->first());
        $res = $this->post('/api/sections/'.$section->bagian_id.'/materials', [
            'judul_materi' => 'Lokal', 'file' => $this->pdf('lokal.pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $key = $res->json('material.file_materi_key');
        Storage::disk('local')->assertExists($key);
        $this->assertStringContainsString('/files/local?', $res->json('material.file_url'));

        // signed url streams the file; tampering is rejected
        $url = $this->getJson('/api/files/materials/'.$res->json('material.materi_id'))->assertStatus(200)->json('url');
        $this->get($url)->assertStatus(200)->assertDownload('lokal.pdf');
        $this->get($url.'x')->assertStatus(403);
    }
}
