// dashboard/dosen/dosen.js
// Logika interaktivitas semua halaman dashboard DOSEN.

/* ==========================================================
   HELPER GLOBAL
   ========================================================== */
function esc(v) {
    return String(v).replace(/[&<>"']/g, c =>
        ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" })[c]);
}
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}
function fmt(bytes) {
    if (!bytes) return "—";
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + " KB";
    return (bytes/1048576).toFixed(2) + " MB";
}
function uid() { return "id" + Date.now() + Math.floor(Math.random()*1000); }

/* ------ Modal helpers ------ */
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add("is-open");
    document.body.style.overflow = "hidden";
    setTimeout(() => {
        const first = m.querySelector("input:not([type=hidden]), textarea, select, button:not(.modal-close)");
        if (first) first.focus();
    }, 60);
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove("is-open");
    document.body.style.overflow = "";
}

// Delegasi tombol tutup (data-close-modal)
document.addEventListener("click", function(e) {
    const btn = e.target.closest("[data-close-modal]");
    if (btn) closeModal(btn.dataset.closeModal);
    // Klik overlay
    if (e.target.classList.contains("modal-overlay")) closeModal(e.target.id);
});
document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        document.querySelectorAll(".modal-overlay.is-open")
            .forEach(m => closeModal(m.id));
    }
});

/* ==========================================================
   INIT — dipanggil saat DOM siap
   ========================================================== */
document.addEventListener("DOMContentLoaded", function () {

    /* ---------- TOPBAR DROPDOWN ---------- */
    const menuTrigger  = document.getElementById("menuTrigger");
    const dropdownMenu = document.getElementById("dropdownMenu");
    if (menuTrigger && dropdownMenu) {
        menuTrigger.addEventListener("click", e => {
            e.stopPropagation();
            dropdownMenu.classList.toggle("d-none");
        });
        document.addEventListener("click", e => {
            if (!dropdownMenu.contains(e.target) && e.target !== menuTrigger)
                dropdownMenu.classList.add("d-none");
        });
    }

    /* ---------- MOCK DATA DOSEN ---------- */
    const mockDosen = {
        name: "Dr. Rina Kusumawati, S.Kom., M.T.",
        nip:  "197804122005012002",
        dept: "Teknik Informatika",
        email:"rina.kusumawati@its.ac.id"
    };
    function injectDosenData() {
        setText("topbar-name", mockDosen.name);
        setText("topbar-nip",  mockDosen.nip);
        setText("topbar-dept", mockDosen.dept);
        const bn = document.getElementById("banner-name");
        if (bn) bn.textContent = mockDosen.name.split(",")[0];
    }
    injectDosenData();

    /* ---------- ROUTER ---------- */
    if (document.getElementById("dosenClassList"))  initClassroom();
    if (document.getElementById("perwalianStudentList")) initPerwalian();
    if (document.getElementById("libraryTableBody")) initLibrary();
});

/* ==========================================================
   CLASSROOM DOSEN
   Struktur per kelas:
     kelas
       .pengumuman[]   { id, judul, isi, tanggal }
       .sections[]     { id, nama, tanggal,
                         materi[]  { id, judul, isi, sources[] },
                         tugas[]   { id, judul, deskripsi, due, tipe,
                                     kiriman[] { mhs, file, size, waktu, komentar } } }
   ========================================================== */
function initClassroom() {

    /* ---- MOCK DATA ---- */
    const kelas = [
        {
            id:"if-201-a", kode:"IF-201", nama:"Dasar Pemrograman", kls:"A", sks:3, mhs:38,
            pengumuman:[
                { id:"p1", judul:"Jadwal UTS", isi:"UTS dilaksanakan 10 November 2026 pukul 08.00 WIB di Gedung B Ruang 301. Bawa kartu ujian.", tanggal:"20 Okt 2026" },
                { id:"p2", judul:"Batas Tugas 1 Diperpanjang", isi:"Tugas 1 diperpanjang hingga 30 Oktober 2026 pukul 23:59.", tanggal:"5 Okt 2026" }
            ],
            sections:[
                {
                    id:"s1", nama:"Pertemuan 1 — Pengantar", tanggal:"2 Sep 2026",
                    materi:[
                        { id:"m1", judul:"Pengantar Pemrograman", isi:"Pertemuan ini membahas konsep dasar algoritma dan pseudocode. Baca referensi berikut sebelum kelas.", sources:[
                            { id:"src1", nama:"Slide_Pertemuan1.pptx", ukuran:"4.2 MB", tipe:"PPT" },
                            { id:"src2", nama:"Kontrak_Kuliah.pdf",    ukuran:"1.1 MB", tipe:"PDF" }
                        ]}
                    ],
                    tugas:[
                        { id:"t1", judul:"Latihan Pseudocode", deskripsi:"Buat pseudocode untuk algoritma mencari bilangan prima antara 1–100.", due:"17 Sep 2026, 23:59", tipe:".pdf, .txt",
                          kiriman:[
                            { mhs:"Azka Asyrafany",   nrp:"5025251131", file:"pseudocode_azka.pdf",   ukuran:"320 KB", waktu:"16 Sep 2026, 21.30", komentar:"Terlampir jawaban saya." },
                            { mhs:"Siti Rahma Dewi",  nrp:"5025251045", file:"pseudocode_siti.pdf",   ukuran:"280 KB", waktu:"17 Sep 2026, 10.15", komentar:"" },
                            { mhs:"Ahmad Farid",      nrp:"5025251088", file:"latihan1_farid.txt",    ukuran:"12 KB",  waktu:"17 Sep 2026, 22.55", komentar:"Ada perbaikan di bagian loop." }
                        ]}
                    ]
                },
                {
                    id:"s2", nama:"Pertemuan 2 — Variabel & Tipe Data", tanggal:"9 Sep 2026",
                    materi:[
                        { id:"m2", judul:"Variabel dan Tipe Data", isi:"Pembahasan int, float, char, dan string dalam bahasa C.", sources:[
                            { id:"src3", nama:"Variabel_TipeData.pdf", ukuran:"1.6 MB", tipe:"PDF" }
                        ]}
                    ],
                    tugas:[
                        { id:"t2", judul:"Tugas 1: Program Variabel Dasar", deskripsi:"Buat program C yang mendeklarasikan minimal 4 tipe variabel dan tampilkan hasilnya.", due:"24 Sep 2026, 23:59", tipe:".c",
                          kiriman:[
                            { mhs:"Azka Asyrafany", nrp:"5025251131", file:"tugas1_azka.c", ukuran:"4 KB", waktu:"23 Sep 2026, 18.00", komentar:"" }
                        ]},
                        { id:"t3", judul:"Tugas 2: Konversi Tipe", deskripsi:"Implementasikan konversi implisit dan eksplisit antar tipe numerik.", due:"1 Okt 2026, 23:59", tipe:".c", kiriman:[] }
                    ]
                }
            ]
        },
        {
            id:"if-301-b", kode:"IF-301", nama:"Sistem Operasi", kls:"B", sks:3, mhs:35,
            pengumuman:[
                { id:"p3", judul:"Kuis Minggu Depan", isi:"Kuis singkat 15 menit — materi Manajemen Proses dan algoritma penjadwalan.", tanggal:"25 Okt 2026" }
            ],
            sections:[
                {
                    id:"s3", nama:"Pertemuan 1 — Pengantar SO", tanggal:"3 Sep 2026",
                    materi:[
                        { id:"m3", judul:"Konsep Sistem Operasi", isi:"Definisi, komponen, dan jenis-jenis sistem operasi. Baca Silberschatz Ch.1.", sources:[
                            { id:"src4", nama:"Konsep_SO.pptx", ukuran:"6.4 MB", tipe:"PPT" }
                        ]}
                    ],
                    tugas:[]
                },
                {
                    id:"s4", nama:"Pertemuan 2 — Manajemen Proses", tanggal:"10 Sep 2026",
                    materi:[
                        { id:"m4", judul:"Penjadwalan Proses", isi:"FCFS, SJF, Round Robin, dan Priority Scheduling.", sources:[
                            { id:"src5", nama:"Penjadwalan.pdf",   ukuran:"2.4 MB", tipe:"PDF" },
                            { id:"src6", nama:"RoundRobin.pptx",   ukuran:"8.1 MB", tipe:"PPT" }
                        ]}
                    ],
                    tugas:[
                        { id:"t4", judul:"Tugas 1: Implementasi Round Robin", deskripsi:"Implementasikan RR dalam C dengan input jumlah proses, burst time, dan time quantum.", due:"24 Okt 2026, 23:59", tipe:".c",
                          kiriman:[
                            { mhs:"Putri Larasati",  nrp:"5025251102", file:"rr_putri.c",   ukuran:"6 KB",  waktu:"23 Okt 2026, 20.00", komentar:"Time quantum 4ms." },
                            { mhs:"Bintang Ramadhan",nrp:"5025251073", file:"rr_bintang.c", ukuran:"5 KB",  waktu:"24 Okt 2026, 23.40", komentar:"" }
                        ]}
                    ]
                }
            ]
        },
        {
            id:"if-401-a", kode:"IF-401", nama:"Basis Data Lanjut", kls:"A", sks:3, mhs:30,
            pengumuman:[], sections:[]
        }
    ];

    /* ---- STATE ---- */
    let pendingDelete = null;   // { type, kelasId, sectionId?, itemId }
    let modalSaveCtx  = {};     // konteks untuk tombol Save di modal

    /* ---- RENDER UTAMA ---- */
    function renderAll() {
        const container = document.getElementById("dosenClassList");
        if (!container) return;
        if (kelas.length === 0) {
            container.innerHTML = `<div class="classroom-empty-state" style="padding:40px;">Belum ada kelas. Klik "Tambah Kelas" untuk memulai.</div>`;
            return;
        }
        container.innerHTML = kelas.map(renderKelasCard).join("");
        wireKelasEvents(container);
    }

    function renderKelasCard(k) {
        const totalTugas = k.sections.reduce((n,s) => n + s.tugas.length, 0);
        return `
        <div class="dc-card" data-kelas-id="${esc(k.id)}">
            <div class="dc-card-header" role="button" tabindex="0" aria-expanded="false">
                <div class="dc-header-left">
                    <div class="dosen-class-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="dc-kelas-nama">${esc(k.nama)} (${esc(k.kls)})</div>
                        <div class="dc-kelas-meta">${esc(k.kode)} &nbsp;·&nbsp; ${k.sks} SKS &nbsp;·&nbsp; ${k.mhs} mahasiswa &nbsp;·&nbsp; ${k.sections.length} section &nbsp;·&nbsp; ${totalTugas} tugas</div>
                    </div>
                </div>
                <svg class="dosen-class-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </div>

            <div class="dc-card-body d-none">
                <!-- Tabs per kelas -->
                <div class="dc-tabs" role="tablist">
                    <button class="dc-tab is-active" role="tab" aria-selected="true"  data-dc-tab="pengumuman">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                        Pengumuman <span class="dc-tab-badge">${k.pengumuman.length}</span>
                    </button>
                    <button class="dc-tab" role="tab" aria-selected="false" data-dc-tab="section">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Section &amp; Konten <span class="dc-tab-badge">${k.sections.length}</span>
                    </button>
                    <button class="dc-tab" role="tab" aria-selected="false" data-dc-tab="kiriman">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Kiriman Mahasiswa
                    </button>
                </div>

                <!-- Panel: Pengumuman -->
                <div class="dc-tab-panel" data-dc-panel="pengumuman">
                    <div class="dc-panel-toolbar">
                        <span class="dc-panel-label">Daftar Pengumuman</span>
                        <button class="btn-primary-small" style="font-size:12px;padding:7px 14px;"
                            data-action="add-pengumuman" data-kelas="${esc(k.id)}">
                            + Tambah Pengumuman
                        </button>
                    </div>
                    <div class="dc-pengumuman-list">
                        ${k.pengumuman.length === 0
                            ? `<div class="classroom-empty-state" style="padding:24px;">Belum ada pengumuman.</div>`
                            : k.pengumuman.map(p => renderPengumumanItem(p, k.id)).join("")
                        }
                    </div>
                </div>

                <!-- Panel: Section & Konten -->
                <div class="dc-tab-panel d-none" data-dc-panel="section">
                    <div class="dc-panel-toolbar">
                        <span class="dc-panel-label">Daftar Section</span>
                        <button class="btn-primary-small" style="font-size:12px;padding:7px 14px;"
                            data-action="add-section" data-kelas="${esc(k.id)}">
                            + Tambah Section
                        </button>
                    </div>
                    <div class="dc-section-list">
                        ${k.sections.length === 0
                            ? `<div class="classroom-empty-state" style="padding:24px;">Belum ada section. Klik "Tambah Section".</div>`
                            : k.sections.map(s => renderSectionItem(s, k.id)).join("")
                        }
                    </div>
                </div>

                <!-- Panel: Kiriman -->
                <div class="dc-tab-panel d-none" data-dc-panel="kiriman">
                    ${renderKirimanPanel(k)}
                </div>
            </div>
        </div>`;
    }

    function renderPengumumanItem(p, kelasId) {
        return `
        <div class="dc-pengumuman-item" data-peng-id="${esc(p.id)}">
            <div class="dc-pengumuman-header">
                <div>
                    <span class="dc-peng-judul">${esc(p.judul)}</span>
                    <span class="dc-peng-tanggal">${esc(p.tanggal)}</span>
                </div>
                <div class="dc-item-actions">
                    <button class="btn-icon" data-action="edit-pengumuman" data-kelas="${esc(kelasId)}" data-id="${esc(p.id)}" title="Edit" aria-label="Edit pengumuman">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                    </button>
                    <button class="btn-icon is-danger" data-action="hapus-pengumuman" data-kelas="${esc(kelasId)}" data-id="${esc(p.id)}" title="Hapus" aria-label="Hapus pengumuman">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>
            </div>
            <p class="dc-peng-isi">${esc(p.isi)}</p>
        </div>`;
    }

    function renderSectionItem(s, kelasId) {
        return `
        <div class="dc-section-item" data-section-id="${esc(s.id)}">
            <div class="dc-section-heading">
                <div class="dc-section-info">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span class="dc-section-nama">${esc(s.nama)}</span>
                    <span class="dc-section-tanggal">${esc(s.tanggal)}</span>
                </div>
                <div class="dc-item-actions">
                    <button class="btn-icon is-danger" data-action="hapus-section" data-kelas="${esc(kelasId)}" data-id="${esc(s.id)}" title="Hapus section" aria-label="Hapus section">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                    <svg class="dosen-section-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="cursor:pointer;"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>
            <div class="dc-section-body">
                <!-- Sub-tabs: Materi | Tugas -->
                <div class="dc-subtabs">
                    <button class="dc-subtab is-active" data-subtab="materi">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7"/></svg>
                        Materi <span class="dc-tab-badge">${s.materi.length}</span>
                    </button>
                    <button class="dc-subtab" data-subtab="tugas">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Tugas <span class="dc-tab-badge">${s.tugas.length}</span>
                    </button>
                </div>

                <!-- Sub-panel: Materi -->
                <div class="dc-subpanel" data-subpanel="materi">
                    <div class="dc-panel-toolbar" style="margin-bottom:8px;">
                        <span></span>
                        <button class="btn-add-content is-primary" data-action="add-materi" data-kelas="${esc(kelasId)}" data-section="${esc(s.id)}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Tambah Materi
                        </button>
                    </div>
                    ${s.materi.length === 0
                        ? `<div class="classroom-empty-state" style="padding:16px;font-size:12px;">Belum ada materi.</div>`
                        : s.materi.map(m => renderMateriItem(m, kelasId, s.id)).join("")
                    }
                </div>

                <!-- Sub-panel: Tugas -->
                <div class="dc-subpanel d-none" data-subpanel="tugas">
                    <div class="dc-panel-toolbar" style="margin-bottom:8px;">
                        <span></span>
                        <button class="btn-add-content" style="border-color:#FDE68A;color:#92400E;background:#FFFBEB;" data-action="add-tugas" data-kelas="${esc(kelasId)}" data-section="${esc(s.id)}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Tambah Tugas
                        </button>
                    </div>
                    ${s.tugas.length === 0
                        ? `<div class="classroom-empty-state" style="padding:16px;font-size:12px;">Belum ada tugas.</div>`
                        : s.tugas.map(t => renderTugasItem(t, kelasId, s.id)).join("")
                    }
                </div>
            </div>
        </div>`;
    }

    function renderMateriItem(m, kelasId, sectionId) {
        const sourcesHtml = m.sources.map(src => `
            <span class="dc-source-chip">
                <span class="dc-source-tipe">${esc(src.tipe)}</span>
                <span class="dc-source-nama">${esc(src.nama)}</span>
                <span class="dc-source-ukuran">${esc(src.ukuran)}</span>
            </span>`).join("");
        return `
        <div class="dc-konten-item is-materi" data-materi-id="${esc(m.id)}">
            <div class="dc-konten-icon is-materi-icon" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10z"/><path d="M13 3v7h7"/></svg>
            </div>
            <div class="dc-konten-body">
                <div class="dc-konten-judul">${esc(m.judul)}</div>
                <div class="dc-konten-isi">${esc(m.isi)}</div>
                ${m.sources.length > 0 ? `<div class="dc-source-list">${sourcesHtml}</div>` : ""}
            </div>
            <div class="dc-item-actions">
                <button class="btn-icon" data-action="edit-materi" data-kelas="${esc(kelasId)}" data-section="${esc(sectionId)}" data-id="${esc(m.id)}" title="Edit" aria-label="Edit materi">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                </button>
                <button class="btn-icon is-danger" data-action="hapus-materi" data-kelas="${esc(kelasId)}" data-section="${esc(sectionId)}" data-id="${esc(m.id)}" title="Hapus" aria-label="Hapus materi">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>`;
    }

    function renderTugasItem(t, kelasId, sectionId) {
        return `
        <div class="dc-konten-item is-tugas" data-tugas-id="${esc(t.id)}">
            <div class="dc-konten-icon is-tugas-icon" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="dc-konten-body">
                <div class="dc-konten-judul">${esc(t.judul)}</div>
                <div class="dc-konten-isi">${esc(t.deskripsi)}</div>
                <div style="display:flex;gap:12px;margin-top:4px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#64748B;">Batas: ${esc(t.due)}</span>
                    <span style="font-size:11px;color:#64748B;">Format: ${esc(t.tipe)}</span>
                    <span class="status-badge badge-primary" style="font-size:10px;">${t.kiriman.length} kiriman</span>
                </div>
            </div>
            <div class="dc-item-actions">
                <button class="btn-icon" data-action="edit-tugas" data-kelas="${esc(kelasId)}" data-section="${esc(sectionId)}" data-id="${esc(t.id)}" title="Edit" aria-label="Edit tugas">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                </button>
                <button class="btn-icon is-danger" data-action="hapus-tugas" data-kelas="${esc(kelasId)}" data-section="${esc(sectionId)}" data-id="${esc(t.id)}" title="Hapus" aria-label="Hapus tugas">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>`;
    }

    function renderKirimanPanel(k) {
        const allTugas = [];
        k.sections.forEach(s => s.tugas.forEach(t => allTugas.push({ ...t, sectionNama: s.nama })));
        if (allTugas.length === 0)
            return `<div class="classroom-empty-state" style="padding:32px;">Belum ada tugas di kelas ini.</div>`;

        return `
        <div class="dc-kiriman-wrapper">
            ${allTugas.map(t => `
            <div class="dc-kiriman-group">
                <div class="dc-kiriman-tugas-header">
                    <div>
                        <span class="dc-kiriman-tugas-nama">${esc(t.judul)}</span>
                        <span class="dc-kiriman-section">${esc(t.sectionNama)}</span>
                    </div>
                    <span class="status-badge badge-primary" style="font-size:11px;">${t.kiriman.length} kiriman</span>
                </div>
                ${t.kiriman.length === 0
                    ? `<div style="padding:12px 0;font-size:12px;color:#94A3B8;text-align:center;">Belum ada mahasiswa yang mengumpulkan.</div>`
                    : `<div class="dc-kiriman-table">
                        <div class="dc-kiriman-thead">
                            <div class="dc-kir-col-mhs">Mahasiswa</div>
                            <div class="dc-kir-col-file">File</div>
                            <div class="dc-kir-col-waktu">Waktu Kirim</div>
                            <div class="dc-kir-col-aksi">Detail</div>
                        </div>
                        ${t.kiriman.map(k => `
                        <div class="dc-kiriman-row">
                            <div class="dc-kir-col-mhs">
                                <span class="dc-mhs-nama">${esc(k.mhs)}</span>
                                <span class="dc-mhs-nrp">${esc(k.nrp)}</span>
                            </div>
                            <div class="dc-kir-col-file">
                                <span class="dc-file-nama">${esc(k.file)}</span>
                                <span class="dc-file-ukuran">${esc(k.ukuran)}</span>
                            </div>
                            <div class="dc-kir-col-waktu">${esc(k.waktu)}</div>
                            <div class="dc-kir-col-aksi">
                                <button class="btn-icon" data-action="detail-kiriman"
                                    data-mhs="${esc(k.mhs)}" data-nrp="${esc(k.nrp)}"
                                    data-file="${esc(k.file)}" data-ukuran="${esc(k.ukuran)}"
                                    data-waktu="${esc(k.waktu)}" data-komentar="${esc(k.komentar)}"
                                    title="Lihat detail" aria-label="Lihat detail kiriman">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>`).join("")}
                       </div>`
                }
            </div>`).join("")}
        </div>`;
    }

    /* ---- SOURCE INPUT ROWS (modal materi) ---- */
    let sourceDraft = [];
    function renderSourceInputList() {
        const list = document.getElementById("sourceInputList");
        if (!list) return;
        if (sourceDraft.length === 0) {
            list.innerHTML = `<p style="font-size:12px;color:#94A3B8;margin:4px 0;">Belum ada file sumber. Klik "Tambah File".</p>`;
            return;
        }
        list.innerHTML = sourceDraft.map((src, i) => `
            <div class="source-input-row" data-src-index="${i}">
                <div class="source-input-fields">
                    <input type="text" class="form-input src-nama" value="${esc(src.nama)}" placeholder="Nama file (cth: Slide.pptx)" style="flex:1;">
                    <input type="text" class="form-input src-ukuran" value="${esc(src.ukuran)}" placeholder="Ukuran (cth: 2.4 MB)" style="width:110px;">
                    <select class="form-input src-tipe" style="width:90px;">
                        ${["PDF","PPT","DOC","ZIP","C","TXT","MP4"].map(t =>
                            `<option${src.tipe===t?" selected":""}>${t}</option>`).join("")}
                    </select>
                </div>
                <button type="button" class="btn-icon is-danger" data-remove-src="${i}" aria-label="Hapus baris">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>`).join("");

        list.querySelectorAll("[data-remove-src]").forEach(btn => {
            btn.addEventListener("click", () => {
                sourceDraft.splice(parseInt(btn.dataset.removeSrc), 1);
                renderSourceInputList();
            });
        });
    }

    /* ---- WIRE EVENTS ---- */
    function wireKelasEvents(container) {
        /* Toggle kelas card */
        container.querySelectorAll(".dc-card-header").forEach(header => {
            header.addEventListener("click", () => {
                const card   = header.closest(".dc-card");
                const body   = card.querySelector(".dc-card-body");
                const isOpen = !body.classList.contains("d-none");
                body.classList.toggle("d-none", isOpen);
                header.classList.toggle("is-open", !isOpen);
                header.setAttribute("aria-expanded", String(!isOpen));
            });
            header.addEventListener("keydown", e => {
                if (e.key === "Enter" || e.key === " ") { e.preventDefault(); header.click(); }
            });
        });

        /* Tab switching per kelas */
        container.querySelectorAll(".dc-tab").forEach(tab => {
            tab.addEventListener("click", () => {
                const body   = tab.closest(".dc-card-body");
                const tabKey = tab.dataset.dcTab;
                body.querySelectorAll(".dc-tab").forEach(t => {
                    t.classList.toggle("is-active", t.dataset.dcTab === tabKey);
                    t.setAttribute("aria-selected", String(t.dataset.dcTab === tabKey));
                });
                body.querySelectorAll(".dc-tab-panel").forEach(p => {
                    p.classList.toggle("d-none", p.dataset.dcPanel !== tabKey);
                });
            });
        });

        /* Toggle section expand/collapse */
        container.querySelectorAll(".dc-section-heading").forEach(heading => {
            heading.addEventListener("click", e => {
                if (e.target.closest("[data-action]")) return;
                const item = heading.closest(".dc-section-item");
                const body = item.querySelector(".dc-section-body");
                const chev = heading.querySelector(".dosen-section-chevron");
                const isOpen = !body.classList.contains("d-none");
                body.classList.toggle("d-none", isOpen);
                if (chev) chev.style.transform = isOpen ? "" : "rotate(180deg)";
            });
        });

        /* Sub-tab: Materi / Tugas */
        container.querySelectorAll(".dc-subtab").forEach(st => {
            st.addEventListener("click", () => {
                const sectionBody = st.closest(".dc-section-body");
                const key = st.dataset.subtab;
                sectionBody.querySelectorAll(".dc-subtab").forEach(t =>
                    t.classList.toggle("is-active", t.dataset.subtab === key));
                sectionBody.querySelectorAll(".dc-subpanel").forEach(p =>
                    p.classList.toggle("d-none", p.dataset.subpanel !== key));
            });
        });

        /* Action dispatcher (delegasi) */
        container.addEventListener("click", e => {
            const btn = e.target.closest("[data-action]");
            if (!btn) return;
            const { action, kelas: kelasId, section: sectionId, id: itemId } = btn.dataset;
            const k = kelas.find(x => x.id === kelasId);
            const s = k ? k.sections.find(x => x.id === sectionId) : null;

            switch (action) {

                case "add-pengumuman":
                    modalSaveCtx = { type:"add-pengumuman", kelasId };
                    document.getElementById("editPengumumanId").value = "";
                    document.getElementById("inputPengumumanJudul").value = "";
                    document.getElementById("inputPengumumanIsi").value   = "";
                    document.getElementById("modalPengumumanTitle").textContent = "Tambah Pengumuman";
                    openModal("modalPengumuman"); break;

                case "edit-pengumuman": {
                    const p = k.pengumuman.find(x => x.id === itemId);
                    if (!p) break;
                    modalSaveCtx = { type:"edit-pengumuman", kelasId, itemId };
                    document.getElementById("editPengumumanId").value    = p.id;
                    document.getElementById("inputPengumumanJudul").value= p.judul;
                    document.getElementById("inputPengumumanIsi").value  = p.isi;
                    document.getElementById("modalPengumumanTitle").textContent = "Edit Pengumuman";
                    openModal("modalPengumuman"); break;
                }

                case "hapus-pengumuman":
                    pendingDelete = { type:"pengumuman", kelasId, itemId };
                    document.getElementById("hapusConfirmText").innerHTML =
                        `Hapus pengumuman "<strong>${esc(k.pengumuman.find(x=>x.id===itemId)?.judul)}</strong>"? Tindakan ini tidak dapat dibatalkan.`;
                    openModal("modalKonfirmasiHapus"); break;

                case "add-section":
                    modalSaveCtx = { type:"add-section", kelasId };
                    document.getElementById("inputSectionNama").value    = "";
                    document.getElementById("inputSectionTanggal").value = "";
                    openModal("modalSection"); break;

                case "hapus-section":
                    pendingDelete = { type:"section", kelasId, itemId: sectionId };
                    document.getElementById("hapusConfirmText").innerHTML =
                        `Hapus section "<strong>${esc(k.sections.find(x=>x.id===sectionId)?.nama)}</strong>" beserta seluruh materi dan tugasnya?`;
                    openModal("modalKonfirmasiHapus"); break;

                case "add-materi":
                    modalSaveCtx = { type:"add-materi", kelasId, sectionId };
                    document.getElementById("editMateriId").value     = "";
                    document.getElementById("inputMateriJudul").value = "";
                    document.getElementById("inputMateriIsi").value   = "";
                    sourceDraft = [];
                    renderSourceInputList();
                    document.getElementById("modalMateriTitle").textContent = "Tambah Materi";
                    openModal("modalMateri"); break;

                case "edit-materi": {
                    const m = s.materi.find(x => x.id === itemId);
                    if (!m) break;
                    modalSaveCtx = { type:"edit-materi", kelasId, sectionId, itemId };
                    document.getElementById("editMateriId").value     = m.id;
                    document.getElementById("inputMateriJudul").value = m.judul;
                    document.getElementById("inputMateriIsi").value   = m.isi;
                    sourceDraft = m.sources.map(src => ({ ...src }));
                    renderSourceInputList();
                    document.getElementById("modalMateriTitle").textContent = "Edit Materi";
                    openModal("modalMateri"); break;
                }

                case "hapus-materi":
                    pendingDelete = { type:"materi", kelasId, sectionId, itemId };
                    document.getElementById("hapusConfirmText").innerHTML =
                        `Hapus materi "<strong>${esc(s.materi.find(x=>x.id===itemId)?.judul)}</strong>"?`;
                    openModal("modalKonfirmasiHapus"); break;

                case "add-tugas":
                    modalSaveCtx = { type:"add-tugas", kelasId, sectionId };
                    document.getElementById("editTugasId").value        = "";
                    document.getElementById("inputTugasJudul").value    = "";
                    document.getElementById("inputTugasDeskripsi").value= "";
                    document.getElementById("inputTugasDue").value      = "";
                    resetFormatChips([]);
                    document.getElementById("modalTugasTitle").textContent = "Tambah Tugas";
                    openModal("modalTugas"); break;

                case "edit-tugas": {
                    const t = s.tugas.find(x => x.id === itemId);
                    if (!t) break;
                    modalSaveCtx = { type:"edit-tugas", kelasId, sectionId, itemId };
                    document.getElementById("editTugasId").value        = t.id;
                    document.getElementById("inputTugasJudul").value    = t.judul;
                    document.getElementById("inputTugasDeskripsi").value= t.deskripsi;
                    document.getElementById("inputTugasDue").value      = "";
                    // Pilih chip sesuai format yang tersimpan
                    const savedFmts = t.tipe ? t.tipe.split(",").map(f => f.trim()) : [];
                    resetFormatChips(savedFmts);
                    document.getElementById("modalTugasTitle").textContent = "Edit Tugas";
                    openModal("modalTugas"); break;
                }

                case "hapus-tugas":
                    pendingDelete = { type:"tugas", kelasId, sectionId, itemId };
                    document.getElementById("hapusConfirmText").innerHTML =
                        `Hapus tugas "<strong>${esc(s.tugas.find(x=>x.id===itemId)?.judul)}</strong>" beserta seluruh kiriman mahasiswa?`;
                    openModal("modalKonfirmasiHapus"); break;

                case "detail-kiriman": {
                    const { mhs, nrp, file, ukuran, waktu, komentar } = btn.dataset;
                    document.getElementById("modalKirimanTitle").textContent = `Kiriman: ${mhs}`;
                    document.getElementById("modalKirimanBody").innerHTML = `
                        <div style="display:flex;flex-direction:column;gap:14px;">
                            <div class="info-grid-4" style="gap:12px 20px;flex-wrap:wrap;">
                                <div class="info-item"><label>Mahasiswa</label><span>${esc(mhs)}</span></div>
                                <div class="info-item"><label>NRP</label><span>${esc(nrp)}</span></div>
                                <div class="info-item"><label>Waktu Kirim</label><span>${esc(waktu)}</span></div>
                            </div>
                            <div class="selected-file">
                                <span class="selected-file-icon" aria-hidden="true">FILE</span>
                                <span class="selected-file-copy">
                                    <span class="selected-file-name">${esc(file)}</span>
                                    <span class="selected-file-size">${esc(ukuran)}</span>
                                </span>
                                <button class="btn-primary-small" style="font-size:12px;padding:7px 12px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Unduh
                                </button>
                            </div>
                            ${komentar ? `<div class="admin-reply-box"><span class="reply-title">Komentar Mahasiswa</span><p class="reply-text">${esc(komentar)}</p></div>` : ""}
                        </div>`;
                    openModal("modalKiriman"); break;
                }
            }
        });
    }

    /* ---- MODAL SAVE HANDLERS ---- */

    /* Pengumuman */
    document.getElementById("btnSavePengumuman")?.addEventListener("click", () => {
        const judul = document.getElementById("inputPengumumanJudul").value.trim();
        const isi   = document.getElementById("inputPengumumanIsi").value.trim();
        if (!judul || !isi) { alert("Judul dan isi pengumuman wajib diisi."); return; }
        const k = kelas.find(x => x.id === modalSaveCtx.kelasId);
        if (!k) return;
        if (modalSaveCtx.type === "add-pengumuman") {
            k.pengumuman.unshift({ id: uid(), judul, isi,
                tanggal: new Date().toLocaleDateString("id-ID",{day:"numeric",month:"short",year:"numeric"}) });
        } else {
            const p = k.pengumuman.find(x => x.id === modalSaveCtx.itemId);
            if (p) { p.judul = judul; p.isi = isi; }
        }
        closeModal("modalPengumuman");
        renderAll();
    });

    /* Section */
    document.getElementById("btnSaveSection")?.addEventListener("click", () => {
        const nama = document.getElementById("inputSectionNama").value.trim();
        if (!nama) { alert("Nama section wajib diisi."); return; }
        const k = kelas.find(x => x.id === modalSaveCtx.kelasId);
        if (!k) return;
        const tgl = document.getElementById("inputSectionTanggal").value;
        k.sections.push({ id:uid(), nama, kiriman:[],
            tanggal: tgl ? new Date(tgl).toLocaleDateString("id-ID",{day:"numeric",month:"short",year:"numeric"}) : "—",
            materi:[], tugas:[] });
        closeModal("modalSection");
        renderAll();
    });

    /* Source row tambah */
    document.getElementById("btnAddSourceRow")?.addEventListener("click", () => {
        sourceDraft.push({ id:uid(), nama:"", ukuran:"", tipe:"PDF" });
        renderSourceInputList();
    });

    /* Materi */
    document.getElementById("btnSaveMateri")?.addEventListener("click", () => {
        const judul = document.getElementById("inputMateriJudul").value.trim();
        const isi   = document.getElementById("inputMateriIsi").value.trim();
        if (!judul) { alert("Judul materi wajib diisi."); return; }

        // Baca sources dari input rows
        const rows = document.querySelectorAll(".source-input-row");
        const sources = [];
        rows.forEach(row => {
            const nama   = row.querySelector(".src-nama")?.value.trim();
            const ukuran = row.querySelector(".src-ukuran")?.value.trim();
            const tipe   = row.querySelector(".src-tipe")?.value || "PDF";
            if (nama) sources.push({ id: uid(), nama, ukuran: ukuran||"—", tipe });
        });

        const k = kelas.find(x => x.id === modalSaveCtx.kelasId);
        const s = k?.sections.find(x => x.id === modalSaveCtx.sectionId);
        if (!k || !s) return;

        if (modalSaveCtx.type === "add-materi") {
            s.materi.push({ id:uid(), judul, isi, sources });
        } else {
            const m = s.materi.find(x => x.id === modalSaveCtx.itemId);
            if (m) { m.judul = judul; m.isi = isi; m.sources = sources; }
        }
        closeModal("modalMateri");
        renderAll();
    });

    /* Tugas */
    document.getElementById("btnSaveTugas")?.addEventListener("click", () => {
        const judul = document.getElementById("inputTugasJudul").value.trim();
        const desk  = document.getElementById("inputTugasDeskripsi").value.trim();
        const due   = document.getElementById("inputTugasDue").value;
        const tipe  = document.getElementById("inputTugasTipe").value.trim();
        if (!judul) { alert("Judul tugas wajib diisi."); return; }

        const dueStr = due
            ? new Date(due).toLocaleDateString("id-ID",{day:"numeric",month:"short",year:"numeric"}) + ", " +
              new Date(due).toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"})
            : "—";

        const k = kelas.find(x => x.id === modalSaveCtx.kelasId);
        const s = k?.sections.find(x => x.id === modalSaveCtx.sectionId);
        if (!k || !s) return;

        if (modalSaveCtx.type === "add-tugas") {
            s.tugas.push({ id:uid(), judul, deskripsi:desk, due:dueStr, tipe:tipe||".pdf", kiriman:[] });
        } else {
            const t = s.tugas.find(x => x.id === modalSaveCtx.itemId);
            if (t) { t.judul = judul; t.deskripsi = desk; t.due = dueStr; t.tipe = tipe; }
        }
        closeModal("modalTugas");
        renderAll();
    });

    /* Konfirmasi Hapus */
    document.getElementById("btnKonfirmasiHapus")?.addEventListener("click", () => {
        if (!pendingDelete) { closeModal("modalKonfirmasiHapus"); return; }
        const { type, kelasId, sectionId, itemId } = pendingDelete;
        const k = kelas.find(x => x.id === kelasId);
        if (!k) { closeModal("modalKonfirmasiHapus"); return; }

        if (type === "pengumuman") k.pengumuman = k.pengumuman.filter(x => x.id !== itemId);
        if (type === "section")    k.sections   = k.sections.filter(x => x.id !== itemId);
        if (type === "materi") {
            const s = k.sections.find(x => x.id === sectionId);
            if (s) s.materi = s.materi.filter(x => x.id !== itemId);
        }
        if (type === "tugas") {
            const s = k.sections.find(x => x.id === sectionId);
            if (s) s.tugas = s.tugas.filter(x => x.id !== itemId);
        }
        pendingDelete = null;
        closeModal("modalKonfirmasiHapus");
        renderAll();
    });

    /* Tambah Kelas (placeholder) */
    document.getElementById("btnTambahKelas")?.addEventListener("click", () => {
        alert("Fitur tambah kelas akan terhubung ke API backend.");
    });

    renderAll();
}

/* ==========================================================
   LIBRARY DOSEN
   ========================================================== */
function initLibrary() {

    const books = [
        { id:"b1", judul:"Introduction to Algorithms", penulis:"Cormen et al.", ukuran:"12.4 MB" },
        { id:"b2", judul:"Operating System Concepts, 10th Ed.", penulis:"Silberschatz, Galvin, Gagne", ukuran:"18.7 MB" },
        { id:"b3", judul:"Clean Code", penulis:"Robert C. Martin", ukuran:"6.2 MB" },
        { id:"b4", judul:"Database System Concepts", penulis:"Silberschatz, Korth, Sudarshan", ukuran:"22.1 MB" },
        { id:"b5", judul:"The Pragmatic Programmer", penulis:"Hunt & Thomas", ukuran:"8.9 MB" }
    ];

    const PAGE_SIZE = 5;
    let page = 1, query = "";
    let pendingDeleteBookId = null;
    let selectedFile = null;

    function filtered() {
        const q = query.toLowerCase();
        return books.filter(b => b.judul.toLowerCase().includes(q) || b.penulis.toLowerCase().includes(q));
    }

    function render() {
        const list     = filtered();
        const total    = list.length;
        const pages    = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (page > pages) page = pages;
        const slice    = list.slice((page-1)*PAGE_SIZE, page*PAGE_SIZE);
        const tbody    = document.getElementById("libraryTableBody");
        const pagEl    = document.getElementById("libraryPagination");
        if (!tbody) return;

        if (slice.length === 0) {
            tbody.innerHTML = `<div style="padding:32px;text-align:center;font-size:13px;color:#94A3B8;">Buku tidak ditemukan.</div>`;
        } else {
            tbody.innerHTML = slice.map((b, i) => `
                <div class="lib-table-row">
                    <div class="col-no">${(page-1)*PAGE_SIZE + i + 1}</div>
                    <div class="col-title">
                        <div class="book-cover-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <span class="book-name">${esc(b.judul)}</span>
                            <span style="display:block;font-size:11px;color:#64748B;margin-top:2px;">${esc(b.penulis)} &nbsp;·&nbsp; ${esc(b.ukuran)}</span>
                        </div>
                    </div>
                    <div class="col-action" style="width:160px;gap:6px;justify-content:flex-end;padding-right:8px;">
                        <button class="btn-primary-small" style="font-size:12px;padding:7px 12px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Unduh
                        </button>
                        <button class="btn-icon is-danger" data-hapus-buku="${esc(b.id)}" data-nama="${esc(b.judul)}" title="Hapus buku" aria-label="Hapus ${esc(b.judul)}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </div>
                </div>`).join("");

            tbody.querySelectorAll("[data-hapus-buku]").forEach(btn => {
                btn.addEventListener("click", () => {
                    pendingDeleteBookId = btn.dataset.hapusBuku;
                    setText("hapusBukuNama", btn.dataset.nama);
                    openModal("modalHapusBuku");
                });
            });
        }

        // Paginasi
        if (pagEl) {
            let html = `<button class="page-btn"${page===1?" disabled":""} data-pg="${page-1}">Sebelumnya</button>`;
            for (let i = 1; i <= pages; i++)
                html += `<button class="page-btn${i===page?" active":""}" data-pg="${i}">${i}</button>`;
            html += `<button class="page-btn"${page===pages?" disabled":""} data-pg="${page+1}">Berikutnya</button>`;
            pagEl.innerHTML = html;
            pagEl.querySelectorAll("[data-pg]").forEach(btn => {
                btn.addEventListener("click", () => { page = parseInt(btn.dataset.pg); render(); });
            });
        }
    }

    /* Search */
    document.getElementById("searchInput")?.addEventListener("input", function() {
        query = this.value; page = 1; render();
    });

    /* Tombol Tambah Buku */
    document.getElementById("btnTambahBuku")?.addEventListener("click", () => {
        document.getElementById("inputBukuJudul").value   = "";
        document.getElementById("inputBukuPenulis").value = "";
        document.getElementById("inputBukuFile").value    = "";
        selectedFile = null;
        updateFilePreview();
        const fb = document.getElementById("bukuFeedback");
        if (fb) { fb.textContent = ""; fb.className = "classroom-feedback"; }
        openModal("modalTambahBuku");
    });

    /* File input */
    function updateFilePreview() {
        const preview  = document.getElementById("bukuFilePreview");
        const nameEl   = document.getElementById("bukuFileName");
        const sizeEl   = document.getElementById("bukuFileSize");
        const dropzone = document.getElementById("bukuDropzone");
        if (!preview) return;
        if (selectedFile) {
            preview.style.display = "flex";
            if (nameEl) nameEl.textContent = selectedFile.name;
            if (sizeEl) sizeEl.textContent = fmt(selectedFile.size);
            if (dropzone) dropzone.style.display = "none";
        } else {
            preview.style.display = "none";
            if (dropzone) dropzone.style.display = "";
        }
    }

    document.getElementById("inputBukuFile")?.addEventListener("change", function() {
        selectedFile = this.files[0] || null;
        updateFilePreview();
    });

    document.getElementById("btnRemoveBukuFile")?.addEventListener("click", () => {
        selectedFile = null;
        const input = document.getElementById("inputBukuFile");
        if (input) input.value = "";
        updateFilePreview();
    });

    /* Drag & drop */
    const dropzone = document.getElementById("bukuDropzone");
    if (dropzone) {
        ["dragenter","dragover"].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add("is-dragover"); }));
        ["dragleave","drop"].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove("is-dragover"); }));
        dropzone.addEventListener("drop", e => {
            const f = e.dataTransfer?.files[0];
            if (f) { selectedFile = f; updateFilePreview(); }
        });
        dropzone.addEventListener("keydown", e => {
            if (e.key === "Enter" || e.key === " ") { e.preventDefault(); document.getElementById("inputBukuFile")?.click(); }
        });
    }

    /* Simpan Buku */
    document.getElementById("btnSaveBuku")?.addEventListener("click", () => {
        const judul   = document.getElementById("inputBukuJudul")?.value.trim();
        const penulis = document.getElementById("inputBukuPenulis")?.value.trim();
        const fb      = document.getElementById("bukuFeedback");

        if (!judul) { if (fb) { fb.textContent = "Judul buku wajib diisi."; fb.className = "classroom-feedback is-visible is-error"; } return; }
        if (!selectedFile) { if (fb) { fb.textContent = "Pilih file PDF terlebih dahulu."; fb.className = "classroom-feedback is-visible is-error"; } return; }
        if (!selectedFile.name.toLowerCase().endsWith(".pdf")) { if (fb) { fb.textContent = "Hanya file PDF yang diizinkan."; fb.className = "classroom-feedback is-visible is-error"; } return; }

        books.unshift({ id: uid(), judul, penulis: penulis||"—", ukuran: fmt(selectedFile.size) });
        closeModal("modalTambahBuku");
        page = 1; render();
    });

    /* Hapus Buku Konfirmasi */
    document.getElementById("btnKonfirmasiHapusBuku")?.addEventListener("click", () => {
        const idx = books.findIndex(b => b.id === pendingDeleteBookId);
        if (idx > -1) books.splice(idx, 1);
        pendingDeleteBookId = null;
        closeModal("modalHapusBuku");
        render();
    });

    render();
}

/* ==========================================================
   PERWALIAN DOSEN — disederhanakan
   ========================================================== */
function initPerwalian() {

    const mahasiswa = [
        { id:"mhs-1", name:"Muhammad Azka Asyrafany", nrp:"5025251131", dept:"Teknik Informatika", semester:"Semester 3 (2026/2027)", sks:20, ipk:"3.72", catatan:"",
          krs:[ {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"A",sks:3}, {kode:"IF-202",nama:"Kalkulus II",kelas:"B",sks:4}, {kode:"IF-203",nama:"Aljabar Linear",kelas:"A",sks:3}, {kode:"IF-204",nama:"Struktur Data",kelas:"A",sks:3}, {kode:"IF-205",nama:"Rekayasa Kebutuhan",kelas:"B",sks:3}, {kode:"IF-206",nama:"Statistika",kelas:"A",sks:3}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"C",sks:1} ]},
        { id:"mhs-2", name:"Siti Rahma Dewi", nrp:"5025251045", dept:"Teknik Informatika", semester:"Semester 3 (2026/2027)", sks:18, ipk:"3.90", catatan:"Pertahankan IPK.",
          krs:[ {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"A",sks:3}, {kode:"IF-202",nama:"Kalkulus II",kelas:"A",sks:4}, {kode:"IF-203",nama:"Aljabar Linear",kelas:"B",sks:3}, {kode:"IF-204",nama:"Struktur Data",kelas:"B",sks:3}, {kode:"IF-206",nama:"Statistika",kelas:"B",sks:3}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"A",sks:1} ]},
        { id:"mhs-3", name:"Ahmad Farid Nugroho", nrp:"5025251088", dept:"Teknik Informatika", semester:"Semester 3 (2026/2027)", sks:16, ipk:"3.15", catatan:"",
          krs:[ {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"B",sks:3}, {kode:"IF-202",nama:"Kalkulus II",kelas:"C",sks:4}, {kode:"IF-204",nama:"Struktur Data",kelas:"A",sks:3}, {kode:"IF-205",nama:"Rekayasa Kebutuhan",kelas:"A",sks:3}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"B",sks:1} ]},
        { id:"mhs-4", name:"Putri Larasati", nrp:"5025251102", dept:"Teknik Informatika", semester:"Semester 3 (2026/2027)", sks:19, ipk:"3.55", catatan:"",
          krs:[ {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"A",sks:3}, {kode:"IF-202",nama:"Kalkulus II",kelas:"B",sks:4}, {kode:"IF-203",nama:"Aljabar Linear",kelas:"A",sks:3}, {kode:"IF-204",nama:"Struktur Data",kelas:"A",sks:3}, {kode:"IF-205",nama:"Rekayasa Kebutuhan",kelas:"A",sks:3}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"C",sks:1} ]},
        { id:"mhs-5", name:"Bintang Ramadhan", nrp:"5025251073", dept:"Teknik Informatika", semester:"Semester 3 (2026/2027)", sks:17, ipk:"2.98", catatan:"Diskusikan pemilihan mata kuliah.",
          krs:[ {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"B",sks:3}, {kode:"IF-202",nama:"Kalkulus II",kelas:"C",sks:4}, {kode:"IF-203",nama:"Aljabar Linear",kelas:"B",sks:3}, {kode:"IF-206",nama:"Statistika",kelas:"A",sks:3}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"A",sks:1} ]}
    ];

    const availableMK = [
        {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"A",sks:3}, {kode:"IF-201",nama:"Dasar Pemrograman",kelas:"B",sks:3},
        {kode:"IF-202",nama:"Kalkulus II",kelas:"A",sks:4},       {kode:"IF-202",nama:"Kalkulus II",kelas:"B",sks:4},
        {kode:"IF-203",nama:"Aljabar Linear",kelas:"A",sks:3},    {kode:"IF-203",nama:"Aljabar Linear",kelas:"B",sks:3},
        {kode:"IF-204",nama:"Struktur Data & Algoritma",kelas:"A",sks:3}, {kode:"IF-204",nama:"Struktur Data & Algoritma",kelas:"B",sks:3},
        {kode:"IF-205",nama:"Rekayasa Kebutuhan Perangkat",kelas:"A",sks:3},
        {kode:"IF-206",nama:"Statistika",kelas:"A",sks:3}, {kode:"IF-206",nama:"Statistika",kelas:"B",sks:3},
        {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"A",sks:1}, {kode:"IF-207",nama:"Bahasa Inggris Teknik",kelas:"C",sks:1},
        {kode:"IF-208",nama:"Jaringan Komputer",kelas:"A",sks:3},
        {kode:"IF-209",nama:"Interaksi Manusia Komputer",kelas:"A",sks:3},
        {kode:"IF-210",nama:"Pemrograman Web",kelas:"A",sks:3}
    ];

    let activeStudentId = null;
    let pendingRemoveMK = null;

    function renderStudentList(q) {
        const list = document.getElementById("perwalianStudentList");
        if (!list) return;
        const keyword = (q||"").toLowerCase();
        const filtered = mahasiswa.filter(m =>
            m.name.toLowerCase().includes(keyword) || m.nrp.includes(keyword));
        if (!filtered.length) {
            list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:#94A3B8;">Mahasiswa tidak ditemukan.</div>`;
            return;
        }
        list.innerHTML = filtered.map(m => {
            const init = m.name.split(" ").slice(0,2).map(w=>w[0]).join("").toUpperCase();
            return `
            <div class="perwalian-student-item${m.id===activeStudentId?" is-active":""}"
                data-student-id="${esc(m.id)}" role="button" tabindex="0" aria-label="${esc(m.name)}">
                <div class="student-avatar-sm">${esc(init)}</div>
                <div style="flex:1;min-width:0;">
                    <div class="perwalian-student-name">${esc(m.name)}</div>
                    <div class="perwalian-student-nrp">${esc(m.nrp)}</div>
                </div>
                <span class="status-badge badge-primary" style="font-size:10px;">${m.sks} SKS</span>
            </div>`;
        }).join("");

        list.querySelectorAll(".perwalian-student-item").forEach(item => {
            item.addEventListener("click", () => {
                activeStudentId = item.dataset.studentId;
                renderStudentList(document.getElementById("searchMahasiswa")?.value||"");
                renderStudentDetail(activeStudentId);
            });
            item.addEventListener("keydown", e => { if (e.key==="Enter"||e.key===" ") { e.preventDefault(); item.click(); }});
        });
    }

    function renderStudentDetail(studentId) {
        const m = mahasiswa.find(x => x.id === studentId);
        const emptyState   = document.getElementById("perwalianEmptyState");
        const detailContent= document.getElementById("perwalianDetailContent");
        if (!m || !emptyState || !detailContent) return;
        emptyState.classList.add("d-none");
        detailContent.classList.remove("d-none");

        const init = m.name.split(" ").slice(0,2).map(w=>w[0]).join("").toUpperCase();
        const av = document.getElementById("detail-avatar");
        if (av) av.textContent = init;

        setText("detail-name",     m.name);
        setText("detail-nrp",      m.nrp);
        setText("detail-dept",     m.dept);
        setText("detail-semester", m.semester);
        setText("detail-sks",      m.sks + " SKS");
        setText("detail-ipk",      m.ipk);

        const catEl = document.getElementById("catatanPerwalian");
        if (catEl) catEl.value = m.catatan||"";

        renderKrsRows(m);
    }

    function renderKrsRows(m) {
        const rowsEl  = document.getElementById("perwalianKrsRows");
        const totalEl = document.getElementById("detail-total-sks");
        if (!rowsEl) return;

        rowsEl.innerHTML = m.krs.length === 0
            ? `<div style="padding:16px;text-align:center;font-size:13px;color:#94A3B8;">Belum ada mata kuliah.</div>`
            : m.krs.map(mk => `
            <div class="perwalian-table-row">
                <div class="p-col-kode">${esc(mk.kode)}</div>
                <div class="p-col-nama">${esc(mk.nama)}</div>
                <div class="p-col-kelas" style="text-align:center;">${esc(mk.kelas)}</div>
                <div class="p-col-sks"   style="text-align:center;">${mk.sks}</div>
                <div class="p-col-aksi"  style="text-align:center;">
                    <button class="btn-icon is-danger" style="margin:auto;"
                        data-hapus-mk="${esc(mk.kode+"-"+mk.kelas)}" data-mhs="${esc(m.id)}"
                        aria-label="Hapus ${esc(mk.nama)}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                </div>
            </div>`).join("");

        rowsEl.querySelectorAll("[data-hapus-mk]").forEach(btn => {
            btn.addEventListener("click", () => {
                const [kode, kelas] = btn.dataset.hapusMk.split("-");
                const mhsId = btn.dataset.mhs;
                pendingRemoveMK = { kode, kelas, mhsId };
                const mk = mahasiswa.find(x=>x.id===mhsId)?.krs.find(x=>x.kode===kode&&x.kelas===kelas);
                setText("hapusMKNama", mk ? mk.nama + " (Kelas " + kelas + ")" : kode);
                openModal("modalHapusMK");
            });
        });

        const total = m.krs.reduce((s,mk)=>s+mk.sks,0);
        if (totalEl) totalEl.textContent = total;
        setText("detail-sks", total + " SKS");
    }

    /* Search */
    document.getElementById("searchMahasiswa")?.addEventListener("input", function() {
        renderStudentList(this.value);
    });

    /* Tombol Tambah Kelas */
    document.getElementById("btnAddKelas")?.addEventListener("click", () => {
        const m = mahasiswa.find(x=>x.id===activeStudentId);
        if (!m) return;
        setText("modal-student-name", m.name);
        const srch = document.getElementById("searchKelas");
        if (srch) srch.value = "";
        renderModalKelasResults("", m);
        openModal("modalAddKelas");
    });

    function renderModalKelasResults(q, m) {
        const container = document.getElementById("modalKelasResults");
        if (!container) return;
        if (!m) m = mahasiswa.find(x=>x.id===activeStudentId);
        if (!m) return;
        const existing = new Set(m.krs.map(k => k.kode+"-"+k.kelas));
        const keyword = (q||"").toLowerCase();
        const filtered = availableMK.filter(mk =>
            mk.kode.toLowerCase().includes(keyword) || mk.nama.toLowerCase().includes(keyword));

        if (!filtered.length) {
            container.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:#94A3B8;">Mata kuliah tidak ditemukan.</div>`;
            return;
        }
        container.innerHTML = filtered.map(mk => {
            const key = mk.kode+"-"+mk.kelas;
            const isAdded = existing.has(key);
            return `
            <div class="modal-course-item${isAdded?" is-added":""}">
                <div>
                    <div class="modal-course-name">${esc(mk.nama)}</div>
                    <div class="modal-course-code">${esc(mk.kode)} · Kelas ${esc(mk.kelas)} · ${mk.sks} SKS</div>
                </div>
                ${isAdded
                    ? `<span class="modal-course-badge added">Ditambahkan</span>`
                    : `<button class="modal-course-badge add-btn"
                            data-add-kode="${esc(mk.kode)}" data-add-nama="${esc(mk.nama)}"
                            data-add-kelas="${esc(mk.kelas)}" data-add-sks="${mk.sks}">+ Tambah</button>`
                }
            </div>`;
        }).join("");

        container.querySelectorAll(".add-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const m = mahasiswa.find(x=>x.id===activeStudentId);
                if (!m) return;
                m.krs.push({ kode:btn.dataset.addKode, nama:btn.dataset.addNama, kelas:btn.dataset.addKelas, sks:parseInt(btn.dataset.addSks) });
                m.sks = m.krs.reduce((s,k)=>s+k.sks,0);
                renderModalKelasResults(document.getElementById("searchKelas")?.value||"", m);
                renderKrsRows(m);
                renderStudentList(document.getElementById("searchMahasiswa")?.value||"");
            });
        });
    }

    document.getElementById("searchKelas")?.addEventListener("input", function() {
        renderModalKelasResults(this.value);
    });

    /* Hapus MK konfirmasi */
    document.getElementById("btnKonfirmasiHapusMK")?.addEventListener("click", () => {
        if (!pendingRemoveMK) { closeModal("modalHapusMK"); return; }
        const { kode, kelas, mhsId } = pendingRemoveMK;
        const m = mahasiswa.find(x=>x.id===mhsId);
        if (m) {
            m.krs = m.krs.filter(x => !(x.kode===kode && x.kelas===kelas));
            m.sks = m.krs.reduce((s,k)=>s+k.sks,0);
        }
        pendingRemoveMK = null;
        closeModal("modalHapusMK");
        renderStudentDetail(mhsId);
        renderStudentList(document.getElementById("searchMahasiswa")?.value||"");
    });

    /* Simpan Catatan */
    document.getElementById("btnSimpanCatatan")?.addEventListener("click", function() {
        const m = mahasiswa.find(x=>x.id===activeStudentId);
        const catEl = document.getElementById("catatanPerwalian");
        if (!m || !catEl) return;
        m.catatan = catEl.value;
        const orig = this.textContent;
        this.textContent = "Tersimpan ✓"; this.disabled = true;
        setTimeout(() => { this.textContent = orig; this.disabled = false; }, 1800);
    });

    renderStudentList("");
}
