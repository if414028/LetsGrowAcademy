<x-dashboard-layout>
    <section class="ga-page" data-grow-ads>
        <header class="ga-page-header"><div><a href="{{ route('grow-ads.index') }}" class="ga-back"><x-grow-ad-icon name="back" />Grow Ads</a><h1>{{ $campaign->placements->first()?->title ?? 'Kampanye Grow Ads' }}</h1><p>Kampanye #{{ $campaign->id }} · {{ $campaign->placements->count() }} konten · {{ $campaign->duration_days }} hari</p></div><span class="ga-status ga-status--{{ $campaign->status }}">{{ $campaign->status_label }}</span></header>
        @include('grow-ads.partials.feedback')
        @if ($campaign->payment_status !== 'paid')
            @include('grow-ads.partials.steps', ['step' => 3])
            <div class="ga-checkout-layout"><div class="ga-panel ga-payment-state"><span class="ga-empty-icon"><x-grow-ad-icon name="wallet" /></span><h2>{{ $campaign->status === 'awaiting_payment' ? 'Satu langkah lagi untuk mulai.' : 'Pembayaran belum selesai.' }}</h2><p>{{ $campaign->status === 'awaiting_payment' ? 'Selesaikan pembayaran untuk mengirimkan pilihan iklanmu ke Admin.' : 'Pembayaran ini tidak dapat dilanjutkan. Kamu bisa membuat kampanye baru dengan pilihan konten yang tersedia.' }}</p>
                @if($campaign->payment_status === 'refunded')<p class="ga-inline-hint">Pembayaran direfund atau disengketakan. Kampanye dihentikan; hubungi Admin untuk rinciannya.</p>@endif
                @if($campaign->status === 'awaiting_payment')
                    <div x-data="growAdPayment(@js($campaign->snap_token))"><button type="button" class="ga-button" @click="pay" :disabled="busy"><span x-text="busy ? 'Membuka pembayaran…' : 'Bayar melalui Midtrans'">Bayar melalui Midtrans</span><x-grow-ad-icon /></button><p class="ga-inline-error" x-show="message" x-cloak x-text="message" role="status"></p></div>
                @else<a href="{{ route('grow-ads.create') }}" class="ga-button">Buat iklan baru <x-grow-ad-icon /></a>@endif
                <form method="POST" action="{{ route('grow-ads.sync-payment', $campaign) }}" id="ga-payment-sync" x-data="{busy:false}" @submit="busy=true">@csrf<button type="submit" class="ga-text-link" :disabled="busy" x-text="busy ? 'Memeriksa pembayaran…' : 'Sudah bayar? Periksa status pembayaran'">Sudah bayar? Periksa status pembayaran</button></form>
            </div><aside class="ga-panel"><h2>Rincian pembayaran</h2>@include('grow-ads.partials.cost', ['item' => $campaign->toArray()])<p class="ga-footnote">Order {{ $campaign->midtrans_order_id }}</p></aside></div>
            @push('scripts')<script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}"></script>@endpush
        @else
            @include('grow-ads.partials.steps', ['step' => 4])
            @if($campaign->status === 'preparing')<div class="ga-preparing"><span class="ga-feature-icon"><x-grow-ad-icon name="clock" /></span><div><h2>Pembayaran diterima. Iklanmu sedang disiapkan.</h2><p>Admin menyiapkan konten, pixel, dan tautan landing page. Durasi {{ $campaign->duration_days }} hari dimulai saat iklan dijalankan.</p></div><span class="ga-status ga-status--completed"><x-grow-ad-icon name="check" />Sudah dibayar</span></div>@endif
            @if ($campaign->admin_note)<div class="ga-notice"><div><strong>Catatan Admin</strong><p>{{ $campaign->admin_note }}</p></div></div>@endif
            <div class="ga-section-heading"><div><h2>Perkembangan iklan</h2><p>{{ $campaign->metrics_updated_at ? 'Data Meta Ads diperbarui Admin pada '.$campaign->metrics_updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB.' : 'Data Meta Ads akan tersedia setelah diperbarui oleh Admin.' }}</p></div>@if($campaign->starts_at)<span class="ga-date">{{ $campaign->starts_at->timezone('Asia/Jakarta')->format('d M Y') }} – {{ $campaign->ends_at->timezone('Asia/Jakarta')->format('d M Y') }}</span>@endif</div>
            @php($metricsComplete = $campaign->placements->every(fn ($placement) => $placement->impressions !== null && $placement->clicks !== null))
            <div class="ga-stats">
                @foreach ([['eye','Tayangan',$metricsComplete ? number_format($campaign->placements->sum('impressions'), 0, ',', '.') : '—','Berapa kali iklan ditampilkan'],['click','Klik iklan',$metricsComplete ? number_format($campaign->placements->sum('clicks'), 0, ',', '.') : '—','Klik yang dilaporkan Meta Ads'],['people','Pendaftar',number_format($registeredCount, 0, ',', '.'),'Dari form landing page iklan'],['check','Peserta sudah bayar',number_format($paidCount, 0, ',', '.'),'Pembayaran dari tautan iklan kampanye ini']] as [$icon,$label,$value,$hint])
                    <article><div><span>{{ $label }}</span><x-grow-ad-icon :name="$icon" /></div><strong>{{ $value }}</strong><p>{{ $hint }}</p></article>
                @endforeach
            </div>
            @unless($metricsComplete)<p class="ga-footnote">Tanda “—” berarti data tayangan atau klik belum lengkap. Pendaftaran dicatat langsung melalui form.</p>@endunless
            <section class="ga-content-performance" aria-labelledby="ga-funnel-title">
                <div class="ga-section-heading">
                    <div><h2 id="ga-funnel-title">Aktivitas landing page</h2><p>Data yang dicatat Grow Ads dari tautan iklan kampanye ini.</p></div>
                </div>
                <div class="ga-stats ga-stats--funnel">
                    @foreach ([
                        ['click', 'AddPaymentInfo', 'Klik tombol menuju form'],
                        ['people', 'AddToCart', 'Form minat yang berhasil dikirim'],
                        ['check', 'Purchase', 'Pembayaran event yang terverifikasi'],
                    ] as [$icon, $event, $description])
                        <article>
                            <div><span>{{ $event }}</span><x-grow-ad-icon :name="$icon" /></div>
                            <strong>{{ number_format($funnel[$event], 0, ',', '.') }}</strong>
                            <p>{{ $description }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
            <section class="ga-panel ga-content-performance"><h2>Konten yang dipromosikan</h2><div class="ga-performance-list">@foreach($campaign->placements as $placement)<article><div class="ga-performance-media">@include('grow-ads.partials.media', ['item' => $placement])</div><div><h3>{{ $placement->title }}</h3><dl><div><dt>Tayangan</dt><dd>{{ $placement->impressions === null ? '—' : number_format($placement->impressions, 0, ',', '.') }}</dd></div><div><dt>Klik</dt><dd>{{ $placement->clicks === null ? '—' : number_format($placement->clicks, 0, ',', '.') }}</dd></div><div><dt>Pendaftar</dt><dd>{{ $placement->leads_count }}</dd></div></dl></div></article>@endforeach</div></section>
            <div class="ga-landing-strip"><span class="ga-feature-icon"><x-grow-ad-icon name="link" /></span><div><h3>Landing page kamu</h3><p>Pengunjung iklan akan terhubung dengan {{ $campaign->user->full_name ?: $campaign->user->name }}.</p></div><a class="ga-button ga-button--secondary" href="{{ route('subscriber-recruitment.show', $campaign->user->landing_page_slug) }}" target="_blank" rel="noopener">Buka halaman <span aria-hidden="true">↗</span><span class="sr-only">(tab baru)</span></a></div>
            @include('grow-ads.partials.leads')
            <details class="ga-receipt"><summary>Lihat rincian pembayaran kampanye</summary><div class="ga-panel">@include('grow-ads.partials.cost', ['item' => $campaign->toArray()])<p class="ga-footnote">Dibayar {{ $campaign->paid_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB · {{ $campaign->payment_type }}</p></div></details>
        @endif
    </section>
</x-dashboard-layout>
