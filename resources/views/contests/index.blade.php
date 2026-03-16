@php
    use Illuminate\Support\Str;

    $status = request('status');
    $q = request('q');
@endphp

<x-dashboard-layout>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Kontes</h1>
            <p class="text-sm text-gray-500">Daftar kontes yang kamu ikuti / kelola.</p>
        </div>

        @can('create', \App\Models\Contest::class)
            <a href="{{ route('contests.create') }}"
                class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + Buat Kontes
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 rounded-2xl border bg-white overflow-hidden">
        {{-- Filters + Search --}}
        <div class="border-b">
            <div class="p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <form method="GET" class="w-full lg:max-w-2xl">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="q" value="{{ $q }}"
                            placeholder="Cari judul / deskripsi kontes..."
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                        <select name="status"
                            class="w-full sm:w-56 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="ended" {{ $status === 'ended' ? 'selected' : '' }}>Selesai</option>
                        </select>

                        <button class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50">
                            Cari
                        </button>

                        @if (request()->filled('q') || request()->filled('status'))
                            <a href="{{ route('contests.index') }}"
                                class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50 text-center">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                <div class="text-sm text-gray-500">
                    Total: {{ $contests->total() }}
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Banner</th>
                        <th class="px-4 py-3 text-left font-semibold">Judul</th>
                        <th class="px-4 py-3 text-left font-semibold">Periode</th>
                        <th class="px-4 py-3 text-left font-semibold">Target</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Dibuat oleh</th>
                        <th class="px-4 py-3 text-left font-semibold">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse ($contests as $contest)
                        @php
                            $creatorName = optional($contest->creator)->name ?? '-';
                            $creatorRole = optional($contest->creatorRole)->name ?? null;

                            $start = $contest->start_date ? $contest->start_date->format('d M Y') : null;
                            $end = $contest->end_date ? $contest->end_date->format('d M Y') : null;

                            $st = $contest->status ?? 'draft';
                            $badge = match ($st) {
                                'active' => ['bg-green-100 text-green-700', 'Aktif'],
                                'ended' => ['bg-gray-100 text-gray-700', 'Selesai'],
                                default => ['bg-yellow-100 text-yellow-700', 'Draft'],
                            };

                            $banner = $contest->banner_url;
                            $bannerSrc = null;
                            if ($banner) {
                                $bannerSrc = Str::startsWith($banner, ['http://', 'https://'])
                                    ? $banner
                                    : asset('storage/' . ltrim($banner, '/'));
                            }
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if ($bannerSrc)
                                    <img src="{{ $bannerSrc }}" class="h-10 w-10 rounded-lg object-cover border"
                                        alt="Banner">
                                @else
                                    <div
                                        class="h-10 w-10 rounded-lg border bg-gray-50 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 6h16v12H4z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 10h.01M4 16l4-4a2 2 0 012.828 0L14 15l2-2a2 2 0 012.828 0L20 15" />
                                        </svg>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">
                                    {{ $contest->title }}
                                </div>
                                @if ($contest->description)
                                    <div class="text-xs text-gray-500 line-clamp-1">
                                        {{ $contest->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                @if ($start && $end)
                                    <div class="font-medium text-gray-900">{{ $start }} - {{ $end }}
                                    </div>
                                @elseif($start && !$end)
                                    <div class="font-medium text-gray-900">Mulai {{ $start }}</div>
                                @elseif(!$start && $end)
                                    <div class="font-medium text-gray-900">Sampai {{ $end }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if (!is_null($contest->target_unit))
                                    <div class="font-semibold text-gray-900">
                                        {{ $contest->target_unit }}
                                        <span class="text-xs font-medium text-gray-500">unit</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif

                                @if ($contest->reward)
                                    <div class="text-xs text-gray-500 line-clamp-1">
                                        Reward: {{ $contest->reward }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[0] }}">
                                    {{ $badge[1] }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-gray-900 font-medium">
                                    {{ $creatorName }}
                                </div>
                                @if ($creatorRole)
                                    <div class="text-xs text-gray-500">
                                        {{ $creatorRole }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    {{-- Show --}}
                                    <a href="{{ route('contests.show', $contest) }}"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                              text-gray-600 hover:bg-blue-50 hover:text-blue-600"
                                        title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                     c4.478 0 8.268 2.943 9.542 7
                                                     -1.274 4.057-5.064 7-9.542 7
                                                     -4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    @can('update', $contest)
                                        <a href="{{ route('contests.edit', $contest) }}"
                                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                                  text-gray-600 hover:bg-yellow-50 hover:text-yellow-600"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @php
                                        $canManagePublish =
                                            auth()
                                                ->user()
                                                ->hasAnyRole(['Admin', 'Head Admin']) ||
                                            (int) $contest->created_by_user_id === (int) auth()->id();
                                    @endphp

                                    {{-- Publish / Unpublish --}}
                                    @if ($canManagePublish)
                                        @if ($contest->status === 'draft')
                                            <form id="publish-form-{{ $contest->id }}" method="POST"
                                                action="{{ route('contests.publish', $contest) }}">
                                                @csrf

                                                <button type="button"
                                                    onclick="showConfirm(
            'Publish kontes ini?',
            'publish',
            () => document.getElementById('publish-form-{{ $contest->id }}').submit()
        )"
                                                    class="px-3 py-1 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700">
                                                    Publish
                                                </button>
                                            </form>
                                        @elseif ($contest->status === 'active')
                                            <form id="unpublish-form-{{ $contest->id }}" method="POST"
                                                action="{{ route('contests.unpublish', $contest) }}">
                                                @csrf

                                                <button type="button"
                                                    onclick="showConfirm(
            'Unpublish kontes ini? Status akan kembali ke draft.',
            'unpublish',
            () => document.getElementById('unpublish-form-{{ $contest->id }}').submit()
        )"
                                                    class="px-3 py-1 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700">
                                                    Unpublish
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Belum ada kontes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t">
            {{ $contests->appends(request()->query())->links() }}
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">

        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6">

            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                Konfirmasi
            </h3>

            <p id="confirmMessage" class="text-sm text-gray-600 mb-6">
                Apakah kamu yakin?
            </p>

            <div class="flex justify-end gap-3">

                <button id="confirmCancel" class="px-4 py-2 rounded-lg text-sm">
                    Cancel
                </button>

                <button id="confirmOk" class="px-4 py-2 rounded-lg text-sm text-white">
                    OK
                </button>

            </div>

        </div>
    </div>

    <script>
        const modal = document.getElementById('confirmModal');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');

        let confirmCallback = null;

        function resetButtonStyles() {
            confirmOk.className = 'px-4 py-2 rounded-lg text-sm text-white transition';
            confirmCancel.className = 'px-4 py-2 rounded-lg text-sm transition';

            // pastikan semua variasi warna lama kebuang
            confirmOk.classList.remove(
                'bg-blue-600', 'hover:bg-blue-700',
                'bg-red-600', 'hover:bg-red-700'
            );

            confirmCancel.classList.remove(
                'bg-gray-200', 'hover:bg-gray-300', 'text-gray-700',
                'bg-blue-600', 'hover:bg-blue-700', 'text-white'
            );
        }

        function showConfirm(message, type, callback) {
            confirmMessage.innerText = message;
            confirmCallback = callback;

            resetButtonStyles();

            if (type === 'unpublish') {
                // OK merah, Cancel biru
                confirmOk.classList.add('bg-red-600', 'hover:bg-red-700');
                confirmCancel.classList.add('bg-blue-600', 'hover:bg-blue-700', 'text-white');
            } else {
                // publish: OK biru, Cancel abu
                confirmOk.classList.add('bg-blue-600', 'hover:bg-blue-700');
                confirmCancel.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        confirmCancel.onclick = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            confirmCallback = null;
        };

        confirmOk.onclick = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            if (confirmCallback) {
                const cb = confirmCallback;
                confirmCallback = null;
                cb();
            }
        };
    </script>
</x-dashboard-layout>
