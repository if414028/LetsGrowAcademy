<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Pembayaran kelas | Let's Grow Community</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="recruitment-page recruitment-community-page">
    <main class="recruitment-checkout" aria-labelledby="checkout-title">
        <div class="recruitment-checkout-card" x-data="recruitmentPayment(@js($checkoutConfig))">
            <div class="recruitment-brand">
                <img src="{{ asset('images/recruitment/letsgrow-logo.png') }}" alt="" width="44" height="44">
                <span>Let's Grow<span>COMMUNITY</span></span>
            </div>
            <h1 id="checkout-title">Pembayaran kelas</h1>
            <p class="recruitment-checkout-intro">Selesaikan pembayaran untuk mengonfirmasi pendaftaran Anda.</p>
            <p class="recruitment-registration-code">Kode pendaftaran: <strong>{{ $lead->registrationId() }}</strong></p>
            @include('subscriber-recruitment.event-details', ['eventDetails' => $lead->only(['seminar_name', 'batch_id', 'batch_starts_at'])])

            @if (! $locked)
                <div class="recruitment-field recruitment-payment-method">
                    <label for="event-payment-method">Metode pembayaran</label>
                    <select id="event-payment-method" name="payment_method" form="event-payment" x-model="paymentMethod" required @disabled(! $paymentMethods)
                        aria-describedby="event-payment-help @error('payment_method') event-payment-error @enderror" @error('payment_method') aria-invalid="true" @enderror>
                        @foreach (collect($paymentMethods)->groupBy('group', preserveKeys: true) as $group => $methods)
                            <optgroup label="{{ $group }}">
                                @foreach ($methods as $method => $details)
                                    <option value="{{ $method }}" @selected($selectedMethod === $method)>{{ $details['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('payment_method')<p id="event-payment-error" class="recruitment-field-error">{{ $message }}</p>@enderror
                    <p id="event-payment-help" class="recruitment-payment-help">{{ $paymentMethods ? 'Biaya tambahan mengikuti metode pembayaran yang Anda pilih.' : 'Metode pembayaran belum tersedia. Silakan hubungi pendamping Anda.' }}</p>
                    <noscript><p class="recruitment-payment-help">Aktifkan JavaScript untuk memperbarui rincian biaya dan membuka pembayaran.</p></noscript>
                </div>
            @elseif ($quote)
                <p class="recruitment-payment-help">Metode pembayaran: <strong>{{ $quote['payment_method_label'] }}</strong>. Rincian mengikuti tagihan yang sudah dibuat.</p>
            @endif

            <dl class="recruitment-checkout-prices" aria-live="polite" aria-atomic="true">
                <div><dt>Harga kelas</dt><dd>Rp{{ number_format($classAmount, 0, ',', '.') }}</dd></div>
                <div><dt>Biaya tambahan</dt><dd x-text="money(additionalFee)">Rp{{ number_format($additionalFee, 0, ',', '.') }}</dd></div>
                @if ($quote)
                <div class="recruitment-fee-detail">
                    <dt>Biaya transaksi</dt><dd x-text="money(quote?.transaction_fee)">Rp{{ number_format($quote['transaction_fee'] ?? 0, 0, ',', '.') }}</dd>
                </div>
                <div class="recruitment-fee-detail">
                    <dt>PPN <span x-text="quote?.gateway_vat_percent">{{ $quote['gateway_vat_percent'] ?? '' }}</span>% biaya transaksi</dt>
                    <dd x-text="money(quote?.gateway_vat_amount)">Rp{{ number_format($quote['gateway_vat_amount'] ?? 0, 0, ',', '.') }}</dd>
                </div>
                @endif
                <div class="recruitment-checkout-total">
                    <dt>Total pembayaran</dt><dd x-text="money(total)">Rp{{ number_format($classAmount + $additionalFee, 0, ',', '.') }}</dd>
                </div>
            </dl>
            <p class="recruitment-payment-help" x-cloak x-show="quote && !quote.merchant_covers_fee">PPN hanya dihitung dari biaya transaksi, bukan harga kelas.</p>
            <p class="recruitment-payment-help" x-show="['gopay', 'shopeepay'].includes(paymentMethod)">Untuk GoPay atau ShopeePay, buka halaman ini di ponsel agar bisa melanjutkan ke aplikasi e-wallet.</p>

            @if (session('payment_error'))
                <p class="recruitment-checkout-status" role="alert">{{ session('payment_error') }}</p>
            @elseif ($lead->event_payment_status === 'refunded')
                <p class="recruitment-checkout-status">Pembayaran ini telah dikembalikan.</p>
            @elseif (in_array($payment?->status, ['failed', 'expired'], true))
                <p class="recruitment-checkout-status">Pembayaran sebelumnya tidak selesai. Anda bisa mencoba lagi.</p>
            @elseif ($payment?->status === 'pending')
                <p class="recruitment-checkout-status">Pembayaran belum terkonfirmasi. Jika sudah membayar, cek status pembayaran.</p>
            @endif
            <p class="recruitment-checkout-status" x-show="paymentError" x-text="paymentError" role="alert"></p>

            @if ($lead->event_payment_status !== 'refunded')
                <form id="event-payment" method="POST" action="{{ route('subscriber-recruitment.payment.pay', [$lead->source_slug, $lead->event_checkout_id]) }}">
                    @csrf
                    <button type="submit" class="recruitment-button" @disabled(! $locked && ! $paymentMethods)>{{ $payment?->status === 'pending' ? 'Lanjutkan pembayaran' : 'Bayar' }} <x-recruitment-icon /></button>
                </form>
            @endif
            @if ($payment?->status === 'pending')
                <a class="recruitment-checkout-refresh" href="{{ route('subscriber-recruitment.payment.return', [$lead->source_slug, $lead->event_checkout_id]) }}">Cek status pembayaran</a>
            @endif
            @if ($crmWhatsappUrl)
                <div class="recruitment-chat-action">
                    <a class="recruitment-button recruitment-button-light" href="{{ $crmWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Chat via WhatsApp <x-recruitment-icon name="chat" /></a>
                    <p>Tekan Kirim di WhatsApp agar tim dapat membantu pendaftaran dan pembayaran Anda.</p>
                </div>
            @endif
        </div>
    </main>
    @if ($lead->event_payment_status !== 'refunded')
        <script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endif
</body>
</html>
