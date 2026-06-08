# Cesa WhatsApp Auth

Login admin menggunakan OTP WhatsApp sebagai **alternatif** login email/password. Alur
login lama tidak diubah; fitur ini menambahkan tombol "Masuk dengan WhatsApp" pada halaman
login admin dan halaman baru `/admin/whatsapp-login`.

## Cara kerja

1. Admin membuka halaman login dan menekan **Masuk dengan WhatsApp**.
2. Admin memasukkan nomor WhatsApp. Sistem mencari karyawan (`employees_employees`)
   berdasarkan kolom `mobile_phone`, `work_phone`, atau `private_phone`, lalu mengambil user
   tertaut yang berstatus aktif.
3. Kode OTP dikirim ke WhatsApp melalui gateway (Fonnte).
4. Admin memasukkan OTP. Bila valid, sistem login ke panel admin.

OTP disimpan ter-hash di tabel `whatsapp_otp_codes` dengan masa berlaku, batas percobaan, dan
cooldown kirim ulang. Tabel `users` inti tidak dimodifikasi.

## Instalasi

```bash
php artisan whatsapp-auth:install
```

Plug and play: begitu plugin terinstall, tautan **Masuk dengan WhatsApp** dan halaman
`/admin/whatsapp-login` otomatis aktif. Jika belum terinstall, fitur tidak muncul dan login
email/password berjalan seperti biasa. Tidak ada flag yang perlu diset di `.env`.

## Konfigurasi (opsional)

Plugin memakai kredensial Fonnte yang sudah dipakai fitur WhatsApp lain
(`WHATSAPP_PROVIDER`, `WHATSAPP_API_ENDPOINT`, `WHATSAPP_API_KEY`, `WHATSAPP_COUNTRY_CODE`,
`WHATSAPP_TIMEOUT`), jadi normalnya tidak perlu menambah apa pun. Driver pengirim mengikuti
`WHATSAPP_PROVIDER` (set `log` untuk pengembangan lokal tanpa mengirim WhatsApp).

> Catatan: OTP login tidak terpengaruh `WHATSAPP_ENABLED`. Selama plugin terinstall, login
> WhatsApp aktif dan tetap mengirim OTP.

Penyetelan OTP punya default di config (6 digit, 5 menit, 5 percobaan, cooldown 60 detik) dan
bisa di-override bila perlu:

```dotenv
WHATSAPP_AUTH_OTP_LENGTH=6
WHATSAPP_AUTH_OTP_TTL=300
WHATSAPP_AUTH_OTP_MAX_ATTEMPTS=5
WHATSAPP_AUTH_OTP_RESEND_COOLDOWN=60
```

Gateway dapat diperluas dengan mengimplementasikan
`Cesa\WhatsAppAuth\Contracts\WhatsAppGateway` dan mem-bind ulang di service container.
