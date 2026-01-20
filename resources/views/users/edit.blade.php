<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
            <p class="mt-2 text-gray-500">Update user information, files, and role</p>
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

        {{-- enctype utk upload --}}
        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Basic --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <input type="text" name="city_of_domicile" value="{{ old('city_of_domicile', $user->city_of_domicile) }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Jakarta">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Join Date</label>
                    <input type="date" name="join_date" value="{{ old('join_date', optional($user->join_date)->format('Y-m-d')) }}"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Role --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Role</label>
                    @php($currentRole = $user->getRoleNames()->first())
                    <select name="role"
                            class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500" required>
                        @foreach(($roles ?? collect()) as $role)
                            <option value="{{ $role->name }}" @selected(old('role', $currentRole) === $role->name)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">Role user ini akan di-update sesuai pilihan.</p>
                </div>
            </div>

            {{-- Uploads --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Photo</label>
                    <input type="file" name="photo" accept="image/*"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">

                    @if(!empty($user->photo))
                        <p class="mt-2 text-xs text-gray-500">Current file:</p>
                        <a class="text-xs font-semibold text-blue-600 hover:underline"
                           href="{{ asset('storage/'.$user->photo) }}" target="_blank" rel="noopener">
                            View Photo
                        </a>
                    @endif
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">ID Card</label>
                    <input type="file" name="id_card" accept=".jpg,.jpeg,.png,.pdf"
                           class="mt-1 w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500">

                    @if(!empty($user->id_card))
                        <p class="mt-2 text-xs text-gray-500">Current file:</p>
                        <a class="text-xs font-semibold text-blue-600 hover:underline"
                           href="{{ asset('storage/'.$user->id_card) }}" target="_blank" rel="noopener">
                            View ID Card
                        </a>
                    @endif
                </div>
            </div>

            {{-- Password --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
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

                <p class="md:col-span-2 text-xs text-gray-500 -mt-2">
                    Kosongkan password jika tidak ingin mengubah.
                </p>
            </div>

            {{-- Actions --}}
            <div class="pt-2 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Changes
                </button>

                <a href="{{ route('users.index') }}"
                   class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-dashboard-layout>
