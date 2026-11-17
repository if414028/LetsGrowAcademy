<x-dashboard-layout>
    <section class="recruitment-leads-page mx-auto max-w-6xl" aria-labelledby="recruitment-leads-title">
        @include('subscriber-recruitment.menu')

        <header class="recruitment-leads-header">
            <div>
                <p class="recruitment-leads-eyebrow">Landing Page Rekrutmen</p>
                <h1 id="recruitment-leads-title">Daftar Peminat</h1>
                <p>Data pendaftar dari form minat rekrutmen Anda.</p>
            </div>
        </header>

        <div class="recruitment-leads-owner">
            <x-recruitment-icon name="people" />
            <div><strong>Milik {{ $consultant->full_name ?: $consultant->name }}</strong><span>Hanya pendaftar melalui form rekrutmen Anda yang tampil di sini.</span></div>
        </div>

        <div class="recruitment-leads-panel">
            <form method="GET" action="{{ route('subscriber-recruitment.leads') }}" class="recruitment-leads-search" role="search">
                <div><label for="recruitment-lead-search">Cari peminat</label><input id="recruitment-lead-search" name="q" type="search" value="{{ $search }}" placeholder="Nama, WhatsApp, atau domisili" maxlength="120"></div>
                <button type="submit">Cari</button>
                @if ($search !== '')<a href="{{ route('subscriber-recruitment.leads') }}">Reset</a>@endif
            </form>
            @error('q')<p class="recruitment-leads-search-error" role="alert">Pencarian maksimal 120 karakter.</p>@enderror

            @if ($leads->count())
                <div class="recruitment-leads-result-count" role="status">{{ $search !== '' ? 'Hasil pencarian: ' : 'Menampilkan ' }}{{ $leads->firstItem() }}–{{ $leads->lastItem() }} dari {{ $leads->total() }} pendaftaran</div>
                <p class="recruitment-leads-status-help"><strong>Belum dibayar:</strong> belum ada tagihan aktif. <strong>Menunggu pembayaran:</strong> tagihan sudah dibuat dan belum terkonfirmasi lunas.</p>
                <div class="recruitment-leads-columns" aria-hidden="true"><span>Peminat</span><span>Data form</span><span>Asal pendaftaran</span><span>Tindak lanjut</span></div>
                <div class="recruitment-leads-list" role="list" aria-label="Peminat dari landing page Anda">
                    @foreach ($leads as $lead)
                        <article class="recruitment-lead-row" role="listitem" aria-labelledby="lead-name-{{ $lead->id }}">
                            <div class="recruitment-lead-contact">
                                <h2 id="lead-name-{{ $lead->id }}">{{ $lead->full_name }}</h2>
                                <p class="recruitment-lead-phone">+{{ $lead->phone_number }}</p>
                                <p class="recruitment-lead-domicile"><span class="sr-only">Domisili: </span>{{ $lead->domicile }}</p>
                            </div>
                            <dl class="recruitment-lead-fields">
                                <div><dt>Aktivitas saat ini</dt><dd>{{ $lead->activity }}</dd></div>
                                <div><dt>Ingin mengetahui</dt><dd>{{ $lead->interest ?: 'Tidak diisi' }}</dd></div>
                            </dl>
                            <div class="recruitment-lead-source">
                                <span class="recruitment-lead-source-badge">Form rekrutmen</span>
                                <p class="recruitment-lead-source-slug"><span class="sr-only">Asal form: </span>/rekrutmen/{{ $lead->source_slug }}</p>
                                <p class="recruitment-lead-consent"><x-recruitment-icon name="check" /> Persetujuan data diberikan</p>
                            </div>
                            <div class="recruitment-lead-followup">
                                <time datetime="{{ $lead->created_at->toIso8601String() }}">{{ $lead->created_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y') }}<span>{{ $lead->created_at->timezone('Asia/Jakarta')->format('H.i') }} WIB</span></time>
                                @php
                                    $paymentStatus = match ($lead->event_payment_status) {
                                        'paid' => ['label' => 'Sudah dibayar', 'tone' => 'paid'],
                                        'awaiting_payment', 'pending', 'unconfirmed' => $lead->latestEventPayment?->status === 'pending'
                                            ? ['label' => 'Menunggu pembayaran', 'tone' => 'pending']
                                            : ['label' => 'Belum dibayar', 'tone' => 'unpaid'],
                                        'failed' => ['label' => 'Pembayaran gagal', 'tone' => 'failed'],
                                        'expired' => ['label' => 'Pembayaran kedaluwarsa', 'tone' => 'neutral'],
                                        'refunded' => ['label' => 'Pembayaran dikembalikan', 'tone' => 'refunded'],
                                        default => ['label' => 'Belum dikonfirmasi', 'tone' => 'neutral'],
                                    };
                                    $paymentUrl = $lead->continuePaymentUrl();
                                @endphp
                                <p class="recruitment-lead-payment">
                                    <span class="recruitment-lead-payment-label">Status pembayaran</span>
                                    <strong class="recruitment-lead-payment-badge recruitment-lead-payment-badge--{{ $paymentStatus['tone'] }}">{{ $paymentStatus['label'] }}</strong>
                                </p>
                                <div class="recruitment-lead-actions" @if ($paymentUrl) x-data="recruitmentPaymentLink(@js(session('payment_link_ready') === $lead->id))" @endif>
                                    @if ($paymentUrl)
                                        <button type="button" @click="copy" aria-describedby="lead-name-{{ $lead->id }}" :aria-label="copied ? 'Link pembayaran disalin' : 'Salin link pembayaran'">Salin link pembayaran</button>
                                        <p class="recruitment-lead-copy-status" role="status" aria-live="polite" x-text="copyStatus">{{ session('payment_link_ready') === $lead->id ? 'Link pembayaran siap disalin.' : '' }}</p>
                                        <input class="recruitment-lead-payment-link" x-cloak x-show="manualCopy" x-ref="paymentLink" type="text" readonly value="{{ $paymentUrl }}" aria-label="Link pembayaran {{ $lead->full_name }}">
                                        <noscript><input class="recruitment-lead-payment-link" type="text" readonly value="{{ $paymentUrl }}" aria-label="Link pembayaran {{ $lead->full_name }}"></noscript>
                                        <a href="{{ $lead->paymentFollowupWhatsappUrl() }}" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" aria-label="Siapkan pesan pembayaran untuk {{ $lead->full_name }} di WhatsApp (tab baru)">Follow-up WhatsApp <span aria-hidden="true">↗</span></a>
                                    @else
                                        @if (! in_array($lead->event_payment_status, ['paid', 'refunded'], true))
                                            <form method="POST" action="{{ route('subscriber-recruitment.payment-link', $lead->id) }}">
                                                @csrf
                                                <input type="hidden" name="q" value="{{ $search }}">
                                                <input type="hidden" name="page" value="{{ $leads->currentPage() }}">
                                                <button type="submit">Buat link pembayaran</button>
                                            </form>
                                        @endif
                                        <a href="{{ $lead->whatsapp_url }}" target="_blank" rel="noopener noreferrer" aria-label="Hubungi {{ $lead->full_name }} melalui WhatsApp (tab baru)">Hubungi WhatsApp <span aria-hidden="true">↗</span></a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if ($leads->hasPages())<div class="recruitment-leads-pagination">{{ $leads->links() }}</div>@endif
            @else
                <div class="recruitment-leads-empty">
                    <span class="recruitment-leads-empty-icon"><x-recruitment-icon name="people" /></span>
                    <h2>{{ $search !== '' ? 'Peminat belum ditemukan' : 'Belum ada pendaftaran masuk' }}</h2>
                    <p>{{ $search !== '' ? 'Coba nama, nomor WhatsApp, atau domisili lain, atau tampilkan semua pendaftar Anda.' : 'Bagikan landing page Anda. Setiap form minat yang berhasil dikirim akan tersimpan di sini.' }}</p>
                    <a href="{{ $search !== '' ? route('subscriber-recruitment.leads') : route('subscriber-recruitment.index') }}">{{ $search !== '' ? 'Tampilkan Semua Peminat' : 'Kelola Landing Page' }} <span aria-hidden="true">→</span></a>
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>
