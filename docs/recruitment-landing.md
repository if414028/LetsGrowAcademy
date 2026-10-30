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
- Konten dan pilihan form berada di `config/recruitment.php`. Profil pendamping memakai nama, inisial, dan role member.
- Seluruh 15 section dipertahankan sesuai susunan awal: hero, tantangan, kebutuhan air minum, solusi kebutuhan, bukti penghasilan, materi sesi, cara kerja, belajar dari dasar, cerita mitra, pembimbing, dukungan, undangan event, FAQ, CTA akhir, dan form. Seluruh wording marketing mengikuti draft lengkap yang diberikan pengguna, tanpa diringkas atau menghapus section.
- Konten mengikuti draft terbaru dengan penjelasan air minum dan pemurnian air secara umum. Promosi merek produsen dihapus. Bagian bukti penghasilan menampilkan lima screenshot transfer dengan nama pengirim disamarkan melalui blur. Tanggal, nominal, kode transaksi, dan detail lain tetap terbaca. Thumbnail serta tautan perbesar sama-sama memakai aset `commission-*-redacted.webp`. PNG tanpa blur tidak lagi berada di direktori publik; salinan asli lokal disimpan di `storage/app/private/recruitment-originals/` dan tidak masuk Git.
- Seluruh 15 section memiliki gambar: 14 ilustrasi realistis yang berbeda dan screenshot dengan nama pengirim disamarkan di section bukti penghasilan. Aset ilustrasi tersedia sebagai WebP 1536px dan 768px dengan `srcset` serta lazy loading setelah hero. Tidak ada caption yang terlihat; alt deskriptif tetap tersedia. Orang dalam ilustrasi bukan foto pembimbing atau subjek testimoni. Detail aset dan prompt tersedia di `docs/recruitment-visual-assets.md`.
- Biaya sesi pengenalan Rp88.000 ditampilkan pada undangan, CTA, dan pengantar form. Form hanya mencatat minat, tidak memproses pembayaran. Nama dan peran pembimbing tetap mengikuti pemilik link.
- Wording, angka, klaim, dan cerita pada konten marketing disalin dari draft pengguna sesuai permintaan; bukan hasil verifikasi independen. Disclaimer pada CTA, cerita, dan footer mengikuti draft. Nama/role pembimbing tetap dinamis sesuai pemilik link, sedangkan penjelasan database pada informasi penggunaan data dipertahankan. Gambar undangan menampilkan ilustrasi sesi kelas yang sedang berlangsung.
- Animasi masuk dan pergerakan foto menggunakan GSAP serta menghormati perubahan pengaturan `prefers-reduced-motion`. Konten tetap tampil bila JavaScript tidak tersedia.
- Jadwal, format, dan ketentuan sesi disampaikan admin setelah pendaftaran.
- Jalankan migration `2026_10_21_000001_create_recruitment_leads_table.php` sebelum menggunakan fitur. Tidak membutuhkan layanan eksternal baru.

Validasi: `php artisan test --filter='RecruitmentLeadsTest|SubscriberRecruitmentPageTest|SubscriberLandingPageTest'` dan `npm run build`.
