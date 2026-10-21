<nav class="recruitment-management-nav" aria-label="Menu Landing Page Rekrutmen">
    <a href="{{ route('subscriber-recruitment.index') }}" @if(request()->routeIs('subscriber-recruitment.index')) aria-current="page" @endif>Landing Page</a>
    <a href="{{ route('subscriber-recruitment.leads') }}" @if(request()->routeIs('subscriber-recruitment.leads')) aria-current="page" @endif>Daftar Peminat <span aria-label="{{ $leadCount }} pendaftaran">{{ number_format($leadCount, 0, ',', '.') }}</span></a>
</nav>
