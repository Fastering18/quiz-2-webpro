// public/js/admin-panel.js - Admin Panel management logic
document.addEventListener("DOMContentLoaded", async function () {
    let currentUser = null;
    try {
        const raw = localStorage.getItem("user");
        if (raw) currentUser = JSON.parse(raw);
    } catch (e) {}

    if (!currentUser || currentUser.role !== "admin") {
        try {
            const meRes = await window.apiFetch("/api/me");
            currentUser = meRes?.user;
            if (currentUser) localStorage.setItem("user", JSON.stringify(currentUser));
        } catch (e) {}
    }

    if (currentUser?.role !== "admin") {
        window.location.href = "/portal";
        return;
    }

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

    // Tab switcher
    const tabButtons = document.querySelectorAll(".classroom-tab");
    const panels = {
        users: document.getElementById("panelUsers"),
        mk: document.getElementById("panelMk"),
        kelas: document.getElementById("panelKelas"),
        krs: document.getElementById("panelKrs"),
    };

    let activeTab = "users";
    function switchTab(tabId) {
        activeTab = tabId;
        tabButtons.forEach(b => {
            const isActive = b.dataset.tab === tabId;
            b.classList.toggle("is-active", isActive);
        });
        Object.keys(panels).forEach(k => {
            if (panels[k]) panels[k].classList.toggle("d-none", k !== tabId);
        });

        if (tabId === "users") loadUsers(1);
        if (tabId === "mk") loadMk(1);
        if (tabId === "kelas") loadKelas(1);
        if (tabId === "krs") loadKrs(1);
    }

    tabButtons.forEach(b => {
        b.addEventListener("click", () => switchTab(b.dataset.tab));
    });

    // 1. STATS OVERVIEW
    async function loadStats() {
        try {
            const res = await window.apiFetch("/api/admin/stats");
            const st = res?.stats || {};
            const usr = st.pengguna || {};

            document.getElementById("statTotalUsers").textContent = usr.total ?? "-";
            document.getElementById("statUserBreakdown").textContent = `${usr.mahasiswa || 0} Mhs • ${usr.dosen || 0} Dosen • ${usr.admin || 0} Admin`;
            document.getElementById("statTotalMk").textContent = st.mata_kuliah ?? "-";
            document.getElementById("statTotalKelas").textContent = st.kelas ?? "-";
            document.getElementById("statPendingKrs").textContent = st.krs?.menunggu ?? 0;
            document.getElementById("statTotalKrs").textContent = `Total ${st.krs?.total || 0} KRS`;
            document.getElementById("statOpenTickets").textContent = st.tiket?.terbuka ?? 0;
            document.getElementById("statTotalTickets").textContent = `Total ${st.tiket?.total || 0} Tiket`;
        } catch (err) {
            console.error("Gagal memuat stats:", err);
        }
    }

    // 2. KELOLA PENGGUNA (USERS)
    let usersCurrentPage = 1;
    let userSearchTimeout = null;

    async function loadUsers(page = 1) {
        usersCurrentPage = page;
        const tbody = document.getElementById("usersTableBody");
        const pagWrapper = document.getElementById("usersPagination");
        const q = document.getElementById("searchUserInput")?.value.trim() || "";
        const role = document.getElementById("filterUserRole")?.value || "";

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data pengguna...</td></tr>';

        try {
            const res = await window.apiFetch(`/api/admin/users?page=${page}&q=${encodeURIComponent(q)}&role=${encodeURIComponent(role)}`);
            const items = res?.data || [];

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Tidak ada pengguna yang cocok.</td></tr>';
                if (pagWrapper) pagWrapper.innerHTML = "";
                return;
            }

            tbody.innerHTML = items.map(u => {
                let info = "-";
                let roleBadgeCls = "badge-info";
                if (u.role === "mahasiswa") {
                    const m = u.mahasiswa || {};
                    const wali = m.dosen_wali?.user?.nama_lengkap ? `<br><small style="color:#64748B;">Wali: ${window.escapeHtml(m.dosen_wali.user.nama_lengkap)}</small>` : "";
                    info = `NRP: <strong>${window.escapeHtml(m.nrp || "-")}</strong> (${window.escapeHtml(m.departemen || "Informatika")})${wali}`;
                    roleBadgeCls = "badge-success";
                } else if (u.role === "dosen") {
                    const d = u.dosen || {};
                    info = `NIP: <strong>${window.escapeHtml(d.nip || "-")}</strong> (${window.escapeHtml(d.departemen || "Informatika")})`;
                    roleBadgeCls = "badge-warning";
                } else {
                    info = "Administrator Sistem";
                    roleBadgeCls = "badge-danger";
                }

                return `
                <tr>
                    <td><strong>${window.escapeHtml(u.username)}</strong></td>
                    <td>${window.escapeHtml(u.nama_lengkap)}</td>
                    <td><span class="status-badge ${roleBadgeCls}">${window.escapeHtml(u.role.toUpperCase())}</span></td>
                    <td>${info}</td>
                    <td>${window.escapeHtml(u.email)}</td>
                    <td style="text-align:right;">
                        <div class="action-btn-group" style="justify-content:flex-end;">
                            <button class="btn-action-small btn-action-secondary btn-reset-pw" data-id="${u.user_id}" data-name="${window.escapeHtml(u.nama_lengkap)}">
                                Reset PW
                            </button>
                            <button class="btn-action-small btn-action-danger btn-delete-user" data-id="${u.user_id}" data-name="${window.escapeHtml(u.nama_lengkap)}">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join("");

            // Wire action buttons
            tbody.querySelectorAll(".btn-reset-pw").forEach(b => {
                b.addEventListener("click", function () {
                    document.getElementById("resetUserId").value = this.dataset.id;
                    document.getElementById("resetUserName").textContent = this.dataset.name;
                    document.getElementById("inputNewPassword").value = "admin";
                    const fb = document.getElementById("feedbackReset");
                    if (fb) { fb.textContent = ""; fb.className = "classroom-feedback"; }
                    openModal("modalResetPassword");
                });
            });

            tbody.querySelectorAll(".btn-delete-user").forEach(b => {
                b.addEventListener("click", async function () {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    if (!confirm(`Apakah Anda yakin ingin menghapus akun "${name}"?`)) return;

                    try {
                        await window.apiFetch(`/api/admin/users/${id}`, { method: "DELETE" });
                        alert("Akun berhasil dihapus.");
                        loadStats();
                        loadUsers(usersCurrentPage);
                    } catch (err) {
                        alert(err.message || "Gagal menghapus pengguna.");
                    }
                });
            });

            renderPagination(pagWrapper, res, p => loadUsers(p));
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#B42318;">Gagal memuat pengguna: ${window.escapeHtml(err.message)}</td></tr>`;
        }
    }

    // Search and filter listeners for Users
    const searchUserInput = document.getElementById("searchUserInput");
    if (searchUserInput) {
        searchUserInput.addEventListener("input", function () {
            clearTimeout(userSearchTimeout);
            userSearchTimeout = setTimeout(() => loadUsers(1), 300);
        });
    }
    const filterUserRole = document.getElementById("filterUserRole");
    if (filterUserRole) {
        filterUserRole.addEventListener("change", () => loadUsers(1));
    }

    // Add User Modal Logic
    const btnOpenAddUser = document.getElementById("btnOpenModalAddUser");
    const inputUserRole = document.getElementById("inputUserRole");
    const fieldsMahasiswa = document.getElementById("fieldsMahasiswa");
    const fieldsDosen = document.getElementById("fieldsDosen");

    if (btnOpenAddUser) {
        btnOpenAddUser.addEventListener("click", async () => {
            // Load dosen options for dosen wali select
            try {
                const dRes = await window.apiFetch("/api/admin/dosen");
                const dosens = dRes?.dosen || [];
                const sel = document.getElementById("inputMhsWali");
                if (sel) {
                    sel.innerHTML = dosens.map(d => `<option value="${d.nip}">${window.escapeHtml(d.user?.nama_lengkap || d.nip)}</option>`).join("");
                }
            } catch (e) {}
            openModal("modalUser");
        });
    }

    if (inputUserRole) {
        inputUserRole.addEventListener("change", function () {
            const role = this.value;
            if (fieldsMahasiswa) fieldsMahasiswa.classList.toggle("d-none", role !== "mahasiswa");
            if (fieldsDosen) fieldsDosen.classList.toggle("d-none", role !== "dosen");
        });
    }

    // Form Add User Submit
    const formUser = document.getElementById("formUser");
    if (formUser) {
        formUser.addEventListener("submit", async function (e) {
            e.preventDefault();
            const fb = document.getElementById("feedbackUser");
            const btn = document.getElementById("btnSubmitUser");
            const role = document.getElementById("inputUserRole").value;

            const payload = {
                role: role,
                username: document.getElementById("inputUserUsername").value.trim(),
                nama_lengkap: document.getElementById("inputUserNama").value.trim(),
                email: document.getElementById("inputUserEmail").value.trim(),
                password: document.getElementById("inputUserPassword").value.trim() || "admin",
            };

            if (role === "mahasiswa") {
                payload.nrp = document.getElementById("inputMhsNrp").value.trim();
                payload.nip_wali = document.getElementById("inputMhsWali").value;
                payload.departemen = document.getElementById("inputMhsDept").value.trim() || "Informatika";
                payload.semester_aktif = parseInt(document.getElementById("inputMhsSem").value, 10) || 1;
            } else if (role === "dosen") {
                payload.nip = document.getElementById("inputDosenNip").value.trim();
                payload.departemen = document.getElementById("inputDosenDept").value.trim() || "Informatika";
            }

            if (btn) { btn.disabled = true; btn.textContent = "Menyimpan..."; }

            try {
                await window.apiFetch("/api/admin/users", {
                    method: "POST",
                    body: JSON.stringify(payload)
                });
                formUser.reset();
                closeModal("modalUser");
                alert("Pengguna baru berhasil ditambahkan!");
                loadStats();
                loadUsers(1);
            } catch (err) {
                if (fb) {
                    fb.textContent = err.message || "Gagal menambahkan pengguna.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Simpan Pengguna"; }
            }
        });
    }

    // Form Reset Password Submit
    const formReset = document.getElementById("formResetPassword");
    if (formReset) {
        formReset.addEventListener("submit", async function (e) {
            e.preventDefault();
            const id = document.getElementById("resetUserId").value;
            const pw = document.getElementById("inputNewPassword").value.trim();
            const fb = document.getElementById("feedbackReset");
            const btn = document.getElementById("btnSubmitReset");

            if (btn) { btn.disabled = true; btn.textContent = "Mereset..."; }

            try {
                await window.apiFetch(`/api/admin/users/${id}/password`, {
                    method: "PATCH",
                    body: JSON.stringify({ password: pw })
                });
                closeModal("modalResetPassword");
                alert("Password berhasil direset!");
            } catch (err) {
                if (fb) {
                    fb.textContent = err.message || "Gagal mereset password.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Reset Password"; }
            }
        });
    }

    // 3. TAB MATA KULIAH
    let mkCurrentPage = 1;
    let mkSearchTimeout = null;

    async function loadMk(page = 1) {
        mkCurrentPage = page;
        const tbody = document.getElementById("mkTableBody");
        const pagWrapper = document.getElementById("mkPagination");
        const q = document.getElementById("searchMkInput")?.value.trim() || "";

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data mata kuliah...</td></tr>';

        try {
            const res = await window.apiFetch(`/api/admin/mata-kuliah?page=${page}&q=${encodeURIComponent(q)}`);
            const items = res?.data || [];

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Tidak ada mata kuliah yang ditemukan.</td></tr>';
                if (pagWrapper) pagWrapper.innerHTML = "";
                return;
            }

            tbody.innerHTML = items.map(mk => `
                <tr>
                    <td><strong>${window.escapeHtml(mk.kode_mk)}</strong></td>
                    <td>${window.escapeHtml(mk.nama_mk)}</td>
                    <td><span class="status-badge badge-info">${mk.sks} SKS</span></td>
                    <td>Semester ${mk.semester}</td>
                    <td>${mk.classroom_count || 0} Kelas</td>
                    <td style="text-align:right;">
                        <button class="btn-action-small btn-action-danger btn-delete-mk" data-kode="${window.escapeHtml(mk.kode_mk)}" data-name="${window.escapeHtml(mk.nama_mk)}">
                            Hapus
                        </button>
                    </td>
                </tr>
            `).join("");

            tbody.querySelectorAll(".btn-delete-mk").forEach(b => {
                b.addEventListener("click", async function () {
                    const kode = this.dataset.kode;
                    const name = this.dataset.name;
                    if (!confirm(`Hapus mata kuliah "${name}" (${kode})?`)) return;

                    try {
                        await window.apiFetch(`/api/admin/mata-kuliah/${encodeURIComponent(kode)}`, { method: "DELETE" });
                        alert("Mata kuliah berhasil dihapus.");
                        loadStats();
                        loadMk(mkCurrentPage);
                    } catch (err) {
                        alert(err.message || "Gagal menghapus mata kuliah.");
                    }
                });
            });

            renderPagination(pagWrapper, res, p => loadMk(p));
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#B42318;">Gagal: ${window.escapeHtml(err.message)}</td></tr>`;
        }
    }

    const searchMkInput = document.getElementById("searchMkInput");
    if (searchMkInput) {
        searchMkInput.addEventListener("input", function () {
            clearTimeout(mkSearchTimeout);
            mkSearchTimeout = setTimeout(() => loadMk(1), 300);
        });
    }

    const btnOpenAddMk = document.getElementById("btnOpenModalAddMk");
    if (btnOpenAddMk) {
        btnOpenAddMk.addEventListener("click", () => openModal("modalMk"));
    }

    const formMk = document.getElementById("formMk");
    if (formMk) {
        formMk.addEventListener("submit", async function (e) {
            e.preventDefault();
            const fb = document.getElementById("feedbackMk");
            const btn = document.getElementById("btnSubmitMk");

            const payload = {
                kode_mk: document.getElementById("inputMkKode").value.trim(),
                nama_mk: document.getElementById("inputMkNama").value.trim(),
                sks: parseInt(document.getElementById("inputMkSks").value, 10),
                semester: parseInt(document.getElementById("inputMkSemester").value, 10),
            };

            if (btn) { btn.disabled = true; btn.textContent = "Menyimpan..."; }

            try {
                await window.apiFetch("/api/admin/mata-kuliah", {
                    method: "POST",
                    body: JSON.stringify(payload)
                });
                formMk.reset();
                closeModal("modalMk");
                alert("Mata kuliah berhasil ditambahkan!");
                loadStats();
                loadMk(1);
            } catch (err) {
                if (fb) {
                    fb.textContent = err.message || "Gagal menambahkan mata kuliah.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Simpan Mata Kuliah"; }
            }
        });
    }

    // 4. TAB KELAS PERKULIAHAN
    let kelasCurrentPage = 1;
    let kelasSearchTimeout = null;

    async function loadKelas(page = 1) {
        kelasCurrentPage = page;
        const tbody = document.getElementById("kelasTableBody");
        const pagWrapper = document.getElementById("kelasPagination");
        const q = document.getElementById("searchKelasInput")?.value.trim() || "";

        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#64748B;">Memuat kelas perkuliahan...</td></tr>';

        try {
            const res = await window.apiFetch(`/api/admin/kelas?page=${page}&q=${encodeURIComponent(q)}`);
            const items = res?.data || [];

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#64748B;">Tidak ada kelas perkuliahan.</td></tr>';
                if (pagWrapper) pagWrapper.innerHTML = "";
                return;
            }

            tbody.innerHTML = items.map(c => {
                const mk = c.mata_kuliah || {};
                const dsn = c.dosen?.user?.nama_lengkap || c.nip || "-";
                const enrolled = c.detail_krs_count ?? 0;

                return `
                <tr>
                    <td><code>${window.escapeHtml(c.kelas_id)}</code></td>
                    <td><strong>${window.escapeHtml(mk.nama_mk || c.kode_mk)}</strong> (${window.escapeHtml(c.kode_mk)})</td>
                    <td>${window.escapeHtml(c.nama_kelas)}</td>
                    <td>${window.escapeHtml(dsn)}</td>
                    <td>${window.escapeHtml(c.semester_tahun)}</td>
                    <td><span class="status-badge ${enrolled >= c.kuota_kelas ? "badge-danger" : "badge-success"}">${enrolled} / ${c.kuota_kelas}</span></td>
                    <td style="text-align:right;">
                        <button class="btn-action-small btn-action-danger btn-delete-kelas" data-id="${window.escapeHtml(c.kelas_id)}" data-name="${window.escapeHtml(c.nama_kelas)}">
                            Hapus
                        </button>
                    </td>
                </tr>`;
            }).join("");

            tbody.querySelectorAll(".btn-delete-kelas").forEach(b => {
                b.addEventListener("click", async function () {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    if (!confirm(`Hapus kelas "${name}" (${id})?`)) return;

                    try {
                        await window.apiFetch(`/api/admin/kelas/${encodeURIComponent(id)}`, { method: "DELETE" });
                        alert("Kelas berhasil dihapus.");
                        loadStats();
                        loadKelas(kelasCurrentPage);
                    } catch (err) {
                        alert(err.message || "Gagal menghapus kelas.");
                    }
                });
            });

            renderPagination(pagWrapper, res, p => loadKelas(p));
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:24px; color:#B42318;">Gagal: ${window.escapeHtml(err.message)}</td></tr>`;
        }
    }

    const searchKelasInput = document.getElementById("searchKelasInput");
    if (searchKelasInput) {
        searchKelasInput.addEventListener("input", function () {
            clearTimeout(kelasSearchTimeout);
            kelasSearchTimeout = setTimeout(() => loadKelas(1), 300);
        });
    }

    const btnOpenAddKelas = document.getElementById("btnOpenModalAddKelas");
    if (btnOpenAddKelas) {
        btnOpenAddKelas.addEventListener("click", async () => {
            // Load MK & Dosen options
            try {
                const [mkRes, dRes] = await Promise.all([
                    window.apiFetch("/api/admin/mata-kuliah?per_page=100"),
                    window.apiFetch("/api/admin/dosen")
                ]);

                const selMk = document.getElementById("selectKelasMk");
                if (selMk && mkRes?.data) {
                    selMk.innerHTML = mkRes.data.map(m => `<option value="${m.kode_mk}">${window.escapeHtml(m.nama_mk)} (${m.kode_mk}) - ${m.sks} SKS</option>`).join("");
                }

                const selDosen = document.getElementById("selectKelasDosen");
                if (selDosen && dRes?.dosen) {
                    selDosen.innerHTML = dRes.dosen.map(d => `<option value="${d.nip}">${window.escapeHtml(d.user?.nama_lengkap || d.nip)}</option>`).join("");
                }
            } catch (e) {}
            openModal("modalKelas");
        });
    }

    const formKelas = document.getElementById("formKelas");
    if (formKelas) {
        formKelas.addEventListener("submit", async function (e) {
            e.preventDefault();
            const fb = document.getElementById("feedbackKelas");
            const btn = document.getElementById("btnSubmitKelas");

            const payload = {
                kelas_id: document.getElementById("inputKelasId").value.trim(),
                kode_mk: document.getElementById("selectKelasMk").value,
                nip: document.getElementById("selectKelasDosen").value,
                nama_kelas: document.getElementById("inputKelasNama").value.trim(),
                semester_tahun: document.getElementById("inputKelasTahun").value.trim(),
                kuota_kelas: parseInt(document.getElementById("inputKelasKuota").value, 10),
            };

            if (btn) { btn.disabled = true; btn.textContent = "Menyimpan..."; }

            try {
                await window.apiFetch("/api/admin/kelas", {
                    method: "POST",
                    body: JSON.stringify(payload)
                });
                formKelas.reset();
                closeModal("modalKelas");
                alert("Kelas perkuliahan berhasil dibuat!");
                loadStats();
                loadKelas(1);
            } catch (err) {
                if (fb) {
                    fb.textContent = err.message || "Gagal membuat kelas.";
                    fb.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Simpan Kelas"; }
            }
        });
    }

    // 5. TAB KELOLA KRS
    let krsCurrentPage = 1;
    let krsSearchTimeout = null;

    async function loadKrs(page = 1) {
        krsCurrentPage = page;
        const tbody = document.getElementById("krsTableBody");
        const pagWrapper = document.getElementById("krsPagination");
        const nrp = document.getElementById("searchKrsInput")?.value.trim() || "";
        const status = document.getElementById("filterKrsStatus")?.value || "";

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data KRS...</td></tr>';

        try {
            const res = await window.apiFetch(`/api/admin/krs?page=${page}&nrp=${encodeURIComponent(nrp)}&status_acc=${encodeURIComponent(status)}`);
            const items = res?.data || [];

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Tidak ada KRS yang cocok.</td></tr>';
                if (pagWrapper) pagWrapper.innerHTML = "";
                return;
            }

            tbody.innerHTML = items.map(k => {
                const mhs = k.mahasiswa || {};
                const usr = mhs.user || {};
                const mhsName = usr.nama_lengkap || k.nrp;
                const details = k.detail_krs || [];
                const totalSks = details.reduce((sum, d) => sum + (d.classroom?.mataKuliah?.sks || 0), 0);

                let badgeCls = "badge-warning";
                if (k.status_acc === "Disetujui") badgeCls = "badge-success";
                if (k.status_acc === "Ditolak") badgeCls = "badge-danger";

                return `
                <tr>
                    <td><code>${window.escapeHtml(k.krs_id)}</code></td>
                    <td>
                        <strong>${window.escapeHtml(mhsName)}</strong>
                        <div style="font-size:12px; color:#64748B;">NRP: ${window.escapeHtml(k.nrp)}</div>
                    </td>
                    <td>Semester ${k.semester} (${window.escapeHtml(k.semester_tahun)})</td>
                    <td><strong>${details.length} Kelas</strong> (${totalSks} SKS)</td>
                    <td><span class="status-badge ${badgeCls}">${window.escapeHtml(k.status_acc)}</span></td>
                    <td style="text-align:right;">
                        <div class="action-btn-group" style="justify-content:flex-end;">
                            ${k.status_acc !== "Disetujui" ? `
                                <button class="btn-action-small btn-action-success btn-krs-status" data-id="${k.krs_id}" data-status="Disetujui">
                                    Setujui
                                </button>
                            ` : ""}
                            ${k.status_acc !== "Ditolak" ? `
                                <button class="btn-action-small btn-action-danger btn-krs-status" data-id="${k.krs_id}" data-status="Ditolak">
                                    Tolak
                                </button>
                            ` : ""}
                            ${k.status_acc !== "Menunggu" ? `
                                <button class="btn-action-small btn-action-secondary btn-krs-status" data-id="${k.krs_id}" data-status="Menunggu">
                                    Reset
                                </button>
                            ` : ""}
                        </div>
                    </td>
                </tr>`;
            }).join("");

            tbody.querySelectorAll(".btn-krs-status").forEach(b => {
                b.addEventListener("click", async function () {
                    const id = this.dataset.id;
                    const st = this.dataset.status;

                    try {
                        await window.apiFetch(`/api/admin/krs/${encodeURIComponent(id)}/status`, {
                            method: "PATCH",
                            body: JSON.stringify({ status_acc: st })
                        });
                        loadStats();
                        loadKrs(krsCurrentPage);
                    } catch (err) {
                        alert(err.message || "Gagal memperbarui status KRS.");
                    }
                });
            });

            renderPagination(pagWrapper, res, p => loadKrs(p));
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#B42318;">Gagal: ${window.escapeHtml(err.message)}</td></tr>`;
        }
    }

    const searchKrsInput = document.getElementById("searchKrsInput");
    if (searchKrsInput) {
        searchKrsInput.addEventListener("input", function () {
            clearTimeout(krsSearchTimeout);
            krsSearchTimeout = setTimeout(() => loadKrs(1), 300);
        });
    }
    const filterKrsStatus = document.getElementById("filterKrsStatus");
    if (filterKrsStatus) {
        filterKrsStatus.addEventListener("change", () => loadKrs(1));
    }

    // Shared Pagination Renderer
    function renderPagination(container, meta, onPageClick) {
        if (!container) return;
        if (!meta || meta.last_page <= 1) {
            container.innerHTML = "";
            return;
        }

        let html = "";
        if (meta.current_page > 1) {
            html += `<button class="page-btn" data-page="${meta.current_page - 1}">Sebelumnya</button>`;
        }
        for (let p = 1; p <= meta.last_page; p++) {
            html += `<button class="page-btn ${p === meta.current_page ? "active" : ""}" data-page="${p}">${p}</button>`;
        }
        if (meta.current_page < meta.last_page) {
            html += `<button class="page-btn" data-page="${meta.current_page + 1}">Berikutnya</button>`;
        }
        container.innerHTML = html;

        container.querySelectorAll(".page-btn").forEach(btn => {
            btn.addEventListener("click", function () {
                const page = parseInt(this.dataset.page, 10);
                if (page) onPageClick(page);
            });
        });
    }

    // Initial Load
    loadStats();
    loadUsers(1);
});
