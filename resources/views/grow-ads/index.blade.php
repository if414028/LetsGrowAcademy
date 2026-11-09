<x-dashboard-layout>
    <section class="ga-page" data-grow-ads>
        <header class="ga-page-header"><div><p class="ga-kicker">Jangkauan baru, peluang baru</p><h1>Grow Ads</h1></div><span class="ga-label"><x-grow-ad-icon name="megaphone" />Khusus subscriber</span></header>
        @include('grow-ads.partials.feedback')
        @if (! $activated)
            <div class="ga-intro">
                <div class="ga-intro-copy"><span class="ga-wordmark">GROW <strong>ADS</strong><span aria-hidden="true">↗</span></span><h2>Event kamu.<br><span>Jangkauan lebih luas.</span></h2><p>Aktifkan Grow Ads untuk mempromosikan event di Facebook dan Instagram, lalu arahkan calon peserta ke landing page kamu.</p>
                    <div class="ga-actions"><form method="POST" action="{{ route('grow-ads.activate') }}" x-data="{busy:false}" @submit="busy=true">@csrf<button class="ga-button" :disabled="busy" type="submit"><span x-text="busy ? 'Mengaktifkan…' : 'Aktifkan Grow Ads'">Aktifkan Grow Ads</span><x-grow-ad-icon /></button></form><a href="#cara-kerja-grow-ads" class="ga-text-link">Pelajari cara kerjanya <span aria-hidden="true">↓</span></a></div>
                    <p class="ga-hint"><x-grow-ad-icon name="check" />Aktivasi gratis. Bayar saat kamu siap beriklan.</p>
                </div>
                <div class="ga-intro-art" data-ga-art>@include('grow-ads.illustration')</div>
            </div>
            <div class="ga-flow-strip" aria-label="Alur calon peserta"><span>Konten di Meta Ads</span><x-grow-ad-icon /><span>Landing page kamu</span><x-grow-ad-icon /><span>Pendaftaran event</span></div>
            <section id="cara-kerja-grow-ads" class="ga-how" aria-labelledby="ga-how-title"><div><p class="ga-kicker">Dari iklan ke pendaftaran</p><h2 id="ga-how-title">Kamu tentukan arahnya.<br>Admin bantu jalankan.</h2><p>Kamu memilih konten, menentukan budget harian, dan mengatur lama iklan berjalan. Setelah pembayaran terkonfirmasi, Admin menyiapkan penayangan di Meta Ads agar calon peserta bisa menemukan event dan mendaftar lewat landing page kamu.</p></div>
                <ol class="ga-how-grid">
                    @foreach ([
                        ['content', 'Kamu', 'Pilih konten iklan', 'Temukan materi promosi yang paling sesuai dengan eventmu. Lihat pratinjaunya, lalu pilih 1–3 konten dari koleksi yang sudah disiapkan Admin.'],
                        ['budget', 'Kamu', 'Atur budget harian & durasi', 'Tentukan biaya harian dan berapa lama iklan ditampilkan. Budget yang lebih besar membuka lebih banyak peluang untuk menjangkau audiens dan mendapatkan engagement.'],
                        ['launch', 'Admin', 'Admin siapkan penayangan', 'Setelah pembayaran terkonfirmasi, Admin menyiapkan iklan di Facebook dan Instagram. Tautan iklan diarahkan ke landing page dengan kamu sebagai pembimbing.'],
                    ] as [$illustration, $owner, $title, $body])
                        <li class="ga-how-item"><div class="ga-how-art">@include('grow-ads.how-illustration', ['variant' => $illustration])</div><div class="ga-how-copy"><p class="ga-how-owner"><span aria-hidden="true">{{ $loop->iteration }}</span>{{ $owner }}</p><h3>{{ $title }}</h3><p>{{ $body }}</p></div></li>
                    @endforeach
                </ol>
                <div class="ga-how-followup"><span class="ga-feature-icon"><x-grow-ad-icon name="chart" /></span><div><h3>Pantau responsnya. Temukan calon peserta berikutnya.</h3><p>Lihat tayangan, klik, dan pendaftaran dari dashboard Grow Ads. Setiap orang yang mendaftar melalui iklanmu tercatat di bawah bimbinganmu, sehingga kamu bisa menindaklanjuti pendaftarannya.</p></div></div>
            </section>
            <section class="ga-personal"><div class="ga-personal-visual"><x-grow-ad-icon name="link" /><span>letsgrowacademy.id/rekrutmen/</span><strong>{{ auth()->user()->landing_page_slug }}</strong></div><div><p class="ga-kicker">Satu halaman, atas nama kamu</p><h2>Calon peserta mengenal<br>pembimbingnya sejak awal.</h2><p>Landing page menampilkan {{ auth()->user()->full_name ?: auth()->user()->name }} sebagai pembimbing. Form pendaftaran langsung menghubungkan peserta dengan akunmu.</p><a class="ga-text-link" href="{{ route('subscriber-recruitment.show', auth()->user()->landing_page_slug) }}" target="_blank" rel="noopener">Lihat landing page kamu <span aria-hidden="true">↗</span><span class="sr-only">(tab baru)</span></a></div></section>
            <div class="ga-final"><div><h2>Siap menjangkau peserta berikutnya?</h2><p>Mulai dari konten yang sesuai dengan eventmu.</p></div><form method="POST" action="{{ route('grow-ads.activate') }}">@csrf<button type="submit" class="ga-button">Aktifkan Grow Ads <x-grow-ad-icon /></button></form></div>
        @else
            <div class="ga-section-heading"><div><h2>Iklan kamu</h2><p>Semua kampanye dan perkembangannya, dalam satu tempat.</p></div><a class="ga-button" href="{{ route('grow-ads.create') }}"><x-grow-ad-icon name="plus" />Buat iklan baru</a></div>
            @forelse ($campaigns as $campaign)
                <a href="{{ route('grow-ads.show', $campaign) }}" class="ga-campaign-row"><div class="ga-campaign-thumb">@if($campaign->placements->first()?->media_type === 'image')<img src="{{ $campaign->placements->first()->media_url }}" alt="" loading="lazy">@else<x-grow-ad-icon name="megaphone" />@endif</div><div class="ga-campaign-info"><span class="ga-status ga-status--{{ $campaign->status }}">{{ $campaign->status_label }}</span><h3>{{ $campaign->placements->first()?->title ?? 'Kampanye baru' }}</h3><p>{{ $campaign->placements->count() }} konten · Dibuat {{ $campaign->created_at->format('d M Y') }}</p></div><div class="ga-campaign-price">@if($campaign->total_amount)<strong>Rp{{ number_format($campaign->total_amount, 0, ',', '.') }}</strong><span>{{ $campaign->duration_days }} hari</span>@else<span>Lanjutkan pengaturan</span>@endif</div><x-grow-ad-icon /></a>
            @empty
                <div class="ga-empty"><span class="ga-empty-icon"><x-grow-ad-icon name="megaphone" /></span><h2>Peluang baru dimulai dari satu iklan.</h2><p>Grow Ads sudah aktif. Pilih konten untuk membuat kampanye pertamamu.</p><a class="ga-button" href="{{ route('grow-ads.create') }}">Pilih konten iklan <x-grow-ad-icon /></a></div>
            @endforelse
            {{ $campaigns->links() }}
        @endif
    </section>
</x-dashboard-layout>
