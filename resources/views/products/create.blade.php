<x-dashboard-layout>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Product</h1>
            <p class="text-sm text-gray-500">Masukkan data product.</p>
        </div>

        <a href="{{ route('products.index') }}"
           class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50">
            Kembali
        </a>
    </div>

    <div class="mt-6 rounded-2xl border bg-white p-6 max-w-3xl">
        <form method="POST"
              action="{{ route('products.store') }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf

            {{-- SKU --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">SKU</label>
                <input type="text" name="sku" value="{{ old('sku') }}"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: P001" />
                @error('sku')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Product Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Product Name</label>
                <input type="text" name="product_name" value="{{ old('product_name') }}"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: Coway Neo Plus" />
                @error('product_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Model --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Model</label>
                <input type="text" name="model" value="{{ old('model') }}"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Contoh: CHP-264L" />
                @error('model')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Harga</label>
                <input type="number"
                    name="price"
                    value="{{ old('price') }}"
                    step="0.01"
                    min="0"
                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Contoh: 12500000" />
                @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Product Image --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Product Image</label>
                <input type="file" name="product_image" accept="image/*"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-gray-500">
                    JPG, PNG, WEBP • Max 2MB
                </p>
                @error('product_image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="flex items-center gap-2">
                <input id="is_active" type="checkbox" name="is_active" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm font-medium text-gray-700">
                    Active
                </label>
            </div>

            {{-- Action --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Simpan
                </button>
                <a href="{{ route('products.index') }}"
                   class="rounded-xl border px-5 py-2.5 text-sm font-semibold hover:bg-gray-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-dashboard-layout>
