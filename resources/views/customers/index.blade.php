<x-dashboard-layout>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Customer</h1>
            <p class="text-sm text-gray-500">Daftar customer milik Anda dan seluruh downline Anda.</p>
        </div>
        @hasanyrole('Admin|Head Admin|Health Planner|Health Manager')
            <a href="{{ route('customers.create') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tambah Customer</a>
        @endhasanyrole
    </div>
    @if (session('success'))
        <div role="status" class="mt-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b p-4">
            <form method="GET" action="{{ route('customers.index') }}" class="flex w-full gap-2 sm:max-w-lg">
                <input aria-label="Cari customer atau pemilik" name="q" value="{{ $search }}" placeholder="Cari customer, telepon, atau nama HP / HM..." class="min-w-0 flex-1 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                <button class="rounded-xl border px-4 py-2 text-sm font-medium hover:bg-gray-50">Cari</button>
            </form>
            <span class="text-sm text-gray-500">Total: {{ $customers->total() }} customer</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Telepon</th><th class="px-5 py-3">Alamat</th><th class="px-5 py-3">Nama HP / HM</th><th class="px-5 py-3">ID HP / HM</th><th class="px-5 py-3">Pemilik</th>
                        @hasanyrole('Admin|Head Admin')<th class="px-5 py-3">Aksi</th>@endhasanyrole
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4 font-semibold text-gray-900">{{ $customer->full_name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $customer->phone_number ?: '-' }}</td>
                            <td class="max-w-xs whitespace-normal break-words px-5 py-4 text-gray-600">{{ $customer->address ?: '-' }}</td>
                            <td class="px-5 py-4 text-gray-900">{{ $customer->owner?->full_name ?: ($customer->owner?->name ?? 'Belum ditentukan') }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $customer->owner?->dst_code ?: ($customer->owner?->id ?? '-') }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">{{ $customer->owner?->hasRole('Health Manager') ? 'HM' : ($customer->owner?->hasRole('Health Planner') ? 'HP' : '-') }}</span></td>
                            @hasanyrole('Admin|Head Admin')
                                <td class="px-5 py-4"><a href="{{ route('customers.edit', $customer) }}" class="font-medium text-blue-600 hover:underline">Edit</a></td>
                            @endhasanyrole
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">{{ $search ? 'Tidak ada customer yang cocok dengan pencarian.' : 'Belum ada customer. Tambahkan customer melalui menu ini atau pembuatan SO.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">{{ $customers->links() }}</div>
    </div>
</x-dashboard-layout>
