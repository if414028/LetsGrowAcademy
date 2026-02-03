<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Lets Grow Academy') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen md:h-screen">

        {{-- Sidebar (Desktop) --}}
        <aside class="w-64 hidden md:flex flex-col border-r bg-white h-full overflow-y-auto">
            <div class="h-16 flex items-center px-6 border-b shrink-0">
                <span class="text-xl font-semibold text-blue-600">Let's Grow Academy</span>
            </div>

            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                          {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h7v7H4V6zm9 0h7v7h-7V6zM4 15h7v7H4v-7zm9 0h7v7h-7v-7z" />
                    </svg>
                    Overview
                </a>

                <a href="{{ route('performances.index') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('performance.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3v18h18M7 14l3-3 3 2 5-6" />
                    </svg>
                    Performance
                </a>

                <a href="{{ route('reports.index') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                        {{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-10 0a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2" />
                    </svg>
                    Reports
                </a>

                @role('Admin')
                    <a href="{{ route('users.index') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Users
                    </a>

                    <a href="{{ route('products.index') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.27 6.96L12 12.01l8.73-5.05M12 22V12" />
                        </svg>
                        Products
                    </a>
                @endrole

                @hasanyrole('Admin|Sales Manager|Health Manager')
                    <a href="{{ route('sales-orders.index') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('sales-orders.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6M7 6h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z" />
                        </svg>
                        Sales Orders
                    </a>
                @endhasanyrole
            </nav>
        </aside>

        {{-- Sidebar (Mobile Drawer) --}}
        <div class="md:hidden">
            {{-- Overlay --}}
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/40 z-40"
                @click="sidebarOpen = false" style="display: none;"></div>

            {{-- Drawer --}}
            <aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 w-72 bg-white border-r z-50 overflow-y-auto" style="display: none;"
                @keydown.escape.window="sidebarOpen = false">
                <div class="h-16 flex items-center justify-between px-6 border-b shrink-0">
                    <span class="text-xl font-semibold text-blue-600">Let's Grow Academy</span>

                    <button @click="sidebarOpen = false" class="p-2 rounded-lg hover:bg-gray-100"
                        aria-label="Close menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="p-4 space-y-1">
                    <a href="{{ route('dashboard') }}" @click="sidebarOpen=false"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h7v7H4V6zm9 0h7v7h-7V6zM4 15h7v7H4v-7zm9 0h7v7h-7v-7z" />
                        </svg>
                        Overview
                    </a>

                    <a href="{{ route('performances.index') }}" @click="sidebarOpen=false"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                            {{ request()->routeIs('performance.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3v18h18M7 14l3-3 3 2 5-6" />
                        </svg>
                        Performance
                    </a>

                    <a href="{{ route('reports.index') }}" @click="sidebarOpen=false"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                            {{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-10 0a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2" />
                        </svg>
                        Reports
                    </a>

                    @role('Admin')
                        <a href="{{ route('users.index') }}" @click="sidebarOpen=false"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                                  {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Users
                        </a>

                        <a href="{{ route('products.index') }}" @click="sidebarOpen=false"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                                  {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3.27 6.96L12 12.01l8.73-5.05M12 22V12" />
                            </svg>
                            Products
                        </a>
                    @endrole

                    @hasanyrole('Admin|Sales Manager|Health Manager')
                        <a href="{{ route('sales-orders.index') }}" @click="sidebarOpen=false"
                            class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                                    {{ request()->routeIs('sales-orders.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6M7 6h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z" />
                            </svg>
                            Sales Orders
                        </a>
                    @endhasanyrole
                </nav>
            </aside>
        </div>

        {{-- Main --}}
        <div class="flex-1 min-w-0 flex flex-col md:h-screen">
            <header
                class="h-16 bg-white border-b flex items-center justify-between px-4 md:px-8 gap-4 shrink-0 sticky top-0 z-40">
                {{-- Hamburger (Mobile) --}}
                <button class="md:hidden p-2 rounded-xl hover:bg-gray-100" @click="sidebarOpen = true"
                    aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Spacer for desktop so profile stays right --}}
                <div class="hidden md:block"></div>

                {{-- Profile dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-3 focus:outline-none">
                        <div
                            class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-semibold">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>

                        <div class="leading-tight hidden sm:block text-left">
                            <div class="text-sm font-semibold">
                                {{ auth()->user()->name ?? 'User' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ auth()->user()->getRoleNames()->first() ?? '-' }}
                            </div>
                        </div>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-transition.opacity.scale.origin.top.right
                        class="absolute right-0 mt-3 w-64 rounded-xl bg-white shadow-lg border z-50"
                        style="display: none;">
                        <div class="px-4 py-3 border-b">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ auth()->user()->email }}
                            </div>
                        </div>

                        <a href="{{ route('profile') }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Profile
                        </a>

                        <div class="my-1 border-t"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-b-xl">
                                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 12H9m0 0 3-3m-3 3 3 3" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 md:p-10">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>

</html>
