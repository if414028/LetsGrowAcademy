# Grow Ads

Grow Ads berada di menu Subscription untuk Health Planner dan Health Manager dengan subscription aktif. Admin dan Head Admin mengelolanya melalui **Kelola Grow Ads**.

## Alur pengguna

1. Halaman pengenalan dan ilustrasi tampil sebelum aktivasi. Aktivasi gratis dan tersimpan per akun.
2. Pilih 1–3 konten aktif dari koleksi Admin. Konten yang disimpan di draf dikunci untuk pengguna lain selama 24 jam sejak pilihan terakhir disimpan. Konten tetap terkunci saat menunggu pembayaran, saat disiapkan, dan selama periode tayang. Judul, deskripsi, dan media disimpan sebagai snapshot kampanye.
3. Atur budget harian dan jumlah hari. Kalkulasi: `budget harian × hari + pembulatan ke atas PPN 11% + biaya layanan aplikasi Rp5.000`. Budget merupakan total untuk seluruh konten dalam kampanye.
4. Checkout membuat order Midtrans Snap. Halaman pembayaran bisa dibuka kembali dan status dapat diperiksa. Browser callback tidak pernah menandai pembayaran berhasil tanpa verifikasi server.
5. Pembayaran terverifikasi mengubah status menjadi **Sedang disiapkan**. Durasi belum dimulai.
6. Admin melengkapi Pixel ID dan Meta Ad ID, kemudian menjalankan kampanye. Waktu mulai dan akhir ditetapkan sekali; penyimpanan berikutnya tidak mengulang durasi.
7. Dashboard menampilkan tayangan, klik, pendaftar, pembayaran event, performa setiap konten, landing page, dan rincian pembayaran.

## Pembayaran

Contoh: Rp50.000 × 7 = Rp350.000, PPN Rp38.500, biaya layanan Rp5.000, total **Rp393.500**.

Pengaturan biaya dan batas input ada di `config/grow-ads.php`. Batas awal budget adalah Rp50.000–Rp10.000.000 per hari dengan durasi 1–90 hari. Sesuaikan batas tersebut jika aturan operasional berbeda. Biaya Rp5.000 adalah **biaya layanan aplikasi**, bukan pernyataan tarif resmi Midtrans. Tarif aktual Midtrans mengikuti perjanjian merchant.

Memakai konfigurasi Midtrans yang sudah ada (`MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, dan `MIDTRANS_IS_PRODUCTION`) serta webhook bersama `/payments/midtrans/notification`. Order Grow Ads memakai awalan `GADS-`. Signature webhook, order ID, dan nilai pembayaran diverifikasi. Notifikasi berulang tidak memulai ulang kampanye. Refund/chargeback menghentikan kampanye dan settlement lama tidak dapat membukanya kembali.

Checkout yang gagal tidak menghilangkan draf. Order pembayaran pending mengunci pilihan agar jumlah tagihan dan konten tetap konsisten.

## Operasional Admin

- Unggah gambar JPG/PNG/WebP atau video MP4/MOV hingga 30 MB, isi judul dan deskripsi, lalu aktifkan atau arsipkan. Koleksi menampilkan konten yang pertama kali diunggah paling baru di bagian atas.
- Arsip dan perubahan koleksi tidak mengganti snapshot konten yang dipilih dalam kampanye. Media lama dipertahankan untuk kampanye tersebut.
- Buka kampanye yang sudah dibayar. Di bagian **Konten & data Meta Ads**, isi Meta Ad ID dan Meta Pixel ID berupa angka untuk setiap iklan. Iklan bisa memakai pixel yang sama atau berbeda.
- Salin **tautan tujuan iklan untuk konten ini** ke CTA Meta Ads. Tautan tersebut menuju landing page rekrutmen yang sudah ada dan ditandatangani server.
- Setelah penyiapan di Meta selesai, pilih **Sedang berjalan**. Selama periode kampanye, tautan iklan yang valid memuat pixel yang diisi untuk iklan tersebut. Migrasi menyalin Pixel ID kampanye lama ke setiap iklan terkait.
- Perbarui angka kumulatif tayangan dan klik dari Meta Ads. Keduanya boleh kosong; nilai yang belum tersedia ditampilkan `—`. Waktu pembaruan ditampilkan kepada pemilik.
- Form minat mencatat calon peserta dan membuka pembayaran event Rp93.000 (kelas Rp88.000 dan biaya layanan aplikasi Rp5.000). Pendaftaran online terkonfirmasi setelah status pembayaran diverifikasi. Admin masih bisa memperbarui status pendaftaran lama yang dicatat sebelum checkout online.

## Atribusi landing page

Pemilik selalu berasal dari slug URL, tidak dari ID yang dikirim pengunjung. Signature mengikat pemilik/kampanye/konten; parameter `fbclid` dan UTM boleh ditambahkan Meta tanpa membatalkan signature. Form membawa token terenkripsi yang berlaku 7 hari dan tetap terikat ke pemilik serta konten yang sama. Parameter atau token yang dipalsukan tidak mengubah kepemilikan pendaftar. Peserta yang sudah bayar lewat tautan langsung tampil di daftar peserta Grow Ads milik pemilik dengan sumber **Landing page langsung**. Angka performa kampanye dan per konten memakai pendaftaran yang teratribusi ke iklan.

Pixel milik iklan mengirim `PageView` dari tautan iklan, `AddPaymentInfo` saat tombol menuju form diklik, dan `AddToCart` saat form lolos validasi browser dan dikirim. Setelah pembayaran terverifikasi, halaman terima kasih mengirim `Purchase` dengan jumlah aktual dalam IDR bersama `CompleteRegistration`. Event konfirmasi dimuat sekali per pendaftaran. Kunjungan organik tidak memuat pixel Grow Ads.

Bagian **Aktivitas landing page** menghitung klik tombol yang tercatat, form yang diterima server, dan pembayaran event yang terverifikasi untuk kampanye terkait. Angka ini berasal dari Grow Ads dan bisa berbeda dari laporan Meta.

## Batas integrasi saat ini

Pembuatan dan penayangan iklan di Meta Ads dilakukan Admin. Tayangan dan klik diinput Admin. Pembayaran event memakai Snap Redirect dan webhook Midtrans yang sama dengan pembayaran kampanye, dengan awalan order `REVT-`. Landing page memakai template rekrutmen yang sudah ada, dengan nama dan kontak pemilik serta pixel dan atribusi per iklan.

## Instalasi & verifikasi

Jalankan `php artisan migrate`, pastikan `php artisan storage:link`, lalu `npm run build`. Webhook Midtrans harus dapat diakses melalui HTTPS pada lingkungan deploy.

`php artisan test --filter=GrowAdsTest` menguji aktivasi, otorisasi, pemilihan konten, harga, checkout, webhook, refund, penyiapan Admin, media, pixel, dan atribusi pendaftaran. Seluruh komunikasi Midtrans dalam tes memakai mock.

## Keputusan desain

Memakai latar putih yang konsisten dengan menu lain, termasuk saat sistem memakai mode gelap. Warna navy/cyan digunakan untuk teks, tombol, dan aksen; body memakai Geist lokal, display Cabinet Grotesk dengan fallback Geist. Onboarding memakai ilustrasi vektor alur konten → landing page → pendaftaran; tidak memakai klaim performa atau testimoni contoh. Form dan dashboard mempertahankan navigasi aplikasi dengan target 44–48px, status berupa teks, fokus keyboard, skema warna terang, dan reduced motion.

Ilustrasi bersifat netral dan menampilkan iklan Meta Ads, landing page, serta pendaftaran. Ilustrasi utama hanya memiliki animasi masuk berbasis transform, tanpa perubahan opacity saat scroll. Cara kerja disajikan dalam tiga langkah bergambar yang selalu terbaca: pilih konten, atur budget harian/durasi, dan penyiapan oleh Admin. Halaman pengenalan membahas manfaat budget bagi peluang jangkauan dan engagement; rincian PPN dan biaya layanan berada pada tahap budget/pembayaran.

Prinsip HIG yang dipakai untuk web: `layout.md › Best practices`, “Make essential information easy to find by giving it sufficient space”; `writing.md › Best practices`, “Be action oriented”; `accessibility.md › Vision`, “Convey information with more than color alone.” Konvensi navigasi native macOS/iOS tidak diterapkan ke aplikasi web ini.
