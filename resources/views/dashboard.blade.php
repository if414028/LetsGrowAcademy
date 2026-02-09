<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight">Overview</h1>
            <p class="mt-2 text-gray-500">Your sales performance at a glance</p>
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
                    <p class="text-sm text-gray-500">Total Unit Terjual</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalUnitsSold ?? 0) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Total units terjual (Anda + bawahan)</p>
                </div>
                <span class="text-blue-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Produk Reguler</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalRegularProducts ?? 0) }} Unit</p>
                    <p class="mt-1 text-xs text-gray-500">Total produk reguler yang aktif.</p>
                </div>
                <span class="text-green-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5h6M9 9h6M9 13h6M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h6" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Bundling</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalBundlings ?? 0) }} Bundling</p>
                    <p class="mt-1 text-xs text-gray-500">Total bundling yang aktif.</p>
                </div>
                <span class="text-purple-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Downline</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalActiveDownline ?? 0) }} Orang</p>
                    <p class="mt-1 text-xs text-gray-500">Total downline yang aktif.</p>
                </div>
                <div class="text-indigo-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <!-- leader -->
                        <circle cx="12" cy="7" r="3" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 20v-1a5 5 0 015-5h4a5 5 0 015 5v1" />

                        <!-- left downline -->
                        <circle cx="4" cy="9" r="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 20v-1a4 4 0 014-4" />

                        <!-- right downline -->
                        <circle cx="20" cy="9" r="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 20v-1a4 4 0 00-4-4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom cards --}}
    <div class="mt-8 rounded-2xl bg-white shadow-sm border">
        {{-- Sales Trend --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold">Sales Trend</h2>

                <form method="GET" class="relative">
                    @foreach (request()->except('trend') as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach

                    <select name="trend" onchange="this.form.submit()"
                        class="appearance-none rounded-lg border bg-white pl-3 pr-9 py-2 text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                        <option value="weekly" {{ ($trend ?? 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly
                        </option>
                        <option value="monthly" {{ ($trend ?? 'weekly') === 'monthly' ? 'selected' : '' }}>Monthly
                        </option>
                    </select>

                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </form>
            </div>

            <div class="mt-6 h-56 rounded-xl bg-gray-50 border p-3">
                <canvas id="salesTrendChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

        <script>
            (function() {
                const el = document.getElementById('salesTrendChart');
                if (!el) return;

                const labels = @json($salesTrendLabels ?? []);
                const dataUnits = @json($salesTrendUnits ?? []);

                // destroy previous instance (jika ada hot reload / livewire)
                if (window.__salesTrendChart) {
                    window.__salesTrendChart.destroy();
                }

                window.__salesTrendChart = new Chart(el, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Units',
                            data: dataUnits,
                            tension: 0.35,
                            pointRadius: 3,
                            borderWidth: 2,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            })();
        </script>
    @endpush
</x-dashboard-layout>
