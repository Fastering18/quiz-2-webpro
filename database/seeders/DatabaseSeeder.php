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
use App\Services\FileStorage;
use App\Services\SimplePdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Demo dataset that exercises every feature. All demo accounts use the password "password".
 *
 *   admin                  Administrator
 *   198501012010121001     Dr. Sarwosri (dosen, teaches SO/PBO/BD, wali of 3 students)
 *   197803152005011002     Prof. Bambang Nugroho (dosen)
 *   198208202008012003     Dr. Rina Kartika (dosen)
 *   5025251131 ...         8 mahasiswa (username = NRP), see README
 *
 * Placeholder PDFs/TXTs are uploaded to the configured disk (S3 or local) unless SEED_FILES=false or the
 * storage is unavailable; then the file keys stay null (seeding never fails because of storage).
 */
class DatabaseSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private ?string $disk = null;

    public function run(): void
    {
        $this->prepareStorage();

        DB::transaction(function () {
            $this->seed();
        });
    }

    private function seed(): void
    {
        // Idempotent: when the demo data already exists only make sure the admin account is there.
        if (Dosen::where('nip', '198501012010121001')->exists() || Dosen::where('nip', '1011011')->exists()) {
            $this->ensureAdmin();

            return;
        }

        $admin = $this->ensureAdmin();
        $dosen = $this->seedDosen();
        $mhs = $this->seedMahasiswa();
        $this->seedMataKuliah();
        $this->seedClasses();
        $this->seedKrs();
        $this->seedContent();
        $this->seedLibrary($admin, $dosen['d1']);
        $this->seedReports($admin, $dosen['d3']);
    }

    // ------------------------------------------------------------------ people

    /** @return array<string, User> */
    private function seedDosen(): array
    {
        $rows = [
            'd1' => ['198501012010121001', 'dsn_1_sarwosri', 'Dr. Sarwosri, S.Kom., M.T.', 'sarwosri@its.ac.id', 'Teknik Informatika', '081200000001', 'Keputih, Sukolilo, Surabaya'],
            'd2' => ['197803152005011002', 'dsn_2_bambang', 'Prof. Dr. Ir. Bambang Nugroho, M.Sc.', 'bambang@its.ac.id', 'Teknik Informatika', '081200000002', 'Gebang Putih, Sukolilo, Surabaya'],
            'd3' => ['198208202008012003', 'dsn_3_rina', 'Dr. Rina Kartika, S.T., M.Sc.', 'rina.kartika@its.ac.id', 'Sistem Informasi', '081200000003', 'Manyar, Surabaya'],
        ];

        $out = [];
        foreach ($rows as $key => [$nip, $uid, $nama, $email, $dept, $tel, $alamat]) {
            $user = User::create([
                'user_id' => $uid, 'username' => $nip, 'nama_lengkap' => $nama, 'email' => $email,
                'password_hash' => self::PASSWORD, 'role' => 'dosen', 'nomor_telepon' => $tel, 'alamat_rumah' => $alamat,
            ]);
            Dosen::create(['nip' => $nip, 'user_id' => $uid, 'departemen' => $dept, 'status' => 'Aktif']);
            $out[$key] = $user;
        }

        return $out;
    }

    /** @return array<string, User> */
    private function seedMahasiswa(): array
    {
        $wali = ['d1' => '198501012010121001', 'd2' => '197803152005011002', 'd3' => '198208202008012003'];
        $rows = [
            // nrp, nama, email, wali, semester, telepon, alamat
            ['5025251131', 'Muhammad Azka Asyrafany', '5025251131@student.its.ac.id', 'd1', 3, '081234567131', 'Gebang Putih, Sukolilo, Surabaya'],
            ['5025251169', 'Kevin Lontong', 'lontong@student.its.ac.id', 'd1', 3, '081234567169', 'Keputih Tegal, Surabaya'],
            ['5025251132', 'Siti Nurhaliza', '5025251132@student.its.ac.id', 'd1', 3, '081234567132', 'Mulyorejo, Surabaya'],
            ['5025251140', 'Budi Santoso', '5025251140@student.its.ac.id', 'd2', 3, '081234567140', 'Rungkut, Surabaya'],
            ['5025251155', 'Dewi Lestari', '5025251155@student.its.ac.id', 'd2', 3, '081234567155', 'Gubeng, Surabaya'],
            ['5025251172', 'Rizky Pratama', '5025251172@student.its.ac.id', 'd2', 3, '081234567172', 'Wonokromo, Surabaya'],
            ['5025251180', 'Anisa Putri', '5025251180@student.its.ac.id', 'd3', 3, '081234567180', 'Sidoarjo'],
            ['5025251195', 'Fajar Nugroho', '5025251195@student.its.ac.id', 'd3', 3, '081234567195', 'Gresik'],
        ];

        $out = [];
        foreach ($rows as [$nrp, $nama, $email, $w, $sem, $tel, $alamat]) {
            $user = User::create([
                'user_id' => 'mhs_'.$nrp, 'username' => $nrp, 'nama_lengkap' => $nama, 'email' => $email,
                'password_hash' => self::PASSWORD, 'role' => 'mahasiswa', 'nomor_telepon' => $tel, 'alamat_rumah' => $alamat,
            ]);
            Mahasiswa::create([
                'nrp' => $nrp, 'user_id' => $user->user_id, 'nip_wali' => $wali[$w],
                'departemen' => 'Teknik Informatika', 'semester_aktif' => $sem, 'status' => 'Aktif',
            ]);
            $out[$nrp] = $user;
        }

        return $out;
    }

    private function ensureAdmin(): User
    {
        return User::firstOrCreate(['user_id' => 'admin'], [
            'username' => 'admin',
            'nama_lengkap' => 'Administrator Akademik',
            'email' => 'admin@its.ac.id',
            'password_hash' => self::PASSWORD,
            'role' => 'admin',
            'nomor_telepon' => '089988776655',
            'alamat_rumah' => 'Gedung Rektorat ITS Surabaya',
        ]);
    }

    // ------------------------------------------------------------------ academic structure

    private function seedMataKuliah(): void
    {
        foreach ([
            ['IF234301', 'Sistem Operasi', 3, 3],
            ['IF234302', 'Pemrograman Berorientasi Objek', 3, 3],
            ['IF234303', 'Basis Data', 4, 3],
            ['IF234304', 'Jaringan Komputer', 3, 3],
            ['IF234101', 'Dasar Pemrograman', 4, 1],
            ['IF234305', 'Struktur Data dan Algoritma', 3, 3],
            ['IF234306', 'Pemrograman Web', 3, 3],
            ['IF234307', 'Kecerdasan Buatan', 3, 5],
        ] as [$kode, $nama, $sks, $sem]) {
            MataKuliah::create(['kode_mk' => $kode, 'nama_mk' => $nama, 'sks' => $sks, 'semester' => $sem]);
        }
    }

    private function seedClasses(): void
    {
        foreach ([
            // kelas_id (<= 12 chars), kode_mk, nip, nama, semester_tahun, kuota
            ['CLS_SO_B', 'IF234301', '198501012010121001', 'Sistem Operasi (B)', '2026/Ganjil', 40],
            ['CLS_PBO_A', 'IF234302', '198501012010121001', 'Pemrograman Berorientasi Objek (A)', '2026/Ganjil', 40],
            ['CLS_BD_C', 'IF234303', '198501012010121001', 'Basis Data (C)', '2026/Ganjil', 45],
            ['CLS_JK_A', 'IF234304', '197803152005011002', 'Jaringan Komputer (A)', '2026/Ganjil', 40],
            ['CLS_SDA_A', 'IF234305', '198208202008012003', 'Struktur Data dan Algoritma (A)', '2026/Ganjil', 40],
            ['CLS_PW_B', 'IF234306', '198208202008012003', 'Pemrograman Web (B)', '2026/Ganjil', 35],
            ['CLS_KB_A', 'IF234307', '197803152005011002', 'Kecerdasan Buatan (A)', '2026/Ganjil', 30],
            ['CLS_DP_A', 'IF234101', '197803152005011002', 'Dasar Pemrograman (A)', '2025/Genap', 40], // past semester
        ] as [$id, $kode, $nip, $nama, $sem, $kuota]) {
            Classroom::create([
                'kelas_id' => $id, 'kode_mk' => $kode, 'nip' => $nip, 'nama_kelas' => $nama,
                'semester_tahun' => $sem, 'kuota_kelas' => $kuota,
            ]);
        }
    }

    private function seedKrs(): void
    {
        $current = ['semester' => 3, 'semester_tahun' => '2026/Ganjil'];
        $plans = [
            // nrp => [status, class ids]
            '5025251131' => ['Disetujui', ['CLS_SO_B', 'CLS_PBO_A', 'CLS_BD_C', 'CLS_JK_A']],
            '5025251169' => ['Menunggu', ['CLS_SO_B', 'CLS_PBO_A', 'CLS_BD_C']],
            '5025251132' => ['Disetujui', ['CLS_SO_B', 'CLS_PBO_A', 'CLS_SDA_A']],
            '5025251140' => ['Disetujui', ['CLS_SO_B', 'CLS_JK_A', 'CLS_PW_B']],
            '5025251155' => ['Menunggu', ['CLS_BD_C', 'CLS_JK_A', 'CLS_SDA_A']],
            '5025251172' => ['Ditolak', ['CLS_SO_B', 'CLS_PBO_A', 'CLS_BD_C', 'CLS_JK_A', 'CLS_SDA_A', 'CLS_PW_B', 'CLS_KB_A']],
            '5025251180' => ['Disetujui', ['CLS_PBO_A', 'CLS_SDA_A', 'CLS_PW_B']],
            '5025251195' => ['Menunggu', ['CLS_SO_B', 'CLS_PW_B']],
        ];

        foreach ($plans as $nrp => [$status, $classes]) {
            $krs = Krs::create([
                'krs_id' => 'KRS_2026G_'.$nrp, 'nrp' => $nrp, ...$current, 'status_acc' => $status,
                'tanggal_cetak' => $status === 'Disetujui' ? now()->subDays(40)->toDateString() : null,
            ]);
            foreach ($classes as $kelas) {
                DetailKrs::create(['krs_id' => $krs->krs_id, 'kelas_id' => $kelas, 'nilai_akhir' => null]);
            }
        }

        // Past semester with final grades (Azka)
        $past = Krs::create([
            'krs_id' => 'KRS_2025G_5025251131', 'nrp' => '5025251131', 'semester' => 2, 'semester_tahun' => '2025/Genap',
            'status_acc' => 'Disetujui', 'tanggal_cetak' => '2026-02-15',
        ]);
        DetailKrs::create(['krs_id' => $past->krs_id, 'kelas_id' => 'CLS_DP_A', 'nilai_akhir' => 90]);
    }

    // ------------------------------------------------------------------ class content

    private function seedContent(): void
    {
        $day = fn (int $n) => now()->addDays($n);

        // announcements (some classes have several, one has none)
        foreach ([
            ['CLS_SO_B', 'Selamat datang di perkuliahan Sistem Operasi Kelas B. Silakan unduh silabus dan kontrak kuliah di bagian Materi.', -50],
            ['CLS_SO_B', 'Kuliah minggu depan diadakan secara tatap muka di Lab Komputer 2 pukul 07.30 WIB.', -6],
            ['CLS_SO_B', 'Batas pengumpulan Tugas 2.2 diundur satu hari karena libur nasional.', -1],
            ['CLS_PBO_A', 'Praktikum PBO dimulai pekan depan. Siapkan JDK 21 dan IDE pilihan Anda.', -20],
            ['CLS_BD_C', 'Kuis 1 Basis Data akan dilaksanakan Jumat pukul 10.00 di ruang kelas.', -4],
            ['CLS_JK_A', 'Materi subnetting tersedia di bagian Materi Kuliah.', -9],
            ['CLS_SDA_A', 'Asisten dosen akan membuka sesi konsultasi setiap Rabu pukul 15.00.', -12],
            ['CLS_PW_B', 'Proyek akhir dikerjakan berkelompok (3 orang). Daftarkan kelompok paling lambat pekan depan.', -3],
        ] as [$kelas, $konten, $offset]) {
            PengumumanClassroom::create(['kelas_id' => $kelas, 'konten' => $konten, 'tanggal_unggah' => $day($offset)->toDateString()]);
        }

        // sections: [kelas, urutan, judul, ringkasan-judul, ringkasan]
        $sections = [];
        foreach ([
            ['CLS_SO_B', 1, 'Pengantar Sistem Operasi', 'Konsep Dasar Kernel dan OS', 'Membahas arsitektur sistem operasi, perbedaan antarmuka pengguna, syscall, serta struktur monolitik vs microkernel.'],
            ['CLS_SO_B', 2, 'Manajemen Proses', 'Memahami Penjadwalan Proses', 'Penjadwalan proses adalah algoritma yang membagi waktu CPU antar proses yang sedang berjalan. Algoritma umum: FCFS, SJF, Priority Scheduling, dan Round Robin (RR).'],
            ['CLS_SO_B', 3, 'Manajemen Memori', 'Paging dan Segmentasi', 'Membahas alokasi memori, paging, segmentasi, dan memori virtual.'],
            ['CLS_SO_B', 4, 'Sistem Berkas', 'File System', 'Membahas struktur direktori, alokasi blok, dan journaling.'],
            ['CLS_PBO_A', 1, 'Konsep Dasar PBO', 'Class dan Object', 'Enkapsulasi, pewarisan, dan polimorfisme.'],
            ['CLS_PBO_A', 2, 'Desain Berorientasi Objek', 'UML dan Design Pattern', 'Diagram kelas dan pola desain dasar.'],
            ['CLS_BD_C', 1, 'Model Relasional', 'ERD dan Normalisasi', 'Dari ERD ke skema relasional hingga bentuk normal ketiga.'],
            ['CLS_BD_C', 2, 'SQL Lanjutan', 'Join dan Subquery', 'Latihan query dengan join, agregasi, dan subquery.'],
            ['CLS_JK_A', 1, 'Model OSI dan TCP/IP', 'Lapisan Jaringan', 'Perbandingan model OSI dan TCP/IP.'],
            ['CLS_SDA_A', 1, 'Struktur Data Linear', 'Array, Linked List, Stack, Queue', 'Implementasi dan analisis kompleksitas.'],
            ['CLS_PW_B', 1, 'HTML, CSS, dan JavaScript', 'Dasar Web', 'Dasar pengembangan antarmuka web.'],
            ['CLS_DP_A', 1, 'Pengantar Pemrograman', 'Variabel dan Kontrol Alur', 'Dasar pemrograman dengan bahasa C.'],
        ] as [$kelas, $urutan, $judul, $rj, $rk]) {
            $sections[$kelas.'#'.$urutan] = BagianKelas::create([
                'kelas_id' => $kelas, 'urutan' => $urutan, 'judul_bagian' => $judul, 'judul_ringkasan' => $rj, 'konten_ringkasan' => $rk,
            ]);
        }

        // materials: [section, judul, nama_file, 'pdf'|'txt']
        foreach ([
            ['CLS_SO_B#1', 'Slide 01 - Pengantar Sistem Operasi', 'Pengantar_Sistem_Operasi.pdf', 'pdf'],
            ['CLS_SO_B#2', 'Slide 02 - Penjadwalan Proses', 'Penjadwalan_Proses.pdf', 'pdf'],
            ['CLS_SO_B#2', 'Slide 03 - Round Robin', 'Round_Robin.pdf', 'pdf'],
            ['CLS_SO_B#2', 'Catatan Proses dan Thread', 'Catatan_Proses_Thread.txt', 'txt'],
            ['CLS_SO_B#3', 'Slide 04 - Manajemen Memori', 'Manajemen_Memori.pdf', 'pdf'],
            ['CLS_PBO_A#1', 'Modul 1 - Class dan Object', 'Modul_PBO_1.pdf', 'pdf'],
            ['CLS_BD_C#1', 'Slide ERD dan Normalisasi', 'ERD_Normalisasi.pdf', 'pdf'],
            ['CLS_JK_A#1', 'Slide Model OSI', 'Model_OSI.pdf', 'pdf'],
            ['CLS_SDA_A#1', 'Slide Struktur Data Linear', 'Struktur_Data_Linear.pdf', 'pdf'],
            ['CLS_PW_B#1', 'Panduan HTML dan CSS', 'Panduan_HTML_CSS.pdf', 'pdf'],
        ] as [$sec, $judul, $nama, $type]) {
            $section = $sections[$sec];
            [$key, $size] = $this->putFile("materials/{$section->kelas_id}", $nama, $judul, $type);
            MateriKelas::create([
                'bagian_id' => $section->bagian_id, 'judul_materi' => $judul, 'nama_file' => $nama,
                'file_materi_key' => $key, 'ukuran_file' => $size ?? 1_200_000, 'tanggal_unggah' => $day(-30)->toDateString(),
            ]);
        }

        // tasks: [section, urutan, judul, deskripsi, format, deadline offset days]
        $tasks = [];
        foreach ([
            ['CLS_SO_B#1', 1, 'Tugas 1: Analisis CPU Scheduling', 'Buat simulasi perbandingan FCFS, SJF, dan Round Robin dalam bahasa C.', 'zip,pdf,c', -20],
            ['CLS_SO_B#2', 1, 'Penjadwalan Proses', 'Implementasikan algoritma penjadwalan Round Robin (RR) dasar dalam bahasa C. Program menerima jumlah proses, waktu burst, dan kuanta waktu sebagai input, lalu menampilkan waktu turnaround dan waktu tunggu setiap proses.', 'c,zip', -3],
            ['CLS_SO_B#2', 2, 'Penjadwalan Prioritas', 'Implementasikan priority scheduling (preemptive) dan bandingkan dengan RR.', 'c,zip', 5],
            ['CLS_SO_B#2', 3, 'Perbandingan SJF dan FCFS', 'Tulis laporan singkat perbandingan SJF dan FCFS beserta contoh perhitungan.', 'pdf', 12],
            ['CLS_PBO_A#1', 1, 'Tugas 1: Kelas dan Objek', 'Rancang kelas Mahasiswa dan MataKuliah dengan enkapsulasi yang benar.', 'java,zip', -8],
            ['CLS_PBO_A#2', 1, 'Tugas 2: Diagram Kelas', 'Gambarkan diagram kelas untuk sistem perpustakaan.', 'pdf,png', 9],
            ['CLS_BD_C#1', 1, 'Tugas 1: ERD Toko Online', 'Buat ERD dan skema relasional dalam 3NF.', 'pdf', -2],
            ['CLS_BD_C#2', 1, 'Tugas 2: Query SQL', 'Kerjakan 10 soal query SQL terlampir.', 'sql,pdf', 14],
            ['CLS_JK_A#1', 1, 'Tugas 1: Subnetting', 'Selesaikan soal subnetting VLSM.', 'pdf', 6],
            ['CLS_SDA_A#1', 1, 'Tugas 1: Linked List', 'Implementasikan singly linked list lengkap dengan pengujian.', 'c,cpp,zip', -5],
            ['CLS_PW_B#1', 1, 'Tugas 1: Halaman Profil', 'Buat halaman profil responsif dengan HTML dan CSS.', 'zip', 3],
        ] as [$sec, $urutan, $judul, $desk, $fmt, $off]) {
            $section = $sections[$sec];
            $tasks[$sec.'/'.$urutan] = TugasKelas::create([
                'bagian_id' => $section->bagian_id, 'urutan' => $urutan, 'judul_tugas' => $judul, 'deskripsi_tugas' => $desk,
                'format_file' => $fmt, 'tenggat_waktu' => $day($off)->setTime(23, 59),
            ]);
        }

        // submissions: [task, nrp, nilai|null, komentar, hours before deadline sent (negative = late), file name]
        foreach ([
            ['CLS_SO_B#1/1', '5025251131', 95, 'Laporan dan source code terlampir dalam file zip.', 50, 'tugas1_5025251131.zip'],
            ['CLS_SO_B#1/1', '5025251132', 88, null, 20, 'tugas1_5025251132.zip'],
            ['CLS_SO_B#1/1', '5025251140', 72, 'Mohon maaf terlambat.', -30, 'tugas1_5025251140.zip'], // late + graded
            ['CLS_SO_B#2/1', '5025251131', null, 'Terlambat karena kendala jaringan kampus.', -26, 'rr_5025251131.zip'], // late, not graded
            ['CLS_SO_B#2/1', '5025251132', null, null, 30, 'rr_5025251132.zip'], // on time, not graded
            ['CLS_PBO_A#1/1', '5025251132', 90, 'Sudah memakai enkapsulasi penuh.', 40, 'tugas1_pbo_5025251132.zip'],
            ['CLS_PBO_A#1/1', '5025251180', null, null, 15, 'tugas1_pbo_5025251180.zip'],
            ['CLS_BD_C#1/1', '5025251169', 80, null, 10, 'erd_5025251169.pdf'],
            ['CLS_SDA_A#1/1', '5025251180', 85, null, 60, 'linkedlist_5025251180.zip'],
        ] as [$task, $nrp, $nilai, $komentar, $hoursBefore, $nama]) {
            $t = $tasks[$task];
            $deadline = $t->tenggat_waktu;
            $sent = $deadline->copy()->subHours($hoursBefore);
            [$key, $size] = $this->putFile("submissions/{$t->tugas_id}/{$nrp}", $nama, "Jawaban {$nrp}", str_ends_with($nama, '.pdf') ? 'pdf' : 'txt');
            PengumpulanTugas::create([
                'tugas_id' => $t->tugas_id, 'nrp' => $nrp, 'nama_file' => $nama, 'file_jawaban_key' => $key,
                'ukuran_file' => $size ?? 900_000, 'komentar' => $komentar, 'tanggal_kirim' => $sent,
                'nilai_tugas' => $nilai, 'terlambat' => $hoursBefore < 0,
            ]);
        }
    }

    // ------------------------------------------------------------------ library + reports

    private function seedLibrary(User $admin, User $dosen): void
    {
        foreach ([
            ['BK_001', 'Operating System Concepts 10th Edition', 'Abraham Silberschatz, Peter B. Galvin, Greg Gagne', $admin, 2018, 'Sistem Komputer'],
            ['BK_002', 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', $admin, 2008, 'Software Engineering'],
            ['BK_003', 'Database System Concepts 7th Edition', 'Abraham Silberschatz, Henry F. Korth, S. Sudarshan', $admin, 2019, 'Basis Data'],
            ['BK_004', 'Introduction to Algorithms', 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein', $admin, 2022, 'Algoritma'],
            ['BK_005', 'The Pragmatic Programmer: From Journeyman to Master', 'Andrew Hunt, David Thomas', $dosen, 1999, 'Software Engineering'],
            ['BK_006', 'Design Patterns: Elements of Reusable Object-Oriented Software', 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides', $dosen, 1994, 'Software Engineering'],
            ['BK_007', 'Head First Design Patterns', 'Eric Freeman, Elisabeth Robson', $dosen, 2020, 'Software Engineering'],
            ['BK_008', 'Computer Networking: A Top-Down Approach', 'James F. Kurose, Keith W. Ross', $admin, 2021, 'Jaringan'],
            ['BK_009', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell, Peter Norvig', $admin, 2020, 'Kecerdasan Buatan'],
        ] as [$id, $judul, $penulis, $publisher, $tahun, $kategori]) {
            $nama = $id.'_'.preg_replace('/[^a-z0-9]+/i', '_', substr($judul, 0, 30)).'.pdf';
            [$key, $size] = $this->putFile("library/{$id}", $nama, $judul, 'pdf');
            Perpustakaan::create([
                'buku_id' => $id, 'judul_buku' => $judul, 'penulis' => $penulis, 'publisher_id' => $publisher->user_id,
                'tahun' => $tahun, 'kategori' => $kategori, 'nama_file' => $nama, 'file_dokumen_key' => $key,
                'ukuran_file' => $size ?? 5_000_000,
            ]);
        }
    }

    private function seedReports(User $admin, User $dosen): void
    {
        // id, user, kategori, judul, deskripsi, status, days ago, answer
        foreach ([
            ['TKT_20261022_A001', 'mhs_5025251131', 'KRS', 'PDF KRS tidak dapat diunduh', 'Tombol Unduh PDF pada halaman KRS tidak menghasilkan berkas apa pun.', 'Diproses', 0, 'Terima kasih, laporan sedang kami periksa bersama tim pengembang.'],
            ['TKT_20261021_A002', 'mhs_5025251131', 'Classroom', 'Rekaman kelas tidak dapat diputar', 'Rekaman pertemuan 2 Sistem Operasi gagal dimuat.', 'Terkirim', 1, null],
            ['TKT_20261018_A003', 'mhs_5025251131', 'Perpustakaan', 'Materi kuliah gagal diunduh', 'Berkas materi pertemuan 1 tidak dapat diunduh dari perpustakaan.', 'Selesai', 8, 'Sudah diperbaiki. Silakan coba unduh kembali.'],
            ['TKT_20261020_B001', 'mhs_5025251169', 'KRS & Jadwal', 'Permohonan penggantian jadwal bentrok', 'Jadwal kelas Basis Data bentrok dengan praktikum Sistem Operasi pada hari Rabu.', 'Diproses', 6, 'Laporan sedang ditinjau bersama koordinator prodi.'],
            ['TKT_20261019_C001', 'mhs_5025251172', 'KRS', 'KRS saya ditolak', 'Mohon penjelasan mengapa KRS saya berstatus Ditolak.', 'Ditolak', 7, 'Jumlah SKS melebihi batas semester ini. Silakan hubungi dosen wali Anda.'],
            ['TKT_20261023_D001', 'mhs_5025251155', 'Profil dan Akun', 'Tidak bisa mengganti foto profil', 'Unggah foto profil selalu gagal dengan pesan ukuran terlalu besar.', 'Terkirim', 3, null],
            ['TKT_20261024_E001', $dosen->user_id, 'Classroom', 'Nilai tugas tidak tersimpan', 'Setelah menekan simpan nilai, nilai kembali kosong.', 'Terkirim', 2, null],
        ] as [$id, $uid, $kat, $judul, $desk, $status, $ago, $answer]) {
            LaporanAdmin::create([
                'tiket_id' => $id, 'user_id' => $uid, 'kategori_modul' => $kat, 'judul_laporan' => $judul, 'deskripsi_kendala' => $desk,
                'lampiran_nama_file' => null, 'lampiran_bukti_key' => null, 'status_tiket' => $status,
                'tanggal_lapor' => now()->subDays($ago + 1), 'admin_id' => $answer ? $admin->user_id : null,
                'tanggapan_admin' => $answer, 'tanggal_tanggapan' => $answer ? now()->subDays($ago) : null,
            ]);
        }
    }

    // ------------------------------------------------------------------ storage helpers

    private function prepareStorage(): void
    {
        $this->disk = null;

        if (filter_var(env('SEED_FILES', true), FILTER_VALIDATE_BOOLEAN) === false || app()->runningUnitTests()) {
            return;
        }

        try {
            $disk = app(FileStorage::class)->disk();
            // probe: write + delete a tiny object; any failure disables file seeding
            // (disks use throw=false, so a failed write returns false instead of throwing)
            if (Storage::disk($disk)->put('seed/.probe', 'ok') !== true) {
                throw new \RuntimeException('probe write failed');
            }
            Storage::disk($disk)->delete('seed/.probe');
            $this->disk = $disk;
        } catch (\Throwable $e) {
            Log::warning('Seeder: storage unavailable, seeding without files: '.$e->getMessage());
        }
    }

    /**
     * Upload a tiny placeholder document.
     *
     * @return array{0: ?string, 1: ?int} [object key, size] or [null, null] when storage is unavailable
     */
    private function putFile(string $prefix, string $name, string $title, string $type): array
    {
        if ($this->disk === null) {
            return [null, null];
        }

        try {
            if ($type === 'pdf') {
                $pdf = new SimplePdf;
                $pdf->text(60, 80, $title, 16, true);
                $pdf->text(60, 110, 'Dokumen contoh (data demo) - Portal Quiz 2 WebPro', 10);
                $content = $pdf->output();
            } else {
                $content = $title."\n\nBerkas contoh (data demo) - Portal Quiz 2 WebPro\n";
            }
            $key = trim($prefix, '/').'/seed-'.preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
            if (Storage::disk($this->disk)->put($key, $content) !== true) {
                throw new \RuntimeException('write failed');
            }

            return [$key, strlen($content)];
        } catch (\Throwable $e) {
            Log::warning('Seeder: could not store '.$name.': '.$e->getMessage());
            $this->disk = null; // stop trying after the first failure

            return [null, null];
        }
    }
}
