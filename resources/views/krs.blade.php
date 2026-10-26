<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KRS - myITS Classroom</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
</head>
<body>
    
    <my-topbar></my-topbar>

    <div class="layout-container">
        
        <!-- Sidebar diset aktif pada 'krs' -->
        <my-sidebar active="krs"></my-sidebar>

        <main class="main-content">
            <div class="view-section">
                
                <!-- Page Header & Actions -->
                <div class="page-header-row">
                    <div class="header-text-group">
                        <h2>Pratinjau Kartu Rencana Studi</h2>
                        <p>Preview dokumen siap cetak untuk semester aktif.</p>
                    </div>
                    <div class="header-actions">
                        <span class="badge-semester" id="krs-badge-semester">Semester Aktif</span>
                        <button class="btn-secondary-icon" id="btnCetakKrs" onclick="window.print()">
                            <!-- Icon Cetak -->
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            Cetak
                        </button>
                        <button class="btn-primary-small" id="btnUnduhKrs" type="button">
                            <!-- Icon Download -->
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Unduh PDF
                        </button>
                    </div>
                </div>

                <!-- KRS Preview Card -->
                <div class="krs-preview-card">
                    
                    <!-- Header Info Mahasiswa -->
                    <div class="krs-student-info">
                        <div class="krs-card-title">
                            <h3>Kartu Rencana Studi (KRS)</h3>
                            <p>Preview untuk evaluasi sebelum dicetak atau diunduh sebagai PDF.</p>
                        </div>
                        <span class="badge-preview" id="krs-status-badge">Disetujui</span>
                    </div>

                    <hr class="divider">

                    <!-- Data Mahasiswa (Siap API) -->
                    <div class="info-grid-4">
                        <div class="info-item">
                            <label>Nama Mahasiswa</label>
                            <span id="krs-nama">Memuat...</span>
                        </div>
                        <div class="info-item">
                            <label>NIM</label>
                            <span id="krs-nim">-</span>
                        </div>
                        <div class="info-item">
                            <label>Jurusan</label>
                            <span id="krs-jurusan">-</span>
                        </div>
                        <div class="info-item">
                            <label>Semester</label>
                            <span id="krs-semester">-</span>
                        </div>
                    </div>

                    <!-- Tabel Mata Kuliah -->
                    <div class="krs-table-section">
                        <h4>Daftar Mata Kuliah</h4>
                        
                        <div class="krs-table">
                            <!-- Header Tabel -->
                            <div class="krs-table-header">
                                <div class="col-kode">Kode MK</div>
                                <div class="col-nama">Nama Mata Kuliah</div>
                                <div class="col-kelas">Kelas</div>
                                <div class="col-sks">SKS</div>
                            </div>
                            
                            <!-- Container Baris Dinamis -->
                            <div id="krsTableRows">
                                <div style="padding: 16px; color: #64748B; font-size: 13px;">Memuat data KRS...</div>
                            </div>
                        </div>

                        <div class="krs-table-footer">
                            <span>Total SKS terdaftar</span>
                            <span id="krs-total-sks">0 SKS</span>
                        </div>
                    </div>

                    <hr class="divider">

                    <!-- Kolom Tanda Tangan -->
                    <div class="signature-section">
                        <div class="signature-box">
                            <label id="krs-sig-mhs-label">Mahasiswa</label>
                            <div class="signature-space"></div>
                        </div>
                        <div class="signature-box">
                            <label id="krs-sig-wali-label">Dosen Wali / Pembimbing Akademik</label>
                            <div class="signature-space"></div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/krs.js"></script>
</body>
</html>
