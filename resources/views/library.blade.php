<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Perpustakaan - myITS Classroom</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/dashboard.css">
    <script src="/components.js"></script>
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
                    <!-- HEADER: Judul & Pencarian -->
                    <div class="library-header">
                        <div class="library-title">
                            <div class="icon-wrapper">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            </div>
                            <h2>Perpustakaan</h2>
                        </div>
                        
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" placeholder="Cari judul buku atau penulis..." id="searchInput">
                        </div>
                    </div>

                    <!-- TABEL BUKU -->
                    <div class="library-table-container">
                        
                        <!-- Header Tabel -->
                        <div class="lib-table-header">
                            <div class="col-no">No.</div>
                            <div class="col-title">Judul Buku</div>
                            <div class="col-action">Aksi</div>
                        </div>

                        <!-- Container Baris Buku Dinamis -->
                        <div id="libraryTableBody">
                            <div style="padding: 20px; text-align: center; color: #64748B;">Memuat koleksi perpustakaan...</div>
                        </div>

                    </div>

                    <!-- PAGINASI -->
                    <div class="pagination-wrapper" id="libraryPagination"></div>

                </div>

            </div>
        </main>
    </div>

    <!-- Modular Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/library.js"></script>
</body>
</html>
