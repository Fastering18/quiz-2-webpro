<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Administrasi - myITS Classroom</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
    <style>
        .admin-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .admin-stat-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .admin-stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .admin-stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #0F172A;
            line-height: 1.2;
        }
        .admin-stat-sub {
            font-size: 11px;
            color: #94A3B8;
        }
        .admin-filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .admin-filter-bar .search-bar {
            flex: 1;
            min-width: 240px;
            margin: 0;
        }
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
            padding: 12px 16px;
            border-bottom: 1px solid #E2E8F0;
            white-space: nowrap;
        }
        .admin-data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9;
            color: #1E293B;
            vertical-align: middle;
        }
        .admin-data-table tr:hover {
            background: #F8FAFC;
        }
        .action-btn-group {
            display: flex;
            gap: 6px;
            align-items: center;
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
        .btn-action-secondary {
            background: #F1F5F9;
            color: #334155;
            border-color: #CBD5E1;
        }
        .btn-action-secondary:hover { background: #E2E8F0; }
        .btn-action-danger {
            background: #FEF2F2;
            color: #B42318;
            border-color: #FECACA;
        }
        .btn-action-danger:hover { background: #FEE2E2; }
        .btn-action-success {
            background: #F0FDF4;
            color: #166534;
            border-color: #BBF7D0;
        }
        .btn-action-success:hover { background: #DCFCE7; }
    </style>
</head>
<body>
    <my-topbar></my-topbar>

    <div class="layout-container">
        <my-sidebar active="admin-panel"></my-sidebar>

        <main class="main-content">
            <div class="view-section">

                <!-- Page Header -->
                <div class="page-header-row">
                    <div class="header-text-group">
                        <h2>Panel Administrasi</h2>
                        <p>Pusat kendali operasional akademik: Kelola akun pengguna, mata kuliah, kelas perkuliahan, dan status KRS.</p>
                    </div>
                    <div class="header-actions">
                        <span class="status-badge badge-success" id="adminRoleBadge">Mode Administrator</span>
                    </div>
                </div>

                <!-- Stats Counters Grid -->
                <div class="admin-stat-grid" id="statsGrid">
                    <div class="admin-stat-card">
                        <span class="admin-stat-label">Total Pengguna</span>
                        <span class="admin-stat-value" id="statTotalUsers">-</span>
                        <span class="admin-stat-sub" id="statUserBreakdown">Memuat data...</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-label">Mata Kuliah</span>
                        <span class="admin-stat-value" id="statTotalMk">-</span>
                        <span class="admin-stat-sub">Kurikulum aktif</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-label">Kelas Terbuka</span>
                        <span class="admin-stat-value" id="statTotalKelas">-</span>
                        <span class="admin-stat-sub">Semester berjalan</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-label">KRS Menunggu</span>
                        <span class="admin-stat-value" id="statPendingKrs" style="color:#B45309;">-</span>
                        <span class="admin-stat-sub" id="statTotalKrs">Menunggu persetujuan</span>
                    </div>
                    <div class="admin-stat-card">
                        <span class="admin-stat-label">Laporan / Tiket</span>
                        <span class="admin-stat-value" id="statOpenTickets" style="color:#B42318;">-</span>
                        <span class="admin-stat-sub" id="statTotalTickets">Tiket terbuka</span>
                    </div>
                </div>

                <!-- Admin Tabs -->
                <div class="classroom-tabs" role="tablist" style="margin-bottom:20px;">
                    <button class="classroom-tab is-active" id="tabUsers" type="button" data-tab="users">Pengguna (Users)</button>
                    <button class="classroom-tab" id="tabMataKuliah" type="button" data-tab="mk">Mata Kuliah</button>
                    <button class="classroom-tab" id="tabKelas" type="button" data-tab="kelas">Kelas Perkuliahan</button>
                    <button class="classroom-tab" id="tabKrs" type="button" data-tab="krs">Kelola KRS</button>
                </div>

                <!-- ========================================================
                     TAB 1: KELOLA PENGGUNA (USERS)
                     ======================================================== -->
                <section id="panelUsers" class="admin-tab-panel">
                    <div class="admin-filter-bar">
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="searchUserInput" placeholder="Cari username, nama, atau email...">
                        </div>
                        <select id="filterUserRole" class="form-input" style="width:160px; padding:10px 14px;">
                            <option value="">Semua Peran</option>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button class="btn-primary-small" id="btnOpenModalAddUser" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            + Tambah Pengguna
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Peran</th>
                                    <th>Identitas / Departemen</th>
                                    <th>Email</th>
                                    <th style="text-align:right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data pengguna...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrapper" id="usersPagination" style="margin-top:16px;"></div>
                </section>

                <!-- ========================================================
                     TAB 2: MATA KULIAH
                     ======================================================== -->
                <section id="panelMk" class="admin-tab-panel d-none">
                    <div class="admin-filter-bar">
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="searchMkInput" placeholder="Cari kode atau nama mata kuliah...">
                        </div>
                        <button class="btn-primary-small" id="btnOpenModalAddMk" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            + Tambah Mata Kuliah
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Semester Paket</th>
                                    <th>Kelas Aktif</th>
                                    <th style="text-align:right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="mkTableBody">
                                <tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data mata kuliah...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrapper" id="mkPagination" style="margin-top:16px;"></div>
                </section>

                <!-- ========================================================
                     TAB 3: KELAS PERKULIAHAN
                     ======================================================== -->
                <section id="panelKelas" class="admin-tab-panel d-none">
                    <div class="admin-filter-bar">
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="searchKelasInput" placeholder="Cari id kelas atau nama kelas...">
                        </div>
                        <button class="btn-primary-small" id="btnOpenModalAddKelas" type="button">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            + Buat Kelas Baru
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>ID Kelas</th>
                                    <th>Mata Kuliah</th>
                                    <th>Nama Kelas</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Semester/Tahun</th>
                                    <th>Peserta / Kuota</th>
                                    <th style="text-align:right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="kelasTableBody">
                                <tr><td colspan="7" style="text-align:center; padding:24px; color:#64748B;">Memuat kelas perkuliahan...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrapper" id="kelasPagination" style="margin-top:16px;"></div>
                </section>

                <!-- ========================================================
                     TAB 4: KELOLA KRS
                     ======================================================== -->
                <section id="panelKrs" class="admin-tab-panel d-none">
                    <div class="admin-filter-bar">
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="searchKrsInput" placeholder="Cari NRP mahasiswa...">
                        </div>
                        <select id="filterKrsStatus" class="form-input" style="width:160px; padding:10px 14px;">
                            <option value="">Semua Status</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-data-table">
                            <thead>
                                <tr>
                                    <th>ID KRS</th>
                                    <th>Mahasiswa (NRP & Nama)</th>
                                    <th>Semester / Tahun</th>
                                    <th>Mata Kuliah Terdaftar</th>
                                    <th>Status Persetujuan</th>
                                    <th style="text-align:right;">Ubah Status</th>
                                </tr>
                            </thead>
                            <tbody id="krsTableBody">
                                <tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data KRS...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-wrapper" id="krsPagination" style="margin-top:16px;"></div>
                </section>

            </div>
        </main>
    </div>

    <!-- ========================================================
         MODAL: TAMBAH PENGGUNA BARU
    ======================================================== -->
    <div class="modal-overlay" id="modalUser" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:560px;">
            <div class="modal-header">
                <h3>Tambah Pengguna Baru</h3>
                <button class="modal-close" data-close-modal="modalUser" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formUser" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Peran (Role) *</label>
                        <select id="inputUserRole" class="form-input" required>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Username *</label>
                            <input type="text" id="inputUserUsername" class="form-input" placeholder="cth: 5025251199" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Email *</label>
                            <input type="email" id="inputUserEmail" class="form-input" placeholder="cth: nama@its.ac.id" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Nama Lengkap *</label>
                        <input type="text" id="inputUserNama" class="form-input" placeholder="cth: Muhammad Fajar" required>
                    </div>

                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Password Awal (Opsional, bawaan: 'admin')</label>
                        <input type="text" id="inputUserPassword" class="form-input" placeholder="Kosongkan untuk otomatis 'admin'">
                    </div>

                    <!-- Role-specific fields -->
                    <div id="fieldsMahasiswa">
                        <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">NRP *</label>
                                <input type="text" id="inputMhsNrp" class="form-input" placeholder="cth: 5025251199">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Dosen Wali *</label>
                                <select id="inputMhsWali" class="form-input"></select>
                            </div>
                        </div>
                        <div class="form-grid" style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Departemen *</label>
                                <input type="text" id="inputMhsDept" class="form-input" value="Informatika">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Semester Aktif</label>
                                <input type="number" id="inputMhsSem" class="form-input" value="1" min="1" max="14">
                            </div>
                        </div>
                    </div>

                    <div id="fieldsDosen" class="d-none">
                        <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">NIP *</label>
                                <input type="text" id="inputDosenNip" class="form-input" placeholder="cth: 198501012010121009">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Departemen *</label>
                                <input type="text" id="inputDosenDept" class="form-input" value="Informatika">
                            </div>
                        </div>
                    </div>

                    <p class="classroom-feedback" id="feedbackUser" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalUser">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitUser">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: RESET PASSWORD
    ======================================================== -->
    <div class="modal-overlay" id="modalResetPassword" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:440px;">
            <div class="modal-header">
                <h3>Reset Password Pengguna</h3>
                <button class="modal-close" data-close-modal="modalResetPassword" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formResetPassword" novalidate>
                <input type="hidden" id="resetUserId">
                <div class="modal-body">
                    <p style="font-size:13px; color:#475569; margin-bottom:12px;">
                        Reset password untuk akun: <strong id="resetUserName">-</strong>
                    </p>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Password Baru</label>
                        <input type="text" id="inputNewPassword" class="form-input" placeholder="Masukkan password baru..." value="admin">
                    </div>
                    <p class="classroom-feedback" id="feedbackReset" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalResetPassword">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitReset">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: TAMBAH MATA KULIAH
    ======================================================== -->
    <div class="modal-overlay" id="modalMk" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:480px;">
            <div class="modal-header">
                <h3>Tambah Mata Kuliah</h3>
                <button class="modal-close" data-close-modal="modalMk" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formMk" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Kode MK *</label>
                        <input type="text" id="inputMkKode" class="form-input" placeholder="cth: IF234101" required>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Nama Mata Kuliah *</label>
                        <input type="text" id="inputMkNama" class="form-input" placeholder="cth: Rekayasa Perangkat Lunak" required>
                    </div>
                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Jumlah SKS *</label>
                            <input type="number" id="inputMkSks" class="form-input" value="3" min="1" max="8" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Semester Paket *</label>
                            <input type="number" id="inputMkSemester" class="form-input" value="3" min="1" max="14" required>
                        </div>
                    </div>
                    <p class="classroom-feedback" id="feedbackMk" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalMk">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitMk">Simpan Mata Kuliah</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: TAMBAH KELAS
    ======================================================== -->
    <div class="modal-overlay" id="modalKelas" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:520px;">
            <div class="modal-header">
                <h3>Buat Kelas Perkuliahan</h3>
                <button class="modal-close" data-close-modal="modalKelas" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formKelas" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">ID Kelas (Maks. 12 Karakter) *</label>
                        <input type="text" id="inputKelasId" class="form-input" placeholder="cth: KLS_RPL_A" maxlength="12" required>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Mata Kuliah *</label>
                        <select id="selectKelasMk" class="form-input" required></select>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Dosen Pengampu *</label>
                        <select id="selectKelasDosen" class="form-input" required></select>
                    </div>
                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Nama Kelas *</label>
                            <input type="text" id="inputKelasNama" class="form-input" placeholder="cth: Kelas A" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Kuota Kelas *</label>
                            <input type="number" id="inputKelasKuota" class="form-input" value="40" min="1" max="1000" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Semester / Tahun *</label>
                        <input type="text" id="inputKelasTahun" class="form-input" value="2026/Gasal" required>
                    </div>
                    <p class="classroom-feedback" id="feedbackKelas" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalKelas">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitKelas">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/admin-panel.js"></script>
</body>
</html>
