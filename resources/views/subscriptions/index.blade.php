<x-dashboard-layout>
    @php
        $selectedMonths = (int) old('duration_months', 6);
    @endphp

    <div class="mx-auto max-w-6xl" x-data="{ months: {{ $selectedMonths }}, monthlyPrice: {{ $monthlyPrice }} }">
        @if (session('payment_success'))
            <div x-data="{ open: true }" x-show="open" x-cloak
                @keydown.escape.window="open = false"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="payment-success-title"
                aria-describedby="payment-success-description">
                <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm"
                    @click="open = false" aria-hidden="true"></div>

                <div x-show="open"
                    x-transition:enter="transition duration-300 ease-out"
                    x-transition:enter-start="translate-y-4 scale-95 opacity-0"
                    x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                    x-transition:leave="transition duration-200 ease-in"
                    x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                    x-transition:leave-end="translate-y-4 scale-95 opacity-0"
                    class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
                    <button type="button" @click="open = false" aria-label="Tutup ucapan pembayaran berhasil"
                        class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="bg-gradient-to-br from-amber-50 via-white to-sky-50 px-6 pb-7 pt-10 text-center sm:px-9">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 ring-8 ring-emerald-50">
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" />
                            </svg>
                        </div>
                        <p class="mt-7 text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-600">Pembayaran berhasil</p>
                        <h2 id="payment-success-title" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Terima kasih sudah subscribe!</h2>
                        <p id="payment-success-description" class="mt-3 text-sm leading-6 text-slate-600">
                            Subscription kamu sudah aktif. Sekarang kamu bisa menikmati seluruh materi premium dan Selling Kit eksklusif.
                        </p>
                    </div>

                    <div class="space-y-3 px-6 py-6 sm:px-9">
                        <a href="{{ route('selling-kit.index') }}" autofocus
                            class="flex min-h-12 w-full items-center justify-center rounded-xl bg-amber-400 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2">
                            Buka Selling Kit
                        </a>
                        <button type="button" @click="open = false"
                            class="min-h-11 w-full rounded-xl px-5 py-2.5 text-sm font-bold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-300">
                            Tetap di halaman subscription
                        </button>
                    </div>
                </div>
            </div>
        @endif

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
                        <h2 class="text-lg font-extrabold text-amber-950">Pembayaran belum selesai</h2>
                        <p class="mt-1 text-sm leading-6 text-amber-900/75">
                            Checkout {{ $pendingSubscription->duration_months }} bulan senilai Rp {{ number_format($pendingSubscription->amount, 0, ',', '.') }} sudah dibuat.
                            Subscription akan aktif otomatis setelah pembayaran dikonfirmasi Midtrans.
                        </p>
                        @if ($pendingSubscription->snap_token)
                            <button type="button" data-snap-token="{{ $pendingSubscription->snap_token }}"
                                data-sync-url="{{ route('subscriptions.sync-payment', $pendingSubscription) }}"
                                class="js-pay-with-snap mt-4 min-h-11 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-extrabold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                Lanjutkan Pembayaran
                            </button>
                        @endif
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
                    <h2 class="mt-1 text-xl font-extrabold text-slate-900">Bayar dengan Midtrans</h2>

                    <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <p class="font-extrabold text-blue-950">Pembayaran aman melalui Snap</p>
                        <p class="mt-2 text-sm leading-6 text-blue-900/75">Pilih metode pembayaran yang tersedia di popup Midtrans. Status akses diperbarui otomatis setelah pembayaran berhasil.</p>
                    </div>

                    <ol class="mt-5 space-y-3 text-sm leading-6 text-slate-600">
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">1.</span>Pastikan durasi dan total pembayaran sudah benar.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">2.</span>Klik tombol bayar dan pilih metode pembayaran.</li>
                        <li class="flex gap-3"><span class="font-extrabold text-amber-600">3.</span>Selesaikan instruksi pembayaran dari Midtrans.</li>
                    </ol>

                    <form method="POST" action="{{ route('subscriptions.store') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="duration_months" :value="months">
                        <button type="submit" class="min-h-11 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Bayar Sekarang
                        </button>
                    </form>
                    <p class="mt-3 text-center text-xs leading-5 text-slate-400">Kamu akan melihat popup pembayaran resmi Midtrans.</p>
                </section>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let syncInProgress = false;
            let paymentPoller = null;
            let paymentPollCount = 0;

            const stopPolling = () => {
                if (paymentPoller) window.clearInterval(paymentPoller);
                paymentPoller = null;
            };

            const syncPayment = async (url, reloadWhenPending = true) => {
                if (!url || syncInProgress) return;
                syncInProgress = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                    });
                    const payment = await response.json();

                    if (payment.payment_status === 'paid') {
                        stopPolling();
                        window.snap?.hide();
                        window.location.reload();
                        return;
                    }

                    if (['cancelled', 'expired', 'failed'].includes(payment.payment_status)) {
                        stopPolling();
                        window.snap?.hide();
                        window.location.reload();
                        return;
                    }

                    if (reloadWhenPending) window.location.reload();
                } catch (error) {
                    // A temporary network error is retried by the next polling cycle.
                    if (reloadWhenPending) window.location.reload();
                } finally {
                    syncInProgress = false;
                }
            };

            const startPolling = (url) => {
                stopPolling();
                paymentPollCount = 0;

                paymentPoller = window.setInterval(() => {
                    paymentPollCount += 1;

                    // Stop after three minutes; callbacks and webhook remain active.
                    if (paymentPollCount > 60) {
                        stopPolling();
                        return;
                    }

                    syncPayment(url, false);
                }, 3000);
            };

            const openSnap = (token, syncUrl = null) => {
                if (!token || !window.snap) return;

                window.snap.pay(token, {
                    onSuccess: () => syncPayment(syncUrl),
                    onPending: () => syncPayment(syncUrl),
                    onError: () => syncPayment(syncUrl),
                    onClose: () => syncPayment(syncUrl),
                });

                startPolling(syncUrl);
            };

            document.querySelectorAll('.js-pay-with-snap').forEach((button) => {
                button.addEventListener('click', () => openSnap(button.dataset.snapToken, button.dataset.syncUrl));
            });

            openSnap(
                @json(session('snap_token')),
                @json($pendingSubscription ? route('subscriptions.sync-payment', $pendingSubscription) : null),
            );
        });
    </script>
    @endpush
</x-dashboard-layout>
