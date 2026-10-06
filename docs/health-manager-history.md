# Riwayat HM untuk perhitungan NS

Jalankan `php artisan migrate --force` setelah deploy. Migrasi menyimpan periode terbuka HM saat ini dari `hm_since` (fallback `join_date` atau `created_at`). Perubahan role lewat form user dan pembuatan/import user selanjutnya mencatat periode HM.

Perhitungan menggunakan bulan promosi sebagai awal inklusif dan bulan demosi sebagai akhir eksklusif. Contoh: HM sejak Maret dan demosi September tidak berkontribusi ke atasan untuk Maret–Agustus; mulai September kembali berkontribusi. Cabang HM lain yang masih menjabat tetap dikecualikan. Promosi ulang membuat periode baru. Active HP di recap mengikuti pengecualian periode yang sama.

## Demosi sebelum fitur ini

Tanggal demosi lama tidak tersedia dan tidak boleh ditebak dari `updated_at`. Setelah memastikan identitas dan tanggal, jalankan sekali:

```bash
php artisan users:record-hm-period IDENTITAS_USER --ended-on=2026-09-01
```

Ganti `IDENTITAS_USER` dengan ID, email, atau kode DST yang persis sesuai. Untuk Monalisa, gunakan tanggal demosi yang diketahui pada September 2026. Perhitungan berbasis bulan sehingga semua tanggal pada September memiliki batas NS yang sama. Tanggal mulai diambil dari `hm_since` yang tersimpan; bila kosong atau keliru, tambahkan `--started-on=YYYY-MM-DD` dengan tanggal mulai HM yang telah dikonfirmasi.

Perintah menolak tanggal tidak valid, tanggal mendatang, identitas ambigu, dan periode tumpang tindih. Periode identik dapat dikirim ulang tanpa duplikasi. Perintah tidak mengubah role, hierarki, atau order. Refresh recap setelah berhasil; tidak ada agregat tersimpan yang perlu dibangun ulang.

Angka aktual Myra pada Agustus perlu dicocokkan di database VPS dengan order selesai dan unit instalasi setelah periode Monalisa diisi. Data uji bukan konfirmasi bahwa angka produksi adalah 52 atau 53.

## Batas cakupan

Pohon downline masih memakai hierarki saat ini. Fitur ini menyimpan riwayat jabatan HM, bukan riwayat perpindahan parent/downline. Perubahan hierarki historis dan perubahan role langsung via SQL atau di luar alur UserController memerlukan penanganan tersendiri. Kolom `hm_since` tetap menjadi awal periode HM saat ini untuk siklus recap enam bulan.

## Status HP dan Net Sales

SO selesai milik Health Planner tetap dihitung dalam Net Sales setelah akun menjadi `Inactive`, sesuai periode dan cakupan tim yang berlaku. Aturan ini berlaku untuk Overview, Performance dan ekspornya, leaderboard HP (personal maupun team, termasuk secondary account), Road to HM, recap HM, dan Net Sales di pohon downline. Pohon tetap menampilkan HP inactive beserta cabangnya agar penjualan mereka tidak tersembunyi.

Filter `Active` hanya dipakai untuk metrik jumlah HP aktif, bukan untuk menentukan SO yang menyumbang NS. Daftar HM yang ditampilkan tetap mengikuti filter HM aktif yang sudah ada; perubahan ini tidak mengubah aturan atribusi antar-HM atau periode promosi/demosi.

## Rekonsiliasi Overview dan tabel HM

Jika Total Net Sales pada card berbeda dari jumlah Total Units tabel HM, jalankan di database lingkungan yang menampilkan selisih tersebut:

```bash
php artisan sales:reconcile-dashboard
```

Perintah ini hanya membaca data dan memakai closing date aktif. Default viewer adalah akun Head Admin/Admin pertama. Untuk mencocokkan akun tertentu, gunakan `--user=ID_USER`; untuk Sales Manager, opsi ini juga mempertahankan pembatasan tim dan daftar HM direct child seperti dashboard.

Output menampilkan total card, total tabel, nomor SO yang tidak masuk tabel, nama/status sales, jalur upline dan riwayat HM. Diagnosis membedakan sales di luar cakupan HM yang ditampilkan dari SO dalam tim HM yang dikecualikan oleh periode jabatan/tanggal instalasi. Atribusi ganda ditampilkan terpisah, karena selisih total saja tidak cukup untuk menentukan jumlah SO yang hilang. Cocokkan output produksi sebelum mengubah hierarki atau riwayat jabatan; data lokal dan fixture pengujian tidak membuktikan asal selisih produksi.
