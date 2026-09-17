<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}">

        @include('partials.nunito-font')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-shell font-sans text-gray-900 antialiased" data-auth-motion>
        <a href="#main-content" class="skip-link">Lewati ke formulir utama</a>
        <main id="main-content" tabindex="-1" class="relative min-h-screen w-full max-w-full overflow-x-hidden p-3 sm:p-5">
            <nav class="auth-navigation absolute inset-x-0 top-0 z-20 flex items-center justify-between px-7 py-7 sm:px-10" aria-label="Navigasi autentikasi">
                <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center gap-3" aria-label="Kembali ke beranda">
                    <img src="{{ asset('images/coway_logo.png') }}" alt="" class="h-10 w-10 object-contain" aria-hidden="true">
                    <span class="text-sm font-black tracking-[-0.02em] text-white">Let's Grow Academy</span>
                </a>
                <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center border-b border-white/35 text-xs font-bold uppercase tracking-[0.16em] text-white transition hover:border-white">
                    Kembali ke situs
                </a>
            </nav>

            <div class="auth-stage relative grid min-h-[calc(100vh-1.5rem)] w-full overflow-hidden rounded-[2rem] lg:grid-cols-[minmax(0,1.08fr)_minmax(28rem,0.92fr)]">
                <aside class="auth-story relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-end lg:p-14 xl:p-20" aria-hidden="true">
                    <div class="absolute inset-0 bg-[url('/images/coway-product-hero-2026.webp')] bg-cover bg-[position:64%_bottom]"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(4,22,38,0.88),rgba(4,52,78,0.48)_56%,rgba(2,132,199,0.12))]"></div>
                    <div class="auth-story-copy relative max-w-3xl text-white">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-300">Sales workspace</p>
                        <h2 class="mt-5 text-[clamp(3.25rem,5.4vw,6.75rem)] font-black leading-[0.88] tracking-[-0.065em]">Tumbuh lebih jauh. Bersama.</h2>
                        <p class="mt-7 max-w-xl text-base leading-7 text-slate-200">Kelola penjualan, pantau perkembangan tim, dan akses materi pendukung dalam satu ruang kerja yang terarah.</p>
                    </div>
                </aside>

                <section class="auth-form-stage flex items-center justify-center bg-[#f4fafc] px-5 pb-10 pt-28 sm:px-10 lg:px-14 lg:pt-24">
                    {{ $slot }}
                </section>
            </div>
        </main>
    </body>
</html>
