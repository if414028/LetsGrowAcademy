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

    $typeIcons = [
        'normal' => asset('images/water-air-normal-icon.png'),
        'dingin' => asset('images/water-air-dingin-icon.png'),
        'panas' => asset('images/water-air-panas-icon.png'),
    ];

    $tableTopProducts = [
        ['name' => 'VILLAEM III', 'model' => 'CHP-7320R', 'price' => 'Rp 18.460.000', 'image' => asset('images/water-villaem-iii-chp-7320r.webp'), 'types' => ['normal', 'dingin', 'panas']],
        ['name' => 'KRISTAL ICE', 'model' => 'CHPI-7520L', 'price' => 'Rp 25.000.000', 'image' => asset('images/water-kristal-ice-chpi-7520l.webp'), 'types' => ['normal', 'dingin', 'panas']],
        ['name' => 'CINNAMON', 'model' => 'P-6320R', 'price' => 'Rp 7.200.000', 'image' => asset('images/water-cinnamon-p-6320r.webp'), 'types' => ['normal']],
        ['name' => 'NEO PLUS', 'model' => 'CHP-264L', 'price' => 'Rp 11.760.000', 'image' => asset('images/water-neo-plus-chp-264l.webp'), 'types' => ['normal', 'dingin', 'panas']],
        ['name' => 'VILLAEM II', 'model' => 'CHP-18AR', 'price' => 'Rp 15.730.000', 'image' => asset('images/water-villaem-ii-chp-18ar.webp'), 'types' => ['normal', 'dingin', 'panas']],
        ['name' => 'OMBAK', 'model' => 'CHP-7310R', 'price' => 'Rp 18.460.000', 'image' => asset('images/water-ombak-chp-7310r.webp'), 'types' => ['normal', 'dingin', 'panas']],
    ];

    $floorStandingProducts = [
        ['name' => 'SLIM STAND', 'model' => 'CHP-5730R', 'price' => 'Rp 18.460.000', 'image' => asset('images/water-slim-stand-chp-5730r.webp'), 'types' => ['normal', 'dingin', 'panas']],
        ['name' => 'CORE', 'model' => 'CHP-671R', 'price' => 'Rp 18.850.000', 'image' => asset('images/water-core-chp-671r.webp'), 'types' => ['normal', 'dingin', 'panas']],
    ];

    $comparisonProducts = [
        ['name' => 'SLIM STAND', 'model' => 'CHP-5730R', 'image' => asset('images/water-slim-stand-chp-5730r.webp')],
        ['name' => 'VILLAEM III', 'model' => 'CHP-7320R', 'image' => asset('images/water-villaem-iii-chp-7320r.webp')],
        ['name' => 'KRISTAL ICE', 'model' => 'CHPI-7520L', 'image' => asset('images/water-kristal-ice-chpi-7520l.webp')],
        ['name' => 'CINNAMON', 'model' => 'P-6320R', 'image' => asset('images/water-cinnamon-p-6320r.webp')],
        ['name' => 'NEO-PLUS', 'model' => 'CHP-264L', 'image' => asset('images/water-neo-plus-chp-264l.webp')],
        ['name' => 'VILLAEM II', 'model' => 'CHP-18AR', 'image' => asset('images/water-villaem-ii-chp-18ar.webp')],
        ['name' => 'OMBAK', 'model' => 'CHP-7310R', 'image' => asset('images/water-ombak-chp-7310r.webp')],
        ['name' => 'CORE', 'model' => 'CHP-671R', 'image' => asset('images/water-core-chp-671r.webp')],
    ];

    $comparisonRows = [
        ['section' => 'KAPASITAS AIR'],
        ['label' => 'Normal', 'values' => ['5.5 ℓ', '6.1 ℓ', '1.8 ℓ', '5.0 ℓ', '2.5 ℓ', '6.4 ℓ', '7.8 ℓ', '11.5 ℓ']],
        ['label' => 'Dingin', 'values' => ['3.5 ℓ', '2.6 ℓ', '1.8 ℓ', '✕', '2.3 ℓ', '3.7 ℓ', '2.3 ℓ', '6.0 ℓ']],
        ['label' => 'Panas', 'values' => ['3.0 ℓ', '2.7 ℓ', '1.5 ℓ', '✕', '1.0 ℓ', '1.2 ℓ', '3.4 ℓ', '3.6 ℓ']],
        ['label' => 'Es', 'values' => ['✕', '✕', '0.7 Kg', '✕', '✕', '✕', '✕', '✕']],
        ['section' => 'HARGA'],
        ['label' => 'Product Price', 'description' => 'Unit only.', 'values' => ['Rp 18.460.000', 'Rp 18.460.000', 'Rp 25.000.000', 'Rp 7.200.000', 'Rp 11.760.000', 'Rp 15.730.000', 'Rp 18.460.000', 'Rp 18.850.000']],
        ['label' => 'Package 36', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 3 Tahun.', 'values' => ['Rp 550.000/bln', 'Rp 550.000/bln', 'Rp 840.000/bln', 'Rp 253.000/bln', 'Rp 380.000/bln', 'Rp 450.000/bln', 'Rp 550.000/bln', 'Rp 570.000/bln']],
        ['label' => 'Package 60', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 5 Tahun.', 'values' => ['Rp 430.000/bln', 'Rp 430.000/bln', 'Rp 550.000/bln', 'Rp 220.000/bln', 'Rp 320.000/bln', 'Rp 370.000/bln', 'Rp 430.000/bln', 'Rp 450.000/bln']],
        ['label' => 'Package 72', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 6 Tahun.', 'values' => ['Rp 390.000/bln', 'Rp 370.000/bln', 'Rp 480.000/bln', 'Rp 200.000/bln', 'Rp 290.000/bln', 'Rp 350.000/bln', 'Rp 390.000/bln', 'Rp 410.000/bln']],
        ['label' => 'Package 84', 'description' => 'Gratis Ongkos Kirim, Gratis Instalasi, Gratis Layanan HEART Selama 7 Tahun.', 'values' => ['Rp 350.000/bln', 'Rp 330.000/bln', 'Rp 440.000/bln', 'Rp 180.000/bln', 'Rp 270.000/bln', 'Rp 310.000/bln', 'Rp 350.000/bln', 'Rp 370.000/bln']],
        ['label' => 'Filter 12', 'description' => 'Paket Layanan HEART Selama 12 Bulan.', 'values' => ['Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.500.000', 'Rp 1.800.000']],
        ['section' => 'FILTER'],
        ['label' => 'Neo Sense', 'description' => 'Penyaring karat, pasir.', 'values' => ['✓', '✓', '✓', '✓', '✓', '✓', '✓', '✕']],
        ['label' => 'RO Membrane', 'description' => 'Penyaring partikel kecil.', 'values' => ['✓', '✓', '✓', '✓', '✓', '✓', '✓', '✓']],
        ['label' => 'Plus Inno-Sense', 'description' => 'Penyaring karbon halus.', 'values' => ['✓', '✓', '✓', '✓', '✓', '✓', '✓', '✓']],
        ['label' => 'Plus Sediment', 'description' => 'Penyaring lumpur, pasir, karat.', 'values' => ['✕', '✓', '✓', '✓', '✓', '✓', '✓', '✓']],
        ['label' => 'Pre Carbon', 'description' => 'Penyaring zat kimia (klorin).', 'values' => ['✕', '✓', '✓', '✓', '✓', '✓', '✓', '✓']],
        ['label' => 'Anti Bacteria', 'description' => 'Penyaring mikroorganisme.', 'values' => ['✕', '✓', '✓', '✓', '✓', '✓', '✓', '✓']],
        ['label' => 'UV Sterilization', 'description' => 'Deaktivasi bakteri, virus, protozoa.', 'values' => ['✓', '✓', '✓', '✕', '✕', '✕', '✓', '✕']],
        ['section' => 'DIMENSI'],
        ['label' => 'P x L x T (cm)', 'values' => ['26 x 44.8 x 117', '31 x 52.3 x 52', '27 x 54.6 x 51.5', '20 x 40 x 40.5', '26 x 50.5 x 50', '34 x 52.3 x 51.8', '34 x 52.3 x 51.8', '37 x 49 x 125.7']],
        ['section' => 'KONSUMSI DAYA'],
        ['label' => 'Ampere / Watt', 'values' => ["Cold 0.6 A\nHot 500 W", "Cold 0.6 A\nHot 670-800 W", "Cold 0.5 A\nHot 260-310 W\nIce 150-180 W", '20 W', "Cold 0.7 A\nHot 300-380 W", "Cold 0.7 A\nHot 270-320 W", "Cold 0.6 A\nHot 672-800 W", "Cold 1.0 A\nHot 500-660 W"]],
    ];

    $renderProduct = function (array $product) use ($typeIcons) {
        $typeMarkup = collect($product['types'])
            ->map(fn ($type) => '<img src="' . $typeIcons[$type] . '" alt="Air ' . $type . '" class="h-9 w-auto object-contain opacity-100 drop-shadow-sm saturate-150 contrast-125 transition duration-300 group-hover:scale-110" loading="lazy">')
            ->implode('');

        return '
            <article class="group flex cursor-pointer flex-col items-center text-center transition duration-300 hover:-translate-y-2">
                <div class="flex h-12 items-center justify-center gap-2">' . $typeMarkup . '</div>
                <div class="mt-4 flex h-56 w-full items-end justify-center overflow-visible">
                    <img src="' . $product['image'] . '" alt="' . $product['name'] . ' ' . $product['model'] . '" class="max-h-56 w-auto object-contain drop-shadow-sm transition duration-500 ease-out group-hover:scale-110 group-hover:drop-shadow-xl" loading="lazy">
                </div>
                <h3 class="mt-6 text-2xl font-extrabold uppercase tracking-wide">' . $product['name'] . '</h3>
                <p class="mt-2 inline-flex rounded border border-white/80 px-3 py-1 text-sm font-extrabold uppercase tracking-wide text-white transition duration-300 group-hover:bg-white group-hover:text-[#0878a7]">' . $product['model'] . '</p>
                <p class="mt-4 text-lg font-extrabold">' . $product['price'] . '</p>
            </article>
        ';
    };
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Katalog produk Water Purifier Coway Indonesia lengkap dengan daftar model, harga, dan perbandingan fitur.">
    <title>Water Purifier Coway | Katalog Produk</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative min-h-screen overflow-hidden pt-[72px]">
            <img src="{{ asset('images/water-purifier-hero-new-wide.webp') }}" alt="Rangkaian Water Purifier Coway" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-black/55 via-black/15 to-transparent"></div>
            <div class="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-[1440px] items-center px-5 py-16 sm:px-8 xl:px-24">
                <div class="max-w-2xl text-white">
                    <p class="text-sm font-extrabold tracking-[0.2em] text-[#21b7e8]">#ChangeYourLife</p>
                    <p class="mt-3 text-sm font-extrabold uppercase tracking-[0.32em] text-[#21b7e8]">Pure Way of Life</p>
                    <h1 class="mt-8 text-5xl font-extrabold uppercase leading-[1.03] tracking-[0.08em] sm:text-6xl">Water Purifier</h1>
                    <p class="mt-5 text-lg font-extrabold uppercase tracking-wide">Tingkatkan kesehatan dari air yang Anda minum</p>
                    <p class="mt-8 max-w-2xl text-lg font-bold leading-8 text-white/90">Nikmati segelas kesempurnaan dari seri pemurni air kami yang dilengkapi dengan sistem penyaringan air canggih untuk memberikan Anda air murni terbaik.</p>
                </div>
            </div>
        </section>

        <section class="relative min-h-[76vh] overflow-hidden bg-slate-50">
            <img src="{{ asset('images/water-halal-m.webp') }}" alt="Filter halal Water Purifier Coway" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-white via-white/85 to-white/10"></div>
            <div class="relative mx-auto flex min-h-[76vh] max-w-[1440px] items-center px-5 py-20 sm:px-8 xl:px-24">
                <div class="max-w-xl">
                    <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-5xl">Halal dari bagian luar hingga bagian dalam</h2>
                    <p class="mt-7 text-lg font-semibold leading-9 text-slate-600">Pertama kali di Indonesia. Filter dan Water Purifier dari Coway memperoleh sertifikat Halal MUI di Indonesia untuk lebih meyakinkan Anda mengkonsumsi air yang terjamin.</p>
                    <img src="{{ asset('images/halal-coway-water-purifier.webp') }}" alt="Sertifikat Halal Coway Water Purifier" class="mt-8 h-20 w-auto object-contain" loading="lazy">
                    <a href="{{ route('about-coway.show', 'halal') }}" class="mt-7 inline-flex h-11 items-center justify-center rounded-full border border-slate-300 px-7 text-sm font-extrabold uppercase tracking-wide text-slate-600 transition hover:border-[#34a9d7] hover:bg-[#34a9d7] hover:text-white">Pelajari</a>
                </div>
            </div>
        </section>

        <section class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-5xl px-5 text-center sm:px-8">
                <h2 class="text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Overview</h2>
                <p class="mt-7 text-lg font-extrabold leading-9 text-slate-600">Water Purifier dari Coway dilengkapi sistem penyaringan air yang canggih, sehingga dapat memberikan Anda air murni terbaik.</p>
                <div class="mt-8 space-y-6 text-left text-lg font-semibold leading-9 text-slate-600">
                    <p>Semua orang tampaknya sepakat bahwa air minum yang murni jauh lebih sehat dan lebih disukai. Kita semua tahu bahwa betapa banyaknya polusi yang mencemari dunia di mana kita hidup sekarang. Asap pembakaran pabrik, sungai yang tercemar, dan asap dari knalpot kendaraan bermotor, serta penggunaan pestisida di beberapa bahan makanan, semuanya menyumbang penurunan terhadap kualitas hidup seorang anak manusia.</p>
                    <p>Semua orang tentu mengharapkan kualitas hidup terbaik, dan hal semacam itu bisa didapatkan dari air yang dikonsumsi. Air murni yang jernih haruslah bebas dari kuman serta bakteri. Dan ini rupanya menjadi fungsi utama dari Water Purifier, yaitu membebaskan air dari kuman serta bakteri yang mengintai setiap saat. Adakah fakta lain yang perlu diketahui dari water purifier? Tentu saja ada. Setidaknya Anda dapat mencari tahu fakta-fakta seputar water purifier sebelum memutuskan untuk membeli salah satu produk Water Purifier dari Coway.</p>
                </div>
            </div>
        </section>

        <section class="bg-[#0878a7] py-20 text-white sm:py-28">
            <div class="mx-auto max-w-6xl px-5 text-center sm:px-8">
                <h2 class="text-4xl font-extrabold uppercase tracking-wide sm:text-5xl">Products</h2>
                <p class="mt-12 text-lg font-extrabold uppercase tracking-wide">Table Top</p>
                <div class="mt-10 grid gap-x-20 gap-y-16 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tableTopProducts as $product)
                        {!! $renderProduct($product) !!}
                    @endforeach
                </div>

                <p class="mt-20 text-lg font-extrabold uppercase tracking-wide">Floor Standing</p>
                <div class="mx-auto mt-10 grid max-w-3xl gap-x-20 gap-y-16 sm:grid-cols-2">
                    @foreach ($floorStandingProducts as $product)
                        {!! $renderProduct($product) !!}
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <h2 class="text-center text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Products Comparison</h2>
                <div class="mt-12 overflow-x-auto border border-slate-200 bg-white">
                    <table class="min-w-[1520px] w-full border-collapse text-left text-sm font-bold text-slate-600">
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
                                        <td colspan="9" class="border border-slate-200 bg-slate-100 px-5 py-4 text-base font-extrabold uppercase tracking-wide text-slate-700">{{ $row['section'] }}</td>
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
                                            <td class="whitespace-pre-line border border-slate-200 px-4 py-4 text-center">
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
            <a href="{{ route('public.products.air-purifier') }}" class="group relative min-h-[300px] overflow-hidden bg-[#72b494] px-8 py-12 text-white sm:px-16">
                <div class="relative z-10 flex h-full flex-col justify-center">
                    <p class="text-lg font-extrabold uppercase tracking-wide">Air Purifier</p>
                    <span class="mt-7 inline-flex h-10 w-max items-center justify-center rounded-full border border-white/70 px-6 text-xs font-extrabold uppercase tracking-wide transition group-hover:bg-white group-hover:text-[#72b494]">Lihat Produk</span>
                </div>
                <img src="{{ asset('images/air-purifier-products.webp') }}" alt="Air Purifier Coway" class="absolute bottom-0 right-8 h-[90%] w-auto object-contain transition duration-300 group-hover:scale-105" loading="lazy">
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
