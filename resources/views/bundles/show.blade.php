<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-gray-900">Detail Bundle</h1>
            </div>
            <p class="text-sm text-gray-500">Informasi lengkap bundling product.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('bundles.edit', $bundle->id) }}"
               class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Edit Bundle
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Image Card --}}
        <div class="rounded-2xl border bg-white p-6">
            <div class="text-sm font-semibold text-gray-900 mb-3">Bundle Image</div>

            @if($bundle->product_image)
                <img
                    src="{{ asset('storage/' . $bundle->product_image) }}"
                    alt="Bundle image"
                    class="w-full max-h-[520px] rounded-2xl border bg-white object-contain"
                />
            @else
                <div class="w-full aspect-square rounded-2xl border bg-gray-50 flex items-center justify-center text-sm text-gray-400">
                    No Image
                </div>
            @endif
        </div>

        {{-- Details + Prices --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Detail --}}
            <div class="rounded-2xl border bg-white p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-900">Detail</div>
                    @if($bundle->is_active)
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
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $bundle->sku }}</dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Model</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $bundle->model }}</dd>
                    </div>

                    <div class="sm:col-span-2 rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Bundle Name</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $bundle->product_name }}</dd>
                    </div>

                    <div class="sm:col-span-2 rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-700">
                            {{ $bundle->description ?: '—' }}
                        </dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">
                            {{ optional($bundle->created_at)->format('d M Y H:i') }}
                        </dd>
                    </div>

                    <div class="rounded-xl border bg-gray-50 p-4">
                        <dt class="text-xs text-gray-500">Updated</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">
                            {{ optional($bundle->updated_at)->format('d M Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Bundle Items --}}
            <div class="rounded-2xl border bg-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Isi Bundling</div>
                        <p class="text-xs text-gray-500 mt-1">Product yang termasuk dalam bundle ini.</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        Total: {{ $bundle->bundleItems->count() }} item
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="py-2 pr-4">SKU</th>
                                <th class="py-2 pr-4">Product</th>
                                <th class="py-2 pr-4">Model</th>
                                <th class="py-2 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($bundle->bundleItems as $item)
                                <tr class="text-gray-900">
                                    <td class="py-3 pr-4 font-semibold">{{ $item->sku }}</td>
                                    <td class="py-3 pr-4">{{ $item->product_name }}</td>
                                    <td class="py-3 pr-4">{{ $item->model }}</td>
                                    <td class="py-3 text-right font-semibold">{{ (int) $item->pivot->qty }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500">
                                        Belum ada item bundling.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bundle Prices --}}
            <div class="rounded-2xl border bg-white p-6">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Bundle Prices</div>
                    <p class="text-xs text-gray-500 mt-1">Daftar variasi harga bundling.</p>
                </div>

                @php
                    $prices = $bundle->prices
                        ? $bundle->prices->sortBy('sort_order')->values()
                        : collect();
                @endphp

                @if($prices->count())
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
                                        <td class="py-3 pr-4">
                                            {{ $p->billing_type === 'monthly' ? $p->duration_months.' bulan' : '-' }}
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
                    <div class="mt-4 rounded-xl border bg-gray-50 p-4 text-sm text-gray-500">
                        Belum ada harga untuk bundle ini.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dashboard-layout>
