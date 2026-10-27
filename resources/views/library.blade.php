<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.seo-head', [
        'title' => 'Perpustakaan Digital',
        'description' => 'Katalog perpustakaan digital Institut Teknologi Sepuluh Nopember, peminjaman dan pengunduhan referensi buku perkuliahan.',
    ])
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
    <style>
        .btn-action-small {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .btn-action-danger {
            background: #FEF2F2;
            color: #B42318;
            border-color: #FECACA;
        }
        .btn-action-danger:hover {
            background: #FEE2E2;
        }
    </style>
</head>
<body>
    
    <!-- Memanggil Top Bar -->
    <my-topbar></my-topbar>

    <div class="layout-container">
        
        <!-- Sidebar diset aktif pada 'library' -->
        <my-sidebar active="library"></my-sidebar>

        <main class="main-content">
            <div class="view-section">
                
                <div class="library-card">
                    <!-- HEADER: Judul, Pencarian & Tombol Unggah -->
                    <div class="library-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                        <div class="library-title">
                            <div class="icon-wrapper">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            </div>
                            <h2>Perpustakaan</h2>
                        </div>
                        
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div class="search-bar" style="margin:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" placeholder="Cari judul buku atau penulis..." id="searchInput">
                            </div>
                            <button class="btn-primary-small {{ in_array(auth()->user()?->role, ['dosen', 'admin'], true) ? '' : 'd-none' }}" id="btnOpenModalUploadBuku" type="button" style="white-space:nowrap;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                + Unggah Buku Baru
                            </button>
                        </div>
                    </div>

                    <!-- TABEL BUKU -->
                    <div class="library-table-container">
                        
                        <!-- Header Tabel -->
                        <div class="lib-table-header">
                            <div class="col-no">No.</div>
                            <div class="col-title">Judul Buku</div>
                            <div class="col-action" style="text-align:right;">Aksi</div>
                        </div>

                        <!-- Container Baris Buku Dinamis -->
                        <div id="libraryTableBody">
                            @forelse (($initialData['books']['data'] ?? []) as $index => $book)
                                <div class="lib-table-row">
                                    <div class="col-no">{{ ($initialData['books']['from'] ?? 1) + $index }}</div>
                                    <div class="col-title">
                                        <div>
                                            <span class="book-name">{{ $book['judul_buku'] }}</span>
                                            <span style="display:block; font-size:12px; color:#64748B; margin-top:2px;">
                                                Penulis: {{ $book['penulis'] }}@if(!empty($book['tahun'])) • Tahun {{ $book['tahun'] }}@endif ({{ $book['kategori'] }})
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-action" style="display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                                        <a href="{{ route('files.show', ['kind' => 'library', 'id' => $book['buku_id']]) }}" class="btn-primary-small" style="text-decoration:none; padding:6px 12px; font-size:12px;">Unduh</a>
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 20px; text-align: center; color: #64748B;">Tidak ada buku yang ditemukan.</div>
                            @endforelse
                        </div>

                    </div>

                    <!-- PAGINASI -->
                    <div class="pagination-wrapper" id="libraryPagination"></div>

                </div>

            </div>
        </main>
    </div>

    <!-- ========================================================
         MODAL: UNGGAH BUKU BARU (DOSEN / ADMIN)
    ======================================================== -->
    <div class="modal-overlay" id="modalUploadBuku" role="dialog" aria-modal="true">
        <div class="modal-box" style="max-width:540px;">
            <div class="modal-header">
                <h3>Unggah Buku Perpustakaan Baru</h3>
                <button class="modal-close" data-close-modal="modalUploadBuku" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form id="formUploadBuku" novalidate>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Judul Buku *</label>
                        <input type="text" id="inputBukuJudul" class="form-input" placeholder="cth: Struktur Data & Algoritma" required>
                    </div>
                    <div class="form-grid" style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Penulis *</label>
                            <input type="text" id="inputBukuPenulis" class="form-input" placeholder="cth: Dr. Ir. Budi Santoso" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Tahun Terbit</label>
                            <input type="number" id="inputBukuTahun" class="form-input" value="2024" min="1900" max="2030">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Kategori Buku *</label>
                        <select id="selectBukuKategori" class="form-input" required>
                            <option value="Referensi">Referensi</option>
                            <option value="Buku Teks">Buku Teks</option>
                            <option value="Jurnal">Jurnal</option>
                            <option value="Modul Praktikum">Modul Praktikum</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px;">Berkas Buku (PDF / EPUB) *</label>
                        <input type="file" id="inputBukuFile" class="form-input" accept=".pdf,.epub,.docx,.zip" required>
                    </div>
                    <p class="classroom-feedback" id="feedbackUploadBuku" role="status"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-close-modal="modalUploadBuku">Batal</button>
                    <button type="submit" class="btn-primary-small" id="btnSubmitUploadBuku">Unggah Buku</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/library.js"></script>
</body>
</html>
