<?php

namespace Tests\Feature;

use App\Models\Krs;
use App\Models\PengumpulanTugas;
use App\Models\TugasKelas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_login_and_access_me(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => '5025251131',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['user_id', 'username', 'nama_lengkap', 'role'],
            ]);

        $token = $response->json('access_token');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('user.username', '5025251131')
            ->assertJsonPath('user.role', 'mahasiswa');
    }

    public function test_student_can_fetch_dashboard(): void
    {
        $student = User::where('username', '5025251131')->first();
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['nama_lengkap', 'nrp', 'departemen', 'semester_aktif', 'status', 'dosen_wali'],
                'summary' => ['total_sks', 'total_kelas', 'tugas_belum_selesai'],
                'enrolled_classrooms',
                'upcoming_tasks',
                'announcements',
            ])
            ->assertJsonPath('user.nrp', '5025251131')
            ->assertJsonPath('summary.total_sks', 10);
    }

    public function test_student_can_view_enrolled_classrooms_and_details(): void
    {
        $student = User::where('username', '5025251131')->first();
        Sanctum::actingAs($student);

        $listResponse = $this->getJson('/api/classrooms');
        $listResponse->assertStatus(200)
            ->assertJsonStructure(['classrooms']);

        $detailResponse = $this->getJson('/api/classrooms/CLS_SO_B');
        $detailResponse->assertStatus(200)
            ->assertJsonPath('classroom.kelas_id', 'CLS_SO_B')
            ->assertJsonPath('classroom.nama_kelas', 'Sistem Operasi (B)')
            ->assertJsonStructure([
                'classroom' => [
                    'mata_kuliah',
                    'dosen',
                    'pengumuman',
                    'bagian_kelas',
                ],
            ]);
    }

    public function test_student_can_view_and_submit_task(): void
    {
        $student = User::where('username', '5025251131')->first();
        Sanctum::actingAs($student);

        $task = TugasKelas::first();

        $showResponse = $this->getJson('/api/tasks/'.$task->tugas_id);
        $showResponse->assertStatus(200)
            ->assertJsonPath('task.tugas_id', $task->tugas_id);

        $submitResponse = $this->postJson('/api/tasks/'.$task->tugas_id.'/submit', [
            'nama_file' => 'revisi_tugas.zip',
            'file_jawaban_key' => 'submissions/test_revisi.zip',
            'ukuran_file' => 2048000,
            'komentar' => 'Revisi tugas bab 1',
        ]);

        $submitResponse->assertStatus(200)
            ->assertJsonPath('submission.nama_file', 'revisi_tugas.zip');

        $this->assertDatabaseHas('pengumpulan_tugas', [
            'tugas_id' => $task->tugas_id,
            'nama_file' => 'revisi_tugas.zip',
        ]);
    }

    public function test_dosen_can_grade_submission(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        Sanctum::actingAs($dosen);

        $submission = PengumpulanTugas::first();

        $response = $this->postJson('/api/submissions/'.$submission->submit_id.'/grade', [
            'nilai_tugas' => 98,
            'komentar' => 'Sangat bagus dan komprehensif.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('submission.nilai_tugas', 98);

        $this->assertDatabaseHas('pengumpulan_tugas', [
            'submit_id' => $submission->submit_id,
            'nilai_tugas' => 98,
        ]);
    }

    public function test_student_can_view_and_submit_krs(): void
    {
        $student = User::where('username', '5025251131')->first();
        Sanctum::actingAs($student);

        $indexResponse = $this->getJson('/api/krs');
        $indexResponse->assertStatus(200)
            ->assertJsonStructure(['krs_list']);

        $krs = Krs::first();
        $showResponse = $this->getJson('/api/krs/'.$krs->krs_id);
        $showResponse->assertStatus(200)
            ->assertJsonPath('krs.krs_id', $krs->krs_id)
            ->assertJsonPath('total_sks', 10);

        // Submit next semester KRS
        $submitResponse = $this->postJson('/api/krs', [
            'semester' => 4,
            'semester_tahun' => '2025/Genap',
            'kelas_ids' => ['CLS_SO_B', 'CLS_PBO_A'],
        ]);

        $submitResponse->assertStatus(201)
            ->assertJsonPath('krs.status_acc', 'Menunggu');
    }

    public function test_dosen_can_approve_krs(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        Sanctum::actingAs($dosen);

        $krs = Krs::first();

        $response = $this->patchJson('/api/krs/'.$krs->krs_id.'/approve', [
            'status_acc' => 'Disetujui',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('krs.status_acc', 'Disetujui');

        $this->assertDatabaseHas('krs', [
            'krs_id' => $krs->krs_id,
            'status_acc' => 'Disetujui',
        ]);
    }
}
