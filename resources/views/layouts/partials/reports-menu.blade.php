@php
    $isReportsRoute = request()->routeIs('reports.*');
    $isMobileMenu = $mobile ?? false;
    $reportsMenuId = $isMobileMenu ? 'mobile-reports-submenu' : 'desktop-reports-submenu';
@endphp

<div x-data="{ open: {{ $isReportsRoute ? 'true' : 'false' }} }">
    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="{{ $reportsMenuId }}"
        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ $isReportsRoute ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
        <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-10 0a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2" />
        </svg>
        <span class="flex-1 text-left">Reports</span>
        <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
        </svg>
    </button>

    <div id="{{ $reportsMenuId }}" x-show="open" x-cloak class="ml-8 mt-1 space-y-1 border-l border-gray-200 pl-3">
        <a href="{{ route('reports.index') }}" @if($isMobileMenu) @click="sidebarOpen=false" @endif
            @if(request()->routeIs('reports.index')) aria-current="page" @endif
            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('reports.index') ? 'bg-blue-50 font-semibold text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
            Leaderboards
        </a>
        <a href="{{ route('reports.hp-status') }}" @if($isMobileMenu) @click="sidebarOpen=false" @endif
            @if(request()->routeIs('reports.hp-status')) aria-current="page" @endif
            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('reports.hp-status') ? 'bg-blue-50 font-semibold text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
            HP Status
        </a>
    </div>
</div>
