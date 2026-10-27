// public/js/profile.js - Profile info, avatar upload, and password change logic
document.addEventListener("DOMContentLoaded", function () {
    const profileNameInput = document.getElementById("profile-name");
    const changePasswordForm = document.getElementById("changePasswordForm");

    if (!profileNameInput && !changePasswordForm) return;

    // Toggle password visibility
    document.querySelectorAll(".toggle-pass-visibility").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const wrapper = this.closest(".password-input-wrapper");
            const input = wrapper?.querySelector("input");
            if (!input) return;
            const isPassword = input.type === "password";
            input.type = isPassword ? "text" : "password";
            this.setAttribute("aria-label", isPassword ? "Sembunyikan sandi" : "Tampilkan sandi");
            this.innerHTML = isPassword
                ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        });
    });

    function applyProfile(user) {
            if (!user) return;

            const mhs = user.mahasiswa || {};
            const dosenWali = mhs.dosen_wali?.user?.nama_lengkap || "-";

            const elName  = document.getElementById("profile-name");
            const elNrp   = document.getElementById("profile-nrp");
            const elDept  = document.getElementById("profile-dept");
            const elEmail = document.getElementById("profile-email");
            const elWali  = document.getElementById("profile-dosen-wali");
            const elPhone = document.getElementById("phone");
            const elAddr  = document.getElementById("address");
            const elAvatar = document.getElementById("profile-page-avatar");

            if (elName)  elName.value  = user.nama_lengkap || "";
            if (elNrp)   elNrp.value   = mhs.nrp || user.dosen?.nip || user.username || "";
            if (elDept)  elDept.value  = mhs.departemen || user.dosen?.departemen || user.role?.toUpperCase();
            if (elEmail) elEmail.value = user.email || "";
            if (elWali)  elWali.value  = dosenWali;
            if (elPhone) elPhone.value = user.nomor_telepon || "";
            if (elAddr)  elAddr.value  = user.alamat_rumah || "";
            if (elAvatar && user.user_id && user.foto_profil_key) {
                elAvatar.src = "/files/avatars/" + encodeURIComponent(user.user_id);
            }
    }

    const seededProfile = window.__INITIAL_DATA__?.profile || window.__AUTH_USER__;
    if (seededProfile) {
        applyProfile(seededProfile);
    } else {
        window.apiFetch("/api/profile")
            .then(function (res) { applyProfile(res?.profile); })
            .catch(function (err) { console.error("Gagal memuat profil:", err); });
    }

    // Contact info update form
    const contactForm = document.getElementById("contactForm");
    const profileFeedback = document.getElementById("profileFeedback");
    if (contactForm) {
        contactForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const phoneInput = document.getElementById("phone");
            const addrInput  = document.getElementById("address");
            const saveBtn    = document.getElementById("btnSaveContact");

            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = "Menyimpan...";
            }

            try {
                await window.apiFetch("/api/profile", {
                    method: "PUT",
                    body: JSON.stringify({
                        nomor_telepon: phoneInput?.value || null,
                        alamat_rumah: addrInput?.value || null
                    })
                });

                if (profileFeedback) {
                    profileFeedback.textContent = "Informasi kontak berhasil disimpan.";
                    profileFeedback.className = "classroom-feedback is-visible is-success";
                }
            } catch (err) {
                if (profileFeedback) {
                    profileFeedback.textContent = err.message || "Gagal memperbarui profil.";
                    profileFeedback.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = "Simpan Perubahan";
                }
            }
        });
    }

    // Avatar upload handler
    const btnChangeAvatar = document.getElementById("btnChangeAvatar");
    const avatarFileInput = document.getElementById("avatarFileInput");
    if (btnChangeAvatar && avatarFileInput) {
        btnChangeAvatar.addEventListener("click", () => avatarFileInput.click());
        avatarFileInput.addEventListener("change", async function () {
            if (!avatarFileInput.files || !avatarFileInput.files[0]) return;
            const file = avatarFileInput.files[0];

            try {
                const formData = new FormData();
                formData.append("_method", "PUT");
                formData.append("foto", file);

                const res = await window.apiFetch("/api/profile", {
                    method: "POST",
                    body: formData
                });

                if (res?.profile) {
                    const profile = res.profile;
                    window.__AUTH_USER__ = profile;
                    try {
                        profile._cachedAt = Date.now();
                        localStorage.setItem("user", JSON.stringify(profile));
                    } catch (e) {}
                    window.dispatchEvent(new CustomEvent("auth:user-updated", { detail: profile }));
                    if (profile.foto_profil_key && profile.user_id) {
                        const newUrl = "/files/avatars/" + encodeURIComponent(profile.user_id) + "?v=" + encodeURIComponent(profile.foto_profil_key);
                        const pageAvatar = document.getElementById("profile-page-avatar");
                        if (pageAvatar) pageAvatar.src = newUrl;
                    }
                }

                if (profileFeedback) {
                    profileFeedback.textContent = "Foto profil berhasil diperbarui.";
                    profileFeedback.className = "classroom-feedback is-visible is-success";
                }
            } catch (err) {
                if (profileFeedback) {
                    profileFeedback.textContent = err.message || "Gagal mengunggah foto profil.";
                    profileFeedback.className = "classroom-feedback is-visible is-error";
                }
            }
        });
    }

    // Password change form
    if (changePasswordForm) {
        changePasswordForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const oldPass  = document.getElementById("oldPassword");
            const newPass  = document.getElementById("newPassword");
            const confPass = document.getElementById("confirmPassword");
            const feedback = document.getElementById("passwordFeedback");
            const submitBtn = document.getElementById("btnSubmitPassword");

            if (!oldPass.value || !newPass.value || !confPass.value) {
                if (feedback) {
                    feedback.textContent = "Semua kolom kata sandi wajib diisi.";
                    feedback.className = "classroom-feedback is-visible is-error";
                }
                return;
            }
            if (newPass.value.length < 8) {
                if (feedback) {
                    feedback.textContent = "Sandi baru minimal 8 karakter.";
                    feedback.className = "classroom-feedback is-visible is-error";
                }
                return;
            }
            if (newPass.value !== confPass.value) {
                if (feedback) {
                    feedback.textContent = "Sandi baru dan konfirmasi sandi tidak cocok.";
                    feedback.className = "classroom-feedback is-visible is-error";
                }
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Menyimpan...";
            }

            try {
                await window.apiFetch("/api/profile/password", {
                    method: "PUT",
                    body: JSON.stringify({
                        current_password: oldPass.value,
                        password: newPass.value,
                        password_confirmation: confPass.value
                    })
                });

                if (feedback) {
                    feedback.textContent = "Kata sandi Anda berhasil diperbarui!";
                    feedback.className = "classroom-feedback is-visible is-success";
                }
                changePasswordForm.reset();
            } catch (err) {
                const msg = err.data?.errors?.current_password?.[0] || err.data?.message || err.message || "Gagal memperbarui sandi.";
                if (feedback) {
                    feedback.textContent = msg;
                    feedback.className = "classroom-feedback is-visible is-error";
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Simpan Sandi Baru";
                }
            }
        });
    }
});
