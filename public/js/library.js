// public/js/library.js - Perpustakaan search, pagination, download, upload & delete logic
document.addEventListener("DOMContentLoaded", async function () {
    const libraryTableBody = document.getElementById("libraryTableBody");
    const searchInput = document.getElementById("searchInput");
    const libraryPagination = document.getElementById("libraryPagination");

    if (!libraryTableBody) return;

    let currentUser = null;
    try {
        const raw = localStorage.getItem("user");
        if (raw) currentUser = JSON.parse(raw);
    } catch (e) {}

    if (!currentUser) {
        try {
            const meRes = await window.apiFetch("/api/me");
            currentUser = meRes?.user;
            if (currentUser) localStorage.setItem("user", JSON.stringify(currentUser));
        } catch (e) {}
    }

    const canManageBooks = currentUser?.role === "dosen" || currentUser?.role === "admin";

    // Modal Helpers
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add("is-open");
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove("is-open");
    }

    document.addEventListener("click", function (e) {
        const closeBtn = e.target.closest("[data-close-modal]");
        if (closeBtn) closeModal(closeBtn.dataset.closeModal);
        if (e.target.classList.contains("modal-overlay")) closeModal(e.target.id);
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-overlay.is-open").forEach(m => closeModal(m.id));
        }
    });

    // If teacher/admin, show upload button
    const btnOpenUpload = document.getElementById("btnOpenModalUploadBuku");
    if (btnOpenUpload && canManageBooks) {
        btnOpenUpload.classList.remove("d-none");
        btnOpenUpload.addEventListener("click", () => openModal("modalUploadBuku"));
    }

    // Form Upload Submit
    const formUpload = document.getElementById("formUploadBuku");
    if (formUpload) {
        formUpload.addEventListener("submit", async function (e) {
            e.preventDefault();
            const judul = document.getElementById("inputBukuJudul")?.value.trim();
            const penulis = document.getElementById("inputBukuPenulis")?.value.trim();
            const tahun = document.getElementById("inputBukuTahun")?.value;
            const kategori = document.getElementById("selectBukuKategori")?.value;
            const fileInput = document.getElementById("inputBukuFile");
            const fb = document.getElementById("feedbackUploadBuku");
            const btn = document.getElementById("btnSubmitUploadBuku");

            if (!judul || !penulis || !fileInput?.files?.[0]) {
                if (fb) {
                    fb.textContent = "Lengkapi judul, penulis, dan pilih berkas buku.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
                return;
            }

            if (btn) { btn.disabled = true; btn.textContent = "Mengunggah..."; }

            try {
                const formData = new FormData();
                formData.append("judul_buku", judul);
                formData.append("penulis", penulis);
                if (tahun) formData.append("tahun", tahun);
                formData.append("kategori", kategori || "Referensi");
                formData.append("file", fileInput.files[0]);

                await window.apiFetch("/api/library", {
                    method: "POST",
                    body: formData
                });

                formUpload.reset();
                closeModal("modalUploadBuku");
                alert("Buku berhasil ditambahkan ke perpustakaan!");
                loadBooks(1, searchQuery);
            } catch (err) {
                if (fb) {
                    fb.textContent = err.message || "Gagal mengunggah buku.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Unggah Buku"; }
            }
        });
    }

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

                const deleteBtnHtml = canManageBooks ? `
                    <button type="button" class="btn-action-small btn-action-danger btn-delete-buku" data-id="${b.buku_id}" data-title="${window.escapeHtml(b.judul_buku)}">
                        Hapus
                    </button>
                ` : "";

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
                    <div class="col-action" style="display:flex; justify-content:flex-end; gap:8px; align-items:center;">
                        <a href="${downloadUrl}" download="${window.escapeHtml(b.nama_file || 'buku.pdf')}" target="_blank" class="btn-primary-small" style="text-decoration:none; padding:6px 12px; font-size:12px;">
                            ${downloadIcon} Unduh
                        </a>
                        ${deleteBtnHtml}
                    </div>
                </div>`;
            }).join("");

            // Wire delete book buttons
            if (canManageBooks) {
                libraryTableBody.querySelectorAll(".btn-delete-buku").forEach(btn => {
                    btn.addEventListener("click", async function () {
                        const id = this.dataset.id;
                        const title = this.dataset.title;
                        if (!confirm(`Hapus buku "${title}" dari perpustakaan?`)) return;

                        try {
                            await window.apiFetch(`/api/library/${encodeURIComponent(id)}`, { method: "DELETE" });
                            alert("Buku berhasil dihapus dari perpustakaan.");
                            loadBooks(currentPage, searchQuery);
                        } catch (err) {
                            alert(err.message || "Gagal menghapus buku.");
                        }
                    });
                });
            }

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
