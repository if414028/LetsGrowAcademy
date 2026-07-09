<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $whatsappOrderBase = 'https://wa.me/62811920291';
    $sq = fn (string $file) => asset('images/squarebig/' . $file);

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

    $featureIcons = [
        ['title' => 'Mode', 'text' => 'Sesuaikan mode: Smart, Sleep, & Eco.', 'image' => $sq('mode.png')],
        ['title' => 'Fan Speed', 'text' => 'Sesuaikan kecepatan kipas dengan kebutuhan Anda.', 'image' => $sq('speed.png')],
        ['title' => 'Light', 'text' => 'Sesuaikan pencahayaan dengan kondisi ruangan.', 'image' => $sq('light.png')],
        ['title' => 'Air Info', 'text' => 'Cek kualitas udara secara real-time (PM2.5, PM10).', 'image' => $sq('airinfo.png')],
    ];

    $specRows = [
        ['PRODUK', 'Pemurni Udara'],
        ['MODEL', 'SQUAREBIG AP-2425H'],
        ['WARNA', 'Pebble Gray & Sand Beige'],
        ['FILTER & SIKLUS PENGGANTIAN', 'Pre-Filter (2x) dapat dicuci, Air Matching Filter (2x) 4 bulan, Deodorization Filter (2x) 24 bulan, High Efficiency Filter (2x) 12 bulan'],
        ['PILIHAN AIR MATCHING FILTER', 'Fine Dust Filter, Allergen Filter, Deodorization Filter'],
        ['LAJU SUPLAI UDARA BERSIH (CADR)', '11,1 m³/min atau 666 m³/h'],
        ['JANGKAUAN AREA', '79 m²'],
        ['TINGKAT KEBISINGAN', '23 dB'],
        ['FITUR', '4-Stage Speed Control, Real-time Air Quality Indicator, Automatic Sleep Mode when room is dark for 3 minutes'],
        ['SENSOR & MODE', 'Sensor: Dust, Light. Mode: Smart, Sleep, Eco, Manual'],
        ['KONSUMSI DAYA', '78 W'],
        ['DIMENSI (P x L x T)', '355 x 365 x 535 mm'],
        ['BERAT BERSIH', '11 kg'],
    ];

    $comparisonRows = [
        ['feature' => 'Jangkauan', 'squarefit' => '33 m²', 'squarebig' => '79 m²'],
        ['feature' => 'Dimensi (P x L x T)', 'squarefit' => '366 x 183 x 500 mm', 'squarebig' => '355 x 365 x 535 mm'],
        ['feature' => 'Berat', 'squarefit' => '7 kg', 'squarebig' => '11 kg'],
        ['feature' => 'Mode', 'squarefit' => 'Smart, Sleep, Eco, Manual', 'squarebig' => 'Smart, Sleep, Eco, Manual'],
        ['feature' => 'Filter', 'squarefit' => 'Single', 'squarebig' => 'Double'],
        ['feature' => 'Sensor', 'squarefit' => 'Cahaya, Debu (PM2.5)', 'squarebig' => 'Cahaya, Debu (PM2.5, PM10)'],
        ['feature' => 'Tingkat Kebisingan', 'squarefit' => '20 dB', 'squarebig' => '23 dB'],
        ['feature' => 'Indikator Kualitas Udara', 'squarefit' => '✓', 'squarebig' => '✓'],
        ['feature' => 'Laju Suplai Udara Bersih', 'squarefit' => '324 m³/h atau 5,4 m³/min', 'squarebig' => '666 m³/h atau 11,1 m³/min'],
        ['feature' => 'Konsumsi Daya', 'squarefit' => '45 W', 'squarebig' => '78 W'],
    ];

    $gallery = [
        ['image' => $sq('gallery-squarebig-coway-jakarta-1.webp'), 'label' => 'White Living Room'],
        ['image' => $sq('gallery-squarebig-coway-jakarta-2.webp'), 'label' => 'Family Moment'],
        ['image' => $sq('gallery-squarebig-coway-jakarta-3.webp'), 'label' => 'Filtration Animation'],
        ['image' => $sq('gallery-squarebig-coway-jakarta-4.webp'), 'label' => 'Kitchen Space'],
        ['image' => $sq('gallery-squarebig-coway-jakarta-5.webp'), 'label' => 'Touch Button'],
        ['image' => $sq('gallery-squarebig-coway-jakarta-6.webp'), 'label' => 'Pebble Gray & Sand Beige'],
    ];

    $priceCards = [
        [
            'name' => 'Product Price',
            'price' => 'Rp 11.280.000',
            'items' => ['Tipe: Cash', 'Periode Service: -', 'Tagihan Bulanan: -', 'Periode Tagihan: -', 'Service & Filter: -'],
            'active' => true,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*Product%20Only*',
        ],
        [
            'name' => 'Package 36',
            'price' => 'Rp 14.040.000',
            'items' => ['Tipe: Installment', 'Periode Service: 36 Bulan', 'Tagihan Bulanan: Rp 390.000', 'Service & Filter: Gratis'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*36*',
        ],
        [
            'name' => 'Package 60',
            'price' => 'Rp 18.600.000',
            'items' => ['Tipe: Installment', 'Periode Service: 60 Bulan', 'Tagihan Bulanan: Rp 310.000', 'Service & Filter: Gratis'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*60*',
        ],
        [
            'name' => 'Package 72',
            'price' => 'Rp 20.880.000',
            'items' => ['Tipe: Installment', 'Periode Service: 72 Bulan', 'Tagihan Bulanan: Rp 290.000', 'Service & Filter: Gratis'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*72*',
        ],
        [
            'name' => 'Package 84',
            'price' => 'Rp 22.680.000',
            'items' => ['Tipe: Installment', 'Periode Service: 84 Bulan', 'Tagihan Bulanan: Rp 270.000', 'Service & Filter: Gratis'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*84*',
        ],
        [
            'name' => 'Filter 12',
            'price' => 'Rp 1.500.000',
            'items' => ['Tipe: Additional', 'Periode Service: 12 Bulan', 'Tagihan Bulanan: -', 'Service & Filter: Included'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Air%20Purifier%0ANama%3A%20*SQUAREBIG*%0AModel%3A%20*AP-2425H*%0APackage%3A%20*Filter%2012*',
        ],
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Detail produk Coway SQUAREBIG AP-2425H dengan fitur, teknologi filter, spesifikasi, dan daftar harga.">
    <title>SQUAREBIG AP-2425H Coway | Detail Produk</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes squarebigFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-16px); }
        }

        @keyframes squarebigAir {
            0% { transform: translateX(-18%) scaleX(.75); opacity: 0; }
            30% { opacity: .75; }
            100% { transform: translateX(92%) scaleX(1.12); opacity: 0; }
        }

        @keyframes squarebigFadeUp {
            from { opacity: 0; transform: translateY(34px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .squarebig-float {
            animation: squarebigFloat 5s ease-in-out infinite;
        }

        .squarebig-air-line {
            animation: squarebigAir 3.6s ease-in-out infinite;
        }

        .squarebig-air-line:nth-child(2) {
            animation-delay: .65s;
        }

        .squarebig-air-line:nth-child(3) {
            animation-delay: 1.35s;
        }

        .squarebig-reveal {
            animation: squarebigFadeUp .9s ease both;
        }

        @supports (animation-timeline: view()) {
            .squarebig-reveal {
                animation-timeline: view();
                animation-range: entry 0% cover 28%;
            }
        }
    </style>
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main class="overflow-hidden">
        <section class="relative min-h-screen bg-[#15171a] pt-[72px] text-white">
            <img src="{{ $sq('squarebig-ap-2425h-in-a-room-with-sunlight.webp') }}" alt="Coway SQUAREBIG AP-2425H di ruangan dengan cahaya matahari" class="absolute inset-0 h-full w-full object-cover object-[60%_center]">
            <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/42 to-transparent"></div>
            <div class="relative mx-auto grid min-h-[calc(100vh-72px)] max-w-[1440px] items-center gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[.9fr_1.1fr] xl:px-24">
                <div class="squarebig-reveal max-w-xl">
                    <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[#20b9e6]">#ChangeYourLife</p>
                    <p class="mt-2 text-xs font-extrabold uppercase tracking-[0.22em] text-[#20b9e6]">Pure Way of Life</p>
                    <p class="mt-10 text-lg font-extrabold uppercase tracking-[0.18em] text-white/90">AP-2425H</p>
                    <h1 class="mt-7 text-5xl font-extrabold uppercase leading-none tracking-[0.12em] sm:text-7xl">Squarebig</h1>
                    <p class="mt-5 max-w-md text-sm font-bold uppercase tracking-[0.16em] text-white/85">Where families breathe together</p>
                    <p class="mt-5 max-w-md text-base font-semibold leading-8 text-white/75">Tingkatkan momen kebersamaan dan kenyamanan dengan Coway Squarebig.</p>
                    <img src="{{ $sq('allergy-uk-logo.webp') }}" alt="Allergy UK" class="mt-8 h-16 w-16 object-contain" loading="lazy">
                    <a href="#price-list" class="mt-10 inline-flex h-12 items-center justify-center rounded-full bg-[#f1ca42] px-7 text-sm font-extrabold uppercase tracking-wide text-slate-950 transition hover:bg-white">Daftar Harga</a>
                </div>
                <div class="hidden lg:block"></div>
            </div>
        </section>

        <section class="relative min-h-screen bg-slate-900 text-white">
            <img src="{{ $sq('squarebig-ap-2425h-in-a-cozy-living-room-2-colors.webp') }}" alt="SQUAREBIG di ruang keluarga" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-black/45"></div>
            <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-5 py-24 text-center sm:px-8">
                <h2 class="text-3xl font-extrabold uppercase tracking-[0.16em] sm:text-5xl">Where Power Meets Space</h2>
                <a href="https://www.youtube.com/watch?v=6o341njsL7Q" target="_blank" rel="noopener" class="mt-8 inline-flex h-16 w-16 items-center justify-center rounded-full border border-white/50 bg-white/15 text-white backdrop-blur transition hover:bg-white hover:text-slate-950" aria-label="Putar video">
                    <svg class="ml-1 h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                </a>
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-[#1d2b4b] text-white">
            <img src="{{ $sq('squarebig-ap2425h-sleeping-quitely-in-apartment.webp') }}" alt="SQUAREBIG sangat senyap di apartemen" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-black/8"></div>
            <div class="relative mx-auto flex min-h-screen max-w-[1500px] items-start justify-center px-5 pb-10 pt-24 text-center sm:px-8 sm:pt-28 xl:px-20">
                <div class="mt-4 max-w-5xl squarebig-reveal sm:mt-8">
                    <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-6xl">Sangat Senyap Berkat Desainnya</h2>
                    <p class="mx-auto mt-8 max-w-4xl text-lg font-bold leading-8 text-white sm:text-2xl">Coway Squarebig beroperasi hanya pada 23 dB untuk menjaga ruang bersama tetap segar. Sehening perpustakaan yang tenang.</p>
                </div>
            </div>
        </section>

        <section class="relative flex min-h-screen flex-col overflow-hidden bg-white px-5 pt-24 text-center sm:px-8 sm:pt-28">
            <div class="relative z-10 mx-auto max-w-6xl">
                <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-6xl">Indikator Real-Time Kualitas Udara</h2>
                <p class="mx-auto mt-5 max-w-4xl text-lg font-bold leading-8 text-slate-700 sm:text-2xl">Warna indikator LED menandakan bagaimana tingkat polusi udara di dalam ruangan saat ini.</p>
            </div>
            <div class="relative mx-auto mt-auto grid w-full max-w-[1680px] items-center gap-8 lg:grid-cols-[1.55fr_.45fr]">
                <video class="-ml-[8vw] h-[68vh] min-h-[640px] w-[118%] max-w-none object-contain object-bottom [clip-path:inset(0_1px_0_1px)] lg:-ml-[10vw] lg:w-[125%]" autoplay muted loop playsinline poster="{{ $sq('air-quality-squarebig-bad.webp') }}">
                    <source src="{{ $sq('air-quality-squarebig-animation.mp4') }}" type="video/mp4">
                </video>
                <p class="pb-16 text-left text-xl font-bold leading-8 text-slate-800 lg:max-w-lg lg:pb-0">Sensor PM2.5 dan PM10 menampilkan indikator kualitas udara dengan 4 indikasi warna yang jelas dan mudah dipahami.</p>
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-[#d6cabb] text-white">
            <img src="{{ $sq('squarebig-ap-2425h-in-a-bedroom.webp') }}" alt="SQUAREBIG di kamar tidur" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative mx-auto flex min-h-screen max-w-[1500px] items-center justify-end px-5 py-24 sm:px-8 xl:px-20">
                <div class="max-w-3xl text-center squarebig-reveal">
                    <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide text-[#fff36a] sm:text-6xl">Fit Space Full Performance</h2>
                    <p class="mt-6 max-w-3xl text-xl font-bold leading-9 text-white">Desain persegi, ringkas, pas di sudut mana pun yang memperindah setiap ruangan. Bahkan saat diletakkan 10 cm dari dinding.</p>
                </div>
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-[#d7c5aa]">
            <img src="{{ $sq('squarebig-ap-2425h-2-colors-in-a-woody-minimalist-room.webp') }}" alt="SQUAREBIG dua warna di ruangan minimalis kayu" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/35"></div>
            <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-5 py-24 sm:px-8 xl:px-20">
                <div class="max-w-2xl squarebig-reveal">
                    <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#e7c73f]">A perfect match for every style</p>
                    <h2 class="mt-5 text-4xl font-extrabold uppercase leading-tight tracking-wide text-white sm:text-6xl">Dua warna netral untuk berbagai gaya interior.</h2>
                    <p class="mt-6 max-w-xl text-lg font-semibold leading-9 text-white/85">Pilih warna yang sempurna untuk Anda dan tingkatkan suasana setiap tarikan nafas di rumah.</p>
                </div>
            </div>
        </section>

        <section class="bg-[#f2f1ee] text-center">
            <div class="mx-auto max-w-none">
                <div class="relative mx-auto overflow-hidden bg-[#f2f1ee]">
                    <img src="{{ $sq('squarebig-ap-2425h-upper-view.webp') }}" alt="Tampilan atas fitur SQUAREBIG" class="mx-auto h-[48vh] min-h-[360px] w-full object-contain object-top">
                </div>
                <div class="-mt-8 bg-[#f2f1ee] px-5 pb-20 sm:px-8 sm:pb-28">
                <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#34a9d7]">Fitur pintar dan serba guna</p>
                <h2 class="mx-auto mt-4 max-w-3xl text-3xl font-extrabold uppercase leading-tight text-slate-900 sm:text-5xl">Nikmati udara yang lebih bersih dengan berbagai cara.</h2>
                <div class="mx-auto mt-12 grid max-w-5xl gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featureIcons as $feature)
                        <article class="border border-slate-200 bg-white px-6 py-8 text-center shadow-sm">
                            <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] }}" class="mx-auto h-10 w-auto object-contain" loading="lazy">
                            <h3 class="mt-5 text-sm font-extrabold uppercase tracking-wide text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-3 text-sm font-semibold leading-6 text-slate-500">{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50">
            <div class="bg-white px-5 py-16 text-center sm:px-8">
                <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#34a9d7]">Features Overview</p>
            </div>
            <div class="grid">
                @foreach ([
                    ['title' => 'Smart Mode', 'text' => 'Memantau kualitas udara & secara instan menyesuaikan performa untuk Anda.', 'image' => $sq('features-overview-smart-mode.webp'), 'tone' => 'bg-[#6fb28c]'],
                    ['title' => 'Sleep Mode', 'text' => 'Meredupkan lampu & mengurangi kebisingan untuk istirahat tanpa gangguan.', 'image' => $sq('features-overview-sleep-mode.webp'), 'tone' => 'bg-[#6f8bb2]'],
                    ['title' => 'Eco Mode', 'text' => 'Menghemat energi sekaligus menjaga udara ruangan tetap murni dan segar.', 'image' => $sq('features-overview-eco-mode.webp'), 'tone' => 'bg-[#b2896f]'],
                ] as $overview)
                    <article class="grid lg:grid-cols-[.45fr_.55fr]">
                        <div class="{{ $overview['tone'] }} flex min-h-[420px] items-center px-5 py-16 text-white sm:px-12 lg:px-20">
                            <div class="max-w-xl squarebig-reveal">
                                <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide sm:text-6xl">{{ $overview['title'] }}</h2>
                                <p class="mt-6 text-lg font-semibold leading-9 text-white/85">{{ $overview['text'] }}</p>
                            </div>
                        </div>
                        <img src="{{ $overview['image'] }}" alt="Features overview {{ $overview['title'] }}" class="h-full min-h-[420px] w-full object-cover" loading="lazy">
                    </article>
                @endforeach
            </div>
            <div class="bg-[#f0ebda]">
                <div class="relative">
                    <img src="{{ $sq('squarefit-and-square-big-in-minimalist-living-room-coway-jakarta.webp') }}" alt="Squarefit dan Squarebig di ruang keluarga minimalis" class="h-auto w-full object-cover">
                    <div class="absolute right-[2%] top-[36%] max-w-xl text-right text-white squarebig-reveal">
                        <h2 class="text-4xl font-extrabold uppercase leading-tight tracking-wide text-[#fff36a] sm:text-6xl">Perfect Fit<br>For Your Space</h2>
                    </div>
                </div>
                <div class="mx-auto max-w-5xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="overflow-hidden bg-white shadow-2xl">
                <table class="w-full table-fixed border-collapse text-center text-base font-bold text-slate-700">
                    <thead>
                        <tr class="text-white">
                            <th class="bg-[#437474] px-5 py-5">Spesifikasi &amp; Fungsi</th>
                            <th class="bg-[#994d3e] px-5 py-5">Squarefit</th>
                            <th class="bg-[#c5853e] px-5 py-5">Squarebig</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as $row)
                            <tr class="border-b border-[#f5f5dc]">
                                <th class="bg-[#e2e2e4] px-5 py-4 text-left text-slate-950">{{ $row['feature'] }}</th>
                                <td class="bg-white px-5 py-4">{{ $row['squarefit'] }}</td>
                                <td class="bg-white px-5 py-4">{{ $row['squarebig'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                </div>
            </div>
        </section>

        <section class="bg-[#c9c9c9] px-5 py-20 sm:px-8">
            <div class="mx-auto grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-3">
                @foreach ($gallery as $item)
                    <figure class="group relative aspect-square overflow-hidden bg-white shadow-lg">
                        <img src="{{ $item['image'] }}" alt="{{ $item['label'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <figcaption class="absolute inset-x-0 bottom-0 bg-black/45 px-4 py-3 text-xs font-extrabold uppercase tracking-wide text-white">{{ $item['label'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#d2d2d2] px-5 pt-24 text-center sm:px-8">
            <div class="relative z-10 mx-auto max-w-6xl">
                <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#34a9d7]">Filtrasi Ganda 4 Lapis</p>
                <p class="mx-auto mt-5 max-w-3xl text-xl font-bold leading-8 text-slate-600">Sistem filtrasi 4 lapis yang menghilangkan hingga 99,999% kotoran di udara, menjaga udara tetap bersih dan terlindungi.</p>
                <div class="mx-auto mt-16 grid max-w-6xl items-start gap-6 lg:grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr]">
                    @foreach ([
                        ['step' => 'STEP 1', 'title' => 'Pre-Filter', 'text' => 'Menyaring partikel besar seperti rambut, bulu, pasir, dan debu.'],
                        ['step' => 'STEP 2', 'title' => 'Air Matching Filter', 'text' => 'Pilih Fine Dust Filter, Allergen Filter, atau Double Deodorization Filter.'],
                        ['step' => 'STEP 3', 'title' => 'Deodorization Filter', 'text' => 'Menghilangkan bau tidak sedap dan gas berbahaya.'],
                        ['step' => 'STEP 4', 'title' => 'High Efficiency Filter', 'text' => 'Menghilangkan debu super halus hingga 99,999%.'],
                    ] as $index => $step)
                        <article class="text-center">
                            <p class="text-lg font-extrabold text-slate-500">{{ $step['step'] }}</p>
                            <h3 class="mt-3 text-xl font-extrabold text-[#34a9d7]">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-base font-bold leading-7 text-slate-600">{{ $step['text'] }}</p>
                        </article>
                        @if ($index < 3)
                            <div class="hidden pt-20 lg:block">
                                <span class="block h-0 w-0 border-y-[10px] border-l-[16px] border-y-transparent border-l-[#60afad]"></span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="-mx-5 mt-16 bg-[#d2d2d2] sm:-mx-8">
                <video class="-mt-px block h-auto w-full object-contain object-top [clip-path:inset(1px_0_0_0)]" autoplay muted loop playsinline poster="{{ $sq('squarebig-double-filtration-technology.webp') }}">
                    <source src="{{ $sq('squarebig-filtration-animation.mp4') }}" type="video/mp4">
                </video>
            </div>
        </section>

        <section class="bg-[#e4edf2] px-5 py-16 text-center sm:px-8">
            <div class="mx-auto max-w-4xl">
                <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#34a9d7]">Melindungi Tempat Perlindungan Pribadi Anda</p>
                <div class="mx-auto mt-10 grid max-w-4xl gap-6 sm:grid-cols-4">
                    @foreach ([
                        ['image' => $sq('bacterial-food-smells.webp'), 'label' => 'Bau Makanan'],
                        ['image' => $sq('bacterial-dust.webp'), 'label' => 'Debu'],
                        ['image' => $sq('bacterial-mold.webp'), 'label' => 'Jamur'],
                        ['image' => $sq('bacterial-viruses.webp'), 'label' => 'Virus'],
                    ] as $bacteria)
                        <article>
                            <img src="{{ $bacteria['image'] }}" alt="{{ $bacteria['label'] }}" class="mx-auto h-28 w-28 rounded-full object-cover" loading="lazy">
                            <p class="mt-4 text-sm font-extrabold uppercase tracking-wide text-slate-600">{{ $bacteria['label'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-slate-900 px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(15,23,42,.92),rgba(15,23,42,.55)),url('/images/world-map-silhouette.webp')] bg-cover bg-center"></div>
            <div class="relative mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1fr_.8fr]">
                <div class="squarebig-reveal">
                    <p class="text-sm font-extrabold uppercase tracking-[0.22em] text-[#75cdf0]">Spesifikasi &amp; Harga</p>
                    <h2 class="mt-4 text-4xl font-extrabold uppercase leading-tight sm:text-6xl">SQUAREBIG AP-2425H</h2>
                    <div class="mt-10 overflow-hidden border border-white/15">
                        <table class="w-full border-collapse text-left text-sm font-bold">
                            <tbody>
                                @foreach ($specRows as $row)
                                    <tr class="border-b border-white/10">
                                        <th class="w-44 bg-white/10 px-5 py-4 uppercase tracking-wide text-white/60">{{ $row[0] }}</th>
                                        <td class="px-5 py-4 text-white/90">{{ $row[1] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="relative flex min-h-[560px] items-center justify-center">
                    <img src="{{ $sq('squarebig-ap-2425h-spec.png') }}" alt="Spesifikasi SQUAREBIG AP-2425H" class="relative z-10 max-h-[560px] w-auto object-contain drop-shadow-2xl" loading="lazy">
                </div>
            </div>
        </section>

        <section id="price-list" class="bg-[#f1eeee] px-5 py-20 text-center sm:px-8 sm:py-28">
            <div class="mx-auto max-w-7xl">
                <h2 class="text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-6xl">Daftar Harga</h2>
                <div class="mt-14 grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($priceCards as $card)
                        <article class="group flex min-h-[480px] flex-col rounded-3xl bg-white p-8 text-center shadow-xl transition duration-300 hover:-translate-y-2 hover:shadow-2xl">
                            <h3 class="text-2xl font-extrabold uppercase tracking-wide text-slate-700">{{ $card['name'] }}</h3>
                            <p class="mt-6 text-4xl font-extrabold text-[#34a9d7]">{{ $card['price'] }}</p>
                            <ul class="mt-8 flex-1 space-y-5 text-lg font-semibold text-slate-500">
                                @foreach ($card['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ $card['href'] }}" target="_blank" rel="noopener" class="mx-auto mt-10 inline-flex h-14 items-center justify-center rounded-full {{ $card['active'] ? 'bg-[#34a9d7] text-white' : 'border border-[#34a9d7] text-[#34a9d7]' }} px-10 text-base font-extrabold uppercase tracking-wide transition group-hover:bg-[#1498cc] group-hover:text-white">Order</a>
                        </article>
                    @endforeach
                </div>
                <div class="mt-12 text-center">
                    <a href="{{ $whatsappOrderBase }}" target="_blank" rel="noopener" class="inline-flex h-14 items-center justify-center rounded bg-emerald-500 px-8 text-base font-extrabold uppercase tracking-wide text-white transition hover:bg-emerald-600">Pesan Sekarang</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-slate-950 px-5 py-8 text-center text-sm font-semibold text-slate-300">
        <p>Coway Indonesia - Katalog Penjualan Produk Coway</p>
    </footer>
</body>
</html>
