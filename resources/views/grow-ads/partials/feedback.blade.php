@if (session('success'))<div class="ga-notice ga-notice--success" role="status"><x-grow-ad-icon name="check" /><p>{{ session('success') }}</p></div>@endif
@if (session('error'))<div class="ga-notice ga-notice--error" role="alert"><p>{{ session('error') }}</p></div>@endif
@if ($errors->any())
    <div class="ga-notice ga-notice--error" role="alert" tabindex="-1"><div><strong>Periksa kembali isian Anda.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif
