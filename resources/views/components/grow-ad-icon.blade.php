@props(['name' => 'arrow'])
<svg {{ $attributes->merge(['class' => 'ga-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('megaphone')<path d="m3 10 14-6v16L3 14v-4Zm4 6 1 5h4l-2-4M21 8v8"/>@break
        @case('check')<path d="m5 12 4 4L19 6"/>@break
        @case('image')<rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8" cy="8" r="1"/><path d="m3 17 5-5 4 4 3-3 6 6"/>@break
        @case('wallet')<path d="M20 8H5a2 2 0 0 1 0-4h13v4M4 6v13a1 1 0 0 0 1 1h15V8M16 12h5v5h-5z"/>@break
        @case('chart')<path d="M4 3v17h17M8 15l4-5 4 2 5-7"/>@break
        @case('people')<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/>@break
        @case('link')<path d="m10 13 4-4M8 15l-2 2a4 4 0 0 1-5-5l4-4a4 4 0 0 1 6 0m2 1 2-2a4 4 0 0 1 5 5l-4 4a4 4 0 0 1-6 0" transform="translate(1 0)"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('eye')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
        @case('click')<path d="m6 3 1 16 4-4 4 6 3-2-4-6 6-2L6 3Z"/>@break
        @case('back')<path d="M19 12H5m6-6-6 6 6 6"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @default<path d="M5 12h14m-6-6 6 6-6 6"/>
    @endswitch
</svg>
