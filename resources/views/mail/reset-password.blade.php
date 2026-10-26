<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:8px;padding:32px;">
<tr><td>
<h2 style="margin:0 0 16px;color:#1d4ed8;">Portal Quiz 2 WebPro</h2>
<p>Halo {{ $nama }},</p>
<p>Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Klik tombol di bawah ini untuk membuat kata sandi baru.</p>
<p style="margin:24px 0;"><a href="{{ $resetUrl }}" style="background:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;display:inline-block;">Atur Ulang Kata Sandi</a></p>
<p>Jika tombol tidak berfungsi, salin tautan berikut ke peramban Anda:<br><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>
<p>Pengguna API dapat memakai token berikut pada <code>POST /api/reset-password</code>:</p>
<p style="font-family:monospace;background:#f1f5f9;padding:10px;border-radius:4px;word-break:break-all;">{{ $token }}</p>
<p>Tautan dan token ini berlaku selama {{ $expireMinutes }} menit dan hanya dapat dipakai satu kali. Jika Anda tidak meminta pengaturan ulang, abaikan email ini; kata sandi Anda tidak akan berubah.</p>
<p style="color:#6b7280;font-size:12px;">Email otomatis dari support@brahmanapriambudi.my.id. Mohon tidak membalas email ini.</p>
</td></tr></table></td></tr></table>
</body>
</html>
