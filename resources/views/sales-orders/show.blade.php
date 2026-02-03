<x-dashboard-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">
                    Sales Order Detail
                </h1>
                <p class="text-sm text-gray-500">
                    {{ $salesOrder->order_no }}
                </p>
            </div>

            <a href="{{ route('sales-orders.index') }}"
               class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                ← Back
            </a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- Left --}}
            <div class="lg:col-span-8 space-y-6">
                {{-- Order Info --}}
                <div class="rounded-2xl border bg-white p-5">
                    <h2 class="text-sm font-semibold text-gray-900">Order Info</h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <div class="text-xs text-gray-500">Order No</div>
                            <div class="mt-1 font-semibold text-gray-900">{{ $salesOrder->order_no }}</div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Key In</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->key_in_at ? $salesOrder->key_in_at->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Install Date</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->install_date ? \Carbon\Carbon::parse($salesOrder->install_date)->format('d M Y') : '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Recurring</div>
                            <div class="mt-1">
                                @if($salesOrder->is_recurring)
                                    <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Yes</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">No</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Payment Method</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->payment_method ? strtoupper($salesOrder->payment_method) : '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Sales</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->salesUser?->name ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">CCP Status</div>
                            <div class="mt-1">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                    {{ $salesOrder->ccp_status ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @php
                            $status = $salesOrder->status;

                            $statusClasses = match ($status) {
                                'dibatalkan', 'gagal penelponan' => 'bg-red-100 text-red-700',
                                'ditunda' => 'bg-yellow-100 text-yellow-700',
                                'selesai' => 'bg-green-100 text-green-700',
                                'menunggu verifikasi', 'dijadwalkan' => 'bg-gray-100 text-gray-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp

                        <div>
                            <div class="text-xs text-gray-500">Order Status</div>
                            <div class="mt-1">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $status ?? '-' }}
                                </span>
                            </div>
                        </div>

                        @if(!empty($salesOrder->status_reason))
                            <div class="md:col-span-2">
                                <div class="text-xs text-gray-500">Alasan Status</div>
                                <div class="mt-1 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900">
                                    {{ $salesOrder->status_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Items --}}
                <div class="rounded-2xl border bg-white p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-900">Items</h2>
                        <div class="text-sm text-gray-500">
                            Total item: <span class="font-semibold text-gray-700">{{ $salesOrder->items->count() }}</span>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left w-20">Image</th>
                                    <th class="px-4 py-3 text-left">SKU</th>
                                    <th class="px-4 py-3 text-left">Product</th>
                                    <th class="px-4 py-3 text-left w-56">Selected Price</th>
                                    <th class="px-4 py-3 text-right w-24">Qty</th>
                                    <th class="px-4 py-3 text-right w-44">Total</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y">
                                @php
                                    $oneTimeTotal = 0;
                                    $monthlyTotal = 0;
                                @endphp

                                @forelse($salesOrder->items as $item)
                                    @php
                                        $pp = $item->productPrice;

                                        $billingType = $pp->billing_type ?? null; // monthly | one_time
                                        $isMonthly = ($billingType === 'monthly');

                                        $priceAmount = (int) ($pp?->amount ?? ($item->product?->price ?? 0)); // fallback data lama
                                        $qty = (int) $item->qty;
                                        $total = $priceAmount * $qty;

                                        if ($isMonthly) {
                                            $monthlyTotal += $total;
                                        } else {
                                            $oneTimeTotal += $total;
                                        }

                                        $img = $item->product?->product_image ?? null;

                                        $priceLabel = '-';
                                        if ($pp) {
                                            $dur = $pp->duration_months ?? null;

                                            if ($isMonthly) {
                                                $suffix = $dur ? " • Monthly ({$dur} bln)" : " • Monthly";
                                            } else {
                                                $suffix = " • One Time";
                                            }

                                            $priceLabel = ($pp->label ?? 'Price') . $suffix;
                                        } elseif ($priceAmount > 0) {
                                            $priceLabel = 'Default Price';
                                        }
                                    @endphp

                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="h-10 w-10 overflow-hidden rounded-lg border bg-white">
                                                @if($img)
                                                    <img
                                                        src="{{ \Illuminate\Support\Str::startsWith($img, ['http://','https://']) ? $img : asset('storage/'.$img) }}"
                                                        alt="Product image"
                                                        class="h-full w-full object-cover"
                                                    />
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">
                                                        N/A
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $item->product?->sku ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">
                                                {{ $item->product?->product_name ?? '-' }}
                                            </div>
                                            @if($item->product?->model)
                                                <div class="text-xs text-gray-500">
                                                    {{ $item->product->model }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $priceLabel }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $priceAmount ? 'Rp '.number_format($priceAmount, 0, ',', '.') : '-' }}
                                                @if($isMonthly)
                                                    <span class="text-gray-400">/bln</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            {{ $qty }}
                                        </td>

                                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                            {{ $total ? 'Rp '.number_format($total, 0, ',', '.') : '-' }}
                                            @if($isMonthly)
                                                <span class="text-gray-400">/bln</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                            Belum ada items.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($salesOrder->items->count())
                        @php
                            $grandTotalAll = $oneTimeTotal + $monthlyTotal;
                        @endphp

                        <div class="mt-4 flex flex-col gap-2 text-sm text-gray-600">
                            <div>
                                Total Qty:
                                <span class="font-semibold text-gray-900">
                                    {{ $salesOrder->items->sum('qty') }}
                                </span>
                            </div>

                            <div>
                                One Time Total:
                                <span class="font-semibold text-gray-900">
                                    {{ 'Rp '.number_format($oneTimeTotal, 0, ',', '.') }}
                                </span>
                            </div>

                            <div>
                                Monthly Total:
                                <span class="font-semibold text-gray-900">
                                    {{ 'Rp '.number_format($monthlyTotal, 0, ',', '.') }}
                                </span>
                                <span class="text-gray-400">/bln</span>
                            </div>

                            <div class="pt-1">
                                Grand Total (All):
                                <span class="font-semibold text-gray-900">
                                    {{ 'Rp '.number_format($grandTotalAll, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right --}}
            <div class="lg:col-span-4 space-y-6">
                {{-- Customer --}}
                <div class="rounded-2xl border bg-white p-5">
                    <h2 class="text-sm font-semibold text-gray-900">Customer</h2>

                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="text-xs text-gray-500">Name</div>
                            <div class="mt-1 font-semibold text-gray-900">
                                {{ $salesOrder->customer?->full_name ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Phone</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->customer?->phone_number ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Address</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->customer?->address ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="rounded-2xl border bg-white p-5">
                    <h2 class="text-sm font-semibold text-gray-900">Meta</h2>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs text-gray-500">Created</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->created_at?->format('d M Y') ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Updated</div>
                            <div class="mt-1 text-gray-900">
                                {{ $salesOrder->updated_at?->format('d M Y') ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
