document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("resetForm");
    const alertBox = document.getElementById("reset-alert");
    const ok = document.getElementById("reset-ok");
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        alertBox.classList.add("d-none");
        const btn = form.querySelector("button[type='submit']");
        btn.disabled = true;
        fetch("/api/reset-password", {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({
                email: document.getElementById("r-email").value.trim(),
                token: document.getElementById("r-token").value.trim(),
                password: document.getElementById("r-pass").value,
                password_confirmation: document.getElementById("r-pass2").value
            })
        }).then(async res => {
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                form.classList.add("d-none");
                ok.classList.remove("d-none");
            } else {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                alertBox.querySelector("p").innerText = first || data.message || "Gagal mengatur ulang kata sandi.";
                alertBox.classList.remove("d-none");
            }
        }).catch(() => {
            alertBox.querySelector("p").innerText = "Tidak dapat terhubung ke server.";
            alertBox.classList.remove("d-none");
        }).finally(() => { btn.disabled = false; });
    });
});
