<x-dashboard-layout>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
                <p class="text-sm text-gray-500">Pantau perkembangan kinerja penjualan secara berkala.</p>
            </div>
        </div>

        {{-- Tabs: Weekly / Monthly --}}
        <div class="inline-flex rounded-xl bg-gray-100 p-1">
            <a href="{{ route('reports.index', ['period' => 'weekly']) }}"
               class="px-6 py-2 rounded-lg text-sm font-medium
               {{ $period === 'weekly' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                Weekly
            </a>
            <a href="{{ route('reports.index', ['period' => 'monthly']) }}"
               class="px-6 py-2 rounded-lg text-sm font-medium
               {{ $period === 'monthly' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                Monthly
            </a>
        </div>

        {{-- Banner --}}
        @php
            $isMonthly = $period === 'monthly';

            $gradientClass = $isMonthly
                ? 'bg-gradient-to-r from-green-600 to-green-500'
                : 'bg-gradient-to-r from-blue-600 to-blue-500';

            $iconBg = $isMonthly ? 'bg-white/20' : 'bg-white/15';
        @endphp

        <div class="rounded-2xl {{ $gradientClass }} px-6 py-6 text-white shadow">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl {{ $iconBg }} flex items-center justify-center">
                        {{-- Icon --}}
                        @if($isMonthly)
                            {{-- Monthly icon --}}
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4 16L9 11L13 15L20 8"
                                    stroke="white"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M14 8H20V14"
                                    stroke="white"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        @else
                            {{-- Weekly icon --}}
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 6h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z" />
                            </svg>
                        @endif
                    </div>

                    <div>
                        <div class="text-sm opacity-90">
                            {{ $isMonthly ? 'Monthly Report' : 'Weekly Report' }}
                        </div>
                        <div class="text-2xl font-semibold">
                            {{ $rangeLabel }}
                        </div>
                        <div class="text-xs opacity-80 mt-1">
                            {{ \Carbon\Carbon::parse($start)->format('d M Y') }}
                            —
                            {{ \Carbon\Carbon::parse($end)->format('d M Y') }}
                        </div>
                    </div>
                </div>

                {{-- ❌ CSV / PDF dihilangkan --}}
            </div>
        </div>

        {{-- Top Performers Chart --}}
        <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Top Performers – {{ $period === 'weekly' ? 'Weekly' : 'Monthly' }}
                </h2>
                <div class="text-sm text-gray-500">
                    Top 10 (auto)
                </div>
            </div>

            <div class="mt-4">
                @if($topPerformers->count() === 0)
                    <div class="text-sm text-gray-500">No data available for this period.</div>
                @else
                    <div class="h-72">
                        <canvas id="topPerformersChart"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Leaderboard --}}
        <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Leaderboard</h2>
            <p class="text-sm text-gray-500 mt-1">
                All downline sales for selected period.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">Rank</th>
                            <th class="py-3 pr-4">Salesperson</th>
                            <th class="py-3 pr-4">Units Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($leaderboard as $row)
                            <tr class="text-gray-800">
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold
                                        {{ $row['rank'] === 1 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }}">
                                        #{{ $row['rank'] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 font-medium">{{ $row['name'] }}</td>
                                <td class="py-3 pr-4">{{ number_format($row['units']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500">
                                    No data available for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if($topPerformers->count() > 0)
        const ctx = document.getElementById('topPerformersChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Units Sold',
                    data: @json($chartUnits),
                    borderWidth: 1,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
        @endif
    </script>
</x-dashboard-layout>