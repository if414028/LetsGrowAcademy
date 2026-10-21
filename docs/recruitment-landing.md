# Landing Page Rekrutmen Subscriber

- Menu dashboard: `/landing-page-rekrutmen`, di bawah Subscription, bersebelahan dengan Landing Page Produk.
- Link publik: `/rekrutmen/{landing_page_slug}`. Memakai domain aplikasi dan slug nama/kode member yang sudah ada.
- Menu, preview, dan salin link tersedia bagi subscriber aktif. Nomor WhatsApp di profil harus dilengkapi agar link publik aktif.
- Link publik dapat dibuka calon mitra tanpa login. Pemilik halaman harus memiliki subscription aktif yang belum berakhir, akun tidak nonaktif, dan nomor WhatsApp.
- Profil pendamping dan kontak WhatsApp mengikuti pemilik slug, termasuk ketika pengunjung login sebagai member lain.
- CTA utama menuju form minat. Form memvalidasi nama, nomor WhatsApp Indonesia, domisili, aktivitas, topik opsional, dan persetujuan data.
- Setiap submit valid menyimpan pendaftaran di tabel `recruitment_leads`, lalu mengarahkan pengunjung dengan respons 303 ke `/rekrutmen/{landing_page_slug}/terima-kasih`. Halaman ini hanya menampilkan ucapan terima kasih dan pemberitahuan bahwa admin akan menghubungi untuk mengikuti event. Memuat ulang halaman terima kasih tidak mengirim ulang form.
- Form, persetujuan, informasi penggunaan data, dan FAQ menjelaskan penyimpanan data di database Let's Grow Academy untuk pendaftaran serta tindak lanjut event. Form tidak lagi mengarahkan pengunjung ke WhatsApp. Kontak WhatsApp pendamping tetap tersedia sebagai tautan terpisah.
- Menu **Daftar Peminat** berada di halaman pengelolaan rekrutmen, dengan URL `/landing-page-rekrutmen/pendaftar`. Pemilik dapat melihat nama, WhatsApp, domisili, aktivitas, topik minat, asal form, persetujuan, serta waktu daftar (WIB), dan mencari berdasarkan nama, nomor, atau domisili.
- Kepemilikan menggunakan `owner_user_id` yang ditentukan dari slug URL di server. Asal pendaftaran ditandai `source = recruitment_landing`, `source_slug`, dan `source_url` saat pendaftaran. Perubahan nama/slug tidak memindahkan data ke user lain. Input pemilik/sumber dari pengunjung diabaikan.
- Daftar dan pencarian selalu terbatas pada user yang login, termasuk untuk HM yang memiliki downline HP. Data ini terpisah dari tabel customer dan tidak digabung dengan customer dari sumber lain.
- Catatan pendaftaran disimpan mulai fitur penyimpanan ini digunakan. Form yang pernah dikirim sebelum fitur ini belum memiliki riwayat di database.
- Konten dan pilihan form berada di `config/recruitment.php`. Profil pendamping memakai nama, foto, dan role member; tidak menggunakan nama mentor statis dari draft.
- Section bukti penghasilan menampilkan lima gambar mutasi yang disediakan, dalam ukuran asli dan tanpa pemotongan. Nominal serta tanggal tersedia sebagai teks; tautan gambar membuka berkas asli pada tab baru. Aset berada di `public/images/recruitment/`, daftar gambar dan highlight di `config/recruitment.php`.
- Jadwal dan ketentuan sesi dikonfirmasi melalui pendamping. Narasi penghasilan memakai penjelasan skema komisi, tanpa angka hasil atau profit yang belum diverifikasi.
- Jalankan migration `2026_10_21_000001_create_recruitment_leads_table.php` sebelum menggunakan fitur. Tidak membutuhkan layanan eksternal baru.

Validasi: `php artisan test --filter='RecruitmentLeadsTest|SubscriberRecruitmentPageTest|SubscriberLandingPageTest'` dan `npm run build`.
