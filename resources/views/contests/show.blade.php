@php
    use Illuminate\Support\Str;

    $st = $contest->status ?? 'draft';
    $badge = match ($st) {
        'active', 'published' => ['bg-green-100 text-green-700', 'Aktif'],
        'ended', 'finished' => ['bg-gray-100 text-gray-700', 'Selesai'],
        default => ['bg-yellow-100 text-yellow-700', 'Draft'],
    };

    $start = $contest->start_date ? $contest->start_date->format('d M Y') : '-';
    $end = $contest->end_date ? $contest->end_date->format('d M Y') : '-';

    $maxInstallDateObj = $contest->max_install_date ?? $contest->end_date;
    $maxInstall = $maxInstallDateObj ? $maxInstallDateObj->format('d M Y') : '-';

    $type = $contest->type ?? 'leaderboard';
    $rules = (array) ($contest->rules ?? []);
    $isQualifier = $type === 'qualifier';

    $months = $months ?? [];
    $winners = $winners ?? [];
    $rows = $rows ?? [];

    $minPersonal = array_key_exists('monthly_min_personal_ns', $rules) ? (int) $rules['monthly_min_personal_ns'] : null;
    $minDirect = array_key_exists('monthly_min_direct_active_partner', $rules)
        ? (int) $rules['monthly_min_direct_active_partner']
        : null;
    $minPartnerActive = (int) ($rules['direct_partner_active_min_personal_ns'] ?? 1);

    $productFilterType = $rules['product_filter_type'] ?? 'all';
    $selectedProductIds = (array) ($rules['product_ids'] ?? []);
    $productMinQtys = (array) ($rules['product_min_qtys'] ?? []);

    $productOptionMap = collect($productOptions ?? [])->keyBy('value');
    $productLabels = [];
    foreach ($selectedProductIds as $value) {
        $pid = Str::startsWith($value, 'product:') ? (int) Str::after($value, 'product:') : null;
        $productLabels[$value] = data_get($productOptionMap->get($value), 'label', 'Product #' . $pid);
    }

    $filterTypeLabel = match ($productFilterType) {
        'specific' => 'Spesifik Produk',
        'exclude' => 'Exclude Produk',
        default => 'Semua Produk',
    };

    $targetUnit = $contest->target_unit;
    $usesTarget = !is_null($targetUnit) && $targetUnit !== '';

    $hasTargetUnit = !is_null($targetUnit) && $targetUnit !== '';
@endphp

<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $contest->title }}</h1>
            <p class="text-sm text-gray-500">
                {{ $isQualifier ? 'Detail kontes dan evaluasi qualifier.' : 'Detail kontes dan ranking Health Planner.' }}
            </p>
        </div>

        <a href="{{ route('contests.index') }}"
            class="inline-flex items-center rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            ← Kembali
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5 lg:col-span-2">
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

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Target</div>

                    @if (!is_null($targetUnit) && $targetUnit !== '')
                        <div class="mt-1 text-lg font-bold text-gray-900">
                            {{ $targetUnit }}
                            <span class="text-sm font-semibold text-gray-500">unit</span>
                        </div>

                        @if ($productFilterType === 'specific')
                            <div class="mt-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Spesifik Produk
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($selectedProductIds as $value)
                                    <span
                                        class="inline-flex items-center rounded-full border bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700">
                                        {{ $productLabels[$value] ?? $value }}
                                        <span
                                            class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                            Min {{ (int) ($productMinQtys[$value] ?? 1) }}
                                        </span>
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-500">Tidak ada produk dipilih.</span>
                                @endforelse
                            </div>
                        @elseif ($productFilterType === 'exclude')
                            <div class="mt-2 text-xs text-gray-500">
                                Target qty dihitung dengan mengecualikan produk tertentu.
                            </div>
                        @endif
                    @else
                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            {{ $filterTypeLabel }}
                        </div>

                        @if ($productFilterType === 'all')
                            <div class="mt-1 text-xs text-gray-500">
                                Semua produk dan bundle ikut dihitung.
                            </div>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($selectedProductIds as $value)
                                    <span
                                        class="inline-flex items-center rounded-full border bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700">
                                        {{ $productLabels[$value] ?? $value }}
                                        @if ($productFilterType === 'specific')
                                            <span
                                                class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                                Min {{ (int) ($productMinQtys[$value] ?? 1) }}
                                            </span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-500">Tidak ada produk dipilih.</span>
                                @endforelse
                            </div>
                        @endif
                    @endif
                </div>

                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Reward</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $contest->reward ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl border bg-gray-50 p-4">
                    <div class="text-xs text-gray-500">Install Maksimum</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $maxInstall }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        SO dihitung dari key-in date pada periode kontes.
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

            @if ($isQualifier)
                <div class="mt-4 rounded-2xl border bg-purple-50 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Rules Qualifier</div>
                            <div class="mt-1 text-xs text-gray-600">
                                Pemenang adalah HP yang <span class="font-semibold">lolos di semua bulan</span> selama
                                periode kontes.
                            </div>
                        </div>
                        <span
                            class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                            QUALIFIER
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @if ($minPersonal !== null)
                            <div class="rounded-xl border bg-white p-4">
                                <div class="text-xs text-gray-500">Personal / Bulan</div>
                                <div class="mt-1 text-sm font-semibold text-gray-900">
                                    ≥ {{ $minPersonal }} unit
                                </div>
                            </div>
                        @endif

                        @if ($minDirect !== null)
                            <div class="rounded-xl border bg-white p-4">
                                <div class="text-xs text-gray-500">Direct Active / Bulan</div>
                                <div class="mt-1 text-sm font-semibold text-gray-900">
                                    ≥ {{ $minDirect }} org
                                </div>
                            </div>
                        @endif

                        @if ($minDirect !== null)
                            <div class="rounded-xl border bg-white p-4">
                                <div class="text-xs text-gray-500">Definisi Active Partner</div>
                                <div class="mt-1 text-sm font-semibold text-gray-900">
                                    Partner qty ≥ {{ $minPartnerActive }} unit / bulan
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 rounded-xl border bg-green-50 p-4 text-sm text-green-800">
                        Total pemenang saat ini: <span class="font-semibold">{{ count($winners) }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
            <div class="text-sm font-semibold text-gray-900">Banner</div>
            <div class="mt-3">
                @if ($contest->banner_url)
                    <img class="w-full rounded-xl border object-cover"
                        src="{{ Str::startsWith($contest->banner_url, ['http://', 'https://'])
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

    @if (($contest->status ?? 'draft') !== 'draft')
        <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
            <div class="border-b p-4">
                <div class="text-lg font-semibold text-gray-900">
                    {{ $isQualifier ? 'Evaluasi Kontes' : 'Ranking Health Planner' }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $isQualifier
                        ? 'Pemenang bisa lebih dari satu: semua HP yang memenuhi syarat di setiap bulan.'
                        : 'Otomatis dihitung dari total qty sales orders “selesai” pada periode kontes.' }}
                </div>
            </div>

            <div class="overflow-x-auto">
                @if ($isQualifier)
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>

                                @foreach ($months as $m)
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">
                                        {{ $m['label'] ?? ($m['key'] ?? '-') }}
                                    </th>
                                @endforeach

                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            @forelse($rows as $r)
                                @php
                                    $isMe = auth()->id() === ($r['user_id'] ?? null);
                                    $isWinner = (bool) ($r['is_winner'] ?? false);
                                    $rowMonths = $r['months'] ?? [];
                                @endphp

                                <tr class="{{ $isMe ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex items-center gap-2">
                                            <div class="font-semibold text-gray-900">{{ $r['name'] ?? '-' }}</div>

                                            @if ($isMe)
                                                <span
                                                    class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                                    Ini kamu
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Harus lolos di semua bulan
                                        </div>
                                    </td>

                                    @foreach ($months as $idx => $m)
                                        @php
                                            $cell = $rowMonths[$idx] ?? null;
                                            $personal = (int) ($cell['personal_ns'] ?? 0);
                                            $activePartner = (int) ($cell['active_partner'] ?? 0);
                                            $ok = (bool) ($cell['eligible'] ?? false);

                                            $personalOk = $minPersonal === null ? true : $personal >= $minPersonal;
                                            $partnerOk = $minDirect === null ? true : $activePartner >= $minDirect;

                                            $personalPct =
                                                $minPersonal && $minPersonal > 0
                                                    ? min(100, (int) round(($personal / $minPersonal) * 100))
                                                    : 100;

                                            $partnerPct =
                                                $minDirect && $minDirect > 0
                                                    ? min(100, (int) round(($activePartner / $minDirect) * 100))
                                                    : 100;
                                        @endphp

                                        <td class="px-4 py-3 align-top">
                                            <div class="min-w-[220px] space-y-3">
                                                <div>
                                                    <div
                                                        class="flex items-center justify-between text-xs text-gray-500">
                                                        <span>Personal</span>
                                                        <span class="font-semibold text-gray-700">
                                                            {{ $personal }} / {{ $minPersonal ?? 0 }} unit
                                                        </span>
                                                    </div>

                                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-200">
                                                        <div class="h-2 rounded-full {{ $personalOk ? 'bg-green-500' : 'bg-red-400' }}"
                                                            style="width: {{ $personalPct }}%"></div>
                                                    </div>
                                                </div>

                                                <div>
                                                    <div
                                                        class="flex items-center justify-between text-xs text-gray-500">
                                                        <span>Partner</span>
                                                        <span class="font-semibold text-gray-700">
                                                            {{ $activePartner }} / {{ $minDirect ?? 0 }} org
                                                        </span>
                                                    </div>

                                                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-200">
                                                        <div class="h-2 rounded-full {{ $partnerOk ? 'bg-green-500' : 'bg-red-400' }}"
                                                            style="width: {{ $partnerPct }}%"></div>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($minPersonal !== null)
                                                        <span
                                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $personalOk ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                            Personal {{ $personalOk ? 'Lolos' : 'Belum' }}
                                                        </span>
                                                    @endif

                                                    @if ($minDirect !== null)
                                                        <span
                                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $partnerOk ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                            Partner {{ $partnerOk ? 'Lolos' : 'Belum' }}
                                                        </span>
                                                    @endif

                                                    <span
                                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $ok ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                                        {{ $ok ? 'Lolos' : 'Tidak' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                    @endforeach

                                    <td class="px-4 py-3 align-top">
                                        @if ($isWinner)
                                            <span
                                                class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                WINNER
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                Belum memenuhi
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + max(1, count($months)) }}"
                                        class="px-4 py-10 text-center text-gray-500">
                                        Belum ada peserta Health Planner atau belum ada data evaluasi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
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
                                    $isMe = auth()->id() === ($r['user_id'] ?? null);
                                @endphp
                                <tr class="{{ $isMe ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        #{{ $r['rank'] ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $r['name'] ?? '-' }}</div>
                                        @if ($isMe)
                                            <div class="text-xs font-semibold text-blue-700">Ini kamu</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($usesTarget)
                                            <div class="w-48 max-w-full">
                                                <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                                                    <div class="h-2 bg-blue-600"
                                                        style="width: {{ (int) ($r['pct'] ?? 0) }}%"></div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ (int) ($r['pct'] ?? 0) }}%
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-sm font-semibold text-gray-900">—</div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                Target tidak digunakan
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        {{ $r['done'] ?? 0 }}
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
                @endif
            </div>
        </div>
    @else
        <div class="mt-6 rounded-2xl border bg-yellow-50 p-4 text-sm text-yellow-800">
            Ranking akan muncul setelah kontes dipublish.
        </div>
    @endif
</x-dashboard-layout>
