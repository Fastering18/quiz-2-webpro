<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi - myITS Classroom</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <main class="login-wrapper">
        <div class="login-card">
            <img class="logo" src="./image/Classroom.png" alt="Logo myITS Classroom">
            <div class="header-text">
                <h1>Atur ulang kata sandi</h1>
                <p>Buat kata sandi baru untuk akun Anda.</p>
            </div>
            <div id="reset-alert" class="alert alert-danger d-none"><h4>Gagal</h4><p></p></div>
            <div id="reset-ok" class="alert alert-info d-none"><h4>Berhasil</h4><p>Kata sandi diperbarui. <a href="/">Masuk sekarang</a>.</p></div>
            <form id="resetForm" class="login-form">
                <div class="input-group"><label for="r-email">Email</label>
                    <div class="input-wrapper"><input type="email" id="r-email" value="{{ request('email') }}" required></div></div>
                <div class="input-group"><label for="r-token">Token</label>
                    <div class="input-wrapper"><input type="text" id="r-token" value="{{ request('token') }}" required></div></div>
                <div class="input-group"><label for="r-pass">Kata sandi baru</label>
                    <div class="input-wrapper"><input type="password" id="r-pass" minlength="8" required></div></div>
                <div class="input-group"><label for="r-pass2">Ulangi kata sandi baru</label>
                    <div class="input-wrapper"><input type="password" id="r-pass2" minlength="8" required></div></div>
                <button type="submit" class="btn-primary">Simpan kata sandi</button>
                <div class="back-link"><a href="/">Kembali ke halaman masuk</a></div>
            </form>
        </div>
    </main>
    <script src="/reset-password.js"></script>
</body>
</html>
