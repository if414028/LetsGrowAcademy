<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Subscription Sales Coway untuk mengakses Selling Kit premium dan landing page produk personal yang mendukung aktivitas penjualan.">
    <title>Subscription Sales | Coway</title>
    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $canSubscribe = auth()->check() && auth()->user()->hasAnyRole(['Health Planner', 'Health Manager', 'Sales Manager']);
@endphp
<body class="public-cover bg-white font-sans text-slate-900 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main id="main-content" tabindex="-1">
        <section class="relative overflow-hidden bg-slate-950 pt-[72px] text-white">
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -right-32 top-12 h-96 w-96 rounded-full bg-sky-500/20 blur-3xl"></div>
                <div class="absolute -left-24 bottom-0 h-72 w-72 rounded-full bg-cyan-300/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.06]" style="background-image:radial-gradient(circle at 1px 1px,white 1px,transparent 0);background-size:28px 28px"></div>
            </div>
            <div class="relative mx-auto grid max-w-[1440px] gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[1.1fr_.9fr] lg:items-center lg:py-28 xl:px-24">
                <div>
                    <span class="inline-flex items-center rounded-full border border-sky-300/30 bg-sky-400/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.2em] text-sky-200">Subscription khusus sales</span>
                    <h1 class="mt-7 max-w-3xl text-4xl font-black leading-[1.08] tracking-tight sm:text-6xl">Materi penjualan siap pakai, dalam satu akses.</h1>
                    <p class="mt-6 max-w-2xl text-lg font-medium leading-8 text-slate-300">Subscription Sales membantu sales Coway menjelaskan produk dengan lebih profesional melalui koleksi materi premium dan landing page produk personal yang mudah dibagikan.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        @if ($canSubscribe)
                            <a href="{{ route('subscriptions.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-sky-500 px-7 text-sm font-extrabold text-white transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-slate-950">Pilih Paket Subscription</a>
                        @elseif (auth()->guest())
                            <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-sky-500 px-7 text-sm font-extrabold text-white transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-slate-950">Login untuk Subscribe</a>
                        @endif
                        <a href="#benefit" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 px-7 text-sm font-extrabold text-white transition hover:border-white/50 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white">Pelajari Keuntungan</a>
                    </div>
                    @guest<p class="mt-4 text-sm font-semibold text-slate-400">Belum punya akun sales? Lihat cara mendaftar di bagian bawah halaman.</p>@endguest
                </div>

                <div class="relative mx-auto w-full max-w-lg">
                    <div class="rounded-[2rem] border border-white/10 bg-white/10 p-3 shadow-2xl shadow-sky-950/60 backdrop-blur">
                        <div class="rounded-[1.5rem] bg-white p-6 text-slate-900 sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div><p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Akses Sales Pro</p><h2 class="mt-2 text-2xl font-black">Subscription Sales</h2></div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Akses digital</span>
                            </div>
                            <div class="mt-7 flex items-end gap-2 border-b border-slate-100 pb-7"><span class="text-4xl font-black">Rp {{ number_format($monthlyPrice, 0, ',', '.') }}</span><span class="pb-1 font-bold text-slate-500">/bulan</span></div>
                            <ul class="mt-7 space-y-4 text-sm font-bold text-slate-700">
                                @foreach (['Selling Kit dan materi premium', 'Landing page produk personal', 'Tombol konsultasi ke WhatsApp sales', 'Akses selama periode subscription aktif'] as $benefit)
                                    <li class="flex gap-3"><svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>{{ $benefit }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-7 rounded-xl bg-sky-50 px-4 py-3 text-sm font-bold leading-6 text-sky-900">Tersedia dalam pilihan {{ implode(', ', $durations) }} bulan. Total pembayaran dihitung sesuai durasi yang dipilih.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="benefit" class="bg-slate-50 py-20 sm:py-24">
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 xl:px-24">
                <div class="max-w-3xl"><p class="text-sm font-extrabold uppercase tracking-[0.22em] text-sky-600">Keuntungan subscription</p><h2 class="mt-3 text-3xl font-black tracking-tight sm:text-5xl">Lebih siap di setiap percakapan penjualan.</h2><p class="mt-5 text-lg leading-8 text-slate-600">Akses digital dirancang untuk membantu aktivitas edukasi produk, follow up, dan konsultasi sales kepada calon pelanggan.</p></div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['folder', 'Selling Kit premium', 'Gunakan katalog, materi edukasi produk, panduan penjualan, dan materi rekrutmen yang tersusun dalam satu galeri.'],
                        ['window', 'Landing page personal', 'Bagikan halaman produk dengan identitas sales dan tombol konsultasi yang langsung terhubung ke WhatsApp Anda.'],
                        ['spark', 'Presentasi lebih profesional', 'Sampaikan manfaat produk dengan materi yang konsisten, mudah dipahami, dan siap dibagikan kapan saja.'],
                    ] as [$icon, $title, $copy])
                        <article class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-100/70">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                                @if ($icon === 'folder')<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>@elseif($icon === 'window')<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 6.5h.01M10 6.5h.01"/></svg>@else<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3Z"/><path d="m19 16 .7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z"/></svg>@endif
                            </div>
                            <h3 class="mt-6 text-xl font-black">{{ $title }}</h3><p class="mt-3 leading-7 text-slate-600">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-20 sm:py-24">
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 xl:px-24">
                <div class="grid gap-12 lg:grid-cols-[.9fr_1.1fr] lg:items-start">
                    <div class="lg:sticky lg:top-28"><p class="text-sm font-extrabold uppercase tracking-[0.22em] text-sky-600">Cara berlangganan</p><h2 class="mt-3 text-3xl font-black tracking-tight sm:text-5xl">Mulai dalam tiga langkah sederhana.</h2><p class="mt-5 text-lg leading-8 text-slate-600">Subscription hanya dapat dibeli oleh sales yang sudah memiliki akun aktif.</p></div>
                    <ol class="space-y-4">
                        @foreach ([['Login ke akun sales', 'Masuk menggunakan akun sales Anda untuk membuka halaman subscription.'], ['Pilih durasi akses', 'Pilih periode 3, 6, atau 12 bulan dan periksa total pembayaran.'], ['Selesaikan pembayaran', 'Pilih metode pembayaran yang tersedia. Akses akan aktif setelah pembayaran berhasil terkonfirmasi.']] as $index => [$title, $copy])
                            <li class="flex gap-5 rounded-2xl border border-slate-200 p-6"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-black text-white">{{ $index + 1 }}</span><div><h3 class="text-lg font-black">{{ $title }}</h3><p class="mt-2 leading-7 text-slate-600">{{ $copy }}</p></div></li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        <section class="bg-sky-600 py-16 text-white sm:py-20">
            <div class="mx-auto grid max-w-[1440px] gap-8 px-5 sm:px-8 lg:grid-cols-[1fr_auto] lg:items-center xl:px-24">
                <div><p class="text-sm font-extrabold uppercase tracking-[0.22em] text-sky-100">Siap meningkatkan penjualan?</p><h2 class="mt-3 max-w-3xl text-3xl font-black tracking-tight sm:text-4xl">Aktifkan akses materi dan halaman produk personal Anda.</h2></div>
                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    @if ($canSubscribe)<a href="{{ route('subscriptions.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-7 text-sm font-extrabold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-sky-600">Pilih Paket Subscription</a>@elseif (auth()->guest())<a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-7 text-sm font-extrabold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-sky-600">Login untuk Subscribe</a>@endif
                    <a href="https://wa.me/628886547788?text={{ urlencode('Halo, saya ingin mendaftar sebagai sales Coway dan membuat akun.') }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/50 px-7 text-sm font-extrabold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white">Belum punya akun? Daftar via WhatsApp</a>
                </div>
            </div>
        </section>
    </main>

    @include('partials.public-footer')
</body>
</html>
