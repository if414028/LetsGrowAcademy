# Dokumentasi API CRM - Let's Grow Academy

Dokumen ini menjelaskan kontrak API v1.2 untuk integrasi data pendaftaran dan pembayaran antara Let's Grow Academy dan CRM. Integrasi WhatsApp, AI Agent, serta automation ditangani oleh CRM. Versi ini menambahkan rincian biaya transaksi dan PPN sesuai metode pembayaran. Environment, seminar/batch, link lanjut bayar, pembaruan persetujuan, dan penghapusan tetap tersedia; path API v1 tetap sama.

## Kontrak untuk vendor

Base URL memakai domain HTTPS aplikasi yang disepakati untuk masing-masing environment. Seluruh respons dan webhook berbentuk JSON, menggunakan prefix `/api/v1/crm` dan `schema_version: "1.2"`. Host staging adalah `https://dev.letsgrowacademy.id`; host production yang direncanakan adalah `https://letsgrowacademy.id`, dengan kesiapan akses dikonfirmasi terpisah.

| Fitur | Arah | Endpoint/event |
| --- | --- | --- |
| Registration Webhook | Academy → CRM | POST ke URL vendor; `registration.created` |
| Pembaruan pendaftar/persetujuan WA | Academy → CRM | POST ke URL pendaftaran; `registration.updated` |
| Penghapusan pendaftar | Academy → CRM | POST ke URL pendaftaran; `registration.deleted` |
| Payment Status Webhook | Academy → CRM | POST ke URL vendor; `payment.updated` |
| Daftar pendaftar | CRM → Academy | `GET /api/v1/crm/registrations` |
| Riwayat ID pendaftar dihapus | CRM → Academy | `GET /api/v1/crm/registrations/deleted` |
| Detail pendaftar | CRM → Academy | `GET /api/v1/crm/registrations/LGA-123` |

`registration_id` berbentuk `LGA-{nomor}`, unik dan stabil selama umur pendaftaran dalam satu environment. Nomor WhatsApp yang sama boleh mempunyai beberapa pendaftaran, termasuk melalui pemilik halaman berbeda. Gunakan pasangan `(environment, registration_id)` sebagai kunci upsert. Contohnya, `LGA-1` di staging dan production adalah dua record berbeda. Kredensial dan penyimpanan data uji dipisahkan dari production. ID checkout tidak diberikan sebagai field terpisah; link lanjut bayar yang mengandung akses checkout hanya diberikan melalui API yang terautentikasi.

Kredensial CRM adalah kredensial integrasi pusat: mempunyai akses baca seluruh pendaftaran dengan sumber `recruitment_landing`, lintas pemilik landing page. Login member tidak memberikan akses API ini. Data pelanggan, subscription, dan pembayaran iklan tidak termasuk.

### GET Registration API

Header wajib:

```http
Authorization: Bearer <CRM_API_TOKEN>
Accept: application/json
```

Token acak minimal 32 karakter disimpan pada server kedua pihak, terpisah dari secret webhook dan kunci Midtrans. API hanya menerima HTTPS, dengan batas 60 request per menit per IP. Tidak ada endpoint CRM untuk mengubah status pembayaran.

| Parameter daftar | Ketentuan |
| --- | --- |
| `per_page` | Integer 1–100; default 50 |
| `cursor` | Nilai `meta.next_cursor` dari halaman sebelumnya |
| `updated_since` | Opsional, waktu ISO 8601 dengan offset atau `Z`, dengan atau tanpa pecahan detik (1–6 digit). Contoh `2026-11-17T10:00:00+07:00` atau `2026-11-17T03:00:00.123Z`. Batas inklusif (`>=`), termasuk perubahan pembayaran/persetujuan. URL-encode tanda `+`. |

Contoh request:

```bash
curl --get 'https://academy.example/api/v1/crm/registrations' \
  --header "Authorization: Bearer $CRM_API_TOKEN" \
  --header 'Accept: application/json' \
  --data-urlencode 'updated_since=2026-11-13T10:00:00+07:00' \
  --data-urlencode 'per_page=50'
```

Contoh satu objek pendaftaran (schema `data` yang juga dipakai webhook):

```json
{
  "registration_id": "LGA-123",
  "environment": "staging",
  "full_name": "Peserta Contoh",
  "whatsapp_number": "+6281234567890",
  "domicile": "Bandung",
  "payment_status": "pending",
  "registered_at": "2026-11-13T03:00:00.000000Z",
  "updated_at": "2026-11-13T03:00:00.000000Z",
  "paid_at": null,
  "whatsapp_consent": true,
  "whatsapp_consented_at": "2026-11-13T03:00:00.000000Z",
  "whatsapp_consent_version": "2026-11-13",
  "whatsapp_consent_revoked_at": null,
  "seminar_id": "SEM-01",
  "seminar_name": "Seminar Pengantar",
  "batch_id": "BATCH-01",
  "batch_starts_at": "2026-11-25T12:00:00.000000Z",
  "continue_payment_url":
    "https://academy.example/rekrutmen/nama-pendamping/pembayaran/6e9e9bd6-e088-43e5-b869-1fb45b61c0a0",
  "source": {
    "type": "recruitment_landing",
    "slug": "nama-pendamping",
    "url": "https://academy.example/rekrutmen/nama-pendamping",
    "owner_user_id": 15
  },
  "payment": null,
  "last_event_sequence": 501
}
```

Respons daftar membungkus objek-objek tersebut pada array `data`, disertai:

```json
{
  "schema_version": "1.2",
  "environment": "staging",
  "next_cursor": null,
  "has_more": false,
  "sync_started_at": "2026-11-17T03:05:00Z"
}
```

Objek di atas berada pada key `meta`. Detail menggunakan `{ "data": objek_pendaftaran }`. Semua timestamp respons, termasuk `meta.sync_started_at`, memakai ISO 8601 UTC (`Z`). Nilai seminar, batch, dan jadwal pada contoh bersifat ilustratif.

Untuk recovery, ambil semua halaman menggunakan filter yang sama. Simpan `sync_started_at` **dari halaman pertama** hanya setelah seluruh halaman berhasil. Pada putaran berikutnya, pakai waktu awal tersebut dikurangi 60 detik sebagai `updated_since`, lalu upsert ulang berdasarkan ID. Overlap mencegah perubahan selama pagination dan ketelitian timestamp database terlewat. Jangan menjadikan waktu selesai sinkronisasi sebagai watermark. Putaran awal tanpa `updated_since` mengambil seluruh data yang tersimpan, termasuk data lama. API membaca status lokal terakhir yang telah diverifikasi; API ini tidak melakukan polling Midtrans secara langsung.

`last_event_sequence` adalah nomor event terakhir untuk pendaftaran tersebut (0 untuk data historis tanpa event). Saat menggabungkan hasil GET dengan webhook, pertahankan state dengan sequence terbesar dalam environment yang sama; jangan menurunkan state karena webhook lama tiba belakangan. Jika sequence sama, hasil GET boleh memperbarui snapshot.

Polling setiap lima menit didukung. Gunakan `per_page=100` jika diperlukan, jeda sekitar dua detik antarhalaman, dan tangani `429` dengan mengikuti header `Retry-After`. Batas 60 request per menit berlaku untuk seluruh endpoint CRM dari IP yang sama.

### Seminar, batch, dan link lanjut bayar

Satu batch aktif dipakai bersama oleh seluruh landing page pendamping. `seminar_id`, `seminar_name`, `batch_id`, dan `batch_starts_at` dicatat saat pendaftaran diterima. Perubahan batch aktif berikutnya tidak mengubah nilai pada peserta yang sudah terdaftar. Field yang belum diketahui, termasuk pada data historis, bernilai `null`; source slug dan `owner_user_id` menunjukkan atribusi pendamping, bukan identitas seminar.

`continue_payment_url` mengarah ke halaman pembayaran Academy untuk peserta yang belum membayar, termasuk Failed/Expired. Membuka link ini tidak langsung membuat transaksi baru. Academy membuat atau melanjutkan transaksi saat peserta menekan tombol bayar. Nilainya `null` untuk peserta Paid/Refunded dan pendaftaran lama yang tidak mempunyai checkout online. Link ini merupakan akses pribadi ke checkout; CRM membagikannya kepada peserta yang sudah dicocokkan dengan pendaftaran terkait, bukan berdasarkan kode pendaftaran saja. Jangan mengubahnya menjadi link Midtrans atau membuat QR/transaksi dari sisi CRM.

Error: `401` token salah/tidak ada; `403` akses HTTP; `404` ID tidak ditemukan/di luar sumber rekrutmen; `422` filter/cursor tidak valid; `429` rate limit; `503` token integrasi belum dikonfigurasi. Respons data menggunakan `Cache-Control: private, no-store`.

### Registration Webhook

Event disimpan **bersamaan dengan submit form yang valid**, tanpa menunggu peserta membuka gateway atau membayar. Form tetap menyimpan pendaftar saat CRM sedang tidak tersedia. Pengiriman webhook berlangsung secara asynchronous, dengan interval pemrosesan normal satu menit. Waktu penerimaan dapat bertambah saat terdapat antrean atau retry.

Envelope:

| Field | Isi |
| --- | --- |
| `schema_version` | `"1.2"` |
| `environment` | Environment sumber, misalnya `staging` atau `production` |
| `event_id` | UUID unik; tetap sama pada setiap retry |
| `event_type` | `registration.created` |
| `sequence` | Integer urut global; urutkan state per registration berdasarkan nilai ini |
| `occurred_at` | Waktu event dibuat, UTC |
| `data` | Objek pendaftaran dengan schema dan contoh di atas |

Payload merupakan snapshot saat event terjadi. Retry mengirim snapshot yang sama, walaupun peserta sudah membayar setelah event awal dibuat.

`registration.updated` memakai envelope dan snapshot pendaftaran yang sama, untuk pembaruan persetujuan atau penyiapan link checkout bagi data lama. Event ini bukan bukti pembayaran dan tidak selalu berarti pencabutan persetujuan; baca nilai field pada snapshot. Saat link data lama disiapkan, `continue_payment_url` menjadi tersedia tanpa membuat transaksi gateway, sehingga `payment` masih dapat bernilai `null`.

### Payment Status Webhook

Envelope sama dengan Registration Webhook, dengan `event_type: "payment.updated"`, `data` berisi snapshot pendaftaran terbaru pada saat event, serta key tambahan `transaction` untuk percobaan pembayaran yang berubah. Contoh objek `transaction` (juga schema objek `data.payment` pada GET):

```json
{
  "transaction_id": "a70c5cd1-0df7-4ee9-bb7e-d5d4464ed681",
  "order_id": "REVT-e3b5fca1-2342-4be6-81e7-f9f924491daf",
  "payment_status": "paid",
  "paid_at": "2026-11-13T03:15:00.000000Z",
  "updated_at": "2026-11-13T03:15:02.000000Z",
  "class_amount": 88000,
  "service_fee": 4440,
  "selected_payment_method": "bni_va",
  "payment_method_label": "BNI Virtual Account",
  "transaction_fee": 4000,
  "gateway_vat_percent": 11,
  "gateway_vat_amount": 440,
  "merchant_covers_fee": false,
  "total_amount": 92440,
  "currency": "IDR"
}
```

Status menggunakan huruf kecil: `pending`, `paid`, `failed`, `expired`, dan tambahan `refunded` untuk refund penuh/chargeback. `expire` dipetakan menjadi `expired`; `deny`, `cancel`, dan `failure` menjadi `failed`. Partial refund belum dimodelkan sebagai status tersendiri.

`transaction_id` adalah ID dari Midtrans, bukan `order_id`. Nilainya dapat `null` sebelum Midtrans memberikan ID transaksi, termasuk saat Snap baru dibuat dan peserta belum memilih metode pembayaran. `data.payment` juga `null` jika checkout belum dibuat. Event `pending` dikirim saat checkout dibuat, dan dikirim lagi jika ID transaksi baru diketahui. `paid_at` bernilai `null` sebelum pembayaran berhasil. Waktu pembayaran memakai `settlement_time` bila tersedia; jika tidak, memakai waktu pertama server memverifikasi pembayaran berhasil. Waktu tersebut tidak diambil dari waktu pembuatan transaksi (`transaction_time`).

`data.payment_status` adalah status **pendaftaran secara keseluruhan**. `transaction.payment_status` adalah status **percobaan transaksi yang berubah**. Gunakan status pendaftaran untuk automation peserta. Transaksi gagal lama tidak boleh membatalkan status pendaftaran yang sudah dibayar melalui percobaan lain. Bila peserta mencoba lagi setelah Failed/Expired, `order_id` baru dibuat dan status kembali Pending. `data.payment` menunjuk pembayaran sukses bila ada, atau percobaan terbaru jika belum ada yang sukses.

Harga kelas adalah **Rp88.000**. Untuk transaksi baru, biaya tambahan mengikuti metode pembayaran yang dipilih peserta dan konfigurasi tarif merchant yang juga digunakan checkout subscription. PPN hanya dikenakan atas biaya transaksi. Contoh konfigurasi saat ini: VA Rp4.000 + PPN Rp440, sehingga total Rp92.440; GoPay total Rp92.090 dengan biaya Rp3.684 + PPN Rp406; GoPay Dynamic QRIS total Rp88.000 karena biaya dan PPN gateway ditanggung Academy. Tarif dapat berubah; gunakan nominal pada snapshot API, jangan menetapkan total tetap di CRM.

`service_fee` tetap tersedia sebagai **total biaya tambahan yang ditagihkan kepada peserta**, yaitu `transaction_fee + gateway_vat_amount`. `total_amount = class_amount + service_fee`; jangan menambahkan PPN kembali ke `total_amount`. `selected_payment_method` adalah pilihan checkout, misalnya `gopay_qris`, dan bukan klaim mengenai nama kanal aktual yang dilaporkan gateway. Untuk QRIS, `service_fee`, `transaction_fee`, dan `gateway_vat_amount` bernilai 0 serta `merchant_covers_fee=true`.

Rincian dan metode disimpan saat transaksi dibuat. Tagihan Pending memakai snapshot tersebut meskipun tarif aktif berubah. Setelah Failed/Expired, peserta dapat memilih metode lain untuk percobaan baru. Transaksi lama tetap memakai nominal aslinya (termasuk biaya tambahan Rp5.000 pada tagihan lama); field rincian/metode yang dahulu belum dicatat bernilai `null`, bukan 0. Snapshot webhook lama tidak diubah atau dikirim ulang hanya karena pembaruan skema.

Status dibaca server-to-server dari Midtrans setelah validasi signature notifikasi, kecocokan order, nominal, dan status antifraud. Callback browser tidak menjadi bukti pembayaran. Acuan gateway: [Midtrans HTTP notifications](https://docs.midtrans.com/docs/https-notification-webhooks) dan [Get Transaction Status](https://docs.midtrans.com/reference/get-transaction-status).

Notifikasi gateway yang identik tidak menghasilkan event status ganda. Koreksi pembayaran manual pada pendaftaran historis yang masih diizinkan aplikasi juga menghasilkan `payment.updated`; tidak ada key `transaction` jika tidak memiliki transaksi gateway.

### Autentikasi webhook dan penanganan retry

Setiap POST memakai HTTPS dengan verifikasi sertifikat TLS dan header:

```http
Content-Type: application/json
X-LGA-Event-Id: <event_id>
X-LGA-Event-Type: registration.created
X-LGA-Timestamp: <unix_seconds>
X-LGA-Signature: v1=<hmac_sha256_hex>
```

Signature dihitung menggunakan:

```text
HMAC-SHA256(secret=CRM_WEBHOOK_SECRET, message=timestamp + "." + raw_request_body)
```

Vendor perlu memverifikasi signature dengan constant-time comparison menggunakan **body mentah sebelum decode JSON**, mencocokkan event ID/type header dengan body, dan menolak timestamp di luar toleransi 5 menit. Signature dan timestamp dibuat ulang pada setiap percobaan; `event_id`, `sequence`, dan payload tetap. Sinkronkan jam server kedua pihak.

CRM harus menyimpan event/upsert secara durable sebelum memberikan respons **2xx**. Bila proses lanjutan berjalan asynchronous, `202` boleh dipakai setelah data masuk antrean durable CRM. Kiriman event yang sudah pernah diterima tetap dibalas 2xx. Respons non-2xx (termasuk redirect), timeout, dan gangguan koneksi dianggap gagal. Redirect tidak diikuti.

Retry otomatis setelah 1 menit, 5 menit, 15 menit, 1 jam, 3 jam, 6 jam, lalu 24 jam berulang sampai berhasil. Timeout request 10 detik; connect timeout 3 detik. Waktu aktual mengikuti jadwal pengiriman. Event yang belum mendapat respons 2xx tetap dijadwalkan untuk retry hingga berhasil.

Delivery bersifat **at least once**. Misalnya, CRM sudah menyimpan data tetapi responsnya timeout, event akan dikirim ulang. Vendor wajib deduplikasi dengan `event_id` dan upsert dengan pasangan `(environment, registration_id)`. Urutan kedatangan tidak dijamin: event pendaftaran/pembayaran membawa snapshot lengkap agar payment event dapat membuat record terlebih dahulu. Abaikan snapshot dengan sequence lebih kecil dari state yang sudah diterapkan.

Upsert tetap memakai pasangan `(environment, registration_id)`. Event v1.0/v1.1 yang sudah tersimpan sebelum pembaruan mempertahankan snapshot lamanya dan dapat dikirim selama recovery. Untuk event v1.0, environment ditentukan dari konfigurasi koneksi sumber; field baru yang belum ada dapat dilengkapi lewat GET jika datanya tersedia. Event penghapusan mempunyai bentuk data minimal, seperti dijelaskan berikut.

### Persetujuan WhatsApp dan data lama

API memberikan `whatsapp_consent`, `whatsapp_consented_at`, dan `whatsapp_consent_version` sebagai status, waktu, dan versi teks persetujuan komunikasi WhatsApp. `true` berarti peserta memilih setuju; `false` berarti tidak memberikan persetujuan atau sudah mencabutnya; `null` berarti status historis belum diketahui. Pencabutan ditandai `whatsapp_consent_revoked_at`. Waktu persetujuan awal tetap disimpan. Persetujuan data historis tidak diasumsikan sebagai persetujuan WhatsApp.

Saat pencabutan dicatat oleh Academy, CRM menerima `registration.updated` pada URL webhook pendaftaran, dengan envelope yang sama dan `data` berisi snapshot terbaru (`whatsapp_consent=false`). Snapshot lama yang belum terkirim digantikan pembaruan ini agar persetujuan lama tidak diterapkan kembali. CRM harus menghentikan automation tindak lanjut dan menghormati permintaan berhenti. Penanganan pesan yang sedang dalam perjalanan dan penghentian komunikasi dari sisi CRM perlu dikoordinasikan kedua operator.

Objek `source` menyediakan asal pendaftaran dan `owner_user_id` untuk atribusi peserta ke pemilik landing page terkait.

Pendaftar lama dapat diambil melalui GET. Event pendaftaran lama tidak dibuat ulang otomatis. ID transaksi dan waktu pembayaran per transaksi lama dapat kosong sampai tersedia dari verifikasi gateway; `data.paid_at` tetap menyediakan waktu pembayaran historis yang sudah tersimpan.

### Penghapusan dan recovery penghapusan

Setelah Academy memproses permintaan penghapusan, record tidak lagi muncul di daftar GET dan endpoint detail mengembalikan `404`. Snapshot lama terkait peserta dihapus dari outbox. Academy mengirim event `registration.deleted` ke URL webhook pendaftaran dengan bentuk berikut:

```json
{
  "schema_version": "1.2",
  "environment": "staging",
  "event_id": "3d7db659-7a6a-498f-8b48-e31770b05b16",
  "event_type": "registration.deleted",
  "sequence": 530,
  "occurred_at": "2026-11-17T03:20:00.000000Z",
  "data": {
    "registration_id": "LGA-123",
    "environment": "staging",
    "deleted_at": "2026-11-17T03:20:00.000000Z",
    "last_event_sequence": 530
  }
}
```

Event penghapusan tidak membawa nama, nomor WhatsApp, domisili, atau link checkout. CRM menghapus data pribadi terkait dan mempertahankan penanda minimal/sequence untuk mencegah snapshot lama membuat peserta tersebut muncul lagi. Retry, HMAC, dan respons 2xx mengikuti aturan webhook yang sama.

Jika webhook terlewat, gunakan `GET /api/v1/crm/registrations/deleted` dengan token dan parameter `updated_since`, `per_page`, serta `cursor` yang sama seperti endpoint daftar. Responsnya `{ "data": [objek_penghapusan], "meta": metadata_pagination }`; schema setiap objek sama dengan `data` pada contoh event di atas. Watermark recovery memakai `meta.sync_started_at` dari halaman pertama, dikurangi overlap 60 detik, setelah seluruh halaman berhasil diproses. Sinkronisasi berkala perlu membaca daftar pendaftaran dan daftar penghapusan.

Permintaan pencabutan/penghapusan diterima dan diverifikasi oleh operator Academy, termasuk permintaan yang diterima melalui CRM. API token CRM tetap hanya memberikan akses baca; tidak ada endpoint DELETE/PATCH untuk vendor. Penghapusan data pendaftaran bukan pembatalan atau refund pembayaran gateway.

### Tombol WhatsApp dan alur peserta

Halaman sukses pembayaran serta halaman pembayaran Pending/Failed/Expired dapat menampilkan tombol `Chat via WhatsApp` ke nomor bisnis CRM yang disepakati. Teks awal menyertakan `registration_id`: Paid menggunakan “Halo, saya sudah daftar seminar.”; Pending/Expired menggunakan “Halo, saya ingin melanjutkan pembayaran.”; Failed menggunakan “Halo, pembayaran saya gagal.”, masing-masing diikuti “Kode: LGA-123”.

Tombol hanya membuka WhatsApp dengan pesan terisi. Peserta tetap perlu menekan Kirim; klik tombol tidak mengubah persetujuan dan tidak dianggap sebagai pesan masuk. CRM menghubungkan chat dengan peserta setelah memverifikasi pendaftaran/nomor pengirim. Status lunas tetap mengacu ke API/webhook pembayaran yang telah diverifikasi Academy.

Transaksi baru menggunakan Snap popup agar metode dan mode QRIS/e-wallet sesuai pilihan peserta. Callback `onSuccess`, `onPending`, `onClose`, dan `onError` membawa peserta ke pemeriksaan status Academy; callback tidak mengubah status lunas. Setelah verifikasi server, Academy menampilkan halaman sukses atau halaman pembayaran beserta tombol WA sesuai status. Tagihan lama yang belum mempunyai token popup tetap dilanjutkan melalui Snap Redirect. CRM menangani percakapan setelah peserta mengirim pesan, sesuai persetujuan dan aturan platform.
