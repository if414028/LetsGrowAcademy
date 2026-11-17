# Operasional internal integrasi CRM

Dokumen ini untuk operator Academy. Dokumentasi yang dibagikan ke vendor tetap [crm-integration.md](crm-integration.md), tanpa instruksi deployment atau perintah internal.

## Konfigurasi yang perlu dilengkapi

- `CRM_ENVIRONMENT`: nama environment stabil, misalnya `staging` atau `production`. Default mengikuti `APP_ENV`. Gunakan kredensial berbeda dan pisahkan data CRM untuk masing-masing environment; `registration_id` dapat sama di database berbeda.
- `CRM_WHATSAPP_NUMBER`: nomor bisnis CRM format Indonesia, misalnya `62...`. Tombol chat disembunyikan jika nomor belum tersedia/tidak valid.
- `CRM_API_TOKEN` dan `CRM_WEBHOOK_SECRET`: minimal 32 karakter. Pastikan pemetaan dua nilai kiriman vendor telah dikonfirmasi. Tidak ada token asli di repository/PDF.
- `CRM_REGISTRATION_WEBHOOK_URL` dan `CRM_PAYMENT_WEBHOOK_URL`: dua URL HTTPS vendor. Endpoint pendaftaran juga harus menerima `registration.updated` dan `registration.deleted` serta rincian pembayaran v1.2 sebelum pengiriman diaktifkan.
- `CRM_WEBHOOKS_ENABLED=false` sampai konfigurasi dan penerimaan vendor siap. Event tetap disimpan untuk retry; saat diaktifkan, backlog ikut dikirim.

## Satu batch aktif untuk semua pendamping

Isi `RECRUITMENT_SEMINAR_ID`, `RECRUITMENT_SEMINAR_NAME`, `RECRUITMENT_BATCH_ID`, dan `RECRUITMENT_BATCH_STARTS_AT` sesuai identitas/jadwal yang disepakati. Waktu menggunakan ISO 8601 dengan offset atau Z, misalnya `2026-12-01T19:00:00+07:00` sebagai contoh format.

Nilai kosong diperbolehkan dan menghasilkan `null`. Form tidak menerima perubahan seminar/batch dari browser. Saat form dikirim, server menyalin konfigurasi batch ke record pendaftar. Batch berikutnya hanya memengaruhi pendaftaran baru; data historis tidak ditetapkan ulang otomatis. Jadwal ditampilkan dalam WIB, sedangkan API memakai UTC.

## Deployment dan pemeriksaan

1. Backup database dan `.env` environment tujuan. Pertahankan `APP_KEY` yang sudah digunakan.
2. Deploy source dan jalankan migration `2026_11_17_000001_extend_recruitment_crm_contract.php` serta `2026_11_17_000002_add_pricing_details_to_recruitment_event_payments.php` melalui `php artisan migrate --force` dalam maintenance singkat. Migration rincian biaya hanya menambahkan kolom nullable; tidak menulis ulang tagihan lama.
3. Jalankan `npm ci --include=dev` dan `npm run build`, atau unggah hasil build melalui pipeline yang digunakan. Perubahan CTA menambahkan CSS baru.
4. Perbarui cache dengan `php artisan optimize`; buka kembali aplikasi setelah seluruh perintah berhasil.
5. Jalankan `php artisan crm:integration-status`. Command ini menampilkan jumlah historis yang akan diambil API, status konfigurasi, mode Midtrans, batch aktif, dan backlog tanpa mencetak token atau data kontak.
6. Verifikasi GET tanpa token (401), dengan token (200), GET penghapusan, timestamp Z, serta tombol WA dan link lanjut bayar. Jangan menilai status lunas dari query string atau callback browser.

Checkout event memakai katalog kanal aktif, tarif, dan PPN dari `config/subscription.php` (`SUBSCRIPTION_PAYMENT_METHODS`, `SUBSCRIPTION_GATEWAY_VAT_PERCENT`). Pastikan kedua kunci Midtrans server/client tersedia. Biaya tetap event Rp5.000 tidak digunakan untuk transaksi baru. Uji VA, e-wallet, dan GoPay Dynamic QRIS: QRIS harus membuka mode `qr` dan menagih hanya harga kelas; e-wallet memakai mode `deeplink`. Status tetap diverifikasi server. Tagihan Pending menyimpan metode dan rincian biaya semula; peserta baru dapat memilih ulang setelah transaksi Failed/Expired. Transaksi lama tanpa snapshot/token tetap memakai URL dan nominal lama, tanpa mengarang rincian PPN.

Cron CRM yang sudah terpasang pada development adalah `/etc/cron.d/letsgrowacademy-dev-crm` dan menjalankan `crm:deliver-webhooks` setiap menit dengan `flock`. Jika menggunakan scheduler Laravel global pada environment lain, job sudah terdaftar di `routes/console.php`. Hindari menambahkan jadwal pengiriman duplikat.

## Prosedur pencabutan persetujuan WhatsApp

Operator memverifikasi permintaan peserta dan mencocokkan identitas/nomor serta ID pendaftar. Catat permintaan pada saluran operasional yang digunakan, kemudian jalankan:

```bash
php artisan crm:revoke-whatsapp-consent LGA-123
```

Command menandai `whatsapp_consent=false`, mencatat waktu pencabutan, mempertahankan waktu persetujuan awal, dan membuat event `registration.updated`. Snapshot tertunda lama yang masih membawa opt-in digantikan pembaruan ini. Pengulangan command tidak membuat event tambahan. Operator CRM juga menghentikan automation, termasuk jika webhook sedang tertunda atau pesan sudah dalam perjalanan. Klik tombol WA tidak otomatis mengaktifkan persetujuan kembali.

## Prosedur penghapusan

Setelah permintaan dan ID terverifikasi, jalankan:

```bash
php artisan crm:delete-registration LGA-123
```

Command meminta konfirmasi karena menghapus record peserta, baris pembayaran lokal, dan seluruh snapshot outbox terkait. Opsi `--force` hanya dipakai oleh operator yang sudah memastikan permintaan yang benar. Tindakan ini tidak membatalkan/refund transaksi Midtrans.

Catatan penghapusan tanpa PII serta event `registration.deleted` tetap ada agar vendor dapat menghapus salinannya dan melakukan recovery melalui GET penghapusan. Operator memantau respons 2xx dan menindaklanjuti bila CRM belum menerima. Gunakan command ini untuk prosedur integrasi; penghapusan SQL langsung atau cascade penghapusan akun tidak menghasilkan tombstone melalui service ini. Retensi backup dan data pada penyedia pembayaran dikelola melalui prosedur masing-masing.

## Data uji bersama vendor

Gunakan staging dengan Midtrans sandbox dan nomor penguji yang disepakati. Siapkan 2–3 pendaftaran melalui form, dengan variasi belum membuat checkout, Pending/Expired, dan satu pembayaran sandbox yang benar-benar diverifikasi gateway. Pendaftaran uji ini perlu tetap tersimpan selama vendor menguji GET dan webhook; tes otomatis repository memakai database terisolasi dan tidak menyediakan fixture permanen di VPS.

Urutan: konfirmasi GET API → konfirmasi webhook pendaftaran/pembayaran → konfirmasi tombol WA/link lanjut bayar → verifikasi opt-out/penghapusan → launch. Ikuti konfirmasi grup sebelum berpindah tahap. Informasi DNS dan IP CRM final tetap memerlukan akses/pemberitahuan dari pemegang akun DNS serta vendor.
