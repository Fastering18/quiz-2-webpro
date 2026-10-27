<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.seo-head', [
        'title' => 'Portal',
        'description' => 'Dashboard ringkasan akademik, informasi SKS aktif, kelas diampu, dan status penugasan mahasiswa & dosen myITS Classroom.',
    ])
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <!-- Memuat Komponen UI -->
    <script src="/components.js"></script>
</head>
<body>
    
    <!-- Memanggil Top Bar -->
    <my-topbar></my-topbar>

    <div class="layout-container">
        
        <!-- Memanggil Side Bar dengan penanda 'portal' yang aktif -->
        <my-sidebar active="portal"></my-sidebar>

        <main class="main-content">
            <div class="view-section">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="banner-content">
                        <span class="banner-subtitle" id="banner-dept">{{ $authUser->mahasiswa?->departemen ?? $authUser->dosen?->departemen ?? 'Institut Teknologi Sepuluh Nopember' }}</span>
                        <h2 class="banner-title">Selamat datang kembali<br><span id="banner-name">{{ $authUser->nama_lengkap }}</span>!</h2>
                        <p class="banner-desc">Lanjutkan belajarmu. Cek tugas dan tonton kembali rekaman kelas di sini.</p>
                    </div>
                    <div class="banner-badge" id="banner-semester">Semester Aktif</div>
                </div>

                <!-- Stats Row -->
                @php
                    $summary = $initialData['summary'] ?? null;
                    if (is_array($summary)) {
                        $statOneLabel = 'SKS Aktif';
                        $statOne = $summary['total_sks'] ?? 0;
                        $statOneDesc = $statOne.' Total SKS Terdaftar';
                        $statTwoLabel = 'Tugas Terkini';
                        $statTwo = $summary['tugas_belum_selesai'] ?? 0;
                        $statTwoDesc = ((int) $statTwo) > 0 ? $statTwo.' tugas belum dikerjakan' : 'Semua tugas telah diselesaikan';
                    } elseif (isset($initialData['total_kelas_ajar'])) {
                        $statOneLabel = 'Kelas Diampu';
                        $statOne = $initialData['total_kelas_ajar'];
                        $statOneDesc = 'Semester Aktif';
                        $statTwoLabel = 'Mata Kuliah Aktif';
                        $statTwo = count($initialData['classrooms'] ?? []);
                        $statTwoDesc = 'Terjadwal semester ini';
                    } else {
                        $statOneLabel = 'Pengguna';
                        $statOne = $initialData['stats']['pengguna']['total'] ?? '-';
                        $statOneDesc = 'Akun terdaftar';
                        $statTwoLabel = 'Tiket Terbuka';
                        $statTwo = $initialData['stats']['tiket']['terbuka'] ?? '-';
                        $statTwoDesc = 'Perlu ditinjau';
                    }
                @endphp
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">{{ $statOneLabel }}</span>
                        <span class="stat-value" id="stat-sks-aktif">{{ $statOne }}</span>
                        <span class="stat-desc text-info" id="stat-sks-total">{{ $statOneDesc }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{ $statTwoLabel }}</span>
                        <span class="stat-value" id="stat-tugas-count">{{ $statTwo }}</span>
                        <span class="stat-desc text-danger" id="stat-tugas-desc">{{ $statTwoDesc }}</span>
                    </div>
                </div>

                <!-- Quick Access -->
                <div class="quick-access-card">
                    <h3>Akses Cepat</h3>
                    <div class="quick-links">
                        <a href="{{ route('classroom') }}" class="quick-btn" style="text-decoration:none; color:inherit; text-align:center; display:inline-flex; align-items:center; justify-content:center;">Classroom</a>
                        <a href="{{ route('library') }}" class="quick-btn" style="text-decoration:none; color:inherit; text-align:center; display:inline-flex; align-items:center; justify-content:center;">Library</a>
                        <a href="{{ route('krs') }}" class="quick-btn" style="text-decoration:none; color:inherit; text-align:center; display:inline-flex; align-items:center; justify-content:center;">KRS</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/portal.js"></script>
</body>
</html>