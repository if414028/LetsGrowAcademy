<x-dashboard-layout>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Products</h1>
            <p class="text-sm text-gray-500">Kelola daftar product.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.create') }}"
               class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + Tambah Product
            </a>

            <a href="{{ route('bundles.create') }}"
               class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + Tambah Bundle
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 rounded-2xl border bg-white overflow-hidden">
        {{-- Tabs + Search --}}
        <div class="border-b">
            {{-- Tabs --}}
            <div class="px-4 pt-4">
                @php
                    $activeType = $type ?? request('type', 'regular');
                    if (!in_array($activeType, ['regular', 'bundle'], true)) $activeType = 'regular';
                @endphp

                <div class="inline-flex rounded-xl bg-gray-100 p-1">
                    <a href="{{ route('products.index', ['type' => 'regular', 'q' => $q ?? null]) }}"
                       class="px-4 py-2 text-sm font-semibold rounded-lg
                              {{ $activeType === 'regular' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        Regular
                        <span class="ml-1 text-xs font-semibold text-gray-500">({{ $countRegular ?? 0 }})</span>
                    </a>

                    <a href="{{ route('products.index', ['type' => 'bundle', 'q' => $q ?? null]) }}"
                       class="px-4 py-2 text-sm font-semibold rounded-lg
                              {{ $activeType === 'bundle' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        Bundling
                        <span class="ml-1 text-xs font-semibold text-gray-500">({{ $countBundle ?? 0 }})</span>
                    </a>
                </div>
            </div>

            {{-- Search + Total --}}
            <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <form method="GET" class="w-full sm:max-w-md">
                    {{-- keep tab --}}
                    <input type="hidden" name="type" value="{{ $activeType }}">

                    <div class="flex gap-2">
                        <input type="text" name="q" value="{{ $q ?? '' }}"
                               placeholder="Cari SKU / nama product..."
                               class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                        <button class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50">
                            Cari
                        </button>
                    </div>
                </form>

                <div class="text-sm text-gray-500">
                    Total: {{ $products->total() }}
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Image</th>
                        <th class="px-4 py-3 text-left font-semibold">SKU</th>
                        <th class="px-4 py-3 text-left font-semibold">Product Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Model</th>
                        <th class="px-4 py-3 text-left font-semibold">Harga</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold">Created</th>
                        <th class="px-4 py-3 text-left font-semibold">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse ($products as $product)
                        @php
                            $showRoute = $product->type === 'bundle'
                                ? route('bundles.show', $product->id)
                                : route('products.show', $product->id);

                            $editRoute = $product->type === 'bundle'
                                ? route('bundles.edit', $product->id)
                                : route('products.edit', $product->id);

                            $amount = optional($product->primaryPrice)->amount;
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if($product->product_image)
                                    <img src="{{ asset('storage/' . $product->product_image) }}"
                                         class="h-10 w-10 rounded-lg object-cover border">
                                @else
                                    <span class="text-xs text-gray-400">No Image</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $product->sku }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $product->product_name }}
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $product->model }}
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900">
                                @php $p = $product->displayPrice; @endphp
                                @if($p)
                                    Rp {{ number_format($p->amount, 0, ',', '.') }}
                                    @if($p->billing_type === 'monthly')
                                        <span class="ml-1 text-xs text-gray-500">/bln</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if($product->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ optional($product->created_at)->format('d M Y') }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    {{-- Show --}}
                                    <a href="{{ $showRoute }}"
                                       class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                              text-gray-600 hover:bg-blue-50 hover:text-blue-600"
                                       title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                     c4.478 0 8.268 2.943 9.542 7
                                                     -1.274 4.057-5.064 7-9.542 7
                                                     -4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ $editRoute }}"
                                       class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                              text-gray-600 hover:bg-yellow-50 hover:text-yellow-600"
                                       title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                Belum ada product.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="p-4 border-t">
            {{ $products->links() }}
        </div>
    </div>
</x-dashboard-layout>