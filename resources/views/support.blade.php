<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $supportProducts = [
        [
            'name' => 'Water Purifier',
            'image' => asset('images/coway-water-purifier-core-slim.jpg'),
            'color' => '#0069a8',
            'models' => ['SLIM STAND CHP-5730R', 'VILLAEM III CHP-7320R', 'KRISTAL ICE CHPI-7520L', 'CINNAMON P-6320R', 'NEO PLUS CHP-264L', 'OMBAK CHP-7310R', 'VILLAEM II CHP-18AR', 'CORE CHP-671R'],
            'guide' => 'Panduan penggunaan, penggantian filter, dan catatan perawatan water purifier untuk percakapan pelanggan.',
        ],
        [
            'name' => 'Air Purifier',
            'image' => asset('images/coway-air-purifier-studio-suite.jpg'),
            'color' => '#6eb291',
            'models' => ['STORM AP-1516D', 'LOMBOK AP-1520C', 'CARTRIDGE AP-1019C', 'NEXT STORM AP-2025A', 'NOBLE 2 AP-2023K', 'SQUAREFIT AP-1125G', 'SQUAREBIG AP-2425H'],
            'guide' => 'Informasi filter, penempatan unit, kualitas udara, dan poin edukasi untuk pengguna air purifier.',
        ],
        [
            'name' => 'Outdoor',
            'image' => asset('images/coway-outdoor-water-filter.jpg'),
            'color' => '#0d4580',
            'models' => ['OUTDOOR FILTER POE-23A'],
            'guide' => 'Ringkasan fungsi outdoor filter, alur air rumah, dan bahan penjelasan untuk kebutuhan instalasi.',
        ],
    ];

    $supportActions = [
        ['title' => 'Panduan Produk', 'copy' => 'Akses model, kategori, dan poin teknis utama sebelum menjawab pertanyaan pelanggan.'],
        ['title' => 'Layanan After Sales', 'copy' => 'Arahkan pelanggan ke HEART Service dan CODY Services untuk perawatan berkala.'],
        ['title' => 'Materi Sales', 'copy' => 'Gunakan halaman ini sebagai pusat referensi cepat saat follow up pelanggan.'],
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Halaman dukungan produk Coway untuk panduan, informasi teknis, dan referensi cepat tim penjualan.">
    <title>Dukungan | Coway</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative min-h-[620px] overflow-hidden pt-[72px]">
            <div class="absolute inset-0">
                <img src="{{ asset('images/coway-support-hero-main-b1.png') }}" alt="Coway Support" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-white/20"></div>
            </div>
            <div class="relative mx-auto flex min-h-[548px] max-w-[1440px] items-center px-5 py-20 sm:px-8 xl:px-24">
                <div class="max-w-2xl text-slate-800">
                    <p class="text-sm font-bold uppercase tracking-[0.35em] text-[#00a4e4]">Dukungan Coway</p>
                    <h1 class="mt-5 text-5xl font-bold leading-tight sm:text-7xl">Selamat Datang di Coway Support</h1>
                    <p class="mt-6 text-xl text-slate-600">Kami selalu siap membantu tim menjawab kebutuhan pelanggan.</p>
                    <a href="#pilih-produk" class="mt-9 inline-flex h-12 items-center justify-center rounded-full border border-[#00a4e4] bg-[#00a4e4] px-8 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008ac4]">Mulai Sekarang</a>
                </div>
            </div>
        </section>

        <section id="pilih-produk" class="bg-white py-20">
            <div class="mx-auto max-w-[1180px] px-5 text-center sm:px-8">
                <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">Pilih Produk</p>
                <h2 class="mt-3 text-4xl font-bold text-slate-950 sm:text-5xl">Produk apa yang ingin dibantu?</h2>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-600">Temukan jawaban dan materi dukungan untuk kategori produk Coway yang tersedia saat ini.</p>

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($supportProducts as $product)
                        <article class="overflow-hidden rounded bg-white text-left shadow-xl shadow-sky-100 ring-1 ring-slate-200">
                            <div class="flex h-56 items-center justify-center p-6" style="background-color: {{ $product['color'] }}">
                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="max-h-44 object-contain">
                            </div>
                            <div class="p-7">
                                <h3 class="text-2xl font-bold text-slate-950">{{ $product['name'] }}</h3>
                                <p class="mt-4 leading-7 text-slate-600">{{ $product['guide'] }}</p>
                                <p class="mt-6 text-xs font-extrabold uppercase tracking-[0.18em] text-[#00a4e4]">Model Tersedia</p>
                                <ul class="mt-3 space-y-1.5 text-sm leading-6 text-slate-600">
                                    @foreach ($product['models'] as $model)
                                        <li>{{ $model }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-20">
            <div class="mx-auto max-w-[1180px] px-5 sm:px-8">
                <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">Bantuan Teknis</p>
                        <h2 class="mt-3 text-4xl font-bold text-slate-950 sm:text-5xl">Pusat jawaban untuk kebutuhan pelanggan Coway.</h2>
                        <p class="mt-5 text-lg leading-8 text-slate-600">Struktur halaman ini mengikuti referensi Coway Malaysia Support: mulai dari pemilihan produk, panduan teknis, hingga akses layanan pendukung.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-3">
                        @foreach ($supportActions as $action)
                            <article class="rounded bg-white p-6 ring-1 ring-slate-200">
                                <div class="mb-5 h-2 w-16 rounded-full bg-[#00a4e4]"></div>
                                <h3 class="text-xl font-bold text-slate-950">{{ $action['title'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $action['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#7dacd3] py-20 text-white">
            <div class="mx-auto grid max-w-[1180px] gap-8 px-5 sm:px-8 lg:grid-cols-[1fr_0.8fr] lg:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-50">Layanan Coway</p>
                    <h2 class="mt-3 text-4xl font-bold sm:text-5xl">Butuh dukungan layanan pelanggan?</h2>
                    <p class="mt-5 text-lg leading-8 text-sky-50">Gunakan halaman service lokal untuk menjelaskan perawatan rutin dan pendampingan pelanggan setelah produk terpasang.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('heart-service') }}" class="rounded bg-white p-6 text-slate-900 transition hover:-translate-y-1 hover:shadow-xl">
                        <p class="text-xl font-bold">HEART Service</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Layanan aftersales dan kunjungan rutin pelanggan.</p>
                    </a>
                    <a href="{{ route('cody-services') }}" class="rounded bg-white p-6 text-slate-900 transition hover:-translate-y-1 hover:shadow-xl">
                        <p class="text-xl font-bold">CODY Services</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Pendampingan pelanggan oleh representatif Coway.</p>
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
