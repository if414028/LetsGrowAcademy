<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $page['description'] }}">
    <title>{{ $page['title'] }} | Coway</title>

    @include('partials.nunito-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
    @include('partials.public-navbar', ['sectionBase' => url('/')])

    <main>
        <section class="relative overflow-hidden pt-[72px]">
            <div class="absolute inset-0">
                <img src="{{ $page['image'] }}" alt="{{ $page['title'] }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-slate-950/55"></div>
            </div>
            <div class="relative mx-auto flex min-h-[560px] max-w-[1440px] items-center px-5 py-20 sm:px-8 xl:px-24">
                <div class="max-w-3xl text-white">
                    <p class="text-sm font-bold uppercase tracking-[0.35em] text-sky-200">About Coway</p>
                    <h1 class="mt-5 text-5xl font-bold leading-tight sm:text-7xl">{{ $page['title'] }}</h1>
                    <p class="mt-5 text-xl font-semibold text-sky-100">{{ $page['kicker'] }}</p>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-white/90">{{ $page['description'] }}</p>
                </div>
            </div>
        </section>

        <section class="bg-white py-20">
            <div class="mx-auto grid max-w-[1180px] gap-12 px-5 sm:px-8 lg:grid-cols-[0.75fr_1.25fr]">
                <aside>
                    <p class="text-sm font-bold uppercase tracking-[0.28em] text-[#00a4e4]">Menu About Coway</p>
                    <div class="mt-5 overflow-hidden rounded bg-[#34a9d7] py-3 text-white shadow-xl">
                        @foreach ($aboutPages as $item)
                            <a href="{{ route('about-coway.show', $item['slug']) }}" class="block px-6 py-3 text-sm font-bold transition hover:bg-white/15 {{ $item['slug'] === $page['slug'] ? 'bg-white/20' : '' }}">
                                {{ $item['nav'] }}
                            </a>
                        @endforeach
                    </div>
                </aside>

                <div>
                    <div class="grid gap-5 sm:grid-cols-3">
                        @foreach ($page['highlights'] as $highlight)
                            <div class="rounded bg-slate-50 p-5 ring-1 ring-slate-200">
                                <div class="mb-4 h-2 w-14 rounded-full bg-[#00a4e4]"></div>
                                <p class="font-bold text-slate-950">{{ $highlight }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10 space-y-6">
                        @foreach ($page['sections'] as $section)
                            <article class="rounded bg-white p-7 shadow-sm ring-1 ring-slate-200">
                                <h2 class="text-2xl font-bold text-slate-950">{{ $section['title'] }}</h2>
                                <p class="mt-4 text-lg leading-8 text-slate-600">{{ $section['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-10 rounded bg-[#7dacd3] p-8 text-white">
                        <p class="text-sm font-bold uppercase tracking-[0.28em] text-sky-50">Materi Sales</p>
                        <h2 class="mt-3 text-3xl font-bold">Gunakan halaman ini untuk memperkuat cerita brand Coway.</h2>
                        <p class="mt-4 leading-7 text-sky-50">Konten ini diringkas dan disusun ulang dari referensi Coway JKT agar tim lebih mudah menjelaskan latar belakang, reputasi, dan nilai perusahaan kepada pelanggan.</p>
                        <a href="{{ $page['source'] }}" target="_blank" rel="noopener" class="mt-7 inline-flex h-12 items-center justify-center rounded-full bg-white px-8 text-sm font-bold uppercase tracking-wide text-[#00a4e4] transition hover:bg-sky-50">Buka Referensi Coway JKT</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
