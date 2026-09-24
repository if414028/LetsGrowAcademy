<x-dashboard-layout>
    @php
        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
        $isAdmin =
            $authUser && method_exists($authUser, 'hasAnyRole') && $authUser->hasAnyRole(['Admin', 'Head Admin']);
        $currentRole = $user->getRoleNames()->first();
        $currentReferrerRole = $currentReferrer?->getRoleNames()->first();
        $isSecondaryAccount = (bool) old('is_secondary_account', $user->is_secondary_account);
    @endphp

    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
            <p class="mt-2 text-gray-500">Update user information, files, role, and referrer</p>
        </div>

        <a href="{{ url()->previous() }}"
            class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            Back
        </a>
    </div>

    <div class="mt-6 max-w-4xl rounded-2xl border bg-white p-6 shadow-sm">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$isAdmin)
            <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Kamu bukan Admin. Kamu hanya bisa mengubah <b>Photo</b>, <b>ID Card</b>, dan <b>Password</b>.
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @if ($isAdmin)
                {{-- Basic --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name (Display)</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Display name" required>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Full legal name (KTP)">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="name@email.com" required>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                {{-- Employment/Profile --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Status</label>
                        <select name="status"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="Active" @selected(old('status', $user->status ?? 'Active') === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $user->status ?? 'Active') === 'Inactive')>Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">DST Code</label>
                        <input type="text" name="dst_code" value="{{ old('dst_code', $user->dst_code) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="DST-xxxx">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">City of Domicile</label>
                        <input type="text" name="city_of_domicile"
                            value="{{ old('city_of_domicile', $user->city_of_domicile) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Jakarta">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" name="date_of_birth"
                            value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Join Date</label>
                        <input type="date" name="join_date"
                            value="{{ old('join_date', optional($user->join_date)->format('Y-m-d')) }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                @if ($authUser->hasRole('Head Admin') && $user->hasRole('Health Manager'))
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <label for="hm_since" class="text-sm font-semibold text-blue-900">Mulai Menjadi HM</label>
                        <input type="date" name="hm_since" id="hm_since"
                            value="{{ old('hm_since', optional($user->hm_since)->format('Y-m-d')) }}"
                            max="{{ now()->toDateString() }}"
                            class="mt-2 block w-full rounded-xl border-blue-200 bg-white focus:border-blue-500 focus:ring-blue-500 md:max-w-sm">
                        <p class="mt-2 text-xs text-blue-700">
                            Tanggal ini menjadi patokan awal siklus evaluasi Performance Recap setiap enam bulan.
                        </p>
                    </div>
                @endif

                {{-- Role + Referrer --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Role</label>
                        <select name="role"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            required>
                            @foreach ($roles ?? collect() as $role)
                                <option value="{{ $role->name }}" @selected(old('role', $currentRole) === $role->name)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Role user ini akan di-update sesuai pilihan.</p>
                    </div>

                    <div class="relative">
                        <label class="text-sm font-medium text-gray-700">Referrer</label>

                        <input type="hidden" name="referrer_user_id" id="referrer_user_id"
                            value="{{ old('referrer_user_id', $currentReferrer?->id) }}">

                        <input type="text" id="referrer_search" autocomplete="off"
                            value="{{ old('referrer_user_id') ? '' : ($currentReferrer ? $currentReferrer->name . ' (' . $currentReferrer->email . ') - ' . ($currentReferrerRole ?? '-') : '') }}"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Cari nama / email referrer...">

                        <div id="referrer_selected"
                            class="mt-2 {{ old('referrer_user_id', $currentReferrer?->id) ? '' : 'hidden' }} rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                            <div class="flex items-start justify-between gap-3">
                                <div id="referrer_selected_text">
                                    @if (old('referrer_user_id', $currentReferrer?->id))
                                        {{ $currentReferrer ? $currentReferrer->name . ' (' . $currentReferrer->email . ') - ' . ($currentReferrerRole ?? '-') : '' }}
                                    @endif
                                </div>
                                <button type="button" id="clear_referrer"
                                    class="shrink-0 font-semibold text-blue-700 hover:text-blue-900">
                                    Ganti
                                </button>
                            </div>
                        </div>

                        <div id="referrer_results"
                            class="absolute z-20 mt-2 hidden max-h-72 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                        </div>

                        <p class="mt-2 text-xs text-gray-500">
                            Cari dan pilih referrer baru. Tidak boleh diri sendiri atau downline user ini.
                        </p>
                    </div>
                </div>

                {{-- Primary / Secondary account relationship --}}
                <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
                    <label for="is_secondary_account" class="flex cursor-pointer items-start gap-3">
                        <input type="hidden" name="is_secondary_account" value="0">
                        <input type="checkbox" id="is_secondary_account" name="is_secondary_account" value="1"
                            @checked($isSecondaryAccount)
                            class="mt-0.5 h-5 w-5 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Secondary Account</span>
                            <span class="mt-0.5 block text-xs leading-5 text-gray-600">
                                Tandai jika akun ini merupakan akun tambahan milik keluarga dari HP atau HM lain.
                            </span>
                        </span>
                    </label>

                    <div id="primary_account_field" class="mt-4 {{ $isSecondaryAccount ? '' : 'hidden' }}">
                        <label for="primary_account_search" class="text-sm font-medium text-gray-700">
                            Primary Account <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="hidden" name="primary_account_id" id="primary_account_id"
                            value="{{ old('primary_account_id', $user->primary_account_id) }}">

                        <div class="relative mt-1">
                            <input type="text" id="primary_account_search" autocomplete="off" role="combobox"
                                aria-autocomplete="list" aria-expanded="false" aria-controls="primary_account_results"
                                value="{{ $selectedPrimaryAccount ? $selectedPrimaryAccount->name . ' (' . $selectedPrimaryAccount->email . ') - ' . ($selectedPrimaryAccount->getRoleNames()->first() ?? '-') : '' }}"
                                class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Cari nama HP atau HM...">

                            <div id="primary_account_dropdown"
                                class="absolute z-30 mt-2 hidden w-full overflow-hidden rounded-xl border bg-white shadow-lg">
                                <ul id="primary_account_results" role="listbox" class="max-h-72 overflow-auto"></ul>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-gray-500">
                            Hanya akun utama Health Planner atau Health Manager yang dapat dipilih.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Uploads --}}
            <div
                class="grid grid-cols-1 gap-4 @if (!$isAdmin) pt-2 @endif @if ($isAdmin) border-t pt-4 @endif md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Photo</label>
                    <input type="file" name="photo" accept="image/*"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">

                    @if (!empty($user->photo))
                        <p class="mt-2 text-xs text-gray-500">Current file:</p>
                        <a class="text-xs font-semibold text-blue-600 hover:underline"
                            href="{{ asset('storage/' . $user->photo) }}" target="_blank" rel="noopener">
                            View Photo
                        </a>
                    @endif
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">ID Card</label>
                    <input type="file" name="id_card" accept=".jpg,.jpeg,.png,.pdf"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">

                    @if (!empty($user->id_card))
                        <p class="mt-2 text-xs text-gray-500">Current file:</p>
                        <a class="text-xs font-semibold text-blue-600 hover:underline"
                            href="{{ asset('storage/' . $user->id_card) }}" target="_blank" rel="noopener">
                            View ID Card
                        </a>
                    @endif
                </div>
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">New Password (optional)</label>
                    <input type="password" name="password"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Leave blank if unchanged">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="password_confirmation"
                        class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Repeat new password">
                </div>

                <p class="md:col-span-2 -mt-2 text-xs text-gray-500">
                    Kosongkan password jika tidak ingin mengubah.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Changes
                </button>

                <a href="{{ route('users.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @if ($isAdmin)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('referrer_search');
                const hiddenInput = document.getElementById('referrer_user_id');
                const resultsBox = document.getElementById('referrer_results');
                const selectedBox = document.getElementById('referrer_selected');
                const selectedText = document.getElementById('referrer_selected_text');
                const clearBtn = document.getElementById('clear_referrer');

                if (!searchInput || !hiddenInput || !resultsBox || !selectedBox || !selectedText) {
                    return;
                }

                let debounceTimer = null;

                function renderResults(items) {
                    if (!items || items.length === 0) {
                        resultsBox.innerHTML = `
                            <div class="px-4 py-3 text-sm text-gray-500">
                                Referrer tidak ditemukan.
                            </div>
                        `;
                        resultsBox.classList.remove('hidden');
                        return;
                    }

                    resultsBox.innerHTML = items.map(item => `
                        <button
                            type="button"
                            class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50 last:border-b-0"
                            data-id="${item.id}"
                            data-label="${escapeHtml(item.label)}"
                        >
                            <div class="text-sm font-semibold text-gray-900">${escapeHtml(item.name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(item.email)}</div>
                            <div class="mt-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700">
                                ${escapeHtml(item.role ?? '-')}
                            </div>
                        </button>
                    `).join('');

                    resultsBox.classList.remove('hidden');
                }

                function escapeHtml(str) {
                    return String(str ?? '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                }

                async function fetchReferrers(keyword) {
                    try {
                        const url = `{{ route('users.search-referrers') }}?q=${encodeURIComponent(keyword)}`;
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch referrers');
                        }

                        const data = await response.json();
                        renderResults(data);
                    } catch (error) {
                        resultsBox.innerHTML = `
                            <div class="px-4 py-3 text-sm text-red-500">
                                Gagal mengambil data referrer.
                            </div>
                        `;
                        resultsBox.classList.remove('hidden');
                    }
                }

                searchInput.addEventListener('input', function() {
                    const keyword = this.value.trim();

                    hiddenInput.value = '';
                    selectedBox.classList.add('hidden');

                    if (debounceTimer) clearTimeout(debounceTimer);

                    if (keyword.length < 2) {
                        resultsBox.classList.add('hidden');
                        resultsBox.innerHTML = '';
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetchReferrers(keyword);
                    }, 300);
                });

                resultsBox.addEventListener('click', function(e) {
                    const btn = e.target.closest('button[data-id]');
                    if (!btn) return;

                    const id = btn.dataset.id;
                    const label = btn.dataset.label;

                    hiddenInput.value = id;
                    searchInput.value = '';
                    selectedText.textContent = label;
                    selectedBox.classList.remove('hidden');
                    resultsBox.classList.add('hidden');
                    resultsBox.innerHTML = '';
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        hiddenInput.value = '';
                        searchInput.value = '';
                        selectedText.textContent = '';
                        selectedBox.classList.add('hidden');
                        resultsBox.classList.add('hidden');
                        resultsBox.innerHTML = '';
                        searchInput.focus();
                    });
                }

                document.addEventListener('click', function(e) {
                    if (!resultsBox.contains(e.target) && e.target !== searchInput) {
                        resultsBox.classList.add('hidden');
                    }
                });
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkbox = document.getElementById('is_secondary_account');
                const field = document.getElementById('primary_account_field');
                const searchInput = document.getElementById('primary_account_search');
                const hiddenInput = document.getElementById('primary_account_id');
                const dropdown = document.getElementById('primary_account_dropdown');
                const results = document.getElementById('primary_account_results');
                let debounceTimer = null;

                if (!checkbox || !field || !searchInput || !hiddenInput || !dropdown || !results) return;

                function closeDropdown() {
                    dropdown.classList.add('hidden');
                    searchInput.setAttribute('aria-expanded', 'false');
                }

                function openDropdown() {
                    dropdown.classList.remove('hidden');
                    searchInput.setAttribute('aria-expanded', 'true');
                }

                function syncVisibility() {
                    field.classList.toggle('hidden', !checkbox.checked);
                    searchInput.required = checkbox.checked;

                    if (!checkbox.checked) {
                        hiddenInput.value = '';
                        searchInput.value = '';
                        results.innerHTML = '';
                        closeDropdown();
                    }
                }

                async function fetchPrimaryAccounts(keyword = '') {
                    const url = new URL(@json(route('users.primary-accounts.search')), window.location.origin);
                    url.searchParams.set('q', keyword);
                    url.searchParams.set('exclude', @json($user->id));

                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    });

                    return response.ok ? response.json() : [];
                }

                function selectAccount(item) {
                    hiddenInput.value = item.id;
                    searchInput.value = item.label;
                    closeDropdown();
                }

                function renderResults(items) {
                    results.innerHTML = '';

                    if (!items.length) {
                        const empty = document.createElement('li');
                        empty.className = 'px-4 py-3 text-sm text-gray-500';
                        empty.textContent = 'Tidak ada akun HP atau HM yang cocok';
                        results.appendChild(empty);
                        openDropdown();
                        return;
                    }

                    items.forEach((item) => {
                        const option = document.createElement('li');
                        option.setAttribute('role', 'option');
                        option.tabIndex = 0;
                        option.className = 'cursor-pointer px-4 py-3 text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none';
                        option.textContent = item.label;
                        option.addEventListener('click', () => selectAccount(item));
                        option.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                selectAccount(item);
                            }
                        });
                        results.appendChild(option);
                    });

                    openDropdown();
                }

                async function loadResults(keyword = searchInput.value.trim()) {
                    renderResults(await fetchPrimaryAccounts(keyword));
                }

                checkbox.addEventListener('change', function() {
                    syncVisibility();
                    if (checkbox.checked) {
                        searchInput.focus();
                        loadResults();
                    }
                });

                searchInput.addEventListener('focus', function() {
                    loadResults(hiddenInput.value ? '' : searchInput.value.trim());
                });
                searchInput.addEventListener('input', function() {
                    hiddenInput.value = '';
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(loadResults, 250);
                });

                document.addEventListener('click', function(event) {
                    if (!dropdown.contains(event.target) && event.target !== searchInput) closeDropdown();
                });
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') closeDropdown();
                });

                syncVisibility();
            });
        </script>
    @endif
</x-dashboard-layout>
