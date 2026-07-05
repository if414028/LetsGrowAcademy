<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="HEART Service dari Coway menyediakan kunjungan rutin dan pengelolaan produk agar dapat digunakan lebih bersih dan lebih lama.">
    <title>HEART Service | Coway</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative overflow-hidden pt-[72px]">
            <div class="absolute inset-0 bg-gradient-to-br from-[#eff8e7] via-white to-[#d8f0f8]"></div>
            <div class="relative mx-auto grid min-h-[calc(100vh-72px)] max-w-[1440px] items-center gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[0.95fr_1.05fr] xl:px-24">
                <div>
                    <p class="text-sm font-bold tracking-[0.2em] text-[#00a4e4]">#ChangeYourLife</p>
                    <h1 class="mt-6 text-6xl font-bold leading-none tracking-[0.1em] text-slate-700 sm:text-7xl">HEART</h1>
                    <p class="mt-4 text-2xl font-bold text-[#00a4e4]">Service</p>
                    <h2 class="mt-8 text-3xl font-bold leading-tight text-slate-700 sm:text-5xl">Layanan aftersales terbaik dari Coway</h2>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">HEART Service dari Coway menyediakan kunjungan kepada pelanggan secara rutin dan mengelola produk Coway agar dapat digunakan lebih bersih dan lebih lama.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#heart-service" class="inline-flex h-12 items-center justify-center rounded-full bg-[#00a4e4] px-8 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008ac4]">Pelajari Layanan</a>
                        <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center rounded-full border border-slate-400 px-8 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:border-[#00a4e4] hover:text-[#00a4e4]">Masuk Admin/Sales</a>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-10 rounded-full bg-sky-200/40 blur-3xl"></div>
                    <img src="https://cowayjkt.id/wp-content/uploads/2023/12/heart-service-hero.webp" alt="Coway HEART Service" class="relative mx-auto max-h-[620px] w-full max-w-xl object-contain">
                </div>
            </div>
        </section>

        <section id="heart-service" class="bg-white py-20">
            <div class="mx-auto max-w-[1180px] px-5 sm:px-8">
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">HEART Service</p>
                    <h2 class="mt-3 text-4xl font-bold text-slate-900 sm:text-5xl">Perawatan rutin untuk produk Coway pelanggan.</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-4">
                    @foreach ([
                        ['title' => 'Kunjungan rutin', 'copy' => 'Tim layanan melakukan kunjungan sesuai jadwal agar kondisi produk tetap terpantau.'],
                        ['title' => 'Produk lebih bersih', 'copy' => 'Perawatan membantu menjaga kebersihan produk Coway yang digunakan pelanggan.'],
                        ['title' => 'Pemakaian lebih lama', 'copy' => 'Pemeriksaan berkala membantu produk berfungsi optimal dalam jangka panjang.'],
                        ['title' => 'Dukungan aftersales', 'copy' => 'Tenaga penjualan dapat menjelaskan layanan pendukung setelah produk terpasang.'],
                    ] as $item)
                        <article class="rounded bg-slate-50 p-7 ring-1 ring-slate-200">
                            <div class="mb-5 h-2 w-16 rounded-full bg-[#00a4e4]"></div>
                            <h3 class="text-xl font-bold text-slate-950">{{ $item['title'] }}</h3>
                            <p class="mt-3 leading-7 text-slate-600">{{ $item['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-[#7dacd3] py-20 text-white">
            <div class="mx-auto grid max-w-[1180px] gap-10 px-5 sm:px-8 lg:grid-cols-[1fr_0.85fr] lg:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-50">Video Layanan</p>
                    <h2 class="mt-3 text-4xl font-bold sm:text-5xl">Kenalkan HEART Service kepada pelanggan.</h2>
                    <p class="mt-5 text-lg leading-8 text-sky-50">Materi layanan ini membantu pelanggan memahami nilai perawatan rutin setelah memilih produk Coway.</p>
                </div>
                <a href="https://www.youtube.com/watch?v=aaJ1p1FXy5Q" target="_blank" rel="noopener" class="group block overflow-hidden rounded bg-white/15 shadow-2xl ring-1 ring-white/25">
                    <div class="relative aspect-video bg-slate-900">
                        <img src="https://i.ytimg.com/vi/aaJ1p1FXy5Q/hqdefault.jpg" alt="Thumbnail video HEART Service Coway" class="h-full w-full object-cover opacity-90 transition duration-300 group-hover:scale-105 group-hover:opacity-100">
                        <div class="absolute inset-0 bg-slate-950/25"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-[#00a4e4] shadow-2xl transition duration-300 group-hover:scale-110">
                                <svg class="ml-1 h-9 w-9" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </div>
                    </div>
                    <span class="block bg-white/10 px-5 py-3 text-center text-sm font-bold uppercase tracking-wide text-white transition group-hover:bg-white/20">
                        Tonton Video HEART Service
                    </span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
