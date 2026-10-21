<!DOCTYPE html>
<html lang="id">
@php($oldText = fn (string $key) => is_string(old($key)) ? old($key) : '')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kenali peluang kemitraan solusi air minum, cara kerja, dan dukungannya bersama {{ $consultantName }}. Mulai dengan sesi pengenalan LetsGrowAcademy.">
    <meta property="og:title" content="Peluang Kemitraan Air Minum | {{ $consultantName }}">
    <meta property="og:description" content="Dari kebutuhan sehari-hari, kenali peluang bisnis baru. Pelajari produk, sistem komisi, dan pendampingannya.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}">
    <meta property="og:image" content="{{ asset('images/letsgrow-academy-hero.png') }}">
    <link rel="canonical" href="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Peluang Kemitraan Air Minum | {{ $consultantName }}</title>
    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="recruitment-page">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <header class="recruitment-header">
        <nav class="recruitment-container recruitment-nav" aria-label="Navigasi rekrutmen">
            <a href="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}" class="recruitment-brand" aria-label="LetsGrowAcademy — halaman rekrutmen {{ $consultantName }}">
                <img src="{{ asset('images/coway_logo.png') }}" alt="" width="42" height="42" aria-hidden="true">
                <span>Let's Grow<span>Academy</span></span>
            </a>
            <div class="recruitment-nav-links"><a href="#peluang">Peluangnya</a><a href="#materi">Materi</a><a href="#cerita">Cerita mitra</a><a href="#faq">FAQ</a></div>
            <a href="#form-minat" class="recruitment-button recruitment-button-small">Kenali Peluang <x-recruitment-icon /></a>
        </nav>
    </header>

    <main id="main-content">
        <section class="recruitment-hero">
            <div class="recruitment-container recruitment-hero-grid">
                <div class="recruitment-hero-copy">
                    <p class="recruitment-eyebrow"><span class="recruitment-status-dot"></span>Peluang bisnis kebutuhan pokok air minum</p>
                    <h1>Dari kebutuhan air minum sehari-hari, <span>bangun sumber penghasilan baru.</span></h1>
                    <p class="recruitment-lead">Air minum adalah kebutuhan pokok setiap keluarga. Kenali bagaimana kemitraan solusi pemurni air bersama principal global membuka peluang bisnis, dengan sistem komisi dan jenjang karier yang bisa Anda pelajari.</p>
                    <div class="recruitment-actions"><a href="#form-minat" class="recruitment-button recruitment-button-light">Pelajari Peluangnya Sekarang <x-recruitment-icon /></a><a href="#cara-kerja" class="recruitment-button recruitment-button-outline">Pelajari Cara Kerjanya</a></div>
                    <p class="recruitment-microcopy">Kenali produk, sistem bisnis, dan dukungannya sebelum menentukan langkah berikutnya.</p>
                </div>
                <div class="recruitment-chapter-wrap">
                    <div class="recruitment-orbit" aria-hidden="true"></div>
                    <div class="recruitment-chapter">
                        <p class="recruitment-eyebrow">Your next chapter</p>
                        <h2>Lebih banyak pilihan.<br> Lebih banyak ruang untuk tumbuh.</h2>
                        <p>Mulai dengan mengenali peluang, memahami proses, dan membangun langkah yang sesuai dengan tujuan Anda.</p>
                        <div class="recruitment-chapter-line" aria-hidden="true"><span></span></div>
                        <div class="recruitment-chapter-caption"><span>Pelajari hari ini</span><span>Bangun langkah berikutnya</span></div>
                    </div>
                    <span class="recruitment-floating-label recruitment-floating-label-top"><span aria-hidden="true">↗</span> Growth mindset</span>
                    <span class="recruitment-floating-label recruitment-floating-label-bottom"><x-recruitment-icon name="target" /> Skill baru, peluang baru</span>
                </div>
            </div>
            <div class="recruitment-container recruitment-hero-footer"><span><x-recruitment-icon name="drop" /> Produk kebutuhan harian</span><span><x-recruitment-icon name="book" /> Belajar dari dasar</span><span><x-recruitment-icon name="people" /> Dukungan tim</span></div>
        </section>

        <section class="recruitment-section">
            <div class="recruitment-container">
                <div class="recruitment-section-heading"><p class="recruitment-eyebrow">Pernah merasa seperti ini?</p><h2>Sudah kerja keras 9-to-5,<br> tapi penghasilan masih mepet?</h2><p>Penghasilan rutin belum tentu membuat kita tenang ketika biaya kebutuhan rumah tangga terus bertambah. Mungkin Anda juga sedang mencari pilihan berikutnya.</p></div>
                <div class="recruitment-grid recruitment-grid-four">
                    @foreach ($content['problems'] as $item)
                        <article class="recruitment-card recruitment-problem"><span class="recruitment-number">0{{ $loop->iteration }}</span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="peluang" class="recruitment-section recruitment-surface">
            <div class="recruitment-container recruitment-split">
                <aside class="recruitment-insight">
                    <x-recruitment-icon name="drop" />
                    <p class="recruitment-eyebrow">Dekat dengan keseharian</p>
                    <h2>Setiap rumah.<br> Setiap hari.<br> <span>Selalu butuh air minum.</span></h2>
                    <p>Kebutuhannya berulang. Peluangnya dimulai dengan memahami cara memberi solusi yang lebih praktis.</p>
                    <div class="recruitment-insight-caption"><span>01 / Kebutuhan pokok</span><span>02 / Penggunaan rutin</span></div>
                </aside>
                <div class="recruitment-section-copy"><p class="recruitment-eyebrow">Peluang di sekitar kita</p><h2>Kebutuhan sehari-hari.<br> Potensi bisnis yang bisa Anda pelajari.</h2><p>Air minum bukan pengeluaran sekali beli. Keluarga membutuhkan solusi air minum setiap hari, dari minum hingga memasak. Di sinilah peluang untuk mengenalkan solusi yang sesuai dengan kebutuhan mereka.</p>
                    <ul class="recruitment-check-list"><li><span class="recruitment-check"><x-recruitment-icon name="check" /></span><div><h3>Semua keluarga membutuhkan</h3><p>Mulai dari lingkungan terdekat Anda. Pelajari siapa calon pelanggan dan kebutuhan air minum mereka.</p></div></li><li><span class="recruitment-check"><x-recruitment-icon name="check" /></span><div><h3>Digunakan rutin setiap hari</h3><p>Produk yang dekat dengan keseharian memberi kesempatan untuk membangun hubungan pelanggan dalam jangka panjang.</p></div></li></ul>
                    <p class="recruitment-callout">Bagaimana jika Anda ikut menjawab kebutuhan ini, sekaligus membangun sumber penghasilan baru?</p>
                </div>
            </div>
        </section>

        <section class="recruitment-section">
            <div class="recruitment-container recruitment-split recruitment-product-grid">
                <div class="recruitment-product-visual"><img class="recruitment-product-logo" src="{{ asset('images/coway-logo.png') }}" alt="Coway" width="110" height="34" loading="lazy"><p>Solusi air minum.<br> Bagian dari keseharian.</p><div class="recruitment-product-units"><img src="{{ asset('images/water-neo-plus-chp-264l.webp') }}" alt="Pemurni air Coway Neo Plus" width="280" height="360" loading="lazy"><img src="{{ asset('images/water-ombak-chp-7310r.webp') }}" alt="Pemurni air Coway Ombak" width="280" height="360" loading="lazy"></div><span>Pemurnian air modern · Perawatan berkala</span></div>
                <div class="recruitment-section-copy"><p class="recruitment-eyebrow">Kenali pasar & produknya</p><h2>Bisnis dimulai dari solusi yang dibutuhkan.</h2><p>Kenali produk water purifier dan alasan keluarga mempertimbangkan cara baru untuk memenuhi kebutuhan air minumnya.</p><ul class="recruitment-check-list">
                        @foreach ($content['product_points'] as $item)
                            <li><span class="recruitment-check"><x-recruitment-icon name="check" /></span><div><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></div></li>
                        @endforeach
                    </ul><a href="#form-minat" class="recruitment-text-link">Saya mau mengenal model bisnis ini <x-recruitment-icon /></a>
                </div>
            </div>
        </section>

        <section id="bukti-penghasilan" class="recruitment-section recruitment-proof-section" aria-labelledby="recruitment-proof-title">
            <div class="recruitment-container">
                <div class="recruitment-section-heading">
                    <p class="recruitment-eyebrow">Bukti & hasil kemitraan</p>
                    <h2 id="recruitment-proof-title"><span aria-hidden="true">💸</span> Bukti Penghasilan Nyata,<br> Bukan Janji Manis.</h2>
                    <p>Dokumentasi pencairan komisi dari Coway. Lihat nominal dan mutasinya, lalu pelajari bagaimana sistem kemitraannya bekerja.</p>
                </div>

                <div class="recruitment-proof-list">
                    @foreach ($content['proof']['receipts'] as $receipt)
                        <figure class="recruitment-proof-item">
                            <figcaption>
                                <div><span>Mutasi rekening</span><strong>{{ $receipt['date'] }}</strong></div>
                                <span class="recruitment-proof-amount">{{ $receipt['amount'] }}</span>
                            </figcaption>
                            <a class="recruitment-proof-image-link" href="{{ asset('images/recruitment/'.$receipt['image']) }}" target="_blank" rel="noopener" aria-label="Lihat gambar asli mutasi {{ $receipt['date'] }}, {{ $receipt['amount'] }} (tab baru)">
                                <img src="{{ asset('images/recruitment/'.$receipt['image']) }}" alt="Mutasi LLG-MANDIRI dari COWAY INTERNATIONA tanggal {{ $receipt['date'] }}, sejumlah {{ $receipt['amount'] }}" width="{{ $receipt['width'] }}" height="{{ $receipt['height'] }}" loading="lazy" decoding="async">
                                <span>Lihat gambar asli <span aria-hidden="true">↗</span></span>
                            </a>
                        </figure>
                    @endforeach
                </div>

                <div class="recruitment-proof-highlights" aria-label="Highlight kemitraan">
                    @foreach ($content['proof']['highlights'] as $highlight)
                        <div><span>{{ $highlight['label'] }}</span><strong>{{ $highlight['value'] }}</strong></div>
                    @endforeach
                </div>
                <p class="recruitment-note">Dokumentasi ini menggambarkan hasil individu. Penghasilan setiap mitra dapat berbeda, bergantung pada aktivitas dan kinerja masing-masing.</p>
                <div class="recruitment-centered-action"><a href="#form-minat" class="recruitment-button">Pelajari Cara Mendapatkan Komisinya <x-recruitment-icon /></a></div>
            </div>
        </section>

        <section id="materi" class="recruitment-section recruitment-surface">
            <div class="recruitment-container"><div class="recruitment-section-heading"><p class="recruitment-eyebrow">Materi sesi pengenalan</p><h2>5 materi untuk melihat<br> peluangnya lebih utuh.</h2><p>Dari memahami pasar dan model bisnis, sampai mengenal mitra, skema komisi, dan langkah untuk memulai.</p></div>
                <div class="recruitment-curriculum">
                    @foreach ($content['materials'] as $item)
                        <article><span class="recruitment-curriculum-number">0{{ $loop->iteration }}</span><div><h3>{{ $item['title'] }}</h3><span>{{ $item['subtitle'] }}</span></div><p>{{ $item['body'] }}</p><x-recruitment-icon /></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="cara-kerja" class="recruitment-section recruitment-dark">
            <div class="recruitment-container"><div class="recruitment-section-heading"><p class="recruitment-eyebrow">Cara kerja peluang ini</p><h2>Mulai dengan mengenal.<br> Lanjut dengan memahami.</h2><p>Anda tidak perlu langsung terjun sendirian. Pelajari alur bisnisnya, ajukan pertanyaan, lalu tentukan langkah yang sesuai.</p></div>
                <ol class="recruitment-grid recruitment-grid-four recruitment-steps">
                    @foreach ($content['steps'] as $item)
                        <li class="recruitment-card"><span class="recruitment-eyebrow">Langkah 0{{ $loop->iteration }}</span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></li>
                    @endforeach
                </ol><div class="recruitment-centered-action"><a href="#form-minat" class="recruitment-button recruitment-button-light">Saya Mau Memahami Sistemnya <x-recruitment-icon /></a></div>
            </div>
        </section>

        <section class="recruitment-section">
            <div class="recruitment-container recruitment-split recruitment-objections"><div class="recruitment-section-copy"><p class="recruitment-eyebrow">Baru mau mulai?</p><h2>Belum berpengalaman?<br> Mulai dari dasarnya.</h2><p>Anda tidak harus menguasai semuanya di awal. Komitmen untuk belajar menjadi langkah pertama untuk mengenal proses dan membangun kemampuan baru.</p><a href="#form-minat" class="recruitment-text-link">Kenali peluangnya bersama kami <x-recruitment-icon /></a></div><div class="recruitment-objection-list">
                    @foreach ($content['objections'] as $item)
                        <article><span class="recruitment-check"><x-recruitment-icon name="check" /></span><div><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></div></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="cerita" class="recruitment-section recruitment-surface">
            <div class="recruitment-container"><div class="recruitment-section-heading"><p class="recruitment-eyebrow">Cerita perjalanan mitra</p><h2>Latar belakang berbeda.<br> Ruang untuk memulai yang sama.</h2><p>Perjalanan karier tidak selalu lurus. Dari memulai kembali hingga mencari kesempatan pertama, ada pelajaran dari setiap proses.</p></div><div class="recruitment-grid recruitment-grid-four recruitment-stories">
                    @foreach ($content['stories'] as $item)
                        <article class="recruitment-card"><span class="recruitment-story-tag">{{ $item['tag'] }}</span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p><div class="recruitment-story-bottom"><span aria-hidden="true">↗</span> Belajar, berproses, bertumbuh</div></article>
                    @endforeach
                </div><p class="recruitment-note">Ringkasan cerita dalam materi pengenalan. Hasil setiap individu dapat berbeda, bergantung pada aktivitas dan kinerja masing-masing, serta bukan jaminan penghasilan.</p><div class="recruitment-centered-action"><a href="#form-minat" class="recruitment-text-link">Saya mau mengenal peluang ini lebih lanjut <x-recruitment-icon /></a></div>
            </div>
        </section>

        <section class="recruitment-section">
            <div class="recruitment-container recruitment-split recruitment-mentor-grid">
                <div class="recruitment-mentor-card">
                    <p class="recruitment-eyebrow">Mari mulai percakapan</p>
                    @if ($consultant->photo)
                        <img src="{{ asset('storage/'.$consultant->photo) }}" alt="{{ $consultantName }}, pendamping kemitraan" class="recruitment-mentor-photo" width="240" height="240" loading="lazy">
                    @else
                        <div class="recruitment-mentor-initials" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($consultantName, 0, 1)) }}</div>
                    @endif
                    <h3>{{ $consultantName }}</h3><p>{{ $consultantRole }}</p><span class="recruitment-mentor-label"><x-recruitment-icon name="people" /> Pendamping Anda</span>
                </div>
                <div class="recruitment-section-copy"><p class="recruitment-eyebrow">Mengenal pembimbing</p><h2>Ada teman diskusi<br> untuk langkah pertama Anda.</h2><p>Bersama {{ $consultantName }}, kenali peta jalan kemitraan, aktivitas di lapangan, dan hal-hal yang perlu Anda pertimbangkan sebelum memulai.</p><p>Gunakan sesi pengenalan untuk bertanya tentang produk, skema komisi, komitmen waktu, dan dukungan yang tersedia. Bangun keputusan dengan informasi yang cukup.</p><a href="#form-minat" class="recruitment-button">Kenali Peluang Bersama Tim Kami <x-recruitment-icon /></a><a href="{{ $whatsappUrl }}" class="recruitment-text-link" target="_blank" rel="noopener">Tanya {{ $consultantName }} di WhatsApp <span aria-hidden="true">↗</span></a></div>
            </div>
        </section>

        <section class="recruitment-section recruitment-surface">
            <div class="recruitment-container"><div class="recruitment-section-heading"><p class="recruitment-eyebrow">Fasilitas & dukungan</p><h2>Anda tidak melangkah sendirian.</h2><p>Kenali ekosistem pendampingan yang membantu proses belajar dan aktivitas kemitraan Anda.</p></div><div class="recruitment-grid recruitment-grid-three">
                    @foreach ($content['support'] as $item)
                        <article class="recruitment-card recruitment-support"><span class="recruitment-check"><x-recruitment-icon :name="['book', 'target', 'people'][$loop->index]" /></span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="recruitment-section recruitment-invitation-section">
            <div class="recruitment-container"><div class="recruitment-invitation"><div><p class="recruitment-eyebrow">Undangan sesi pengenalan</p><h2>Kenali sistemnya sebelum mengambil keputusan.</h2><p>Pelajari peluang bisnis air minum, cara kerja kemitraan, dan hal-hal penting yang perlu Anda pertimbangkan.</p><a href="#form-minat" class="recruitment-button">Saya Tertarik Mengikuti Sesi <x-recruitment-icon /></a></div><ul><li><span class="recruitment-check"><x-recruitment-icon name="clock" /></span><div><h3>Jadwal & format sesi</h3><p>Konfirmasi melalui WhatsApp pendamping.</p></div></li><li><span class="recruitment-check"><x-recruitment-icon name="people" /></span><div><h3>Kontak pendamping</h3><p>{{ $consultantName }}</p></div></li><li><span class="recruitment-check"><x-recruitment-icon name="book" /></span><div><h3>Materi & ketentuan</h3><p>Tanyakan detail sesi dan biaya, jika ada, sebelum mendaftar.</p></div></li></ul></div></div>
        </section>

        <section id="faq" class="recruitment-section recruitment-surface">
            <div class="recruitment-container"><div class="recruitment-section-heading"><p class="recruitment-eyebrow">Pertanyaan yang sering diajukan</p><h2>Sebelum memulai,<br> cari tahu jawabannya.</h2><p>Masih ada yang ingin ditanyakan? Pendamping Anda siap membantu menjelaskan.</p></div><div class="recruitment-faq">
                    @foreach ($content['faqs'] as $item)
                        <details><summary>{{ $item['question'] }}<span class="recruitment-faq-plus" aria-hidden="true"></span></summary><p>{{ $item['answer'] }}</p></details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="recruitment-final-cta"><div class="recruitment-container"><p class="recruitment-eyebrow">Langkah kecil, pilihan baru</p><h2>Impian besar dimulai dari<br> langkah pertama yang terinformasi.</h2><p>Kenali peluangnya, ajukan pertanyaan, dan putuskan apakah langkah ini sesuai dengan tujuan Anda.</p><a href="#form-minat" class="recruitment-button">Pelajari Peluangnya Sekarang <x-recruitment-icon /></a></div></section>

        <section id="form-minat" class="recruitment-section recruitment-form-section">
            <div class="recruitment-form-card"><p class="recruitment-eyebrow">Form minat</p><h2>Ambil langkah pertama<br> untuk mengenal peluang ini.</h2><p>Isi informasi berikut untuk didaftarkan ke dalam event. Pastikan nomor WhatsApp Anda aktif agar admin dapat menghubungi Anda.</p>
                <div class="recruitment-form-recipient"><x-recruitment-icon name="people" /><span>Terhubung dengan <strong>{{ $consultantName }}</strong></span></div>
                @if ($errors->any())
                    <div class="recruitment-form-errors" role="alert" tabindex="-1"><strong>Periksa kembali data Anda.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form method="POST" action="{{ route('subscriber-recruitment.submit', $consultant->landing_page_slug) }}">
                    @csrf
                    <div class="recruitment-field"><label for="full_name">Nama lengkap <span aria-hidden="true">*</span></label><input id="full_name" name="full_name" type="text" value="{{ $oldText('full_name') }}" placeholder="Masukkan nama lengkap" autocomplete="name" maxlength="120" required @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror>@error('full_name')<p id="full_name-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-form-row">
                        <div class="recruitment-field"><label for="phone_number">Nomor WhatsApp <span aria-hidden="true">*</span></label><input id="phone_number" name="phone_number" type="tel" value="{{ $oldText('phone_number') }}" placeholder="08xxxxxxxxxx" autocomplete="tel" inputmode="tel" maxlength="25" required @error('phone_number') aria-invalid="true" aria-describedby="phone_number-error" @enderror>@error('phone_number')<p id="phone_number-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                        <div class="recruitment-field"><label for="domicile">Domisili <span aria-hidden="true">*</span></label><input id="domicile" name="domicile" type="text" value="{{ $oldText('domicile') }}" placeholder="Contoh: Jakarta Selatan" autocomplete="address-level2" maxlength="120" required @error('domicile') aria-invalid="true" aria-describedby="domicile-error" @enderror>@error('domicile')<p id="domicile-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="recruitment-field"><label for="activity">Aktivitas saat ini <span aria-hidden="true">*</span></label><select id="activity" name="activity" required @error('activity') aria-invalid="true" aria-describedby="activity-error" @enderror><option value="" disabled @selected(!old('activity'))>Pilih salah satu</option>@foreach ($content['activities'] as $activity)<option value="{{ $activity }}" @selected(old('activity') === $activity)>{{ $activity }}</option>@endforeach</select>@error('activity')<p id="activity-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-field"><label for="interest">Hal yang paling ingin Anda ketahui <span class="recruitment-optional">(opsional)</span></label><select id="interest" name="interest" @error('interest') aria-invalid="true" aria-describedby="interest-error" @enderror><option value="">Pilih topik yang Anda minati</option>@foreach ($content['interests'] as $interest)<option value="{{ $interest }}" @selected(old('interest') === $interest)>{{ $interest }}</option>@endforeach</select>@error('interest')<p id="interest-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-consent"><input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><label for="consent">Saya menyetujui penyimpanan data saya di database Let's Grow Academy untuk pendaftaran event dan keperluan admin menghubungi saya, sesuai <a href="#privasi">informasi penggunaan data di bawah</a>.</label></div>@error('consent')<p id="consent-error" class="recruitment-field-error">{{ $message }}</p>@enderror
                    <button type="submit" class="recruitment-button recruitment-submit">Kirim Minat Saya <x-recruitment-icon /></button><p class="recruitment-form-helper">Data Anda disimpan di database Let's Grow Academy untuk pendaftaran event. Admin akan menghubungi Anda dalam waktu dekat.</p>
                </form>
                <details id="privasi" class="recruitment-privacy"><summary>Informasi penggunaan data</summary><p>Nama, nomor WhatsApp, domisili, aktivitas, topik pilihan, waktu pendaftaran, dan persetujuan Anda disimpan di database Let's Grow Academy untuk didaftarkan ke dalam event. Data ini dikaitkan dengan halaman rekrutmen {{ $consultantName }} dan dapat dilihat oleh pemilik halaman untuk proses pendaftaran serta tindak lanjut event. Admin akan menghubungi Anda dalam waktu dekat melalui nomor WhatsApp yang Anda berikan. Anda dapat meminta penghentian tindak lanjut atau penghapusan data melalui kontak pendamping pada halaman ini.</p></details>
            </div>
        </section>
    </main>

    <footer class="recruitment-footer"><div class="recruitment-container"><div class="recruitment-footer-top"><div><a class="recruitment-brand" href="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}"><img src="{{ asset('images/coway_logo.png') }}" width="42" height="42" alt="" aria-hidden="true"><span>Let's Grow<span>Academy</span></span></a><p>Program edukasi, pendampingan, dan peluang bisnis kemitraan solusi pemurnian air modern di Indonesia.</p></div><div><span class="recruitment-eyebrow">Pendamping kemitraan</span><strong>{{ $consultantName }}</strong><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Hubungi melalui WhatsApp <span aria-hidden="true">↗</span></a><a href="#privasi">Informasi privasi</a></div></div><p class="recruitment-footer-disclaimer">Informasi produk, persyaratan, fasilitas, komisi, dan peluang penghasilan mengikuti ketentuan kemitraan resmi yang berlaku. Hasil setiap individu dapat berbeda dan bergantung pada aktivitas serta kinerja masing-masing.</p><div class="recruitment-footer-bottom"><span>© {{ now()->year }} Let's Grow Academy</span><a href="#main-content">Kembali ke atas ↑</a></div></div></footer>
</body>
</html>
