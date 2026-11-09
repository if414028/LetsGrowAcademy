# Subscription: biaya transaksi dan PPN gateway

Harga paket adalah durasi × `SUBSCRIPTION_MONTHLY_PRICE`. PPN 11% hanya
dikenakan atas biaya gateway, bukan atas harga subscription.

## Konfigurasi

`config/subscription.php` menyimpan katalog metode dan tarif dari screenshot
dashboard merchant. Tarif aktual mengikuti perjanjian merchant dengan Midtrans:

| Metode | Biaya gateway sebelum PPN |
| --- | --- |
| BSI, CIMB, BNI, BRI, Mandiri, Permata, SeaBank, Danamon | Rp4.000 |
| GoPay / ShopeePay, kategori produk digital | 4% |
| DANA | 1,5% |
| OVO, kategori produk digital lokal | 2,73% |
| GoPay Dynamic QRIS (0,7%) | Ditanggung merchant; pengguna tidak dikenai biaya tambahan |

Persentase disimpan dalam basis points (`400` = 4%, `273` = 2,73%).
Ubah `fixed_fee` dan `fee_basis_points` di konfigurasi jika kontrak berubah.

Tampilkan hanya metode yang sudah aktif pada dashboard Midtrans:

```dotenv
SUBSCRIPTION_PAYMENT_METHODS=bni_va,cimb_va,permata_va,bri_va,gopay_qris,bsi_va,gopay,echannel
SUBSCRIPTION_GATEWAY_VAT_PERCENT=11
```

Daftar kosong menonaktifkan checkout. Nama metode yang tidak ada dalam katalog
diabaikan. Konfigurasi bawaan memuat delapan metode yang dikonfirmasi aktif oleh pemilik:
BNI, CIMB Niaga, PermataBank, BRI, BSI, Mandiri, GoPay Dynamic QRIS, dan GoPay.
SeaBank, Danamon, DANA, OVO, dan ShopeePay tersimpan dalam katalog, tetapi tidak
ditampilkan kecuali diaktifkan lewat konfigurasi. Setelah mengubah konfigurasi pada server yang memakai config cache,
jalankan `php artisan config:cache`.

## Perhitungan dan penyimpanan

Pengguna memilih metode sebelum checkout. Server menghitung seluruh kutipan
harga untuk tampilan dan menghitung ulang pilihan saat POST; nominal kiriman
browser tidak dipakai. Snap dibatasi melalui `enabled_payments` ke satu metode.

Gateway memotong biaya persentase dari total yang ditagihkan. Agar penerimaan
bersih tetap sebesar harga paket, perhitungan mencari total terkecil yang
memenuhi:

```text
biaya transaksi = biaya tetap + ceil(total × persentase)
PPN gateway     = ceil(biaya transaksi × 11%)
total          = harga paket + biaya transaksi + PPN gateway
```

Pembulatan ke atas dilakukan ke rupiah penuh. Nominal ini merupakan perhitungan
aplikasi berdasarkan tarif konfigurasi; pembulatan settlement aktual bisa berbeda.
Contoh paket 6 bulan Rp300.000: VA menjadi Rp304.440; ShopeePay digital menjadi
Rp313.940; QRIS tetap Rp300.000. PPN biaya QRIS juga ditanggung merchant.

`gopay_qris` adalah kode internal aplikasi untuk GoPay Dynamic QRIS. Metode ini
dikirim sebagai `enabled_payments: ["gopay"]` dengan `uiMode: qr`. Ini memakai
aktivasi GoPay Dynamic QRIS merchant, bukan kanal `other_qris`. Biaya gateway
merchant 0,7% dan PPN-nya disimpan sebagai estimasi pada snapshot dan terlihat
di rincian admin. Pelanggan tetap membayar harga paket.

Untuk GoPay dan ShopeePay, Snap memakai `uiMode: deeplink` agar biaya e-wallet
tidak terbawa ke fallback QRIS desktop. Pengguna diminta membuka halaman di
ponsel untuk melanjutkan ke aplikasi. Pilihan GoPay Dynamic QRIS memakai alur QRIS secara eksplisit.

`subscriptions.amount` adalah total tagihan; `pricing_details` menyimpan snapshot
harga, tarif, biaya, PPN, dan nama metode. `selected_payment_method` berbeda dari
`payment_type` aktual yang dilaporkan gateway. Halaman pembayaran tertunda dan
admin memakai snapshot, sehingga perubahan tarif tidak mengubah tagihan lama.
Transaksi sebelum migrasi memiliki snapshot null dan tetap memakai nominal lama.
Webhook dan sinkronisasi tetap memverifikasi total `amount` sebelum aktivasi.

Migrasi:

```bash
php artisan migrate --path=database/migrations/2026_11_09_000001_add_pricing_details_to_subscriptions_table.php
```

Referensi:

- [Biaya dan PPN Midtrans](https://docs.midtrans.com/docs/berapa-harga-midtrans-payment-service)
- [Menambahkan biaya sebagai item checkout](https://docs.midtrans.com/docs/can-i-charge-service-fees-to-my-customers-1)
- [Snap enabled_payments](https://docs.midtrans.com/reference/request-body-json-parameter)
- [Snap uiMode dan perilaku QRIS](https://docs.midtrans.com/docs/snap-advanced-feature)
- [BI: MDR QRIS ditanggung merchant](https://www.bi.go.id/id/publikasi/ruang-media/cerita-bi/Pages/mdr-qris.aspx)
