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
            'href' => route('public.products.water-purifier'),
            'image' => asset('images/coway-water-purifier-core-slim.jpg'),
            'gradient' => 'from-cyan-400 via-sky-500 to-blue-900',
            'items' => ['SLIM STAND CHP-5730R', 'VILLAEM III CHP-7320R', 'KRISTAL ICE CHPI-7520L', 'CINNAMON P-6320R', 'NEO PLUS CHP-264L', 'OMBAK CHP-7310R', 'VILLAEM II CHP-18AR', 'CORE CHP-671R'],
        ],
        [
            'name' => 'Outdoor',
            'href' => route('public.products.outdoor'),
            'image' => asset('images/coway-outdoor-water-filter.jpg'),
            'gradient' => 'from-cyan-400 via-sky-600 to-teal-700',
            'items' => ['OUTDOOR FILTER POE-23A'],
        ],
    ];

    $products = [
        ['name' => 'SQUAREBIG', 'model' => 'AP-2425H', 'price' => 'Rp 11.280.000', 'image' => asset('images/air-squarebig-ap-2425h.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png'), 'href' => route('public.products.air-purifier-squarebig')],
        ['name' => 'SQUAREFIT', 'model' => 'AP-1125G', 'price' => 'Rp 8.400.000', 'image' => asset('images/air-squarefit-ap-1125g.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png')],
        ['name' => 'NOBLE 2', 'model' => 'AP-2023K', 'price' => 'Rp 17.582.400', 'image' => asset('images/air-noble-2-ap-2023k.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png')],
        ['name' => 'NEXT STORM', 'model' => 'AP-2025A', 'price' => 'Rp 11.280.000', 'image' => asset('images/air-next-storm-ap-2025a.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png')],
        ['name' => 'CARTRIDGE', 'model' => 'AP-1019C', 'price' => 'Rp 3.000.000', 'image' => asset('images/air-cartridge-ap-1019c.webp'), 'speed' => asset('images/air-purifier-2-speed-icon.png')],
        ['name' => 'LOMBOK', 'model' => 'AP-1520C', 'price' => 'Rp 12.220.000', 'image' => asset('images/air-lombok-ap-1520c.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png')],
        ['name' => 'STORM', 'model' => 'AP-1516D', 'price' => 'Rp 9.600.000', 'image' => asset('images/air-storm-ap-1516d.webp'), 'speed' => asset('images/air-purifier-3-speed-icon.png')],
    ];

    $comparisonProducts = [
        ['name' => 'SQUAREBIG', 'model' => 'AP-2425H', 'image' => asset('images/air-squarebig-ap-2425h.webp')],
        ['name' => 'SQUAREFIT', 'model' => 'AP-1125G', 'image' => asset('images/air-squarefit-ap-1125g.webp')],
        ['name' => 'NOBLE 2', 'model' => 'AP-2023K', 'image' => asset('images/air-noble-2-ap-2023k.webp')],
        ['name' => 'NEXT STORM', 'model' => 'AP-2025A', 'image' => asset('images/air-next-storm-ap-2025a.webp')],
        ['name' => 'CARTRIDGE', 'model' => 'AP-1019C', 'image' => asset('images/air-cartridge-ap-1019c.webp')],
        ['name' => 'LOMBOK', 'model' => 'AP-1520C', 'image' => asset('images/air-lombok-ap-1520c.webp')],
        ['name' => 'STORM', 'model' => 'AP-1516D', 'image' => asset('images/air-storm-ap-1516d.webp')],
    ];

    $comparisonRows = [
        ['section' => 'JANGKAUAN AREA'],
        ['label' => 'Meter Persegi', 'values' => ['79 m²', '33 m²', '67 m²', '50 m²', '33 m²', '50 m²', '49.5 m²']],
        ['section' => 'HARGA'],
        ['label' => 'Product Price', 'description' => 'Unit only.', 'values' => ['Rp 11.280.400', 'Rp 8.400.400', 'Rp 17.582.400', 'Rp 11.280.000', 'Rp 3.000.000', 'Rp 12.220.000', 'Rp 9.600.000']],
        ['label' => 'Package 36', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 3 Tahun.', 'values' => ['Rp 390.000/bln', 'Rp 270.000/bln', 'Rp 550.000/bln', 'Rp 390.000/bln', '—', 'Rp 390.000/bln', 'Rp 300.000/bln']],
        ['label' => 'Package 60', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 5 Tahun.', 'values' => ['Rp 310.000/bln', 'Rp 230.000/bln', 'Rp 470.000/bln', 'Rp 320.000/bln', '—', 'Rp 320.000/bln', 'Rp 250.000/bln']],
        ['label' => 'Package 72', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 6 Tahun.', 'values' => ['Rp 290.000/bln', 'Rp 210.000/bln', 'Rp 440.000/bln', 'Rp 290.000/bln', '—', 'Rp 290.000/bln', 'Rp 230.000/bln']],
        ['label' => 'Package 84', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 7 Tahun.', 'values' => ['Rp 270.000/bln', 'Rp 190.000/bln', 'Rp 410.000/bln', 'Rp 270.000/bln', '—', 'Rp 270.000/bln', 'Rp 210.000/bln']],
        ['label' => 'Filter 12', 'description' => 'Paket Layanan HEART Selama 12 Bulan.', 'values' => ['Rp 1.500.000', 'Rp 1.100.000', 'Rp 2.000.000', 'Rp 1.400.000', 'Rp 500.000', 'Rp 1.500.000', 'Rp 1.100.000']],
        ['section' => 'FILTER'],
        ['label' => 'Pre-Filter', 'description' => 'Menyaring partikel besar seperti rambut, bulut, pasir & debu.', 'values' => ['✓ (2 pcs)', '✓', '✓ (4 pcs)', '✓', '✓', '✓', '✓']],
        ['label' => 'Fine Dust Filter', 'description' => 'Menyaring partikel kecil seperti debu halus, jamur & serbuk sari.', 'values' => ['✓', '✓', '✓', '✓', '✕', '✓', '✓']],
        ['label' => 'Deodorization Filter', 'description' => 'Menghilangkan bau tak sedap & gas berbahaya.', 'values' => ['✓ (2 pcs)', '✓', '✓ (2 pcs)', '✓ double', '✓', '✓', '✓']],
        ['label' => 'HEPA Filter', 'description' => 'Menghilangkan asap rokok, menyaring virus, kuman, & debu mikro.', 'values' => ['✓ (2 pcs)', '✓', '✓ (4 pcs) copper', '✓ copper', '✓', '✓', '✓']],
        ['label' => 'RBD Plasma Filter', 'description' => 'Menghilangkan jamur & kuman seperti Aspergillus Nigermand & E. Coli.', 'values' => ['✕', '✕', '✕', '✕', '✕', '✓', '✕']],
        ['label' => 'Catalyst Filter', 'description' => 'Menghilangkan gas berbahaya seperti formaldehyde, toluene xylene & VOCs.', 'values' => ['✕', '✕', '✕', '✕', '✕', '✓', '✕']],
        ['label' => 'Air Matching Filter', 'description' => 'Smoke Filter, Allergen Filter, dan pilihan filter tambahan sesuai kebutuhan udara ruangan.', 'values' => ['✓ (2 pcs)', '✓', '✓ (2 pcs)', '✕', '✕', '✕', '✕']],
        ['label' => 'UVC Sterilisation', 'description' => 'Menonaktifkan DNA mikroorganisme hingga 99,9% seperti bakteri, virus, & patogen lainnya.', 'values' => ['✕', '✕', '✓', '✕', '✕', '✕', '✕']],
        ['section' => 'DIMENSI'],
        ['label' => 'P x L x T (cm)', 'values' => ['35.5 x 36.5 x 53.5', '36.6 x 18.3 x 50', '32 x 32 x 80.5', '41.2 x 23.2 x 75.25', '34 x 16.5 x 46.9', '47.6 x 29 x 69.3', '41 x 24 x 76.5']],
        ['section' => 'KONSUMSI DAYA'],
        ['label' => 'Watt', 'values' => ['78 W', '45 W', '55 W', '42 W', '35 W', '90 W', '65 W']],
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Katalog produk Air Purifier Coway Indonesia lengkap dengan daftar model, harga, dan perbandingan fitur.">
    <title>Air Purifier Coway | Katalog Produk</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative min-h-screen overflow-hidden pt-[72px]">
            <img src="{{ asset('images/air-purifier-hero-2026.webp') }}" alt="Rangkaian Air Purifier Coway" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent"></div>
            <div class="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-[1440px] items-center px-5 py-16 sm:px-8 xl:px-24">
                <div class="max-w-2xl text-white">
                    <p class="text-sm font-extrabold tracking-[0.2em] text-[#21b7e8]">#ChangeYourLife</p>
                    <p class="mt-3 text-sm font-extrabold uppercase tracking-[0.32em] text-[#21b7e8]">Pure Way of Life</p>
                    <h1 class="mt-8 text-5xl font-extrabold uppercase leading-[1.03] tracking-[0.08em] sm:text-6xl">Air Purifier</h1>
                    <p class="mt-5 text-lg font-extrabold uppercase tracking-wide">Selalu hirup kesegaran udara di rumah Anda</p>
                    <p class="mt-8 max-w-2xl text-lg font-bold leading-8 text-white/90">Lindungi Anda dan orang yang Anda sayangi dari bakteri, virus, dan kabut asap dengan pemurni udara Coway yang dilengkapi HEPA &amp; filter anti-virus lainnya.</p>
                </div>
            </div>
        </section>

        <section class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-5xl px-5 text-center sm:px-8">
                <h2 class="text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Overview</h2>
                <p class="mt-7 text-lg font-extrabold leading-9 text-slate-600">Air Purifier dari Coway memiliki performa penyaringan yang kuat &amp; kemudahan penggunaan, disertai fitur-fitur penting &amp; desain yang estetis guna menghadirkan pemurni udara terbaik di tengah keluarga Anda.</p>
                <div class="mt-8 space-y-6 text-left text-lg font-semibold leading-9 text-slate-600">
                    <p>Berdasarkan penelitian dari lembaga pemerhati lingkungan / Environmental Protection Agency (EPA), polusi udara merupakan 1 (satu) dari 5 (lima) penyebab buruknya kondisi udara yang dapat berakibat pada menurunnya tingkat kesehatan Anda.</p>
                    <p>Maka dari itu, Air Purifier dapat dijadikan pilihan untuk Anda yang tinggal di lingkungan dengan kualitas udara yang buruk. Bagi Anda yang memiliki anggota keluarga yang perokok, manfaat Air Purifier ini bisa membantu Anda meminimalisir bau serta asap rokok yang ada dalam ruangan. Bahkan tak hanya menetralisir bau atau asap, Air Purifier juga mampu membunuh virus atau bakteri yang menyebar melalui udara.</p>
                    <p>Air Purifier berbeda dengan Air Conditioner (AC). Air Purifier bisa dikatakan sebagai teknologi yang tepat saat ini untuk mendapatkan udara sehat di dalam ruangan, terlebih di jaman pandemi Corona Virus Disease 2019 (COVID-19) seperti saat ini.</p>
                    <div>
                        <p class="font-extrabold text-slate-700">Apa Saja Manfaat Air Purifier?</p>
                        <ol class="mt-2 list-decimal pl-6">
                            <li>Menghilangkan virus dan bakteri di udara.</li>
                            <li>Menghilangkan bau tidak sedap</li>
                            <li>Menghilangkan jamur.</li>
                            <li>Menghilangkan kabut asap.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#72b494] py-20 text-white sm:py-28">
            <div class="mx-auto max-w-6xl px-5 text-center sm:px-8">
                <h2 class="text-4xl font-extrabold uppercase tracking-wide sm:text-5xl">Products</h2>
                <div class="mt-16 grid gap-x-20 gap-y-16 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        @if (isset($product['href']))
                            <a href="{{ $product['href'] }}" class="group flex cursor-pointer flex-col items-center text-center transition duration-300 hover:-translate-y-2">
                        @else
                            <article class="group flex cursor-pointer flex-col items-center text-center transition duration-300 hover:-translate-y-2">
                        @endif
                            <img src="{{ $product['speed'] }}" alt="Speed {{ $product['name'] }}" class="h-4 w-auto object-contain transition duration-300 group-hover:scale-110" loading="lazy">
                            <div class="mt-4 flex h-52 w-full items-end justify-center overflow-visible">
                                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }} {{ $product['model'] }}" class="max-h-52 w-auto object-contain drop-shadow-sm transition duration-500 ease-out group-hover:scale-110 group-hover:drop-shadow-xl" loading="lazy">
                            </div>
                            <h3 class="mt-6 text-2xl font-extrabold uppercase tracking-wide transition duration-300 group-hover:text-white">{{ $product['name'] }}</h3>
                            <p class="mt-2 inline-flex rounded border border-white/80 px-3 py-1 text-sm font-extrabold uppercase tracking-wide text-white transition duration-300 group-hover:bg-white group-hover:text-[#72b494]">{{ $product['model'] }}</p>
                            <p class="mt-4 text-lg font-extrabold transition duration-300 group-hover:text-white">{{ $product['price'] }}</p>
                        @if (isset($product['href']))
                            </a>
                        @else
                            </article>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <h2 class="text-center text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Products Comparison</h2>
                <div class="mt-12 overflow-x-auto border border-slate-200 bg-white">
                    <table class="min-w-[1360px] w-full border-collapse text-left text-sm font-bold text-slate-600">
                        <thead>
                            <tr class="bg-slate-50 text-center uppercase tracking-wide text-slate-500">
                                <th class="w-56 border border-slate-200 px-5 py-5 text-left">Model</th>
                                @foreach ($comparisonProducts as $product)
                                    <th class="w-40 border border-slate-200 px-4 py-5 align-bottom">
                                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="mx-auto h-20 w-auto object-contain" loading="lazy">
                                        <span class="mt-3 block text-sm font-extrabold text-slate-600">{{ $product['name'] }}</span>
                                        <span class="block text-xs text-slate-400">{{ $product['model'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparisonRows as $row)
                                @if (isset($row['section']))
                                    <tr>
                                        <td colspan="8" class="border border-slate-200 bg-slate-100 px-5 py-4 text-base font-extrabold uppercase tracking-wide text-slate-700">{{ $row['section'] }}</td>
                                    </tr>
                                @else
                                    <tr class="align-top">
                                        <th class="border border-slate-200 bg-white px-5 py-4 text-left">
                                            <span class="block text-sm font-extrabold text-slate-700">{{ $row['label'] }}</span>
                                            @isset($row['description'])
                                                <span class="mt-2 block text-xs font-semibold leading-5 text-slate-400">{{ $row['description'] }}</span>
                                            @endisset
                                        </th>
                                        @foreach ($row['values'] as $value)
                                            <td class="border border-slate-200 px-4 py-4 text-center">
                                                @if (\Illuminate\Support\Str::contains($value, '✓'))
                                                    <span class="font-extrabold text-emerald-500">{{ $value }}</span>
                                                @elseif (\Illuminate\Support\Str::contains($value, '✕'))
                                                    <span class="font-extrabold text-rose-500">{{ $value }}</span>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid md:grid-cols-2">
            <a id="water-purifier" href="{{ route('public.products.water-purifier') }}" class="group relative min-h-[300px] overflow-hidden bg-[#057db1] px-8 py-12 text-white sm:px-16">
                <div class="relative z-10 flex h-full flex-col justify-center">
                    <p class="text-lg font-extrabold uppercase tracking-wide">Water Purifier</p>
                    <span class="mt-7 inline-flex h-10 w-max items-center justify-center rounded-full border border-white/70 px-6 text-xs font-extrabold uppercase tracking-wide transition group-hover:bg-white group-hover:text-[#057db1]">Lihat Produk</span>
                </div>
                <img src="{{ asset('images/water-purifier-products.webp') }}" alt="Water Purifier Coway" class="absolute bottom-0 right-8 h-[90%] w-auto object-contain transition duration-300 group-hover:scale-105" loading="lazy">
            </a>
            <a id="outdoor" href="{{ route('public.products.outdoor') }}" class="group relative min-h-[300px] overflow-hidden bg-[#0d4f8f] px-8 py-12 text-white sm:px-16">
                <div class="relative z-10 flex h-full flex-col justify-center">
                    <p class="text-lg font-extrabold uppercase tracking-wide">Outdoor</p>
                    <span class="mt-7 inline-flex h-10 w-max items-center justify-center rounded-full border border-white/70 px-6 text-xs font-extrabold uppercase tracking-wide transition group-hover:bg-white group-hover:text-[#0d4f8f]">Lihat Produk</span>
                </div>
                <img src="{{ asset('images/outdoor-filter-products.webp') }}" alt="Outdoor Filter Coway" class="absolute -bottom-4 right-6 h-[105%] w-auto object-contain transition duration-300 group-hover:scale-105" loading="lazy">
            </a>
        </section>
    </main>

    <footer class="bg-slate-950 px-5 py-8 text-center text-sm font-semibold text-slate-300">
        <p>Coway Indonesia - Katalog Penjualan Produk Coway</p>
    </footer>
</body>
</html>
