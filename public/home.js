document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");
    const inputUsername = document.getElementById("username");
    const inputPassword = document.getElementById("password");
    
    const alertError = document.getElementById("alert-error");
    const errUsername = document.getElementById("err-username");
    const errPassword = document.getElementById("err-password");

    const viewLogin = document.getElementById("view-login");
    const viewForgot = document.getElementById("view-forgot-password");
    const viewCheckEmail = document.getElementById("view-check-email");

    loginForm.addEventListener("submit", function(e) {
        e.preventDefault(); // Mencegah form reload page

        alertError.classList.add("d-none");
        inputUsername.classList.remove("input-error");
        inputPassword.classList.remove("input-error");
        errUsername.classList.add("d-none");
        errPassword.classList.add("d-none");

        const valUser = inputUsername.value.trim();
        const valPass = inputPassword.value;

        let hasError = false;

        if (valUser === "") {
            inputUsername.classList.add("input-error");
            errUsername.classList.remove("d-none");
            hasError = true;
        }
        if (valPass === "") {
            inputPassword.classList.add("input-error");
            errPassword.classList.remove("d-none");
            hasError = true;
        }

        if (hasError) return; // Stop jika ada input kosong

        const submitBtn = loginForm.querySelector("button[type='submit']");
        const originalBtnText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = "Memproses...";

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

        fetch("/api/login", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {})
            },
            body: JSON.stringify({
                username: valUser,
                password: valPass
            })
        }).then(async res => {
            const data = await res.json()
            console.log(data)
            if (res.ok) {
                localStorage.setItem("auth_token", data.access_token);
                localStorage.setItem("user", JSON.stringify(data.user));
                window.location.href = "/portal";
            } else {
                const errorMsg = data.message || "Nama pengguna atau kata sandi salah.";
                alertError.querySelector("p").innerText = errorMsg;

                alertError.classList.remove("d-none");
                inputUsername.classList.add("input-error");
                inputPassword.classList.add("input-error");
            }
        }).catch(err => {
            console.error(err)
            alertError.querySelector("p").innerText = "Tidak dapat terhubung ke server. Silakan coba lagi nanti.";
            alertError.classList.remove("d-none");
        }).finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerText = originalBtnText;
        })
    });

    const togglePassword = document.getElementById("togglePassword");
    togglePassword.addEventListener("click", function() {
        const type = inputPassword.getAttribute("type") === "password" ? "text" : "password";
        inputPassword.setAttribute("type", type);
        
        // Ubah warna ikon saat diklik sebagai feedback visual
        if (type === "text") {
            this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#012D66" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"></path></svg>';
        } else {
            this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
    });

    
    // Klik "Lupa kata sandi?"
    document.getElementById("link-forgot-password").addEventListener("click", function(e) {
        e.preventDefault();
        viewLogin.classList.add("d-none");
        viewForgot.classList.remove("d-none");
    });

    // Form Lupa Password -> POST /api/forgot-password
    document.getElementById("forgotForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector("button[type='submit']");
        const label = btn.innerText;
        const value = document.getElementById("email").value.trim();
        if (!value) return;
        btn.disabled = true;
        btn.innerText = "Mengirim...";
        fetch("/api/forgot-password", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify(value.includes("@") ? { email: value } : { username: value })
        }).then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                viewForgot.classList.add("d-none");
                viewCheckEmail.classList.remove("d-none");
            } else {
                alert(data.message || "Permintaan gagal. Coba lagi nanti.");
            }
        }).catch(() => alert("Tidak dapat terhubung ke server. Silakan coba lagi nanti."))
          .finally(() => { btn.disabled = false; btn.innerText = label; });
    });

    // Klik tombol "Kembali ke halaman masuk"
    const backButtons = document.querySelectorAll(".btn-back, .btn-back-full");
    backButtons.forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            viewForgot.classList.add("d-none");
            viewCheckEmail.classList.add("d-none");
            viewLogin.classList.remove("d-none");
            
            loginForm.reset();
            alertError.classList.add("d-none");
            inputUsername.classList.remove("input-error");
            inputPassword.classList.remove("input-error");
            errUsername.classList.add("d-none");
            errPassword.classList.add("d-none");
        });
    });
});