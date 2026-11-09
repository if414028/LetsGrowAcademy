<x-dashboard-layout>
    @php
        $selectedMonths = (int) old('duration_months', 6);
        $selectedMonths = in_array($selectedMonths, $durations, true) ? $selectedMonths : $durations[0];
        $selectedPaymentMethod = old('payment_method', array_key_first($paymentMethods));
        $selectedPaymentMethod = is_string($selectedPaymentMethod) && isset($paymentMethods[$selectedPaymentMethod]) ? $selectedPaymentMethod : array_key_first($paymentMethods);
        $initialQuote = $quotes[$selectedMonths][$selectedPaymentMethod] ?? null;
        $checkoutData = ['months' => $selectedMonths, 'paymentMethod' => $selectedPaymentMethod, 'quotes' => $quotes];
        $snapUiMode = $pendingSubscription?->pricing_details['snap_ui_mode'] ?? 'auto';
    @endphp

    <div class="mx-auto max-w-6xl" x-data="{
        ...@js($checkoutData),
        get quote() { return this.quotes[this.months]?.[this.paymentMethod] ?? null; },
        money(amount) { return 'Rp ' + (amount ?? 0).toLocaleString('id-ID'); }
    }">
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
            <x-subscription-status :subscription="$activeSubscription" :show-time="true">
                <x-slot:details>
                    @if (auth()->user()->whatsapp_url)
                        <p class="subscription-status-card__detail">{{ route('subscriber-landing.show', auth()->user()->landing_page_slug) }}</p>
                    @else
                        <p class="subscription-status-card__detail">Tambahkan nomor WhatsApp di profil agar landing page dapat dipublikasikan.</p>
                    @endif
                </x-slot:details>
                <x-slot:actions>
                    @if (auth()->user()->whatsapp_url)
                        <a href="{{ route('subscriber-landing.show', auth()->user()->landing_page_slug) }}" target="_blank" rel="noopener" class="subscription-status-card__action"><span>Buka Landing Page</span></a>
                    @endif
                    <a href="{{ route('selling-kit.index') }}" class="subscription-status-card__action"><span>Buka Selling Kit</span></a>
                </x-slot:actions>
            </x-subscription-status>
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
                        @if ($pendingSubscription->pricing_details)
                            <div class="mt-4 max-w-md rounded-xl bg-white/70 p-4">
                                <x-subscription-payment-details :subscription="$pendingSubscription" />
                            </div>
                        @endif
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
                                :aria-pressed="months === {{ $duration }}"
                                :class="months === {{ $duration }} ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="min-h-28 cursor-pointer rounded-2xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <span class="block text-2xl font-extrabold text-slate-900">{{ $duration }}</span>
                                <span class="text-sm font-medium text-slate-500">bulan</span>
                                <span class="mt-3 block text-sm font-bold text-slate-800">Rp {{ number_format($duration * $monthlyPrice, 0, ',', '.') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="mt-3 text-xs leading-5 text-slate-500">Harga paket belum termasuk biaya transaksi dan PPN atas biaya transaksi.</p>

                    @if ($initialQuote)
                        <div class="mt-6 rounded-2xl bg-slate-50 p-5" aria-live="polite" aria-atomic="true">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rincian pembayaran</p>
                            <dl class="mt-4 space-y-3 text-sm text-slate-600">
                                <div class="flex justify-between gap-4">
                                    <dt>Subscription <span x-text="months">{{ $selectedMonths }}</span> bulan</dt>
                                    <dd class="shrink-0 font-semibold" x-text="money(quote?.subtotal)">Rp {{ number_format($initialQuote['subtotal'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt>Biaya transaksi</dt>
                                    <dd class="shrink-0 font-semibold" x-text="money(quote?.transaction_fee)">Rp {{ number_format($initialQuote['transaction_fee'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt>PPN {{ $initialQuote['gateway_vat_percent'] }}% biaya transaksi</dt>
                                    <dd class="shrink-0 font-semibold" x-text="money(quote?.gateway_vat_amount)">Rp {{ number_format($initialQuote['gateway_vat_amount'], 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-4 text-slate-950">
                                    <dt class="font-bold">Total pembayaran</dt>
                                    <dd class="text-2xl font-extrabold" x-text="money(quote?.total)">Rp {{ number_format($initialQuote['total'], 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                            <p class="mt-3 text-xs leading-5 text-slate-500" x-text="quote?.merchant_covers_fee ? 'Biaya QRIS dan PPN atas biaya tersebut ditanggung oleh Let\'s Grow Academy.' : 'PPN hanya dihitung dari biaya transaksi, bukan harga subscription.'">
                                {{ $initialQuote['merchant_covers_fee'] ? "Biaya QRIS dan PPN atas biaya tersebut ditanggung oleh Let's Grow Academy." : 'PPN hanya dihitung dari biaya transaksi, bukan harga subscription.' }}
                            </p>
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Langkah 2</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-900">Pilih metode pembayaran</h2>

                    <form method="POST" action="{{ route('subscriptions.store') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="duration_months" value="{{ $selectedMonths }}" :value="months">
                        <label for="subscription-payment-method" class="block text-sm font-bold text-slate-700">Metode pembayaran</label>
                        <select id="subscription-payment-method" name="payment_method" x-model="paymentMethod" required
                            aria-describedby="subscription-fee-help @error('payment_method') subscription-method-error @enderror"
                            @disabled(! $paymentMethods)
                            class="mt-2 min-h-12 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-amber-400 focus:ring-amber-400">
                            @foreach (collect($paymentMethods)->groupBy('group', preserveKeys: true) as $group => $methods)
                                <optgroup label="{{ $group }}">
                                    @foreach ($methods as $method => $details)
                                        <option value="{{ $method }}" @selected($selectedPaymentMethod === $method)>{{ $details['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('payment_method')<p id="subscription-method-error" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('duration_months')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                        @if ($initialQuote)
                            <p id="subscription-fee-help" class="mt-3 text-sm leading-6 text-slate-500"
                                x-text="quote?.merchant_covers_fee ? 'Biaya QRIS ' + (quote.fee_basis_points / 100).toLocaleString('id-ID') + '% ditanggung merchant. Tidak ada biaya tambahan untuk kamu.' : 'Tarif gateway: ' + (quote?.fee_basis_points > 0 ? (quote.fee_basis_points / 100).toLocaleString('id-ID') + '% dari total pembayaran' : money(quote?.fixed_fee) + ' per transaksi') + ', belum termasuk PPN.'">
                                Biaya transaksi dan PPN menyesuaikan metode pilihanmu. Lihat total pada rincian pembayaran.
                            </p>
                            <p class="mt-2 text-xs leading-5 text-slate-500" x-show="quote?.fee_basis_points > 0 && !quote?.merchant_covers_fee">
                                Biaya persentase dihitung dari total pembayaran, termasuk biaya transaksi, lalu dibulatkan ke atas ke rupiah penuh.
                            </p>
                        @else
                            <p id="subscription-fee-help" class="mt-3 text-sm leading-6 text-slate-500">Metode pembayaran belum tersedia. Hubungi administrator.</p>
                        @endif

                        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                            <p class="font-extrabold text-blue-950">Pembayaran aman melalui Midtrans</p>
                            <p class="mt-2 text-sm leading-6 text-blue-900/75">Periksa durasi, metode, dan total pembayaran, lalu selesaikan instruksi di popup Midtrans. Subscription aktif otomatis setelah pembayaran berhasil.</p>
                            <p class="mt-2 text-xs leading-5 text-blue-900/75" x-show="['gopay', 'shopeepay'].includes(paymentMethod)">Untuk GoPay dan ShopeePay, buka halaman ini di ponsel agar bisa melanjutkan ke aplikasi e-wallet.</p>
                        </div>

                        <button type="submit" @disabled(! $paymentMethods) class="mt-6 min-h-11 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
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
                    // Keep charged e-wallet checkouts in app mode; their desktop QRIS
                    // fallback must not turn an e-wallet fee into a QRIS surcharge.
                    uiMode: @json($snapUiMode),
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
