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
                    <label class="text-sm font-medium text-gray-700">City of Domicile</label>
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
                <div>
                    <label class="text-sm font-medium text-gray-700">Referrer (Atasan)</label>
                    <select id="referrer_user_id" name="referrer_user_id"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                            required>
                        <option value="" disabled selected>Select referrer</option>

                        @foreach(($referrers ?? collect()) as $ref)
                            @php
                                $refRole = $ref->getRoleNames()->first();
                                $refRank = $roleRanks[$refRole] ?? 999;
                            @endphp

                            <option
                                value="{{ $ref->id }}"
                                data-role="{{ $refRole }}"
                                data-rank="{{ $refRank }}"
                                @selected(old('referrer_user_id') == $ref->id)
                            >
                                {{ $ref->name }} ({{ $ref->email }}) - {{ $refRole ?? '-' }}
                            </option>
                        @endforeach
                    </select>
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

            {{-- Uploads --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Photo</label>
                    <input type="file" name="photo" accept="image/*"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-2 text-xs text-gray-500">JPG/PNG, max 2MB</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">ID Card</label>
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
        const referrerSelect = document.getElementById('referrer_user_id');
        const roleSelect = document.getElementById('role');

        function filterRolesByReferrer() {
            const selected = referrerSelect.options[referrerSelect.selectedIndex];
            const refRank = selected?.dataset?.rank ? parseInt(selected.dataset.rank, 10) : null;

            if (!refRank) {
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

            if (!currentAllowed) {
                roleSelect.value = firstAllowedValue ?? "";
            }
        }

        referrerSelect.addEventListener('change', filterRolesByReferrer);
        window.addEventListener('DOMContentLoaded', filterRolesByReferrer);
    })();
</script>
