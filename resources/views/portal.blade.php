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
                        <span class="banner-subtitle" id="banner-dept">Departemen Informatika</span>
                        <h2 class="banner-title">Selamat datang kembali<br><span id="banner-name">Mahasiswa</span>!</h2>
                        <p class="banner-desc">Lanjutkan belajarmu. Cek tugas dan tonton kembali rekaman kelas di sini.</p>
                    </div>
                    <div class="banner-badge" id="banner-semester">Semester Aktif</div>
                </div>

                <!-- Stats Row -->
                <div class="stats-row">
                    <div class="stat-card">
                        <span class="stat-label">SKS Aktif</span>
                        <span class="stat-value" id="stat-sks-aktif">-</span>
                        <span class="stat-desc text-info" id="stat-sks-total">- Total SKS</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Tugas Terkini</span>
                        <span class="stat-value" id="stat-tugas-count">-</span>
                        <span class="stat-desc text-danger" id="stat-tugas-desc">Tugas belum selesai</span>
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