<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight">Overview</h1>
            <p class="mt-2 text-gray-500">Your sales performance at a glance</p>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative">
                <button
                    class="inline-flex items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    This Week
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Deactivation warning (Month-5) --}}
    @php
        $authUser = request()->user();
        $isHP = $authUser->hasRole('Health Planner');
        $isManager = $authUser->hasAnyRole(['Sales Manager', 'Health Manager']);
        $isAdmin = $authUser->hasRole('Admin');

        $hasWarning = ($isHP && !empty($selfWarning)) || (!$isHP && $soDeactivationWarnings->count() > 0);
    @endphp

    @if ($hasWarning)
        <div class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 text-orange-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </span>

                    <div>
                        <h3 class="text-lg font-bold text-orange-900">
                            Peringatan Aktivitas Sales Order
                        </h3>

                        @if ($isHP)
                            <p class="mt-1 text-sm text-orange-900/80">
                                Anda sudah <span class="font-semibold">5 bulan</span> tidak membuat Sales Order.
                                Jika tidak membuat SO sampai
                                <span class="font-semibold">
                                    {{ $selfWarning->deactivate_at->translatedFormat('d M Y') }}
                                </span>,
                                maka akun Anda akan dinonaktifkan.
                            </p>
                            <p class="mt-2 text-xs text-orange-900/70">
                                Aktivitas terakhir: {{ $selfWarning->last_activity_at->translatedFormat('d M Y') }}
                            </p>
                        @else
                            <p class="mt-1 text-sm text-orange-900/80">
                                Berikut daftar Health Planner yang sudah 5 bulan tidak membuat Sales Order dan akan
                                dinonaktifkan pada bulan ke-6.
                            </p>
                        @endif
                    </div>
                </div>

                <span
                    class="text-xs font-semibold text-orange-700 bg-orange-100 border border-orange-200 px-2 py-1 rounded-lg">
                    Warning
                </span>
            </div>

            @if (!$isHP)
                <div class="mt-4 -mx-6 sm:mx-0 overflow-x-auto">
                    <div class="px-6 sm:px-0">
                        <table class="min-w-[720px] w-full text-sm">
                            <thead class="text-orange-900/70">
                                <tr class="border-b border-orange-200">
                                    <th class="text-left font-semibold py-2">Health Planner</th>
                                    <th class="text-left font-semibold py-2">Health Manager</th>
                                    <th class="text-left font-semibold py-2">DST</th>
                                    <th class="text-left font-semibold py-2">Aktivitas Terakhir</th>
                                    <th class="text-left font-semibold py-2">Perkiraan Nonaktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($soDeactivationWarnings as $u)
                                    <tr class="border-b border-orange-100">
                                        <td class="py-2">
                                            <div class="font-semibold text-orange-900">{{ $u->name }}</div>
                                            <div class="text-xs text-orange-900/70">{{ $u->email }}</div>
                                        </td>

                                        <td class="py-2 text-orange-900/80">
                                            {{ $u->health_manager_name ?? '-' }}
                                        </td>

                                        <td class="py-2 text-orange-900/80 whitespace-nowrap">
                                            {{ $u->dst_code ?? '-' }}
                                        </td>

                                        <td class="py-2 text-orange-900/80 whitespace-nowrap">
                                            {{ $u->last_activity_at->translatedFormat('d M Y') }}
                                        </td>

                                        <td class="py-2 text-orange-900 font-semibold whitespace-nowrap">
                                            {{ $u->deactivate_at->translatedFormat('d M Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                @if ($isManager)
                    <p class="mt-3 text-xs text-orange-900/70">
                        *List ini hanya menampilkan bawahan Anda.
                    </p>
                @elseif($isAdmin)
                    <p class="mt-3 text-xs text-orange-900/70">
                        *List ini menampilkan seluruh Health Planner.
                    </p>
                @endif
            @endif
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Units Sold</p>
                    <p class="mt-2 text-3xl font-bold">0</p>
                    <p class="mt-1 text-xs text-gray-500">Total units in period</p>
                </div>
                <span class="text-blue-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Revenue</p>
                    <p class="mt-2 text-3xl font-bold">Rp 0</p>
                    <p class="mt-1 text-xs text-gray-500">Total revenue</p>
                </div>
                <span class="text-green-600 font-bold">$</span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Conversion Rate</p>
                    <p class="mt-2 text-3xl font-bold">75.5%</p>
                    <p class="mt-1 text-xs text-gray-500">Success rate</p>
                </div>
                <span class="text-orange-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 11V5a1 1 0 112 0v6a1 1 0 01-2 0zm1 10a9 9 0 100-18 9 9 0 000 18z" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Rank</p>
                    <p class="mt-2 text-3xl font-bold">#-</p>
                    <p class="mt-1 text-xs text-gray-500">Company ranking</p>
                </div>
                <span class="text-purple-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
        </div>
    </div>

    {{-- Top Sales Leaderboard --}}
    <div class="mt-8 rounded-2xl bg-white shadow-sm border">
        <div class="px-6 py-5 border-b">
            <h2 class="text-xl font-bold">Top Sales Leaderboard</h2>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="text-left font-semibold py-3">Rank</th>
                            <th class="text-left font-semibold py-3">Salesperson</th>
                            <th class="text-left font-semibold py-3">Units</th>
                            <th class="text-left font-semibold py-3">Revenue</th>
                            <th class="text-left font-semibold py-3">Avg Deal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="py-10 text-center text-gray-500">
                                No data for this range
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Bottom cards --}}
    <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold">Sales Trend</h2>
                <button class="rounded-lg border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                    Weekly
                    <span class="inline-block align-middle ml-1">▾</span>
                </button>
            </div>
            <div class="mt-6 h-56 rounded-xl bg-gray-50 border flex items-center justify-center text-gray-400">
                Chart placeholder
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <h2 class="text-xl font-bold">Product Mix</h2>
            <div class="mt-6 h-56 rounded-xl bg-gray-50 border flex items-center justify-center text-gray-400">
                Chart placeholder
            </div>
        </div>
    </div>
</x-dashboard-layout>
