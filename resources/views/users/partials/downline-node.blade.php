@php
    $hasChildren = !empty($node['children'] ?? []);
    $initial = strtoupper(mb_substr($node['name'] ?? 'U', 0, 1));
    $photoUrl = !empty($node['photo']) ? asset('storage/' . $node['photo']) : null;
@endphp

<div class="flex flex-col items-center">
    {{-- CARD (match style dashboard) --}}
    <a href="{{ $node['id'] ? route('users.show', $node['id']) : '#' }}"
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

            <p class="mt-1 text-[11px] text-gray-500">
                {{ $node['phone_number'] ?? '-' }}
            </p>
        </div>
    </a>

    {{-- CONNECTOR + CHILDREN --}}
    @if ($hasChildren)
        {{-- line down from parent --}}
        <div class="h-6 w-px bg-gray-200"></div>

        {{-- children wrapper --}}
        <div class="relative flex gap-6 justify-center items-start">
            {{-- horizontal line --}}
            <div class="absolute top-0 left-0 right-0 mx-auto h-px bg-gray-200" style="width: calc(100% - 1.5rem);">
            </div>

            @foreach ($node['children'] as $child)
                <div class="flex flex-col items-center">
                    {{-- line down to child --}}
                    <div class="h-6 w-px bg-gray-200"></div>
                    @include('users.partials.downline-node', ['node' => $child])
                </div>
            @endforeach
        </div>
    @endif
</div>
