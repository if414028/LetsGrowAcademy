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
                    <p class="text-sm text-gray-500">Total Net Sales</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalUnitsSold ?? 0) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Total units terjual (Anda + Tim Anda)</p>
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
                    <p class="text-sm text-gray-500">Total Penjualan Individu</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalSalesIndividu ?? 0) }} Unit</p>
                    <p class="mt-1 text-xs text-gray-500">Total unit terjual (customer individu).</p>
                </div>
                <span class="text-teal-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 20v-1a7 7 0 0114 0v1" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Penjualan Corporate</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalSalesCorporate ?? 0) }} Unit</p>
                    <p class="mt-1 text-xs text-gray-500">Total unit terjual (customer corporate).</p>
                </div>
                <span class="text-rose-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21h18M5 21V7a2 2 0 012-2h4v16M13 21V3h4a2 2 0 012 2v16" />
                    </svg>
                </span>
            </div>
        </div>


        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Produk Satuan</p>
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

        <a href="{{ route('profile') }}#downline-tree"
            class="block rounded-2xl bg-white p-6 shadow-sm border hover:shadow transition cursor-pointer">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Partners</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalActiveDownline ?? 0) }} Orang</p>
                    <p class="mt-1 text-xs text-gray-500">Total Partners yang aktif.</p>
                    <p class="mt-3 text-sm font-semibold text-blue-600">Lihat Health Planner →</p>
                </div>

                <div class="text-indigo-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="7" r="3" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 20v-1a5 5 0 015-5h4a5 5 0 015 5v1" />
                        <circle cx="4" cy="9" r="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 20v-1a4 4 0 014-4" />
                        <circle cx="20" cy="9" r="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 20v-1a4 4 0 00-4-4" />
                    </svg>
                </div>
            </div>
        </a>

        <div class="rounded-2xl bg-white p-6 shadow-sm border">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Health Planner (Aktif Bulan Ini)</p>
                    <p class="mt-2 text-3xl font-bold">
                        {{ number_format($totalActiveHealthPlannersThisMonth ?? 0) }} Orang
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        @if (auth()->user()->hasAnyRole(['Admin', 'Head Admin']))
                            Seluruh Health Planner yang membuat minimal 1 SO di bulan ini.
                        @else
                            Health Planner di bawah Anda (multi-level) yang membuat minimal 1 SO di bulan ini.
                        @endif
                    </p>
                </div>

                <span class="text-sky-600">
                    {{-- Icon --}}
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="7" r="3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 21v-1a7 7 0 0114 0v1" />
                    </svg>
                </span>
            </div>
        </div>


        @if (auth()->user()->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']))
            <div class="rounded-2xl bg-white p-6 shadow-sm border">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Health Manager</p>
                        <p class="mt-2 text-3xl font-bold">
                            {{ number_format($totalActiveHealthManagers ?? 0) }} Orang
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Total Health Manager yang aktif.
                        </p>
                    </div>

                    <span class="text-indigo-600">
                        <!-- Icon Team / Leader -->
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="7" r="3" stroke-width="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 21v-1a7 7 0 0114 0v1" />
                        </svg>
                    </span>
                </div>
            </div>
        @endif

    </div>

    @if (auth()->user()->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']))
        <div class="mt-8 rounded-2xl bg-white shadow-sm border">
            <div class="p-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">Health Manager Performance</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Total unit terjual (SO selesai) oleh Health Manager + tim nya (multi-level).
                    </p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <div class="pl-6 pr-12 pb-6">
                    <table class="min-w-[920px] w-full text-sm">
                        <thead class="text-gray-500">
                            <tr class="border-b">
                                <th class="text-left font-semibold py-3">Health Manager</th>
                                <th class="text-left font-semibold py-3">DST</th>
                                <th class="text-center font-semibold py-3">Team Size</th>
                                <th class="text-center font-semibold py-3">Total Units</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($healthManagerPerformance ?? [] as $hm)
                                <tr class="border-b last:border-b-0">
                                    <td class="py-3">
                                        <div class="font-semibold text-gray-900">{{ $hm->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $hm->email }}</div>
                                    </td>

                                    <td class="py-3 text-gray-700">
                                        {{ $hm->dst_code ?? '-' }}
                                    </td>

                                    <td class="py-3 text-center text-gray-700">
                                        {{ number_format($hm->team_size ?? 0) }}
                                    </td>

                                    <td class="py-3 text-center font-bold text-gray-900">
                                        {{ number_format($hm->units ?? 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500">
                                        Belum ada data Health Manager.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if (auth()->user()->hasRole('Sales Manager'))
                        <p class="mt-3 text-xs text-gray-500">
                            *List ini hanya menampilkan Health Manager di bawah Anda.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8 rounded-2xl bg-white shadow-sm border">
        <div class="p-6 pb-4">
            <h2 class="text-xl font-bold">Kontes Berlangsung</h2>
            <p class="mt-1 text-sm text-gray-500">
                Daftar kontes aktif yang Anda ikuti.
            </p>
        </div>

        <div class="px-6 pb-6">
            @forelse($activeContests as $contest)
                <a href="{{ route('contests.show', $contest) }}"
                    class="block rounded-xl border p-4 mb-4 hover:bg-gray-50 transition">

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">
                                {{ $contest->title }}
                            </p>

                            <p class="text-xs text-gray-500 mt-1">
                                {{ \Carbon\Carbon::parse($contest->start_date)->translatedFormat('d M Y') }}
                                —
                                {{ \Carbon\Carbon::parse($contest->end_date)->translatedFormat('d M Y') }}
                            </p>
                        </div>

                        <span class="text-amber-600">
                            <!-- Trophy icon -->
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 21h8M12 17v4M7 4h10v3a5 5 0 01-10 0V4z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 7H4a2 2 0 00-2 2v1a4 4 0 004 4M19 7h1a2 2 0 012 2v1a4 4 0 01-4 4" />
                            </svg>
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-6 text-sm text-gray-500">
                    Tidak ada kontes aktif yang Anda ikuti.
                </div>
            @endforelse
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
