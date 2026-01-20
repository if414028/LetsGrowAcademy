<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">User Detail</h1>
            <p class="mt-2 text-gray-500">Detail information for this user</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('users.edit', $user) }}"
               class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Edit
            </a>

            <a href="{{ route('users.index') }}"
               class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Back
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Profile card --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-white shadow-sm border p-6">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
                        @if(!empty($user->photo))
                            <img
                                src="{{ asset('storage/'.$user->photo) }}"
                                alt="{{ $user->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <span class="text-lg font-bold text-white bg-blue-600 h-full w-full flex items-center justify-center">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                    </div>


                    <div class="min-w-0">
                        <p class="text-lg font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-500">Roles</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-xs font-semibold">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span class="text-gray-400 text-sm">-</span>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6 border-t pt-4 space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">Status</p>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $user->status ?? '-' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">DST Code</p>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $user->dst_code ?? '-' }}
                        </span>
                    </div>
                </div>

                {{-- Photo --}}
                <div class="mt-6">
                    <p class="text-sm text-gray-500">Photo</p>

                    @if(!empty($user->photo))
                        <a href="{{ asset('storage/'.$user->photo) }}" target="_blank" rel="noopener"
                           class="mt-2 block overflow-hidden rounded-xl border hover:opacity-95">
                            <img src="{{ asset('storage/'.$user->photo) }}" alt="User Photo" class="w-full h-56 object-cover">
                        </a>
                        <a href="{{ asset('storage/'.$user->photo) }}" target="_blank" rel="noopener"
                           class="mt-2 inline-block text-sm font-semibold text-blue-600 hover:underline">
                            Open Photo
                        </a>
                    @else
                        <p class="mt-2 text-sm text-gray-400">-</p>
                    @endif
                </div>

                {{-- ID Card --}}
                <div class="mt-6">
                    <div>
                        <p class="text-sm text-gray-500">ID Card</p>

                        @if(!empty($user->id_card))
                            @php
                                $idCardUrl = asset('storage/'.$user->id_card);
                                $extension = pathinfo($user->id_card, PATHINFO_EXTENSION);
                            @endphp

                            @if(in_array(strtolower($extension), ['jpg','jpeg','png']))
                                {{-- Preview Image --}}
                                <div class="mt-2 rounded-xl border overflow-hidden max-w-xs">
                                    <img
                                        src="{{ $idCardUrl }}"
                                        alt="ID Card {{ $user->name }}"
                                        class="w-full h-auto object-contain bg-gray-50"
                                    >
                                </div>

                                <a href="{{ $idCardUrl }}"
                                target="_blank"
                                class="mt-2 inline-block text-sm font-semibold text-blue-600 hover:underline">
                                    Open ID Card
                                </a>
                            @else
                                {{-- PDF / Other --}}
                                <a href="{{ $idCardUrl }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:underline mt-2">
                                    📄 Open ID Card (PDF)
                                </a>
                            @endif
                        @else
                            <p class="mt-1 text-gray-400">-</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Details --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900">Profile Details</h2>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Full Name</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $user->full_name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Phone Number</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $user->phone_number ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Date of Birth</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $user->date_of_birth ? $user->date_of_birth->format('d M Y') : '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Join Date</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $user->join_date ? $user->join_date->format('d M Y') : '-' }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">City of Domicile</p>
                        <p class="mt-1 text-base font-semibold text-gray-900">
                            {{ $user->city_of_domicile ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900">System</h2>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Created At</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ $user->created_at?->format('d M Y H:i') ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Last Login</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t pt-6">
                    <h2 class="text-lg font-semibold text-gray-900">Hierarchy</h2>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Referrer / Parent</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ $parentUser?->name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Direct Reports</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ $childrenCount ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Direct Reports --}}
                <div class="mt-6 border-t pt-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Direct Reports</h3>
                        <span class="text-xs text-gray-500">{{ $childrenCount ?? 0 }} user</span>
                    </div>

                    @if(($directReports ?? collect())->isEmpty())
                        <p class="mt-3 text-sm text-gray-500">Belum ada bawahan.</p>
                    @else
                        <div class="mt-4 overflow-hidden rounded-xl border">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">Role</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($directReports as $child)
                                        @php($childRole = $child->getRoleNames()->first())
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                {{ $child->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $child->email }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-xs font-semibold">
                                                    {{ $childRole ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('users.show', $child) }}"
                                                class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-dashboard-layout>
