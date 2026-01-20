<x-dashboard-layout>
    <div class="p-4 md:p-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">Sales Orders</h1>
                <p class="text-sm text-gray-500">Kelola daftar sales order.</p>
            </div>

            <a href="{{ route('sales-orders.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                <span>+ Create Sales Order</span>
            </a>
        </div>

        {{-- Table Card --}}
        <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
            {{-- Topbar (Filter style like Products) --}}
            <div class="flex flex-col gap-3 border-b px-4 py-4 md:flex-row md:items-center md:justify-between">
                <form method="GET" class="flex w-full max-w-xl items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari Order No / Customer / Sales..."
                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                    />

                    <button
                        type="submit"
                        class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cari
                    </button>

                    @if(request()->filled('search'))
                        <a href="{{ route('sales-orders.index') }}"
                           class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    @endif
                </form>

                <div class="text-sm text-gray-500">
                    Total: <span class="font-semibold text-gray-700">{{ $salesOrders->total() }}</span>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Order No</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-left">Sales</th>
                            <th class="px-4 py-3 text-left">Key In</th>
                            <th class="px-4 py-3 text-left">Install Date</th>
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
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $so->customer?->full_name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $so->customer?->phone_number ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $so->salesUser?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    {{ $so->key_in_at ? $so->key_in_at->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $so->install_date ? \Carbon\Carbon::parse($so->install_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($so->is_recurring)
                                        <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Yes</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                        {{ $so->ccp_status ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                        {{ $so->status ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('sales-orders.show', $so) }}" class="text-blue-600 hover:underline">Detail</a>
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
    </div>
</x-dashboard-layout>
