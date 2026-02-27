<x-dashboard-layout>
    <div class="p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">Buat Sales Order</h1>
                <p class="text-sm text-gray-500">Input sales order baru.</p>
            </div>

            <a href="{{ url()->previous() }}"
                class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Back
            </a>
        </div>

        @if (session('success'))
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Terjadi error:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sales-orders.store') }}" class="mt-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-stretch">
                {{-- Left --}}
                <div class="lg:col-span-8 space-y-6 h-full">
                    {{-- Order Info --}}
                    <div class="rounded-2xl border bg-white p-5 h-full">
                        <h2 class="text-sm font-semibold text-gray-900">Order Info</h2>

                        <div class="mt-4" x-data="hmHpPicker()" x-init="init()" data-hmhp-picker>
                            <label class="text-xs font-medium text-gray-600">Health Manager <span
                                    class="text-red-500">*</span></label>

                            <input type="hidden" name="health_manager_id" :value="selectedHmId">

                            <div class="relative mt-1">
                                <input type="text" x-model="hmQuery" @input.debounce.250ms="searchHm()"
                                    @focus="hmOpen = true" @keydown.escape="hmOpen = false"
                                    placeholder="Ketik nama Health Manager..."
                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                <div x-show="hmOpen && hmItems.length > 0" x-transition
                                    class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                    <template x-for="u in hmItems" :key="u.id">
                                        <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                            @click="chooseHm(u)">
                                            <div class="text-sm font-semibold text-gray-900" x-text="u.label"></div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-2 text-xs" :class="selectedHmId ? 'text-green-700' : 'text-gray-400'">
                                <span x-show="selectedHmId">Health Manager terpilih.</span>
                                <span x-show="!selectedHmId">Wajib pilih Health Manager dari dropdown.</span>
                            </div>

                            <div class="mt-4">
                                <label class="text-xs font-medium text-gray-600">Health Planner <span
                                        class="text-red-500">*</span></label>

                                <input type="hidden" name="sales_user_id" :value="selectedHpId">

                                <div class="relative mt-1">
                                    <input type="text" x-model="hpQuery" @focus="hpOpen = true; ensureHpLoaded()"
                                        @input="filterHp()" @keydown.escape="hpOpen = false" :disabled="!selectedHmId"
                                        :class="!selectedHmId ? 'bg-gray-50 cursor-not-allowed' : ''"
                                        placeholder="Pilih / ketik Health Planner..."
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                    <!-- Dropdown putih -->
                                    <div x-show="hpOpen && filteredHp.length > 0" x-transition
                                        class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                        <template x-for="u in filteredHp" :key="u.id">
                                            <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                                @click="chooseHp(u)">
                                                <div class="text-sm font-semibold text-gray-900" x-text="u.label"></div>
                                                <div class="text-xs text-gray-500"
                                                    x-text="u.dst_code ? ('DST: ' + u.dst_code) : ''"></div>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-2 text-xs" :class="selectedHpId ? 'text-green-700' : 'text-gray-400'">
                                    <span x-show="selectedHpId">Health Planner terpilih.</span>
                                    <span x-show="!selectedHpId">Wajib pilih Health Planner dari dropdown.</span>
                                </div>
                            </div>

                        </div>


                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Order No <span
                                        class="text-red-500">*</span></label>
                                <input id="order_no" name="order_no" value="{{ old('order_no') }}"
                                    class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Auto generated" readonly />
                                <div class="mt-1 text-xs text-gray-400">Otomatis terisi setelah memilih Sales.</div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Key In At <span
                                        class="text-red-500">*</span></label>
                                <input type="datetime-local" name="key_in_at" value="{{ old('key_in_at') }}"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />
                                <div class="mt-1 text-xs text-gray-400">Kosongkan untuk otomatis: sekarang.</div>
                            </div>

                            @php
                                $isPrivileged = auth()
                                    ->user()
                                    ->hasAnyRole(['Admin', 'Head Admin']);
                            @endphp

                            <div x-data="paymentMethodRemarksCreate()" x-init="init()">
                                <label class="text-xs font-medium text-gray-600">Payment Method <span
                                        class="text-red-500">*</span></label>

                                <select name="payment_method" x-model="paymentMethod"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-</option>

                                    @foreach ($paymentMethods as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- ✅ Remarks POA (outright) - Admin only --}}
                                @if ($isPrivileged)
                                    <div class="mt-4" x-show="showRemarks" x-transition>
                                        <label class="text-xs font-medium text-gray-600">Remarks (POA)</label>
                                        <textarea name="payment_method_remarks" rows="3" x-model="remarks"
                                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Opsional, khusus POA...">{{ old('payment_method_remarks') }}</textarea>

                                        <div class="mt-1 text-xs text-gray-400">
                                            Hanya muncul jika Payment Method = POA.
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input id="is_recurring" type="checkbox" name="is_recurring" value="1"
                                    @checked(old('is_recurring'))
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <label for="is_recurring" class="text-sm text-gray-700">Recurring</label>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Jenis Customer</label>
                            <select name="customer_type"
                                class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                <option value="individu" @selected(old('customer_type', 'individu') === 'individu')>Individu</option>
                                <option value="corporate" @selected(old('customer_type') === 'corporate')>Corporate</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Right --}}
                <div class="lg:col-span-4 space-y-6">

                    {{-- Customer --}}
                    <div class="rounded-2xl border bg-white p-5" x-data="customerPicker()" x-init="init()">
                        <h2 class="text-sm font-semibold text-gray-900">Customer</h2>

                        <input type="hidden" name="customer_id" :value="selectedId">

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="relative md:col-span-2">
                                <label class="text-xs font-medium text-gray-600">Customer Name <span
                                        class="text-red-500">*</span></label>
                                <input name="customer_name" value="{{ old('customer_name') }}" x-model="query"
                                    @input.debounce.250ms="search()" @focus="open = true"
                                    @keydown.escape="open = false" placeholder="Ketik nama customer..."
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                {{-- dropdown --}}
                                <div x-show="open && items.length > 0" x-transition
                                    class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                    <template x-for="c in items" :key="c.id">
                                        <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                            @click="choose(c)">
                                            <div class="text-sm font-semibold text-gray-900" x-text="c.full_name">
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <span x-text="c.phone_number ?? '-'"></span>
                                                <span x-show="c.address"> • </span>
                                                <span x-text="c.address ?? ''"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <div class="mt-2 text-xs" :class="selectedId ? 'text-green-700' : 'text-gray-400'">
                                    <span x-show="selectedId">Selected existing customer.</span>
                                    <span x-show="!selectedId">Jika tidak ada di dropdown, customer akan dibuat
                                        otomatis saat submit.</span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Phone Number <span
                                        class="text-red-500">*</span></label>
                                <input name="customer_phone" value="{{ old('customer_phone') }}" x-model="phone"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="08xxxx" />
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">
                                    Address <span class="text-red-500">*</span>
                                </label>
                                <input name="customer_address" value="{{ old('customer_address') }}"
                                    x-model="address" required
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Alamat customer..." />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-white p-5" x-data="statusInstallFormCreate()" x-init="init();
                    bindWatchers()">
                        <h2 class="text-sm font-semibold text-gray-900">Status</h2>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-xs font-medium text-gray-600">CCP Status</label>
                                <input type="hidden" name="ccp_status" x-bind:value="ccpStatus"
                                    x-show="disabledAll">
                                <select name="ccp_status" x-model="ccpStatus" :disabled="disabledAll"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($ccpStatuses as $s)
                                        <option value="{{ $s }}" @selected(old('ccp_status', 'menunggu pengecekan') === $s)>
                                            {{ ucfirst($s) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- CCP Remarks (when ditolak) --}}
                            <div class="mt-4" x-show="showCcpRemarks" x-transition>
                                <label class="text-xs font-medium text-gray-600">Remarks (CCP Ditolak)</label>
                                <textarea name="ccp_remarks" :disabled="disabledAll" rows="3" x-model="ccpRemarks"
                                    :required="requiredCcpRemarks"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Masukkan alasan kenapa ditolak..."></textarea>

                                <div class="mt-1 text-xs text-gray-400" x-show="requiredCcpRemarks">
                                    Wajib diisi jika CCP status: ditolak.
                                </div>
                            </div>

                            {{-- CCP Approved At (when disetujui) --}}
                            <div class="mt-4" x-show="showCcpApprovedAt" x-transition>
                                <label class="text-xs font-medium text-gray-600">Tanggal CCP Disetujui</label>
                                <input type="datetime-local" name="ccp_approved_at" :disabled="disabledAll"
                                    x-model="ccpApprovedAt" :required="requiredCcpApprovedAt"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                <div class="mt-1 text-xs text-gray-400" x-show="requiredCcpApprovedAt">
                                    Wajib diisi jika CCP status: disetujui.
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-gray-600">Status Instalasi</label>
                                <input type="hidden" name="status" x-bind:value="status"
                                    x-show="disabledAll">
                                <select name="status" x-model="status" :disabled="disabledAll"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" @selected(old('status', 'menunggu verifikasi') === $s)>
                                            {{ ucfirst($s) }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Installation Date --}}
                                <div class="mt-4" x-show="showInstallDate" x-transition>
                                    <label class="text-xs font-medium text-gray-600">Installation Date</label>
                                    <input type="date" name="install_date" :disabled="disabledAll"
                                        x-model="installDate" :required="requiredInstallDate"
                                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" />

                                    <div class="mt-1 text-xs text-gray-400" x-show="requiredInstallDate">
                                        Wajib diisi jika status instalasi membutuhkan tanggal instalasi.
                                    </div>
                                </div>
                            </div>

                            {{-- Status Reason --}}
                            <div class="mt-4" x-show="showReason" x-transition>
                                <label class="text-xs font-medium text-gray-600">Alasan</label>
                                <textarea name="status_reason" :disabled="disabledAll" rows="3" x-model="reason" :required="requiredReason"
                                    class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Masukkan alasan..."></textarea>

                                <div class="mt-1 text-xs text-gray-400" x-show="requiredReason">
                                    Wajib diisi untuk status: dibatalkan / ditunda / gagal penelponan.
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                            class="mt-6 w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Save Sales Order
                        </button>
                    </div>

                </div>

                {{-- Items / Products --}}
                <div class="lg:col-span-12">
                    <div class="rounded-2xl border bg-white p-5" x-data="salesOrderItems(@js($products), @js(old('items', [['product_id' => '', 'qty' => 1]])))" x-init="init()">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">Products <span
                                    class="text-red-500">*</span>
                            </h2>

                            <button type="button" @click="addRow()"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                + Add Item
                            </button>
                        </div>

                        <div class="mt-4 overflow-visible rounded-xl border">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Product</th>
                                        <th class="px-4 py-3 text-left w-40">Qty</th>
                                        <th class="px-4 py-3 text-left w-56">Price</th>
                                        <th class="px-4 py-3 text-right w-24">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y">
                                    <template x-for="(row, idx) in rows" :key="idx">
                                        <tr class="align-top">
                                            {{-- PRODUCT PICKER (searchable dropdown) --}}
                                            <td class="px-4 py-3 align-top" data-product-cell>
                                                <div class="flex flex-col gap-1">
                                                    {{-- hidden product_id --}}
                                                    <input type="hidden" :name="`items[${idx}][product_id]`"
                                                        :value="row.product_id" required>

                                                    {{-- input product --}}
                                                    <div class="relative">
                                                        <input type="text"
                                                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                            placeholder="Ketik SKU / nama product..."
                                                            x-model="row.query"
                                                            @input.debounce.150ms="searchProduct(idx)"
                                                            @focus="row.open = true; searchProduct(idx)"
                                                            @keydown.escape="row.open = false" />

                                                        {{-- dropdown --}}
                                                        <div x-show="row.open && row.items.length > 0" x-transition
                                                            class="absolute z-30 mt-2 w-full rounded-xl border bg-white shadow-lg overflow-hidden">
                                                            <template x-for="p in row.items" :key="p.id">
                                                                <button type="button"
                                                                    class="w-full text-left px-4 py-3 hover:bg-gray-50"
                                                                    @click="chooseProduct(idx, p)">
                                                                    <div class="text-sm font-semibold text-gray-900"
                                                                        x-text="p.label"></div>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    {{-- helper text (tingginya konsisten) --}}
                                                    <div class="text-xs min-h-[16px]"
                                                        :class="row.product_id ? 'text-green-700' : 'text-gray-400'">
                                                        <span x-show="row.product_id">Product terpilih.</span>
                                                        <span x-show="!row.product_id">Wajib pilih product dari
                                                            dropdown.</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                <input type="number" min="1"
                                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                    :name="`items[${idx}][qty]`" x-model.number="row.qty" required />
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                <select
                                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                                    :name="`items[${idx}][product_price_id]`"
                                                    x-model="row.product_price_id" :disabled="!row.product_id"
                                                    required>
                                                    <option value="">-- Select Price --</option>

                                                    <template x-for="pr in row.prices" :key="pr.id">
                                                        <option :value="pr.id" x-text="pr.label"></option>
                                                    </template>
                                                </select>

                                                <div class="mt-1 text-xs text-gray-400"
                                                    x-show="row.product_id && row.prices.length === 0">
                                                    Product ini belum punya price aktif.
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 text-right align-top">
                                                <button type="button" @click="removeRow(idx)"
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
            </div>
        </form>
    </div>

    <script>
        function paymentMethodRemarksCreate() {
            return {
                paymentMethod: @json(old('payment_method', '')),
                remarks: @json(old('payment_method_remarks', '')),

                normalize(v) {
                    return (v || '').toString().trim().toLowerCase();
                },

                get showRemarks() {
                    // POA label = outright value
                    return this.normalize(this.paymentMethod) === 'outright';
                },

                init() {
                    if (!this.showRemarks) this.remarks = '';

                    this.$watch('paymentMethod', () => {
                        if (!this.showRemarks) this.remarks = '';
                    });
                },
            };
        }

        function statusInstallFormCreate() {
            return {
                // 🔹 recurring state (ambil dari checkbox)
                isRecurring: @json(old('is_recurring') ? true : false),

                status: @json(old('status', 'menunggu verifikasi')),
                installDate: @json(old('install_date', '')),
                reason: @json(old('status_reason', '')),

                ccpStatus: @json(old('ccp_status', 'menunggu pengecekan')),
                ccpRemarks: @json(old('ccp_remarks', '')),
                ccpApprovedAt: @json(old('ccp_approved_at', '')),

                normalizeStatus(v) {
                    return (v || '').toString().trim().toLowerCase();
                },

                normalizeCcp(v) {
                    return (v || '').toString().trim().toLowerCase();
                },

                // 🔹 DISABLE SECTION jika tidak recurring
                get disabledAll() {
                    return !this.isRecurring;
                },

                get showInstallDate() {
                    if (this.disabledAll) return false;
                    const st = this.normalizeStatus(this.status);
                    return !['menunggu verifikasi', 'menunggu jadwal'].includes(st);
                },

                get requiredInstallDate() {
                    if (this.disabledAll) return false;
                    const st = this.normalizeStatus(this.status);
                    return ['dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'].includes(st);
                },

                get showReason() {
                    if (this.disabledAll) return false;
                    const st = this.normalizeStatus(this.status);
                    return ['dibatalkan', 'ditunda', 'gagal penelponan'].includes(st);
                },

                get requiredReason() {
                    return this.showReason;
                },

                get showCcpRemarks() {
                    return !this.disabledAll &&
                        this.normalizeCcp(this.ccpStatus) === 'ditolak';
                },

                get requiredCcpRemarks() {
                    return this.showCcpRemarks;
                },

                get showCcpApprovedAt() {
                    return !this.disabledAll &&
                        this.normalizeCcp(this.ccpStatus) === 'disetujui';
                },

                get requiredCcpApprovedAt() {
                    return this.showCcpApprovedAt;
                },

                init() {
                    this.syncCheckbox();

                    if (!this.isRecurring) {
                        this.resetAll();
                    }
                },

                bindWatchers() {
                    // 🔹 listen checkbox recurring
                    document.getElementById('is_recurring')
                        ?.addEventListener('change', (e) => {
                            this.isRecurring = e.target.checked;

                            if (!this.isRecurring) {
                                this.resetAll();
                            }
                        });

                    this.$watch('status', (val) => {
                        if (this.disabledAll) return;

                        const st = this.normalizeStatus(val);
                        if (['menunggu verifikasi', 'menunggu jadwal'].includes(st)) this.installDate = '';
                        if (!['dibatalkan', 'ditunda', 'gagal penelponan'].includes(st)) this.reason = '';
                    });

                    this.$watch('ccpStatus', (val) => {
                        if (this.disabledAll) return;

                        const c = this.normalizeCcp(val);
                        if (c !== 'ditolak') this.ccpRemarks = '';
                        if (c !== 'disetujui') this.ccpApprovedAt = '';
                    });
                },

                resetAll() {
                    this.status = 'menunggu verifikasi';
                    this.installDate = '';
                    this.reason = '';
                    this.ccpStatus = 'menunggu pengecekan';
                    this.ccpRemarks = '';
                    this.ccpApprovedAt = '';
                },

                syncCheckbox() {
                    const el = document.getElementById('is_recurring');
                    if (el) el.checked = this.isRecurring;
                }
            }
        }

        function customerPicker() {
            return {
                query: @json(old('customer_name', '')),
                phone: @json(old('customer_phone', '')),
                address: @json(old('customer_address', '')),
                open: false,
                items: [],
                selectedId: @json(old('customer_id', null)),
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

                    const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
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
            const mappedProducts = (products || []).map(p => ({
                id: p.id,
                label: `${p.product_name} (${p.sku}${p.model ? ` • ${p.model}` : ''})`,
                prices: (p.prices || []).map(pr => ({
                    id: pr.id,
                    // label price yang enak dibaca
                    label: `${pr.label} • ${pr.billing_type === 'monthly' ? 'Monthly' : 'One Time'}${pr.duration_months ? ` (${pr.duration_months} bln)` : ''} • Rp${Number(pr.amount || 0).toLocaleString('id-ID')}`
                }))
            }));

            function byId(id) {
                return mappedProducts.find(x => String(x.id) === String(id));
            }

            function labelById(id) {
                const p = byId(id);
                return p ? p.label : '';
            }

            function filter(q) {
                const qq = (q || '').trim().toLowerCase();
                if (!qq) return [];
                return mappedProducts
                    .filter(p => p.label.toLowerCase().includes(qq))
                    .slice(0, 10);
            }

            const rows = (Array.isArray(initialRows) && initialRows.length) ?
                initialRows.map(r => {
                    const pid = r.product_id ?? '';
                    const p = pid ? byId(pid) : null;

                    return {
                        product_id: pid,
                        product_price_id: r.product_price_id ?? '', // ✅ new
                        qty: r.qty ?? 1,
                        query: pid ? labelById(pid) : '',
                        open: false,
                        items: [],
                        lastFetch: '',
                        prices: p ? (p.prices || []) : [], // ✅ new
                    };
                }) : [{
                    product_id: '',
                    product_price_id: '',
                    qty: 1,
                    query: '',
                    open: false,
                    items: [],
                    lastFetch: '',
                    prices: [],
                }];

            return {
                rows,

                init() {
                    document.addEventListener('click', (e) => {
                        if (e.target.closest('[data-product-cell]')) return;
                        this.rows.forEach(r => r.open = false);
                    });
                },

                addRow() {
                    this.rows.push({
                        product_id: '',
                        product_price_id: '',
                        qty: 1,
                        query: '',
                        open: false,
                        items: [],
                        lastFetch: '',
                        prices: [],
                    });
                },

                removeRow(i) {
                    if (this.rows.length === 1) return;
                    this.rows.splice(i, 1);
                },

                searchProduct(idx) {
                    const row = this.rows[idx];
                    if (!row) return;

                    row.product_id = '';
                    row.product_price_id = '';
                    row.prices = [];

                    const q = (row.query || '').trim();
                    if (!q) {
                        row.items = [];
                        row.open = false;
                        return;
                    }

                    if (row.lastFetch === q) return;
                    row.lastFetch = q;

                    row.items = filter(q);
                    row.open = true;
                },

                chooseProduct(idx, p) {
                    const row = this.rows[idx];
                    if (!row) return;

                    row.product_id = p.id;
                    row.query = p.label;
                    row.items = [];
                    row.open = false;

                    // ✅ set prices dropdown sesuai product terpilih
                    row.prices = p.prices || [];

                    // ✅ auto pilih price pertama kalau tersedia
                    row.product_price_id = row.prices.length ? row.prices[0].id : '';
                },
            }
        }

        function hmHpPicker() {
            const orderNoEl = () => document.getElementById('order_no');

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            function formatTs(d) {
                const dd = pad2(d.getDate());
                const MM = pad2(d.getMonth() + 1);
                const yyyy = d.getFullYear();
                const HH = pad2(d.getHours());
                const mm = pad2(d.getMinutes());
                const ss = pad2(d.getSeconds());
                return `${dd}${MM}${yyyy}${HH}${mm}${ss}`;
            }

            function sanitizeDst(dst) {
                return (dst || '').toString().trim().toUpperCase().replace(/\s+/g, '');
            }

            return {
                // HM
                hmQuery: @json(
                    $oldHealthManager
                        ? $oldHealthManager->name . ($oldHealthManager->email ? " ({$oldHealthManager->email})" : '')
                        : ''),
                hmOpen: false,
                hmItems: [],
                selectedHmId: @json(old('health_manager_id', $oldHealthManager?->id)),
                hmLastFetch: '',

                // HP (custom dropdown)
                hpQuery: @json($oldSalesUser ? $oldSalesUser->name . ($oldSalesUser->email ? " ({$oldSalesUser->email})" : '') : ''),
                selectedHpId: @json(old('sales_user_id', $oldSalesUser?->id)),
                hpAll: [],
                filteredHp: [],
                hpOpen: false,
                hpLoadedForHmId: @json(old('health_manager_id', $oldHealthManager?->id) ?? null),

                init() {
                    // kalau page reload karena validation error & HM sudah ada, load list HP
                    if (this.selectedHmId) {
                        this.ensureHpLoaded().then(() => this.filterHp());
                    }

                    // optional: klik di luar -> tutup dropdown
                    document.addEventListener('click', (e) => {
                        if (e.target.closest('[data-hmhp-picker]')) return;
                        this.hmOpen = false;
                        this.hpOpen = false;
                    });
                },

                async searchHm() {
                    this.selectedHmId = null;

                    // reset HP saat HM berubah
                    this.resetHp();

                    const el = orderNoEl();
                    if (el) el.value = '';

                    const q = (this.hmQuery || '').trim();
                    if (q.length < 2) {
                        this.hmItems = [];
                        return;
                    }
                    if (this.hmLastFetch === q) return;
                    this.hmLastFetch = q;

                    const res = await fetch(
                        `{{ route('sales-orders.health-managers.search') }}?q=${encodeURIComponent(q)}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!res.ok) return;

                    const data = await res.json();
                    this.hmItems = Array.isArray(data) ? data : [];
                    this.hmOpen = true;
                },

                async chooseHm(u) {
                    this.selectedHmId = u.id;
                    this.hmQuery = u.label;
                    this.hmOpen = false;
                    this.hmItems = [];

                    // reset HP setelah HM dipilih
                    this.resetHp();

                    const el = orderNoEl();
                    if (el) el.value = '';

                    await this.ensureHpLoaded();
                    this.filterHp();
                },

                resetHp() {
                    this.selectedHpId = null;
                    this.hpQuery = '';
                    this.hpAll = [];
                    this.filteredHp = [];
                    this.hpOpen = false;
                    this.hpLoadedForHmId = null;
                },

                async ensureHpLoaded() {
                    if (!this.selectedHmId) return;

                    // kalau HM yang sama dan data sudah ada, skip fetch
                    if (String(this.hpLoadedForHmId) === String(this.selectedHmId) && this.hpAll.length) return;

                    const res = await fetch(
                        `{{ route('sales-orders.health-planners.list') }}?health_manager_id=${encodeURIComponent(this.selectedHmId)}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!res.ok) {
                        this.hpAll = [];
                        this.filteredHp = [];
                        this.hpLoadedForHmId = this.selectedHmId;
                        return;
                    }

                    const data = await res.json();
                    this.hpAll = Array.isArray(data) ? data : [];
                    this.filteredHp = this.hpAll;
                    this.hpLoadedForHmId = this.selectedHmId;
                },

                filterHp() {
                    const q = (this.hpQuery || '').trim().toLowerCase();

                    if (!q) {
                        this.filteredHp = this.hpAll;
                        return;
                    }

                    this.filteredHp = this.hpAll.filter(u =>
                        (u.label || '').toLowerCase().includes(q)
                    );
                },

                chooseHp(u) {
                    this.selectedHpId = u.id;
                    this.hpQuery = u.label;
                    this.hpOpen = false;

                    const dst = sanitizeDst(u.dst_code);
                    const ts = formatTs(new Date());
                    const code = dst || 'DST';
                    const orderNo = `SO-${code}-${ts}`;

                    const el = orderNoEl();
                    if (el) el.value = orderNo;
                },
            };
        }
    </script>
</x-dashboard-layout>
