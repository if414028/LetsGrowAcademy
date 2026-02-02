<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Bundling</h1>
            <p class="text-sm text-gray-500">Buat bundling baru dan pilih isi produknya.</p>
        </div>

        <a href="{{ route('products.index', ['type' => 'bundle']) }}"
            class="inline-flex items-center rounded-xl border px-4 py-2 text-sm bg-white hover:bg-gray-50">
            Kembali
        </a>
    </div>

    {{-- Main Card --}}
    <div class="mt-6 rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
        <form method="POST" action="{{ route('bundles.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- Basic Info --}}
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">SKU</label>
                    <input name="sku" value="{{ old('sku') }}"
                        class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: B001">
                    @error('sku')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Product Name</label>
                    <input name="product_name" value="{{ old('product_name') }}"
                        class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Neo Plus + Storm Package">
                    @error('product_name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Model</label>
                    <input name="model" value="{{ old('model') }}"
                        class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: COMBO-NEO-STORM">
                    @error('model')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Deskripsi Bundling</label>
                    <textarea name="description" rows="3"
                        class="mt-1 w-full rounded-xl border-gray-200 px-4 py-3 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Tulis deskripsi bundling...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Bundle Items --}}
            <div>
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Isi Bundling</div>
                        <p class="text-xs text-gray-500 mt-1">Ketik SKU / nama product lalu pilih dari
                            suggestion (qty diperlukan).</p>
                    </div>

                    <button type="button" id="add-item"
                        class="inline-flex items-center rounded-xl border px-3 py-2 text-sm bg-white hover:bg-gray-50">
                        + Tambah Item
                    </button>
                </div>

                @error('items')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror

                @if ($errors->has('items.*.product_id'))
                    <p class="text-sm text-red-600 mt-2">Pastikan kamu memilih product dari suggestion.</p>
                @endif

                <div id="items-wrap" class="mt-4 space-y-3"></div>
            </div>

            {{-- Prices --}}
            <div>
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Harga Variasi</div>
                        <p class="text-xs text-gray-500 mt-1">Tambahkan beberapa harga (One Time / Monthly).</p>
                    </div>

                    <button type="button" id="add-price"
                        class="inline-flex items-center rounded-xl border px-3 py-2 text-sm bg-white hover:bg-gray-50">
                        + Tambah Harga
                    </button>
                </div>

                @error('prices')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror

                <div id="prices-wrap" class="mt-4 space-y-3"></div>
            </div>

            {{-- Image + Active --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Product Image</label>
                    <input type="file" name="product_image" class="mt-1 block w-full text-sm">
                    @error('product_image')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP • Max 2MB</p>
                </div>

                <div class="flex items-center gap-2 mt-6 md:mt-0">
                    <input id="is_active" name="is_active" type="checkbox" value="1"
                        class="rounded border-gray-300" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button
                    class="inline-flex items-center rounded-xl bg-blue-600 text-white px-5 py-2.5 text-sm font-medium hover:bg-blue-700">
                    Simpan Bundling
                </button>
            </div>
        </form>
    </div>

    @php
        $seedItems = old('items') ?? [['product_id' => '', 'qty' => 1]];

        $seedPrices = old('prices') ?? [
            [
                'label' => 'Bundling Price',
                'billing_type' => 'one_time',
                'duration_months' => '',
                'amount' => '',
                'is_active' => true,
            ],
        ];
    @endphp

    {{-- datalist untuk auto-suggest --}}
    <datalist id="bundle-products-list">
        @foreach ($products as $p)
            <option
                value="{{ $p->sku }} — {{ $p->product_name }}{{ $p->model ? ' (' . $p->model . ')' : '' }}">
            </option>
        @endforeach
    </datalist>

    <script>
        (function() {
            const products = @json(
                $products->map(fn($p) => [
                            'id' => $p->id,
                            'label' => "{$p->sku} — {$p->product_name}" . ($p->model ? " ({$p->model})" : ''),
                        ])->values());

            const seedItems = @json($seedItems);
            const seedPrices = @json($seedPrices);

            const itemsWrap = document.getElementById('items-wrap');
            const pricesWrap = document.getElementById('prices-wrap');

            function labelById(productId) {
                const found = products.find(p => String(p.id) === String(productId));
                return found ? found.label : '';
            }

            function idByLabel(label) {
                const found = products.find(p => p.label === label);
                return found ? found.id : '';
            }

            // ✅ item row (layout mirip create product & sejajar)
            function itemRow(i, val) {
                const currentLabel = val.product_id ? labelById(val.product_id) : '';

                return `
                <div class="rounded-2xl border border-gray-200 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-8">
                            <label class="text-xs font-medium text-gray-600">Product</label>

                            <input
                                type="text"
                                list="bundle-products-list"
                                data-product-search
                                value="${currentLabel}"
                                placeholder="Ketik SKU / nama product..."
                                class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 leading-none
                                       focus:border-blue-500 focus:ring-blue-500"
                            />

                            <input type="hidden" name="items[${i}][product_id]" data-product-id value="${val.product_id ?? ''}">
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-gray-600">Qty</label>
                            <input type="number" min="1" name="items[${i}][qty]" value="${val.qty ?? 1}"
                                   class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 leading-none
                                          focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div class="md:col-span-1 flex items-end justify-end">
                            <button type="button"
                                    class="remove-item inline-flex h-11 items-center rounded-xl border px-3 text-sm bg-white hover:bg-gray-50">
                                Hapus
                            </button>
                        </div>

                        <div class="md:col-span-12">
                            <p class="text-xs text-gray-400">Pilih dari suggestion agar tersimpan.</p>
                        </div>
                    </div>
                </div>`;
            }

            // ✅ price row (ada judul Harga #n seperti create product)
            function priceRow(i, val) {
                const isMonthly = (val.billing_type === 'monthly');
                const isActive = (val.is_active === true || val.is_active === 1 || val.is_active === '1' || val
                    .is_active === 'on');

                return `
                <div class="rounded-2xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Harga #${i + 1}</div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-4">
                            <label class="text-xs font-medium text-gray-600">Label</label>
                            <input name="prices[${i}][label]" value="${val.label ?? ''}"
                                   class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-xs font-medium text-gray-600">Type</label>
                            <select name="prices[${i}][billing_type]" data-billing-type
                                    class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500">
                                <option value="one_time" ${val.billing_type === 'one_time' ? 'selected' : ''}>One Time</option>
                                <option value="monthly" ${val.billing_type === 'monthly' ? 'selected' : ''}>Monthly</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-gray-600">Durasi (bulan)</label>
                            <input name="prices[${i}][duration_months]" data-duration
                                   value="${val.duration_months ?? ''}"
                                   ${isMonthly ? '' : 'disabled'}
                                   class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="-" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-medium text-gray-600">Nominal</label>
                            <input name="prices[${i}][amount]" value="${val.amount ?? ''}"
                                   class="mt-1 w-full h-11 rounded-xl border-gray-200 px-4 focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Contoh: 1250000" />
                        </div>

                        <div class="md:col-span-1 flex items-center gap-2">
                            <label class="text-xs text-gray-600">Active</label>
                            <input type="checkbox" name="prices[${i}][is_active]" value="1" ${isActive ? 'checked' : ''}
                                   class="rounded border-gray-300" />
                        </div>

                        <div class="md:col-span-12 flex justify-end">
                            <button type="button"
                                    class="remove-price inline-flex items-center rounded-xl border px-3 py-2 text-sm bg-white hover:bg-gray-50">
                                Hapus Harga
                            </button>
                        </div>
                    </div>
                </div>`;
            }

            function renderItems(values) {
                itemsWrap.innerHTML = values.map((v, i) => itemRow(i, v)).join('');
            }

            function renderPrices(values) {
                pricesWrap.innerHTML = values.map((v, i) => priceRow(i, v)).join('');
            }

            renderItems(seedItems);
            renderPrices(seedPrices);

            // Add item
            document.getElementById('add-item').addEventListener('click', () => {
                const current = itemsWrap.querySelectorAll('input[data-product-id]').length;
                itemsWrap.insertAdjacentHTML('beforeend', itemRow(current, {
                    product_id: '',
                    qty: 1
                }));
            });

            // Select suggestion -> set hidden product_id
            itemsWrap.addEventListener('input', (e) => {
                if (!e.target.matches('input[data-product-search]')) return;

                const card = e.target.closest('.rounded-2xl');
                const hidden = card.querySelector('input[data-product-id]');
                hidden.value = idByLabel(e.target.value) || '';
            });

            // Remove item + reindex
            itemsWrap.addEventListener('click', (e) => {
                if (!e.target.classList.contains('remove-item')) return;

                e.target.closest('.rounded-2xl').remove();

                [...itemsWrap.children].forEach((card, i) => {
                    card.querySelector('input[data-product-id]').name = `items[${i}][product_id]`;
                    card.querySelector('input[type="number"]').name = `items[${i}][qty]`;
                });
            });

            // Add price
            document.getElementById('add-price').addEventListener('click', () => {
                const current = pricesWrap.querySelectorAll('input[name^="prices["][name$="[label]"]').length;
                pricesWrap.insertAdjacentHTML('beforeend', priceRow(current, {
                    label: 'Bundling Price',
                    billing_type: 'one_time',
                    duration_months: '',
                    amount: '',
                    is_active: true
                }));
            });

            // Remove price + reindex
            pricesWrap.addEventListener('click', (e) => {
                if (!e.target.classList.contains('remove-price')) return;

                e.target.closest('.rounded-2xl').remove();

                [...pricesWrap.children].forEach((card, i) => {
                    card.querySelector('input[name$="[label]"]').name = `prices[${i}][label]`;
                    card.querySelector('select[data-billing-type]').name = `prices[${i}][billing_type]`;
                    card.querySelector('input[data-duration]').name = `prices[${i}][duration_months]`;
                    card.querySelector('input[name$="[amount]"]').name = `prices[${i}][amount]`;
                    card.querySelector('input[type="checkbox"]').name = `prices[${i}][is_active]`;
                });

                // re-render judul Harga #n biar urut lagi
                const values = [...pricesWrap.children].map((card, idx) => {
                    const label = card.querySelector('input[name$="[label]"]')?.value ?? '';
                    const billing = card.querySelector('select[data-billing-type]')?.value ??
                        'one_time';
                    const dur = card.querySelector('input[data-duration]')?.value ?? '';
                    const amt = card.querySelector('input[name$="[amount]"]')?.value ?? '';
                    const act = card.querySelector('input[type="checkbox"]')?.checked ?? true;

                    return {
                        label,
                        billing_type: billing,
                        duration_months: dur,
                        amount: amt,
                        is_active: act
                    };
                });
                renderPrices(values);
            });

            // Toggle duration enable/disable
            pricesWrap.addEventListener('change', (e) => {
                if (!e.target.matches('select[data-billing-type]')) return;

                const card = e.target.closest('.rounded-2xl');
                const duration = card.querySelector('input[data-duration]');

                if (e.target.value === 'monthly') {
                    duration.disabled = false;
                    duration.placeholder = 'Contoh: 36';
                } else {
                    duration.disabled = true;
                    duration.value = '';
                    duration.placeholder = '-';
                }
            });
        })();
    </script>
</x-dashboard-layout>
