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
              class="space-y-5"
              x-data="productPriceForm()">
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

            {{-- ✅ Harga Variasi --}}
            <div class="pt-2">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Harga Variasi</label>
                        <p class="text-xs text-gray-500 mt-1">
                            Tambahkan beberapa harga (mis. Product Price, Package 36/60/72/84, dll).
                        </p>
                    </div>

                    <button type="button"
                            @click="addRow()"
                            class="rounded-xl border px-3 py-2 text-sm font-semibold hover:bg-gray-50">
                        + Tambah Harga
                    </button>
                </div>

                {{-- error array (kalau perlu) --}}
                @error('prices')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-3 space-y-3">
                    <template x-for="(row, idx) in prices" :key="row.key">
                        <div class="rounded-2xl border p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900">
                                    Harga #<span x-text="idx + 1"></span>
                                </p>

                                <button type="button"
                                        class="text-sm font-semibold text-red-600 hover:text-red-700"
                                        @click="removeRow(idx)"
                                        x-show="prices.length > 1">
                                    Hapus
                                </button>
                            </div>

                            <div class="mt-3 grid grid-cols-1 md:grid-cols-12 gap-3">
                                {{-- Label --}}
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-medium text-gray-600">Label</label>
                                    <input type="text"
                                           class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                           :name="`prices[${idx}][label]`"
                                           x-model="row.label"
                                           placeholder="Contoh: Product Price / Package 36" />
                                    {{-- error laravel per index (best effort) --}}
                                    @error('prices.*.label')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Billing Type --}}
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600">Tipe</label>
                                    <select class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                            :name="`prices[${idx}][billing_type]`"
                                            x-model="row.billing_type"
                                            @change="onTypeChange(row)">
                                        <option value="one_time">One Time</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                    @error('prices.*.billing_type')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Duration (months) --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600">Durasi (bulan)</label>
                                    <input type="number"
                                           min="1"
                                           max="120"
                                           class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                           :name="`prices[${idx}][duration_months]`"
                                           x-model="row.duration_months"
                                           :disabled="row.billing_type !== 'monthly'"
                                           :placeholder="row.billing_type === 'monthly' ? '36' : '-'" />
                                    @error('prices.*.duration_months')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Amount --}}
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-600">Nominal</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                           :name="`prices[${idx}][amount]`"
                                           x-model="row.amount"
                                           placeholder="Contoh: 12500000" />
                                    @error('prices.*.amount')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Active --}}
                                <div class="md:col-span-12">
                                    <label class="inline-flex items-center gap-2 mt-1">
                                        <input type="checkbox"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               :name="`prices[${idx}][is_active]`"
                                               value="1"
                                               x-model="row.is_active">
                                        <span class="text-sm text-gray-700">Active</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
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

            {{-- Status Product --}}
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

    <script>
        function productPriceForm() {
            // old('prices') untuk kasus validation error balik lagi
            const oldPrices = @json(old('prices'));

            return {
                prices: (Array.isArray(oldPrices) && oldPrices.length)
                    ? oldPrices.map((p, i) => ({
                        key: Date.now() + '-' + i,
                        label: p.label ?? '',
                        billing_type: p.billing_type ?? 'one_time',
                        duration_months: p.duration_months ?? '',
                        amount: p.amount ?? '',
                        is_active: (p.is_active ?? 1) ? true : false,
                    }))
                    : [
                        {
                            key: Date.now() + '-0',
                            label: 'Product Price',
                            billing_type: 'one_time',
                            duration_months: '',
                            amount: '',
                            is_active: true,
                        }
                    ],

                addRow() {
                    this.prices.push({
                        key: Date.now() + '-' + Math.random().toString(16).slice(2),
                        label: '',
                        billing_type: 'one_time',
                        duration_months: '',
                        amount: '',
                        is_active: true,
                    });
                },

                removeRow(idx) {
                    this.prices.splice(idx, 1);
                },

                onTypeChange(row) {
                    // kalau ganti ke one_time, kosongkan durasi
                    if (row.billing_type !== 'monthly') {
                        row.duration_months = '';
                    }
                }
            }
        }
    </script>
</x-dashboard-layout>
