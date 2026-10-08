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

Pohon referrer tetap memakai hierarki saat ini. Penugasan tim HP sekarang disimpan terpisah melalui `users.health_manager_id` (lihat bagian berikut). Riwayat jabatan HM tetap berlaku untuk penjualan HM sendiri dan atribusi lama tanpa penugasan eksplisit; fitur ini tidak menyimpan snapshot HM di setiap SO. Perubahan role langsung via SQL atau di luar alur UserController memerlukan penanganan tersendiri. Kolom `hm_since` tetap menjadi awal periode HM saat ini untuk siklus recap enam bulan.

## Status HP dan Net Sales

SO selesai milik Health Planner tetap dihitung dalam Net Sales setelah akun menjadi `Inactive`, sesuai periode dan cakupan tim yang berlaku. Aturan ini berlaku untuk Overview, Performance dan ekspornya, leaderboard HP (personal maupun team, termasuk secondary account), Road to HM, recap HM, dan Net Sales di pohon downline. Pohon tetap menampilkan HP inactive beserta cabangnya agar penjualan mereka tidak tersembunyi.

Filter `Active` hanya dipakai untuk metrik jumlah HP aktif, bukan untuk menentukan SO yang menyumbang NS. Daftar HM yang ditampilkan tetap mengikuti filter HM aktif yang sudah ada.

## Penugasan HM dan perpindahan tim HP

Deploy kode, lalu jalankan `php artisan migrate --force`. Migrasi menambahkan foreign key nullable `users.health_manager_id` dan mengisi HM terdekat dari referrer untuk HP yang masih memiliki HM, termasuk HP Inactive. Migrasi tidak menebak siapa mantan HM untuk HP yang sekarang berada di bawah SM tanpa HM; gunakan alur pengalihan berikut untuk kasus tersebut.

Saat membuat/import HP, HM disimpan dari HM referrer atau HM yang ditunjuk pada form. Admin/Head Admin dapat mengubah HM pada halaman edit HP, atau mengalihkan seluruh HP dari profil HM/SM melalui **Alihkan tim HP ke HM lain**. Referrer dan struktur Partner Tree tetap. HM tujuan harus berstatus Active dan memiliki role Health Manager. Cabang HP milik HM lain serta HP yang sudah dialihkan dikecualikan.

**Seluruh NS HP, termasuk SO lama dan akun Inactive, mengikuti HM yang ditunjuk sekarang.** Dashboard, Performance dan ekspor, recap, report, pilihan HP pada SO, serta akses tim HM/SM menggunakan penugasan tersebut. Riwayat periode HM pada referrer lama tidak mengeluarkan NS HP yang sudah ditugaskan ke HM tujuan. Promosi HP menjadi HM memindahkan HP dalam cabangnya yang sebelumnya ditangani HM yang sama ke HM baru. Promosi HM menjadi SM mewajibkan HM pengganti jika masih ada HP yang harus dialihkan; role dan pengalihan disimpan dalam satu transaksi.

Untuk HM yang sudah terlanjur menjadi SM (misalnya Gabrielle → Daniel), command berikut menampilkan daftar terlebih dahulu, termasuk HP tanpa HM tersimpan dalam cabang sumber yang tidak memiliki HM lain:

```bash
php artisan users:transfer-health-planners DST230500300 DST230600279 --dry-run
```

Jalankan pengalihan pada database lingkungan yang ingin diperbaiki:

```bash
php artisan users:transfer-health-planners DST230500300 DST230600279
php artisan sales:reconcile-dashboard
```

Argumen sumber dan tujuan menerima ID, email, atau kode DST. Format `DST230600279` dan `DST-230600279` dikenali; identitas ambigu ditolak. Command hanya memperbarui HM HP beserta timestamp user, tanpa mengubah role/status, referrer, SO, atau owner customer. Aman dijalankan ulang: HP yang sudah dipindahkan tidak dialihkan lagi. Tidak ada cache agregat NS yang perlu dibangun ulang; refresh halaman setelah berhasil.

## Rekonsiliasi Overview dan tabel HM

Jika Total Net Sales pada card berbeda dari jumlah Total Units tabel HM, jalankan di database lingkungan yang menampilkan selisih tersebut:

```bash
php artisan sales:reconcile-dashboard
```

Perintah ini hanya membaca data dan memakai closing date aktif. Default viewer adalah akun Head Admin/Admin pertama. Untuk mencocokkan akun tertentu, gunakan `--user=ID_USER`; untuk Sales Manager, opsi ini juga mempertahankan pembatasan tim dan daftar HM direct child seperti dashboard.

Output menampilkan total card, total tabel, nomor SO yang tidak masuk tabel, nama/status sales, jalur upline dan riwayat HM. Diagnosis membedakan sales di luar cakupan HM yang ditampilkan dari SO dalam tim HM yang dikecualikan oleh periode jabatan/tanggal instalasi. Atribusi ganda ditampilkan terpisah, karena selisih total saja tidak cukup untuk menentukan jumlah SO yang hilang. Cocokkan output produksi sebelum mengubah hierarki atau riwayat jabatan; data lokal dan fixture pengujian tidak membuktikan asal selisih produksi.
