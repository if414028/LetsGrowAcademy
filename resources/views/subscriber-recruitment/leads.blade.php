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
                <div class="recruitment-leads-columns" aria-hidden="true"><span>Peminat</span><span>Data form</span><span>Asal pendaftaran</span><span>Terdaftar</span></div>
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
                                <a href="{{ $lead->whatsapp_url }}" target="_blank" rel="noopener" aria-label="Hubungi {{ $lead->full_name }} melalui WhatsApp (tab baru)">Hubungi WhatsApp <span aria-hidden="true">↗</span></a>
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
