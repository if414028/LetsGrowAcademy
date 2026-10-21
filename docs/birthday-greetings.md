# Birthday Greetings

Subscriber Health Planner dan Health Manager dapat membuka `/birthday-greetings`, mengaktifkan fitur, lalu memilih customer miliknya sendiri. Customer baru tidak otomatis terpilih. Menonaktifkan fitur menyimpan pilihan penerima, tetapi menghentikan pengiriman. Subscription kedaluwarsa, akun nonaktif, perubahan role, data tidak lengkap, atau perubahan pemilik customer juga menghentikan pengiriman untuk customer terkait.

## Instalasi

1. Jalankan `php artisan migrate` dan `npm run build`.
2. Jalankan Laravel scheduler di server, misalnya cron `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`.
3. Hubungkan gateway WhatsApp sebelum mengharapkan pengiriman. Tidak dibutuhkan queue worker untuk command ini.

Nomor pengirim default adalah `+6285121069310`. Pesan diproses mulai pukul 09.00 WIB pada hari ulang tahun. Scheduler memeriksa ulang setiap 15 menit sampai pukul 18.00 WIB untuk retry kegagalan dan penerima yang ditambahkan hari itu. Tanggal 29 Februari diperlakukan sebagai 28 Februari saat bukan tahun kabisat. Tidak ada pengiriman susulan untuk hari yang sudah lewat.

## Gateway WhatsApp

Proyek belum memiliki akun/provider WhatsApp. Adapter ini adalah kontrak integrasi untuk gateway milik admin, bukan URL API provider tertentu. Tanpa konfigurasi, halaman menampilkan **Menunggu layanan WhatsApp**; command selesai tanpa mengirim HTTP dan tanpa menandai pesan terkirim.

Tambahkan ke environment server:

```dotenv
BIRTHDAY_WHATSAPP_SENDER=+6285121069310
BIRTHDAY_WHATSAPP_GATEWAY_URL=https://gateway.example.com/whatsapp/messages
BIRTHDAY_WHATSAPP_GATEWAY_TOKEN=your-server-secret
```

URL contoh harus diganti dengan endpoint gateway nyata. Simpan token di environment server, jangan di browser. Setelah mengubah environment yang di-cache, jalankan `php artisan config:cache`.

Gateway harus mengautentikasi `Authorization: Bearer <token>`, menerima JSON berikut, dan mengirim pesan melalui akun WhatsApp admin yang telah terhubung:

```json
{
  "from": "+6285121069310",
  "to": "+6281234567890",
  "message": "Isi ucapan lengkap dengan nama customer dan identitas HP/HM"
}
```

Respons sukses wajib berupa HTTP 2xx dan JSON:

```json
{"success": true, "message_id": "provider-message-id"}
```

Respons ini berarti gateway/provider telah menerima pesan, bukan konfirmasi dibaca atau terkirim ke perangkat. Respons HTTP gagal atau respons tanpa acknowledgment tersebut disimpan sebagai `failed` dan dicoba kembali pada jadwal berikutnya di hari yang sama.

**Gateway wajib mendukung idempotensi.** Header `Idempotency-Key: birthday-{customer_id}-{YYYY-MM-DD}` harus menghasilkan maksimal satu pesan dan mengembalikan ID yang sama untuk retry, termasuk saat permintaan pertama timeout setelah provider menerima pesan. Retensi kunci minimal 48 jam. Unique constraint customer/tanggal mencegah dua proses dan perpindahan pemilik mengirim ulang; record `processing` yang terhenti dapat dicoba kembali setelah 30 menit dengan kunci yang sama. Sebelum gateway digunakan, pastikan adapter provider menerapkan kontrak ini, termasuk persyaratan akun/template yang diwajibkan oleh provider pilihan.

Command manual: `php artisan birthday-greetings:send`. Gunakan hanya setelah nomor pengirim dan gateway nyata siap karena command ini mengirim pesan ke penerima yang memenuhi syarat hari itu.

## Verifikasi

`php artisan test --filter=BirthdayGreetingsTest` menggunakan database SQLite terisolasi dan HTTP palsu; tidak mengirim pesan WhatsApp nyata.
