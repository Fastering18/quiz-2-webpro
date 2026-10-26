// public/js/portal.js - Logic for /portal dashboard
document.addEventListener("DOMContentLoaded", function () {
    const statSksAktif = document.getElementById("stat-sks-aktif");
    const statTugasCount = document.getElementById("stat-tugas-count");

    if (!statSksAktif && !statTugasCount) return;

    window.apiFetch("/api/dashboard")
        .then(function (data) {
            if (!data) return;

            const user = data.user || {};
            const summary = data.summary || {};

            // Welcome banner elements
            const elBannerName = document.getElementById("banner-name");
            const elBannerDept = document.getElementById("banner-dept");
            const elBannerSem  = document.getElementById("banner-semester");

            if (elBannerName && user.nama_lengkap) elBannerName.textContent = user.nama_lengkap;
            if (elBannerDept) {
                if (user.departemen) elBannerDept.textContent = "Prodi " + user.departemen;
                else if (data.dosen?.departemen) elBannerDept.textContent = "Departemen " + data.dosen.departemen;
            }
            if (elBannerSem) {
                if (summary.semester_tahun) {
                    elBannerSem.textContent = "Semester " + (user.semester_aktif ? user.semester_aktif + " " : "") + "(" + summary.semester_tahun + ")";
                } else if (data.total_kelas_ajar !== undefined) {
                    elBannerSem.textContent = data.total_kelas_ajar + " Kelas Diampu";
                }
            }

            // Student stats
            if (statSksAktif) {
                if (summary.total_sks !== undefined) {
                    statSksAktif.textContent = summary.total_sks;
                    const statSksTotal = document.getElementById("stat-sks-total");
                    if (statSksTotal) statSksTotal.textContent = summary.total_sks + " Total SKS Terdaftar";
                } else if (data.total_kelas_ajar !== undefined) {
                    const label = statSksAktif.closest('.stat-card')?.querySelector('.stat-label');
                    if (label) label.textContent = "Kelas Diampu";
                    statSksAktif.textContent = data.total_kelas_ajar;
                    const statSksTotal = document.getElementById("stat-sks-total");
                    if (statSksTotal) statSksTotal.textContent = "Semester Aktif";
                }
            }

            if (statTugasCount) {
                const pendingCount = summary.tugas_belum_selesai ?? 0;
                const statTugasDesc = document.getElementById("stat-tugas-desc");
                if (summary.tugas_belum_selesai !== undefined) {
                    statTugasCount.textContent = pendingCount;
                    if (statTugasDesc) {
                        if (pendingCount > 0) {
                            statTugasDesc.textContent = pendingCount + " tugas belum dikerjakan";
                            statTugasDesc.className = "stat-desc text-danger";
                        } else {
                            statTugasDesc.textContent = "Semua tugas telah diselesaikan";
                            statTugasDesc.className = "stat-desc text-info";
                        }
                    }
                } else if (data.classrooms) {
                    const label = statTugasCount.closest('.stat-card')?.querySelector('.stat-label');
                    if (label) label.textContent = "Mata Kuliah Aktif";
                    statTugasCount.textContent = data.classrooms.length;
                    if (statTugasDesc) {
                        statTugasDesc.textContent = "Terjadwal semester ini";
                        statTugasDesc.className = "stat-desc text-info";
                    }
                }
            }
        })
        .catch(function (err) {
            console.error("Gagal memuat ringkasan dashboard:", err);
        });
});
