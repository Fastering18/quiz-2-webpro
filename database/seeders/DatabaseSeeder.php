<?php

namespace Database\Seeders;

use App\Models\BagianKelas;
use App\Models\Classroom;
use App\Models\DetailKrs;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\LaporanAdmin;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MateriKelas;
use App\Models\PengumpulanTugas;
use App\Models\PengumumanClassroom;
use App\Models\Perpustakaan;
use App\Models\TugasKelas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seed();
        });
    }

    private function seed(): void
    {
        // 1. Dosen User & Record
        $userDosen = User::create([
            'user_id' => 'id_dosen',
            'username' => 'dosen1',
            'nama_lengkap' => 'Bapak ibu dosen',
            'email' => 'dosen@its.ac.id',
            'password_hash' => Hash::make('dosen'),
            'role' => 'dosen',
            'foto_profil_key' => null,
            'nomor_telepon' => '0666666',
            'alamat_rumah' => 'Surabaya, Jawa Timur',
        ]);

        $dosen = Dosen::create([
            'nip' => '1011011',
            'user_id' => $userDosen->user_id,
            'departemen' => 'Teknik Informatika',
            'status' => 'Aktif',
        ]);

        // 2. Mahasiswa User & Record (From Figma Design: Muhammad Azka Asyrafany, 5025251131)
        $userMhs = User::create([
            'user_id' => 'id_mahasiswa',
            'username' => 'kevinlontong',
            'nama_lengkap' => 'Kevin Lontong',
            'email' => 'lontong@student.its.ac.id',
            'password_hash' => Hash::make('kevin'),
            'role' => 'mahasiswa',
            'foto_profil_key' => null,
            'nomor_telepon' => '081234567890',
            'alamat_rumah' => 'Gebang Putih, Sukolilo, Surabaya',
        ]);

        $mahasiswa = Mahasiswa::create([
            'nrp' => '5025251169',
            'user_id' => $userMhs->user_id,
            'nip_wali' => $dosen->nip,
            'departemen' => 'Teknik Informatika',
            'semester_aktif' => 3,
            'status' => 'Aktif',
        ]);

        // 3. Admin User
        $userAdmin = User::create([
            'user_id' => 'admin',
            'username' => 'admin',
            'nama_lengkap' => 'Administrator Akademik',
            'email' => 'admin@its.ac.id',
            'password_hash' => Hash::make('admin'),
            'role' => 'admin',
            'foto_profil_key' => null,
            'nomor_telepon' => '089988776655',
            'alamat_rumah' => 'Gedung Rektorat ITS Surabaya',
        ]);

        // 4. Mata Kuliah
        $mkList = [
            [
                'kode_mk' => 'IF234301',
                'nama_mk' => 'Sistem Operasi',
                'sks' => 3,
                'semester' => 3,
            ],
            [
                'kode_mk' => 'IF234302',
                'nama_mk' => 'Pemrograman Berorientasi Objek',
                'sks' => 3,
                'semester' => 3,
            ],
            [
                'kode_mk' => 'IF234303',
                'nama_mk' => 'Basis Data',
                'sks' => 4,
                'semester' => 3,
            ],
            [
                'kode_mk' => 'IF234304',
                'nama_mk' => 'Jaringan Komputer',
                'sks' => 3,
                'semester' => 3,
            ],
            [
                'kode_mk' => 'IF234101',
                'nama_mk' => 'Dasar Pemrograman',
                'sks' => 4,
                'semester' => 1,
            ],
        ];

        foreach ($mkList as $mk) {
            MataKuliah::create($mk);
        }

        // 5. Classroom
        $classroomSo = Classroom::create([
            'kelas_id' => 'CLS_SO_B',
            'kode_mk' => 'IF234301',
            'nip' => $dosen->nip,
            'nama_kelas' => 'Sistem Operasi (B)',
            'semester_tahun' => '2024/Ganjil',
            'kuota_kelas' => 40,
        ]);

        $classroomPbo = Classroom::create([
            'kelas_id' => 'CLS_PBO_A',
            'kode_mk' => 'IF234302',
            'nip' => $dosen->nip,
            'nama_kelas' => 'Pemrograman Berorientasi Objek (A)',
            'semester_tahun' => '2024/Ganjil',
            'kuota_kelas' => 40,
        ]);

        $classroomBd = Classroom::create([
            'kelas_id' => 'CLS_BD_C',
            'kode_mk' => 'IF234303',
            'nip' => $dosen->nip,
            'nama_kelas' => 'Basis Data (C)',
            'semester_tahun' => '2024/Ganjil',
            'kuota_kelas' => 45,
        ]);

        // 6. KRS & Detail KRS
        $krs = Krs::create([
            'krs_id' => 'KRS_2024G_5025251131',
            'nrp' => $mahasiswa->nrp,
            'semester' => 3,
            'semester_tahun' => '2024/Ganjil',
            'status_acc' => 'Disetujui',
            'tanggal_cetak' => '2024-09-01',
        ]);

        DetailKrs::create([
            'krs_id' => $krs->krs_id,
            'kelas_id' => $classroomSo->kelas_id,
            'nilai_akhir' => null,
        ]);

        DetailKrs::create([
            'krs_id' => $krs->krs_id,
            'kelas_id' => $classroomPbo->kelas_id,
            'nilai_akhir' => null,
        ]);

        DetailKrs::create([
            'krs_id' => $krs->krs_id,
            'kelas_id' => $classroomBd->kelas_id,
            'nilai_akhir' => null,
        ]);

        // 7. Pengumuman Classroom
        PengumumanClassroom::create([
            'kelas_id' => $classroomSo->kelas_id,
            'konten' => 'Selamat datang di perkuliahan Sistem Operasi Kelas B. Silakan unduh silabus dan kontrak kuliah di bagian Materi.',
            'tanggal_unggah' => '2024-09-02',
        ]);

        PengumumanClassroom::create([
            'kelas_id' => $classroomSo->kelas_id,
            'konten' => 'Kuliah minggu depan diadakan secara tatap muka di Lab Komputer 2 pukul 07.30 WIB.',
            'tanggal_unggah' => '2024-10-15',
        ]);

        // 8. Bagian Kelas (Sections)
        $bagian1 = BagianKelas::create([
            'kelas_id' => $classroomSo->kelas_id,
            'urutan' => 1,
            'judul_bagian' => 'Pertemuan 1: Pengantar Sistem Operasi',
            'judul_ringkasan' => 'Konsep Dasar Kernel dan OS',
            'konten_ringkasan' => 'Membahas arsitektur sistem operasi, perbedaan antarmuka pengguna, syscall, dan struktur monolitik vs microkernel.',
        ]);

        $bagian2 = BagianKelas::create([
            'kelas_id' => $classroomSo->kelas_id,
            'urutan' => 2,
            'judul_bagian' => 'Pertemuan 2: Proses dan Manajemen Thread',
            'judul_ringkasan' => 'Multithreading & Concurrency',
            'konten_ringkasan' => 'Membahas process control block (PCB), context switching, serta sinkronisasi thread menggunakan mutex.',
        ]);

        // 9. Materi Kelas
        MateriKelas::create([
            'bagian_id' => $bagian1->bagian_id,
            'judul_materi' => 'Slide 01 - Pengantar Sistem Operasi',
            'nama_file' => 'pengantar_sistem_operasi.pdf',
            'file_materi_key' => 'materials/pengantar_sistem_operasi.pdf',
            'ukuran_file' => 2450000,
            'tanggal_unggah' => '2024-09-02',
        ]);

        MateriKelas::create([
            'bagian_id' => $bagian2->bagian_id,
            'judul_materi' => 'Slide 02 - Process & Threads',
            'nama_file' => 'process_management.pdf',
            'file_materi_key' => 'materials/process_management.pdf',
            'ukuran_file' => 3120000,
            'tanggal_unggah' => '2024-09-09',
        ]);

        // 10. Tugas Kelas
        $tugas1 = TugasKelas::create([
            'bagian_id' => $bagian1->bagian_id,
            'urutan' => 1,
            'judul_tugas' => 'Tugas 1: Analisis CPU Scheduling Algorithm',
            'deskripsi_tugas' => 'Buatlah simulasi perbandingan algoritma penjadwalan CPU (FCFS, SJF, dan Round Robin) menggunakan bahasa C/C++.',
            'format_file' => 'zip,pdf',
            'tenggat_waktu' => now()->addDays(7),
        ]);

        $tugas2 = TugasKelas::create([
            'bagian_id' => $bagian2->bagian_id,
            'urutan' => 2,
            'judul_tugas' => 'Tugas 2: Implementasi Mutex Locking di POSIX Threads',
            'deskripsi_tugas' => 'Selesaikan masalah Dining Philosophers dengan menghindari kondisi race condition dan deadlock menggunakan pthread_mutex.',
            'format_file' => 'zip',
            'tenggat_waktu' => now()->addDays(14),
        ]);

        // 11. Pengumpulan Tugas (Submitted by Azka)
        PengumpulanTugas::create([
            'tugas_id' => $tugas1->tugas_id,
            'nrp' => $mahasiswa->nrp,
            'nama_file' => 'tugas1_5025251131.zip',
            'file_jawaban_key' => 'submissions/tugas1_5025251131.zip',
            'ukuran_file' => 1250000,
            'komentar' => 'Pengumpulan tugas selesai, laporan dan source code terlampir dalam file zip.',
            'tanggal_kirim' => now()->subDay(),
            'nilai_tugas' => 95,
        ]);

        // 12. Perpustakaan (Books / Documents)
        Perpustakaan::create([
            'buku_id' => 'BK_001',
            'judul_buku' => 'Operating System Concepts 10th Edition',
            'penulis' => 'Abraham Silberschatz, Peter B. Galvin, Greg Gagne',
            'publisher_id' => $userAdmin->user_id,
            'tahun' => 2018,
            'kategori' => 'Sistem Komputer',
            'nama_file' => 'silberschatz_os_10th.pdf',
            'file_dokumen_key' => 'library/silberschatz_os_10th.pdf',
            'ukuran_file' => 18400000,
        ]);

        Perpustakaan::create([
            'buku_id' => 'BK_002',
            'judul_buku' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'penulis' => 'Robert C. Martin',
            'publisher_id' => $userAdmin->user_id,
            'tahun' => 2008,
            'kategori' => 'Software Engineering',
            'nama_file' => 'clean_code_martin.pdf',
            'file_dokumen_key' => 'library/clean_code_martin.pdf',
            'ukuran_file' => 9500000,
        ]);

        Perpustakaan::create([
            'buku_id' => 'BK_003',
            'judul_buku' => 'Database System Concepts 7th Edition',
            'penulis' => 'Abraham Silberschatz, Henry F. Korth, S. Sudarshan',
            'publisher_id' => $userAdmin->user_id,
            'tahun' => 2019,
            'kategori' => 'Basis Data',
            'nama_file' => 'db_concepts_7th.pdf',
            'file_dokumen_key' => 'library/db_concepts_7th.pdf',
            'ukuran_file' => 21300000,
        ]);

        // 13. Laporan Admin
        LaporanAdmin::create([
            'tiket_id' => 'TKT_20241026_01',
            'user_id' => $userMhs->user_id,
            'kategori_modul' => 'KRS & Jadwal',
            'judul_laporan' => 'Permohonan Penggantian Jadwal Bentrok',
            'deskripsi_kendala' => 'Jadwal kelas Basis Data bentrok dengan praktikum Sistem Operasi pada hari Rabu.',
            'lampiran_nama_file' => 'screenshot_bentrok.png',
            'lampiran_bukti_key' => 'reports/screenshot_bentrok.png',
            'status_tiket' => 'Diproses',
            'tanggal_lapor' => now()->subDays(2),
            'admin_id' => $userAdmin->user_id,
            'tanggapan_admin' => 'Laporan sedang ditinjau dengan koordinator prodi.',
            'tanggal_tanggapan' => now()->subDay(),
        ]);
    }
}
