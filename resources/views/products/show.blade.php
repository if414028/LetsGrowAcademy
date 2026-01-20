<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Detail Product</h1>
            <p class="text-sm text-gray-500">Informasi lengkap product.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.index') }}"
               class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50">
                Kembali
            </a>
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
        <div class="lg:col-span-2 rounded-2xl border bg-white p-6">
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
                    <dt class="text-xs text-gray-500">Harga</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </dd>
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
    </div>
</x-dashboard-layout>