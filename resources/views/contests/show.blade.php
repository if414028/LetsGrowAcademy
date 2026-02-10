@php
    $st = $contest->status ?? 'draft';
    $badge = match ($st) {
        'active', 'published' => ['bg-green-100 text-green-700', 'Aktif'],
        'ended', 'finished' => ['bg-gray-100 text-gray-700', 'Selesai'],
        default => ['bg-yellow-100 text-yellow-700', 'Draft'],
    };

    $start = $contest->start_date ? $contest->start_date->format('d M Y') : '-';
    $end = $contest->end_date ? $contest->end_date->format('d M Y') : '-';
@endphp

<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $contest->title }}</h1>
            <p class="text-sm text-gray-500">Detail kontes dan ranking Health Planner.</p>
        </div>

        <a href="{{ route('contests.index') }}"
            class="inline-flex items-center rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            ← Kembali
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Info --}}
        <div class="lg:col-span-2 rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm text-gray-500">
                    Periode: <span class="font-semibold text-gray-900">{{ $start }} - {{ $end }}</span>
                </div>

                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[0] }}">
                    {{ $badge[1] }}
                </span>
            </div>

            @if ($contest->description)
                <div class="mt-4 text-sm text-gray-700">
                    {{ $contest->description }}
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Target</div>
                    <div class="mt-1 text-lg font-bold text-gray-900">
                        {{ $contest->target_unit ?? '-' }}
                        <span class="text-sm font-semibold text-gray-500">unit</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Reward</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $contest->reward ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Dibuat oleh</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ optional($contest->creator)->name ?? '-' }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ optional($contest->creatorRole)->name ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Banner --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
            <div class="text-sm font-semibold text-gray-900">Banner</div>
            <div class="mt-3">
                @if ($contest->banner_url)
                    <img class="w-full rounded-xl border object-cover"
                        src="{{ \Illuminate\Support\Str::startsWith($contest->banner_url, ['http://', 'https://'])
                            ? $contest->banner_url
                            : asset('storage/' . ltrim($contest->banner_url, '/')) }}"
                        alt="Banner">
                @else
                    <div class="rounded-xl border bg-gray-50 p-6 text-sm text-gray-500">
                        Tidak ada banner.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Leaderboard --}}
    @if (($contest->status ?? 'draft') !== 'draft')
        <div class="mt-6 rounded-2xl border bg-white overflow-hidden">
            <div class="border-b p-4">
                <div class="text-lg font-semibold text-gray-900">Ranking Health Planner</div>
                <div class="text-sm text-gray-500">Otomatis dihitung dari Sales Orders “selesai” pada periode kontes.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Rank</th>
                            <th class="px-4 py-3 text-left font-semibold">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold">Progress</th>
                            <th class="px-4 py-3 text-left font-semibold">Unit</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @forelse($rows as $r)
                            @php
                                $isMe = auth()->id() === $r['user_id'];
                            @endphp
                            <tr class="{{ $isMe ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    #{{ $r['rank'] }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $r['name'] }}</div>
                                    @if ($isMe)
                                        <div class="text-xs text-blue-700 font-semibold">Ini kamu</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="w-48 max-w-full">
                                        <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                                            <div class="h-2 bg-blue-600" style="width: {{ $r['pct'] }}%"></div>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $r['pct'] }}%</div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $r['done'] }}
                                    <span class="text-xs font-semibold text-gray-500">unit</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-gray-500">
                                    Belum ada peserta Health Planner atau belum ada data progress.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-2xl border bg-yellow-50 p-4 text-sm text-yellow-800">
            Ranking akan muncul setelah kontes dipublish.
        </div>
    @endif
</x-dashboard-layout>
