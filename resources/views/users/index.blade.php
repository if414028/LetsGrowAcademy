<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Users</h1>
            <p class="mt-2 text-gray-500">Manage registered users and their roles</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 rounded-2xl bg-white shadow-sm border">
        <div class="p-4 md:p-6 border-b flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Total: <span class="font-semibold text-gray-900">{{ $users->total() }}</span>
            </div>

            @role('Admin')
                <a href="{{ route('users.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add User
                </a>
            @endrole
        </div>


        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500">
                    <tr class="border-b">
                        <th class="text-left font-semibold px-6 py-3">Name</th>
                        <th class="text-left font-semibold px-6 py-3">Email</th>
                        <th class="text-left font-semibold px-6 py-3">Roles</th>
                        <th class="text-left font-semibold px-6 py-3">Created</th>
                        <th class="text-left font-semibold px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium">
                                <a href="{{ route('users.show', $user) }}"
                                class="text-blue-600 hover:underline">
                                    {{ $user->name }}
                                </a>
                            </td>

                            <td class="px-6 py-4 text-gray-700">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4">
                                @php($roleNames = $user->roles->pluck('name'))
                                @if($roleNames->isEmpty())
                                    <span class="text-gray-400">-</span>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($roleNames as $role)
                                            <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-2.5 py-1 text-xs font-semibold">
                                                {{ $role }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                {{ optional($user->created_at)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    {{-- Show --}}
                                    <a href="{{ route('users.show', $user) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border bg-white text-gray-600 hover:bg-gray-50"
                                    title="View detail">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('users.edit', $user) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border bg-white text-gray-600 hover:bg-gray-50"
                                    title="Edit user">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.93Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 7.125 16.875 4.5"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 md:p-6">
            {{ $users->links() }}
        </div>
    </div>
</x-dashboard-layout>
