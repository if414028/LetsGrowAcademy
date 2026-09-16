@php($closeSidebar = ($mobile ?? false) ? '@click=sidebarOpen=false' : '')

@hasanyrole('Health Planner|Health Manager|Sales Manager')
    <a href="{{ route('subscriptions.index') }}" {!! $closeSidebar !!}
        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('subscriptions.*', 'selling-kit.*', 'subscriber-landing.index') ? 'bg-amber-50 text-amber-700' : 'text-gray-700 hover:bg-gray-50' }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2.4 4.86 5.36.78-3.88 3.78.92 5.34L12 15.24 7.2 17.76l.92-5.34-3.88-3.78 5.36-.78L12 3z" /></svg>
        Subscription
    </a>

    @if (auth()->user()->hasActiveSubscription())
        <div class="ml-5 border-l-2 border-amber-200 pl-3">
            <p class="px-3 pb-1 pt-2 text-[9px] font-extrabold uppercase tracking-[0.16em] text-amber-600">Khusus subscriber</p>
            <a href="{{ route('selling-kit.index') }}" {!! $closeSidebar !!} class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('selling-kit.*') ? 'bg-amber-100 text-amber-800' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7V3z" /></svg><span class="flex-1">Selling Kit</span>
            </a>
            <a href="{{ route('subscriber-landing.index') }}" {!! $closeSidebar !!} class="mt-1 flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('subscriber-landing.index') ? 'bg-sky-100 text-sky-800' : 'text-gray-600 hover:bg-sky-50 hover:text-sky-700' }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7m0-7-9 9M10 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" /></svg><span class="flex-1">Landing Page Produk</span>
            </a>
        </div>
    @endif
@endhasanyrole

@role('Admin|Head Admin')
    <div class="mt-1">
        <div class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.subscriptions.*', 'admin.selling-kit.*') ? 'bg-amber-50 text-amber-700' : 'text-gray-700' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2.4 4.86 5.36.78-3.88 3.78.92 5.34L12 15.24 7.2 17.76l.92-5.34-3.88-3.78 5.36-.78L12 3z" /></svg>
            Subscription
        </div>
        <div class="ml-5 border-l-2 border-amber-200 pl-3">
            <a href="{{ route('admin.subscriptions.index') }}" {!! $closeSidebar !!} class="flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.subscriptions.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-4.5A11 11 0 0112 3a11 11 0 01-8 2.5V11c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V5.5z" /></svg><span>Verifikasi Subscription</span>
            </a>
            <a href="{{ route('admin.selling-kit.index') }}" {!! $closeSidebar !!} class="mt-1 flex min-h-11 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.selling-kit.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6.5A2.5 2.5 0 0 1 6.5 4H9l2 2h6.5A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-10Z" /></svg><span>Manage Selling Kit</span>
            </a>
        </div>
    </div>
@endrole
