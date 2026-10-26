<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.seo-head', [
        'title' => 'Masuk',
        'description' => 'Masuk ke portal myITS Classroom Institut Teknologi Sepuluh Nopember untuk mengakses perkuliahan daring, materi, tugas mahasiswa, perpustakaan digital, dan KRS.',
        'robots' => 'index, follow'
    ])
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <main class="login-wrapper">
        <div class="login-card">
            <img class="logo" src="./image/Classroom.png" alt="Logo myITS Classroom">


            <div id="view-login" class="view-section">
                <div class="header-text">
                    <h1>Selamat datang di myITS Classroom</h1>
                    <p>Masukkan data akun Anda untuk masuk</p>
                </div>

                <div id="alert-error" class="alert alert-danger d-none">
                    <h4>Tidak dapat masuk</h4>
                    <p>Nama pengguna atau kata sandi salah. Periksa kembali dan coba lagi.</p>
                </div>

                <form id="loginForm" class="login-form">
                    <div class="input-group">
                        <label for="username">Nama pengguna</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" placeholder="Masukkan nama pengguna">
                        </div>
                        <span id="err-username" class="error-text d-none">Nama pengguna wajib diisi.</span>
                    </div>

                    <div class="input-group">
                        <label for="password">Kata sandi</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" placeholder="Masukkan kata sandi">

                            <span class="toggle-password" id="togglePassword">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                        <span id="err-password" class="error-text d-none">Kata sandi wajib diisi.</span>
                    </div>

                    <div class="forgot-password">
                        <a href="#" id="link-forgot-password">Lupa kata sandi?</a>
                    </div>

                    <button type="submit" class="btn-primary">Masuk</button>
                </form>
            </div>


            <div id="view-forgot-password" class="view-section d-none">
                <div class="header-text">
                    <h1>Lupa kata sandi?</h1>
                    <p>Masukkan email atau nama pengguna Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
                </div>
                <form id="forgotForm" class="login-form">
                    <div class="input-group">
                        <label for="email">Email atau nama pengguna</label>
                        <div class="input-wrapper">
                            <input type="text" id="email" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Kirim tautan pemulihan</button>
                    <div class="back-link">
                        <a href="#" class="btn-back">Kembali ke halaman masuk</a>
                    </div>
                </form>
            </div>

            <div id="view-check-email" class="view-section d-none">
                <div class="header-text">
                    <h1>Periksa email Anda</h1>
                    <p>Jika email tersebut terdaftar, Anda akan menerima tautan untuk mengatur ulang kata sandi.</p>
                </div>
                <div class="alert alert-info">
                    <h4>Periksa kotak masuk dan folder spam</h4>
                    <p>Buka tautan dalam email untuk membuat kata sandi baru.</p>
                </div>
                <p class="info-text">Email mungkin tiba dalam beberapa menit. Jika belum diterima, Anda dapat meminta tautan lagi.</p>
                <div class="back-link">
                    <a href="#" class="btn-primary btn-back-full">Kembali ke halaman masuk</a>
                </div>
            </div>

        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <span>© 2026 Institut Teknologi Sepuluh Nopember</span>
            <span>Bahasa Indonesia</span>
        </div>
    </footer>

    <script src="/home.js"></script>
</body>
</html>