<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Sales Orders</h1>
            <p class="text-sm text-gray-500">Kelola daftar sales order.</p>
        </div>

        <a href="{{ route('sales-orders.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            <span>+ Buat Sales Order</span>
        </a>
    </div>

    {{-- Table Card --}}
    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        {{-- Topbar (Filter style like Products) --}}
        <div class="flex flex-col gap-3 border-b px-4 py-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" class="flex w-full max-w-xl items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari Order No / Customer / Sales..."
                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                <button type="submit"
                    class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cari
                </button>

                @if (request()->filled('search'))
                    <a href="{{ route('sales-orders.index', request()->except('page')) }}"
                        class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                @endif
            </form>

            <div class="text-sm text-gray-500">
                Total: <span class="font-semibold text-gray-700">{{ $salesOrders->total() }}</span>
            </div>
        </div>

        {{-- Tabs by Status --}}
        @php
            // bawa semua query kecuali page
            $baseQuery = request()->except('page');
            $active = $activeStatus ?? (request('status') ?: 'all');
        @endphp

        <div class="border-b px-4">
            <nav class="-mb-px flex gap-2 overflow-x-auto py-3">
                {{-- Semua --}}
                <a href="{{ route('sales-orders.index', array_filter(array_merge($baseQuery, ['status' => null]), fn($v) => $v !== null && $v !== '')) }}"
                    class="whitespace-nowrap rounded-xl px-3 py-2 text-sm font-semibold
                            {{ $active === 'all' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    Semua
                </a>

                {{-- Per Status --}}
                @foreach ($statuses ?? [] as $st)
                    <a href="{{ route('sales-orders.index', array_filter(array_merge($baseQuery, ['status' => $st]), fn($v) => $v !== null && $v !== '')) }}"
                        class="whitespace-nowrap rounded-xl px-3 py-2 text-sm font-semibold
                                {{ $active === $st ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        {{ ucfirst($st) }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Order No</th>
                        <th class="px-4 py-3 text-left">Sales</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Key In</th>
                        <th class="px-4 py-3 text-left">Recurring</th>
                        <th class="px-4 py-3 text-left">CCP</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($salesOrders as $so)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $so->order_no }}
                            </td>

                            <td class="px-4 py-3">{{ $so->salesUser?->name ?? '-' }}</td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $so->customer?->full_name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $so->customer?->phone_number ?? '' }}</div>
                            </td>

                            <td class="px-4 py-3">
                                {{ $so->key_in_at ? $so->key_in_at->format('d M Y') : '-' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($so->is_recurring)
                                    <span
                                        class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Yes</span>
                                @else
                                    <span
                                        class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">No</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                    {{ $so->ccp_status ?? '-' }}
                                </span>
                            </td>

                            @php
                                $status = $so->status;

                                $statusClasses = match ($status) {
                                    'dibatalkan', 'gagal penelponan' => 'bg-red-100 text-red-700',
                                    'ditunda' => 'bg-yellow-100 text-yellow-700',
                                    'selesai' => 'bg-green-100 text-green-700',
                                    'menunggu verifikasi', 'dijadwalkan' => 'bg-gray-100 text-gray-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $status ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    {{-- Detail --}}
                                    <a href="{{ route('sales-orders.show', $so) }}"
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
                                    <a href="{{ route('sales-orders.edit', $so) }}"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                            text-gray-600 hover:bg-yellow-50 hover:text-yellow-600"
                                        title="Edit">
                                        {{-- icon pencil --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                                Belum ada sales order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t px-4 py-3">
            {{ $salesOrders->links() }}
        </div>
    </div>

</x-dashboard-layout>
