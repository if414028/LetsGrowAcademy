<x-dashboard-layout>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
                <p class="text-sm text-gray-500">Pantau perkembangan kinerja penjualan secara berkala.</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-4">
            <form method="GET" action="{{ route('reports.index') }}"
                class="flex flex-col md:flex-row md:items-end gap-4">
                <div>
                    <label for="from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="from" id="from" value="{{ $from }}"
                        class="w-full md:w-44 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="to" id="to" value="{{ $to }}"
                        class="w-full md:w-44 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Apply
                    </button>

                    <a href="{{ route('reports.index') }}"
                        class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                        Reset
                    </a>
                </div>
            </form>

            <p class="mt-3 text-xs text-gray-500">
                Default filter menggunakan <span class="font-semibold">Closing Date</span>.
            </p>
        </div>

        {{-- Banner --}}
        <div class="rounded-2xl bg-gradient-to-r from-green-600 to-green-500 px-6 py-6 text-white shadow">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 16L9 11L13 15L20 8" stroke="white" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M14 8H20V14" stroke="white" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <div>
                    <div class="text-sm opacity-90">Report by {{ $rangeLabel }}</div>
                    <div class="text-2xl font-semibold">Selected Range</div>
                    <div class="text-xs opacity-80 mt-1">
                        {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                        —
                        {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Leaderboard Health Manager --}}
        @if ($showHmLeaderboard)
            <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Leaderboard Health Manager
                    </h2>
                    <div class="text-sm text-gray-500">Top 10</div>
                </div>

                <div class="mt-4">
                    @if ($hmLeaderboard->count() === 0)
                        <div class="text-sm text-gray-500">No data available for selected closing date.</div>
                    @else
                        <div class="h-72">
                            <canvas id="hmLeaderboardChart"></canvas>
                        </div>
                    @endif
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-3 pr-4">Rank</th>
                                <th class="py-3 pr-4">Health Manager</th>
                                <th class="py-3 pr-4">Total NS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($hmLeaderboard as $row)
                                <tr class="text-gray-800">
                                    <td class="py-3 pr-4">
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold {{ $row['rank'] === 1 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }}">
                                            #{{ $row['rank'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 font-medium">{{ $row['name'] }}</td>
                                    <td class="py-3 pr-4">{{ number_format($row['units']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-gray-500">
                                        No data available for selected closing date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Leaderboard Health Planner --}}
        <div class="bg-white rounded-2xl shadow ring-1 ring-black/5 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Leaderboard Health Planner
                </h2>
                <div class="text-sm text-gray-500">Top 10</div>
            </div>

            <div class="mt-4">
                @if ($hpLeaderboard->count() === 0)
                    <div class="text-sm text-gray-500">No data available for selected closing date.</div>
                @else
                    <div class="h-72">
                        <canvas id="hpLeaderboardChart"></canvas>
                    </div>
                @endif
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">Rank</th>
                            <th class="py-3 pr-4">Health Planner</th>
                            <th class="py-3 pr-4">Total NS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($hpLeaderboard as $row)
                            <tr class="text-gray-800">
                                <td class="py-3 pr-4">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold {{ $row['rank'] === 1 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }}">
                                        #{{ $row['rank'] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4 font-medium">{{ $row['name'] }}</td>
                                <td class="py-3 pr-4">{{ number_format($row['units']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-gray-500">
                                    No data available for selected closing date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if ($showHmLeaderboard && $hmLeaderboard->count() > 0)
            new Chart(document.getElementById('hmLeaderboardChart'), {
                type: 'bar',
                data: {
                    labels: @json($hmChartLabels),
                    datasets: [{
                        label: 'Units Sold',
                        data: @json($hmChartUnits),
                        borderWidth: 1,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        @endif

        @if ($hpLeaderboard->count() > 0)
            new Chart(document.getElementById('hpLeaderboardChart'), {
                type: 'bar',
                data: {
                    labels: @json($hpChartLabels),
                    datasets: [{
                        label: 'Units Sold',
                        data: @json($hpChartUnits),
                        borderWidth: 1,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        @endif
    </script>
</x-dashboard-layout>
