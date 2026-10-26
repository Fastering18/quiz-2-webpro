// public/js/library.js - Perpustakaan search, pagination, and download logic
document.addEventListener("DOMContentLoaded", function () {
    const libraryTableBody = document.getElementById("libraryTableBody");
    const searchInput = document.getElementById("searchInput");
    const libraryPagination = document.getElementById("libraryPagination");

    if (!libraryTableBody) return;

    let currentPage = 1;
    let searchQuery = "";
    let searchTimeout = null;

    const downloadIcon = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>`;
    const bookIcon = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>`;

    async function loadBooks(page = 1, query = "") {
        libraryTableBody.innerHTML = '<div style="padding:20px; text-align:center; color:#64748B;">Memuat koleksi perpustakaan...</div>';

        try {
            const res = await window.apiFetch(`/api/library?page=${page}&search=${encodeURIComponent(query)}`);
            const booksData = res?.books || {};
            const list = booksData.data || [];
            const fromIdx = (booksData.current_page - 1) * (booksData.per_page || 12);

            if (list.length === 0) {
                libraryTableBody.innerHTML = '<div style="padding:24px; text-align:center; color:#64748B;">Tidak ada buku yang ditemukan.</div>';
                if (libraryPagination) libraryPagination.innerHTML = "";
                return;
            }

            libraryTableBody.innerHTML = list.map(function (b, idx) {
                const downloadUrl = b.file_dokumen_key ? `/storage/${b.file_dokumen_key}` : "#";
                return `
                <div class="lib-table-row">
                    <div class="col-no">${fromIdx + idx + 1}</div>
                    <div class="col-title">
                        <div class="book-cover-icon">${bookIcon}</div>
                        <div>
                            <span class="book-name">${window.escapeHtml(b.judul_buku)}</span>
                            <span style="display:block; font-size:12px; color:#64748B; margin-top:2px;">
                                Penulis: ${window.escapeHtml(b.penulis)}${b.tahun ? " • Tahun " + b.tahun : ""} (${window.escapeHtml(b.kategori)})
                            </span>
                        </div>
                    </div>
                    <div class="col-action">
                        <a href="${downloadUrl}" download="${window.escapeHtml(b.nama_file || 'buku.pdf')}" target="_blank" class="btn-primary-small" style="text-decoration:none;">
                            ${downloadIcon} Unduh
                        </a>
                    </div>
                </div>`;
            }).join("");

            // Render pagination
            if (libraryPagination && booksData.last_page > 1) {
                let pagHtml = "";
                if (booksData.current_page > 1) {
                    pagHtml += `<button class="page-btn" data-page="${booksData.current_page - 1}">Sebelumnya</button>`;
                }
                for (let p = 1; p <= booksData.last_page; p++) {
                    pagHtml += `<button class="page-btn ${p === booksData.current_page ? "active" : ""}" data-page="${p}">${p}</button>`;
                }
                if (booksData.current_page < booksData.last_page) {
                    pagHtml += `<button class="page-btn" data-page="${booksData.current_page + 1}">Berikutnya</button>`;
                }
                libraryPagination.innerHTML = pagHtml;

                libraryPagination.querySelectorAll(".page-btn").forEach(function (btn) {
                    btn.addEventListener("click", function () {
                        const p = parseInt(this.dataset.page, 10);
                        if (p) {
                            currentPage = p;
                            loadBooks(currentPage, searchQuery);
                        }
                    });
                });
            } else if (libraryPagination) {
                libraryPagination.innerHTML = "";
            }
        } catch (err) {
            libraryTableBody.innerHTML = '<div style="padding:20px; text-align:center; color:#B42318;">Gagal memuat buku perpustakaan.</div>';
        }
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimeout);
            searchQuery = this.value.trim();
            searchTimeout = setTimeout(function () {
                currentPage = 1;
                loadBooks(currentPage, searchQuery);
            }, 300);
        });
    }

    loadBooks(1, "");
});
