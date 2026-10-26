Halo {{ $nama }},

Kami menerima permintaan untuk mengatur ulang kata sandi akun Portal Quiz 2 WebPro Anda.

Buka tautan berikut untuk membuat kata sandi baru:
{{ $resetUrl }}

Pengguna API: kirim token berikut ke POST /api/reset-password
(bersama email dan password baru):
{{ $token }}

Tautan dan token berlaku selama {{ $expireMinutes }} menit dan hanya bisa dipakai satu kali.
Jika Anda tidak meminta pengaturan ulang, abaikan email ini; kata sandi Anda tidak akan berubah.
