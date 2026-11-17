<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Terima kasih sudah mendaftar | Let's Grow Community</title>
    @include('partials.nunito-font')
    @vite('resources/css/app.css')
    @include('grow-ads.pixel', ['pixelId' => $growAdPixelId, 'registration' => true, 'purchase' => $purchase])
</head>
<body class="recruitment-page recruitment-community-page">
    <main class="recruitment-success" aria-labelledby="registration-success-title">
        <div class="recruitment-success-content">
            <span class="recruitment-success-icon" aria-hidden="true"><x-recruitment-icon name="check" /></span>
            <h1 id="registration-success-title">Terima kasih sudah mendaftar!</h1>
            <p>Pembayaran Anda sudah terkonfirmasi.</p>
            <p class="recruitment-registration-code">Kode pendaftaran: <strong>{{ $lead->registrationId() }}</strong></p>
            @include('subscriber-recruitment.event-details', ['eventDetails' => $lead->only(['seminar_name', 'batch_id', 'batch_starts_at'])])
            @if ($crmWhatsappUrl)
                <div class="recruitment-chat-action">
                    <a class="recruitment-button" href="{{ $crmWhatsappUrl }}" target="_blank" rel="noopener noreferrer">Chat via WhatsApp <x-recruitment-icon name="chat" /></a>
                    <p>Kirim pesan yang sudah disiapkan di WhatsApp untuk mendapatkan informasi seminar dan langkah berikutnya.</p>
                </div>
            @else
                <p>Simpan kode pendaftaran Anda untuk keperluan informasi seminar.</p>
            @endif
        </div>
    </main>
</body>
</html>
