# Dokumentasi API CRM - Let's Grow Academy

Dokumen ini menjelaskan kontrak API untuk integrasi data pendaftaran dan pembayaran antara Let's Grow Academy dan CRM. Integrasi WhatsApp, AI Agent, serta automation ditangani oleh CRM.

## Kontrak untuk vendor

Base URL memakai domain HTTPS aplikasi yang digunakan saat deployment. Seluruh respons dan webhook berbentuk JSON. Versi pertama menggunakan prefix `/api/v1/crm` dan `schema_version: "1.0"`.

| Fitur | Arah | Endpoint/event |
| --- | --- | --- |
| Registration Webhook | Academy → CRM | POST ke URL vendor; `registration.created` |
| Payment Status Webhook | Academy → CRM | POST ke URL vendor; `payment.updated` |
| Daftar pendaftar | CRM → Academy | `GET /api/v1/crm/registrations` |
| Detail pendaftar | CRM → Academy | `GET /api/v1/crm/registrations/LGA-123` |

`registration_id` berbentuk `LGA-{nomor}`, unik dan stabil selama umur pendaftaran. Nomor WhatsApp yang sama boleh mempunyai beberapa pendaftaran, termasuk melalui pemilik halaman berbeda. Gunakan `registration_id` sebagai kunci upsert, bukan nomor telepon. ID ini berbeda dari ID rahasia untuk membuka checkout; URL dan ID checkout tidak diberikan ke CRM.

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
| `updated_since` | Opsional, waktu ISO 8601 dengan offset, tanpa pecahan detik; contoh `2026-11-13T10:00:00+07:00` atau `2026-11-13T03:00:00Z`. Batas inklusif (`>=`), termasuk perubahan pembayaran. URL-encode tanda `+`. |

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
  "next_cursor": null,
  "has_more": false,
  "sync_started_at": "2026-11-13T10:05:00+07:00"
}
```

Objek di atas berada pada key `meta`. Detail menggunakan `{ "data": objek_pendaftaran }`. Semua field timestamp pada data pendaftaran/transaksi/webhook memakai ISO 8601 UTC (`Z`); `sync_started_at` menyertakan offset zona aplikasi.

Untuk recovery, ambil semua halaman menggunakan filter yang sama. Simpan `sync_started_at` **dari halaman pertama** hanya setelah seluruh halaman berhasil. Pada putaran berikutnya, pakai waktu awal tersebut dikurangi 60 detik sebagai `updated_since`, lalu upsert ulang berdasarkan ID. Overlap mencegah perubahan selama pagination dan ketelitian timestamp database terlewat. Jangan menjadikan waktu selesai sinkronisasi sebagai watermark. Putaran awal tanpa `updated_since` mengambil seluruh data yang tersimpan, termasuk data lama. API membaca status lokal terakhir yang telah diverifikasi; API ini tidak melakukan polling Midtrans secara langsung.

`last_event_sequence` adalah nomor event terakhir untuk pendaftaran tersebut (0 untuk data historis tanpa event). Saat menggabungkan hasil GET dengan webhook, pertahankan state dengan sequence terbesar; jangan menurunkan state karena webhook lama tiba belakangan. Jika sequence sama, hasil GET boleh memperbarui snapshot.

Error: `401` token salah/tidak ada; `403` akses HTTP; `404` ID tidak ditemukan/di luar sumber rekrutmen; `422` filter/cursor tidak valid; `429` rate limit; `503` token integrasi belum dikonfigurasi. Respons data menggunakan `Cache-Control: private, no-store`.

### Registration Webhook

Event disimpan **bersamaan dengan submit form yang valid**, tanpa menunggu peserta membuka gateway atau membayar. Form tetap menyimpan pendaftar saat CRM sedang tidak tersedia. Pengiriman webhook berlangsung secara asynchronous, dengan interval pemrosesan normal satu menit. Waktu penerimaan dapat bertambah saat terdapat antrean atau retry.

Envelope:

| Field | Isi |
| --- | --- |
| `schema_version` | `"1.0"` |
| `event_id` | UUID unik; tetap sama pada setiap retry |
| `event_type` | `registration.created` |
| `sequence` | Integer urut global; urutkan state per registration berdasarkan nilai ini |
| `occurred_at` | Waktu event dibuat, UTC |
| `data` | Objek pendaftaran dengan schema dan contoh di atas |

Payload merupakan snapshot saat event terjadi. Retry mengirim snapshot yang sama, walaupun peserta sudah membayar setelah event awal dibuat.

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
  "service_fee": 5000,
  "total_amount": 93000,
  "currency": "IDR"
}
```

Status menggunakan huruf kecil: `pending`, `paid`, `failed`, `expired`, dan tambahan `refunded` untuk refund penuh/chargeback. `expire` dipetakan menjadi `expired`; `deny`, `cancel`, dan `failure` menjadi `failed`. Partial refund belum dimodelkan sebagai status tersendiri.

`transaction_id` adalah ID dari Midtrans, bukan `order_id`. Nilainya dapat `null` sebelum Midtrans memberikan ID transaksi, termasuk saat Snap baru dibuat dan peserta belum memilih metode pembayaran. `data.payment` juga `null` jika checkout belum dibuat. Event `pending` dikirim saat checkout dibuat, dan dikirim lagi jika ID transaksi baru diketahui. `paid_at` bernilai `null` sebelum pembayaran berhasil. Waktu pembayaran memakai `settlement_time` bila tersedia; jika tidak, memakai waktu pertama server memverifikasi pembayaran berhasil. Waktu tersebut tidak diambil dari waktu pembuatan transaksi (`transaction_time`).

`data.payment_status` adalah status **pendaftaran secara keseluruhan**. `transaction.payment_status` adalah status **percobaan transaksi yang berubah**. Gunakan status pendaftaran untuk automation peserta. Transaksi gagal lama tidak boleh membatalkan status pendaftaran yang sudah dibayar melalui percobaan lain. Bila peserta mencoba lagi setelah Failed/Expired, `order_id` baru dibuat dan status kembali Pending. `data.payment` menunjuk pembayaran sukses bila ada, atau percobaan terbaru jika belum ada yang sukses.

Pembayaran mengikuti harga yang ada: **kelas Rp88.000 + layanan Rp5.000 = total gateway Rp93.000**. Nominal dipisahkan agar CRM dapat membedakan harga kelas dan jumlah yang benar-benar ditagih.

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

Delivery bersifat **at least once**. Misalnya, CRM sudah menyimpan data tetapi responsnya timeout, event akan dikirim ulang. Vendor wajib deduplikasi dengan `event_id` dan upsert dengan `registration_id`. Urutan kedatangan tidak dijamin: setiap event membawa snapshot lengkap pendaftaran agar payment event dapat membuat record terlebih dahulu. Abaikan snapshot dengan sequence lebih kecil dari state yang sudah diterapkan.

### Persetujuan WhatsApp dan data lama

API memberikan `whatsapp_consent`, `whatsapp_consented_at`, dan `whatsapp_consent_version` sebagai status, waktu, dan versi teks persetujuan komunikasi WhatsApp. `true` berarti peserta memilih setuju; `false` berarti tidak memberikan persetujuan pada form baru; `null` berarti status historis belum diketahui. CRM hanya menjalankan komunikasi WhatsApp bila nilainya `true`. Persetujuan data historis tidak diasumsikan sebagai persetujuan WhatsApp.

Objek `source` menyediakan asal pendaftaran dan `owner_user_id` untuk atribusi peserta ke pemilik landing page terkait.

Pendaftar lama dapat diambil melalui GET. Event pendaftaran lama tidak dibuat ulang otomatis. ID transaksi dan waktu pembayaran per transaksi lama dapat kosong sampai tersedia dari verifikasi gateway; `data.paid_at` tetap menyediakan waktu pembayaran historis yang sudah tersimpan. Sinkronisasi penghapusan/revokasi consent bukan bagian dari kontrak v1 ini dan perlu dikoordinasikan oleh operator kedua pihak.
