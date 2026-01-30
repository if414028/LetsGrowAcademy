<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Detail Product</h1>
            <p class="text-sm text-gray-500">Informasi lengkap product.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.edit', $product->id) }}"
               class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Edit
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Image Card --}}
        <div class="rounded-2xl border bg-white p-6">
            <div class="text-sm font-semibold text-gray-900 mb-3">Product Image</div>

            @if($product->product_image)
                <img
                    src="{{ asset('storage/' . $product->product_image) }}"
                    alt="Product image"
                    class="w-full max-h-[520px] rounded-2xl border bg-white object-contain"
                />
            @else
                <div class="w-full aspect-square rounded-2xl border bg-gray-50 flex items-center justify-center text-sm text-gray-400">
                    No Image
                </div>
            @endif
        </div>

        {{-- Details Card --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Detail --}}
            <div class="rounded-2xl border bg-white p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-900">Detail</div>
                    @if($product->is_active)
                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                            Active
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                            Inactive
                        </span>
                    @endif
                </div>

                <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">SKU</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $product->sku }}</dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Model</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $product->model }}</dd>
                    </div>

                    <div class="sm:col-span-2 rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Product Name</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $product->product_name }}</dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">
                            {{ optional($product->created_at)->format('d M Y H:i') }}
                        </dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Updated</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">
                            {{ optional($product->updated_at)->format('d M Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- ✅ Product Prices --}}
            <div class="rounded-2xl border bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Product Prices</div>
                        <p class="text-xs text-gray-500 mt-1">Daftar variasi harga untuk product ini.</p>
                    </div>
                </div>

                @php
                    $prices = $product->prices
                        ? $product->prices->sortBy('sort_order')->values()
                        : collect();

                    $hasPrices = $prices->count() > 0;
                @endphp

                @if($hasPrices)
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="py-2 pr-4">Label</th>
                                    <th class="py-2 pr-4">Tipe</th>
                                    <th class="py-2 pr-4">Durasi</th>
                                    <th class="py-2 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($prices as $p)
                                    <tr class="text-gray-900">
                                        <td class="py-3 pr-4">
                                            <div class="font-semibold">{{ $p->label }}</div>
                                            @if(!$p->is_active)
                                                <div class="text-xs text-gray-500">Inactive</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold">
                                                {{ $p->billing_type === 'monthly' ? 'Monthly' : 'One Time' }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-700">
                                            @if($p->billing_type === 'monthly')
                                                {{ $p->duration_months }} bulan
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 text-right font-semibold">
                                            Rp {{ number_format((float) $p->amount, 0, ',', '.') }}
                                            @if($p->billing_type === 'monthly')
                                                <span class="text-xs text-gray-500 font-medium">/bln</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- fallback untuk data lama --}}
                    <div class="mt-4 rounded-xl border bg-gray-50 p-4">
                        <div class="text-sm font-semibold text-gray-900">Belum ada variasi harga</div>

                        @if(!is_null($product->price))
                            <div class="mt-3 text-sm font-semibold text-gray-900">
                                Harga Lama: Rp {{ number_format((float) $product->price, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dashboard-layout>
