@php
    $hasPerformanceRecap = auth()->user()->hasAnyRole(['Health Manager', 'Sales Manager', 'Head Admin']);
    $isMobileMenu = $mobile ?? false;
@endphp

@if ($hasPerformanceRecap)
    <div x-data="{ open: {{ request()->routeIs('performance.*') ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open"
            class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('performance.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 14l3-3 3 2 5-6" />
            </svg>
            <span class="flex-1 text-left">Performance</span>
            <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1 border-l border-gray-200 pl-3">
            <a href="{{ route('performance.index') }}" @if($isMobileMenu) @click="sidebarOpen=false" @endif
                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('performance.index') ? 'bg-blue-50 font-semibold text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                My Performance
            </a>
            <a href="{{ route('performance.recap') }}" @if($isMobileMenu) @click="sidebarOpen=false" @endif
                class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('performance.recap') ? 'bg-blue-50 font-semibold text-blue-700' : 'text-gray-600 hover:bg-gray-50' }}">
                Performance Recap
            </a>
        </div>
    </div>
@else
    <a href="{{ route('performance.index') }}" @if($isMobileMenu) @click="sidebarOpen=false" @endif
        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('performance.index') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 14l3-3 3 2 5-6" />
        </svg>
        Performance
    </a>
@endif
