// dashboard/dashboard.js
// Semua logika interaktivitas halaman dashboard mahasiswa.
// JS hanya bertugas: inject data (simulasi API) + event listener.
// Pembuatan DOM struktural ada di file HTML masing-masing.

document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // 1. LOGIKA DROPDOWN MENU TOPBAR
    // ==========================================
    const menuTrigger = document.getElementById("menuTrigger");
    const dropdownMenu = document.getElementById("dropdownMenu");

    if (menuTrigger && dropdownMenu) {
        menuTrigger.addEventListener("click", function (e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle("d-none");
        });
        document.addEventListener("click", function (e) {
            if (!dropdownMenu.contains(e.target) && !menuTrigger.contains(e.target)) {
                dropdownMenu.classList.add("d-none");
            }
        });
    }

    // ==========================================
    // 2. LOGIKA CUSTOM SELECT DROPDOWN (LAPOR ADMIN)
    // ==========================================
    const selectWrapper = document.getElementById("kategoriSelect");
    const selectTrigger = document.querySelector(".custom-select-trigger");
    const selectOptions = document.querySelector(".custom-select-options");
    const selectText = document.getElementById("kategoriText");
    const optionItems = document.querySelectorAll(".option-item");

    if (selectWrapper && selectTrigger && selectOptions) {
        selectTrigger.addEventListener("click", function (e) {
            e.stopPropagation();
            selectWrapper.classList.toggle("open");
            selectOptions.classList.toggle("d-none");
        });
        optionItems.forEach(function (item) {
            item.addEventListener("click", function () {
                optionItems.forEach(function (opt) { opt.classList.remove("active"); });
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

    // ==========================================
    // 3. LOGIKA EXPANDABLE HISTORY CARD (LAPOR ADMIN)
    // ==========================================
    const historyHeaders = document.querySelectorAll(".history-card-header");
    if (historyHeaders.length > 0) {
        historyHeaders.forEach(function (header) {
            header.addEventListener("click", function () {
                const card = this.closest(".history-card");
                if (card) card.classList.toggle("active");
            });
        });
    }

    const btnLihatSemua = document.getElementById("btnLihatSemua");
    if (btnLihatSemua) {
        btnLihatSemua.addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelectorAll(".history-card").forEach(function (card) {
                card.classList.add("active");
            });
        });
    }

    // ==========================================
    // 4. LOGIKA GANTI PASSWORD (profile.html)
    // ==========================================
    const toggleBtns = document.querySelectorAll(".toggle-pass-visibility");
    toggleBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
            const inputWrapper = this.closest(".password-input-wrapper");
            if (!inputWrapper) return;
            const input = inputWrapper.querySelector("input");
            if (!input) return;
            const isPassword = input.type === "password";
            input.type = isPassword ? "text" : "password";
            this.setAttribute("aria-label", isPassword ? "Sembunyikan sandi" : "Tampilkan sandi");
            // Ganti ikon eye
            this.innerHTML = isPassword
                ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        });
    });

    const changePasswordForm = document.getElementById("changePasswordForm");
    if (changePasswordForm) {
        changePasswordForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const oldPass  = document.getElementById("oldPassword");
            const newPass  = document.getElementById("newPassword");
            const confPass = document.getElementById("confirmPassword");
            const feedback = document.getElementById("passwordFeedback");

            // Reset feedback
            if (feedback) { feedback.textContent = ""; feedback.className = "classroom-feedback"; }

            if (!oldPass.value || !newPass.value || !confPass.value) {
                if (feedback) { feedback.textContent = "Semua kolom sandi wajib diisi."; feedback.className = "classroom-feedback is-visible is-error"; }
                return;
            }
            if (newPass.value.length < 8) {
                if (feedback) { feedback.textContent = "Sandi baru minimal 8 karakter."; feedback.className = "classroom-feedback is-visible is-error"; }
                return;
            }
            if (newPass.value !== confPass.value) {
                if (feedback) { feedback.textContent = "Sandi baru dan konfirmasi sandi tidak cocok."; feedback.className = "classroom-feedback is-visible is-error"; }
                return;
            }

            // Simulasi sukses
            if (feedback) {
                feedback.textContent = "Kata sandi berhasil diperbarui (simulasi frontend).";
                feedback.className = "classroom-feedback is-visible is-success";
            }
            this.reset();
        });
    }

    // ==========================================
    // 5. SIMULASI PEMANGGILAN API — Data Pengguna
    // ==========================================
    function fetchUserData() {
        const mockApiResponse = {
            status: 200,
            data: {
                name: "Muhammad Azka Asyrafany",
                nrp: "5025251131",
                department: "Teknik Informatika",
                email: "5025251131@its.ac.id"
            }
        };
        const userData = mockApiResponse.data;

        // Topbar
        const elTopName = document.getElementById("topbar-name");
        const elTopNrp  = document.getElementById("topbar-nrp");
        const elTopDept = document.getElementById("topbar-dept");
        if (elTopName) elTopName.textContent = userData.name;
        if (elTopNrp)  elTopNrp.textContent  = userData.nrp;
        if (elTopDept) elTopDept.textContent  = userData.department;

        // Portal — Welcome Banner
        const elBannerName = document.getElementById("banner-name");
        if (elBannerName) elBannerName.textContent = userData.name;

        // Profil
        const elProfName  = document.getElementById("profile-name");
        const elProfNrp   = document.getElementById("profile-nrp");
        const elProfDept  = document.getElementById("profile-dept");
        const elProfEmail = document.getElementById("profile-email");
        if (elProfName)  elProfName.value  = userData.name;
        if (elProfNrp)   elProfNrp.value   = userData.nrp;
        if (elProfDept)  elProfDept.value  = userData.department;
        if (elProfEmail) elProfEmail.value = userData.email;

        // KRS
        const elKrsNama    = document.getElementById("krs-nama");
        const elKrsNim     = document.getElementById("krs-nim");
        const elKrsJurusan = document.getElementById("krs-jurusan");
        if (elKrsNama)    elKrsNama.textContent    = userData.name;
        if (elKrsNim)     elKrsNim.textContent     = userData.nrp;
        if (elKrsJurusan) elKrsJurusan.textContent = userData.department;
    }
    fetchUserData();

});

/* ==========================================================
   CLASSROOM INTERACTIONS
   Data: Mock data (siap diganti dengan fetch() ke API).
   Prinsip: HTML menyediakan struktur, JS mengisi data & event.
   ========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    // Guard: hanya jalankan di halaman classroom mahasiswa
    const classroomApp = document.getElementById("classroomApp");
    if (!classroomApp) return;

    // --- Elemen DOM tetap (ada di HTML) ---
    const lessonEyebrow        = document.getElementById("lessonEyebrow");
    const lessonTitle          = document.getElementById("lessonTitle");
    const lessonMeta           = document.getElementById("lessonMeta");
    const materialsContent     = document.getElementById("materialsContent");
    const announcementsContent = document.getElementById("announcementsContent");
    const tasksContent         = document.getElementById("tasksContent");
    const materialsPanel       = document.getElementById("materialsPanel");
    const tasksPanel           = document.getElementById("tasksPanel");
    const tabButtons           = document.querySelectorAll(".classroom-tab");

    // View containers (bukan tab panel)
    const courseView           = document.getElementById("courseView");
    const announcementView     = document.getElementById("announcementView");
    const btnOpenAnnouncement  = document.getElementById("btnOpenAnnouncement");
    const btnCloseAnnouncement = document.getElementById("btnCloseAnnouncement");
    const announcementBadge    = document.getElementById("announcementBadge");

    // Elemen header pengumuman
    const annEyebrow = document.getElementById("annEyebrow");
    const annTitle   = document.getElementById("annTitle");
    const annMeta    = document.getElementById("annMeta");

    // ==========================================
    // MOCK DATA — Mata Kuliah, Materi & Tugas
    // ==========================================
    const courses = [
        {
            id: "dasar-pemrograman",
            name: "Dasar Pemrograman",
            className: "A",
            sks: 3,
            announcements: [
                {
                    id: "dp-ann-1",
                    title: "Jadwal UTS Dasar Pemrograman",
                    date: "20 Oktober 2026",
                    body: "UTS akan dilaksanakan pada 10 November 2026 pukul 08.00 WIB di Gedung B Ruang 301. Materi yang diujikan mencakup Bab 1 hingga Bab 4. Harap membawa kartu ujian dan alat tulis."
                },
                {
                    id: "dp-ann-2",
                    title: "Perubahan Jadwal Kuliah Minggu Ini",
                    date: "15 Oktober 2026",
                    body: "Kuliah hari Selasa 18 Oktober 2026 dipindahkan ke hari Kamis 20 Oktober 2026 pukul 10.00 WIB. Ruang tetap sama. Mohon disesuaikan jadwalnya."
                },
                {
                    id: "dp-ann-3",
                    title: "Batas Pengumpulan Tugas 1 Diperpanjang",
                    date: "5 Oktober 2026",
                    body: "Batas pengumpulan Tugas 1 (Variabel dan Tipe Data) diperpanjang hingga 30 Oktober 2026 pukul 23:59. Tidak ada perpanjangan lebih lanjut. Kumpulkan tepat waktu."
                }
            ],
            lessons: [
                {
                    id: "1",
                    label: "1. Pengenalan Pemrograman",
                    eyebrow: "Bagian 1",
                    title: "Pengenalan Pemrograman",
                    sources: [
                        { name: "Pengenalan_Pemrograman.pdf", description: "Dokumen PDF • 1.8 MB", type: "PDF", icon: "pdf" },
                        { name: "Bahasa_C.pptx", description: "Slide Presentasi • 5.1 MB", type: "PPT", icon: "ppt" },
                        { name: "Catatan_Algoritma.txt", description: "Teks Biasa • 32 KB", type: "TXT", icon: "txt" }
                    ],
                    summaryTitle: "Dasar Pemrograman",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Pemrograman adalah proses menyusun instruksi agar komputer dapat menyelesaikan suatu masalah. Sebelum menulis kode, pahami masalah, susun algoritma, lalu terjemahkan langkah-langkahnya menjadi program yang dapat dijalankan."
                },
                {
                    id: "2",
                    label: "2. Variabel dan Tipe Data",
                    eyebrow: "Bagian 2",
                    title: "Variabel dan Tipe Data",
                    sources: [
                        { name: "Variabel_Tipe_Data.pdf", description: "Dokumen PDF • 1.6 MB", type: "PDF", icon: "pdf" },
                        { name: "Tipe_Data_C.pptx", description: "Slide Presentasi • 3.7 MB", type: "PPT", icon: "ppt" },
                        { name: "Contoh_Variabel.txt", description: "Teks Biasa • 24 KB", type: "TXT", icon: "txt" }
                    ],
                    summaryTitle: "Memahami Variabel dan Tipe Data",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Variabel digunakan untuk menyimpan nilai selama program berjalan. Tipe data menentukan jenis nilai yang disimpan, misalnya bilangan bulat, bilangan desimal, karakter, atau teks."
                },
                {
                    id: "3",
                    label: "3. Struktur Kontrol",
                    eyebrow: "Bagian 3",
                    title: "Struktur Kontrol",
                    sources: [
                        { name: "Struktur_Kontrol.pdf", description: "Dokumen PDF • 2.1 MB", type: "PDF", icon: "pdf" },
                        { name: "Percabangan_Perulangan.pptx", description: "Slide Presentasi • 4.8 MB", type: "PPT", icon: "ppt" }
                    ],
                    summaryTitle: "Percabangan dan Perulangan",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Struktur kontrol mengatur alur eksekusi program. Percabangan digunakan untuk memilih tindakan berdasarkan kondisi, sedangkan perulangan digunakan untuk menjalankan instruksi berulang kali selama syarat tertentu terpenuhi."
                },
                {
                    id: "4",
                    label: "4. Fungsi dan Array",
                    eyebrow: "Bagian 4",
                    title: "Fungsi dan Array",
                    sources: [
                        { name: "Fungsi_Array.pdf", description: "Dokumen PDF • 1.9 MB", type: "PDF", icon: "pdf" },
                        { name: "Latihan_Fungsi.c", description: "Contoh kode C • 8 KB", type: "C", icon: "txt" }
                    ],
                    summaryTitle: "Menyusun Program Modular",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Fungsi membagi program menjadi bagian yang dapat digunakan kembali. Array menyimpan sekumpulan nilai dengan tipe yang sama dalam satu variabel."
                }
            ],
            tasks: [
                {
                    id: "A.1",
                    title: "Latihan Variabel dan Tipe Data",
                    due: "27 Oktober 2026",
                    status: "Tersedia",
                    description: "Buat program sederhana dalam bahasa C yang menggunakan variabel dengan beberapa tipe data. Tampilkan hasilnya dan berikan komentar singkat pada bagian kode yang penting."
                },
                {
                    id: "A.2",
                    title: "Percabangan dan Perulangan",
                    due: "3 November 2026",
                    status: "Tersedia",
                    description: "Buat program yang menerapkan percabangan dan perulangan untuk memproses input pengguna. Sertakan contoh masukan dan keluaran program."
                },
                {
                    id: "A.3",
                    title: "Fungsi Dasar",
                    due: "10 November 2026",
                    status: "Tersedia",
                    description: "Pisahkan program menjadi beberapa fungsi. Jelaskan parameter, nilai balik, dan cara fungsi tersebut dipanggil dari program utama."
                }
            ]
        },
        {
            id: "sistem-operasi",
            name: "Sistem Operasi",
            className: "B",
            sks: 3,
            announcements: [
                {
                    id: "so-ann-1",
                    title: "Kuis Mendadak Minggu Depan",
                    date: "25 Oktober 2026",
                    body: "Akan diadakan kuis singkat (15 menit) pada pertemuan minggu depan. Materi mencakup Manajemen Proses dan algoritma penjadwalan. Tidak ada kalkulator atau buku catatan."
                },
                {
                    id: "so-ann-2",
                    title: "Rekaman Kuliah Tersedia",
                    date: "18 Oktober 2026",
                    body: "Rekaman kuliah pertemuan ke-3 (Manajemen Proses) sudah tersedia di tab Materi. Silakan tonton kembali untuk mempersiapkan tugas penjadwalan proses."
                },
                {
                    id: "so-ann-3",
                    title: "Perpustakaan Referensi Diperbarui",
                    date: "10 Oktober 2026",
                    body: "Buku referensi utama mata kuliah ini (Operating System Concepts, edisi ke-10) sudah tersedia di Perpustakaan digital portal. Silakan unduh dari menu Perpustakaan."
                }
            ],
            lessons: [
                {
                    id: "1",
                    label: "1. Pengantar Sistem Operasi",
                    eyebrow: "Bagian 1",
                    title: "Pengantar Sistem Operasi",
                    sources: [
                        { name: "Pengantar_Sistem_Operasi.pdf", description: "Dokumen PDF • 2.0 MB", type: "PDF", icon: "pdf" },
                        { name: "Konsep_Sistem_Operasi.pptx", description: "Slide Presentasi • 6.4 MB", type: "PPT", icon: "ppt" },
                        { name: "Catatan_Pengantar.txt", description: "Teks Biasa • 28 KB", type: "TXT", icon: "txt" }
                    ],
                    summaryTitle: "Mengenal Sistem Operasi",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Sistem operasi mengelola sumber daya komputer dan menyediakan layanan bagi program aplikasi. Tanggung jawab utamanya meliputi pengelolaan proses, memori, berkas, dan perangkat I/O."
                },
                {
                    id: "2",
                    label: "2. Manajemen Proses",
                    eyebrow: "Bagian 2",
                    title: "Manajemen Proses",
                    sources: [
                        { name: "Penjadwalan_Proses.pdf", description: "Dokumen PDF • 2.4 MB", type: "PDF", icon: "pdf" },
                        { name: "Round_Robin.pptx", description: "Slide Presentasi • 8.1 MB", type: "PPT", icon: "ppt" },
                        { name: "Catatan_Proses_Thread.txt", description: "Teks Biasa • 45 KB", type: "TXT", icon: "txt" }
                    ],
                    summaryTitle: "Memahami Penjadwalan Proses",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Penjadwalan proses adalah algoritma yang membagi waktu CPU antara proses yang sedang berjalan.\n\nAda beberapa jenis algoritma penjadwalan: FCFS, SJF, Priority Scheduling, dan Round Robin (RR). Setiap algoritma memiliki kelebihan dan kekurangan tersendiri.\n\nRound Robin (RR) adalah algoritma time-sharing yang memberikan time quantum tetap ke setiap proses secara bergiliran."
                },
                {
                    id: "3",
                    label: "3. Manajemen Memori",
                    eyebrow: "Bagian 3",
                    title: "Manajemen Memori",
                    sources: [
                        { name: "Manajemen_Memori.pdf", description: "Dokumen PDF • 2.3 MB", type: "PDF", icon: "pdf" },
                        { name: "Paging_Segmentasi.pptx", description: "Slide Presentasi • 5.6 MB", type: "PPT", icon: "ppt" }
                    ],
                    summaryTitle: "Konsep Manajemen Memori",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Manajemen memori mengatur alokasi dan pembebasan memori untuk proses. Teknik seperti paging dan segmentasi membantu sistem mengelola ruang alamat secara efisien."
                },
                {
                    id: "4",
                    label: "4. Sistem Berkas",
                    eyebrow: "Bagian 4",
                    title: "Sistem Berkas",
                    sources: [
                        { name: "Sistem_Berkas.pdf", description: "Dokumen PDF • 1.7 MB", type: "PDF", icon: "pdf" },
                        { name: "Direktori_dan_Berkas.pptx", description: "Slide Presentasi • 4.6 MB", type: "PPT", icon: "ppt" }
                    ],
                    summaryTitle: "Memahami Sistem Berkas",
                    summarySubtitle: "Ringkasan singkat dari konsep utama.",
                    summary: "Sistem berkas menyediakan cara untuk menamai, menyimpan, mengorganisasi, dan mengakses data pada media penyimpanan. Struktur direktori dan hak akses melindungi berkas pengguna."
                }
            ],
            tasks: [
                {
                    id: "2.1",
                    title: "Penjadwalan Proses",
                    due: "24 Oktober 2026",
                    status: "Terlambat",
                    description: "Implementasikan algoritma penjadwalan Round Robin (RR) dasar dalam bahasa C. Program menerima jumlah proses, waktu burst, dan kuanta waktu, lalu menampilkan turnaround time dan waiting time."
                },
                {
                    id: "2.2",
                    title: "Penjadwalan Prioritas",
                    due: "1 November 2026",
                    status: "Tersedia",
                    description: "Implementasikan algoritma Priority Scheduling dalam bahasa C. Program menerima daftar proses beserta burst time dan prioritasnya, lalu menampilkan urutan eksekusi dan waktu tunggu setiap proses."
                },
                {
                    id: "2.3",
                    title: "Perbandingan SJF dan FCFS",
                    due: "8 November 2026",
                    status: "Tersedia",
                    description: "Buat program atau laporan singkat yang membandingkan algoritma SJF dan FCFS. Jelaskan perbedaan urutan eksekusi serta dampaknya terhadap waktu tunggu."
                }
            ]
        }
    ];

    // ==========================================
    // STATE
    // ==========================================
    let activeCourseId = "sistem-operasi";
    let activeLessonId = "2";
    let activeTab      = "materials";
    let activeTaskId   = "2.1";
    let selectedFile   = null;
    let taskComment    = "";
    const submittedTaskIds = new Set();

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
        });
    }
    function getActiveCourse() { return courses.find(c => c.id === activeCourseId) || courses[0]; }
    function getActiveLesson() {
        const course = getActiveCourse();
        return course.lessons.find(l => l.id === activeLessonId) || course.lessons[0];
    }
    function getActiveTask() {
        const course = getActiveCourse();
        return course.tasks.find(t => t.id === activeTaskId) || course.tasks[0];
    }
    function effectiveTaskStatus(task) {
        return submittedTaskIds.has(activeCourseId + ":" + task.id) ? "Terkirim" : task.status;
    }
    function statusClass(status) {
        if (status === "Terlambat") return "is-late";
        if (status === "Terkirim") return "is-submitted";
        return "is-available";
    }
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / (1024 * 1024)).toFixed(2) + " MB";
    }

    // SVG Ikon
    const fileIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7M8 15h8M8 18h6"/></svg>`;
    const slideIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 9h10M7 12h6"/></svg>`;
    const uploadIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg>`;
    const chevronIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" width="16" height="16"><path d="m6 9 6 6 6-6"/></svg>`;

    // ==========================================
    // RENDER: SIDEBAR KURSUS (Update state saja)
    // ==========================================
    function updateCourseListState() {
        // Update state aktif pada elemen HTML yang sudah ada
        document.querySelectorAll(".course-item").forEach(function (section) {
            const courseId = section.dataset.courseId;
            const isActive = courseId === activeCourseId;
            section.classList.toggle("is-active", isActive);
            section.classList.toggle("is-expanded", isActive);

            const toggle = section.querySelector(".course-toggle");
            if (toggle) toggle.setAttribute("aria-expanded", String(isActive));

            const lessonList = section.querySelector(".lesson-list");
            if (lessonList) lessonList.classList.toggle("d-none", !isActive);

            // Update active lesson button
            section.querySelectorAll(".lesson-nav-button").forEach(function (btn) {
                const isActiveLesson = btn.dataset.course === activeCourseId && btn.dataset.lesson === activeLessonId;
                btn.classList.toggle("is-active", isActiveLesson);
                if (isActiveLesson) {
                    btn.setAttribute("aria-current", "page");
                } else {
                    btn.removeAttribute("aria-current");
                }
            });
        });
    }

    // ==========================================
    // RENDER: HEADER PANEL KANAN
    // ==========================================
    function renderHeader() {
        const course = getActiveCourse();
        const lesson = getActiveLesson();
        lessonEyebrow.textContent = lesson.eyebrow;
        lessonTitle.textContent   = lesson.title;
        lessonMeta.innerHTML      = escapeHtml(course.name) + " (" + escapeHtml(course.className) + ") <span aria-hidden='true'>•</span> " + course.sks + " SKS";
    }

    // ==========================================
    // RENDER: TAB MATERI KULIAH
    // ==========================================
    function renderMaterials() {
        const lesson = getActiveLesson();
        const sourcesHtml = lesson.sources.map(function (source) {
            const icon = source.icon === "ppt" ? slideIcon : (source.icon === "pdf" ? fileIcon : "TXT");
            const iconClass = source.icon === "txt" ? "source-file-icon is-text" : "source-file-icon";
            return `<div class="source-row">
                <span class="${iconClass}" aria-hidden="true">${icon}</span>
                <span class="source-copy">
                    <span class="source-name">${escapeHtml(source.name)}</span>
                    <span class="source-description">${escapeHtml(source.description)}</span>
                </span>
                <span class="file-type-badge">${escapeHtml(source.type)}</span>
            </div>`;
        }).join("");

        materialsContent.innerHTML = `
            <div class="classroom-stack">
                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div><h2>Sumber Kuliah</h2><p>Referensi yang disediakan untuk bagian ini.</p></div>
                        <span class="classroom-count">${lesson.sources.length} Sumber</span>
                    </div>
                    <div class="source-list">
                        ${sourcesHtml || '<div class="classroom-empty-state">Belum ada sumber kuliah untuk bagian ini.</div>'}
                    </div>
                    <p class="classroom-demo-note">Nama dan ukuran file adalah data contoh; file belum terhubung ke backend.</p>
                </section>
                <section class="classroom-card">
                    <h2 class="classroom-summary-title">${escapeHtml(lesson.summaryTitle)}</h2>
                    <p class="classroom-summary-subtitle">${escapeHtml(lesson.summarySubtitle)}</p>
                    <p class="classroom-summary-copy">${escapeHtml(lesson.summary)}</p>
                </section>
            </div>`;
    }

    // ==========================================
    // RENDER: TAB PENGUMUMAN — VIEW TERPISAH
    // Dipanggil saat tombol megaphone diklik.
    // ==========================================
    function openAnnouncementView() {
        const course = getActiveCourse();

        // Header panel pengumuman
        if (annEyebrow) annEyebrow.textContent = "Pengumuman Kelas";
        if (annTitle)   annTitle.textContent   = "Pengumuman — " + course.name + " (" + course.className + ")";
        if (annMeta)    annMeta.innerHTML      = escapeHtml(course.name) + " (" + escapeHtml(course.className) + ") <span aria-hidden='true'>•</span> " + course.sks + " SKS";

        renderAnnouncements();

        // Tampilkan view pengumuman, sembunyikan view materi/tugas
        if (courseView)       courseView.classList.add("d-none");
        if (announcementView) announcementView.classList.remove("d-none");
    }

    function closeAnnouncementView() {
        if (announcementView) announcementView.classList.add("d-none");
        if (courseView)       courseView.classList.remove("d-none");
    }

    function updateAnnouncementBadge() {
        const course = getActiveCourse();
        const count  = course.announcements ? course.announcements.length : 0;
        if (announcementBadge) {
            if (count > 0) {
                announcementBadge.textContent = count;
                announcementBadge.style.display = "";
            } else {
                announcementBadge.style.display = "none";
            }
        }
    }

    function renderAnnouncements() {
        const course = getActiveCourse();
        const announcements = course.announcements;

        if (!announcementsContent) return;

        if (!announcements || announcements.length === 0) {
            announcementsContent.innerHTML = `
                <div class="classroom-card">
                    <div class="classroom-empty-state">Belum ada pengumuman untuk mata kuliah ini.</div>
                </div>`;
            return;
        }

        const itemsHtml = announcements.map(function (ann, index) {
            const isOpen = index === 0; // pengumuman terbaru otomatis terbuka
            return `
                <div class="announcement-item${isOpen ? " is-open" : ""}" data-announcement-id="${escapeHtml(ann.id)}">
                    <button class="announcement-header" type="button" aria-expanded="${isOpen}">
                        <div class="announcement-header-left">
                            <span class="announcement-dot${isOpen ? " is-new" : ""}"></span>
                            <div>
                                <span class="announcement-title">${escapeHtml(ann.title)}</span>
                                <span class="announcement-date">${escapeHtml(ann.date)}</span>
                            </div>
                        </div>
                        ${chevronIcon.replace("viewBox", 'class="announcement-chevron" viewBox')}
                    </button>
                    <div class="announcement-body${isOpen ? "" : " d-none"}">
                        <p class="announcement-text">${escapeHtml(ann.body)}</p>
                    </div>
                </div>`;
        }).join("");

        announcementsContent.innerHTML = `
            <section class="classroom-card">
                <div class="classroom-card-heading">
                    <div>
                        <h2>Pengumuman</h2>
                        <p>Klik pada judul untuk membuka atau menutup detail pengumuman.</p>
                    </div>
                    <span class="classroom-count">${announcements.length} Pengumuman</span>
                </div>
                <div class="announcement-list">${itemsHtml}</div>
            </section>`;

        // Wire toggle expand/collapse tiap item
        announcementsContent.querySelectorAll(".announcement-header").forEach(function (header) {
            header.addEventListener("click", function () {
                const item = this.closest(".announcement-item");
                const body = item.querySelector(".announcement-body");
                const isOpen = item.classList.contains("is-open");
                item.classList.toggle("is-open", !isOpen);
                body.classList.toggle("d-none", isOpen);
                this.setAttribute("aria-expanded", String(!isOpen));
            });
        });
    }

    // ==========================================
    // RENDER: TAB TUGAS
    // ==========================================
    function renderTasks() {
        const course   = getActiveCourse();
        const taskList = course.tasks;
        activeTaskId   = taskList.some(t => t.id === activeTaskId) ? activeTaskId : taskList[0].id;
        const selectedTask = getActiveTask();
        const status = effectiveTaskStatus(selectedTask);

        const taskCardsHtml = taskList.map(function (task) {
            const taskStatus = effectiveTaskStatus(task);
            const isSelected = task.id === activeTaskId;
            return `
                <button type="button" class="task-card${isSelected ? " is-selected" : ""}"
                    data-task-id="${escapeHtml(task.id)}" aria-pressed="${isSelected}">
                    <span class="task-card-top">
                        <span class="task-code">${escapeHtml(task.id)}</span>
                        <span class="task-status ${statusClass(taskStatus)}">${escapeHtml(taskStatus)}</span>
                    </span>
                    <span class="task-card-title">${escapeHtml(task.title)}</span>
                    <span class="task-card-bottom">
                        <span class="task-due">Batas: ${escapeHtml(task.due)}</span>
                        ${isSelected ? '<span class="task-selected-label">Dipilih</span>' : ""}
                    </span>
                </button>`;
        }).join("");

        tasksContent.innerHTML = `
            <div class="classroom-stack">
                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div class="task-section-heading">
                            <h2>Tugas</h2>
                            <p>Pilih tugas untuk melihat detail dan mengunggah pekerjaan Anda.</p>
                        </div>
                        <span class="classroom-count">${taskList.length} tugas</span>
                    </div>
                    <div class="task-grid">${taskCardsHtml}</div>
                </section>

                <section class="classroom-card" aria-labelledby="taskDetailTitle">
                    <div class="task-detail-heading">
                        <div>
                            <h2 id="taskDetailTitle">Tugas ${escapeHtml(selectedTask.id)}: ${escapeHtml(selectedTask.title)}</h2>
                            <p class="task-deadline">Batas: ${escapeHtml(selectedTask.due)} • 23:59</p>
                        </div>
                        <span class="task-status ${statusClass(status)}">${escapeHtml(status)}</span>
                    </div>
                    <p class="task-description">${escapeHtml(selectedTask.description)}</p>
                </section>

                <section class="classroom-card">
                    <div class="classroom-card-heading">
                        <div><h2>Unggah pekerjaan Anda</h2><p>Pilih file tugas yang akan dikumpulkan.</p></div>
                    </div>
                    <form class="classroom-form" id="taskSubmitForm" novalidate>
                        <div class="classroom-field">
                            <label for="taskFileInput">Unggah file C (.c)</label>
                            <input id="taskFileInput" type="file" accept=".c,text/x-c,text/plain">
                            <label class="classroom-dropzone" id="taskDropzone" for="taskFileInput" tabindex="0">
                                ${uploadIcon}
                                <span class="dropzone-text">
                                    <strong>Seret dan lepas file Anda di sini</strong><br>
                                    atau klik untuk menjelajah (.c)
                                </span>
                            </label>
                            <div id="selectedFileContainer"></div>
                            <p class="classroom-field-hint">Prototype frontend menerima satu file dengan ekstensi .c.</p>
                        </div>
                        <div class="classroom-field">
                            <label for="taskComment">Komentar tambahan (opsional)</label>
                            <textarea class="classroom-textarea" id="taskComment"
                                placeholder="Tambahkan catatan untuk dosen...">${escapeHtml(taskComment)}</textarea>
                        </div>
                        <p class="classroom-feedback" id="taskFeedback" role="status" aria-live="polite"></p>
                        <div class="classroom-form-actions">
                            <button type="button" class="classroom-button classroom-button-secondary" id="cancelTaskButton">Batal</button>
                            <button type="submit" class="classroom-button classroom-button-primary" id="submitTaskButton">Kirim Tugas</button>
                        </div>
                        <p class="classroom-demo-note">Mode frontend: pengiriman hanya disimulasikan. File tidak diunggah ke server.</p>
                    </form>
                </section>
            </div>`;

        wireTaskForm();
        renderSelectedFile();
    }

    function renderSelectedFile() {
        const container = document.getElementById("selectedFileContainer");
        if (!container) return;
        if (!selectedFile) { container.innerHTML = ""; return; }
        container.innerHTML = `
            <div class="selected-file">
                <span class="selected-file-icon" aria-hidden="true">C</span>
                <span class="selected-file-copy">
                    <span class="selected-file-name">${escapeHtml(selectedFile.name)}</span>
                    <span class="selected-file-size">${formatFileSize(selectedFile.size)}</span>
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

    function showFeedback(message, kind) {
        const feedback = document.getElementById("taskFeedback");
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = "classroom-feedback is-visible " + (kind === "success" ? "is-success" : "is-error");
    }

    function resetFeedback() {
        const feedback = document.getElementById("taskFeedback");
        if (feedback) { feedback.textContent = ""; feedback.className = "classroom-feedback"; }
    }

    function setFile(file) {
        resetFeedback();
        if (!file) return;
        if (!file.name.toLowerCase().endsWith(".c")) {
            selectedFile = null;
            const input = document.getElementById("taskFileInput");
            if (input) input.value = "";
            renderSelectedFile();
            showFeedback("Format file tidak sesuai. Pilih file dengan ekstensi .c.", "error");
            return;
        }
        selectedFile = file;
        renderSelectedFile();
    }

    function wireTaskForm() {
        const form         = document.getElementById("taskSubmitForm");
        const input        = document.getElementById("taskFileInput");
        const dropzone     = document.getElementById("taskDropzone");
        const commentField = document.getElementById("taskComment");
        const cancelButton = document.getElementById("cancelTaskButton");

        if (input) {
            input.addEventListener("change", function () { setFile(input.files && input.files[0]); });
        }
        if (dropzone && input) {
            dropzone.addEventListener("keydown", function (e) {
                if (e.key === "Enter" || e.key === " ") { e.preventDefault(); input.click(); }
            });
            ["dragenter", "dragover"].forEach(function (ev) {
                dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.add("is-dragover"); });
            });
            ["dragleave", "drop"].forEach(function (ev) {
                dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.remove("is-dragover"); });
            });
            dropzone.addEventListener("drop", function (e) {
                const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                setFile(file);
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
                renderSelectedFile(); resetFeedback();
            });
        }
        if (form) {
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                if (!selectedFile) { showFeedback("Pilih file .c terlebih dahulu sebelum mengirim.", "error"); return; }
                const key = activeCourseId + ":" + getActiveTask().id;
                submittedTaskIds.add(key);
                showFeedback("Tugas berhasil dikirim (simulasi frontend). Hubungkan ke API backend agar tersimpan.", "success");
                renderTasksKeepingFeedback();
            });
        }
    }

    function renderTasksKeepingFeedback() {
        const currentFeedback = document.getElementById("taskFeedback");
        const message   = currentFeedback ? currentFeedback.textContent : "";
        const isSuccess = currentFeedback && currentFeedback.classList.contains("is-success");
        renderTasks();
        if (message) showFeedback(message, isSuccess ? "success" : "error");
    }

    // ==========================================
    // TAB SWITCHING — hanya Materials & Tasks
    // ==========================================
    function activateTab(tabName) {
        activeTab = tabName;
        tabButtons.forEach(function (btn) {
            const isActive = btn.dataset.tab === activeTab;
            btn.classList.toggle("is-active", isActive);
            btn.setAttribute("aria-selected", String(isActive));
        });

        if (materialsPanel) materialsPanel.classList.toggle("d-none", activeTab !== "materials");
        if (tasksPanel)     tasksPanel.classList.toggle("d-none",     activeTab !== "tasks");

        if (activeTab === "tasks") renderTasks();
    }

    // ==========================================
    // EVENT LISTENERS — Tombol Megaphone
    // ==========================================
    if (btnOpenAnnouncement) {
        btnOpenAnnouncement.addEventListener("click", function () {
            openAnnouncementView();
        });
    }

    if (btnCloseAnnouncement) {
        btnCloseAnnouncement.addEventListener("click", function () {
            closeAnnouncementView();
        });
    }

    // ==========================================
    // EVENT LISTENERS — Sidebar Kursus (dari HTML)
    // ==========================================
    document.querySelectorAll(".course-toggle").forEach(function (toggle) {
        toggle.addEventListener("click", function () {
            const section  = this.closest(".course-item");
            const courseId = section ? section.dataset.courseId : null;
            if (!courseId) return;

            const course = courses.find(c => c.id === courseId);
            if (!course) return;

            activeCourseId = courseId;
            activeLessonId = course.lessons[0].id;
            activeTaskId   = course.tasks[0].id;
            selectedFile   = null;
            taskComment    = "";

            // Jika sedang di view pengumuman, kembali ke view utama
            closeAnnouncementView();

            updateCourseListState();
            renderHeader();
            updateAnnouncementBadge();
            renderMaterials();
            if (activeTab === "tasks") renderTasks();
        });
    });

    document.querySelectorAll(".lesson-nav-button").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const courseId = this.dataset.course;
            const lessonId = this.dataset.lesson;
            if (!courseId || !lessonId) return;

            activeCourseId = courseId;
            activeLessonId = lessonId;
            activeTaskId   = getActiveCourse().tasks[0].id;
            selectedFile   = null;
            taskComment    = "";

            // Jika sedang di view pengumuman, kembali ke materi
            closeAnnouncementView();

            updateCourseListState();
            renderHeader();
            updateAnnouncementBadge();
            renderMaterials();
            if (activeTab === "tasks") renderTasks();
        });
    });

    // Tab buttons
    tabButtons.forEach(function (btn) {
        btn.addEventListener("click", function () { activateTab(btn.dataset.tab); });
    });

    // Klik task card
    if (tasksContent) {
        tasksContent.addEventListener("click", function (e) {
            const taskButton = e.target.closest("[data-task-id]");
            if (!taskButton) return;
            activeTaskId = taskButton.dataset.taskId;
            selectedFile = null; taskComment = "";
            renderTasks();
        });
    }

    // ==========================================
    // INITIAL RENDER
    // ==========================================
    updateCourseListState();
    renderHeader();
    updateAnnouncementBadge();
    renderMaterials();
    activateTab("materials");
});
