<x-dashboard-layout>
    <div class="flex items-start justify-between gap-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Users</h1>
            <p class="text-sm text-gray-500">Kelola user dan role.</p>
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
                    Tambah User
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
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                            text-gray-600 hover:bg-blue-50 hover:text-blue-600"
                                    title="Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                    c4.478 0 8.268 2.943 9.542 7
                                                    -1.274 4.057-5.064 7-9.542 7
                                                    -4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('users.edit', $user) }}"
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-lg border
                                            text-gray-600 hover:bg-yellow-50 hover:text-yellow-600"
                                        title="Edit">
                                            {{-- icon pencil --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"/>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
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
