// public/js/lapor-admin.js - Lapor Admin ticket submit and history logic
// Supports Mahasiswa/Dosen report submission and Administrator ticket triage/answering.

document.addEventListener("DOMContentLoaded", async function () {
    const reportForm = document.getElementById("reportForm");
    const reportHistoryList = document.getElementById("reportHistoryList");
    const reportFormSection = document.querySelector(".report-form-section");

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

    const isAdmin = currentUser?.role === "admin";

    // Adjust UI for Administrator
    if (isAdmin) {
        const pageTitle = document.querySelector(".page-header h2");
        const pageDesc  = document.querySelector(".page-header p");
        if (pageTitle) pageTitle.textContent = "Kelola Laporan Kendala (Admin Helpdesk)";
        if (pageDesc)  pageDesc.textContent  = "Daftar tiket pengaduan diurutkan dari yang belum dijawab dan terbaru.";

        const historyTitle = document.querySelector(".history-header .section-title");
        if (historyTitle) historyTitle.textContent = "Daftar Semua Tiket Pengaduan";

        // Replace left form with summary widget for admin
        if (reportFormSection) {
            reportFormSection.innerHTML = `
                <div class="report-card">
                    <h3 class="section-title">Pusat Bantuan Helpdesk</h3>
                    <p style="font-size:13px; color:#64748B; margin-bottom:16px;">
                        Tinjau kendala teknis yang dialami civitas akademika. Tanggapan Anda akan langsung muncul di portal mahasiswa/dosen terkait.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="background:#FEF3C7; border:1px solid #FCD34D; border-radius:8px; padding:12px;">
                            <span style="font-size:12px; font-weight:700; color:#92400E; display:block;">Prioritas Utama</span>
                            <span style="font-size:13px; color:#78350F;">Tiket belum dijawab diprioritaskan di urutan teratas.</span>
                        </div>
                        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px; padding:12px;">
                            <span style="font-size:12px; font-weight:700; color:#166534; display:block;">Status Tiket</span>
                            <span style="font-size:12px; color:#14532D;">Tandai "Diproses" atau "Selesai" setelah menindaklanjuti kendala.</span>
                        </div>
                    </div>
                </div>`;
        }
    }

    // Custom select dropdown logic for student/dosen report form
    const selectWrapper = document.getElementById("kategoriSelect");
    const selectTrigger = selectWrapper?.querySelector(".custom-select-trigger");
    const selectOptions = selectWrapper?.querySelector(".custom-select-options");
    const selectText    = document.getElementById("kategoriText");
    const optionItems   = selectWrapper?.querySelectorAll(".option-item");

    if (selectWrapper && selectTrigger && selectOptions) {
        selectTrigger.addEventListener("click", function (e) {
            e.stopPropagation();
            selectWrapper.classList.toggle("open");
            selectOptions.classList.toggle("d-none");
        });
        optionItems.forEach(function (item) {
            item.addEventListener("click", function () {
                optionItems.forEach(opt => opt.classList.remove("active"));
                this.classList.add("active");
                if (selectText) selectText.textContent = this.textContent;
                selectWrapper.classList.remove("open");
                selectOptions.classList.add("d-none");
            });
        });
        document.addEventListener("click", function (e) {
            if (!selectWrapper.contains(e.target)) {
                selectWrapper.classList.remove("open");
                selectOptions.classList.add("d-none");
            }
        });
    }

    // Dropzone & File selection
    const reportFile = document.getElementById("reportFile");
    const reportDropzone = document.getElementById("reportDropzone");
    const reportFileLabel = document.getElementById("reportFileLabel");
    let selectedReportFile = null;

    if (reportDropzone && reportFile) {
        reportDropzone.addEventListener("click", () => reportFile.click());
        reportDropzone.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") { e.preventDefault(); reportFile.click(); }
        });
        reportFile.addEventListener("change", function () {
            if (reportFile.files && reportFile.files[0]) {
                selectedReportFile = reportFile.files[0];
                if (reportFileLabel) reportFileLabel.textContent = selectedReportFile.name + " (" + window.formatFileSize(selectedReportFile.size) + ")";
            }
        });
        ["dragenter", "dragover"].forEach(ev => {
            reportDropzone.addEventListener(ev, e => { e.preventDefault(); reportDropzone.classList.add("is-dragover"); });
        });
        ["dragleave", "drop"].forEach(ev => {
            reportDropzone.addEventListener(ev, e => { e.preventDefault(); reportDropzone.classList.remove("is-dragover"); });
        });
        reportDropzone.addEventListener("drop", function (e) {
            const file = e.dataTransfer?.files?.[0];
            if (file) {
                selectedReportFile = file;
                if (reportFileLabel) reportFileLabel.textContent = selectedReportFile.name + " (" + window.formatFileSize(selectedReportFile.size) + ")";
            }
        });
    }

    function showReportFeedback(msg, kind) {
        const fb = document.getElementById("reportFeedback");
        if (!fb) return;
        fb.textContent = msg;
        fb.className = "classroom-feedback is-visible " + (kind === "success" ? "is-success" : "is-error");
    }

    // Load and render report cards
    async function loadReports() {
        if (!reportHistoryList) return;
        reportHistoryList.innerHTML = '<div style="padding:16px; color:#64748B; font-size:13px;">Memuat daftar laporan...</div>';

        try {
            const res = await window.apiFetch("/api/reports");
            const reports = res?.data || [];

            if (reports.length === 0) {
                reportHistoryList.innerHTML = `<div style="padding:16px; color:#64748B; font-size:13px;">${isAdmin ? "Tidak ada tiket laporan saat ini." : "Belum ada laporan yang Anda ajukan."}</div>`;
                return;
            }

            reportHistoryList.innerHTML = reports.map(function (rep, idx) {
                const isOpen = idx === 0 || (!rep.tanggapan_admin && isAdmin);
                const hasAnswer = Boolean(rep.tanggapan_admin);

                let badgeClass = "badge-primary";
                if (rep.status_tiket === "Diproses") badgeClass = "badge-warning";
                if (rep.status_tiket === "Selesai")  badgeClass = "badge-success";
                if (rep.status_tiket === "Ditolak")  badgeClass = "badge-danger";

                const pelaporName = rep.pelapor?.nama_lengkap || rep.pelapor?.username || "Pengguna";
                const pelaporRole = (rep.pelapor?.role || "user").toUpperCase();

                // Admin Answer Form vs Student/Dosen Display Box
                const adminReplySection = isAdmin ? `
                    <div class="admin-reply-box" style="margin-top:12px; border:1px solid #CBD5E1; background:#F8FAFC;">
                        <span class="reply-title" style="font-weight:700; color:#0F172A; margin-bottom:8px;">
                            ${hasAnswer ? "Tanggapan Admin (Sudah Dijawab)" : "Tanggapi Kendala Ini (Admin)"}
                        </span>
                        <div style="margin-bottom:8px;">
                            <textarea class="form-textarea" rows="3" id="admin-tanggapan-${rep.tiket_id}" placeholder="Tuliskan solusi atau tindak lanjut kendala...">${window.escapeHtml(rep.tanggapan_admin || "")}</textarea>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <label style="font-size:12px; font-weight:600;">Status:</label>
                                <select class="form-input" style="padding:6px 12px; width:auto; font-size:13px;" id="admin-status-${rep.tiket_id}">
                                    <option value="Diproses" ${rep.status_tiket === "Diproses" ? "selected" : ""}>Diproses</option>
                                    <option value="Selesai" ${rep.status_tiket === "Selesai" || !hasAnswer ? "selected" : ""}>Selesai</option>
                                    <option value="Ditolak" ${rep.status_tiket === "Ditolak" ? "selected" : ""}>Ditolak</option>
                                </select>
                            </div>
                            <button type="button" class="btn-primary-small btn-kirim-tanggapan" data-tiket-id="${rep.tiket_id}">
                                Simpan Tanggapan
                            </button>
                        </div>
                        <div id="reply-feedback-${rep.tiket_id}" style="font-size:12px; margin-top:6px;"></div>
                    </div>
                ` : `
                    <div class="admin-reply-box">
                        <div class="reply-header">
                            <span class="reply-title">${hasAnswer ? "Balasan Administrator" : "Menunggu balasan admin..."}</span>
                            ${hasAnswer ? `<div class="reply-meta"><span class="reply-author">${window.escapeHtml(rep.admin?.nama_lengkap || "Admin Portal")}</span><span class="reply-time">${window.formatDate(rep.tanggal_tanggapan)}</span></div>` : ""}
                        </div>
                        ${hasAnswer ? `<p class="reply-text">${window.escapeHtml(rep.tanggapan_admin)}</p>` : ""}
                    </div>
                `;

                return `
                <div class="history-card ${isOpen ? "active" : ""}" data-card-tiket="${rep.tiket_id}">
                    <div class="history-card-header">
                        <h4>${window.escapeHtml(rep.judul_laporan)}</h4>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" class="icon-toggle"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                    <div class="history-card-meta">
                        <div>
                            <span class="status-badge ${badgeClass}" id="status-badge-${rep.tiket_id}">${window.escapeHtml(rep.status_tiket)}</span>
                            ${isAdmin && !hasAnswer ? '<span style="font-size:11px; background:#FEF2F2; color:#B42318; padding:2px 8px; border-radius:12px; font-weight:600; margin-left:6px;">Belum Dijawab</span>' : ""}
                        </div>
                        <span class="history-time">${window.formatDate(rep.tanggal_lapor)}</span>
                    </div>
                    <div class="history-details">
                        <hr class="divider">
                        <div class="detail-row">
                            <span class="detail-label">Pelapor: <strong>${window.escapeHtml(pelaporName)} (${pelaporRole})</strong></span>
                            <span class="detail-cat">Kategori: ${window.escapeHtml(rep.kategori_modul)}</span>
                        </div>
                        <p class="detail-text" style="white-space:pre-line;">${window.escapeHtml(rep.deskripsi_kendala)}</p>
                        ${rep.lampiran_bukti_key ? `<p style="font-size:12px; margin-bottom:12px;"><a href="/storage/${rep.lampiran_bukti_key}" target="_blank" class="link-blue">Unduh Lampiran Bukti</a></p>` : ""}
                        ${adminReplySection}
                    </div>
                </div>`;
            }).join("");

            // Wire accordion
            reportHistoryList.querySelectorAll(".history-card-header").forEach(function (header) {
                header.addEventListener("click", function () {
                    const card = this.closest(".history-card");
                    if (card) card.classList.toggle("active");
                });
            });

            // Wire admin reply action buttons
            if (isAdmin) {
                reportHistoryList.querySelectorAll(".btn-kirim-tanggapan").forEach(function (btn) {
                    btn.addEventListener("click", async function () {
                        const tiketId = this.dataset.tiketId;
                        const textarea = document.getElementById(`admin-tanggapan-${tiketId}`);
                        const statusSelect = document.getElementById(`admin-status-${tiketId}`);
                        const fb = document.getElementById(`reply-feedback-${tiketId}`);
                        const badge = document.getElementById(`status-badge-${tiketId}`);

                        const tanggapan = textarea?.value?.trim();
                        const status = statusSelect?.value || "Selesai";

                        if (!tanggapan) {
                            if (fb) {
                                fb.textContent = "Tuliskan isi tanggapan terlebih dahulu.";
                                fb.style.color = "#B42318";
                            }
                            return;
                        }

                        this.disabled = true;
                        this.textContent = "Menyimpan...";

                        try {
                            await window.apiFetch(`/api/reports/${tiketId}/reply`, {
                                method: "PATCH",
                                body: JSON.stringify({
                                    tanggapan_admin: tanggapan,
                                    status_tiket: status
                                })
                            });

                            if (fb) {
                                fb.textContent = "Tanggapan berhasil disimpan!";
                                fb.style.color = "#166534";
                            }
                            if (badge) {
                                badge.textContent = status;
                                badge.className = "status-badge " + (status === "Selesai" ? "badge-success" : (status === "Diproses" ? "badge-warning" : "badge-danger"));
                            }
                        } catch (err) {
                            if (fb) {
                                fb.textContent = err.message || "Gagal menyimpan tanggapan.";
                                fb.style.color = "#B42318";
                            }
                        } finally {
                            this.disabled = false;
                            this.textContent = "Simpan Tanggapan";
                        }
                    });
                });
            }
        } catch (err) {
            reportHistoryList.innerHTML = '<div style="padding:16px; color:#B42318; font-size:13px;">Gagal memuat daftar laporan.</div>';
        }
    }

    const btnLihatSemua = document.getElementById("btnLihatSemua");
    if (btnLihatSemua) {
        btnLihatSemua.addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelectorAll(".history-card").forEach(c => c.classList.add("active"));
        });
    }

    // Submit handler (for Mahasiswa / Dosen)
    if (reportForm && !isAdmin) {
        reportForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const kategori = selectText?.textContent || "KRS";
            const titleInput = document.getElementById("reportTitle");
            const descInput  = document.getElementById("reportDescription");
            const submitBtn  = document.getElementById("btnSubmitReport");

            if (!titleInput.value.trim() || !descInput.value.trim()) {
                showReportFeedback("Harap isi subjek laporan dan deskripsi kendala.", "error");
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Mengirim...";
            }

            try {
                const formData = new FormData();
                formData.append("kategori_modul", kategori);
                formData.append("judul_laporan", titleInput.value.trim());
                formData.append("deskripsi_kendala", descInput.value.trim());
                if (selectedReportFile) formData.append("lampiran", selectedReportFile);

                await window.apiFetch("/api/reports", {
                    method: "POST",
                    body: formData
                });

                showReportFeedback("Laporan kendala Anda berhasil dikirim ke Administrator.", "success");
                titleInput.value = "";
                descInput.value = "";
                selectedReportFile = null;
                if (reportFile) reportFile.value = "";
                if (reportFileLabel) reportFileLabel.textContent = "Klik atau seret file ke sini (PDF, JPG, PNG)";

                loadReports();
            } catch (err) {
                showReportFeedback(err.message || "Gagal mengirim laporan.", "error");
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Kirim Laporan";
                }
            }
        });
    }

    loadReports();
});
