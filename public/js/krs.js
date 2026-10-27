// public/js/krs.js - KRS Submission & Dosen Wali Approval logic
document.addEventListener("DOMContentLoaded", async function () {
    const krsTableRows = document.getElementById("krsTableRows");
    if (!krsTableRows) return;

    let currentUser = window.currentAuthUser ? window.currentAuthUser() : null;

    if (!currentUser) {
        try {
            const meRes = await window.apiFetch("/api/me");
            currentUser = meRes?.user;
            if (currentUser) localStorage.setItem("user", JSON.stringify(currentUser));
        } catch (e) {}
    }

    let studentKrsList = Array.isArray(window.__INITIAL_DATA__?.krs_list) ? window.__INITIAL_DATA__.krs_list : [];

    function takenCourseCodes(exceptSemester) {
        const codes = new Set();
        studentKrsList.forEach(function (krs) {
            if (krs.status_acc === "Ditolak") return;
            if (exceptSemester && krs.semester_tahun === exceptSemester && krs.status_acc !== "Disetujui") return;
            (krs.detail_krs || []).forEach(function (detail) {
                const code = detail.classroom?.kode_mk;
                if (code) codes.add(code);
            });
        });
        return codes;
    }

    const isDosen = currentUser?.role === "dosen";
    const isAdmin = currentUser?.role === "admin";
    const isMahasiswa = currentUser?.role === "mahasiswa";

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

    let currentActiveKrs = null;

    // Helper to display a KRS in the preview card
    function renderKrsPreview(krs, totalSks) {
        currentActiveKrs = krs;
        if (!krs) {
            krsTableRows.innerHTML = '<div style="padding:16px; color:#64748B;">Belum ada Kartu Rencana Studi terdaftar.</div>';
            return;
        }

        const mhs = krs.mahasiswa || {};
        const user = mhs.user || {};
        const dosenWali = mhs.dosen_wali?.user?.nama_lengkap || "Dosen Wali / Pembimbing Akademik";

        const elNama = document.getElementById("krs-nama");
        const elNim  = document.getElementById("krs-nim");
        const elDept = document.getElementById("krs-jurusan");
        const elSem  = document.getElementById("krs-semester");
        const elBadgeSem = document.getElementById("krs-badge-semester");
        const elBadgeStatus = document.getElementById("krs-status-badge");
        const elTotalSks = document.getElementById("krs-total-sks");
        const elSigMhs = document.getElementById("krs-sig-mhs-label");
        const elSigWali = document.getElementById("krs-sig-wali-label");

        if (elNama) elNama.textContent = user.nama_lengkap || krs.nrp;
        if (elNim)  elNim.textContent  = krs.nrp;
        if (elDept) elDept.textContent = mhs.departemen || "Departemen Informatika";
        if (elSem)  elSem.textContent  = `Semester ${krs.semester || 1} (${krs.semester_tahun})`;
        if (elBadgeSem) elBadgeSem.textContent = `Semester ${krs.semester || 1} (${krs.semester_tahun})`;
        if (elTotalSks) elTotalSks.textContent = `${totalSks ?? 0} SKS`;
        if (elSigMhs)  elSigMhs.textContent  = user.nama_lengkap || "Mahasiswa";
        if (elSigWali) elSigWali.textContent = dosenWali;

        if (elBadgeStatus) {
            elBadgeStatus.textContent = krs.status_acc || "Menunggu";
            if (krs.status_acc === "Disetujui") {
                elBadgeStatus.style.background = "#DCFCE7";
                elBadgeStatus.style.color = "#166534";
            } else if (krs.status_acc === "Ditolak") {
                elBadgeStatus.style.background = "#FEF2F2";
                elBadgeStatus.style.color = "#B42318";
            } else {
                elBadgeStatus.style.background = "#FEF3C7";
                elBadgeStatus.style.color = "#92400E";
            }
        }

        const details = krs.detail_krs || [];
        if (details.length === 0) {
            krsTableRows.innerHTML = '<div style="padding:16px; color:#64748B;">Tidak ada mata kuliah yang terdaftar dalam KRS ini.</div>';
            return;
        }

        krsTableRows.innerHTML = details.map(function (d) {
            const cls = d.classroom || {};
            const mk = cls.mata_kuliah || {};
            return `
            <div class="krs-table-row">
                <div class="col-kode">${window.escapeHtml(mk.kode_mk || "-")}</div>
                <div class="col-nama">${window.escapeHtml(mk.nama_mk || "-")}</div>
                <div class="col-kelas">${window.escapeHtml(window.classroomSectionLabel ? window.classroomSectionLabel(mk.nama_mk, cls.nama_kelas) : (cls.nama_kelas || "-"))}</div>
                <div class="col-sks">${mk.sks || 0}</div>
            </div>`;
        }).join("");
    }

    // "Unduh PDF" button handler
    const btnPdf = document.getElementById("btnUnduhKrs");
    if (btnPdf) {
        btnPdf.addEventListener("click", async function () {
            if (!currentActiveKrs) {
                alert("Belum ada data KRS yang dapat diunduh.");
                return;
            }
            btnPdf.disabled = true;
            try {
                const r = await fetch(`/api/krs/${encodeURIComponent(currentActiveKrs.krs_id)}/pdf`, {
                    headers: {
                        "Accept": "application/pdf",
                        "Authorization": "Bearer " + (localStorage.getItem("auth_token") || "")
                    },
                    credentials: "same-origin"
                });
                if (!r.ok) throw new Error("HTTP " + r.status);
                const url = URL.createObjectURL(await r.blob());
                const a = document.createElement("a");
                a.href = url;
                a.download = `KRS_${currentActiveKrs.nrp}_${String(currentActiveKrs.semester_tahun).replace("/", "-")}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error(e);
                window.print();
            } finally {
                btnPdf.disabled = false;
            }
        });
    }

    // ========================================================
    // 1. MAHASISWA: Pengajuan KRS Modal & Form
    // ========================================================
    if (isMahasiswa) {
        const btnAjukan = document.getElementById("btnOpenModalAjukanKrs");
        if (btnAjukan) {
            btnAjukan.classList.remove("d-none");
            btnAjukan.addEventListener("click", async function () {
                openModal("modalAjukanKrs");
                await loadAvailableClassroomsForKrs();
            });
        }

        let availableClasses = [];
        let selectedKelasIds = new Set();

        async function loadAvailableClassroomsForKrs() {
            const container = document.getElementById("krsAvailableClassrooms");
            container.innerHTML = '<div style="color:#64748B; padding:12px; text-align:center;">Memuat daftar kelas perkuliahan...</div>';

            try {
                const res = await window.apiFetch("/api/classrooms");
                availableClasses = res?.classrooms || [];

                if (availableClasses.length === 0) {
                    container.innerHTML = '<div style="color:#64748B; padding:12px; text-align:center;">Tidak ada kelas perkuliahan yang tersedia saat ini.</div>';
                    return;
                }

                // If existing KRS has details, pre-check them
                selectedKelasIds.clear();
                if (currentActiveKrs?.detail_krs) {
                    currentActiveKrs.detail_krs.forEach(d => {
                        if (d.kelas_id) selectedKelasIds.add(d.kelas_id);
                    });
                }

                renderClassroomCheckboxes();
            } catch (err) {
                container.innerHTML = `<div style="color:#B42318; padding:12px; text-align:center;">Gagal memuat kelas: ${window.escapeHtml(err.message)}</div>`;
            }
        }

        function renderClassroomCheckboxes() {
            const container = document.getElementById("krsAvailableClassrooms");
            const exceptSemester = document.getElementById("inputKrsSemesterTahun")?.value.trim() || "";
            const taken = takenCourseCodes(exceptSemester);
            container.innerHTML = availableClasses.map(c => {
                const mk = c.mata_kuliah || {};
                const dsn = c.dosen?.user?.nama_lengkap || c.nip || "Dosen Pengampu";
                const alreadyTaken = taken.has(c.kode_mk);
                if (alreadyTaken) selectedKelasIds.delete(c.kelas_id);
                const isChecked = !alreadyTaken && selectedKelasIds.has(c.kelas_id) ? "checked" : "";
                const sks = mk.sks || 3;

                const displayName = c.display_name || (window.formatClassroomName ? window.formatClassroomName(mk.nama_mk, c.nama_kelas) : (c.nama_kelas || mk.nama_mk));

                return `
                <label class="classroom-checkbox-item" style="${alreadyTaken ? "opacity:0.65;" : ""}">
                    <input type="checkbox" class="cb-kelas" value="${window.escapeHtml(c.kelas_id)}" data-sks="${sks}" ${isChecked} ${alreadyTaken ? "disabled" : ""}>
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#0F172A; font-size:13px;">
                            ${window.escapeHtml(displayName)}
                        </div>
                        <div style="font-size:12px; color:#64748B;">
                            Kode: ${window.escapeHtml(c.kode_mk)} • Pengampu: ${window.escapeHtml(dsn)}
                            ${alreadyTaken ? " • Sudah diambil" : ""}
                        </div>
                    </div>
                    <span class="status-badge ${alreadyTaken ? "badge-warning" : "badge-info"}" style="font-weight:700;">${alreadyTaken ? "Sudah diambil" : sks + " SKS"}</span>
                </label>`;
            }).join("");

            updateSelectedSks();

            container.querySelectorAll(".cb-kelas").forEach(cb => {
                cb.addEventListener("change", function () {
                    if (this.checked) {
                        selectedKelasIds.add(this.value);
                    } else {
                        selectedKelasIds.delete(this.value);
                    }
                    updateSelectedSks();
                });
            });
        }

        function updateSelectedSks() {
            let total = 0;
            availableClasses.forEach(c => {
                if (selectedKelasIds.has(c.kelas_id)) {
                    total += (c.mata_kuliah?.sks || 3);
                }
            });

            const counterEl = document.getElementById("krsSelectedSksCounter");
            const submitBtn = document.getElementById("btnSubmitAjukanKrs");

            if (counterEl) {
                counterEl.textContent = `${total} SKS (Maks. 24 SKS)`;
                if (total > 24) {
                    counterEl.style.color = "#B42318";
                } else {
                    counterEl.style.color = "#00337F";
                }
            }

            if (submitBtn) {
                submitBtn.disabled = total > 24 || selectedKelasIds.size === 0;
            }
        }

        // Handle Submit Form Ajukan KRS
        const formAjukan = document.getElementById("formAjukanKrs");
        if (formAjukan) {
            formAjukan.addEventListener("submit", async function (e) {
                e.preventDefault();
                const fb = document.getElementById("feedbackAjukanKrs");
                const btn = document.getElementById("btnSubmitAjukanKrs");

                if (selectedKelasIds.size === 0) {
                    if (fb) {
                        fb.textContent = "Pilih minimal 1 kelas perkuliahan.";
                        fb.className = "classroom-feedback is-visible is-error";
                    }
                    return;
                }

                const semester = parseInt(document.getElementById("inputKrsSemester").value, 10);
                const semTahun = document.getElementById("inputKrsSemesterTahun").value.trim();

                if (btn) { btn.disabled = true; btn.textContent = "Mengirim..."; }

                try {
                    const res = await window.apiFetch("/api/krs", {
                        method: "POST",
                        body: JSON.stringify({
                            semester: semester,
                            semester_tahun: semTahun,
                            kelas_ids: Array.from(selectedKelasIds)
                        })
                    });

                    closeModal("modalAjukanKrs");
                    alert("KRS berhasil diajukan dan sedang menunggu persetujuan Dosen Wali!");
                    await loadStudentKrs();
                } catch (err) {
                    if (fb) {
                        fb.textContent = err.message || "Gagal mengajukan KRS.";
                        fb.className = "classroom-feedback is-visible is-error";
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.textContent = "Ajukan KRS ke Dosen Wali"; }
                }
            });
        }
    }

    // ========================================================
    // 2. DOSEN & ADMIN: Perwalian & Persetujuan KRS
    // ========================================================
    if (isDosen || isAdmin) {
        const perwalianSection = document.getElementById("perwalianSection");
        const pageTitleText = document.getElementById("pageTitleText");
        const pageSubTitleText = document.getElementById("pageSubTitleText");

        if (perwalianSection) perwalianSection.classList.remove("d-none");
        if (pageTitleText) pageTitleText.textContent = isDosen ? "Perwalian & Persetujuan KRS" : "Kelola Kartu Rencana Studi (KRS)";
        if (pageSubTitleText) pageSubTitleText.textContent = "Tinjau pengajuan rencana studi mahasiswa dan berikan keputusan persetujuan.";

        async function loadPerwalianList() {
            const tbody = document.getElementById("perwalianTableBody");
            const summaryBadge = document.getElementById("perwalianSummaryBadge");

            try {
                let res;
                if (Array.isArray(window.__INITIAL_DATA__?.krs_list)) {
                    res = { krs_list: window.__INITIAL_DATA__.krs_list };
                    window.__INITIAL_DATA__.krs_list = null;
                } else {
                    res = await window.apiFetch("/api/krs");
                }
                const list = res?.krs_list || [];

                if (summaryBadge) {
                    const pendingCount = list.filter(k => k.status_acc === "Menunggu").length;
                    summaryBadge.textContent = `${pendingCount} Menunggu Persetujuan • Total ${list.length} Mahasiswa`;
                    summaryBadge.className = pendingCount > 0 ? "status-badge badge-warning" : "status-badge badge-success";
                }

                if (list.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:16px; color:#64748B;">Belum ada mahasiswa bimbingan yang mengajukan KRS.</td></tr>';
                    renderKrsPreview(null, 0);
                    return;
                }

                tbody.innerHTML = list.map(k => {
                    const mhs = k.mahasiswa || {};
                    const u = mhs.user || {};
                    const mhsName = u.nama_lengkap || k.nrp;
                    const details = k.detail_krs || [];
                    const sks = details.reduce((sum, d) => sum + (d.classroom?.mataKuliah?.sks || 0), 0);

                    let badgeCls = "badge-warning";
                    if (k.status_acc === "Disetujui") badgeCls = "badge-success";
                    if (k.status_acc === "Ditolak") badgeCls = "badge-danger";

                    return `
                    <tr>
                        <td><strong>${window.escapeHtml(k.nrp)}</strong></td>
                        <td>${window.escapeHtml(mhsName)}</td>
                        <td>Semester ${k.semester} (${window.escapeHtml(k.semester_tahun)})</td>
                        <td>${details.length} MK • <strong>${sks} SKS</strong></td>
                        <td><span class="status-badge ${badgeCls}" id="status-badge-${k.krs_id}">${window.escapeHtml(k.status_acc)}</span></td>
                        <td style="text-align:right;">
                            <div class="action-btn-group" style="justify-content:flex-end;">
                                <button class="btn-action-small btn-action-secondary btn-view-krs" data-id="${k.krs_id}">
                                    Tinjau
                                </button>
                                ${k.status_acc !== "Disetujui" ? `
                                    <button class="btn-action-small btn-action-success btn-approve-krs" data-id="${k.krs_id}" data-action="Disetujui">
                                        Setujui
                                    </button>
                                ` : ""}
                                ${k.status_acc !== "Ditolak" ? `
                                    <button class="btn-action-small btn-action-danger btn-approve-krs" data-id="${k.krs_id}" data-action="Ditolak">
                                        Tolak
                                    </button>
                                ` : ""}
                            </div>
                        </td>
                    </tr>`;
                }).join("");

                // Wire Tinjau
                tbody.querySelectorAll(".btn-view-krs").forEach(b => {
                    b.addEventListener("click", async function () {
                        const id = this.dataset.id;
                        const detailRes = await window.apiFetch(`/api/krs/${id}`);
                        if (detailRes?.krs) {
                            renderKrsPreview(detailRes.krs, detailRes.total_sks);
                            document.getElementById("krsPreviewCard")?.scrollIntoView({ behavior: "smooth" });
                        }
                    });
                });

                // Wire Setujui / Tolak
                tbody.querySelectorAll(".btn-approve-krs").forEach(b => {
                    b.addEventListener("click", async function () {
                        const id = this.dataset.id;
                        const action = this.dataset.action;
                        this.disabled = true;

                        try {
                            await window.apiFetch(`/api/krs/${id}/approve`, {
                                method: "PATCH",
                                body: JSON.stringify({ status_acc: action })
                            });
                            alert(`KRS berhasil di-${action.toLowerCase()}!`);
                            await loadPerwalianList();
                            if (currentActiveKrs?.krs_id === id) {
                                const detailRes = await window.apiFetch(`/api/krs/${id}`);
                                renderKrsPreview(detailRes.krs, detailRes.total_sks);
                            }
                        } catch (err) {
                            alert(err.message || "Gagal memproses persetujuan KRS.");
                        } finally {
                            this.disabled = false;
                        }
                    });
                });

                // Load first KRS as preview
                if (list.length > 0) {
                    const first = list[0];
                    if (Array.isArray(first.detail_krs)) {
                        const sks = first.detail_krs.reduce((sum, d) => sum + (d.classroom?.mata_kuliah?.sks || 0), 0);
                        renderKrsPreview(first, sks);
                    } else {
                        const detailRes = await window.apiFetch(`/api/krs/${first.krs_id}`);
                        renderKrsPreview(detailRes?.krs || first, detailRes?.total_sks);
                    }
                }
            } catch (err) {
                console.error("Gagal memuat daftar perwalian:", err);
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:16px; color:#B42318;">Gagal memuat mahasiswa perwalian: ${window.escapeHtml(err.message)}</td></tr>`;
            }
        }

        await loadPerwalianList();
        return;
    }

    // ========================================================
    // 3. MAHASISWA: Load Single Student KRS
    // ========================================================
    async function loadStudentKrs() {
        try {
            let res;
            if (Array.isArray(window.__INITIAL_DATA__?.krs_list)) {
                res = { krs_list: window.__INITIAL_DATA__.krs_list };
                window.__INITIAL_DATA__.krs_list = null;
            } else {
                res = await window.apiFetch("/api/krs");
            }
            const krsList = res?.krs_list || [];
            studentKrsList = krsList;

            if (krsList.length === 0) {
                renderKrsPreview(null, 0);
                return;
            }

            const latestKrs = krsList[0];
            if (Array.isArray(latestKrs.detail_krs)) {
                const sks = latestKrs.detail_krs.reduce((sum, d) => sum + (d.classroom?.mata_kuliah?.sks || 0), 0);
                renderKrsPreview(latestKrs, sks);
            } else {
                const detailRes = await window.apiFetch(`/api/krs/${latestKrs.krs_id}`);
                renderKrsPreview(detailRes?.krs || latestKrs, detailRes?.total_sks);
            }
        } catch (err) {
            console.error("Gagal memuat data KRS:", err);
            krsTableRows.innerHTML = '<div style="padding:16px; color:#B42318;">Gagal memuat data KRS. Silakan coba lagi.</div>';
        }
    }

    await loadStudentKrs();
});
