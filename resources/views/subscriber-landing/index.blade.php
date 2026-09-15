<x-dashboard-layout>
    <div class="mx-auto max-w-6xl" x-data="{ copied: false }">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-10 p-6 sm:p-8 lg:grid-cols-[1.05fr_.95fr] lg:p-10">
                <div class="flex flex-col justify-center">
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                        Subscriber only
                    </span>
                    <p class="mt-6 text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Alat bantu penjualan</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Landing Page Produk Anda</h1>
                    <p class="mt-4 max-w-xl text-sm font-medium leading-7 text-slate-600 sm:text-base">
                        Sebagai subscriber, Anda memiliki landing page produk personal yang dapat dibagikan kepada calon customer. Semua tombol konsultasi di halaman tersebut langsung terhubung ke nomor WhatsApp Anda.
                    </p>

                    <div class="mt-7 grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            ['Bagikan link', 'Kirim melalui WhatsApp atau media sosial.'],
                            ['Edukasi customer', 'Produk, manfaat, harga, dan FAQ tersedia.'],
                            ['Terima konsultasi', 'CTA masuk langsung ke WhatsApp Anda.'],
                        ] as [$title, $description])
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <svg class="h-5 w-5 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                                <p class="mt-3 text-sm font-extrabold text-slate-900">{{ $title }}</p>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-6 ring-1 ring-sky-100 sm:p-8">
                    <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-sky-200/60 blur-3xl"></div>
                    <div class="relative rounded-2xl border border-white bg-white/90 p-5 shadow-xl shadow-sky-200/40">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-6 w-auto">
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700">Aktif</span>
                        </div>
                        <div class="pt-5 text-center">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-sky-600">Air murni untuk keluarga</p>
                            <h2 class="mx-auto mt-2 max-w-xs text-xl font-black leading-tight text-slate-950">Solusi Cerdas Air Minum Tanpa Repot Angkat Galon</h2>
                            <div class="mt-5 flex h-40 items-end justify-center gap-2">
                                <img src="{{ asset('images/water-neo-plus-chp-264l.webp') }}" alt="" class="max-h-32 w-auto object-contain" aria-hidden="true">
                                <img src="{{ asset('images/water-ombak-chp-7310r.webp') }}" alt="" class="max-h-40 w-auto object-contain" aria-hidden="true">
                            </div>
                            <span class="mt-5 inline-flex min-h-9 items-center justify-center rounded-lg bg-sky-500 px-5 text-xs font-extrabold text-white">Konsultasi Sekarang</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($canPublish)
                <div class="border-t border-slate-200 p-6 sm:p-8 lg:px-10">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Preview</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-slate-950">Tampilan Landing Page Produk</h2>
                            <p class="mt-2 text-sm font-medium text-slate-500">Preview ini menampilkan halaman yang akan dilihat calon customer Anda.</p>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Halaman aktif</span>
                    </div>
                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-inner">
                        <div class="flex h-10 items-center gap-2 border-b border-slate-200 bg-white px-4" aria-hidden="true">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span class="ml-3 min-w-0 flex-1 truncate rounded-md bg-slate-100 px-3 py-1 text-[10px] font-semibold text-slate-500">{{ $landingPageUrl }}</span>
                        </div>
                        <iframe src="{{ $landingPageUrl }}" title="Preview Landing Page Produk {{ $consultant->name }}" loading="lazy" class="h-[560px] w-full bg-white sm:h-[680px]"></iframe>
                    </div>
                </div>
            @endif

            <div class="border-t border-slate-200 bg-slate-50 p-6 sm:p-8 lg:px-10">
                @if ($canPublish)
                    <label for="product-landing-url" class="text-sm font-extrabold text-slate-900">Link Landing Page Produk Anda</label>
                    <p class="mt-1 text-xs font-medium text-slate-500">Salin dan bagikan link ini kepada calon customer.</p>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <input id="product-landing-url" type="text" readonly value="{{ $landingPageUrl }}" class="min-h-12 min-w-0 flex-1 rounded-xl border-slate-200 bg-white text-sm font-semibold text-slate-700 focus:border-sky-500 focus:ring-sky-500">
                        <button type="button"
                            @click="navigator.clipboard.writeText(@js($landingPageUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-sky-200 bg-white px-5 text-sm font-extrabold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <span x-text="copied ? 'Tersalin' : 'Salin Link'">Salin Link</span>
                        </button>
                        <a href="{{ $landingPageUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-extrabold text-white transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                            Lihat Landing Page
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 3h7v7m0-7-9 9"/><path d="M10 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/></svg>
                        </a>
                    </div>
                @else
                    <div class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-extrabold text-amber-900">Lengkapi nomor WhatsApp terlebih dahulu</p><p class="mt-1 text-sm font-medium text-amber-800/80">Landing page akan aktif setelah nomor WhatsApp ditambahkan ke profil Anda.</p></div>
                        <a href="{{ route('profile') }}" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-xl bg-amber-500 px-5 text-sm font-extrabold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500">Lengkapi Profil</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dashboard-layout>
