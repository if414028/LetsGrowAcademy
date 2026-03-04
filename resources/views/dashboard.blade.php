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

    {{-- Birthday celebration section --}}
    @php
        $authUser = request()->user();
        $isAdminOrHead = $authUser->hasAnyRole(['Admin', 'Head Admin']);
        $todayBirthdays = $todayBirthdays ?? collect();
    @endphp

    @if (($isBirthdayToday ?? false) || $todayBirthdays->count() > 0)
        <div class="mt-8 space-y-6">

            {{-- Self Birthday Mega Card --}}
            @if ($isBirthdayToday ?? false)
                <div class="relative overflow-hidden rounded-2xl border shadow-sm">
                    {{-- background gradient (di bawah semua) --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-red-600 via-rose-500 to-pink-500 z-0"></div>

                    {{-- confetti canvas (di atas bg) --}}
                    <canvas id="confettiCanvas"
                        class="pointer-events-none absolute inset-0 w-full h-full z-10"></canvas>

                    {{-- content (paling atas) --}}
                    <div class="relative z-20 p-6 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-3 py-1 text-xs font-semibold">
                                    <span class="h-2 w-2 rounded-full bg-yellow-300"></span>
                                    It’s your day!
                                </div>

                                <h2 class="mt-3 text-2xl md:text-3xl font-extrabold tracking-tight">
                                    Selamat Ulang Tahun, {{ $authUser->name }}! 🎉
                                </h2>

                                <p class="mt-2 text-sm text-white/90 max-w-2xl">
                                    Tuhan memberkati langkahmu hari ini dan seterusnya. Semoga makin sehat, makin
                                    diberkati,
                                    dan makin berdampak. 🙌
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold border border-white/20">
                                        🎂 Happy Birthday
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold border border-white/20">
                                        ✨ Be blessed
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold border border-white/20">
                                        🚀 Keep growing
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- subtle decorations --}}
                        <div class="absolute -right-24 -top-24 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                        <div class="absolute -left-24 -bottom-24 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
                    </div>
                </div>
            @endif

            {{-- Team Birthdays Celebration --}}
            @if ($todayBirthdays->count() > 0)
                {{-- IMPORTANT: overflow-visible so glow/badge won't be clipped --}}
                <div class="rounded-2xl bg-white shadow-sm border">
                    <div class="p-6 pb-4 flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-bold">Ulang Tahun Hari Ini</h2>

                                <span
                                    class="inline-flex items-center gap-2 rounded-full bg-amber-50 text-amber-800 border border-amber-100 px-3 py-1 text-xs font-semibold">
                                    🎉 {{ $todayBirthdays->count() }} orang
                                </span>
                            </div>

                            <p class="mt-2 text-sm text-gray-500">
                                Jangan lupa kirim ucapan — kecil tapi berkesan 🙂
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($todayBirthdays as $u)
                                <a href="{{ route('users.show', $u->id) }}"
                                    class="block relative rounded-2xl border p-4 bg-white transition
          shadow-sm hover:shadow-md hover:-translate-y-0.5 overflow-hidden
          focus:outline-none focus:ring-2 focus:ring-indigo-500">

                                    {{-- GLOW INSIDE CARD --}}
                                    <div class="pointer-events-none absolute inset-0">
                                        <div
                                            class="absolute -left-16 -top-16 h-36 w-36 rounded-full bg-pink-200/25 blur-2xl">
                                        </div>
                                        <div
                                            class="absolute -right-16 -bottom-16 h-36 w-36 rounded-full bg-amber-200/25 blur-2xl">
                                        </div>
                                    </div>

                                    {{-- ALWAYS-ON badge --}}
                                    <div class="absolute right-3 top-3 rotate-12 z-10">
                                        <div
                                            class="rounded-full bg-gradient-to-r from-pink-500 to-amber-400 text-white text-[10px]
                   font-extrabold px-3 py-1 shadow-sm border border-white/30">
                                            BIRTHDAY
                                        </div>
                                    </div>

                                    <div class="relative flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 truncate">
                                                {{ $u->name }}
                                            </div>

                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $u->role_name }} • DST: {{ $u->dst_code ?? '-' }}
                                            </div>

                                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full bg-pink-50 text-pink-700 border border-pink-100 px-3 py-1 font-semibold">
                                                    🎂 {{ $u->dob_fmt }}
                                                </span>
                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full bg-gray-50 text-gray-700 border border-gray-100 px-3 py-1">
                                                    {{ $u->age }} tahun
                                                </span>
                                            </div>

                                            <div class="mt-3 text-xs text-gray-500">
                                                Email: <span
                                                    class="font-semibold text-gray-700">{{ $u->email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
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
                    <p class="text-sm text-gray-500">Total Penjualan Produk Satuan</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalSalesProductSatuan ?? 0) }} Unit</p>
                    <p class="mt-1 text-xs text-gray-500">Total qty terjual untuk produk satuan.</p>
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
                    <p class="text-sm text-gray-500">Total Penjualan Produk Bundling</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($totalSalesProductBundling ?? 0) }} Unit
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Total unit terjual.
                    </p>
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
                            Health Planner di bawah Anda yang membuat minimal 1 SO di bulan ini.
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

                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Thumbnail --}}
                            @php
                                $img = $contest->banner_url
                                    ? asset('storage/' . ltrim($contest->banner_url, '/'))
                                    : null;
                            @endphp

                            <div
                                class="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                @if ($img)
                                    <img src="{{ $img }}" alt="{{ $contest->title }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <span class="text-xs font-semibold text-gray-500">
                                        {{ strtoupper(mb_substr($contest->title ?? 'C', 0, 1)) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Text --}}
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">
                                    {{ $contest->title }}
                                </p>

                                <p class="text-xs text-gray-500 mt-1">
                                    {{ \Carbon\Carbon::parse($contest->start_date)->translatedFormat('d M Y') }}
                                    —
                                    {{ \Carbon\Carbon::parse($contest->end_date)->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>

                        <span class="text-amber-600 flex-shrink-0">
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

            (function() {
                // --- tiny confetti engine (no deps) ---
                window.burstConfetti = function burstConfetti(canvasId, durationMs = 2000) {
                    const canvas = document.getElementById(canvasId);
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');
                    const rect = canvas.getBoundingClientRect();

                    const dpr = window.devicePixelRatio || 1;
                    canvas.width = Math.floor(rect.width * dpr);
                    canvas.height = Math.floor(rect.height * dpr);
                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                    const W = rect.width;
                    const H = rect.height;

                    const rand = (min, max) => Math.random() * (max - min) + min;

                    const pieces = [];
                    const count = Math.min(140, Math.floor(W * 0.22));

                    for (let i = 0; i < count; i++) {
                        pieces.push({
                            x: rand(0, W),
                            y: rand(-H * 0.2, 0),
                            vy: rand(1.6, 2.8),
                            vx: rand(-1.1, 1.1),
                            g: rand(0.02, 0.045),
                            r: rand(3, 6),
                            a: rand(0, Math.PI * 2),
                            va: rand(-0.12, 0.12),
                            color: `hsl(${Math.floor(rand(0, 360))} 90% 60%)`
                        });
                    }

                    let start = performance.now();
                    let raf;

                    function frame(now) {
                        const elapsed = now - start;
                        ctx.clearRect(0, 0, W, H);

                        for (const p of pieces) {
                            p.vy += p.g;
                            p.y += p.vy;
                            p.x += p.vx;
                            p.a += p.va;

                            ctx.save();
                            ctx.translate(p.x, p.y);
                            ctx.rotate(p.a);
                            ctx.fillStyle = p.color;
                            ctx.fillRect(-p.r, -p.r / 2, p.r * 2, p.r);
                            ctx.restore();
                        }

                        if (elapsed < durationMs) raf = requestAnimationFrame(frame);
                        else {
                            ctx.clearRect(0, 0, W, H);
                            cancelAnimationFrame(raf);
                        }
                    }

                    requestAnimationFrame(frame);
                };

                // Auto burst if self birthday
                const canvas = document.getElementById('confettiCanvas');
                if (canvas) {
                    // slight delay biar keliatan “pop”
                    setTimeout(() => burstConfetti('confettiCanvas', 6000), 250);

                    const btn = document.getElementById('confettiBtn');
                    if (btn) btn.addEventListener('click', () => burstConfetti('confettiCanvas', 6000));
                }

                const teamBtn = document.getElementById('confettiTeamBtn');
                if (teamBtn) {
                    teamBtn.addEventListener('click', () => {
                        // if self canvas exists, burst there; else make a temporary overlay canvas
                        const c = document.getElementById('confettiCanvas');
                        if (c) return burstConfetti('confettiCanvas', 6000);

                        // fallback: create overlay canvas on body
                        const tmp = document.createElement('canvas');
                        tmp.id = '__confettiTmp';
                        tmp.className = 'pointer-events-none fixed inset-0 w-full h-full z-[60]';
                        document.body.appendChild(tmp);

                        burstConfetti('__confettiTmp', 1200);
                        setTimeout(() => tmp.remove(), 1300);
                    });
                }

                // Refit canvas on resize (only if exists)
                window.addEventListener('resize', () => {
                    const c = document.getElementById('confettiCanvas');
                    if (!c) return;
                    // next burst will resize; no need to do heavy work here
                });
            })();

            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('confettiCanvas');
                if (canvas) {
                    setTimeout(() => window.burstConfetti('confettiCanvas', 6000), 250);

                    const btn = document.getElementById('confettiBtn');
                    if (btn) btn.addEventListener('click', () => window.burstConfetti('confettiCanvas', 6000));
                }
            });
        </script>
    @endpush
</x-dashboard-layout>
