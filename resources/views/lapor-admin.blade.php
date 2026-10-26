<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.seo-head', [
        'title' => 'Lapor Admin',
        'description' => 'Layanan bantuan teknis dan pelaporan kendala perkuliahan myITS Classroom Institut Teknologi Sepuluh Nopember.',
    ])
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
</head>
<body>
    
    <my-topbar></my-topbar>

    <div class="layout-container">
        
        <!-- Sidebar diset aktif pada 'admin' -->
        <my-sidebar active="admin"></my-sidebar>

        <main class="main-content">
            <div class="view-section">
                
                <div class="page-header">
                    <h2>Lapor Kendala</h2>
                    <p>Laporkan masalah yang Anda temui di portal mahasiswa.</p>
                </div>

                <div class="report-layout">
                    <!-- KOLOM KIRI: FORM LAPORAN -->
                    <div class="report-form-section">
                        <div class="report-card">
                            <h3 class="section-title">Detail Laporan</h3>
                            
                            <form id="reportForm" novalidate>
                                <!-- Custom Dropdown Kategori -->
                                <div class="form-group">
                                    <label>Kategori Kendala</label>
                                    <div class="custom-select-wrapper" id="kategoriSelect">
                                        <div class="custom-select-trigger" tabindex="0">
                                            <span id="kategoriText">KRS</span>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2" class="icon-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </div>
                                        <!-- Daftar Pilihan Dropdown -->
                                        <div class="custom-select-options d-none">
                                            <div class="option-item">Portal Mahasiswa</div>
                                            <div class="option-item">Classroom</div>
                                            <div class="option-item">Perpustakaan</div>
                                            <div class="option-item active">KRS</div>
                                            <div class="option-item">Profil dan Akun</div>
                                            <div class="option-item">Lainnya</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="reportTitle">Subjek Laporan</label>
                                    <input type="text" id="reportTitle" placeholder="Contoh: Halaman KRS tidak menampilkan mata kuliah" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label for="reportDescription">Deskripsi Kendala</label>
                                    <textarea id="reportDescription" class="form-textarea" rows="4" placeholder="Jelaskan detail kendala yang Anda alami..." required></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="reportFile">Lampiran (Opsional)</label>
                                    <input type="file" id="reportFile" accept=".pdf,.jpg,.jpeg,.png,.zip" style="display:none;">
                                    <div class="dropzone" id="reportDropzone" tabindex="0">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        <span id="reportFileLabel">Klik atau seret file ke sini (PDF, JPG, PNG)</span>
                                    </div>
                                </div>

                                <p class="classroom-feedback" id="reportFeedback" role="status" aria-live="polite"></p>

                                <button type="submit" id="btnSubmitReport" class="btn-primary" style="width: auto; padding: 12px 24px;">Kirim Laporan</button>
                            </form>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: RIWAYAT LAPORAN -->
                    <div class="report-history-section">
                        <div class="report-card">
                            <div class="history-header">
                                <h3 class="section-title">Riwayat Laporan</h3>
                                <!--a href="#" class="link-blue" id="btnLihatSemua">Buka Semua</a-->
                            </div>

                            <div class="history-list" id="reportHistoryList">
                                <div style="padding: 16px; color: #64748B; font-size: 13px;">Memuat riwayat laporan...</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/lapor-admin.js"></script>
</body>
</html>
