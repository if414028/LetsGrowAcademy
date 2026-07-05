<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $whatsappOrderBase = 'https://wa.me/62811920291';

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

    $heroFeatures = [
        ['title' => 'Tekanan Air', 'subtitle' => 'Instalasi Rendah', 'icon' => asset('images/outdoor-icon-psi.webp')],
        ['title' => 'Membran', 'subtitle' => 'PVDF UF', 'icon' => asset('images/outdoor-icon-membran.webp')],
        ['title' => 'Aliran Air', 'subtitle' => 'Lebih Kencang', 'icon' => asset('images/outdoor-icon-flow.webp')],
        ['title' => 'Katup', 'subtitle' => 'Multiport', 'icon' => asset('images/outdoor-icon-multiport.webp')],
        ['title' => 'Sistem Pembilasan', 'subtitle' => 'Otomatis Terbaru', 'icon' => asset('images/outdoor-icon-flushing.webp')],
    ];

    $problems = [
        ['title' => 'Baju menguning', 'image' => asset('images/outdoor-problem-baju.webp')],
        ['title' => 'Keran bernoda', 'image' => asset('images/outdoor-problem-keran.webp')],
        ['title' => 'Air berbau', 'image' => asset('images/outdoor-problem-air-berbau.webp')],
        ['title' => 'Kulit gatal', 'image' => asset('images/outdoor-problem-kulit.webp')],
    ];

    $finishingFeatures = [
        [
            'title' => 'Dua Pilihan Cara Pemasangan',
            'text' => 'Opsi dipasang di dinding atau diberdirikan di lantai tergantung pada ketersediaan ruang atau lingkungan pemasangan.',
            'image' => asset('images/outdoor-feature-install.webp'),
        ],
        [
            'title' => 'Tahan untuk Penggunaan Outdoor',
            'text' => 'Coway Outdoor Filter terbuat dari stainless steel 304, membuatnya kedap air dan tahan lama untuk penggunaan di luar ruangan.',
            'image' => asset('images/outdoor-feature-outdoor.webp'),
        ],
        [
            'title' => 'Peningkatan Desain untuk Mempertahankan Tekanan Air',
            'text' => 'Menambahkan lebih banyak rusuk di atas dan di bawah untuk memperkuat poros tengah dan penutup membran.',
            'image' => asset('images/outdoor-feature-pressure.webp'),
        ],
        [
            'title' => 'Tidak Memerlukan Tenaga Listrik',
            'text' => 'Beroperasi sepenuhnya menggunakan tekanan air sehingga hemat energi dan praktis digunakan setiap hari.',
            'image' => asset('images/outdoor-feature-electric.webp'),
        ],
    ];

    $gallery = [
        asset('images/outdoor-gallery-eksterior.webp'),
        asset('images/outdoor-gallery-dinding.webp'),
        asset('images/outdoor-gallery-lantai.webp'),
        asset('images/outdoor-gallery-atas.webp'),
        asset('images/outdoor-gallery-finishing.webp'),
        asset('images/outdoor-gallery-bawah.webp'),
    ];

    $specs = [
        ['PRODUK', 'Outdoor'],
        ['MODEL', 'OUTDOOR FILTER POE-23A'],
        ['TIPE FILTER', 'Polyvinylidene Fluoride (PVDF) Ultrafiltration Membrane'],
        ['MATERIAL RANGKA', '304 Stainless Steel'],
        ['SUHU KERJA', '5-45 C'],
        ['SUMBER AIR', 'Air PAM*, Air Tanah'],
        ['TEKANAN AIR INPUT', '15-80 PSI'],
        ['KAPASITAS FILTRASI', '3.200-3.300 L/h'],
        ['DIMENSI (diameter x T)', '127 x 1.090 mm'],
        ['BERAT BERSIH', '10 kg'],
    ];

    $priceCards = [
        [
            'name' => 'Product Price',
            'price' => 'Rp 15.000.000',
            'items' => ['Tipe: Cash', 'Periode Service: -', 'Tagihan Bulanan: -', 'Periode Tagihan: -', 'Service & Filter: -'],
            'active' => true,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Produk%20outdoor%0ANama%3A%20*Coway%20Outdoor%20Filter*%0AModel%3A%20*POE-23A*%0APackage%3A%20*Product%20Only*',
        ],
        [
            'name' => 'Package 84',
            'price' => 'Rp 24.360.000',
            'items' => ['Tipe: Installment', 'Periode Service: 84 Bulan', 'Tagihan Bulanan: Rp 290.000', 'Periode Tagihan: 84 Bulan', 'Service & Filter: Gratis'],
            'active' => false,
            'href' => $whatsappOrderBase . '?text=Hallo..%0A%0ASaya%20ingin%20order%20Water%20Purifier%0ANama%3A%20*Coway%20Outdoor%20Filter*%0AModel%3A%20*POE-23A*%0APackage%3A%20*84*',
        ],
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Katalog produk Outdoor Filter Coway POE-23A lengkap dengan fitur, spesifikasi, harga, dan paket layanan.">
    <title>Outdoor Filter POE-23A Coway | Katalog Produk</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes outdoorFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-14px); }
        }

        @keyframes outdoorFadeUp {
            from { opacity: 0; transform: translateY(34px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .outdoor-float {
            animation: outdoorFloat 4.5s ease-in-out infinite;
        }

        .outdoor-reveal {
            animation: outdoorFadeUp .9s ease both;
        }

        @supports (animation-timeline: view()) {
            .outdoor-reveal {
                animation-timeline: view();
                animation-range: entry 0% cover 26%;
            }
        }
    </style>
</head>
<body class="bg-white font-sans text-slate-800 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative min-h-screen overflow-hidden bg-[#1c2c41] pt-[72px] text-white">
            <img src="{{ asset('images/outdoor-hero.webp') }}" alt="Coway Outdoor Filter POE-23A" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-r from-[#152439]/80 via-[#152439]/35 to-transparent"></div>
            <div class="relative mx-auto flex min-h-[calc(100vh-72px)] max-w-[1440px] items-center px-5 py-16 sm:px-8 xl:px-24">
                <div class="max-w-2xl outdoor-reveal">
                    <p class="text-sm font-extrabold tracking-[0.2em] text-[#21b7e8]">#ChangeYourLife</p>
                    <p class="mt-3 text-sm font-extrabold uppercase tracking-[0.32em] text-[#21b7e8]">Pure Way of Life</p>
                    <p class="mt-8 text-base font-extrabold uppercase tracking-[0.25em] text-white/85">POE-23A</p>
                    <h1 class="mt-3 text-5xl font-extrabold uppercase leading-[1.03] tracking-[0.08em] sm:text-6xl">Outdoor Filter</h1>
                    <p class="mt-5 text-lg font-extrabold uppercase tracking-wide">Solusi menyeluruh untuk air di rumah</p>
                    <p class="mt-8 max-w-2xl text-lg font-bold leading-8 text-white/90">Volume tinggi, kemurnian tinggi, sahabat baru terbaik rumah tangga.</p>
                </div>
            </div>
        </section>

        <section class="relative z-10 bg-[#2b3850] px-5 py-6 text-white shadow-2xl sm:px-8">
            <div class="mx-auto grid max-w-4xl grid-cols-2 gap-4 sm:grid-cols-5">
                @foreach ($heroFeatures as $feature)
                    <article class="group rounded bg-white/10 px-3 py-4 text-center transition duration-300 hover:-translate-y-1 hover:bg-white/20">
                        <img src="{{ $feature['icon'] }}" alt="{{ $feature['title'] }}" class="mx-auto h-9 w-auto object-contain transition duration-300 group-hover:scale-110" loading="lazy">
                        <h2 class="mt-3 text-sm font-extrabold uppercase tracking-wide">{{ $feature['title'] }}</h2>
                        <p class="mt-1 text-xs font-bold text-white/75">{{ $feature['subtitle'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-slate-900 text-white">
            <img src="{{ asset('images/outdoor-video-bg.webp') }}" alt="Solusi air rumah tangga yang bersih" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-black/35"></div>
            <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center px-5 py-24 text-center sm:px-8">
                <h2 class="max-w-2xl text-3xl font-extrabold uppercase leading-tight tracking-wide sm:text-5xl">Solusi terbaik untuk air rumah tangga yang bersih</h2>
                <div class="mt-10 aspect-video w-full max-w-3xl overflow-hidden rounded-2xl border border-white/30 bg-black/40 shadow-2xl outdoor-reveal">
                    <iframe class="h-full w-full" src="https://www.youtube.com/embed/Cq4kjhIpgzQ" title="Coway Outdoor Filter" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </section>

        <section class="bg-[#243552] px-5 py-20 text-center text-white sm:px-8 sm:py-28">
            <div class="mx-auto max-w-5xl">
                <h2 class="text-3xl font-extrabold uppercase tracking-wide sm:text-5xl">Pernahkah Anda mengalami masalah ini di rumah?</h2>
                <p class="mx-auto mt-5 max-w-4xl text-base font-semibold leading-8 text-white/80 sm:text-lg">Kami menjaga Anda tetap aman dengan meningkatkan kualitas pasokan air rumah tangga langsung dari titik masuk rumah Anda dengan Coway Outdoor Filter.</p>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($problems as $problem)
                        <article class="group overflow-hidden rounded bg-white/10 text-left shadow-xl transition duration-300 hover:-translate-y-2 hover:bg-white/15">
                            <img src="{{ $problem['image'] }}" alt="{{ $problem['title'] }}" class="h-56 w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                            <p class="px-5 py-4 text-sm font-extrabold uppercase tracking-wide">{{ $problem['title'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#253849] px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="absolute inset-0 bg-gradient-to-b from-[#253849] via-[#2f5367] to-[#253849]"></div>
            <div class="relative mx-auto max-w-6xl text-center">
                <h2 class="text-3xl font-extrabold uppercase tracking-wide sm:text-5xl">Lapisan pertama proteksi untuk rumah Anda</h2>
                <p class="mx-auto mt-6 max-w-5xl text-base font-semibold leading-8 text-white/80 sm:text-lg">Air mengalir melalui pipa bawah tanah, bertemu karat, tanah, dan jaringan pipa yang rusak. Ketika airnya sampai, air tersebut mungkin mengandung kontaminan yang berpotensi mempengaruhi kehidupan sehari-hari Anda, meskipun Anda tidak meminumnya. Coway Outdoor Filter menyaring kotoran seperti endapan dan karat, sekaligus menghilangkan bakteri berbahaya yang berpotensi mengancam kehidupan keluarga Anda.</p>
                <img src="{{ asset('images/outdoor-protection-poster.webp') }}" alt="Proteksi pertama Outdoor Filter Coway" class="outdoor-float mx-auto mt-12 max-h-[460px] w-full object-contain" loading="lazy">
            </div>
        </section>

        <section class="grid bg-white lg:grid-cols-2">
            <div class="relative min-h-[70vh] overflow-hidden lg:min-h-screen">
                <img src="{{ asset('images/outdoor-compatible-left.webp') }}" alt="Outdoor Filter kompatibel dengan rumah" class="absolute inset-0 h-full w-full object-cover object-center">
            </div>
            <div class="flex items-center justify-center px-5 py-20 text-center sm:px-12 lg:min-h-screen lg:px-20">
                <div class="mx-auto max-w-2xl outdoor-reveal">
                    <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-5xl lg:text-6xl">Cocok dengan rumah yang tekanan airnya kecil</h2>
                    <img src="{{ asset('images/outdoor-install-requirements.webp') }}" alt="Persyaratan instalasi Outdoor Filter" class="mx-auto mt-10 w-full max-w-xl object-contain" loading="lazy">
                    <p class="mx-auto mt-10 max-w-2xl text-lg font-semibold leading-9 text-slate-600">Coway Outdoor Filter memiliki persyaratan instalasi yang mudah, mulai dari tekanan air rendah sebesar 15 psi. Sehingga dapat diaplikasikan pada rumah yang tekanan airnya kecil. Anda dapat menikmati air dengan kemurnian tinggi yang melimpah tanpa repotnya pengaturan yang rumit, apa pun ukuran dan lokasi rumah Anda.</p>
                    <p class="mt-5 text-sm font-bold text-slate-400">*Syarat dan ketentuan berlaku.</p>
                </div>
            </div>
            <div class="flex items-center justify-center px-5 py-20 text-center sm:px-12 lg:min-h-screen lg:px-20">
                <div class="mx-auto max-w-2xl outdoor-reveal">
                    <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-5xl lg:text-6xl">Membran Ultrafiltrasi PVDF</h2>
                    <img src="{{ asset('images/outdoor-membrane-diagram.webp') }}" alt="Membran ultrafiltrasi PVDF" class="mx-auto mt-10 w-full max-w-xl object-contain" loading="lazy">
                    <p class="mx-auto mt-10 max-w-2xl text-lg font-semibold leading-9 text-slate-600">Membran Ultrafiltrasi PVDF Coway memastikan bahwa setiap tetes air dimurnikan dengan cermat hingga ke tingkat 0,01 mikron. Dengan menggunakan konsep dari dalam ke luar untuk menghilangkan kotoran sekecil apa pun, Anda dapat yakin bahwa keluarga Anda menerima air bersih.</p>
                </div>
            </div>
            <div class="relative min-h-[70vh] overflow-hidden lg:min-h-screen">
                <img src="{{ asset('images/outdoor-membrane-right.webp') }}" alt="Detail membran Outdoor Filter Coway" class="absolute inset-0 h-full w-full object-cover object-center">
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-sky-100 text-center">
            <img src="{{ asset('images/outdoor-flow-bg.webp') }}" alt="Laju aliran cepat Outdoor Filter" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-white/10"></div>
            <div class="relative mx-auto min-h-screen max-w-5xl px-5 py-10 sm:px-8">
                <div class="mx-auto pt-0 sm:pt-2 lg:pt-4">
                    <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide text-[#34a9d7] sm:text-5xl">Kombinasi hebat laju aliran cepat & air yang lebih bersih</h2>
                    <p class="mx-auto mt-7 max-w-4xl text-lg font-semibold leading-9 text-slate-600">Coway Outdoor Filter memiliki kapasitas filtrasi sebesar 3.200-3.300 L/H, memastikan keseimbangan sempurna antara aliran air dan kemurnian air yang ditingkatkan untuk rumah tangga di Indonesia.</p>
                </div>
                <div class="absolute inset-x-5 bottom-[4vh] mx-auto max-w-3xl sm:bottom-[5vh]">
                    <p class="text-sm font-bold text-slate-500 sm:text-base">Jumlah air yang disaring dalam 1 menit sekitar 55L.</p>
                    <p class="mt-3 text-xs font-bold text-slate-500 sm:text-sm">*Semua hasil yang ditampilkan di atas, diuji di bawah tekanan 60 psi.</p>
                </div>
            </div>
        </section>

        <section class="bg-[#0d1928] px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="mx-auto grid max-w-6xl gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div class="relative aspect-square overflow-hidden rounded-full border border-white/10 bg-white/5 shadow-2xl">
                    <img src="{{ asset('images/outdoor-multiport-poster.webp') }}" alt="Katup multiport Outdoor Filter" class="h-full w-full object-cover transition duration-700 hover:scale-110" loading="lazy">
                </div>
                <div class="outdoor-reveal">
                    <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide sm:text-5xl">Katup Multiport 4 Arah yang mudah digunakan</h2>
                    <p class="mt-6 text-lg font-semibold leading-9 text-white/75">Filter tahan lama Coway dilengkapi katup multiport 4 arah yang menawarkan kendali air lengkap di tangan Anda. Dengan tuas yang mudah digunakan, Anda dapat dengan mudah beralih di antara mode Filter, Bypass, Fast Rinse, dan Backwash.</p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach (['Bypass', 'Backwash', 'Fast Rinse', 'Filter Mode'] as $mode)
                            <div class="rounded border border-white/10 bg-white/5 p-5">
                                <p class="text-lg font-extrabold">{{ $mode }}</p>
                                <p class="mt-2 text-sm font-semibold text-white/60">Mode pengaturan air untuk perawatan dan penggunaan harian.</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-[#24324a] text-white">
            <img src="{{ asset('images/outdoor-flushing-bg.webp') }}" alt="Sistem flushing otomatis Outdoor Filter" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-[#1b2639]/20"></div>
            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col items-center px-5 pb-12 pt-20 text-center sm:px-8 sm:pt-24 lg:pt-28">
                <div class="outdoor-reveal">
                    <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide sm:text-5xl lg:text-6xl">Sistem flushing otomatis terbaru</h2>
                    <p class="mx-auto mt-6 max-w-5xl text-lg font-semibold leading-9 text-white/90 sm:text-xl">Sistem inovatif kami secara otomatis membersihkan filter, menjamin proses pemurnian air yang bebas masalah. Fitur ini juga dengan cermat menangani perawatan filter, memastikan pengalaman yang lancar tanpa hambatan.</p>
                    <img src="{{ asset('images/outdoor-flushing-detail.webp') }}" alt="Automatic flushing dan IPX5" class="mx-auto mt-16 max-h-[520px] w-full max-w-5xl object-contain sm:mt-20" loading="lazy">
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#1d2a3d] px-5 py-20 text-center text-white sm:px-8 sm:py-28">
            <img src="{{ asset('images/outdoor-finishing-bg.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25">
            <div class="relative mx-auto max-w-6xl">
                <h2 class="text-3xl font-extrabold uppercase tracking-wide sm:text-5xl">Finishing penuh pertimbangan</h2>
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($finishingFeatures as $feature)
                        <article class="group rounded-2xl border border-white/10 bg-white/8 p-6 transition duration-300 hover:-translate-y-2 hover:bg-white/15">
                            <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] }}" class="mx-auto h-20 w-auto object-contain transition duration-300 group-hover:scale-110" loading="lazy">
                            <h3 class="mt-6 text-lg font-extrabold uppercase leading-snug">{{ $feature['title'] }}</h3>
                            <p class="mt-4 text-sm font-semibold leading-7 text-white/70">{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative min-h-screen overflow-hidden bg-[#101a30] text-white">
            <img src="{{ asset('images/outdoor-small-strong-bg.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-80">
            <div class="absolute inset-0 bg-[#101a30]/40"></div>
            <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center px-5 py-24 text-center sm:px-8">
                <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-wide sm:text-5xl">Outdoor Filter terbaru - lebih kecil & lebih kuat</h2>
                <img src="{{ asset('images/outdoor-small-strong.webp') }}" alt="Outdoor Filter terbaru lebih kecil dan lebih kuat" class="outdoor-float mt-12 max-h-[500px] w-full object-contain" loading="lazy">
            </div>
        </section>

        <section class="bg-white py-20 sm:py-28">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <h2 class="text-center text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Features Overview</h2>
                <div class="mt-12 grid overflow-hidden rounded-2xl shadow-2xl lg:grid-cols-3">
                    <article class="group bg-[#0f5592] p-8 text-white">
                        <img src="{{ asset('images/outdoor-overview-flushing.webp') }}" alt="Automatic flushing system" class="h-72 w-full object-contain transition duration-500 group-hover:scale-105" loading="lazy">
                        <h3 class="mt-6 text-xl font-extrabold uppercase tracking-wide">Automatic Flushing System</h3>
                    </article>
                    <article class="group bg-slate-100 p-8 text-slate-700">
                        <img src="{{ asset('images/outdoor-overview-compatible.webp') }}" alt="Tekanan air kecil" class="h-72 w-full object-contain transition duration-500 group-hover:scale-105" loading="lazy">
                        <h3 class="mt-6 text-xl font-extrabold uppercase tracking-wide text-[#34a9d7]">Cocok untuk tekanan air kecil</h3>
                    </article>
                    <article class="group bg-slate-800 p-8 text-white">
                        <img src="{{ asset('images/outdoor-overview-multiport.webp') }}" alt="Katup multiport" class="h-72 w-full object-contain transition duration-500 group-hover:scale-105" loading="lazy">
                        <h3 class="mt-6 text-xl font-extrabold uppercase tracking-wide">Katup Multiport 4 Arah</h3>
                    </article>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($gallery as $image)
                        <img src="{{ $image }}" alt="Galeri Outdoor Filter Coway" class="h-52 w-full rounded bg-slate-100 object-cover transition duration-300 hover:scale-[1.03]" loading="lazy">
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-[#314450] px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="mx-auto grid max-w-6xl gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-extrabold uppercase tracking-wide sm:text-5xl">Spesifikasi & Harga</h2>
                    <p class="mt-4 text-lg font-semibold text-white/75">Semua yang perlu Anda ketahui.</p>
                    <div class="mt-8 overflow-hidden rounded-xl border border-white/15">
                        <table class="w-full text-left text-sm font-bold">
                            <tbody>
                                @foreach ($specs as $spec)
                                    <tr class="border-b border-white/10 last:border-b-0">
                                        <th class="w-44 bg-white/5 px-5 py-4 uppercase tracking-wide text-white/70">{{ $spec[0] }}</th>
                                        <td class="px-5 py-4 text-white">{{ $spec[1] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-5 text-sm font-semibold leading-7 text-white/60">*Spesifikasi dapat berubah tanpa pemberitahuan sebelumnya. Kapasitas filtrasi dapat bervariasi tergantung tekanan, suhu, dan kualitas air di lokasi pemasangan.</p>
                    <a href="https://drive.google.com/file/d/1YmEfdlQ0gydaKxW1wU9EOIiuXzvkmQvk/view?usp=drive_link" target="_blank" rel="noopener" class="mt-7 inline-flex h-11 items-center justify-center rounded-full border border-white/60 px-8 text-sm font-extrabold uppercase tracking-wide transition hover:bg-white hover:text-[#314450]">Brosur</a>
                </div>
                <div class="text-center">
                    <img src="{{ asset('images/outdoor-spec-product.webp') }}" alt="Spesifikasi produk Outdoor Filter POE-23A" class="mx-auto max-h-[620px] w-auto object-contain" loading="lazy">
                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-white/15 bg-white/8 p-5">
                            <p class="text-sm font-bold uppercase tracking-wide text-white/65">Product Price</p>
                            <p class="mt-2 text-2xl font-extrabold">Rp 15.000.000</p>
                            <p class="mt-1 text-sm font-semibold text-white/60">Unit Only</p>
                        </div>
                        <div class="rounded-xl border border-white/15 bg-white/8 p-5">
                            <p class="text-sm font-bold uppercase tracking-wide text-white/65">Package 84</p>
                            <p class="mt-2 text-2xl font-extrabold">Rp 290.000<span class="text-base">/bln</span></p>
                            <p class="mt-1 text-sm font-semibold text-white/60">Gratis Service 7 Tahun</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#f1eeee] px-5 py-20 sm:px-8 sm:py-28">
            <div class="mx-auto max-w-5xl">
                <h2 class="text-center text-4xl font-extrabold uppercase tracking-wide text-[#34a9d7] sm:text-5xl">Daftar Harga</h2>
                <div class="mt-12 grid gap-8 md:grid-cols-2">
                    @foreach ($priceCards as $card)
                        <article class="rounded-3xl bg-white p-8 text-center shadow-xl transition duration-300 hover:-translate-y-2">
                            <h3 class="text-2xl font-extrabold uppercase tracking-wide text-slate-700">{{ $card['name'] }}</h3>
                            <p class="mt-6 text-4xl font-extrabold text-[#34a9d7]">{{ $card['price'] }}</p>
                            <ul class="mt-8 space-y-5 text-lg font-semibold text-slate-500">
                                @foreach ($card['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ $card['href'] }}" target="_blank" rel="noopener" class="mt-10 inline-flex h-14 items-center justify-center rounded-full {{ $card['active'] ? 'bg-[#34a9d7] text-white' : 'border border-[#34a9d7] text-[#34a9d7]' }} px-10 text-base font-extrabold uppercase tracking-wide transition hover:bg-[#1498cc] hover:text-white">Order</a>
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
