<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Add User</h1>
            <p class="mt-2 text-gray-500">Create a new user, assign role, and set hierarchy</p>
        </div>

        <a href="{{ route('users.index') }}"
           class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            Back
        </a>
    </div>

    <div class="mt-6 max-w-3xl rounded-2xl bg-white shadow-sm border p-6">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- penting: enctype utk upload --}}
        <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Basic --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Name (Display)</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Display name" required>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Full legal name (KTP)">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="name@email.com" required>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="08xxxxxxxxxx">
                </div>
            </div>

            {{-- Employment/Profile --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select name="status"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="Active" @selected(old('status','Active') === 'Active')>Active</option>
                        <option value="Inactive" @selected(old('status','Active') === 'Inactive')>Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">DST Code</label>
                    <input type="text" name="dst_code" value="{{ old('dst_code') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="DST-xxxx">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Kelurahan</label>
                    <input type="text" name="city_of_domicile" value="{{ old('city_of_domicile') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Jakarta">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Join Date</label>
                    <input type="date" name="join_date" value="{{ old('join_date') }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Hierarchy + Role --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Referrer (searchable) --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Referrer (Atasan)</label>

                    {{-- Hidden yang dikirim ke backend --}}
                    <input type="hidden" name="referrer_user_id" id="referrer_user_id"
                        value="{{ old('referrer_user_id') }}">

                    {{-- Input search --}}
                    <div class="relative mt-1">
                        <input
                            type="text"
                            id="referrer_search"
                            autocomplete="off"
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ketik nama referrer..."
                            value="{{ $oldReferrer ? ($oldReferrer->name.' ('.$oldReferrer->email.') - '.$oldReferrer->getRoleNames()->first()) : '' }}"
                        >

                        {{-- Dropdown result --}}
                        <div id="referrer_dropdown"
                            class="absolute z-20 mt-2 hidden w-full overflow-hidden rounded-xl border bg-white shadow-lg">
                            <ul id="referrer_results" class="max-h-72 overflow-auto"></ul>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        User baru akan menjadi bawahan langsung dari referrer.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Role</label>
                    <select id="role" name="role"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            required disabled>
                        <option value="" disabled selected>Select role</option>

                        @foreach(($roles ?? collect()) as $role)
                            @php $rank = $roleRanks[$role->name] ?? 999; @endphp

                            <option
                                value="{{ $role->name }}"
                                data-rank="{{ $rank }}"
                                @selected(old('role') === $role->name)
                            >
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-2 text-xs text-gray-500">
                        Role yang tersedia akan mengikuti role referrer (setara atau di bawahnya).
                    </p>
                </div>
            </div>

            {{-- Primary / Secondary account relationship --}}
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
                <label for="is_secondary_account" class="flex cursor-pointer items-start gap-3">
                    <input type="hidden" name="is_secondary_account" value="0">
                    <input
                        type="checkbox"
                        id="is_secondary_account"
                        name="is_secondary_account"
                        value="1"
                        @checked(old('is_secondary_account'))
                        class="mt-0.5 h-5 w-5 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
                    >
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Secondary Account</span>
                        <span class="mt-0.5 block text-xs leading-5 text-gray-600">
                            Tandai jika akun ini merupakan akun tambahan milik keluarga dari HP atau HM yang sudah terdaftar.
                        </span>
                    </span>
                </label>

                <div id="primary_account_field" class="mt-4 {{ old('is_secondary_account') ? '' : 'hidden' }}">
                    <label for="primary_account_search" class="text-sm font-medium text-gray-700">
                        Primary Account <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="hidden"
                        name="primary_account_id"
                        id="primary_account_id"
                        value="{{ old('primary_account_id') }}"
                    >

                    <div class="relative mt-1">
                        <input
                            type="text"
                            id="primary_account_search"
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="primary_account_results"
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Cari nama HP atau HM..."
                            value="{{ $oldPrimaryAccount ? ($oldPrimaryAccount->name.' ('.$oldPrimaryAccount->email.') - '.$oldPrimaryAccount->getRoleNames()->first()) : '' }}"
                        >

                        <div id="primary_account_dropdown"
                             class="absolute z-30 mt-2 hidden w-full overflow-hidden rounded-xl border bg-white shadow-lg">
                            <ul id="primary_account_results" role="listbox" class="max-h-72 overflow-auto"></ul>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        Hanya akun utama dengan role Health Planner atau Health Manager yang dapat dipilih.
                    </p>
                </div>
            </div>

            {{-- Uploads --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Foto</label>
                    <input type="file" name="photo" accept="image/*"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-2 text-xs text-gray-500">JPG/PNG, max 2MB</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Foto KTP</label>
                    <input type="file" name="id_card" accept=".jpg,.jpeg,.png,.pdf"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-2 text-xs text-gray-500">JPG/PNG/PDF, max 4MB</p>
                </div>
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t">
                <div>
                    <label class="text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Minimum 8 characters" required>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           required>
                </div>
            </div>

            {{-- Actions --}}
            <div class="pt-2 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Create User
                </button>

                <a href="{{ route('users.index') }}"
                   class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-dashboard-layout>

<script>
    (function () {
        const searchInput = document.getElementById('referrer_search');
        const hiddenId = document.getElementById('referrer_user_id');

        const dropdown = document.getElementById('referrer_dropdown');
        const resultsEl = document.getElementById('referrer_results');

        const roleSelect = document.getElementById('role');

        let refRank = null; // rank referrer yg dipilih
        let debounceTimer = null;

        function closeDropdown() {
            dropdown.classList.add('hidden');
            resultsEl.innerHTML = '';
        }

        function openDropdown() {
            dropdown.classList.remove('hidden');
        }

        function setSelectedReferrer(item) {
            hiddenId.value = item.id;
            searchInput.value = item.label;

            refRank = parseInt(item.rank ?? 999, 10);

            closeDropdown();
            filterRolesByReferrerRank();
        }

        function filterRolesByReferrerRank() {
            if (!refRank || isNaN(refRank)) {
                roleSelect.disabled = true;
                roleSelect.value = "";
                return;
            }

            roleSelect.disabled = false;

            const options = Array.from(roleSelect.options);
            let firstAllowedValue = null;

            options.forEach((opt, idx) => {
                if (idx === 0) return; // placeholder
                const roleRank = parseInt(opt.dataset.rank || "999", 10);

                // allowed: setara atau di bawah referrer (rank lebih besar = lebih bawah)
                const allowed = roleRank >= refRank;

                opt.hidden = !allowed;
                opt.disabled = !allowed;

                if (allowed && !firstAllowedValue) firstAllowedValue = opt.value;
            });

            const current = roleSelect.value;
            const currentOpt = options.find(o => o.value === current);
            const currentAllowed = currentOpt && !currentOpt.disabled && !currentOpt.hidden;

            if (!currentAllowed) roleSelect.value = firstAllowedValue ?? "";
        }

        async function searchReferrers(q) {
            const url = "{{ route('users.referrers.search') }}" + "?q=" + encodeURIComponent(q);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return [];
            return await res.json();
        }

        function renderResults(items) {
            resultsEl.innerHTML = '';

            if (!items.length) {
                resultsEl.innerHTML = `<li class="px-4 py-3 text-sm text-gray-500">Tidak ada hasil</li>`;
                openDropdown();
                return;
            }

            items.forEach((item) => {
                const li = document.createElement('li');
                li.className = "px-4 py-3 text-sm hover:bg-blue-50 cursor-pointer";
                li.textContent = item.label;

                li.addEventListener('click', () => setSelectedReferrer(item));
                resultsEl.appendChild(li);
            });

            openDropdown();
        }

        // Reset selection kalau user mengetik lagi (biar wajib pilih dari list)
        function clearSelectedIfTyping() {
            hiddenId.value = "";
            refRank = null;
            roleSelect.disabled = true;
            roleSelect.value = "";
        }

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();

            clearSelectedIfTyping();

            if (debounceTimer) clearTimeout(debounceTimer);

            if (q.length < 2) {
                closeDropdown();
                return;
            }

            debounceTimer = setTimeout(async () => {
                const items = await searchReferrers(q);
                renderResults(items);
            }, 250);
        });

        // click outside -> close
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== searchInput) closeDropdown();
        });

        // ESC -> close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDropdown();
        });

        // Kalau oldReferrer ada, coba set refRank awal (supaya role auto filter saat reload)
        @if($oldReferrer)
            (function(){
                const role = @json($oldReferrer->getRoleNames()->first());
                const map = @json($roleRanks);
                refRank = role ? (map[role] ?? 999) : 999;
                filterRolesByReferrerRank();
            })();
        @endif

    })();

    (function () {
        const checkbox = document.getElementById('is_secondary_account');
        const field = document.getElementById('primary_account_field');
        const searchInput = document.getElementById('primary_account_search');
        const hiddenId = document.getElementById('primary_account_id');
        const dropdown = document.getElementById('primary_account_dropdown');
        const resultsEl = document.getElementById('primary_account_results');
        let debounceTimer = null;

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
                hiddenId.value = '';
                searchInput.value = '';
                resultsEl.innerHTML = '';
                closeDropdown();
            }
        }

        async function searchPrimaryAccounts(query = '') {
            const url = "{{ route('users.primary-accounts.search') }}" + '?q=' + encodeURIComponent(query);
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            return response.ok ? response.json() : [];
        }

        function selectAccount(item) {
            hiddenId.value = item.id;
            searchInput.value = item.label;
            closeDropdown();
        }

        function renderResults(items) {
            resultsEl.innerHTML = '';

            if (!items.length) {
                const empty = document.createElement('li');
                empty.className = 'px-4 py-3 text-sm text-gray-500';
                empty.textContent = 'Tidak ada akun HP atau HM yang cocok';
                resultsEl.appendChild(empty);
                openDropdown();
                return;
            }

            items.forEach((item) => {
                const option = document.createElement('li');
                option.setAttribute('role', 'option');
                option.className = 'cursor-pointer px-4 py-3 text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none';
                option.tabIndex = 0;
                option.textContent = item.label;
                option.addEventListener('click', () => selectAccount(item));
                option.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        selectAccount(item);
                    }
                });
                resultsEl.appendChild(option);
            });

            openDropdown();
        }

        async function loadResults() {
            renderResults(await searchPrimaryAccounts(searchInput.value.trim()));
        }

        checkbox.addEventListener('change', () => {
            syncVisibility();
            if (checkbox.checked) {
                searchInput.focus();
                loadResults();
            }
        });

        searchInput.addEventListener('focus', loadResults);
        searchInput.addEventListener('input', () => {
            hiddenId.value = '';
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadResults, 250);
        });

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && event.target !== searchInput) closeDropdown();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeDropdown();
        });

        syncVisibility();
    })();
</script>
