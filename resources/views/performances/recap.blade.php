<x-dashboard-layout>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Performance Recap</h1>
            <p class="mt-1 text-sm text-gray-500">
                Evaluasi pencapaian Health Manager dalam siklus enam bulan sejak mulai menjabat sebagai HM.
            </p>
        </div>

        @if (!$isSingleHealthManager)
            <div class="rounded-2xl border bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('performance.recap') }}"
                    class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="w-full sm:max-w-md">
                        <label for="health_manager_id" class="mb-1 block text-sm font-medium text-gray-700">
                            Nama Health Manager
                        </label>
                        <select name="health_manager_id" id="health_manager_id"
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Health Manager</option>
                            @foreach ($healthManagerOptions as $healthManager)
                                <option value="{{ $healthManager->id }}"
                                    @selected((int) $selectedHealthManagerId === (int) $healthManager->id)>
                                    {{ $healthManager->full_name ?: $healthManager->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                            Terapkan Filter
                        </button>
                        @if ($selectedHealthManagerId)
                            <a href="{{ route('performance.recap') }}"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        @endif

        @forelse ($recaps as $recap)
            <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $recap['name'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Mulai HM: {{ $recap['hm_since'] }} · Periode: {{ $recap['cycle_label'] }}
                        </p>
                    </div>
                    <span @class([
                        'inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold',
                        'bg-green-100 text-green-700' => $recap['achieved'],
                        'bg-amber-100 text-amber-700' => !$recap['achieved'],
                    ])>
                        {{ $recap['achieved'] ? 'Target Tercapai' : 'Dalam Progress' }}
                    </span>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-blue-50 p-4">
                            <div class="text-sm text-gray-500">Achievement</div>
                            <div class="mt-1 text-2xl font-bold text-blue-700">{{ $recap['total'] }} NS</div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4">
                            <div class="text-sm text-gray-500">Target 6 Bulan</div>
                            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $recap['target'] }} NS</div>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-4">
                            <div class="text-sm text-gray-500">Shortage</div>
                            <div class="mt-1 text-2xl font-bold text-amber-700">{{ $recap['shortage'] }} NS</div>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="sticky left-0 z-10 border-b border-r bg-white px-4 py-3 text-left font-semibold text-gray-900">List</th>
                                    @foreach ($recap['months'] as $month)
                                        <th colspan="2" class="border-b border-r bg-gray-50 px-4 py-3 text-center font-semibold text-gray-900">
                                            {{ $month['label'] }}
                                        </th>
                                    @endforeach
                                </tr>
                                <tr>
                                    <th class="sticky left-0 z-10 border-b border-r bg-white"></th>
                                    @foreach ($recap['months'] as $month)
                                        <th class="border-b bg-gray-50 px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500">Ach</th>
                                        <th class="border-b border-r bg-gray-50 px-3 py-2 text-center text-xs font-semibold uppercase text-gray-500">Shrt</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="sticky left-0 z-10 border-r bg-white px-4 py-3 font-medium text-gray-900">Team NS</td>
                                    @foreach ($recap['months'] as $month)
                                        <td @class(['px-3 py-3 text-center font-semibold', 'text-gray-300' => $month['is_future'], 'text-gray-900' => !$month['is_future']])>
                                            {{ $month['achievement'] }}
                                        </td>
                                        <td @class(['border-r px-3 py-3 text-center', 'text-gray-300' => $month['is_future'], 'text-gray-500' => !$month['is_future']])>
                                            {{ $month['shortage'] }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-4 text-sm text-gray-500">
                        Achievement menghitung NS selesai milik HM dan seluruh downline-nya. Shortage menunjukkan sisa kumulatif menuju 120 NS.
                    </p>
                </div>
            </section>
        @empty
            <div class="rounded-2xl border bg-white px-6 py-12 text-center text-sm text-gray-500 shadow-sm">
                Belum ada Health Manager aktif untuk ditampilkan.
            </div>
        @endforelse
    </div>
</x-dashboard-layout>
