<x-dashboard-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">Edit Sales Order</h1>
                <p class="text-sm text-gray-500">Ubah data sales order.</p>
            </div>

            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="text-sm text-blue-600 hover:underline">
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

        <form method="POST" action="{{ route('sales-orders.update', $salesOrder) }}" class="mt-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                {{-- Left --}}
                <div class="lg:col-span-8 space-y-6">
                    {{-- Order Info --}}
                    <div class="rounded-2xl border bg-white p-5">
                        <h2 class="text-sm font-semibold text-gray-900">Order Info</h2>

                        @php
                            /** @var \App\Models\User $authUser */
                            $authUser = auth()->user();
                        @endphp

                        @if($authUser->hasRole('Admin'))
                            <div x-data="salesUserPickerEdit()" x-init="init()">
                                <label class="text-xs font-medium text-gray-600">Sales User</label>

                                <input type="hidden" name="sales_user_id" :value="selectedId">

                                <div class="relative mt-1">
                                    <input
                                        type="text"
                                        x-model="query"
                                        @input.debounce.250ms="search()"
                                        @focus="open = true"
                                        @keydown.escape="open = false"
                                        placeholder="Ketik nama sales..."
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    />

                                    <div x-show="open && items.length > 0" x-transition
                                         class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                        <template x-for="u in items" :key="u.id">
                                            <button type="button"
                                                    class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                                    @click="choose(u)">
                                                <div class="text-sm font-semibold text-gray-900" x-text="u.label"></div>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-1 text-xs"
                                     :class="selectedId ? 'text-green-700' : 'text-gray-400'">
                                    <span x-show="selectedId">Sales user terpilih.</span>
                                    <span x-show="!selectedId">Wajib pilih sales dari dropdown.</span>
                                </div>
                            </div>
                        @else
                            {{-- Non-admin: sales user dipaksa auth user --}}
                            <input type="hidden" name="sales_user_id" value="{{ $authUser->id }}">
                            <div class="mt-2 text-xs text-gray-500">
                                Sales User: <span class="font-semibold text-gray-900">{{ $authUser->name }}</span>
                            </div>
                        @endif

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Order No</label>
                                <input
                                    id="order_no"
                                    name="order_no"
                                    value="{{ old('order_no', $salesOrder->order_no) }}"
                                    class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500"
                                    readonly
                                />
                                <div class="mt-1 text-xs text-gray-400">Order No tidak diubah saat edit.</div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Key In At</label>
                                <input type="datetime-local"
                                       name="key_in_at"
                                       value="{{ old('key_in_at', optional($salesOrder->key_in_at)->format('Y-m-d\TH:i')) }}"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Payment Method</label>
                                <select name="payment_method"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-</option>
                                    @foreach($paymentMethods as $m)
                                        <option value="{{ $m }}" @selected(old('payment_method', $salesOrder->payment_method)===$m)>{{ strtoupper($m) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input id="is_recurring" type="checkbox" name="is_recurring" value="1"
                                       @checked(old('is_recurring', $salesOrder->is_recurring))
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <label for="is_recurring" class="text-sm text-gray-700">Recurring</label>
                            </div>
                        </div>
                    </div>

                    {{-- Items / Products --}}
                    @php
                        $initialItems = old('items');
                        if (!$initialItems) {
                            $initialItems = $salesOrder->items->map(fn($it) => [
                                'product_id' => $it->product_id,
                                'qty' => $it->qty,
                            ])->values()->all();

                            if (empty($initialItems)) $initialItems = [['product_id' => '', 'qty' => 1]];
                        }
                    @endphp

                    <div class="rounded-2xl border bg-white p-5"
                         x-data="salesOrderItems(@js($products), @js($initialItems))">
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
                                    <template x-for="(row, idx) in rows" :key="row._key">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <select
                                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                    :name="`items[${idx}][product_id]`"
                                                    x-model="row.product_id"
                                                    x-init="$nextTick(() => { $el.value = row.product_id })"
                                                    required
                                                >
                                                    <option value="">-- Select Product --</option>
                                                    <template x-for="p in products" :key="p.id">
                                                        <option :value="String(p.id)" x-text="`${p.product_name} (${p.sku})`"></option>
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
                </div>

                {{-- Right --}}
                <div class="lg:col-span-4 space-y-6">
                    {{-- Customer --}}
                    <div class="rounded-2xl border bg-white p-5"
                         x-data="customerPickerEdit()"
                         x-init="init()">
                        <h2 class="text-sm font-semibold text-gray-900">Customer</h2>

                        <input type="hidden" name="customer_id" :value="selectedId">

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="relative md:col-span-2">
                                <label class="text-xs font-medium text-gray-600">Customer Name</label>
                                <input
                                    name="customer_name"
                                    value="{{ old('customer_name', $salesOrder->customer?->full_name) }}"
                                    x-model="query"
                                    @input.debounce.250ms="search()"
                                    @focus="open = true"
                                    @keydown.escape="open = false"
                                    placeholder="Ketik nama customer..."
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                />

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
                                    <span x-show="selectedId">Selected customer.</span>
                                    <span x-show="!selectedId">Jika tidak ada di dropdown, customer akan dibuat otomatis saat submit.</span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Phone Number</label>
                                <input name="customer_phone"
                                       value="{{ old('customer_phone', $salesOrder->customer?->phone_number) }}"
                                       x-model="phone"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="08xxxx" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Address</label>
                                <input name="customer_address"
                                       value="{{ old('customer_address', $salesOrder->customer?->address) }}"
                                       x-model="address"
                                       class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="Alamat (optional)" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-white p-5"
                        x-data="statusInstallFormEdit()"
                        x-init="init(); bindWatchers()">
                        <h2 class="text-sm font-semibold text-gray-900">Status</h2>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-600">CCP Status</label>
                                <select name="ccp_status"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($ccpStatuses as $s)
                                        <option value="{{ $s }}" @selected(old('ccp_status', $salesOrder->ccp_status)===$s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Status Instalasi</label>
                                <select name="status"
                                    x-model="status"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s }}" @selected(old('status', $salesOrder->status)===$s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>

                                {{-- Installation Date (muncul kalau status = dijadwalkan) --}}
                                <div class="mt-4" x-show="showInstallDate" x-transition>
                                    <label class="text-xs font-medium text-gray-600">Installation Date</label>
                                    <input type="date"
                                        name="install_date"
                                        x-model="installDate"
                                        :required="requiredInstallDate"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                    <div class="mt-1 text-xs text-gray-400" x-show="requiredInstallDate">
                                        Wajib diisi jika status instalasi "dijadwalkan".
                                    </div>

                                    <div class="mt-1 text-xs text-gray-400" x-show="!requiredInstallDate">
                                        Optional (tidak wajib) untuk status ini.
                                    </div>
                                </div>
                            </div>

                            {{-- Status Reason (muncul kalau status dibatalkan/ditunda/gagal penelponan) --}}
                            <div class="mt-4" x-show="showReason" x-transition>
                                <label class="text-xs font-medium text-gray-600">Alasan</label>
                                <textarea
                                    name="status_reason"
                                    rows="3"
                                    x-model="reason"
                                    :required="requiredReason"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Masukkan alasan..."
                                ></textarea>

                                <div class="mt-1 text-xs text-gray-400" x-show="requiredReason">
                                    Wajib diisi untuk status: dibatalkan / ditunda / gagal penelponan.
                                </div>
                            </div>

                        </div>

                        <button type="submit"
                                class="mt-6 w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Update Sales Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function customerPickerEdit() {
            return {
                query: @json(old('customer_name', $salesOrder->customer?->full_name ?? '')),
                phone: @json(old('customer_phone', $salesOrder->customer?->phone_number ?? '')),
                address: @json(old('customer_address', $salesOrder->customer?->address ?? '')),
                open: false,
                items: [],
                selectedId: @json(old('customer_id', $salesOrder->customer_id)),
                lastFetch: '',

                init() {},

                async search() {
                    // user mengubah input => reset selection
                    this.selectedId = null;

                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.items = [];
                        return;
                    }

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
            const normalize = (r, i) => ({
                _key: `${Date.now()}-${i}-${Math.random().toString(16).slice(2)}`,
                product_id: (r?.product_id === null || r?.product_id === undefined) ? '' : String(r.product_id),
                qty: r?.qty ?? 1,
            });

            return {
                products: products || [],
                rows: Array.isArray(initialRows) && initialRows.length
                    ? initialRows.map((r, i) => normalize(r, i))
                    : [normalize({ product_id: '', qty: 1 }, 0)],

                addRow() {
                    this.rows.push(normalize({ product_id: '', qty: 1 }, this.rows.length));
                },

                removeRow(i) {
                    if (this.rows.length === 1) return;
                    this.rows.splice(i, 1);
                },
            }
        }

        // Versi edit: tidak generate order_no
        function salesUserPickerEdit() {
            return {
                query: @json($oldSalesUser ? ($oldSalesUser->name . ($oldSalesUser->email ? " ({$oldSalesUser->email})" : "")) : ''),
                open: false,
                items: [],
                selectedId: @json(old('sales_user_id', $oldSalesUser?->id)),
                lastFetch: '',

                init() {},

                async search() {
                    this.selectedId = null;

                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.items = [];
                        return;
                    }

                    if (this.lastFetch === q) return;
                    this.lastFetch = q;

                    const res = await fetch(`{{ route('sales-orders.sales-users.search') }}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    this.items = Array.isArray(data) ? data : [];
                    this.open = true;
                },

                choose(u) {
                    this.selectedId = u.id;
                    this.query = u.label;
                    this.open = false;
                    this.items = [];
                }
            }
        }

        function statusInstallFormEdit() {
            return {
                status: @json(old('status', $salesOrder->status)),
                installDate: @json(old('install_date', $salesOrder->install_date ? \Carbon\Carbon::parse($salesOrder->install_date)->format('Y-m-d') : '')),
                reason: @json(old('status_reason', $salesOrder->status_reason ?? '')),

                normalizeStatus(v) {
                    return (v || '').toString().trim().toLowerCase();
                },

                get showInstallDate() {
                    // hide hanya untuk "menunggu verifikasi"
                    return this.normalizeStatus(this.status) !== 'menunggu verifikasi';
                },

                get requiredInstallDate() {
                    return this.normalizeStatus(this.status) === 'dijadwalkan';
                },

                get showReason() {
                    const st = this.normalizeStatus(this.status);
                    return ['dibatalkan', 'ditunda', 'gagal penelponan'].includes(st);
                },

                get requiredReason() {
                    return this.showReason;
                },

                init() {
                    // kalau status menunggu verifikasi, pastikan input kosong
                    if (!this.showInstallDate) this.installDate = '';
                    // alasan hanya wajib untuk 3 status, tapi kalau status awal bukan 3 itu, biarkan reason tetap tampil kosong
                },

                bindWatchers() {
                    this.$watch('status', (val) => {
                        const st = this.normalizeStatus(val);

                        // kalau balik ke menunggu verifikasi => hapus install date
                        if (st === 'menunggu verifikasi') {
                            this.installDate = '';
                        }

                        // kalau status bukan yg butuh alasan => hapus reason
                        if (!['dibatalkan', 'ditunda', 'gagal penelponan'].includes(st)) {
                            this.reason = '';
                        }
                    });
                }
            }
        }

    </script>
</x-dashboard-layout>