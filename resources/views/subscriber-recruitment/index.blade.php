<x-dashboard-layout>
    <div class="mx-auto max-w-6xl" x-data="recruitmentShare">
        @include('subscriber-recruitment.menu')
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-10 p-6 sm:p-8 lg:grid-cols-[1.1fr_.9fr] lg:p-10">
                <div>
                    <x-subscriber-badge />
                    <p class="mt-6 text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Kembangkan tim Anda</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Landing Page Rekrutmen Anda</h1>
                    <p class="mt-4 max-w-xl text-sm font-medium leading-7 text-slate-600 sm:text-base">Bagikan peluang kemitraan melalui halaman personal Anda. Calon mitra dapat mengenal bisnis, mengisi form minat, lalu menunggu konfirmasi event dari admin.</p>
                    <ol class="mt-7 space-y-4">
                        @foreach ([['Bagikan link personal', 'Link menggunakan nama dan kode member Anda.'], ['Kenalkan peluangnya', 'Materi, alur bisnis, dukungan, dan FAQ dalam satu halaman.'], ['Pantau pendaftaran masuk', 'Data form tersimpan di Daftar Peminat milik Anda, lalu dapat ditindaklanjuti melalui WhatsApp.']] as [$title, $description])
                            <li class="flex items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-xs font-extrabold text-sky-700">0{{ $loop->iteration }}</span>
                                <div><p class="text-sm font-extrabold text-slate-900">{{ $title }}</p><p class="mt-1 text-xs font-medium leading-5 text-slate-500">{{ $description }}</p></div>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="relative flex flex-col justify-between overflow-hidden rounded-3xl bg-[#082d4f] p-7 text-white sm:p-9">
                    <svg class="pointer-events-none absolute -bottom-20 -right-20 h-80 w-80 text-sky-300/10" viewBox="0 0 200 200" fill="none" aria-hidden="true"><circle cx="100" cy="100" r="94" stroke="currentColor"/><circle cx="100" cy="100" r="70" stroke="currentColor"/><circle cx="100" cy="100" r="45" stroke="currentColor"/></svg>
                    <p class="relative text-[10px] font-extrabold uppercase tracking-[0.2em] text-sky-200">Your next chapter</p>
                    <div class="relative py-8">
                        <p class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Lebih banyak pilihan.<br><span class="text-sky-300">Lebih banyak ruang untuk tumbuh.</span></p>
                        <p class="mt-5 text-sm leading-6 text-sky-100/80">Mulai dari mengenal peluang. Bangun langkah berikutnya bersama tim Anda.</p>
                    </div>
                    <div class="relative border-t border-white/15 pt-5"><p class="text-xs text-sky-200">Pendamping calon mitra</p><p class="mt-1 font-extrabold">{{ $consultant->full_name ?: $consultant->name }}</p></div>
                </div>
            </div>

            @if ($canPublish)
                <div class="border-t border-slate-200 bg-slate-50 p-6 sm:p-8 lg:px-10">
                    <label for="recruitment-landing-url" class="text-sm font-extrabold text-slate-900">Link Landing Page Rekrutmen Anda</label>
                    <p class="mt-1 text-xs font-medium leading-5 text-slate-500">Pendaftaran dari link ini tersimpan di Daftar Peminat Anda. Kontak WhatsApp pada halaman: {{ $consultant->phone_number }}.</p>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <input x-ref="link" id="recruitment-landing-url" type="text" readonly value="{{ $landingPageUrl }}" class="min-h-12 min-w-0 flex-1 rounded-xl border-slate-200 bg-white text-sm font-semibold text-slate-700 focus:border-sky-500 focus:ring-sky-500">
                        <button type="button" @click="copy" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-sky-200 bg-white px-5 text-sm font-extrabold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500"><span x-text="copied ? 'Tersalin' : 'Salin Link'">Salin Link</span></button>
                        <a href="{{ $landingPageUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-extrabold text-white transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Lihat Landing Page <span aria-hidden="true">↗</span></a>
                    </div>
                    <p x-text="status" role="status" class="mt-2 text-xs font-semibold text-sky-700"></p>
                </div>
                <div class="border-t border-slate-200 p-6 sm:p-8 lg:px-10">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-600">Preview</p><h2 class="mt-1 text-2xl font-extrabold text-slate-950">Tampilan Landing Page Rekrutmen</h2><p class="mt-2 text-sm text-slate-500">Halaman yang akan dilihat calon mitra ketika membuka link Anda.</p></div>
                        <span class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700">Halaman aktif</span>
                    </div>
                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                        <div class="flex h-10 items-center gap-2 border-b border-slate-200 bg-slate-50 px-4" aria-hidden="true"><span class="h-2 w-2 rounded-full bg-slate-300"></span><span class="h-2 w-2 rounded-full bg-slate-300"></span><span class="h-2 w-2 rounded-full bg-slate-300"></span><span class="ml-3 truncate text-[10px] font-semibold text-slate-500">{{ $landingPageUrl }}</span></div>
                        <iframe src="{{ $landingPageUrl }}" title="Preview Landing Page Rekrutmen {{ $consultant->name }}" loading="lazy" class="h-[560px] w-full bg-white sm:h-[680px]"></iframe>
                    </div>
                </div>
            @else
                <div class="border-t border-slate-200 bg-slate-50 p-6 sm:p-8 lg:px-10">
                    <div class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-extrabold text-amber-900">Lengkapi nomor WhatsApp terlebih dahulu</p><p class="mt-1 text-sm leading-6 text-amber-800">Tambahkan nomor WhatsApp Indonesia yang valid di profil agar halaman rekrutmen Anda aktif.</p></div>
                        <a href="{{ route('profile') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-sky-600 px-5 text-sm font-extrabold text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Lengkapi Profil</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
