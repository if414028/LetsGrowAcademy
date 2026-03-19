@php
    use Illuminate\Support\Str;

    $isHM = auth()->user()->hasRole('Health Manager');
    $isSM = auth()->user()->hasRole('Sales Manager');

    $banner = $contest->banner_url;
    $bannerSrc = null;
    if ($banner) {
        $bannerSrc = Str::startsWith($banner, ['http://', 'https://'])
            ? $banner
            : asset('storage/' . ltrim($banner, '/'));
    }

    $selectedHmIds = $selectedHmIds ?? [];
    $oldHmIds = collect(old('hm_ids', $selectedHmIds))->map(fn($v) => (int) $v);

    $rules = (array) ($contest->rules ?? []);
    $type = old('type', $contest->type ?? 'leaderboard');
    $isQualifier = $type === 'qualifier';

    $oldMinPersonal = old('monthly_min_personal_ns', $rules['monthly_min_personal_ns'] ?? null);
    $oldMinDirect = old('monthly_min_direct_active_partner', $rules['monthly_min_direct_active_partner'] ?? null);

    $productFilterType = old('product_filter_type', $rules['product_filter_type'] ?? 'all');
    $selectedProductIds = collect(old('product_ids', $rules['product_ids'] ?? []));
    $productMinQtys = old('product_min_qtys', $rules['product_min_qtys'] ?? []);
@endphp

<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Edit Kontes</h1>
            <p class="text-sm text-gray-500">Ubah kontes yang sudah dibuat (hanya bisa saat masih draft).</p>
        </div>

        <a href="{{ route('contests.show', $contest) }}"
            class="inline-flex items-center rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            ← Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="mb-1 font-semibold">Ada input yang belum valid:</div>
            <ul class="ml-5 list-disc">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (($contest->status ?? 'draft') !== 'draft')
        <div class="mt-4 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            Kontes ini sudah <span class="font-semibold">{{ $contest->status }}</span>, sehingga tidak bisa diubah.
        </div>
    @endif

    <div class="mt-6 rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
        <form method="POST" action="{{ route('contests.update', $contest) }}" enctype="multipart/form-data"
            class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Judul Kontes</label>
                    <input type="text" name="title" value="{{ old('title', $contest->title) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Kontes Instalasi Februari" required>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="(Opsional)">{{ old('description', $contest->description) }}</textarea>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Start Date</label>
                    <input type="date" name="start_date"
                        value="{{ old('start_date', optional($contest->start_date)->format('Y-m-d')) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">End Date</label>
                    <input type="date" name="end_date"
                        value="{{ old('end_date', optional($contest->end_date)->format('Y-m-d')) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Tanggal Install Maksimum</label>
                    <input type="date" name="max_install_date"
                        value="{{ old('max_install_date', optional($contest->max_install_date)->format('Y-m-d')) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-2 text-xs text-gray-500">
                        SO dihitung jika key in ada di periode kontes dan tanggal install <= tanggal ini. Jika
                            dikosongkan, default mengikuti End Date. </p>
                </div>

                <div class="hidden md:block"></div>

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Tipe Kontes</label>
                    <select id="contest_type" name="type"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="leaderboard" {{ $type === 'leaderboard' ? 'selected' : '' }}>
                            Kontes Pribadi
                        </option>
                        <option value="qualifier" {{ $type === 'qualifier' ? 'selected' : '' }}>
                            Kontes Team
                        </option>
                    </select>
                    <p class="mt-2 text-xs text-gray-500">
                        Semua kontes dihitung dari total qty (SUM qty) Sales Order “selesai”.
                        Kontes Team punya syarat bulanan (personal qty + direct active partner).
                    </p>
                </div>

                <div>
                    <label id="target_label" class="text-sm font-semibold text-gray-700">Target Total Qty</label>
                    <input type="number" id="target_unit" name="target_unit"
                        value="{{ old('target_unit', $contest->target_unit) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: 25" min="1">
                    <p id="target_hint" class="mt-2 hidden text-xs text-gray-500">
                        Untuk Qualifier, target ini adalah total qty selama periode.
                    </p>
                    <p id="target_unit_note" class="mt-1 text-xs text-gray-500 hidden">
                        Target Total Qty tidak digunakan jika Jenis Produk = Spesifik Produk.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Reward</label>
                    <input type="text" name="reward" value="{{ old('reward', $contest->reward) }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Voucher / Bonus / Hadiah">
                </div>

                <div class="md:col-span-2 rounded-2xl border bg-gray-50 p-5">
                    <div class="text-sm font-semibold text-gray-900">Filter Produk</div>
                    <div class="mt-1 text-xs text-gray-600">
                        Tentukan apakah kontes berlaku untuk semua produk, hanya produk tertentu, atau mengecualikan
                        produk tertentu.
                    </div>

                    <div class="mt-4">
                        <label class="text-sm font-semibold text-gray-700">Jenis Produk</label>
                        <select id="product_filter_type" name="product_filter_type"
                            class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" {{ $productFilterType === 'all' ? 'selected' : '' }}>
                                Semua Produk
                            </option>
                            <option value="specific" {{ $productFilterType === 'specific' ? 'selected' : '' }}>
                                Spesifik Produk
                            </option>
                            <option value="exclude" {{ $productFilterType === 'exclude' ? 'selected' : '' }}>
                                Exclude Produk
                            </option>
                        </select>
                    </div>

                    <div id="product_picker_wrapper" class="mt-4 hidden">
                        <label class="text-sm font-semibold text-gray-700">Pilih Produk</label>
                        <input type="text" id="product_search"
                            class="mt-2 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ketik nama produk / bundle...">

                        <div id="product_list"
                            class="mt-3 max-h-80 space-y-3 overflow-y-auto rounded-xl border bg-white p-3">
                            @forelse(($productOptions ?? []) as $item)
                                @php
                                    $checked = $selectedProductIds->contains($item->value);
                                    $oldMinQty = data_get($productMinQtys, $item->value, 1);
                                @endphp

                                <div class="product-option rounded-xl border border-gray-200 p-3"
                                    data-label="{{ strtolower($item->label) }}">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <label class="flex flex-1 items-start gap-3">
                                            <input type="checkbox" name="product_ids[]" value="{{ $item->value }}"
                                                class="product-checkbox mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                data-product-value="{{ $item->value }}"
                                                {{ $checked ? 'checked' : '' }}>

                                            <div>
                                                <div class="text-sm font-medium text-gray-800">{{ $item->label }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $item->type === 'bundle' ? 'Bundle' : 'Produk Satuan' }}
                                                </div>
                                            </div>
                                        </label>

                                        <div class="product-min-qty-wrapper {{ $checked ? '' : 'hidden' }}"
                                            data-product-min-wrapper="{{ $item->value }}">
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Min Qty</label>
                                            <input type="number" name="product_min_qtys[{{ $item->value }}]"
                                                value="{{ $oldMinQty }}" min="1"
                                                class="product-min-qty-input w-24 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">Belum ada data produk.</div>
                            @endforelse
                        </div>

                        @error('product_ids')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Banner (Opsional)</label>

                    @if ($bannerSrc)
                        <div class="mt-2 flex items-center gap-3">
                            <img src="{{ $bannerSrc }}" alt="Banner"
                                class="h-16 w-16 rounded-xl border object-cover">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_banner" value="1"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    {{ old('remove_banner') ? 'checked' : '' }}>
                                Hapus banner
                            </label>
                        </div>
                    @endif

                    <input type="file" name="banner" accept="image/*"
                        class="mt-2 block w-full text-sm text-gray-600
                            file:mr-4 file:rounded-xl file:border-0 file:bg-gray-100
                            file:px-4 file:py-2 file:text-sm file:font-semibold
                            file:text-gray-700 hover:file:bg-gray-200">
                    <p class="mt-2 text-xs text-gray-500">
                        Jika diupload, akan mengganti banner sebelumnya.
                    </p>
                </div>

                <div id="rules_133" class="md:col-span-2 {{ $isQualifier ? '' : 'hidden' }}">
                    <div class="rounded-2xl border bg-purple-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Rules Qualifier</div>
                                <div class="text-xs text-gray-600">
                                    Pemenang adalah HP yang memenuhi syarat di setiap bulan selama periode kontes.
                                    Semua hitungan memakai total qty (SUM qty).
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Minimal Qty Pribadi / Bulan</label>
                                <input type="number" name="monthly_min_personal_ns" min="1"
                                    value="{{ $oldMinPersonal }}"
                                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-2 text-xs text-gray-500">
                                    Contoh: 3 (tiap bulan minimal total qty pribadi = 3).
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700">Minimal Direct Active Partner /
                                    Bulan</label>
                                <input type="number" name="monthly_min_direct_active_partner" min="0"
                                    value="{{ $oldMinDirect }}"
                                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-2 text-xs text-gray-500">
                                    Partner dianggap “active” jika minimal qty pribadinya/bulan memenuhi angka di kiri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-gray-50 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Target Tim</div>
                        <div class="text-xs text-gray-600">
                            Peserta kontes akan otomatis mencakup HM terpilih + semua HP di bawah HM tersebut.
                        </div>
                    </div>

                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        {{ auth()->user()->getRoleNames()->first() }}
                    </span>
                </div>

                <div class="mt-4">
                    @if ($isSM)
                        <div class="rounded-xl border bg-white px-4 py-3 text-sm text-gray-700">
                            Kamu adalah <span class="font-semibold">Sales Manager</span>.
                            Kontes akan otomatis berlaku untuk
                            <span class="font-semibold">semua Health Manager</span> di bawah kamu
                            dan seluruh Health Planner di bawah HM tersebut.
                        </div>
                        <input type="hidden" name="target_mode" value="all_downline">
                    @elseif(
                        $isHM ||
                            auth()->user()->hasAnyRole(['Admin', 'Head Admin']))
                        <label class="text-sm font-semibold text-gray-700">Pilih Health Manager</label>
                        <select name="hm_ids[]" multiple
                            class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @forelse(($healthManagers ?? []) as $hm)
                                <option value="{{ $hm->id }}"
                                    {{ $oldHmIds->contains((int) $hm->id) ? 'selected' : '' }}>
                                    {{ $hm->name }} ({{ $hm->dst_code ?? '-' }})
                                </option>
                            @empty
                                <option disabled>Tidak ada Health Manager</option>
                            @endforelse
                        </select>
                        <p class="mt-2 text-xs text-gray-500">
                            Peserta kontes akan mencakup HM terpilih dan seluruh HP di bawahnya.
                        </p>
                    @else
                        <div class="rounded-xl border bg-white px-4 py-3 text-sm text-gray-700">
                            Kamu tidak memiliki akses untuk mengubah kontes.
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('contests.show', $contest) }}"
                    class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </a>

                <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const typeEl = document.getElementById('contest_type');
            const rulesEl = document.getElementById('rules_133');
            const targetLabel = document.getElementById('target_label');
            const targetHint = document.getElementById('target_hint');

            const productFilterTypeEl = document.getElementById('product_filter_type');
            const productPickerWrapper = document.getElementById('product_picker_wrapper');
            const productSearchEl = document.getElementById('product_search');
            const productOptionEls = Array.from(document.querySelectorAll('.product-option'));
            const productCheckboxEls = Array.from(document.querySelectorAll('.product-checkbox'));

            const targetUnitEl = document.getElementById('target_unit');
            const targetUnitNoteEl = document.getElementById('target_unit_note');

            function syncContestType() {
                const type = typeEl?.value || 'leaderboard';

                if (type === 'qualifier') {
                    rulesEl?.classList.remove('hidden');
                    targetHint?.classList.remove('hidden');
                    if (targetLabel) targetLabel.textContent = 'Target Total Qty (Periode)';
                } else {
                    rulesEl?.classList.add('hidden');
                    targetHint?.classList.add('hidden');
                    if (targetLabel) targetLabel.textContent = 'Target Total Qty';
                }
            }

            function syncProductFilter() {
                const value = productFilterTypeEl?.value || 'all';

                if (value === 'specific' || value === 'exclude') {
                    productPickerWrapper?.classList.remove('hidden');
                } else {
                    productPickerWrapper?.classList.add('hidden');
                }

                if (targetUnitEl) {
                    const isRequired = value === 'all' || value === 'exclude';
                    targetUnitEl.disabled = false;
                    targetUnitEl.required = isRequired;

                    targetUnitEl.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');

                    if (value === 'specific') {
                        targetUnitNoteEl?.classList.remove('hidden');
                        targetUnitNoteEl.textContent =
                            'Untuk Spesifik Produk, Target Total Qty boleh dikosongkan jika hanya ingin memakai min qty produk.';
                    } else {
                        targetUnitNoteEl?.classList.add('hidden');
                    }
                }

                syncMinQtyInputs();
            }

            function filterProducts() {
                const keyword = (productSearchEl?.value || '').trim().toLowerCase();

                productOptionEls.forEach((el) => {
                    const label = el.dataset.label || '';
                    el.style.display = label.includes(keyword) ? '' : 'none';
                });
            }

            function syncMinQtyInputs() {
                const filterType = productFilterTypeEl?.value || 'all';

                productCheckboxEls.forEach((checkbox) => {
                    const value = checkbox.dataset.productValue;
                    const wrapper = document.querySelector(`[data-product-min-wrapper="${value}"]`);

                    if (!wrapper) return;

                    if (filterType === 'specific' && checkbox.checked) {
                        wrapper.classList.remove('hidden');
                    } else {
                        wrapper.classList.add('hidden');
                    }
                });
            }

            typeEl?.addEventListener('change', syncContestType);
            productFilterTypeEl?.addEventListener('change', syncProductFilter);
            productSearchEl?.addEventListener('input', filterProducts);

            productCheckboxEls.forEach((checkbox) => {
                checkbox.addEventListener('change', syncMinQtyInputs);
            });

            syncContestType();
            syncProductFilter();
            filterProducts();
            syncMinQtyInputs();
        })();
    </script>
</x-dashboard-layout>
