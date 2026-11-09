<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Pembayaran kelas | Let's Grow Community</title>
    @vite('resources/css/app.css')
</head>
<body class="recruitment-page recruitment-community-page">
    <main class="recruitment-checkout" aria-labelledby="checkout-title">
        <div class="recruitment-checkout-card">
            <div class="recruitment-brand">
                <img src="{{ asset('images/recruitment/letsgrow-logo.png') }}" alt="" width="44" height="44">
                <span>Let's Grow<span>COMMUNITY</span></span>
            </div>
            <h1 id="checkout-title">Pembayaran kelas</h1>
            <p class="recruitment-checkout-intro">Selesaikan pembayaran untuk mengonfirmasi pendaftaran Anda.</p>

            <dl class="recruitment-checkout-prices">
                <div><dt>Harga kelas</dt><dd>Rp{{ number_format($price['class_amount'], 0, ',', '.') }}</dd></div>
                <div><dt>Biaya layanan aplikasi</dt><dd>Rp{{ number_format($price['service_fee'], 0, ',', '.') }}</dd></div>
                <div class="recruitment-checkout-total">
                    <dt>Total pembayaran</dt><dd>Rp{{ number_format(array_sum($price), 0, ',', '.') }}</dd>
                </div>
            </dl>

            @if (session('payment_error'))
                <p class="recruitment-checkout-status" role="alert">{{ session('payment_error') }}</p>
            @elseif ($lead->event_payment_status === 'refunded')
                <p class="recruitment-checkout-status">Pembayaran ini telah dikembalikan.</p>
            @elseif ($payment?->status === 'failed')
                <p class="recruitment-checkout-status">Pembayaran sebelumnya tidak selesai. Anda bisa mencoba lagi.</p>
            @elseif ($payment?->status === 'pending')
                <p class="recruitment-checkout-status">Pembayaran belum terkonfirmasi. Jika sudah membayar, cek status pembayaran.</p>
            @endif

            @if ($lead->event_payment_status !== 'refunded')
                <form method="POST" action="{{ route('subscriber-recruitment.payment.pay', [$lead->source_slug, $lead->event_checkout_id]) }}">
                    @csrf
                    <button type="submit" class="recruitment-button">Bayar <x-recruitment-icon /></button>
                </form>
            @endif
            @if ($payment?->status === 'pending')
                <a class="recruitment-checkout-refresh" href="{{ route('subscriber-recruitment.payment.return', [$lead->source_slug, $lead->event_checkout_id]) }}">Cek status pembayaran</a>
            @endif
        </div>
    </main>
</body>
</html>
