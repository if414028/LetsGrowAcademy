@php
    $rawChildren = $node['children'] ?? [];

    // FILTER: anggap "node valid" kalau punya id (paling aman)
    // kalau di data kamu id kadang kosong tapi tetap valid, ganti patokan ke name
    $children = collect($rawChildren)
        ->filter(fn($c) => !empty($c['id'])) // atau: !empty($c['name'])
        ->values()
        ->all();

    $hasChildren = count($children) > 0;

    $initial = strtoupper(mb_substr($node['name'] ?? 'U', 0, 1));
    $photoUrl = !empty($node['photo']) ? asset('storage/' . $node['photo']) : null;

    $role = $node['role'] ?? null;

    $roleConfig = match ($role) {
        'Admin', 'Head Admin' => [
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-700',
            'border' => 'border-blue-200',
            'dot' => 'bg-blue-500',
        ],
        'Sales Manager' => [
            'bg' => 'bg-green-50',
            'text' => 'text-green-700',
            'border' => 'border-green-200',
            'dot' => 'bg-green-500',
        ],
        'Health Manager' => [
            'bg' => 'bg-yellow-50',
            'text' => 'text-yellow-800',
            'border' => 'border-yellow-200',
            'dot' => 'bg-yellow-500',
        ],
        'Health Planner' => [
            'bg' => 'bg-red-50',
            'text' => 'text-red-700',
            'border' => 'border-red-200',
            'dot' => 'bg-red-500',
        ],
        default => [
            'bg' => 'bg-gray-50',
            'text' => 'text-gray-600',
            'border' => 'border-gray-200',
            'dot' => 'bg-gray-400',
        ],
    };

    // STYLE garis (lebih gelap + lebih tebal)
    $lineColor = 'bg-gray-300'; // coba 'bg-gray-400' kalau mau lebih tegas
    $vLine = 'w-[2px]';
    $hLine = 'h-[2px]';
@endphp

<div class="flex flex-col items-center">
    {{-- CARD (match style dashboard) --}}
    <a href="{{ !empty($node['id']) ? route('users.show', $node['id']) : '#' }}"
        class="w-52 rounded-2xl bg-white shadow-sm border px-4 py-4 hover:shadow transition">
        <div class="flex flex-col items-center text-center">
            {{-- Avatar (mirip profile card kiri) --}}
            <div class="h-12 w-12 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $node['name'] }}" class="h-full w-full object-cover">
                @else
                    <span
                        class="text-lg font-bold text-white bg-blue-600 h-full w-full flex items-center justify-center">
                        {{ $initial }}
                    </span>
                @endif
            </div>

            <p class="mt-2 text-sm font-semibold text-gray-900 truncate w-full">
                {{ $node['name'] ?? '-' }}
            </p>

            @if ($role)
                <div
                    class="mt-1 inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-semibold rounded-full border
                        {{ $roleConfig['bg'] }} {{ $roleConfig['text'] }} {{ $roleConfig['border'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $roleConfig['dot'] }}"></span>
                    {{ $role }}
                </div>
            @endif

            <p class="mt-1 text-[11px] text-gray-500">
                {{ $node['phone_number'] ?? '-' }}
            </p>
        </div>
    </a>

    {{-- CONNECTOR + CHILDREN --}}
    @if ($hasChildren)
        {{-- line down from parent --}}
        <div class="h-6 {{ $vLine }} {{ $lineColor }}"></div>

        {{-- children wrapper --}}
        <div class="flex gap-6 justify-center items-start">
            @foreach ($children as $child)
                <div class="relative flex flex-col items-center">
                    {{-- connector area --}}
                    <div class="relative h-6 w-full">
                        {{-- horizontal pieces (bridge the gap so it won't be broken) --}}
                        @if (count($children) > 1)
                            @if (!$loop->first)
                                {{-- extend into left half-gap --}}
                                <div class="absolute top-0 -left-3 right-1/2 {{ $hLine }} {{ $lineColor }}">
                                </div>
                            @endif
                            @if (!$loop->last)
                                {{-- extend into right half-gap --}}
                                <div class="absolute top-0 left-1/2 -right-3 {{ $hLine }} {{ $lineColor }}">
                                </div>
                            @endif
                        @endif

                        {{-- vertical down to child --}}
                        <div
                            class="absolute top-0 left-1/2 -translate-x-1/2 h-6 {{ $vLine }} {{ $lineColor }}">
                        </div>
                    </div>

                    @include('users.partials.downline-node', ['node' => $child])
                </div>
            @endforeach
        </div>
    @endif
</div>
