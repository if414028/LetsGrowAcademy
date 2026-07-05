@php
    $sectionBase = $sectionBase ?? '';
    $productCategories = $productCategories ?? [
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
    $services = $services ?? [
        ['name' => 'HEART Service', 'href' => route('heart-service')],
        ['name' => 'CODY Services', 'href' => route('cody-services')],
    ];
    $aboutCoway = $aboutCoway ?? array_values(config('coway_public.about_pages', []));
    $resolvePublicHref = function (string $href) use ($sectionBase) {
        return \Illuminate\Support\Str::startsWith($href, ['http://', 'https://', '/'])
            ? $href
            : $sectionBase . $href;
    };
@endphp

<header class="fixed inset-x-0 top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8 xl:px-24">
        <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Beranda Coway">
            <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-8 w-auto object-contain">
        </a>

        <nav class="hidden h-full items-center text-[13px] font-bold uppercase tracking-wide text-slate-700 lg:flex" aria-label="Navigasi utama">
            <a href="{{ $sectionBase }}#change-your-life" class="flex h-full items-center px-4 transition hover:bg-sky-100 hover:text-sky-600">Ubah Hidupmu</a>
            <div class="group relative flex h-full items-center">
                <a href="{{ $sectionBase }}#products" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
                    Produk
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </a>
                <div class="invisible absolute left-1/2 top-full w-[760px] -translate-x-1/2 translate-y-2 bg-[#34a9d7] p-7 text-white opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                    <div class="grid grid-cols-3 gap-7">
                        @foreach ($productCategories as $category)
                            <a href="{{ $resolvePublicHref($category['href']) }}" class="block">
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
                <a href="{{ $sectionBase }}#services" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
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
                <a href="{{ $sectionBase }}#about-coway" class="flex h-full items-center gap-1 px-4 transition hover:bg-sky-100 hover:text-sky-600">
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
            <a href="{{ $sectionBase }}#change-your-life" x-on:click="open = false">Ubah Hidupmu</a>
            <div>
                <button type="button" class="flex w-full items-center justify-between text-left uppercase" x-on:click="productOpen = !productOpen" x-bind:aria-expanded="productOpen.toString()" aria-controls="mobile-products-menu">
                    <span>Produk</span>
                    <svg class="h-4 w-4 transition" x-bind:class="{ 'rotate-180': productOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="mobile-products-menu" x-show="productOpen" class="mt-4 overflow-hidden rounded bg-[#34a9d7] p-4 text-white shadow-inner">
                    <div class="space-y-5">
                        @foreach ($productCategories as $category)
                            <a href="{{ $resolvePublicHref($category['href']) }}" x-on:click="open = false" class="block">
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
