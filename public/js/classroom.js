// public/js/classroom.js - Classroom logic for Mahasiswa & Dosen
// Optimized to prevent element flickering and support full Dosen content creation and grading.

document.addEventListener("DOMContentLoaded", async function () {
    const classroomApp = document.getElementById("classroomApp");
    if (!classroomApp) return;

    // DOM Elements
    const courseListContainer    = document.getElementById("courseList");
    const lessonEyebrow          = document.getElementById("lessonEyebrow");
    const lessonTitle            = document.getElementById("lessonTitle");
    const lessonMeta             = document.getElementById("lessonMeta");
    const materialsContent       = document.getElementById("materialsContent");
    const announcementsContent   = document.getElementById("announcementsContent");
    const tasksContent           = document.getElementById("tasksContent");
    const usersContent           = document.getElementById("usersContent");
    const materialsPanel         = document.getElementById("materialsPanel");
    const tasksPanel             = document.getElementById("tasksPanel");
    const usersPanel             = document.getElementById("usersPanel");
    const tabButtons             = document.querySelectorAll(".classroom-tab");
    const tabTasks               = document.getElementById("tabTasks");
    const tabUsers               = document.getElementById("tabUsers");
    const courseView             = document.getElementById("courseView");
    const announcementView       = document.getElementById("announcementView");
    const btnOpenAnnouncement    = document.getElementById("btnOpenAnnouncement");
    const btnCloseAnnouncement   = document.getElementById("btnCloseAnnouncement");
    const announcementBadge      = document.getElementById("announcementBadge");
    const annEyebrow             = document.getElementById("annEyebrow");
    const annTitle               = document.getElementById("annTitle");
    const annMeta                = document.getElementById("annMeta");
    const btnOpenModalSection    = document.getElementById("btnOpenModalSection");

    // Icons
    const fileIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" width="20" height="20"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h6"/></svg>`;
    const slideIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" width="20" height="20"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 9h10M7 12h6"/></svg>`;
    const uploadIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" width="28" height="28"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg>`;
    const bookIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H21v17H6.5A2.5 2.5 0 0 0 4 22z"/><path d="M4 5.5V22M8 7h9M8 11h9"/></svg>`;

    // State
    let currentUser = null;
    let isDosen = false;
    let classroomsData = [];
    let activeClassroomId = null;
    let currentDetail = null;
    let activeSectionId = null;
    let activeTab = "materials";
    let activeTaskId = null;
    let selectedFile = null;
    let taskComment = "";

    // Modal helpers
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

    // 1. Identify Role
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

    isDosen = currentUser?.role === "dosen";

    // Setup role UI adjustments
    if (isDosen) {
        if (tabTasks) tabTasks.textContent = "Tugas & Kiriman";
        if (tabUsers) tabUsers.classList.remove("d-none");
        if (btnOpenModalSection) {
            btnOpenModalSection.classList.remove("d-none");
            btnOpenModalSection.addEventListener("click", () => openModal("modalSection"));
        }
    }

    function getActiveSection() {
        if (!currentDetail || !currentDetail.bagian_kelas) return null;
        return currentDetail.bagian_kelas.find(b => b.bagian_id === activeSectionId) || currentDetail.bagian_kelas[0] || null;
    }

    function getAllTasks() {
        if (!currentDetail || !currentDetail.bagian_kelas) return [];
        const tasks = [];
        currentDetail.bagian_kelas.forEach(b => {
            if (b.tugas && b.tugas.length > 0) {
                b.tugas.forEach(t => tasks.push(t));
            }
        });
        return tasks;
    }

    function getActiveTask() {
        const tasks = getAllTasks();
        return tasks.find(t => t.tugas_id === activeTaskId) || tasks[0] || null;
    }

    function taskStatusInfo(task) {
        if (!task) return { status: "Tersedia", cls: "is-available" };
        if (isDosen) {
            const subCount = task.pengumpulan ? task.pengumpulan.length : 0;
            return { status: `${subCount} Kiriman`, cls: subCount > 0 ? "is-submitted" : "is-available" };
        }
        if (task.pengumpulan && task.pengumpulan.length > 0) {
            return { status: "Terkirim", cls: "is-submitted" };
        }
        if (task.tenggat_waktu && new Date(task.tenggat_waktu) < new Date()) {
            return { status: "Terlambat", cls: "is-late" };
        }
        return { status: "Tersedia", cls: "is-available" };
    }

    // Render course sidebar list ONCE to prevent image flickering
    function renderCourseListInitial() {
        if (!courseListContainer) return;
        if (classroomsData.length === 0) {
            courseListContainer.innerHTML = `<div style="padding:16px; font-size:13px; color:#64748B;">${isDosen ? "Belum ada kelas yang Anda ampu." : "Belum ada kelas yang terdaftar."}</div>`;
            return;
        }

        courseListContainer.innerHTML = classroomsData.map(function (c, idx) {
            const isActive = idx === 0;
            const mkName = c.mata_kuliah?.nama_mk || c.nama_kelas;
            const sks = c.mata_kuliah?.sks || 3;

            return `
            <section class="course-item ${isActive ? "is-active is-expanded" : ""}" data-course-id="${window.escapeHtml(c.kelas_id)}">
                <button class="course-toggle" type="button" aria-expanded="${isActive}">
                    <span class="course-icon" aria-hidden="true">${bookIcon}</span>
                    <span class="course-copy">
                        <span class="course-name">${window.escapeHtml(mkName)} (${window.escapeHtml(c.nama_kelas)})</span>
                        <span class="course-sks">${sks} SKS</span>
                    </span>
                    <svg class="course-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <ul class="lesson-list ${isActive ? "" : "d-none"}" id="lessons-${window.escapeHtml(c.kelas_id)}">
                    <!-- Bagian pertemuan diisi dinamis tanpa menghapus seluruh sidebar -->
                </ul>
            </section>`;
        }).join("");

        // Wire click handler on course toggle
        courseListContainer.querySelectorAll(".course-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const section = this.closest(".course-item");
                const cid = section?.dataset.courseId;
                if (cid && cid !== activeClassroomId) {
                    selectCourse(cid);
                }
            });
        });
    }

    // Update active course class in DOM without destroying sidebar HTML
    function updateCourseSidebarActiveState() {
        if (!courseListContainer) return;
        courseListContainer.querySelectorAll(".course-item").forEach(function (sec) {
            const isActive = sec.dataset.courseId === activeClassroomId;
            sec.classList.toggle("is-active", isActive);
            sec.classList.toggle("is-expanded", isActive);

            const toggle = sec.querySelector(".course-toggle");
            if (toggle) toggle.setAttribute("aria-expanded", String(isActive));

            const ul = sec.querySelector(".lesson-list");
            if (ul) ul.classList.toggle("d-none", !isActive);
        });
    }

    // Populate lesson list for current course
    function updateLessonList() {
        if (!activeClassroomId || !currentDetail) return;
        const ul = document.getElementById("lessons-" + activeClassroomId);
        if (!ul) return;

        const sections = currentDetail.bagian_kelas || [];
        if (sections.length === 0) {
            ul.innerHTML = '<li><span style="font-size:12px; color:#94A3B8; padding:8px 12px; display:block;">Belum ada pertemuan.</span></li>';
            return;
        }

        ul.innerHTML = sections.map(function (sec) {
            const isActive = sec.bagian_id === activeSectionId;
            return `
            <li>
                <button class="lesson-nav-button ${isActive ? "is-active" : ""}" type="button"
                    data-section-id="${sec.bagian_id}" ${isActive ? 'aria-current="page"' : ""}>
                    ${sec.urutan}. ${window.escapeHtml(sec.judul_bagian)}
                </button>
            </li>`;
        }).join("");

        ul.querySelectorAll(".lesson-nav-button").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const secId = parseInt(this.dataset.sectionId, 10);
                if (secId === activeSectionId) return;
                activeSectionId = secId;

                // Update active state in lesson buttons without recreating DOM
                ul.querySelectorAll(".lesson-nav-button").forEach(b => {
                    const active = parseInt(b.dataset.sectionId, 10) === activeSectionId;
                    b.classList.toggle("is-active", active);
                    if (active) b.setAttribute("aria-current", "page");
                    else b.removeAttribute("aria-current");
                });

                closeAnnouncementView();
                updateHeader();
                renderMaterials();
                if (activeTab === "tasks") renderTasks();
            });
        });
    }

    function updateHeader() {
        if (!currentDetail) return;
        const sec = getActiveSection();
        const mk = currentDetail.mata_kuliah || {};
        const sks = mk.sks || 3;
        const courseTitle = `${mk.nama_mk || currentDetail.nama_kelas} (${currentDetail.nama_kelas})`;

        if (lessonEyebrow) lessonEyebrow.textContent = sec ? `Bagian ${sec.urutan}` : "Kelas Perkuliahan";
        if (lessonTitle)   lessonTitle.textContent   = sec ? sec.judul_bagian : courseTitle;
        if (lessonMeta)    lessonMeta.innerHTML      = `${window.escapeHtml(courseTitle)} <span aria-hidden="true">•</span> ${sks} SKS`;

        // Announcement count badge
        const annCount = currentDetail.pengumuman ? currentDetail.pengumuman.length : 0;
        if (announcementBadge) {
            if (annCount > 0) {
                announcementBadge.textContent = annCount;
                announcementBadge.style.display = "";
            } else {
                announcementBadge.style.display = "none";
            }
        }
    }

    function renderMaterials() {
        if (!materialsContent) return;
        const sec = getActiveSection();
        if (!sec) {
            materialsContent.innerHTML = '<div class="classroom-card"><div class="classroom-empty-state">Belum ada bagian pertemuan yang dipilih. Klik "Tambah Pertemuan" untuk memulai.</div></div>';
            return;
        }

        const materiList = sec.materi || [];
        const sourcesHtml = materiList.map(function (m) {
            const isPpt = m.nama_file && m.nama_file.match(/\.(ppt|pptx)$/i);
            const isPdf = m.nama_file && m.nama_file.match(/\.pdf$/i);
            const icon = isPpt ? slideIcon : (isPdf ? fileIcon : "DOC");
            const iconClass = (!isPpt && !isPdf) ? "source-file-icon is-text" : "source-file-icon";
            const fileType = isPpt ? "PPT" : (isPdf ? "PDF" : "DOC");

            return `
            <div class="source-row">
                <span class="${iconClass}" aria-hidden="true">${icon}</span>
                <span class="source-copy">
                    <span class="source-name">${window.escapeHtml(m.judul_materi)}</span>
                    <span class="source-description">${window.escapeHtml(m.nama_file)} • ${window.formatFileSize(m.ukuran_file)}</span>
                </span>
                <span class="file-type-badge">${fileType}</span>
                ${m.file_materi_key ? `<a href="/storage/${m.file_materi_key}" download="${window.escapeHtml(m.nama_file)}" class="btn-primary-small" style="text-decoration:none; margin-left:auto;">Unduh</a>` : ""}
            </div>`;
        }).join("");

        const dosenTambahMateriBtn = isDosen ? `
            <button type="button" class="btn-primary-small" id="btnOpenModalMateri" style="font-size:12px; padding:7px 14px;">
                + Unggah Materi Baru
            </button>
        ` : "";

        materialsContent.innerHTML = `
            <div class="classroom-stack">
                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div><h2>Sumber Kuliah</h2><p>Dokumen dan materi perkuliahan untuk pertemuan ini.</p></div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="classroom-count">${materiList.length} Berkas</span>
                            ${dosenTambahMateriBtn}
                        </div>
                    </div>
                    <div class="source-list">
                        ${sourcesHtml || '<div class="classroom-empty-state">Belum ada berkas materi untuk pertemuan ini.</div>'}
                    </div>
                </section>
                <section class="classroom-card">
                    <h2 class="classroom-summary-title">${window.escapeHtml(sec.judul_ringkasan || sec.judul_bagian)}</h2>
                    <p class="classroom-summary-subtitle">Ringkasan Konsep Utama</p>
                    <p class="classroom-summary-copy" style="white-space:pre-line;">${window.escapeHtml(sec.konten_ringkasan || "Belum ada ringkasan materi untuk bagian perkuliahan ini.")}</p>
                </section>
            </div>`;

        if (isDosen) {
            const btnMateri = document.getElementById("btnOpenModalMateri");
            if (btnMateri) {
                btnMateri.addEventListener("click", function () {
                    populateSectionDropdowns();
                    openModal("modalMateri");
                });
            }
        }
    }

    // ==========================================
    // TAB: TUGAS (Assignments) - MAHASISWA & DOSEN
    // ==========================================
    function renderTasks() {
        if (!tasksContent) return;
        const tasks = getAllTasks();

        if (tasks.length === 0) {
            const tambahTugasDosen = isDosen ? `
                <div style="margin-top:16px;">
                    <button type="button" class="btn-primary-small" id="btnOpenModalTugasEmpty" style="font-size:13px; padding:9px 18px;">
                        + Buat Tugas Baru
                    </button>
                </div>
            ` : "";

            tasksContent.innerHTML = `
                <div class="classroom-card" style="text-align:center; padding:32px;">
                    <div class="classroom-empty-state">Belum ada tugas perkuliahan untuk kelas ini.</div>
                    ${tambahTugasDosen}
                </div>`;

            if (isDosen) {
                const btnEmpty = document.getElementById("btnOpenModalTugasEmpty");
                if (btnEmpty) {
                    btnEmpty.addEventListener("click", function () {
                        populateSectionDropdowns();
                        openModal("modalTugas");
                    });
                }
            }
            return;
        }

        if (!activeTaskId || !tasks.some(t => t.tugas_id === activeTaskId)) {
            activeTaskId = tasks[0].tugas_id;
        }

        const currentTask = getActiveTask();
        const currStatus = taskStatusInfo(currentTask);

        const taskCardsHtml = tasks.map(function (t) {
            const isSelected = t.tugas_id === activeTaskId;
            const st = taskStatusInfo(t);
            return `
            <button type="button" class="task-card ${isSelected ? "is-selected" : ""}"
                data-task-id="${t.tugas_id}" aria-pressed="${isSelected}">
                <span class="task-card-top">
                    <span class="task-code">Tugas #${t.urutan || t.tugas_id}</span>
                    <span class="task-status ${st.cls}">${window.escapeHtml(st.status)}</span>
                </span>
                <span class="task-card-title">${window.escapeHtml(t.judul_tugas)}</span>
                <span class="task-card-bottom">
                    <span class="task-due">Batas: ${window.formatDate(t.tenggat_waktu)}</span>
                    ${isSelected ? '<span class="task-selected-label">Dipilih</span>' : ""}
                </span>
            </button>`;
        }).join("");

        if (isDosen) {
            renderDosenTasksView(tasks, currentTask, currStatus, taskCardsHtml);
        } else {
            renderMahasiswaTasksView(tasks, currentTask, currStatus, taskCardsHtml);
        }
    }

    // Student Task View
    function renderMahasiswaTasksView(tasks, currentTask, currStatus, taskCardsHtml) {
        const mySub = currentTask?.pengumpulan?.[0];

        tasksContent.innerHTML = `
            <div class="classroom-stack">
                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div class="task-section-heading">
                            <h2>Daftar Tugas</h2>
                            <p>Pilih tugas untuk melihat detail dan mengunggah pekerjaan Anda.</p>
                        </div>
                        <span class="classroom-count">${tasks.length} tugas</span>
                    </div>
                    <div class="task-grid">${taskCardsHtml}</div>
                </section>

                <section class="classroom-card" aria-labelledby="taskDetailTitle">
                    <div class="task-detail-heading">
                        <div>
                            <h2 id="taskDetailTitle">${window.escapeHtml(currentTask.judul_tugas)}</h2>
                            <p class="task-deadline">Batas Pengumpulan: ${window.formatDate(currentTask.tenggat_waktu)} • 23:59 WIB</p>
                        </div>
                        <span class="task-status ${currStatus.cls}">${window.escapeHtml(currStatus.status)}</span>
                    </div>
                    <p class="task-description" style="white-space:pre-line;">${window.escapeHtml(currentTask.deskripsi_tugas || "Kerjakan tugas sesuai arahan dosen.")}</p>

                    ${mySub ? `
                        <div style="margin-top:16px; padding:14px; background:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:13px; font-weight:600; color:#166534;">Tugas Sudah Dikumpulkan</span>
                                <span style="font-size:12px; color:#15803D;">${window.formatDate(mySub.tanggal_kirim)}</span>
                            </div>
                            <div style="margin-top:6px; font-size:13px; color:#14532D;">
                                Berkas: <strong>${window.escapeHtml(mySub.nama_file)}</strong> (${window.formatFileSize(mySub.ukuran_file)})
                                ${mySub.nilai_tugas !== null ? `<span style="margin-left:12px; padding:2px 8px; background:#166534; color:white; border-radius:4px; font-weight:bold;">Nilai: ${mySub.nilai_tugas}/100</span>` : "<span style='margin-left:12px; color:#64748B;'>Belum dinilai</span>"}
                            </div>
                        </div>
                    ` : ""}
                </section>

                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div><h2>${mySub ? "Perbarui Kiriman Anda" : "Unggah Pekerjaan Anda"}</h2><p>Pilih file tugas yang akan dikumpulkan.</p></div>
                    </div>
                    <form class="classroom-form" id="taskSubmitForm" novalidate>
                        <div class="classroom-field">
                            <label for="taskFileInput">Berkas Tugas (${window.escapeHtml(currentTask.format_file || "Semua format")})</label>
                            <input id="taskFileInput" type="file" style="display:none;">
                            <label class="classroom-dropzone" id="taskDropzone" for="taskFileInput" tabindex="0">
                                ${uploadIcon}
                                <span class="dropzone-text">
                                    <strong>Seret dan lepas file tugas Anda di sini</strong><br>
                                    atau klik untuk memilih berkas dari komputer
                                </span>
                            </label>
                            <div id="selectedFileContainer"></div>
                        </div>
                        <div class="classroom-field">
                            <label for="taskComment">Komentar / Catatan tambahan (opsional)</label>
                            <textarea class="classroom-textarea" id="taskComment"
                                placeholder="Tambahkan catatan untuk dosen...">${window.escapeHtml(taskComment)}</textarea>
                        </div>
                        <p class="classroom-feedback" id="taskFeedback" role="status" aria-live="polite"></p>
                        <div class="classroom-form-actions">
                            <button type="button" class="classroom-button classroom-button-secondary" id="cancelTaskButton">Batal</button>
                            <button type="submit" class="classroom-button classroom-button-primary" id="submitTaskButton">${mySub ? "Perbarui Kiriman" : "Kirim Tugas"}</button>
                        </div>
                    </form>
                </section>
            </div>`;

        wireStudentTaskForm();
    }

    // Dosen Task View - See submissions, grade students, and manage assignment
    function renderDosenTasksView(tasks, currentTask, currStatus, taskCardsHtml) {
        const submissions = currentTask.pengumpulan || [];

        const submissionsHtml = submissions.length === 0
            ? '<div style="padding:24px; text-align:center; color:#64748B; font-size:13px;">Belum ada mahasiswa yang mengumpulkan tugas ini.</div>'
            : submissions.map(function (sub, idx) {
                const mhs = sub.mahasiswa || {};
                const mhsUser = mhs.user || {};
                const mhsName = mhsUser.nama_lengkap || mhs.nrp || "Mahasiswa";
                const isGraded = sub.nilai_tugas !== null;
                const submitId = sub.submit_id; // Primary key column on model!

                return `
                <div class="history-card active" style="margin-bottom:12px; border:1px solid #E2E8F0;" data-submission-id="${submitId}">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-weight:700; color:#0F172A; font-size:14px;">${idx + 1}. ${window.escapeHtml(mhsName)}</span>
                            <span style="font-size:12px; color:#64748B; margin-left:8px;">NRP: ${window.escapeHtml(mhs.nrp)}</span>
                        </div>
                        <span class="status-badge ${isGraded ? "badge-success" : "badge-warning"}" id="grade-badge-${submitId}">
                            ${isGraded ? `Nilai: ${sub.nilai_tugas}/100` : "Belum Dinilai"}
                        </span>
                    </div>

                    <div style="margin-top:10px; font-size:13px; color:#334155;">
                        <div>
                            <strong>Berkas:</strong>
                            <a href="/storage/${sub.file_jawaban_key}" download="${window.escapeHtml(sub.nama_file)}" class="link-blue" target="_blank" style="margin-left:4px; font-weight:600;">
                                ${window.escapeHtml(sub.nama_file)} (${window.formatFileSize(sub.ukuran_file)})
                            </a>
                            <span style="font-size:11px; color:#64748B; margin-left:8px;">Dikirim: ${window.formatDate(sub.tanggal_kirim)}</span>
                        </div>
                        ${sub.komentar ? `<div style="margin-top:4px; color:#475569;"><em>Catatan Mahasiswa: "${window.escapeHtml(sub.komentar)}"</em></div>` : ""}
                    </div>

                    <hr class="divider" style="margin:12px 0;">

                    <!-- Form Input Nilai & Feedback Dosen -->
                    <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                        <div style="width:110px;">
                            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Nilai (0-100)</label>
                            <input type="number" min="0" max="100" class="form-input" style="padding:6px 10px;" id="input-nilai-${submitId}" value="${sub.nilai_tugas ?? ""}" placeholder="0-100">
                        </div>
                        <div style="flex:1; min-width:200px;">
                            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:4px;">Komentar Dosen (Opsional)</label>
                            <input type="text" class="form-input" style="padding:6px 10px;" id="input-komentar-${submitId}" value="${window.escapeHtml(sub.komentar || "")}" placeholder="Beri catatan evaluasi tugas...">
                        </div>
                        <button type="button" class="btn-primary-small btn-simpan-nilai" data-sub-id="${submitId}" style="padding:8px 16px;">
                            Simpan Nilai
                        </button>
                    </div>
                    <div id="grade-feedback-${submitId}" style="font-size:12px; margin-top:6px;"></div>
                </div>`;
            }).join("");

        tasksContent.innerHTML = `
            <div class="classroom-stack">
                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div class="task-section-heading">
                            <h2>Manajemen Tugas Kelas</h2>
                            <p>Pilih tugas untuk memeriksa kiriman mahasiswa dan memberikan nilai.</p>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="classroom-count">${tasks.length} Tugas Dosen</span>
                            <button type="button" class="btn-primary-small" id="btnOpenModalTugas" style="font-size:12px; padding:7px 14px;">
                                + Buat Tugas Baru
                            </button>
                        </div>
                    </div>
                    <div class="task-grid">${taskCardsHtml}</div>
                </section>

                <section class="classroom-card">
                    <div class="task-detail-heading">
                        <div>
                            <h2>${window.escapeHtml(currentTask.judul_tugas)}</h2>
                            <p class="task-deadline">Batas: ${window.formatDate(currentTask.tenggat_waktu)} • Format: ${window.escapeHtml(currentTask.format_file || "Bebas")}</p>
                        </div>
                        <span class="task-status is-submitted">${submissions.length} Pengumpulan</span>
                    </div>
                    <p class="task-description" style="white-space:pre-line;">${window.escapeHtml(currentTask.deskripsi_tugas || "Instruksi tugas perkuliahan.")}</p>
                </section>

                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div>
                            <h2>Kiriman Tugas Mahasiswa</h2>
                            <p>Evaluasi berkas mahasiswa dan masukkan nilai langsung.</p>
                        </div>
                        <span class="classroom-count">${submissions.length} Kiriman</span>
                    </div>
                    <div style="margin-top:16px;">${submissionsHtml}</div>
                </section>
            </div>`;

        // Wire create task button
        const btnTugas = document.getElementById("btnOpenModalTugas");
        if (btnTugas) {
            btnTugas.addEventListener("click", function () {
                populateSectionDropdowns();
                openModal("modalTugas");
            });
        }

        // Wire grading buttons
        tasksContent.querySelectorAll(".btn-simpan-nilai").forEach(function (btn) {
            btn.addEventListener("click", async function () {
                const subId = this.dataset.subId;
                const inputNilai = document.getElementById(`input-nilai-${subId}`);
                const inputKomentar = document.getElementById(`input-komentar-${subId}`);
                const feedbackEl = document.getElementById(`grade-feedback-${subId}`);
                const badgeEl = document.getElementById(`grade-badge-${subId}`);

                const valNilai = parseInt(inputNilai?.value, 10);
                if (isNaN(valNilai) || valNilai < 0 || valNilai > 100) {
                    if (feedbackEl) {
                        feedbackEl.textContent = "Nilai harus berupa angka antara 0 hingga 100.";
                        feedbackEl.style.color = "#B42318";
                    }
                    return;
                }

                this.disabled = true;
                this.textContent = "Menyimpan...";

                try {
                    await window.apiFetch(`/api/submissions/${subId}/grade`, {
                        method: "POST",
                        body: JSON.stringify({
                            nilai_tugas: valNilai,
                            komentar: inputKomentar?.value || null
                        })
                    });

                    if (badgeEl) {
                        badgeEl.className = "status-badge badge-success";
                        badgeEl.textContent = `Nilai: ${valNilai}/100`;
                    }
                    if (feedbackEl) {
                        feedbackEl.textContent = "Nilai berhasil disimpan!";
                        feedbackEl.style.color = "#166534";
                    }
                } catch (err) {
                    if (feedbackEl) {
                        feedbackEl.textContent = err.message || "Gagal menyimpan nilai.";
                        feedbackEl.style.color = "#B42318";
                    }
                } finally {
                    this.disabled = false;
                    this.textContent = "Simpan Nilai";
                }
            });
        });
    }

    function wireStudentTaskForm() {
        const form         = document.getElementById("taskSubmitForm");
        const input        = document.getElementById("taskFileInput");
        const dropzone     = document.getElementById("taskDropzone");
        const commentField = document.getElementById("taskComment");
        const cancelButton = document.getElementById("cancelTaskButton");
        const submitBtn    = document.getElementById("submitTaskButton");

        if (input) {
            input.addEventListener("change", function () {
                if (input.files && input.files[0]) {
                    selectedFile = input.files[0];
                    renderSelectedFile();
                }
            });
        }

        if (dropzone && input) {
            ["dragenter", "dragover"].forEach(ev => {
                dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add("is-dragover"); });
            });
            ["dragleave", "drop"].forEach(ev => {
                dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove("is-dragover"); });
            });
            dropzone.addEventListener("drop", function (e) {
                const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (file) {
                    selectedFile = file;
                    renderSelectedFile();
                }
            });
        }

        if (commentField) {
            commentField.addEventListener("input", function () { taskComment = commentField.value; });
        }

        if (cancelButton) {
            cancelButton.addEventListener("click", function () {
                selectedFile = null; taskComment = "";
                if (input) input.value = "";
                if (commentField) commentField.value = "";
                renderSelectedFile();
                const fb = document.getElementById("taskFeedback");
                if (fb) { fb.textContent = ""; fb.className = "classroom-feedback"; }
            });
        }

        if (form) {
            form.addEventListener("submit", async function (e) {
                e.preventDefault();
                const fb = document.getElementById("taskFeedback");
                if (!selectedFile) {
                    if (fb) {
                        fb.textContent = "Pilih file tugas terlebih dahulu sebelum mengirim.";
                        fb.className = "classroom-feedback is-visible is-error";
                    }
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Mengirim...";
                }

                try {
                    const formData = new FormData();
                    formData.append("file", selectedFile);
                    if (taskComment) formData.append("komentar", taskComment);

                    await window.apiFetch(`/api/tasks/${activeTaskId}/submit`, {
                        method: "POST",
                        body: formData
                    });

                    selectedFile = null;
                    taskComment = "";
                    if (fb) {
                        fb.textContent = "Tugas berhasil dikirim ke dosen!";
                        fb.className = "classroom-feedback is-visible is-success";
                    }

                    // Reload classroom detail quietly
                    const refreshed = await window.apiFetch(`/api/classrooms/${activeClassroomId}`);
                    if (refreshed?.classroom) {
                        currentDetail = refreshed.classroom;
                        renderTasks();
                    }
                } catch (err) {
                    if (fb) {
                        fb.textContent = err.message || "Gagal mengumpulkan tugas.";
                        fb.className = "classroom-feedback is-visible is-error";
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Kirim Tugas";
                    }
                }
            });
        }
    }

    function renderSelectedFile() {
        const container = document.getElementById("selectedFileContainer");
        if (!container) return;
        if (!selectedFile) { container.innerHTML = ""; return; }
        container.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon" aria-hidden="true">FILE</span>
                <span class="selected-file-copy">
                    <span class="selected-file-name">${window.escapeHtml(selectedFile.name)}</span>
                    <span class="selected-file-size">${window.formatFileSize(selectedFile.size)}</span>
                </span>
                <button class="remove-file-button" type="button" id="removeSelectedFile">Hapus</button>
            </div>`;

        const removeButton = document.getElementById("removeSelectedFile");
        if (removeButton) {
            removeButton.addEventListener("click", function () {
                selectedFile = null;
                const input = document.getElementById("taskFileInput");
                if (input) input.value = "";
                renderSelectedFile();
            });
        }
    }

    // ==========================================
    // TAB: USERS (MAHASISWA TERDAFTAR) - DOSEN
    // ==========================================
    function renderUsers() {
        if (!usersContent || !currentDetail) return;
        const details = currentDetail.detail_krs || [];

        if (details.length === 0) {
            usersContent.innerHTML = `
                <div class="classroom-card">
                    <div class="classroom-empty-state">Belum ada mahasiswa yang mengambil kelas ini.</div>
                </div>`;
            return;
        }

        const rowsHtml = details.map(function (d, idx) {
            const krs = d.krs || {};
            const mhs = krs.mahasiswa || {};
            const u = mhs.user || {};
            const nama = u.nama_lengkap || mhs.nrp || "-";
            const nrp  = mhs.nrp || "-";
            const dept = mhs.departemen || "Informatika";
            const sem  = mhs.semester_aktif || krs.semester || "-";
            const status = mhs.status || "Aktif";

            return `
            <div class="krs-table-row">
                <div style="width:40px; font-weight:600; color:#64748B;">${idx + 1}</div>
                <div class="col-kode">${window.escapeHtml(nrp)}</div>
                <div class="col-nama" style="font-weight:600; color:#0F172A;">${window.escapeHtml(nama)}</div>
                <div class="col-kelas">${window.escapeHtml(dept)}</div>
                <div class="col-sks">Sem. ${sem}</div>
                <div style="width:80px; text-align:right;">
                    <span class="status-badge badge-success">${window.escapeHtml(status)}</span>
                </div>
            </div>`;
        }).join("");

        usersContent.innerHTML = `
            <section class="classroom-card">
                <div class="classroom-card-heading">
                    <div>
                        <h2>Mahasiswa Terdaftar</h2>
                        <p>Daftar seluruh peserta mata kuliah di kelas ini.</p>
                    </div>
                    <span class="classroom-count">${details.length} Mahasiswa</span>
                </div>
                <div class="krs-table" style="margin-top:16px;">
                    <div class="krs-table-header">
                        <div style="width:40px;">No.</div>
                        <div class="col-kode">NRP</div>
                        <div class="col-nama">Nama Lengkap</div>
                        <div class="col-kelas">Departemen</div>
                        <div class="col-sks">Semester</div>
                        <div style="width:80px; text-align:right;">Status</div>
                    </div>
                    <div id="usersTableRows">${rowsHtml}</div>
                </div>
            </section>`;
    }

    // ==========================================
    // ANNOUNCEMENTS VIEW
    // ==========================================
    function openAnnouncementView() {
        if (!currentDetail) return;
        const courseTitle = `${currentDetail.mata_kuliah?.nama_mk || currentDetail.nama_kelas} (${currentDetail.nama_kelas})`;

        if (annEyebrow) annEyebrow.textContent = "Pengumuman Kelas";
        if (annTitle)   annTitle.textContent   = "Pengumuman — " + courseTitle;
        if (annMeta)    annMeta.innerHTML      = window.escapeHtml(courseTitle);

        renderAnnouncements();
        if (courseView)       courseView.classList.add("d-none");
        if (announcementView) announcementView.classList.remove("d-none");
    }

    function closeAnnouncementView() {
        if (announcementView) announcementView.classList.add("d-none");
        if (courseView)       courseView.classList.remove("d-none");
    }

    function renderAnnouncements() {
        if (!announcementsContent || !currentDetail) return;
        const annList = currentDetail.pengumuman || [];

        const formDosenHtml = isDosen ? `
            <div class="classroom-card" style="margin-bottom:20px;">
                <h3 style="font-size:16px; font-weight:700; margin-bottom:12px; color:#0F172A;">Buat Pengumuman Baru</h3>
                <form id="formPostAnnouncement" novalidate>
                    <div class="form-group" style="margin-bottom:12px;">
                        <textarea id="inputKontenPengumuman" class="form-textarea" rows="3" placeholder="Tulis pengumuman kelas untuk mahasiswa..." required></textarea>
                    </div>
                    <p class="classroom-feedback" id="feedbackAnnouncement" role="status" aria-live="polite"></p>
                    <button type="submit" class="btn-primary-small" id="btnSubmitPengumuman">Posting Pengumuman</button>
                </form>
            </div>
        ` : "";

        const itemsHtml = annList.length === 0
            ? '<div class="classroom-card"><div class="classroom-empty-state">Belum ada pengumuman untuk kelas ini.</div></div>'
            : annList.map(function (ann, index) {
                const isOpen = index === 0;
                return `
                <div class="announcement-item ${isOpen ? "is-open" : ""}">
                    <button class="announcement-header" type="button" aria-expanded="${isOpen}">
                        <div class="announcement-header-left">
                            <span class="announcement-dot ${isOpen ? "is-new" : ""}"></span>
                            <div>
                                <span class="announcement-title">Pengumuman Perkuliahan</span>
                                <span class="announcement-date">${window.formatDate(ann.tanggal_unggah)}</span>
                            </div>
                        </div>
                        <svg class="announcement-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="announcement-body ${isOpen ? "" : "d-none"}">
                        <p class="announcement-text" style="white-space:pre-line;">${window.escapeHtml(ann.konten)}</p>
                    </div>
                </div>`;
            }).join("");

        announcementsContent.innerHTML = `
            ${formDosenHtml}
            <section class="classroom-card">
                <div class="classroom-card-heading">
                    <div>
                        <h2>Daftar Pengumuman</h2>
                        <p>Klik pada judul untuk membaca detail pengumuman.</p>
                    </div>
                    <span class="classroom-count">${annList.length} Pengumuman</span>
                </div>
                <div class="announcement-list">${itemsHtml}</div>
            </section>`;

        // Wire accordion
        announcementsContent.querySelectorAll(".announcement-header").forEach(function (header) {
            header.addEventListener("click", function () {
                const item = this.closest(".announcement-item");
                const body = item?.querySelector(".announcement-body");
                const isOpen = item?.classList.contains("is-open");
                if (item && body) {
                    item.classList.toggle("is-open", !isOpen);
                    body.classList.toggle("d-none", isOpen);
                    this.setAttribute("aria-expanded", String(!isOpen));
                }
            });
        });

        // Wire dosen announcement post
        const formAnn = document.getElementById("formPostAnnouncement");
        if (formAnn) {
            formAnn.addEventListener("submit", async function (e) {
                e.preventDefault();
                const txt = document.getElementById("inputKontenPengumuman");
                const fb = document.getElementById("feedbackAnnouncement");
                const btn = document.getElementById("btnSubmitPengumuman");

                if (!txt || !txt.value.trim()) return;

                if (btn) { btn.disabled = true; btn.textContent = "Memposting..."; }

                try {
                    await window.apiFetch(`/api/classrooms/${activeClassroomId}/announcements`, {
                        method: "POST",
                        body: JSON.stringify({ konten: txt.value.trim() })
                    });
                    txt.value = "";
                    // Refresh data
                    const refreshed = await window.apiFetch(`/api/classrooms/${activeClassroomId}`);
                    if (refreshed?.classroom) {
                        currentDetail = refreshed.classroom;
                        updateHeader();
                        renderAnnouncements();
                    }
                } catch (err) {
                    if (fb) {
                        fb.textContent = err.message || "Gagal memposting pengumuman.";
                        fb.className = "classroom-feedback is-visible is-error";
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.textContent = "Posting Pengumuman"; }
                }
            });
        }
    }

    // Populate dropdowns in modals with current sections
    function populateSectionDropdowns() {
        const sections = currentDetail?.bagian_kelas || [];
        const opts = sections.map(s => `<option value="${s.bagian_id}" ${s.bagian_id === activeSectionId ? "selected" : ""}>${s.urutan}. ${window.escapeHtml(s.judul_bagian)}</option>`).join("");

        const selMateri = document.getElementById("selectMateriSection");
        const selTugas  = document.getElementById("selectTugasSection");
        if (selMateri) selMateri.innerHTML = opts;
        if (selTugas)  selTugas.innerHTML  = opts;
    }

    // Wire Dosen Modal Form Submissions
    // 1. Tambah Section
    const formSection = document.getElementById("formSection");
    if (formSection) {
        formSection.addEventListener("submit", async function (e) {
            e.preventDefault();
            const judul = document.getElementById("inputSectionNama")?.value.trim();
            const subjudul = document.getElementById("inputSectionRingkasanJudul")?.value.trim();
            const konten = document.getElementById("inputSectionRingkasanKonten")?.value.trim();
            const fb = document.getElementById("feedbackSection");
            const btn = document.getElementById("btnSubmitSection");

            if (!judul) {
                if (fb) { fb.textContent = "Judul pertemuan wajib diisi."; fb.className = "classroom-feedback is-visible is-error"; }
                return;
            }

            if (btn) { btn.disabled = true; btn.textContent = "Menyimpan..."; }

            try {
                await window.apiFetch(`/api/classrooms/${activeClassroomId}/sections`, {
                    method: "POST",
                    body: JSON.stringify({
                        judul_bagian: judul,
                        judul_ringkasan: subjudul || null,
                        konten_ringkasan: konten || null
                    })
                });

                formSection.reset();
                closeModal("modalSection");

                // Refresh classroom
                const refreshed = await window.apiFetch(`/api/classrooms/${activeClassroomId}`);
                if (refreshed?.classroom) {
                    currentDetail = refreshed.classroom;
                    updateLessonList();
                    updateHeader();
                }
            } catch (err) {
                if (fb) { fb.textContent = err.message || "Gagal menambah pertemuan."; fb.className = "classroom-feedback is-visible is-error"; }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Simpan Pertemuan"; }
            }
        });
    }

    // 2. Unggah Materi
    const formMateri = document.getElementById("formMateri");
    if (formMateri) {
        formMateri.addEventListener("submit", async function (e) {
            e.preventDefault();
            const secSelect = document.getElementById("selectMateriSection");
            const judul = document.getElementById("inputMateriJudul")?.value.trim();
            const fileInput = document.getElementById("inputMateriFile");
            const fb = document.getElementById("feedbackMateri");
            const btn = document.getElementById("btnSubmitMateri");

            const bagianId = secSelect?.value;
            if (!bagianId || !judul) {
                if (fb) { fb.textContent = "Pilih pertemuan dan isi judul materi."; fb.className = "classroom-feedback is-visible is-error"; }
                return;
            }

            if (btn) { btn.disabled = true; btn.textContent = "Mengunggah..."; }

            try {
                const formData = new FormData();
                formData.append("judul_materi", judul);
                if (fileInput?.files?.[0]) {
                    formData.append("file", fileInput.files[0]);
                }

                await window.apiFetch(`/api/sections/${bagianId}/materials`, {
                    method: "POST",
                    body: formData
                });

                formMateri.reset();
                closeModal("modalMateri");

                // Refresh classroom
                const refreshed = await window.apiFetch(`/api/classrooms/${activeClassroomId}`);
                if (refreshed?.classroom) {
                    currentDetail = refreshed.classroom;
                    renderMaterials();
                }
            } catch (err) {
                if (fb) { fb.textContent = err.message || "Gagal mengunggah materi."; fb.className = "classroom-feedback is-visible is-error"; }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Unggah Materi"; }
            }
        });
    }

    // 3. Buat Tugas
    const formTugas = document.getElementById("formTugas");
    if (formTugas) {
        formTugas.addEventListener("submit", async function (e) {
            e.preventDefault();
            const secSelect = document.getElementById("selectTugasSection");
            const judul = document.getElementById("inputTugasJudul")?.value.trim();
            const deskripsi = document.getElementById("inputTugasDeskripsi")?.value.trim();
            const format = document.getElementById("inputTugasFormat")?.value.trim();
            const due = document.getElementById("inputTugasDue")?.value;
            const fb = document.getElementById("feedbackTugas");
            const btn = document.getElementById("btnSubmitTugas");

            const bagianId = secSelect?.value;
            if (!bagianId || !judul || !due) {
                if (fb) { fb.textContent = "Lengkapi pertemuan, judul tugas, dan tenggat waktu."; fb.className = "classroom-feedback is-visible is-error"; }
                return;
            }

            if (btn) { btn.disabled = true; btn.textContent = "Menerbitkan..."; }

            try {
                await window.apiFetch(`/api/sections/${bagianId}/tasks`, {
                    method: "POST",
                    body: JSON.stringify({
                        judul_tugas: judul,
                        deskripsi_tugas: deskripsi || null,
                        format_file: format || ".c, .zip, .pdf",
                        tenggat_waktu: due
                    })
                });

                formTugas.reset();
                closeModal("modalTugas");

                // Refresh classroom
                const refreshed = await window.apiFetch(`/api/classrooms/${activeClassroomId}`);
                if (refreshed?.classroom) {
                    currentDetail = refreshed.classroom;
                    renderTasks();
                }
            } catch (err) {
                if (fb) { fb.textContent = err.message || "Gagal membuat tugas."; fb.className = "classroom-feedback is-visible is-error"; }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = "Terbitkan Tugas"; }
            }
        });
    }

    // ==========================================
    // SELECT COURSE
    // ==========================================
    async function selectCourse(courseId) {
        activeClassroomId = courseId;
        updateCourseSidebarActiveState();

        try {
            const res = await window.apiFetch(`/api/classrooms/${courseId}`);
            if (!res || !res.classroom) return;
            currentDetail = res.classroom;

            const sections = currentDetail.bagian_kelas || [];
            activeSectionId = sections.length > 0 ? sections[0].bagian_id : null;
            selectedFile = null;
            taskComment = "";

            closeAnnouncementView();
            updateCourseSidebarActiveState();
            updateLessonList();
            updateHeader();
            renderMaterials();
            if (activeTab === "tasks") renderTasks();
            if (activeTab === "users") renderUsers();
        } catch (err) {
            console.error("Gagal memuat detail kelas:", err);
        }
    }

    // Tab Switching
    function activateTab(tabName) {
        activeTab = tabName;
        tabButtons.forEach(function (btn) {
            const isActive = btn.dataset.tab === activeTab;
            btn.classList.toggle("is-active", isActive);
            btn.setAttribute("aria-selected", String(isActive));
        });

        if (materialsPanel) materialsPanel.classList.toggle("d-none", activeTab !== "materials");
        if (tasksPanel)     tasksPanel.classList.toggle("d-none",     activeTab !== "tasks");
        if (usersPanel)     usersPanel.classList.toggle("d-none",     activeTab !== "users");

        if (activeTab === "tasks") renderTasks();
        if (activeTab === "users") renderUsers();
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener("click", function () { activateTab(btn.dataset.tab); });
    });

    if (btnOpenAnnouncement) btnOpenAnnouncement.addEventListener("click", openAnnouncementView);
    if (btnCloseAnnouncement) btnCloseAnnouncement.addEventListener("click", closeAnnouncementView);

    // Delegate task click
    if (tasksContent) {
        tasksContent.addEventListener("click", function (e) {
            const taskBtn = e.target.closest("[data-task-id]");
            if (taskBtn) {
                activeTaskId = parseInt(taskBtn.dataset.taskId, 10);
                selectedFile = null;
                taskComment = "";
                renderTasks();
            }
        });
    }

    // Initial fetch of classrooms
    try {
        const data = await window.apiFetch("/api/classrooms");
        classroomsData = data?.classrooms || [];
        renderCourseListInitial();
        if (classroomsData.length > 0) {
            selectCourse(classroomsData[0].kelas_id);
        }
    } catch (err) {
        console.error("Gagal mengambil daftar kelas:", err);
    }
});
