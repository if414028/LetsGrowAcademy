@php($stepLabels = ['Pilih konten', 'Atur budget', 'Pembayaran', 'Pantau iklan'])
<nav class="ga-stepper" aria-label="Tahapan Grow Ads"><ol>
    @foreach ($stepLabels as $label)
        <li class="{{ $loop->iteration < $step ? 'is-done' : '' }} {{ $loop->iteration === $step ? 'is-current' : '' }}" @if($loop->iteration === $step) aria-current="step" @endif>
            <span class="ga-step-number">@if($loop->iteration < $step)<x-grow-ad-icon name="check" />@else{{ $loop->iteration }}@endif</span><span>{{ $label }}</span>
        </li>
    @endforeach
</ol></nav>
