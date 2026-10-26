// public/js/krs.js - KRS & Perwalian logic
document.addEventListener("DOMContentLoaded", async function () {
    const krsTableRows = document.getElementById("krsTableRows");
    if (!krsTableRows) return;

    try {
        const res = await window.apiFetch("/api/krs");
        const krsList = res?.krs_list || [];

        if (krsList.length === 0) {
            krsTableRows.innerHTML = '<div style="padding:16px; color:#64748B;">Belum ada Kartu Rencana Studi terdaftar.</div>';
            return;
        }

        const latestKrs = krsList[0];
        const detailRes = await window.apiFetch(`/api/krs/${latestKrs.krs_id}`);
        const krs = detailRes?.krs || latestKrs;
        const totalSks = detailRes?.total_sks ?? 0;

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
        if (elTotalSks) elTotalSks.textContent = `${totalSks} SKS`;
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
                <div class="col-kelas">${window.escapeHtml(cls.nama_kelas || "-")}</div>
                <div class="col-sks">${mk.sks || 0}</div>
            </div>`;
        }).join("");
    } catch (err) {
        console.error("Gagal memuat data KRS:", err);
        krsTableRows.innerHTML = '<div style="padding:16px; color:#B42318;">Gagal memuat data KRS. Silakan coba lagi.</div>';
    }
});
