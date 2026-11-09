<svg class="ga-how-illustration" viewBox="0 0 320 180" fill="none" aria-hidden="true" focusable="false">
    @switch($variant)
        @case('content')
            <rect x="18" y="25" width="104" height="132" rx="10" fill="#fff" stroke="#d8e5ed"/>
            <rect x="28" y="38" width="84" height="65" rx="6" fill="#e7f4fa"/>
            <path d="m28 92 33-28 19 16 15-10 17 22v11H28V92" fill="#b4deed"/>
            <rect x="28" y="114" width="66" height="4" rx="2" fill="#a8bfcc"/><rect x="28" y="126" width="52" height="4" rx="2" fill="#d8e5ed"/>
            <rect x="109" y="11" width="104" height="146" rx="10" fill="#fff" stroke="#075c83" stroke-width="1.5"/>
            <rect x="119" y="24" width="84" height="79" rx="6" fill="#d2eef7"/>
            <path d="m139 54 37-11v37l-37-11V54Zm6 17 3 17h11l-4-14M184 48l8-5m-6 18h10m-12 13 8 5" stroke="#075c83" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
            <rect x="119" y="114" width="66" height="4" rx="2" fill="#075c83"/><rect x="119" y="126" width="52" height="4" rx="2" fill="#a8bfcc"/>
            <rect x="201" y="25" width="104" height="132" rx="10" fill="#fff" stroke="#d8e5ed"/>
            <rect x="211" y="38" width="84" height="65" rx="6" fill="#e7f4fa"/>
            <circle cx="253" cy="63" r="10" fill="#a8d9e9"/><path d="M231 93a22 22 0 0 1 44 0" fill="#7cb7cd"/>
            <rect x="211" y="114" width="66" height="4" rx="2" fill="#a8bfcc"/><rect x="211" y="126" width="52" height="4" rx="2" fill="#d8e5ed"/>
            @foreach([96,187,279] as $x)<circle cx="{{ $x }}" cy="{{ $x === 187 ? 24 : 38 }}" r="12" fill="#075c83" stroke="#fff" stroke-width="3"/><path d="m{{ $x-5 }} {{ $x === 187 ? 24 : 38 }} 3 3 6-7" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>@endforeach
            @break
        @case('budget')
            <rect x="27" y="19" width="266" height="141" rx="13" fill="#fff" stroke="#d8e5ed"/>
            <text x="45" y="46" fill="#52697a" font-family="system-ui, sans-serif" font-size="13">Budget harian</text>
            <text x="45" y="78" fill="#102c40" font-family="system-ui, sans-serif" font-size="25" font-weight="700">Rp / hari</text>
            <path d="M46 103h228" stroke="#d8e5ed" stroke-width="5" stroke-linecap="round"/>
            <path d="M46 103h145" stroke="#075c83" stroke-width="5" stroke-linecap="round"/>
            <circle cx="191" cy="103" r="10" fill="#fff" stroke="#075c83" stroke-width="3"/>
            <path d="M236 75V60m10 15V49m10 26V38" stroke="#86c9de" stroke-width="6" stroke-linecap="round"/>
            <rect x="44" y="123" width="122" height="23" rx="6" fill="#e7f4fa"/>
            <path d="M54 129h12v11H54zM57 127v4m6-4v4m-9 3h12" stroke="#075c83" stroke-width="1.2"/>
            <text x="74" y="139" fill="#075c83" font-family="system-ui, sans-serif" font-size="11">Atur durasinya</text>
            @break
        @case('launch')
            <rect x="21" y="27" width="188" height="120" rx="10" fill="#fff" stroke="#d8e5ed"/>
            <path d="M21 52h188" stroke="#d8e5ed"/><circle cx="35" cy="40" r="2" fill="#a8bfcc"/><circle cx="43" cy="40" r="2" fill="#a8bfcc"/><circle cx="51" cy="40" r="2" fill="#a8bfcc"/>
            <text x="70" y="44" fill="#075c83" font-family="system-ui, sans-serif" font-size="11" font-weight="700">META ADS</text>
            <rect x="35" y="65" width="70" height="65" rx="6" fill="#e7f4fa"/>
            <path d="m54 86 29-9v28l-29-9V86Zm5 13 2 13h9l-3-11" stroke="#075c83" stroke-width="2" stroke-linejoin="round"/>
            <rect x="118" y="73" width="72" height="4" rx="2" fill="#a8bfcc"/><rect x="118" y="85" width="58" height="4" rx="2" fill="#d8e5ed"/>
            <rect x="118" y="104" width="65" height="21" rx="5" fill="#eaf7ed"/>
            <path d="m127 114 3 3 6-7" stroke="#166534" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><text x="142" y="118" fill="#166534" font-family="system-ui, sans-serif" font-size="9">Siap tayang</text>
            <path d="M211 86h16m-5-5 5 5-5 5" stroke="#7cb7cd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <rect x="237" y="36" width="64" height="115" rx="10" fill="#fff" stroke="#075c83" stroke-width="1.5"/>
            <rect x="248" y="49" width="42" height="34" rx="4" fill="#d2eef7"/><circle cx="261" cy="63" r="5" fill="#075c83"/><path d="M252 78a9 9 0 0 1 18 0" fill="#075c83"/>
            <path d="M248 95h40m-40 9h29" stroke="#a8bfcc" stroke-width="3" stroke-linecap="round"/>
            <rect x="248" y="118" width="42" height="16" rx="4" fill="#075c83"/>
            @break
    @endswitch
</svg>
