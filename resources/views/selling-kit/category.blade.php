<x-dashboard-layout>
    @php
        $styles = match ($category['color']) {
            'emerald' => ['badge' => 'bg-emerald-100 text-emerald-700', 'icon' => 'bg-emerald-50 text-emerald-600', 'button' => 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'],
            'violet' => ['badge' => 'bg-violet-100 text-violet-700', 'icon' => 'bg-violet-50 text-violet-600', 'button' => 'border-violet-200 text-violet-700 hover:bg-violet-50'],
            default => ['badge' => 'bg-sky-100 text-sky-700', 'icon' => 'bg-sky-50 text-sky-600', 'button' => 'border-sky-200 text-sky-700 hover:bg-sky-50'],
        };
    @endphp
    <div class="mx-auto max-w-6xl">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500"><a href="{{ route('selling-kit.index') }}" class="cursor-pointer transition hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500">Selling Kit</a><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg><span class="text-slate-900" aria-current="page">{{ $category['name'] }}</span></nav>

        <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4"><span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $styles['icon'] }}"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg></span><div><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $styles['badge'] }}">{{ $category->documents->count() }} dokumen</span><h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $category['name'] }}</h1><p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-500">{{ $category['description'] }}</p></div></div>
                <a href="{{ route('selling-kit.index') }}" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Kembali ke Galeri</a>
            </div>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-2" aria-label="Dokumen {{ $category['name'] }}">
            @foreach ($category['documents'] as $document)
                <article class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:border-slate-300 hover:shadow-lg">
                    <div class="flex items-start gap-4"><span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-red-50 px-2 text-center text-[10px] font-black text-red-600">{{ $document->type_label }}</span><div class="min-w-0"><h2 class="text-lg font-extrabold text-slate-950">{{ $document['title'] }}</h2><p class="mt-1 text-xs font-bold text-slate-400">{{ strtoupper($document->extension) }} · {{ $document->formatted_size }}</p></div></div>
                    <p class="mt-5 flex-1 text-sm font-medium leading-6 text-slate-600">{{ $document['description'] }}</p>
                    <div class="mt-6 grid grid-cols-2 gap-3"><a href="{{ route('selling-kit.show', $document['slug']) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border bg-white px-4 text-sm font-extrabold transition focus:outline-none focus:ring-2 focus:ring-sky-500 {{ $styles['button'] }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>Lihat</a><a href="{{ route('selling-kit.download', $document['slug']) }}" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-extrabold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 21h14"/></svg>Unduh</a></div>
                </article>
            @endforeach
        </section>
    </div>
</x-dashboard-layout>
