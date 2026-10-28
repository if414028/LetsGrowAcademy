<x-dashboard-layout>
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">HP Status</h1>
        <p class="mt-1 text-sm text-gray-500">Pantau aktivitas penjualan Health Planner dan status user.</p>
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
                            Peringatan Aktivitas Penjualan
                        </h3>

                        @if ($isHP)
                            <p class="mt-1 text-sm text-orange-900/80">
                                Anda sudah <span class="font-semibold">5 bulan</span> tidak memiliki instalasi selesai.
                                Jika tidak ada instalasi selesai sampai
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
                                Berikut daftar Health Planner yang sudah 5 bulan tidak memiliki instalasi selesai dan akan
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
                <details class="group mt-4">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 rounded-lg border border-orange-200 bg-orange-100 px-4 py-3 text-sm font-semibold text-orange-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600 [&::-webkit-details-marker]:hidden">
                        <span>{{ $soDeactivationWarnings->count() }} Health Planner dalam peringatan</span>
                        <span class="flex items-center gap-2">
                            <span class="group-open:hidden">Lihat daftar</span>
                            <span class="hidden group-open:inline">Tutup daftar</span>
                            <svg class="h-4 w-4 transition-transform group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                            </svg>
                        </span>
                    </summary>
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
                </details>
            @endif
        </div>
    @endif

    @if ($inactiveDownlines->isNotEmpty())
        <section class="mt-6 rounded-2xl border border-gray-200 bg-gray-50 p-6 shadow-sm" aria-labelledby="inactive-downlines-heading">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="inactive-downlines-heading" class="text-lg font-bold text-gray-800">User Nonaktif</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $authUser->hasAnyRole(['Admin', 'Head Admin']) ? 'Daftar seluruh user yang berstatus nonaktif.' : 'Daftar downline Anda yang berstatus nonaktif, termasuk downline tidak langsung.' }}
                    </p>
                </div>
                <span class="rounded-lg border border-gray-300 bg-gray-200 px-2 py-1 text-xs font-semibold text-gray-700">Inactive</span>
            </div>

            <details class="group mt-4">
                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-300 bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 [&::-webkit-details-marker]:hidden">
                    <span>{{ $inactiveDownlines->count() }} user nonaktif</span>
                    <span class="flex items-center gap-2">
                        <span class="group-open:hidden">Lihat daftar</span>
                        <span class="hidden group-open:inline">Tutup daftar</span>
                        <svg class="h-4 w-4 transition-transform group-open:rotate-180" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                        </svg>
                    </span>
                </summary>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="text-gray-600">
                            <tr class="border-b border-gray-300">
                                <th class="py-2 text-left font-semibold">User</th>
                                <th class="py-2 text-left font-semibold">Role</th>
                                <th class="py-2 text-left font-semibold">Health Manager</th>
                                <th class="py-2 text-left font-semibold">DST</th>
                                <th class="py-2 text-left font-semibold">Status</th>
                                <th class="py-2 text-left font-semibold">Tanggal Nonaktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inactiveDownlines as $downline)
                                <tr class="border-b border-gray-200">
                                    <td class="py-2 pr-4">
                                        <div class="font-semibold text-gray-800">{{ $downline->name }}</div>
                                        <div class="text-xs text-gray-600">{{ $downline->email }}</div>
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $downline->roles->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $downline->health_manager_name }}</td>
                                    <td class="whitespace-nowrap py-2 pr-4 text-gray-600">{{ $downline->dst_code ?? '-' }}</td>
                                    <td class="py-2 font-semibold text-gray-700">Inactive</td>
                                    <td class="whitespace-nowrap py-2 pl-4 text-gray-600">{{ $downline->deactivated_at?->translatedFormat('d M Y') ?? 'Belum tercatat' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    @endif

    @if (!$hasWarning && $inactiveDownlines->isEmpty())
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-500 shadow-sm">
            Tidak ada peringatan aktivitas penjualan atau user nonaktif.
        </div>
    @endif
</x-dashboard-layout>
