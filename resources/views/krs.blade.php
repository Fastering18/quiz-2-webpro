<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.seo-head', [
        'title' => 'Kartu Rencana Studi (KRS)',
        'description' => 'Penyusunan Kartu Rencana Studi, pemilihan mata kuliah semester aktif, dan persetujuan perwalian Dosen Wali.',
    ])
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
    <style>
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            background: white;
        }
        .admin-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .admin-data-table th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }
        .admin-data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #F1F5F9;
            color: #1E293B;
            vertical-align: middle;
        }
        .admin-data-table tr:hover {
            background: #F8FAFC;
        }
        .btn-action-small {
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .btn-action-secondary { background: #F1F5F9; color: #334155; border-color: #CBD5E1; }
        .btn-action-secondary:hover { background: #E2E8F0; }
        .btn-action-danger { background: #FEF2F2; color: #B42318; border-color: #FECACA; }
        .btn-action-danger:hover { background: #FEE2E2; }
        .btn-action-success { background: #F0FDF4; color: #166534; border-color: #BBF7D0; }
        .btn-action-success:hover { background: #DCFCE7; }
        .classroom-checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .classroom-checkbox-item:hover {
            background: #F1F5F9;
        }
        .classroom-checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
    </style>
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
                        <h2 id="pageTitleText">Kartu Rencana Studi</h2>
                        <p id="pageSubTitleText">Preview dokumen dan pengelolaan rencana studi semester aktif.</p>
                    </div>
                    <div class="header-actions">
                        <span class="badge-semester" id="krs-badge-semester">Semester Aktif</span>
                        <button class="btn-primary-small d-none" id="btnOpenModalAjukanKrs" type="button" style="background-color:#166534;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            + Ajukan / Ubah KRS
                        </button>
                        <button class="btn-secondary-icon" id="btnCetakKrs" onclick="window.print()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            Cetak
                        </button>
                        <button class="btn-primary-small" id="btnUnduhKrs" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Unduh PDF
                        </button>
                    </div>
                </div>

                <!-- SECTION KHUSUS DOSEN: DAFTAR MAHASISWA PERWALIAN & APPROVAL -->
                <div class="krs-preview-card d-none" id="perwalianSection" style="margin-bottom: 24px;">
                    <div class="krs-student-info">
                        <div class="krs-card-title">
                            <h3>Daftar Mahasiswa Bimbingan & Persetujuan KRS</h3>
                            <p>Tinjau rencana studi mahasiswa bimbingan Anda, periksa total SKS, dan berikan persetujuan.</p>
                        </div>
                        <span class="status-badge badge-warning" id="perwalianSummaryBadge">Memuat mahasiswa...</span>
                    </div>

                    <hr class="divider">

                    <div class="table-responsive" style="margin-top: 12px;">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>NRP</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Semester / Tahun</th>
                                    <th>Mata Kuliah / SKS</th>
                                    <th>Status ACC</th>
                                    <th style="text-align:right;">Aksi Persetujuan</th>
                                </tr>
                            </thead>
                            <tbody id="perwalianTableBody">
                                <tr><td colspan="6" style="text-align:center; padding:16px; color:#64748B;">Memuat daftar mahasiswa perwalian...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KRS Preview Card (Dokumen Cetak / Preview) -->
                <div class="krs-preview-card" id="krsPreviewCard">
                    
                    <!-- Header Info Mahasiswa -->
                    <div class="krs-student-info">
                        <div class="krs-card-title">
                            <h3 id="krsCardHeaderTitle">Kartu Rencana Studi (KRS)</h3>
                            <p id="krsCardHeaderSub">Preview untuk evaluasi sebelum dicetak atau diunduh sebagai PDF.</p>
                        </div>
                        <span class="badge-preview" id="krs-status-badge">Menunggu</span>
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
                        <h4>Daftar Mata Kuliah Terdaftar</h4>
                        
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

    <!-- ========================================================
         MODAL: AJUKAN / PERBARUI KRS (MAHASISWA)
    ======================================================== -->
    <div class="modal-overlay" id="modalAjukanKrs" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width: 720px;">
            <div class="modal-header">
                <h3>Pengisian / Perbaruan Kartu Rencana Studi (KRS)</h3>
                <button class="modal-close" data-close-modal="modalAjukanKrs" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formAjukanKrs" novalidate>
                <div class="modal-body">
                    <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:14px;">
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Semester *</label>
                            <input type="number" id="inputKrsSemester" class="form-input" min="1" max="14" value="3" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Semester / Tahun Ajaran *</label>
                            <input type="text" id="inputKrsSemesterTahun" class="form-input" value="2026/Gasal" required>
                        </div>
                    </div>

                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:8px;">Pilih Kelas Mata Kuliah yang Diambil *</label>
                    <div style="max-height: 280px; overflow-y: auto; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px; background: #F8FAFC;" id="krsAvailableClassrooms">
                        <div style="color: #64748B; padding: 12px; text-align: center;">Memuat daftar kelas perkuliahan...</div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding: 10px 14px; background: #F1F5F9; border-radius: 6px;">
                        <span style="font-size: 13px; font-weight: 600; color: #334155;">Total SKS Dipilih:</span>
                        <span style="font-size: 15px; font-weight: 700; color: #00337F;" id="krsSelectedSksCounter">0 SKS (Maks. 24 SKS)</span>
                    </div>

                    <p class="classroom-feedback" id="feedbackAjukanKrs" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalAjukanKrs">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitAjukanKrs">Ajukan KRS ke Dosen Wali</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/krs.js"></script>
</body>
</html>
