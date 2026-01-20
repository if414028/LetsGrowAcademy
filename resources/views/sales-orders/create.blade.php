<x-dashboard-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">Create Sales Order</h1>
                <p class="text-sm text-gray-500">Input sales order baru.</p>
            </div>

            <a href="{{ route('sales-orders.index') }}" class="text-sm text-blue-600 hover:underline">
                ← Back
            </a>
        </div>

        @if(session('success'))
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Terjadi error:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sales-orders.store') }}" class="mt-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Left --}}
                <div class="lg:col-span-8 space-y-6">
                    {{-- Order Info --}}
                    <div class="rounded-2xl border bg-white p-5">
                        <h2 class="text-sm font-semibold text-gray-900">Order Info</h2>

                        @if(auth()->user()->hasRole('Admin'))
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Sales User</label>
                                    <select name="sales_user_id"
                                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">(Default: saya)</option>
                                        @foreach($salesUsers as $u)
                                            <option value="{{ $u->id }}" @selected(old('sales_user_id')==$u->id)>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Order No</label>
                                <input name="order_no" value="{{ old('order_no') }}"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="SO-0001" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Key In At</label>
                                <input type="datetime-local" name="key_in_at" value="{{ old('key_in_at') }}"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />
                                <div class="mt-1 text-xs text-gray-400">Kosongkan untuk otomatis: sekarang.</div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Install Date</label>
                                <input type="date" name="install_date" value="{{ old('install_date') }}"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Payment Method</label>
                                <select name="payment_method"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-</option>
                                    @foreach($paymentMethods as $m)
                                        <option value="{{ $m }}" @selected(old('payment_method')===$m)>{{ strtoupper($m) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input id="is_recurring" type="checkbox" name="is_recurring" value="1"
                                       @checked(old('is_recurring')) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <label for="is_recurring" class="text-sm text-gray-700">Recurring</label>
                            </div>
                        </div>
                    </div>

                    {{-- Items / Products --}}
                    <div class="rounded-2xl border bg-white p-5"
                        x-data="salesOrderItems(@js($products), @js(old('items', [['product_id' => '', 'qty' => 1]])))">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">Products</h2>

                            <button type="button"
                                    @click="addRow()"
                                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                + Add Item
                            </button>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl border">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Product</th>
                                        <th class="px-4 py-3 text-left w-40">Qty</th>
                                        <th class="px-4 py-3 text-right w-24">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y">
                                    <template x-for="(row, idx) in rows" :key="idx">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <select
                                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                    :name="`items[${idx}][product_id]`"
                                                    x-model="row.product_id"
                                                    required
                                                >
                                                    <option value="">-- Select Product --</option>
                                                    <template x-for="p in products" :key="p.id">
                                                        <option :value="p.id" x-text="`${p.product_name} (${p.sku})`"></option>
                                                    </template>
                                                </select>
                                            </td>

                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                    :name="`items[${idx}][qty]`"
                                                    x-model.number="row.qty"
                                                    required
                                                />
                                            </td>

                                            <td class="px-4 py-3 text-right">
                                                <button type="button"
                                                        @click="removeRow(idx)"
                                                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                                        :disabled="rows.length === 1"
                                                        :class="rows.length === 1 ? 'opacity-50 cursor-not-allowed' : ''">
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 text-xs text-gray-500">
                            Minimal 1 item. Qty harus &ge; 1.
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div class="rounded-2xl border bg-white p-5"
                         x-data="customerPicker()"
                         x-init="init()">
                        <h2 class="text-sm font-semibold text-gray-900">Customer</h2>

                        <input type="hidden" name="customer_id" :value="selectedId">

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="relative md:col-span-2">
                                <label class="text-xs font-medium text-gray-600">Customer Name</label>
                                <input
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    x-model="query"
                                    @input.debounce.250ms="search()"
                                    @focus="open = true"
                                    @keydown.escape="open = false"
                                    placeholder="Ketik nama customer..."
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />

                                {{-- dropdown --}}
                                <div x-show="open && items.length > 0" x-transition
                                     class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                    <template x-for="c in items" :key="c.id">
                                        <button type="button"
                                                class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                                @click="choose(c)">
                                            <div class="text-sm font-semibold text-gray-900" x-text="c.full_name"></div>
                                            <div class="text-xs text-gray-500">
                                                <span x-text="c.phone_number ?? '-'"></span>
                                                <span x-show="c.address"> • </span>
                                                <span x-text="c.address ?? ''"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <div class="mt-2 text-xs"
                                     :class="selectedId ? 'text-green-700' : 'text-gray-400'">
                                    <span x-show="selectedId">Selected existing customer.</span>
                                    <span x-show="!selectedId">Jika tidak ada di dropdown, customer akan dibuat otomatis saat submit.</span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Phone Number</label>
                                <input name="customer_phone" value="{{ old('customer_phone') }}"
                                       x-model="phone"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="08xxxx" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Address</label>
                                <input name="customer_address" value="{{ old('customer_address') }}"
                                       x-model="address"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Alamat (optional)" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right --}}
                <div class="lg:col-span-4 space-y-6">
                    <div class="rounded-2xl border bg-white p-5">
                        <h2 class="text-sm font-semibold text-gray-900">Status</h2>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-600">CCP Status</label>
                                <select name="ccp_status"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($ccpStatuses as $s)
                                        <option value="{{ $s }}" @selected(old('ccp_status','pending')===$s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Order Status</label>
                                <select name="status"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" @selected(old('status','draft')===$s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit"
                                class="mt-6 w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Save Sales Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function customerPicker() {
            return {
                query: @json(old('customer_name', '')),
                phone: @json(old('customer_phone', '')),
                address: @json(old('customer_address', '')),
                open: false,
                items: [],
                selectedId: @json(old('customer_id', null)),
                lastFetch: '',

                init() {
                    // kalau sebelumnya ada customer_id, biarkan (user sudah select)
                    // kalau user edit nama lagi, kita reset selection
                },

                async search() {
                    // kalau user mengubah input, reset selected customer
                    this.selectedId = null;

                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.items = [];
                        return;
                    }

                    // avoid duplicate request
                    if (this.lastFetch === q) return;
                    this.lastFetch = q;

                    const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!res.ok) return;

                    const data = await res.json();
                    this.items = Array.isArray(data) ? data : [];
                    this.open = true;
                },

                choose(c) {
                    this.selectedId = c.id;
                    this.query = c.full_name;
                    this.phone = c.phone_number || '';
                    this.address = c.address || '';
                    this.open = false;
                    this.items = [];
                }
            }
        }

        function salesOrderItems(products, initialRows) {
            return {
                products: products || [],
                rows: Array.isArray(initialRows) && initialRows.length
                    ? initialRows.map(r => ({ product_id: r.product_id ?? '', qty: r.qty ?? 1 }))
                    : [{ product_id: '', qty: 1 }],

                addRow() {
                    this.rows.push({ product_id: '', qty: 1 });
                },

                removeRow(i) {
                    if (this.rows.length === 1) return;
                    this.rows.splice(i, 1);
                },
            }
        }
    </script>
</x-dashboard-layout>
