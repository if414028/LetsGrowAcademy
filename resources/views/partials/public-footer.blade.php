<footer class="public-site-footer bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-[1440px] gap-10 px-5 py-12 sm:px-8 lg:grid-cols-[1fr_auto] lg:items-end xl:px-24">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center" aria-label="Kembali ke beranda Coway">
                <img src="{{ asset('images/coway-logo.png') }}" alt="Coway" class="h-8 w-auto brightness-0 invert">
            </a>
            <p class="mt-4 max-w-md text-sm font-semibold leading-6 text-slate-400">Informasi produk, layanan, dan alat bantu digital untuk mendukung aktivitas Sales Coway.</p>
        </div>
        <nav class="flex flex-wrap gap-x-6 gap-y-3 text-xs font-extrabold uppercase tracking-[0.14em]" aria-label="Navigasi footer">
            <a href="{{ route('home') }}#products" class="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400">Produk</a>
            <a href="{{ route('home') }}#services" class="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400">Services</a>
            <a href="{{ route('support') }}" class="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400">Dukungan</a>
            <a href="{{ route('subscriptions.public') }}" class="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400">Subscription</a>
            <a href="{{ route('login') }}" class="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400">Masuk</a>
        </nav>
    </div>
    <div class="border-t border-white/10 px-5 py-5 text-center text-xs font-semibold text-slate-500">Coway Sales Digital Platform</div>
</footer>
