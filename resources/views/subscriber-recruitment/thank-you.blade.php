<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>Terima kasih sudah mendaftar | Let's Grow Academy</title>
    @include('partials.nunito-font')
    @vite('resources/css/app.css')
</head>
<body class="recruitment-page">
    <main class="recruitment-success" aria-labelledby="registration-success-title">
        <div class="recruitment-success-content">
            <span class="recruitment-success-icon" aria-hidden="true"><x-recruitment-icon name="check" /></span>
            <h1 id="registration-success-title">Terima kasih sudah mendaftar!</h1>
            <p>Dalam waktu dekat, admin akan menghubungi Anda untuk mengikuti event.</p>
        </div>
    </main>
</body>
</html>
