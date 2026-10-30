<!DOCTYPE html>
<html lang="id">
@php($oldText = fn (string $key) => is_string(old($key)) ? old($key) : '')
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kenali kebutuhan air minum sehari-hari dan peluang di sekitarnya. Daftar sesi pengenalan bersama {{ $consultantName }} di Let's Grow Academy.">
    <meta property="og:title" content="Peluang Bisnis Kebutuhan Air Minum | Let's Grow Academy">
    <meta property="og:description" content="Mulai dari kebutuhan air minum. Kenali peluangnya melalui sesi pengenalan bersama pendamping Anda.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}">
    <meta property="og:image" content="{{ asset('images/recruitment/drinking-water-family.webp') }}">
    <link rel="canonical" href="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Kebutuhan Air Minum & Peluangnya | {{ $consultantName }}</title>
    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="recruitment-page" data-recruitment-landing>
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <header class="recruitment-header">
        <nav class="recruitment-container recruitment-nav" aria-label="Navigasi rekrutmen">
            <a href="{{ route('subscriber-recruitment.show', $consultant->landing_page_slug) }}" class="recruitment-brand" aria-label="Let's Grow Academy — halaman rekrutmen {{ $consultantName }}">
                <img src="{{ asset('images/recruitment/letsgrow-logo.png') }}" alt="" width="42" height="42" aria-hidden="true">
                <span>Let's Grow<span>Academy</span></span>
            </a>
            <div class="recruitment-nav-links"><a href="#peluang">Air minum</a><a href="#materi">Materi sesi</a><a href="#cerita">Cerita mitra</a><a href="#faq">FAQ</a></div>
            <a href="#form-minat" class="recruitment-button recruitment-button-small">Daftar sesi <x-recruitment-icon /></a>
        </nav>
    </header>

    <main id="main-content" tabindex="-1">
        <section id="pengenalan" class="recruitment-hero" aria-labelledby="recruitment-title">
            <div class="recruitment-container recruitment-hero-grid">
                <div class="recruitment-hero-copy" data-recruitment-reveal>
                    <p class="recruitment-eyebrow">{{ $content['hero']['eyebrow'] }}</p>
                    <h1 id="recruitment-title">Dari Kebutuhan Air Minum Sehari-hari, <span>Bangun Sumber Penghasilan Baru yang Nyata.</span></h1>
                    <p class="recruitment-lead">{{ $content['hero']['body'] }}</p>
                    <div class="recruitment-actions">
                        <a href="#form-minat" class="recruitment-button">{{ $content['hero']['primary_cta'] }} <x-recruitment-icon /></a>
                        <a href="#cara-kerja" class="recruitment-text-link">{{ $content['hero']['secondary_cta'] }} <span aria-hidden="true">↘</span></a>
                    </div>
                    <p class="recruitment-microcopy">{{ $content['hero']['microcopy'] }}</p>
                </div>
                <figure class="recruitment-photo recruitment-hero-photo" data-recruitment-photo>
                    <img src="{{ asset('images/recruitment/drinking-water-family.webp') }}" srcset="{{ asset('images/recruitment/drinking-water-family-768.webp') }} 768w, {{ asset('images/recruitment/drinking-water-family.webp') }} 1536w" sizes="(max-width: 800px) 100vw, 45vw" alt="Keluarga menikmati air minum bersama saat sarapan di rumah" width="1536" height="1024" fetchpriority="high">
                </figure>
            </div>
        </section>

        <section id="tantangan" class="recruitment-section" aria-labelledby="recruitment-problems-title">
            <div class="recruitment-container recruitment-needs-grid">
                <div class="recruitment-section-copy" data-recruitment-reveal>
                    <p class="recruitment-eyebrow">{{ $content['problem']['eyebrow'] }}</p>
                    <h2 id="recruitment-problems-title">Sudah Kerja Keras <span class="recruitment-nowrap">9-to-5,</span> Tapi Penghasilan Masih Mepet?</h2>
                    <p>{{ $content['problem']['body'] }}</p>
                    <div class="recruitment-compact-list">@foreach ($content['problems'] as $item)<article data-recruitment-reveal><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div>
                </div>
                <x-recruitment-photo name="drinking-water-learning" alt="Seseorang mempelajari catatan dan menyusun rencana di rumah" />
            </div>
        </section>

        <section id="peluang" class="recruitment-section recruitment-surface" aria-labelledby="recruitment-needs-title">
            <div class="recruitment-container recruitment-needs-grid">
                <x-recruitment-photo name="drinking-water-pour" class="recruitment-water-photo" alt="Air minum dituangkan dari teko kaca ke dalam gelas" />
                <div class="recruitment-section-copy" data-recruitment-reveal>
                    <p class="recruitment-eyebrow">{{ $content['market']['eyebrow'] }}</p>
                    <h2 id="recruitment-needs-title">{{ $content['market']['headline'] }}</h2>
                    <p>{{ $content['market']['paragraphs'][0] }}</p>
                    <ul class="recruitment-needs-list">@foreach ($content['needs'] as $item)<li><x-recruitment-icon name="check" /><div><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></div></li>@endforeach</ul>
                    <p class="recruitment-callout">{{ $content['market']['highlight'] }}</p>
                </div>
            </div>
        </section>

        <section id="solusi" class="recruitment-section" aria-labelledby="recruitment-solutions-title">
            <div class="recruitment-container recruitment-needs-grid">
                <div class="recruitment-section-copy" data-recruitment-reveal>
                    <p class="recruitment-eyebrow">{{ $content['product']['eyebrow'] }}</p>
                    <h2 id="recruitment-solutions-title">{{ $content['product']['headline'] }}</h2>
                    <p>{{ $content['product']['body'] }}</p>
                    <div class="recruitment-compact-list">@foreach ($content['product_points'] as $item)<article data-recruitment-reveal><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div>
                    <a href="#form-minat" class="recruitment-text-link recruitment-section-link">{{ $content['product']['cta'] }} <x-recruitment-icon /></a>
                </div>
                <x-recruitment-photo name="drinking-water-solutions" alt="Gelas air minum dan perangkat filtrasi tanpa merek di dapur rumah" />
            </div>
        </section>

        <section id="bukti-penghasilan" class="recruitment-section recruitment-surface" aria-labelledby="recruitment-proof-title">
            <div class="recruitment-container">
                <div class="recruitment-centered-heading" data-recruitment-reveal><p class="recruitment-eyebrow">Proof & social proof metrics</p><h2 id="recruitment-proof-title">{{ $content['proof']['headline'] }}</h2></div>
                <div class="recruitment-receipts">
                    @foreach ($content['proof']['receipts'] as $receipt)
                        <a href="{{ asset('images/recruitment/'.$receipt['image']) }}" class="recruitment-receipt" target="_blank" rel="noopener" aria-label="Perbesar bukti transfer {{ $receipt['date'] }} sebesar {{ $receipt['amount'] }}">
                            <img src="{{ asset('images/recruitment/'.$receipt['image']) }}" alt="Screenshot bukti transfer {{ $receipt['date'] }} sebesar {{ $receipt['amount'] }}, nama pengirim disamarkan" width="{{ $receipt['width'] }}" height="{{ $receipt['height'] }}" loading="lazy" decoding="async">
                        </a>
                    @endforeach
                </div>
                <div class="recruitment-proof-highlights">@foreach ($content['proof']['highlights'] as $item)<div><span>{{ $item['title'] }}</span><strong>{{ $item['body'] }}</strong></div>@endforeach</div>
            </div>
        </section>

        <section id="materi" class="recruitment-section" aria-labelledby="recruitment-learning-title">
            <div class="recruitment-container">
                <div class="recruitment-learning-heading" data-recruitment-reveal><div><p class="recruitment-eyebrow">{{ $content['curriculum']['eyebrow'] }}</p><h2 id="recruitment-learning-title">{{ $content['curriculum']['headline'] }}</h2></div><p>{{ $content['curriculum']['body'] }}</p></div>
                <x-recruitment-photo name="drinking-water-discussion" class="recruitment-discussion-photo" sizes="(max-width: 1280px) 100vw, 1200px" alt="Kelompok peserta berdiskusi dan mempelajari catatan bersama" />
                <div class="recruitment-materials recruitment-materials-five">@foreach ($content['materials'] as $item)<article data-recruitment-reveal><span class="recruitment-material-number">0{{ $loop->iteration }}</span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div>
            </div>
        </section>

        <section id="cara-kerja" class="recruitment-section recruitment-dark" aria-labelledby="recruitment-process-title">
            <div class="recruitment-container">
                <div class="recruitment-needs-grid"><div class="recruitment-section-copy" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['process']['eyebrow'] }}</p><h2 id="recruitment-process-title">{{ $content['process']['headline'] }}</h2><p>{{ $content['process']['body'] }}</p><a href="#form-minat" class="recruitment-button recruitment-button-light recruitment-section-link">{{ $content['process']['cta'] }} <x-recruitment-icon /></a></div><x-recruitment-photo name="drinking-water-steps" alt="Pendamping mendengarkan kebutuhan keluarga dalam percakapan di rumah" /></div>
                <ol class="recruitment-steps recruitment-steps-four">@foreach ($content['steps'] as $item)<li data-recruitment-reveal><span class="recruitment-step-number" aria-hidden="true">{{ $loop->iteration }}</span><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></li>@endforeach</ol>
            </div>
        </section>

        <section id="mulai-dari-dasar" class="recruitment-section" aria-labelledby="recruitment-objections-title">
            <div class="recruitment-container recruitment-needs-grid"><x-recruitment-photo name="drinking-water-mentoring" alt="Dua orang mempelajari catatan dan berdiskusi bersama" /><div class="recruitment-section-copy" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['beginner']['eyebrow'] }}</p><h2 id="recruitment-objections-title">{{ $content['beginner']['headline'] }}</h2><p>{{ $content['beginner']['body'] }}</p><div class="recruitment-compact-list">@foreach ($content['objections'] as $item)<article data-recruitment-reveal><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div></div></div>
        </section>

        <section id="cerita" class="recruitment-section recruitment-surface" aria-labelledby="recruitment-stories-title">
            <div class="recruitment-container">
                <div class="recruitment-centered-heading" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['journeys']['eyebrow'] }}</p><h2 id="recruitment-stories-title">{{ $content['journeys']['headline'] }}</h2><p>{{ $content['journeys']['body'] }}</p></div>
                <x-recruitment-photo name="drinking-water-stories" class="recruitment-stories-photo" sizes="(max-width: 1280px) 100vw, 1200px" alt="Orang dari berbagai usia berjalan bersama setelah kegiatan belajar" />
                <div class="recruitment-compact-grid recruitment-grid-four recruitment-story-grid">@foreach ($content['stories'] as $item)<article data-recruitment-reveal><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div>
                <p class="recruitment-note">{{ $content['journeys']['note'] }}</p>
                <div class="recruitment-centered-action"><a href="#form-minat" class="recruitment-text-link">{{ $content['journeys']['cta'] }} <x-recruitment-icon /></a></div>
            </div>
        </section>

        <section id="pembimbing" class="recruitment-section" aria-labelledby="recruitment-mentor-title">
            <div class="recruitment-container recruitment-needs-grid">
                <div class="recruitment-mentor-visual"><x-recruitment-photo name="drinking-water-workspace" alt="Meja pendamping dengan buku catatan, laptop, dan segelas air minum" /><div class="recruitment-mentor"><span class="recruitment-mentor-initials" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($consultantName, 0, 1)) }}</span><div><span>Pendamping Anda</span><strong>{{ $consultantName }}</strong><span>{{ $consultantRole }}</span></div></div></div>
                <div class="recruitment-section-copy" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['mentor']['eyebrow'] }}</p><h2 id="recruitment-mentor-title">{{ $content['mentor']['headline'] }}</h2><p>{{ $content['mentor']['body'] }}</p><a href="#form-minat" class="recruitment-button recruitment-section-link">{{ $content['mentor']['cta'] }} <x-recruitment-icon /></a><a href="{{ $whatsappUrl }}" class="recruitment-text-link recruitment-section-link" target="_blank" rel="noopener">Tanya {{ $consultantName }} <span aria-hidden="true">↗</span></a></div>
            </div>
        </section>

        <section id="dukungan" class="recruitment-section recruitment-surface" aria-labelledby="recruitment-support-title">
            <div class="recruitment-container recruitment-needs-grid"><div class="recruitment-section-copy" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['community']['eyebrow'] }}</p><h2 id="recruitment-support-title">{{ $content['community']['headline'] }}</h2><p>{{ $content['community']['body'] }}</p><div class="recruitment-compact-list">@foreach ($content['support'] as $item)<article data-recruitment-reveal><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></article>@endforeach</div></div><x-recruitment-photo name="drinking-water-support" alt="Komunitas kecil saling berbagi ide di ruang belajar" /></div>
        </section>

        <section id="undangan" class="recruitment-section" aria-labelledby="recruitment-invitation-title">
            <div class="recruitment-container recruitment-invitation">
                <div data-recruitment-reveal>
                    <p class="recruitment-eyebrow">{{ $content['form']['eyebrow'] }}</p>
                    <h2 id="recruitment-invitation-title">{{ $content['form']['headline'] }}</h2>
                    <p>{{ $content['form']['body'] }}</p>
                    <a href="#form-minat" class="recruitment-button">Saya Tertarik Mengikuti Sesi <x-recruitment-icon /></a>
                </div>
                <x-recruitment-photo name="drinking-water-classroom" alt="Fasilitator membawakan sesi pengenalan di kelas dengan peserta yang menyimak dan mencatat" />
            </div>
        </section>

        <section id="faq" class="recruitment-section" aria-labelledby="recruitment-faq-title">
            <div class="recruitment-container recruitment-faq-grid"><div data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['faq']['eyebrow'] }}</p><h2 id="recruitment-faq-title">{{ $content['faq']['headline'] }}</h2><x-recruitment-photo name="drinking-water-questions" class="recruitment-faq-photo" alt="Seorang peserta mengangkat tangan untuk bertanya dalam diskusi" /></div><div class="recruitment-faq">@foreach ($content['faqs'] as $item)<details><summary>{{ $item['question'] }}<span class="recruitment-faq-plus" aria-hidden="true"></span></summary><p>{{ $item['answer'] }}</p></details>@endforeach</div></div>
        </section>

        <section id="langkah-pertama" class="recruitment-section recruitment-final-cta" aria-labelledby="recruitment-final-title">
            <div class="recruitment-container recruitment-needs-grid"><div class="recruitment-section-copy" data-recruitment-reveal><p class="recruitment-eyebrow">Tentukan langkah Anda</p><h2 id="recruitment-final-title">{{ $content['final_cta']['headline'] }}</h2><div class="recruitment-choice-list">@foreach ($content['final_cta']['choices'] as $item)<div><span>0{{ $loop->iteration }}</span><div><h3>{{ $item['title'] }}</h3><p>{{ $item['body'] }}</p></div></div>@endforeach</div><a href="#form-minat" class="recruitment-button">{{ $content['final_cta']['cta'] }} <x-recruitment-icon /></a></div><x-recruitment-photo name="drinking-water-choice" alt="Seseorang meluangkan waktu untuk memikirkan rencana belajar berikutnya" /></div>
            <p class="recruitment-note recruitment-container">{{ $content['final_cta']['disclaimer'] }}</p>
        </section>

        <section id="form-minat" class="recruitment-section recruitment-form-section" aria-labelledby="recruitment-form-title">
            <div class="recruitment-container recruitment-signup-grid"><div class="recruitment-signup-intro" data-recruitment-reveal><p class="recruitment-eyebrow">{{ $content['form']['eyebrow'] }}</p><h2 id="recruitment-form-title">{{ $content['form']['headline'] }}</h2><p>{{ $content['form']['body'] }}</p><x-recruitment-photo name="drinking-water-signup" class="recruitment-signup-photo" alt="Tangan menggunakan ponsel untuk mengisi pendaftaran di meja rumah" /></div>
            <div class="recruitment-form-card"><h3>Form Pendaftaran Minat</h3>
                <div class="recruitment-form-recipient"><x-recruitment-icon name="people" /><span>Terhubung dengan <strong>{{ $consultantName }}</strong></span></div>
                @if ($errors->any())
                    <div class="recruitment-form-errors" role="alert" tabindex="-1"><strong>Periksa kembali data Anda.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form method="POST" action="{{ route('subscriber-recruitment.submit', $consultant->landing_page_slug) }}">
                    @csrf
                    <div class="recruitment-field"><label for="full_name">Nama Lengkap <span aria-hidden="true">*</span></label><input id="full_name" name="full_name" type="text" value="{{ $oldText('full_name') }}" placeholder="Masukkan nama lengkap" autocomplete="name" maxlength="120" required @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror>@error('full_name')<p id="full_name-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-form-row">
                        <div class="recruitment-field"><label for="phone_number">Nomor WhatsApp <span aria-hidden="true">*</span></label><input id="phone_number" name="phone_number" type="tel" value="{{ $oldText('phone_number') }}" placeholder="08xxxxxxxxxx" autocomplete="tel" inputmode="tel" maxlength="25" required @error('phone_number') aria-invalid="true" aria-describedby="phone_number-error" @enderror>@error('phone_number')<p id="phone_number-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                        <div class="recruitment-field"><label for="domicile">Domisili <span aria-hidden="true">*</span></label><input id="domicile" name="domicile" type="text" value="{{ $oldText('domicile') }}" placeholder="Contoh: Jakarta Selatan" autocomplete="address-level2" maxlength="120" required @error('domicile') aria-invalid="true" aria-describedby="domicile-error" @enderror>@error('domicile')<p id="domicile-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="recruitment-field"><label for="activity">Aktivitas Saat Ini <span aria-hidden="true">*</span></label><select id="activity" name="activity" required @error('activity') aria-invalid="true" aria-describedby="activity-error" @enderror><option value="" disabled @selected(!old('activity'))>Pilih salah satu</option>@foreach ($content['activities'] as $activity)<option value="{{ $activity }}" @selected(old('activity') === $activity)>{{ $activity }}</option>@endforeach</select>@error('activity')<p id="activity-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-field"><label for="interest">Hal yang Paling Ingin Kamu Ketahui <span class="recruitment-optional">(opsional)</span></label><select id="interest" name="interest" @error('interest') aria-invalid="true" aria-describedby="interest-error" @enderror><option value="">Pilih topik yang Anda minati</option>@foreach ($content['interests'] as $interest)<option value="{{ $interest }}" @selected(old('interest') === $interest)>{{ $interest }}</option>@endforeach</select>@error('interest')<p id="interest-error" class="recruitment-field-error">{{ $message }}</p>@enderror</div>
                    <div class="recruitment-consent"><input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror><label for="consent">{{ $content['form']['consent'] }}</label></div>@error('consent')<p id="consent-error" class="recruitment-field-error">{{ $message }}</p>@enderror
                    <button type="submit" class="recruitment-button recruitment-submit">{{ $content['form']['cta'] }} <x-recruitment-icon /></button><p class="recruitment-form-helper">{{ $content['form']['helper'] }}</p>
                </form>
                <details id="privasi" class="recruitment-privacy"><summary>Informasi penggunaan data</summary><p>Nama, nomor WhatsApp, domisili, aktivitas, topik pilihan, waktu pendaftaran, dan persetujuan Anda disimpan di database Let's Grow Academy untuk didaftarkan ke dalam event. Data ini dikaitkan dengan halaman rekrutmen {{ $consultantName }} dan dapat dilihat oleh pemilik halaman untuk proses pendaftaran serta tindak lanjut event. Admin akan menghubungi Anda dalam waktu dekat melalui nomor WhatsApp yang Anda berikan. Anda dapat meminta penghentian tindak lanjut atau penghapusan data melalui kontak pendamping pada halaman ini.</p></details>
            </div>
            </div>
        </section>
    </main>
    <footer class="recruitment-footer"><div class="recruitment-container recruitment-footer-content"><div><strong>{{ $content['footer']['brand'] }}</strong><p>{{ $content['footer']['body'] }}</p><p>{{ $content['footer']['disclaimer'] }}</p><span>© {{ now()->year }} Let's Grow Academy</span></div><div><a href="#privasi">Informasi penggunaan data</a><a href="#main-content">Kembali ke atas ↑</a></div></div></footer>
</body>
</html>
