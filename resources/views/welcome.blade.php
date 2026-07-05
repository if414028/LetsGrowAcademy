<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $productCategories = [
        [
            'name' => 'Air Purifier',
            'href' => route('public.products.air-purifier'),
            'image' => asset('images/coway-air-purifier-studio-suite.jpg'),
            'gradient' => 'from-cyan-300 via-sky-500 to-emerald-300',
            'items' => ['STORM AP-1516D', 'LOMBOK AP-1520C', 'CARTRIDGE AP-1019C', 'NEXT STORM AP-2025A', 'NOBLE 2 AP-2023K', 'SQUAREFIT AP-1125G', 'SQUAREBIG AP-2425H'],
        ],
        [
            'name' => 'Water Purifier',
            'href' => '#water-purifier',
            'image' => asset('images/coway-water-purifier-core-slim.jpg'),
            'gradient' => 'from-cyan-400 via-sky-500 to-blue-900',
            'items' => ['SLIM STAND CHP-5730R', 'VILLAEM III CHP-7320R', 'KRISTAL ICE CHPI-7520L', 'CINNAMON P-6320R', 'NEO PLUS CHP-264L', 'OMBAK CHP-7310R', 'VILLAEM II CHP-18AR', 'CORE CHP-671R'],
        ],
        [
            'name' => 'Outdoor',
            'href' => '#outdoor',
            'image' => asset('images/coway-outdoor-water-filter.jpg'),
            'gradient' => 'from-cyan-400 via-sky-600 to-teal-700',
            'items' => ['OUTDOOR FILTER POE-23A'],
        ],
    ];

    $services = [
        ['name' => 'HEART Service', 'href' => route('heart-service')],
        ['name' => 'CODY Services', 'href' => route('cody-services')],
    ];

    $aboutCoway = array_values(config('coway_public.about_pages', []));
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Katalog penjualan produk Coway untuk membantu pelanggan memilih water purifier, air purifier, dan outdoor filter yang tepat.">
    <title>Coway | Katalog Penjualan Produk</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8 xl:px-24">
            <a href="/" class="flex items-center gap-3" aria-label="Beranda Coway">
                <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-8 w-auto object-contain">
            </a>

            <nav class="hidden h-full items-center text-[13px] font-bold uppercase tracking-wide text-slate-700 lg:flex" aria-label="Navigasi utama">
                <a href="#change-your-life" class="flex h-full items-center px-4 transition hover:bg-sky-100 hover:text-sky-600">Ubah Hidupmu</a>
                <div class="group relative flex h-full items-center">
                    <a href="#products" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
                        Produk
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </a>
                    <div class="invisible absolute left-1/2 top-full w-[760px] -translate-x-1/2 translate-y-2 bg-[#34a9d7] p-7 text-white opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                        <div class="grid grid-cols-3 gap-7">
                            @foreach ($productCategories as $category)
                                <a href="{{ $category['href'] }}" class="block">
                                    <div class="mb-4 flex h-32 items-center justify-center overflow-hidden rounded bg-white/15">
                                        <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" class="h-full w-full object-contain p-3" loading="lazy">
                                    </div>
                                    <h3 class="border-b border-white/70 pb-3 text-base font-bold">{{ $category['name'] }}</h3>
                                    <ul class="mt-3 space-y-1.5 text-sm normal-case leading-6 tracking-normal text-sky-50">
                                        @foreach ($category['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="group relative flex h-full items-center">
                    <a href="#services" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
                        Services
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </a>
                    <div class="invisible absolute left-1/2 top-full w-56 -translate-x-1/2 translate-y-2 bg-[#34a9d7] py-3 text-white opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                        @foreach ($services as $service)
                            <a href="{{ $service['href'] }}" class="block px-6 py-3 text-sm font-bold normal-case tracking-normal transition hover:bg-white/15">{{ $service['name'] }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="group relative flex h-full items-center">
                    <a href="#about-coway" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
                        About Coway
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </a>
                    <div class="invisible absolute left-1/2 top-full w-80 -translate-x-1/2 translate-y-2 bg-[#34a9d7] py-3 text-white opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                        @foreach ($aboutCoway as $item)
                            <a href="{{ route('about-coway.show', $item['slug']) }}" class="block px-6 py-3 text-sm font-bold normal-case tracking-normal transition hover:bg-white/15">{{ $item['nav'] }}</a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('support') }}" class="flex h-full items-center px-4 transition hover:bg-sky-100 hover:text-sky-600">Dukungan</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="inline-flex h-10 items-center justify-center rounded-full border border-sky-500 bg-sky-500 px-6 text-sm font-bold text-white transition hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">
                    Masuk Admin/Sales
                </a>
                <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-700 lg:hidden" aria-label="Buka menu" x-data x-on:click="$dispatch('toggle-cover-menu')">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div x-data="{ open: false, productOpen: false, serviceOpen: false, aboutOpen: false }" x-on:toggle-cover-menu.window="open = !open" x-show="open" x-cloak class="border-t border-slate-100 bg-white px-5 py-5 shadow-xl lg:hidden">
            <nav class="mx-auto grid max-w-7xl gap-4 text-sm font-bold uppercase tracking-wide text-slate-700" aria-label="Navigasi mobile">
                <a href="#change-your-life" x-on:click="open = false">Ubah Hidupmu</a>
                <div>
                    <button type="button" class="flex w-full items-center justify-between text-left uppercase" x-on:click="productOpen = !productOpen" x-bind:aria-expanded="productOpen.toString()" aria-controls="mobile-products-menu">
                        <span>Produk</span>
                        <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': productOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="mobile-products-menu" x-show="productOpen" class="mt-4 overflow-hidden rounded bg-[#34a9d7] p-4 text-white shadow-inner">
                        <div class="space-y-5">
                            @foreach ($productCategories as $category)
                                <a href="{{ $category['href'] }}" x-on:click="open = false" class="block">
                                    <div class="flex items-center gap-3">
                                        <div class="h-16 w-24 shrink-0 overflow-hidden rounded bg-white/15">
                                            <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" class="h-full w-full object-cover" loading="lazy">
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold">{{ $category['name'] }}</h3>
                                            <p class="mt-1 text-xs font-semibold normal-case tracking-normal text-sky-50">{{ count($category['items']) }} model tersedia</p>
                                        </div>
                                    </div>
                                    <ul class="mt-3 space-y-1 pl-1 text-xs font-semibold normal-case leading-5 tracking-normal text-sky-50">
                                        @foreach ($category['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <button type="button" class="flex w-full items-center justify-between text-left uppercase" x-on:click="serviceOpen = !serviceOpen" x-bind:aria-expanded="serviceOpen.toString()" aria-controls="mobile-services-menu">
                        <span>Services</span>
                        <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': serviceOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="mobile-services-menu" x-show="serviceOpen" class="mt-4 overflow-hidden rounded bg-[#34a9d7] py-2 text-white shadow-inner">
                        @foreach ($services as $service)
                            <a href="{{ $service['href'] }}" x-on:click="open = false" class="block px-4 py-3 text-sm font-bold normal-case tracking-normal text-sky-50">{{ $service['name'] }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <button type="button" class="flex w-full items-center justify-between text-left uppercase" x-on:click="aboutOpen = !aboutOpen" x-bind:aria-expanded="aboutOpen.toString()" aria-controls="mobile-about-menu">
                        <span>About Coway</span>
                        <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': aboutOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div id="mobile-about-menu" x-show="aboutOpen" class="mt-4 overflow-hidden rounded bg-[#34a9d7] py-2 text-white shadow-inner">
                        @foreach ($aboutCoway as $item)
                            <a href="{{ route('about-coway.show', $item['slug']) }}" x-on:click="open = false" class="block px-4 py-3 text-sm font-bold normal-case tracking-normal text-sky-50">{{ $item['nav'] }}</a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('support') }}" x-on:click="open = false">Dukungan</a>
            </nav>
        </div>
    </header>

    <main>
        <section id="change-your-life" class="relative min-h-screen overflow-hidden pt-[72px]">
            <div class="absolute inset-0">
                <img src="{{ asset('images/coway-product-hero-2026.webp') }}" alt="Rangkaian produk Coway" class="h-full w-full object-cover object-[58%_bottom]">
                <div class="absolute inset-0 bg-gradient-to-r from-white via-white/80 to-white/5"></div>
                <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-white via-white/55 to-transparent"></div>
            </div>
            <div class="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-[1440px] items-center px-5 py-14 sm:px-8 xl:px-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold tracking-[0.2em] text-[#00a4e4]">#ChangeYourLife</p>
                    <p class="mt-3 text-sm font-bold uppercase tracking-[0.32em] text-[#00a4e4]">Pure Way of Life</p>
                    <h1 class="mt-8 text-5xl font-bold leading-[1.03] tracking-[0.08em] text-slate-700 sm:text-6xl">PRODUK COWAY</h1>
                    <p class="mt-5 text-lg font-bold uppercase tracking-wide text-slate-600">Mulai harimu dengan pilihan yang tepat</p>
                    <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-600">Temukan pilihan produk Coway untuk kebutuhan rumah dan keluarga, mulai dari pemurni air, pemurni udara, hingga outdoor filter dengan layanan purna jual yang mendukung kenyamanan pelanggan.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#products" class="inline-flex h-12 items-center justify-center rounded-full bg-[#00a4e4] px-8 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-sky-300/40 transition hover:bg-[#008ac4] focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">Lihat Produk</a>
                        <a href="#services" class="inline-flex h-12 items-center justify-center rounded-full border border-slate-400 bg-white/60 px-8 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:border-[#00a4e4] hover:text-[#00a4e4]">Lihat Layanan</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="triple-benefit" class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-[1440px] px-5 text-center sm:px-8 xl:px-24">
                <p class="text-4xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-5xl">Triple Benefit</p>
                <p class="mt-5 text-lg font-bold text-slate-600">Produk Berkualitas, Keuntungan Berlipat</p>

                <div class="mt-20 grid gap-14 md:grid-cols-3 md:gap-10">
                    <div class="flex flex-col items-center">
                        <div class="flex h-24 items-center justify-center">
                            <img src="{{ asset('images/free-shipping.webp') }}" alt="Gratis Ongkir" class="h-24 w-24 object-contain" loading="lazy">
                        </div>
                        <h2 class="mt-8 text-2xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-3xl">Gratis Ongkir</h2>
                        <p class="mt-3 max-w-sm text-base font-semibold leading-7 text-slate-600">Gratis ongkos kirim untuk daerah Jawa, Sumatera, Kalimantan Barat, &amp; Kalimantan Tengah **</p>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="flex h-24 items-center justify-center">
                            <img src="{{ asset('images/free-installation.webp') }}" alt="Gratis Instalasi" class="h-24 w-24 object-contain" loading="lazy">
                        </div>
                        <h2 class="mt-8 text-2xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-3xl">Gratis Instalasi</h2>
                        <p class="mt-3 max-w-sm text-base font-semibold leading-7 text-slate-600">Instalasi dilakukan oleh Coway Technician yang terlatih &amp; bersertifikat.</p>
                    </div>

                    <div class="flex flex-col items-center">
                        <div class="flex h-24 items-center justify-center">
                            <img src="{{ asset('images/gratis-service-icon.webp') }}" alt="Gratis Service" class="h-24 w-28 object-contain" loading="lazy">
                        </div>
                        <h2 class="mt-8 text-2xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-3xl">Gratis Service</h2>
                        <p class="mt-3 max-w-sm text-base font-semibold leading-7 text-slate-600">Gratis HEART Service secara berkala oleh Coway Lady setiap 2 bulan.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="leading-world" class="relative flex min-h-screen items-center overflow-hidden bg-[#9fc7d7] py-24 text-white sm:py-28">
            <img src="{{ asset('images/world-map-silhouette.webp') }}" alt="" class="pointer-events-none absolute -bottom-44 -left-40 h-[520px] w-[520px] object-contain opacity-35 brightness-150 saturate-0 mix-blend-screen sm:-bottom-56 sm:-left-48 sm:h-[720px] sm:w-[720px]" aria-hidden="true">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0)_42%)]"></div>

            <div class="relative mx-auto w-full max-w-[1440px] px-5 text-center sm:px-8 xl:px-24">
                <p class="text-4xl font-bold uppercase tracking-[0.08em] sm:text-5xl">Memimpin Dunia</p>
                <p class="mt-4 text-xl font-bold text-white/95">Live Pure Together with Us.</p>

                <div class="mt-20 grid gap-12 sm:grid-cols-2 lg:grid-cols-5 lg:gap-8">
                    <div>
                        <p class="text-5xl font-bold leading-none tracking-tight sm:text-6xl">1 JUTA</p>
                        <p class="mt-4 text-lg font-bold text-white/90">Pengujian</p>
                    </div>
                    <div>
                        <p class="text-5xl font-bold leading-none tracking-tight sm:text-6xl">451</p>
                        <p class="mt-4 text-lg font-bold text-white/90">Peneliti</p>
                    </div>
                    <div>
                        <p class="text-5xl font-bold leading-none tracking-tight sm:text-6xl">10 JUTA</p>
                        <p class="mt-4 text-lg font-bold text-white/90">Rumah di Dunia</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold uppercase leading-tight tracking-[0.04em] sm:text-4xl">Pusat R&amp;D<br>Terbesar</p>
                        <p class="mt-4 text-lg font-bold text-white/90">di Dunia</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold uppercase leading-tight tracking-[0.04em] sm:text-4xl">WQA<br>Certified</p>
                        <p class="mt-4 text-lg font-bold text-white/90">Water Test Lab</p>
                    </div>
                </div>

                <div class="mt-20 flex flex-wrap items-center justify-center gap-5 sm:gap-7">
                    <img src="{{ asset('images/halal-mui-seeklogo.webp') }}" alt="Halal MUI" class="h-16 w-16 object-contain" loading="lazy">
                    <img src="{{ asset('images/rohs-compliant-seeklogo.webp') }}" alt="RoHS Compliant" class="h-16 w-16 object-contain" loading="lazy">
                    <img src="{{ asset('images/wqa-certified-logo.webp') }}" alt="Water Quality Association Tested and Certified" class="h-16 w-16 object-contain" loading="lazy">
                    <img src="{{ asset('images/tuv-sud-seeklogo.webp') }}" alt="TUV SUD" class="h-16 w-16 object-contain" loading="lazy">
                </div>
            </div>
        </section>

        <section id="market-share-preview" class="relative flex min-h-screen items-center overflow-hidden bg-slate-100 pt-[72px]">
            <div class="absolute inset-0">
                <img src="{{ asset('images/busy-new-yorkers-scaled.jpeg') }}" alt="Jutaan pelanggan Coway" class="h-full w-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-r from-white via-white/88 to-white/20"></div>
                <div class="absolute inset-y-0 left-0 w-2/3 bg-[radial-gradient(circle_at_35%_45%,rgba(255,255,255,0.95),rgba(255,255,255,0.78)_36%,rgba(255,255,255,0)_72%)]"></div>
            </div>

            <div class="relative mx-auto grid w-full max-w-[1440px] gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center xl:px-24">
                <div class="text-center lg:text-left">
                    <div class="mx-auto mb-8 flex h-28 w-36 items-center justify-center lg:mx-0" aria-hidden="true">
                        <img src="{{ asset('images/bendera-korea.png') }}" alt="" class="h-24 w-36 object-contain drop-shadow-xl" loading="lazy">
                    </div>
                    <p class="text-4xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-5xl">Jutaan Pelanggan</p>
                    <p class="mt-6 text-xl font-bold text-slate-600">Dipercaya Selama 25 Tahun</p>
                    <p class="mx-auto mt-10 max-w-2xl text-lg font-bold leading-9 text-slate-600 lg:mx-0">Dengan lebih dari 10 juta pelanggan di seluruh dunia, Coway terus memperluas keahlian dan pengetahuan untuk menghadirkan produk yang dipercaya pelanggan.</p>
                </div>

                <div class="flex justify-center lg:justify-end">
                    <div class="relative h-72 w-72 sm:h-96 sm:w-96" aria-label="Water purifier market share Coway">
                        <div class="absolute inset-8 rounded-full bg-[conic-gradient(#7fc2ea_0_34%,#d1d5db_34%_100%)] shadow-2xl shadow-slate-500/20"></div>
                        <div class="absolute inset-16 flex items-center justify-center rounded-full bg-white shadow-inner">
                            <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-9 w-auto sm:h-12">
                        </div>
                        <span class="absolute right-8 top-14 flex h-14 w-14 items-center justify-center rounded-full bg-white text-sm font-bold text-slate-600 shadow-lg">Coway</span>
                        <span class="absolute left-8 top-14 flex h-12 w-12 items-center justify-center rounded-full bg-white text-lg font-bold text-slate-600 shadow-lg">Z</span>
                        <span class="absolute left-0 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white text-lg font-bold text-slate-600 shadow-lg">Y</span>
                        <span class="absolute bottom-12 left-10 flex h-12 w-12 items-center justify-center rounded-full bg-white text-lg font-bold text-slate-600 shadow-lg">X</span>
                        <div class="absolute -bottom-16 right-8 text-center text-white drop-shadow-lg sm:right-12">
                            <p class="text-2xl font-bold uppercase tracking-[0.08em]">Water Purifier</p>
                            <p class="mt-1 text-2xl font-bold uppercase tracking-[0.08em] text-[#34a9d7]">Market Share</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="design-awards-preview" class="relative flex min-h-screen items-center overflow-hidden bg-white pt-[72px]">
            <div class="absolute inset-0 grid lg:grid-cols-2">
                <div class="relative flex items-center justify-center bg-white">
                    <img src="{{ asset('images/coway-design-award-product.jpg') }}" alt="Desain produk Coway" class="h-[72%] max-h-[760px] w-full object-contain object-center px-8 sm:h-[78%] lg:object-left">
                    <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-r from-transparent to-white"></div>
                </div>
                <div class="relative hidden bg-slate-50 lg:block">
                    <div class="absolute inset-0 bg-[linear-gradient(rgba(148,163,184,0.16)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.16)_1px,transparent_1px)] bg-[size:52px_52px]"></div>
                    <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-white to-transparent"></div>
                </div>
            </div>

            <div class="relative mx-auto grid w-full max-w-[1440px] gap-10 px-5 py-20 sm:px-8 lg:grid-cols-2 lg:items-center xl:px-24">
                <div class="min-h-[360px]"></div>
                <div class="rounded bg-white/80 p-8 text-center backdrop-blur-sm lg:bg-transparent lg:p-0">
                    <p class="text-4xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-5xl">Desain Terbaik</p>
                    <p class="mt-6 text-xl font-bold text-slate-600">Jumlah Penghargaan Yang Tak Terhitung</p>
                    <p class="mx-auto mt-10 max-w-3xl text-lg font-bold leading-9 text-slate-600">Setiap produk berteknologi tinggi yang dihasilkan Coway dilengkapi dengan desain yang praktis, modern, dan berkelas untuk rumah pelanggan.</p>

                    <div class="mx-auto mt-12 flex max-w-4xl flex-wrap items-center justify-center gap-4 sm:gap-6">
                        <div class="flex h-16 w-28 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/reddot-design-award.png') }}" alt="Red Dot Design Award" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                        <div class="flex h-16 w-28 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/if-product-design-award.png') }}" alt="iF Product Design Award" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                        <div class="flex h-16 w-28 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/good-design-logo.png') }}" alt="Good Design" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                        <div class="flex h-16 w-32 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/japan-good-design-award.png') }}" alt="Good Design Award" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                        <div class="flex h-16 w-32 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/idea-awards-logo.png') }}" alt="International Design Excellence Awards" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                        <div class="flex h-16 w-28 items-center justify-center rounded bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200">
                            <img src="{{ asset('images/pin-up-design-award.png') }}" alt="Pin Up Design Award" class="max-h-full max-w-full object-contain" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="heart-service-preview" class="relative flex min-h-screen items-center overflow-hidden bg-white pt-[72px]">
            <div class="absolute inset-0 grid lg:grid-cols-[0.9fr_1.1fr]">
                <div class="relative bg-white"></div>
                <div class="relative min-h-[420px] bg-white">
                    <img src="{{ asset('images/coway-heart-service-seamless.png') }}" alt="Coway HEART Service" class="h-full w-full object-cover object-[68%_center]">
                    <div class="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-white via-white/75 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-white/75 to-transparent"></div>
                </div>
                <div class="absolute inset-0 bg-gradient-to-r from-white via-white/35 to-transparent lg:hidden"></div>
            </div>

            <div class="relative mx-auto w-full max-w-[1440px] px-5 py-20 sm:px-8 xl:px-24">
                <div class="max-w-2xl text-center lg:text-left">
                    <p class="text-4xl font-bold uppercase tracking-[0.08em] text-[#34a9d7] sm:text-5xl">Layanan Terbaik</p>
                    <p class="mt-6 text-xl font-bold text-[#34a9d7]">Pengalaman Yang Tak Tertandingi</p>
                    <p class="mx-auto mt-10 max-w-2xl text-lg font-bold leading-9 text-slate-600 lg:mx-0">Dengan jangkauan yang luas, layanan Coway HEART menjadi salah satu alasan mengapa pelanggan semakin yakin memilih produk Coway untuk kebutuhan hidup sehat.</p>
                    <div class="mt-14 flex flex-col items-center gap-8 lg:items-start">
                        <img src="{{ asset('images/gratis-service-icon.webp') }}" alt="Coway HEART Service" class="h-28 w-auto object-contain">
                        <a href="{{ route('heart-service') }}" class="inline-flex h-14 items-center justify-center rounded-full border border-slate-400 bg-white/70 px-10 text-sm font-bold uppercase tracking-wide text-slate-600 transition hover:border-[#00a4e4] hover:text-[#00a4e4]">Lihat Coway HEART Service</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="products" class="bg-white py-20">
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 xl:px-24">
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">Produk</p>
                    <h2 class="mt-3 text-4xl font-bold tracking-normal text-slate-950 sm:text-5xl">Pilihan produk Coway untuk kebutuhan pelanggan.</h2>
                    <p class="mx-auto mt-5 max-w-3xl leading-8 text-slate-600">Jelajahi kategori dan model produk Coway yang tersedia untuk membantu pelanggan memilih solusi air dan udara yang paling sesuai.</p>
                </div>

                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($productCategories as $category)
                        <article id="{{ Str::slug($category['name']) }}" class="group overflow-hidden rounded bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-100">
                            <div class="flex h-56 items-center justify-center bg-gradient-to-br {{ $category['gradient'] }}">
                                <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" class="max-h-48 object-contain p-5 transition duration-300 group-hover:scale-105" loading="lazy">
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-slate-950">{{ $category['name'] }}</h3>
                                <p class="mt-5 text-xs font-extrabold uppercase tracking-[0.18em] text-[#00a4e4]">Produk</p>
                                <ul class="mt-3 space-y-1.5 text-sm leading-6 text-slate-600">
                                    @foreach ($category['items'] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="services" class="bg-slate-50 py-20">
            <div class="mx-auto grid max-w-[1440px] gap-10 px-5 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center xl:px-24">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">Services</p>
                    <h2 class="mt-3 text-4xl font-bold tracking-normal text-slate-950 sm:text-5xl">Layanan Coway untuk menjaga kualitas produk pelanggan.</h2>
                    <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Layanan Coway membantu pelanggan menjaga kualitas produk setelah pembelian melalui kunjungan, konsultasi, dan perawatan berkala.</p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach ($services as $service)
                            <a href="{{ $service['href'] }}" class="rounded bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-100">
                                <p class="text-xl font-bold text-slate-950">{{ $service['name'] }}</p>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Pelajari dukungan {{ $service['name'] }} sebagai nilai tambah saat memilih produk Coway.</p>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="overflow-hidden rounded bg-[#7dacd3] p-8 text-white shadow-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-sky-50">Dukungan Layanan</p>
                    <h3 class="mt-4 text-3xl font-bold">Bantu pelanggan memahami perawatan produk Coway.</h3>
                    <div class="mt-8 space-y-4">
                        @foreach (['HEART Service', 'CODY Services'] as $item)
                            <div class="flex items-center justify-between border-b border-white/25 pb-3">
                                <span class="font-semibold">{{ $item }}</span>
                                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase">Layanan</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('heart-service') }}" class="mt-8 inline-flex h-12 items-center justify-center rounded-full bg-white px-8 text-sm font-bold uppercase tracking-wide text-[#00a4e4] transition hover:bg-sky-50">Lihat HEART Service</a>
                </div>
            </div>
        </section>

        <section id="about-coway" class="bg-white py-20">
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 xl:px-24">
                <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">About Coway</p>
                        <h2 class="mt-3 text-4xl font-bold tracking-normal text-slate-950 sm:text-5xl">Cerita brand Coway untuk memperkuat percakapan penjualan.</h2>
                    </div>
                    <p class="text-lg leading-8 text-slate-600">Kenali cerita, teknologi, desain, sertifikasi, dan komitmen Coway agar pelanggan semakin yakin memilih produk yang tepat.</p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-4">
                    @foreach ($aboutCoway as $item)
                        <a href="{{ route('about-coway.show', $item['slug']) }}" class="rounded bg-slate-50 p-6 ring-1 ring-slate-200 transition hover:-translate-y-1 hover:bg-white hover:shadow-xl hover:shadow-sky-100">
                            <div class="mb-7 h-2 w-20 rounded-full bg-[#00a4e4]"></div>
                            <h3 class="text-xl font-bold text-slate-950">{{ $item['nav'] }}</h3>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="support" class="bg-[#7dacd3] py-20 text-white">
            <div class="mx-auto grid max-w-[1440px] gap-10 px-5 sm:px-8 lg:grid-cols-[1fr_1fr] lg:items-center xl:px-24">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-50">Dukungan</p>
                    <h2 class="mt-3 text-4xl font-bold tracking-normal sm:text-5xl">Temukan panduan produk dan layanan pelanggan.</h2>
                    <p class="mt-5 max-w-xl text-lg leading-8 text-sky-50">Temukan informasi layanan dan panduan produk untuk membantu pelanggan memahami manfaat serta perawatan produk Coway.</p>
                </div>
                <div class="rounded bg-white p-8 text-slate-900 shadow-xl">
                    <h3 class="text-2xl font-bold">Akses Admin & Sales</h3>
                    <p class="mt-3 leading-7 text-slate-600">Area login hanya untuk admin dan sales Coway yang memiliki akses pengelolaan data internal.</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('support') }}" class="inline-flex h-12 items-center justify-center rounded-full bg-[#00a4e4] px-6 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008ac4]">Buka Dukungan</a>
                        <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center rounded-full border border-slate-300 px-6 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:border-[#00a4e4] hover:text-[#00a4e4]">Masuk Admin/Sales</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-[#7dacd3] py-10 text-white">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-8 px-5 sm:px-8 lg:flex-row lg:items-center lg:justify-between xl:px-24">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-8 w-auto object-contain">
                <div>
                    <p class="font-extrabold tracking-tight">COWAY</p>
                    <p class="text-sm text-sky-50">Katalog penjualan produk Coway</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-5 text-sm font-semibold uppercase tracking-wide text-sky-50">
                <a href="#products" class="hover:text-white">Produk</a>
                <a href="#services" class="hover:text-white">Services</a>
                <a href="#about-coway" class="hover:text-white">About Coway</a>
                <a href="{{ route('login') }}" class="hover:text-white">Masuk</a>
            </div>
        </div>
    </footer>
</body>
</html>
