<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-role" content="{{ auth()->user()?->role ?? '' }}">
    <title>Classroom - myITS Classroom</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
    <style>
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
    </style>
</head>
<body>
    <my-topbar></my-topbar>

    <div class="layout-container classroom-layout">
        <my-sidebar active="classroom"></my-sidebar>

        <main class="main-content classroom-main-content">
            <div class="view-section classroom-page">

                <div class="page-header classroom-page-header">
                    <h2>Classroom</h2>
                    <p>Akses materi perkuliahan, kumpulkan atau periksa tugas, dan kelola aktivitas perkuliahan.</p>
                </div>

                <div class="classroom-workspace" id="classroomApp">

                    <!-- ===================================================
                         PANEL KIRI — Daftar Mata Kuliah
                         =================================================== -->
                    <aside class="course-panel" aria-label="Daftar mata kuliah">
                        <div class="course-panel-heading">
                            <span class="course-heading-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18">
                                    <path d="M3 5.5A2.5 2.5 0 0 1 5.5 3H21v16H5.5A2.5 2.5 0 0 0 3 21.5z"/>
                                    <path d="M3 5.5v16M7 7h10M7 11h10"/>
                                </svg>
                            </span>
                            <span>Daftar Mata Kuliah</span>
                        </div>

                        <!-- List mata kuliah dinamis via JS -->
                        <div class="course-list" id="courseList">
                            <div style="padding: 16px; color: #64748B; font-size: 13px;">Memuat mata kuliah...</div>
                        </div>

                        <!-- Tombol Dosen: Tambah Section/Pertemuan -->
                        <button class="btn-primary-small d-none" id="btnOpenModalSection" type="button" style="margin-top:14px; width:100%; font-size:12px; justify-content:center; padding:9px 12px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Tambah Pertemuan
                        </button>
                    </aside>

                    <!-- ===================================================
                         PANEL KANAN — Konten Aktif
                         =================================================== -->
                    <section class="classroom-content" aria-live="polite">

                        <!-- =====================================================
                             VIEW A: MATERI & TUGAS
                             ===================================================== -->
                        <div id="courseView">

                            <!-- Header Pelajaran — judul + tombol Megaphone -->
                            <header class="classroom-lesson-header">
                                <div>
                                    <p class="classroom-eyebrow" id="lessonEyebrow">Bagian 1</p>
                                    <h1 id="lessonTitle">Mata Kuliah</h1>
                                    <p class="classroom-course-meta" id="lessonMeta">
                                        Memuat informasi kelas...
                                    </p>
                                </div>

                                <!-- Tombol Pengumuman -->
                                <button class="btn-announcement" id="btnOpenAnnouncement"
                                    type="button" aria-label="Lihat pengumuman kelas">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                                    </svg>
                                    Pengumuman
                                    <span class="announcement-badge" id="announcementBadge"></span>
                                </button>
                            </header>

                            <!-- Tab Switcher: Materi, Tugas, dan Users (Dosen) -->
                            <div class="classroom-tabs" role="tablist" aria-label="Konten mata kuliah">
                                <button class="classroom-tab is-active" id="tabMaterials" type="button"
                                    role="tab" aria-selected="true" aria-controls="materialsPanel" data-tab="materials">
                                    Materi Kuliah
                                </button>
                                <button class="classroom-tab" id="tabTasks" type="button"
                                    role="tab" aria-selected="false" aria-controls="tasksPanel" data-tab="tasks">
                                    Pengumpulan Tugas
                                </button>
                                <button class="classroom-tab d-none" id="tabUsers" type="button"
                                    role="tab" aria-selected="false" aria-controls="usersPanel" data-tab="users">
                                    Users (Mahasiswa)
                                </button>
                            </div>

                            <!-- TAB: MATERI KULIAH -->
                            <section class="classroom-tab-panel" id="materialsPanel"
                                role="tabpanel" aria-labelledby="tabMaterials">
                                <div id="materialsContent"></div>
                            </section>

                            <!-- TAB: PENGUMPULAN / MANAJEMEN TUGAS -->
                            <section class="classroom-tab-panel d-none" id="tasksPanel"
                                role="tabpanel" aria-labelledby="tabTasks">
                                <div id="tasksContent"></div>
                            </section>

                            <!-- TAB: USERS (DOSEN) -->
                            <section class="classroom-tab-panel d-none" id="usersPanel"
                                role="tabpanel" aria-labelledby="tabUsers">
                                <div id="usersContent"></div>
                            </section>

                        </div><!-- /courseView -->

                        <!-- =====================================================
                             VIEW B: PENGUMUMAN
                             ===================================================== -->
                        <div id="announcementView" class="d-none">

                            <header class="classroom-lesson-header">
                                <div>
                                    <p class="classroom-eyebrow" id="annEyebrow">Pengumuman Kelas</p>
                                    <h1 id="annTitle">Pengumuman</h1>
                                    <p class="classroom-course-meta" id="annMeta"></p>
                                </div>
                                <button class="btn-back-announcement" id="btnCloseAnnouncement"
                                    type="button" aria-label="Kembali ke materi">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M19 12H5"/><path d="m12 5-7 7 7 7"/>
                                    </svg>
                                    Kembali
                                </button>
                            </header>

                            <!-- Konten pengumuman -->
                            <div id="announcementsContent" class="classroom-stack"></div>

                        </div><!-- /announcementView -->

                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- ========================================================
         MODAL: TAMBAH PERTEMUAN / SECTION (DOSEN)
    ======================================================== -->
    <div class="modal-overlay" id="modalSection" role="dialog" aria-modal="true" aria-labelledby="modalSectionTitle">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="modalSectionTitle">Tambah Pertemuan / Bagian Kelas</h3>
                <button class="modal-close" data-close-modal="modalSection" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formSection" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputSectionNama" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Pertemuan *</label>
                        <input type="text" id="inputSectionNama" class="form-input" placeholder="cth: Pertemuan 3 — Rekursi & Pointer" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputSectionRingkasanJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Subjudul Ringkasan (Opsional)</label>
                        <input type="text" id="inputSectionRingkasanJudul" class="form-input" placeholder="cth: Konsep Memori dan Call Stack">
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputSectionRingkasanKonten" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Catatan Ringkasan Materi (Opsional)</label>
                        <textarea id="inputSectionRingkasanKonten" class="form-textarea" rows="4" placeholder="Tulis ringkasan poin-poin penting materi ini untuk mahasiswa..."></textarea>
                    </div>
                    <p class="classroom-feedback" id="feedbackSection" role="status" aria-live="polite"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalSection">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitSection">Simpan Pertemuan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: UNGGAH MATERI BARU (DOSEN)
    ======================================================== -->
    <div class="modal-overlay" id="modalMateri" role="dialog" aria-modal="true" aria-labelledby="modalMateriTitle">
        <div class="modal-box" style="max-width:580px;">
            <div class="modal-header">
                <h3 id="modalMateriTitle">Unggah Materi Perkuliahan</h3>
                <button class="modal-close" data-close-modal="modalMateri" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formMateri" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="selectMateriSection" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Pilih Pertemuan *</label>
                        <select id="selectMateriSection" class="form-input" required></select>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputMateriJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Dokumen / Materi *</label>
                        <input type="text" id="inputMateriJudul" class="form-input" placeholder="cth: Slide Presentasi Rekursi (PPTX)" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputMateriFile" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Berkas File (PDF, PPT, DOC, ZIP) *</label>
                        <input type="file" id="inputMateriFile" class="form-input" required>
                    </div>
                    <p class="classroom-feedback" id="feedbackMateri" role="status" aria-live="polite"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalMateri">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitMateri">Unggah Materi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: BUAT TUGAS BARU (DOSEN)
    ======================================================== -->
    <div class="modal-overlay" id="modalTugas" role="dialog" aria-modal="true" aria-labelledby="modalTugasTitle">
        <div class="modal-box" style="max-width:600px;">
            <div class="modal-header">
                <h3 id="modalTugasTitle">Buat Tugas Perkuliahan Baru</h3>
                <button class="modal-close" data-close-modal="modalTugas" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formTugas" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="selectTugasSection" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Pilih Pertemuan *</label>
                        <select id="selectTugasSection" class="form-input" required></select>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputTugasJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Tugas *</label>
                        <input type="text" id="inputTugasJudul" class="form-input" placeholder="cth: Praktikum 3 — Implementasi Stack & Queue" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="inputTugasDeskripsi" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Instruksi & Deskripsi Tugas *</label>
                        <textarea id="inputTugasDeskripsi" class="form-textarea" rows="4" placeholder="Jelaskan detail petunjuk tugas yang harus dikerjakan mahasiswa..." required></textarea>
                    </div>
                    <div class="form-grid" style="gap:14px; margin-bottom:14px;">
                        <div class="form-group">
                            <label for="inputTugasFormat" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Format Berkas (.c, .zip, .pdf)</label>
                            <input type="text" id="inputTugasFormat" class="form-input" value=".c, .zip, .pdf">
                        </div>
                        <div class="form-group">
                            <label for="inputTugasDue" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Tenggat Waktu *</label>
                            <input type="datetime-local" id="inputTugasDue" class="form-input" required>
                        </div>
                    </div>
                    <p class="classroom-feedback" id="feedbackTugas" role="status" aria-live="polite"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalTugas">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitTugas">Terbitkan Tugas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: EDIT PERTEMUAN / SECTION (DOSEN/ADMIN)
    ======================================================== -->
    <div class="modal-overlay" id="modalEditSection" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Edit Pertemuan / Bagian Kelas</h3>
                <button class="modal-close" data-close-modal="modalEditSection" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formEditSection" novalidate>
                <input type="hidden" id="editSectionId">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editSectionNama" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Pertemuan *</label>
                        <input type="text" id="editSectionNama" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editSectionRingkasanJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Subjudul Ringkasan (Opsional)</label>
                        <input type="text" id="editSectionRingkasanJudul" class="form-input">
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editSectionRingkasanKonten" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Catatan Ringkasan Materi</label>
                        <textarea id="editSectionRingkasanKonten" class="form-textarea" rows="4"></textarea>
                    </div>
                    <p class="classroom-feedback" id="feedbackEditSection" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalEditSection">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitEditSection">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: EDIT MATERI (DOSEN/ADMIN)
    ======================================================== -->
    <div class="modal-overlay" id="modalEditMateri" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:540px;">
            <div class="modal-header">
                <h3>Edit Materi Perkuliahan</h3>
                <button class="modal-close" data-close-modal="modalEditMateri" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formEditMateri" novalidate>
                <input type="hidden" id="editMateriId">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editMateriJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Dokumen / Materi *</label>
                        <input type="text" id="editMateriJudul" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editMateriFile" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Ganti Berkas (Opsional, kosongkan jika tetap)</label>
                        <input type="file" id="editMateriFile" class="form-input">
                    </div>
                    <p class="classroom-feedback" id="feedbackEditMateri" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalEditMateri">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitEditMateri">Simpan Materi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: EDIT TUGAS (DOSEN/ADMIN)
    ======================================================== -->
    <div class="modal-overlay" id="modalEditTugas" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:600px;">
            <div class="modal-header">
                <h3>Edit Tugas Perkuliahan</h3>
                <button class="modal-close" data-close-modal="modalEditTugas" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formEditTugas" novalidate>
                <input type="hidden" id="editTugasId">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editTugasJudul" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Tugas *</label>
                        <input type="text" id="editTugasJudul" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editTugasDeskripsi" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Instruksi Tugas</label>
                        <textarea id="editTugasDeskripsi" class="form-textarea" rows="4"></textarea>
                    </div>
                    <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                        <div class="form-group">
                            <label for="editTugasFormat" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Format Berkas</label>
                            <input type="text" id="editTugasFormat" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="editTugasDue" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Tenggat Waktu *</label>
                            <input type="datetime-local" id="editTugasDue" class="form-input" required>
                        </div>
                    </div>
                    <p class="classroom-feedback" id="feedbackEditTugas" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalEditTugas">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitEditTugas">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL: EDIT PENGUMUMAN (DOSEN/ADMIN)
    ======================================================== -->
    <div class="modal-overlay" id="modalEditAnnouncement" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:540px;">
            <div class="modal-header">
                <h3>Edit Pengumuman Kelas</h3>
                <button class="modal-close" data-close-modal="modalEditAnnouncement" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formEditAnnouncement" novalidate>
                <input type="hidden" id="editAnnouncementId">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="editAnnouncementKonten" style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Isi Pengumuman *</label>
                        <textarea id="editAnnouncementKonten" class="form-textarea" rows="4" required></textarea>
                    </div>
                    <p class="classroom-feedback" id="feedbackEditAnnouncement" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalEditAnnouncement">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitEditAnnouncement">Simpan Pengumuman</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/classroom.js"></script>
</body>
</html>
