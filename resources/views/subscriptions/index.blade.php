<x-dashboard-layout>
    @php
        $selectedMonths = (int) old('duration_months', 6);
    @endphp

    <div class="mx-auto max-w-6xl" x-data="{ months: {{ $selectedMonths }}, monthlyPrice: {{ $monthlyPrice }} }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-600">Premium access</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Subscription</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Aktifkan akses ke dokumen premium dan materi penjualan eksklusif.
                </p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600">
                <span class="h-2 w-2 rounded-full {{ $activeSubscription ? 'bg-emerald-500' : ($pendingSubscription ? 'bg-amber-500' : 'bg-slate-300') }}"></span>
                {{ $activeSubscription ? 'Subscriber aktif' : ($pendingSubscription ? 'Menunggu verifikasi' : 'Belum berlangganan') }}
            </span>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">{{ session('error') }}</div>
        @endif

        @if ($activeSubscription)
            <section class="mt-8 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-xl sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-amber-300/30 bg-amber-400/10 px-3 py-1 text-xs font-bold text-amber-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2.4 4.86 5.36.78-3.88 3.78.92 5.34L12 15.24 7.2 17.76l.92-5.34-3.88-3.78 5.36-.78L12 3z"/></svg>
                            Subscriber
                        </span>
                        <h2 class="mt-4 text-2xl font-extrabold">Akses premium kamu aktif</h2>
                        <p class="mt-2 text-sm text-slate-300">Berlaku sampai {{ $activeSubscription->ends_at->translatedFormat('d F Y, H.i') }} WIB</p>
                    </div>
                    <a href="{{ route('selling-kit.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-400 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                        Buka Selling Kit
                    </a>
                </div>
            </section>
        @elseif ($pendingSubscription)
            <section class="mt-8 rounded-3xl border border-amber-200 bg-amber-50 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-amber-950">Pembayaran sedang diperiksa</h2>
                        <p class="mt-1 text-sm leading-6 text-amber-900/75">
                            Konfirmasi {{ $pendingSubscription->duration_months }} bulan senilai Rp {{ number_format($pendingSubscription->amount, 0, ',', '.') }} sudah diterima.
                            Admin akan mengaktifkan akses setelah transfer terverifikasi.
                        </p>
                    </div>
                </div>
            </section>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langkah 1</p>
                            <h2 class="mt-1 text-xl font-extrabold text-slate-900">Pilih lama subscription</h2>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Rp {{ number_format($monthlyPrice, 0, ',', '.') }}/bulan</p>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        @foreach ($durations as $duration)
                            <button type="button" @click="months = {{ $duration }}"
                                :class="months === {{ $duration }} ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="min-h-28 cursor-pointer rounded-2xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <span class="block text-2xl font-extrabold text-slate-900">{{ $duration }}</span>
                                <span class="text-sm font-medium text-slate-500">bulan</span>
                                <span class="mt-3 block text-sm font-bold text-slate-800">Rp {{ number_format($duration * $monthlyPrice, 0, ',', '.') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-2xl bg-slate-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total transfer</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950" x-text="'Rp ' + (months * monthlyPrice).toLocaleString('id-ID')"></p>
                        <p class="mt-1 text-sm text-slate-500"><span x-text="months"></span> bulan × Rp {{ number_format($monthlyPrice, 0, ',', '.') }}</p>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langkah 2</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-900">Transfer & konfirmasi</h2>

                    <dl class="mt-6 space-y-4 rounded-2xl border border-slate-200 p-5">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Bank</dt>
                            <dd class="mt-1 font-extrabold text-slate-900">{{ $bank['name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Nomor rekening</dt>
                            <dd class="mt-1 select-all text-xl font-extrabold tracking-wide text-slate-900">{{ $bank['account_number'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Atas nama</dt>
                            <dd class="mt-1 font-bold text-slate-900">{{ $bank['account_holder'] }}</dd>
                        </div>
                    </dl>

                    <ol class="mt-5 space-y-3 text-sm leading-6 text-slate-600">
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">1.</span>Transfer sesuai total yang dipilih.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">2.</span>Simpan bukti transfer dari aplikasi bank kamu.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">3.</span>Unggah bukti transfer lalu kirim konfirmasi.</li>
                    </ol>

                    <form method="POST" action="{{ route('subscriptions.store') }}" enctype="multipart/form-data" class="mt-6">
                        @csrf
                        <input type="hidden" name="duration_months" :value="months">
                        <label for="payment_proof" class="block text-sm font-extrabold text-slate-800">Bukti transfer</label>
                        <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required
                            class="mt-2 block min-h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white text-sm text-slate-600 file:mr-4 file:min-h-11 file:border-0 file:bg-slate-100 file:px-4 file:text-sm file:font-bold file:text-slate-700 hover:file:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-2 text-xs text-slate-400">Format JPG, PNG, WEBP, atau PDF. Maksimal 5 MB.</p>
                        @error('payment_proof')
                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="mt-4 min-h-11 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Saya Sudah Transfer
                        </button>
                    </form>
                    <p class="mt-3 text-center text-xs leading-5 text-slate-400">Dengan mengonfirmasi, kamu menyatakan transfer sudah dilakukan.</p>
                </section>
            </div>
        @endif
    </div>
</x-dashboard-layout>
