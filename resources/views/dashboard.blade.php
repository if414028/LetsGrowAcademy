<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight">Overview</h1>
            <p class="mt-2 text-gray-500">Your sales performance at a glance</p>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative">
                <button class="inline-flex items-center gap-2 rounded-lg border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    This Week
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6"/>
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
                              d="M11 11V5a1 1 0 112 0v6a1 1 0 01-2 0zm1 10a9 9 0 100-18 9 9 0 000 18z"/>
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
                              d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
