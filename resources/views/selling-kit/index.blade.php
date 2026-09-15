<x-dashboard-layout>
    <div class="mx-auto max-w-6xl">
        <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-7 text-white shadow-xl sm:p-10">
            <span class="inline-flex rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300">Subscriber only</span>
            <div class="mt-5 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-xs font-extrabold uppercase tracking-[0.2em] text-sky-400">Resource library</p><h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Selling Kit</h1><p class="mt-3 max-w-2xl text-sm font-medium leading-7 text-slate-300 sm:text-base">Pilih galeri sesuai kebutuhan Anda. Dokumen akan ditampilkan setelah kategori dibuka.</p></div>
                <div class="inline-flex w-fit items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-bold text-slate-200"><svg class="h-5 w-5 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>3 galeri · 6 dokumen</div>
            </div>
        </div>

        <section class="mt-8" aria-labelledby="gallery-title">
            <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Pilih kategori</p><h2 id="gallery-title" class="mt-1 text-2xl font-extrabold text-slate-950">Galeri Selling Kit</h2></div></div>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($categories as $category)
                    @php
                        $theme = match ($category['color']) {
                            'emerald' => ['surface' => 'from-emerald-500 to-teal-700', 'soft' => 'bg-emerald-400/15 text-emerald-100', 'pattern' => 'text-emerald-300'],
                            'violet' => ['surface' => 'from-violet-600 to-indigo-800', 'soft' => 'bg-violet-300/15 text-violet-100', 'pattern' => 'text-violet-300'],
                            default => ['surface' => 'from-sky-500 to-blue-700', 'soft' => 'bg-sky-300/15 text-sky-100', 'pattern' => 'text-sky-300'],
                        };
                    @endphp
                    <a href="{{ route('selling-kit.category', $category['slug']) }}" class="group relative min-h-80 cursor-pointer overflow-hidden rounded-3xl bg-gradient-to-br {{ $theme['surface'] }} p-6 text-white shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 sm:p-7">
                        <svg class="pointer-events-none absolute -bottom-12 -right-12 h-56 w-56 {{ $theme['pattern'] }} opacity-15 transition duration-300 group-hover:-translate-x-2 group-hover:-translate-y-2" viewBox="0 0 200 200" fill="none" aria-hidden="true"><rect x="28" y="20" width="116" height="150" rx="14" stroke="currentColor" stroke-width="9"/><path d="M58 60h58M58 90h58M58 120h38" stroke="currentColor" stroke-width="9" stroke-linecap="round"/><path d="m116 148 48-48" stroke="currentColor" stroke-width="9" stroke-linecap="round"/></svg>
                        <div class="relative flex h-full flex-col">
                            <div class="flex items-start justify-between gap-4"><span class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $theme['soft'] }} backdrop-blur"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg></span><span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-extrabold backdrop-blur">{{ count($category['documents']) }} dokumen</span></div>
                            <div class="mt-auto pt-16"><h3 class="max-w-xs text-2xl font-extrabold leading-tight">{{ $category['name'] }}</h3><p class="mt-3 max-w-sm text-sm font-medium leading-6 text-white/80">{{ $category['description'] }}</p><span class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 text-sm font-extrabold text-slate-900 shadow-sm">Buka Galeri<svg class="h-4 w-4 transition duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-dashboard-layout>
