<!DOCTYPE html>
<html lang="id">
@php
    $consultantName = $consultant->full_name ?: $consultant->name;
    $products = [
        ['name' => 'Coway Neo Plus', 'model' => 'CHP-264L', 'image' => asset('images/water-neo-plus-chp-264l.webp'), 'prices' => [3 => '380.000', 5 => '320.000', 6 => '290.000', 7 => '270.000'], 'features' => ['Panas, dingin & biasa', 'Kapasitas tangki 5,8 liter', 'Eco Mode hemat listrik', 'Pengunci air panas aman untuk anak']],
        ['name' => 'Coway Ombak', 'model' => 'CHP-7310R', 'image' => asset('images/water-ombak-chp-7310r.webp'), 'prices' => [3 => '550.000', 5 => '450.000', 6 => '410.000', 7 => '390.000'], 'features' => ['4 pilihan suhu air', 'Kapasitas tangki 13,5 liter', 'RO Filtration System', 'Cocok untuk keluarga besar']],
        ['name' => 'Coway Core', 'model' => 'CHP-671R', 'image' => asset('images/water-core-chp-671r.webp'), 'prices' => [3 => '570.000', 5 => '450.000', 6 => '430.000', 7 => '410.000'], 'features' => ['Panas, dingin & biasa', 'Kapasitas tangki 21,1 liter', 'Floor standing design', 'RO Filtration System']],
    ];
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Konsultasi dan informasi produk pemurni air Coway bersama {{ $consultantName }}.">
    <title>Solusi Air Murni Coway | {{ $consultantName }}</title>
    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-white font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-[72px] max-w-6xl items-center justify-between px-5 sm:px-8">
            <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-8 w-auto">
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="cursor-pointer rounded-full bg-[#09a8e8] px-5 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#078bc0] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Konsultasi Gratis</a>
        </div>
    </header>

    <main>
        <section class="relative isolate overflow-hidden bg-gradient-to-b from-sky-50 to-white px-5 pb-20 pt-14 text-center sm:px-8 sm:pt-20">
            <div class="pointer-events-none absolute left-1/2 top-12 -z-10 h-80 w-80 -translate-x-1/2 rounded-full bg-sky-200/50 blur-3xl"></div>
            <div class="mx-auto max-w-3xl">
                <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#08a5df]">Air murni untuk keluarga</p>
                <h1 class="mt-5 text-4xl font-black leading-tight tracking-tight text-[#090d29] sm:text-6xl">Solusi Cerdas Air Minum Tanpa Repot Angkat Galon</h1>
                <p class="mx-auto mt-6 max-w-2xl text-base font-semibold leading-8 text-slate-600 sm:text-lg">Nikmati air murni higienis setiap hari, dengan cicilan bulanan yang sudah termasuk penggantian filter dan layanan HEART Service berkala.</p>
                <div class="mx-auto mt-10 flex max-w-2xl items-end justify-center gap-2 sm:gap-8">
                    <img src="{{ asset('images/water-neo-plus-chp-264l.webp') }}" alt="Coway Neo Plus" class="h-56 w-auto object-contain drop-shadow-xl sm:h-80">
                    <img src="{{ asset('images/water-ombak-chp-7310r.webp') }}" alt="Coway Ombak" class="h-64 w-auto object-contain drop-shadow-xl sm:h-96">
                </div>
                <a href="#calculator" class="mt-9 inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-8 text-sm font-extrabold text-white shadow-lg shadow-sky-200 transition hover:-translate-y-0.5 hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Hitung Penghematan Anda</a>
            </div>
        </section>

        <section id="calculator" class="px-5 py-20 sm:px-8" x-data="{ gallons: 15, gallonPrice: 20000, cowayPrice: 270000 }">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#08a5df]">Bandingkan biaya</p>
                <h2 class="mt-4 text-3xl font-black leading-tight text-[#090d29] sm:text-5xl">Dan Nikmati Air Murni Higienis untuk Keluarga Anda</h2>
                <p class="mx-auto mt-5 max-w-2xl font-semibold leading-7 text-slate-500">Geser jumlah galon sesuai konsumsi bulanan keluarga Anda dan lihat potensi hematnya.</p>
            </div>
            <div class="mx-auto mt-10 max-w-2xl rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60 sm:p-8">
                <h3 class="flex items-center gap-2 border-b border-slate-100 pb-5 text-xl font-black text-[#090d29]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 10h2m2 0h2m2 0h.01M8 14h2m2 0h2m2 0h.01M8 18h2m2 0h2m2 0h.01"/></svg>
                    Kalkulator Hemat Galon
                </h3>
                <label for="gallons" class="mt-7 block text-sm font-bold text-slate-700">Konsumsi Galon Keluarga Anda (Per Bulan):</label>
                <input id="gallons" type="range" min="5" max="45" step="5" x-model.number="gallons" class="mt-4 w-full cursor-pointer accent-[#0ca9e8]" aria-describedby="gallon-value">
                <div class="mt-2 flex justify-between text-sm font-extrabold text-[#087cba]"><span>5 Galon</span><span id="gallon-value" x-text="gallons + ' Galon'"></span><span>45 Galon</span></div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-rose-700">Beli Air Galon</p>
                        <p class="mt-4 text-3xl font-black text-rose-600">Rp <span x-text="(gallons * gallonPrice).toLocaleString('id-ID')"></span></p>
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Per bulan, estimasi Rp20.000/galon</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">Coway Neo Plus</p>
                        <p class="mt-4 text-3xl font-black text-emerald-600">Rp <span x-text="cowayPrice.toLocaleString('id-ID')"></span></p>
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Per bulan, sudah termasuk filter &amp; servis</p>
                    </div>
                </div>
                <div class="mt-5 rounded-2xl border border-dashed border-emerald-300 bg-emerald-50/60 p-5 text-center font-black text-emerald-700" x-show="(gallons * gallonPrice) > cowayPrice">
                    Potensi hemat Rp <span x-text="((gallons * gallonPrice) - cowayPrice).toLocaleString('id-ID')"></span> / bulan
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-6 flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-6 text-sm font-extrabold text-white transition hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Konsultasikan Kebutuhan Saya</a>
            </div>
        </section>

        <section class="overflow-hidden bg-white px-5 py-20 sm:px-8 sm:py-28">
            <div class="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-[.9fr_1.1fr]">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-emerald-600">Lebih leluasa setiap hari</p>
                    <h2 class="mt-4 text-3xl font-black leading-tight text-[#090d29] sm:text-5xl">Dapatkan Kualitas &amp; Kuantitas Berlipat</h2>
                    <p class="mt-6 font-semibold leading-8 text-slate-600">Membeli air galon eceran sering membuat keluarga membatasi konsumsi karena khawatir cepat habis. Dengan pemurni air, kebutuhan minum dan memasak dapat mengalir langsung dari sumber air rumah yang telah melalui proses filtrasi.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['Pure Water', 'Air terfiltrasi untuk minum, memasak nasi, sup, dan kebutuhan keluarga lainnya.'],
                        ['Suhu Instan', 'Pilihan air panas, dingin, atau suhu normal tersedia tanpa dispenser terpisah.'],
                        ['HEART Service', 'Kunjungan perawatan serta penggantian filter berkala sesuai paket layanan.'],
                    ] as [$title, $body])
                        <article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 transition duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                            </span>
                            <h3 class="mt-5 text-lg font-black text-slate-900">{{ $title }}</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#05091e] px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="pointer-events-none absolute -right-24 top-20 h-72 w-72 rounded-full bg-sky-500/10 blur-3xl"></div>
            <div class="relative mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-amber-400">Beban yang sering terlupakan</p>
                    <h2 class="mt-4 text-3xl font-black leading-tight sm:text-5xl">Tega Lihat Orang Tua Mengangkat Galon 19 kg?</h2>
                    <p class="mt-5 font-semibold leading-7 text-slate-300">Kurangi pekerjaan berat dan risiko terpeleset saat mengganti galon, terutama bagi orang tua yang kekuatan fisiknya sudah menurun.</p>
                </div>
                <div class="mt-12 grid items-center gap-10 lg:grid-cols-[1.1fr_.9fr]">
                    <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900 shadow-2xl">
                        <img src="{{ asset('images/landing-elderly-gallon.jpg') }}" alt="Seorang ibu lanjut usia kesulitan mengangkat galon air besar di dapur" class="aspect-[4/3] h-full w-full object-cover" loading="lazy">
                    </div>
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-[0.2em] text-sky-400">Peduli dimulai dari rumah</p>
                        <h3 class="mt-4 text-3xl font-black leading-tight">Lindungi Punggung &amp; Sendi Orang Tua Kita</h3>
                        <p class="mt-5 font-semibold leading-8 text-slate-300">Mengangkat beban berat berulang kali dapat membebani punggung dan persendian. Pemurni air yang terhubung langsung ke sumber air membantu menghilangkan kebutuhan mengangkat dan membalik galon.</p>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-8 inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl bg-amber-500 px-7 text-sm font-extrabold text-slate-950 transition duration-200 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-[#05091e]">Bebaskan Orang Tua dari Galon</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-[#fffdf9] px-5 py-20 sm:px-8 sm:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-amber-600">Praktis untuk keluarga muda</p>
                    <h2 class="mt-4 text-3xl font-black leading-tight text-[#090d29] sm:text-5xl">Menyiapkan Susu di Tengah Malam? Lebih Praktis dengan Suhu Presisi</h2>
                    <p class="mt-5 font-semibold leading-7 text-slate-600">Tidak perlu lagi merebus air lalu menunggu terlalu lama. Pilih suhu air sesuai kebutuhan dan tetap ikuti petunjuk penyajian pada kemasan susu formula.</p>
                </div>
                <div class="mt-12 grid items-center gap-10 lg:grid-cols-2">
                    <img src="{{ asset('images/landing-baby-formula.jpg') }}" alt="Ibu menyiapkan botol susu bayi menggunakan air hangat dari pemurni air pada malam hari" class="aspect-[4/3] w-full rounded-[2rem] object-cover shadow-xl shadow-amber-100" loading="lazy">
                    <div class="rounded-[2rem] border border-amber-100 bg-white p-7 shadow-sm sm:p-9">
                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-amber-700">Pilihan suhu hingga 40°C*</span>
                        <h3 class="mt-5 text-3xl font-black leading-tight text-slate-950">Air Hangat Konsisten untuk Rutinitas Si Kecil</h3>
                        <p class="mt-5 font-semibold leading-8 text-slate-600">Pada model dengan kontrol multi-suhu, air hangat dapat tersedia dalam hitungan detik. Lebih tenang saat anak terbangun lapar, tanpa menebak-nebak campuran air panas dan dingin.</p>
                        <p class="mt-4 text-xs font-semibold leading-5 text-slate-400">*Ketersediaan pilihan suhu berbeda menurut model. Pastikan suhu dan cara penyajian mengikuti petunjuk produsen susu formula.</p>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-7 inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-6 text-sm font-extrabold text-white transition duration-200 hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Tanyakan Model dengan Multi-Suhu</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-[#05091e] px-5 py-20 text-white sm:px-8 sm:py-28">
            <div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-2">
                <div class="lg:order-2">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-rose-400">Bahaya yang tak kasatmata</p>
                    <h2 class="mt-4 text-3xl font-black leading-tight sm:text-5xl">Yakin Tangki Dispenser Lama Masih Bersih?</h2>
                    <p class="mt-6 font-semibold leading-8 text-slate-300">Ruang tangki, leher galon, dan selang yang lembap perlu dibersihkan rutin. Jika jarang dirawat, endapan dan pertumbuhan mikroorganisme dapat muncul pada area yang sulit terlihat.</p>
                    <div class="mt-7 rounded-2xl border border-sky-400/20 bg-sky-400/10 p-5">
                        <h3 class="font-black text-sky-300">Perawatan profesional berkala</h3>
                        <p class="mt-2 text-sm font-semibold leading-7 text-slate-300">HEART Service membantu menjaga kebersihan unit melalui pemeriksaan, sanitasi, dan penggantian filter sesuai jadwal layanan.</p>
                    </div>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-8 inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-7 text-sm font-extrabold text-white transition duration-200 hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-[#05091e]">Konsultasi Sistem Perawatan</a>
                </div>
                <div class="overflow-hidden rounded-[2rem] border border-white/10 shadow-2xl lg:order-1">
                    <img src="{{ asset('images/landing-dirty-dispenser.jpg') }}" alt="Ilustrasi bagian dalam tangki dispenser lama yang memiliki endapan dan perlu dibersihkan" class="aspect-[4/3] h-full w-full object-cover" loading="lazy">
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-gradient-to-b from-sky-50 to-white px-5 py-20 sm:px-8 sm:py-28">
            <div class="mx-auto max-w-5xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#087cba]">Kurangi ketergantungan pada kemasan</p>
                <h2 class="mx-auto mt-4 max-w-4xl text-3xl font-black leading-tight text-[#090d29] sm:text-5xl">Pilihan Air Minum dengan Lebih Sedikit Kontak Plastik Galon</h2>
                <p class="mx-auto mt-6 max-w-3xl font-semibold leading-8 text-slate-600">Galon guna ulang dapat mengalami proses distribusi, penyimpanan, dan paparan lingkungan yang beragam. Pemurni air mengolah air langsung dari sumber rumah sehingga membantu mengurangi ketergantungan pada kemasan galon berulang.</p>
                <div class="mt-10 grid gap-5 text-left sm:grid-cols-3">
                    @foreach ([
                        ['Filtrasi Berlapis', 'Sistem filtrasi dirancang untuk membantu mengurangi berbagai kontaminan sesuai spesifikasi model.'],
                        ['Tanpa Angkat Galon', 'Tidak perlu menyimpan, mengangkat, dan membalik kemasan galon besar berulang kali.'],
                        ['Air Sesuai Kebutuhan', 'Nikmati pasokan air untuk minum dan memasak tanpa menunggu galon berikutnya.'],
                    ] as [$title, $body])
                        <article class="rounded-3xl border border-sky-100 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-black text-slate-950">{{ $title }}</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-9 inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-8 text-sm font-extrabold text-white transition duration-200 hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Ganti Cara Minum Keluarga Anda</a>
            </div>
        </section>

        <section id="products" class="px-5 py-20 sm:px-8 sm:py-28">
            <div class="mx-auto max-w-6xl" x-data="{ periods: {0: 7, 1: 6, 2: 7} }">
                <div class="text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#08a5df]">Produk kami</p>
                    <h2 class="mt-4 text-3xl font-black leading-tight text-[#090d29] sm:text-5xl">Pilih Unit Coway Terbaik untuk Kebutuhan Anda</h2>
                    <p class="mx-auto mt-5 max-w-2xl font-semibold leading-7 text-slate-500">Cicilan sudah termasuk instalasi, kunjungan servis berkala, serta penggantian filter sesuai jadwal.</p>
                </div>
                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    @foreach ($products as $index => $product)
                        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60">
                            <div class="flex h-64 items-end justify-center bg-gradient-to-b from-sky-50 to-white px-8 pt-8"><img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="max-h-56 w-auto object-contain drop-shadow-lg" loading="lazy"></div>
                            <div class="p-6">
                                <p class="text-xs font-extrabold uppercase tracking-widest text-[#08a5df]">{{ $product['model'] }}</p>
                                <h3 class="mt-2 text-2xl font-black text-[#090d29]">{{ $product['name'] }}</h3>
                                <div class="mt-5 grid grid-cols-4 rounded-xl bg-slate-50 p-1" aria-label="Pilihan masa cicilan">
                                    @foreach ($product['prices'] as $years => $price)
                                        <button type="button" x-on:click="periods[{{ $index }}] = {{ $years }}" x-bind:class="periods[{{ $index }}] === {{ $years }} ? 'bg-white text-[#087cba] shadow-sm' : 'text-slate-500'" class="min-h-10 cursor-pointer rounded-lg text-xs font-extrabold transition focus:outline-none focus:ring-2 focus:ring-sky-500">{{ $years }} Th</button>
                                    @endforeach
                                </div>
                                <p class="mt-6 text-xs font-bold uppercase tracking-wide text-slate-500">Cicilan</p>
                                @foreach ($product['prices'] as $years => $price)
                                    <p x-show="periods[{{ $index }}] === {{ $years }}" class="mt-1 text-3xl font-black text-[#087cba]">Rp {{ $price }}<span class="text-sm font-bold text-slate-500">/bulan</span></p>
                                @endforeach
                                <ul class="mt-6 space-y-3 border-t border-slate-100 pt-6 text-sm font-semibold text-slate-600">
                                    @foreach ($product['features'] as $feature)
                                        <li class="flex gap-3"><svg class="mt-0.5 h-4 w-4 shrink-0 text-[#08a5df]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-7 flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-[#08a5df] px-4 text-sm font-extrabold text-white transition hover:bg-[#078fc2] focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Tanyakan Promo {{ str_replace('Coway ', '', $product['name']) }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-slate-50 px-5 py-20 sm:px-8 sm:py-28" x-data="{ open: 0 }">
            <div class="mx-auto max-w-3xl">
                <div class="text-center"><p class="text-xs font-extrabold uppercase tracking-[0.25em] text-[#08a5df]">FAQ</p><h2 class="mt-4 text-3xl font-black text-[#090d29] sm:text-5xl">Pertanyaan yang Sering Diajukan</h2></div>
                <div class="mt-10 divide-y divide-slate-200 rounded-3xl border border-slate-200 bg-white px-6 shadow-sm">
                    @foreach ([
                        ['Apakah cicilan sudah termasuk instalasi dan filter?', 'Ya. Paket mencakup pemasangan unit, kunjungan HEART Service berkala, serta penggantian filter sesuai jadwal selama masa paket.'],
                        ['Bagaimana setelah masa cicilan berakhir?', 'Setelah masa cicilan selesai, unit menjadi milik Anda. Anda dapat melanjutkan layanan perawatan melalui paket filter yang tersedia.'],
                        ['Apakah air bisa langsung diminum?', 'Sistem filtrasi Coway dirancang untuk menghasilkan air minum yang praktis langsung dari unit. Kondisi sumber air dan instalasi akan dikonsultasikan terlebih dahulu.'],
                    ] as $index => [$question, $answer])
                        <div>
                            <button type="button" x-on:click="open = open === {{ $index }} ? -1 : {{ $index }}" class="flex min-h-20 w-full cursor-pointer items-center justify-between gap-5 py-5 text-left text-base font-black text-[#090d29] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-sky-500" x-bind:aria-expanded="open === {{ $index }}"><span>{{ $question }}</span><svg class="h-5 w-5 shrink-0 text-[#08a5df] transition" x-bind:class="open === {{ $index }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>
                            <div x-show="open === {{ $index }}" x-transition.opacity class="pb-6 text-sm font-semibold leading-7 text-slate-600">{{ $answer }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-[#08a5df] px-5 py-16 text-center text-white sm:px-8">
            <div class="mx-auto max-w-3xl"><h2 class="text-3xl font-black sm:text-5xl">Masih Bingung Memilih?</h2><p class="mt-5 font-semibold leading-7 text-sky-50">Konsultasikan kebutuhan keluarga Anda langsung bersama {{ $consultantName }}.</p><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="mt-8 inline-flex min-h-12 cursor-pointer items-center justify-center rounded-xl bg-white px-8 text-sm font-extrabold text-[#087cba] shadow-lg transition hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#08a5df]">Chat WhatsApp Sekarang</a></div>
        </section>
    </main>

    <footer class="bg-[#05091e] px-5 py-10 text-center text-sm font-semibold text-slate-400"><img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="mx-auto h-7 w-auto brightness-0 invert"><p class="mt-4">Informasi produk bersama {{ $consultantName }}</p></footer>

    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp dengan {{ $consultantName }}" class="fixed bottom-5 right-5 z-50 flex h-14 w-14 cursor-pointer items-center justify-center rounded-full bg-[#21d46b] text-white shadow-xl shadow-emerald-300/50 transition hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:bottom-7 sm:right-7">
        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.4-4.7a8.5 8.5 0 1 1 16.1-4.1Z"/><path d="M8.2 7.7c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.8 1.8c.1.3.1.5-.1.7l-.6.7c-.2.2-.1.4 0 .6.7 1.2 1.7 2.2 3 2.8.2.1.4.1.6-.1l.8-1c.2-.2.4-.3.7-.2l1.9.9c.3.1.4.3.4.5 0 .5-.2 1.5-1.1 2.1-.7.5-1.6.7-2.6.4-1.1-.3-2.5-.8-4.2-2.3-1.4-1.2-2.4-2.8-2.7-3.4-.3-.6-1.9-3.7.4-3.4Z"/></svg>
    </a>
</body>
</html>
