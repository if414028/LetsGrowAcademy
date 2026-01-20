<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Lets Grow Academy') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-900 h-screen overflow-hidden">
    <div class="flex h-full">
        {{-- Sidebar --}}
        <aside class="w-64 hidden md:flex flex-col border-r bg-white h-full overflow-y-auto">
            <div class="h-16 flex items-center px-6 border-b shrink-0">
                <span class="text-xl font-semibold text-blue-600">Let's Groww Academy</span>
            </div>

            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                          {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h7v7H4V6zm9 0h7v7h-7V6zM4 15h7v7H4v-7zm9 0h7v7h-7v-7z"/>
                    </svg>
                    Overview
                </a>

                <a href="#"
                   class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 10-8 0 4 4 0 008 0z"/>
                    </svg>
                    My Team
                </a>

                <a href="#"
                   class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-8 0h8m-10 0a2 2 0 01-2-2V7a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2"/>
                    </svg>
                    Reports
                </a>

                @role('Admin')
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium
                              {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Users
                    </a>
                @endrole
            </nav>
        </aside>

        {{-- Main --}}
        <div class="flex-1 min-w-0 flex flex-col h-full">
            <header class="h-16 bg-white border-b flex items-center justify-end px-4 md:px-8 gap-4 shrink-0 sticky top-0 z-40">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-3 focus:outline-none">
                        <div class="h-9 w-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-semibold">
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

                    <div
                        x-show="open"
                        @click.outside="open = false"
                        x-transition.opacity.scale.origin.top.right
                        class="absolute right-0 mt-3 w-64 rounded-xl bg-white shadow-lg border z-50">
                        <div class="px-4 py-3 border-b">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ auth()->user()->email }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
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
</body>
</html>