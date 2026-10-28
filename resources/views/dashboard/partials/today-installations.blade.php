<section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="today-installations-heading">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 id="today-installations-heading" class="text-lg font-bold text-gray-900">Jadwal Pemasangan Hari Ini</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ today()->translatedFormat('d F Y') }} ·
                {{ request()->user()->hasAnyRole(['Admin', 'Head Admin']) ? 'Seluruh SO dengan jadwal pemasangan hari ini.' : 'SO Anda dan seluruh downline dengan jadwal pemasangan hari ini.' }}
            </p>
        </div>
        <span class="w-fit shrink-0 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $todayInstallations->total() }} SO</span>
    </div>

    @if ($todayInstallations->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500">Tidak ada jadwal pemasangan hari ini.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-gray-600">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold">Order Number</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Sales</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Customer</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Alamat Customer</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Detail SO</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($todayInstallations as $installation)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">{{ $installation->order_no }}</td>
                            <td class="px-5 py-4 text-gray-700">{{ $installation->salesUser?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-gray-700">{{ $installation->customer?->full_name ?? '-' }}</td>
                            <td class="min-w-[220px] whitespace-pre-line px-5 py-4 text-gray-600">{{ $installation->customer?->address ?: '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <a href="{{ route('dashboard.installations.show', ['salesOrder' => $installation, 'installations_page' => $todayInstallations->currentPage(), 'trend' => request('trend')]) }}"
                                    aria-label="Lihat detail SO {{ $installation->order_no }}"
                                    class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($todayInstallations->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $todayInstallations->links() }}</div>
        @endif
    @endif
</section>
