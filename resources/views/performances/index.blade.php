<x-dashboard-layout>
    <script>
        window.performanceMemberOptions = @json($memberOptions ?? []);
    </script>
    <div x-data="teamSheet">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">My Performance</h1>
                <p class="text-sm text-gray-500">Pantau kinerja tim penjualan.</p>
            </div>

            <a href="{{ route('performance.export', request()->query()) }}"
                class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm
           hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">

                {{-- Download Icon --}}
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5
               M7.5 12l4.5 4.5m0 0L16.5 12m-4.5 4.5V3" />
                </svg>

                Download
            </a>
        </div>

        {{-- Blue Card --}}
        <div class="mt-6 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-500 p-6 text-white shadow-sm">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H2v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a4 4 0 10-8 0 4 4 0 008 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm text-white/80">Direct Reports</div>
                        <div class="text-3xl font-bold leading-tight">{{ $teamMemberCount }}</div>
                    </div>
                </div>

                @hasanyrole('Health Planner')
                    <div class="text-right">
                        <div class="text-sm text-white/80">Total Net Sales Saya</div>
                        <div class="text-3xl font-bold leading-tight">{{ $myTotalUnits }}</div>
                    </div>
                @endhasanyrole
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Total Key-In --}}
            <div class="rounded-2xl border border-gray-300 bg-gray-50 p-5 shadow-sm">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Total Key-In
                </div>
                <div class="mt-2 text-3xl font-bold text-gray-800">
                    {{ (int) ($summary->total_key_in ?? 0) }}
                </div>
                <div class="mt-1 text-sm text-gray-500">
                    Total keseluruhan SO
                </div>
            </div>

            {{-- Total Key-In --}}
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <div class="text-xs font-semibold text-blue-700 uppercase tracking-wider">
                    Total Recurring
                </div>
                <div class="mt-2 text-3xl font-bold text-blue-900">
                    {{ (int) ($summary->total_recurring ?? 0) }}
                </div>
                <div class="mt-1 text-sm text-blue-600">
                    Status: menunggu verifikasi
                </div>
            </div>

            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm">
                <div class="text-xs font-semibold text-yellow-700 uppercase tracking-wider">
                    Dijadwalkan
                </div>
                <div class="mt-2 text-3xl font-bold text-yellow-900">
                    {{ (int) ($summary->menunggu_jadwal ?? 0) }}
                </div>
                <div class="mt-1 text-sm text-yellow-600">
                    Status: dijadwalkan
                </div>
            </div>

            <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
                <div class="text-xs font-semibold text-purple-700 uppercase tracking-wider">
                    Task ID
                </div>
                <div class="mt-2 text-3xl font-bold text-purple-900">
                    {{ (int) ($summary->task_id ?? 0) }}
                </div>
                <div class="mt-1 text-sm text-purple-600">
                    CCP disetujui + menunggu verifikasi
                </div>
            </div>

            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm">
                <div class="text-xs font-semibold text-green-700 uppercase tracking-wider">
                    Total sudah install (OK)
                </div>
                <div class="mt-2 text-3xl font-bold text-green-900">
                    {{ (int) ($summary->total_sudah_install ?? 0) }}
                </div>
                <div class="mt-1 text-sm text-green-600">
                    Status: selesai
                </div>
            </div>
        </div>


        {{-- Table --}}
        {{-- Table (Excel-like Sheet) --}}
        <div class="mt-6 rounded-2xl border bg-white shadow-sm">
            <div class="relative flex items-center justify-between gap-4 p-5 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-900">Team Sheet</h2>

                {{-- form filter kamu tetap --}}
                <form method="GET" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-end sm:gap-3">
                    {{-- Date From --}}
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                        <input type="date" name="from" value="{{ $from }}"
                            class="w-full sm:w-40 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none
                    focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                    </div>

                    {{-- Date To --}}
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                        <input type="date" name="to" value="{{ $to }}"
                            class="w-full sm:w-40 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none
                    focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                    </div>

                    {{-- Member (searchable dropdown) --}}
                    <div class="w-full sm:w-auto" x-data="memberSelect(window.performanceMemberOptions, @js($memberId ?? null), @js($memberLabel ?? ''))">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Partner</label>

                        <input type="hidden" name="member_id" :value="selectedId">

                        <div class="relative">
                            <input type="text" x-model="query" @focus="open = true" @click="open = true"
                                @input="open = true" @keydown.escape="open = false" placeholder="Pilih partner..."
                                class="w-full sm:w-64 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none
                   focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />

                            <div x-show="open" x-cloak @click.outside="open = false"
                                class="absolute z-[9999] mt-2 w-full max-h-56 overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                <template x-for="m in filtered()" :key="m.id">
                                    <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50"
                                        @click="choose(m)">
                                        <span x-text="m.label"></span>
                                    </button>
                                </template>

                                <div x-show="filtered().length === 0" class="px-3 py-2 text-sm text-gray-500">
                                    Tidak ada hasil.
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full sm:w-auto rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Apply
                    </button>

                    <a href="{{ url('/performance') }}"
                        class="w-full sm:w-auto text-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead
                        class="border-t border-b bg-blue-50 text-xs font-semibold uppercase tracking-wider text-blue-900">
                        <tr>
                            <th class="px-4 py-3 w-[240px]">Nama HP</th>
                            <th class="px-4 py-3 w-[320px]">Nama Customer</th>
                            <th class="px-4 py-3">Tanggal Key-in</th>
                            <th class="px-4 py-3">CCP Disetujui</th>
                            <th class="px-4 py-3">Key-in</th>
                            <th class="px-4 py-3">Install/NS</th>
                            <th class="px-4 py-3">Tanggal Instalasi</th>
                            <th class="px-4 py-3 w-[280px]">Remarks</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @php
                            $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d-M') : '-';
                        @endphp

                        @forelse(($teamSheetRows ?? collect()) as $hpName => $rows)
                            @php $rowspan = $rows->count(); @endphp

                            @foreach ($rows as $i => $r)
                                <tr class="hover:bg-gray-50">
                                    {{-- Nama HP (rowspan) --}}
                                    @if ($i === 0)
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 align-top"
                                            rowspan="{{ $rowspan }}">
                                            {{ $hpName }}
                                        </td>
                                    @endif

                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $r->customer_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $fmt($r->key_in_at) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $r->ccp_approved_at ? $fmt($r->ccp_approved_at) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                        {{ (int) $r->ns_units }}NS
                                    </td>

                                    {{-- Install/NS --}}
                                    <td class="px-4 py-3 text-sm">
                                        @if (($r->status ?? '') === 'selesai')
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                OK
                                            </span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>

                                    {{-- Tanggal Instalasi --}}
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $fmt($r->install_date) }}
                                    </td>

                                    {{-- Remarks (simple derived; kamu bisa ganti sesuai field yang ada) --}}
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $r->remarks ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Tidak ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom Sheet --}}
        <div x-show="sheetOpen" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="closeSheet()">
            {{-- overlay --}}
            <div class="absolute inset-0 bg-black/50" @click="closeSheet()"
                x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            {{-- panel --}}
            <div x-show="sheetOpen" x-ref="sheet" @click.stop
                class="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl shadow-2xl border-t transform will-change-transform"
                :style="`transform: translateY(${dragY}px);`"
                :class="dragging ? '' : 'transition-transform duration-300 ease-out'">

                {{-- ✅ HANDLE (drag ONLY here) --}}
                <div class="pt-3 pb-2 cursor-grab active:cursor-grabbing select-none"
                    @mousedown.prevent.stop="dragStart($event)" @touchstart.prevent.stop="dragStart($event)">
                    <div class="mx-auto w-12 h-1.5 bg-gray-200 rounded-full"></div>
                </div>

                {{-- ✅ CONTENT (NOT draggable) --}}
                <div class="p-6 max-h-[75vh] overflow-y-auto">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold text-gray-900" x-text="detail.name ?? '-'"></div>
                            <div class="text-sm text-gray-500">Detailed performance breakdown</div>
                        </div>
                        <button class="p-2 rounded-lg hover:bg-gray-100" @click="closeSheet()" aria-label="Close">
                            ✕
                        </button>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-blue-50 p-5">
                            <div class="text-sm text-gray-500">Total Units</div>
                            <div class="text-3xl font-bold text-gray-900"
                                x-text="loading ? '…' : (detail.total_units ?? 0)"></div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm font-semibold text-gray-900">
                            Orders (<span x-text="detail.orders?.length ?? 0"></span>)
                        </div>

                        <div class="mt-3 overflow-x-auto border rounded-xl">
                            <table class="min-w-full text-left">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3">Order No</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Key In</th>
                                        <th class="px-4 py-3">Install</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-sm">
                                    <template x-if="loading">
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Loading...
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-if="!loading && (detail.orders?.length ?? 0) === 0">
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No orders
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="o in (detail.orders ?? [])" :key="o.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-900" x-text="o.order_no"></td>
                                            <td class="px-4 py-3 text-gray-700" x-text="o.status ?? '-'"></td>
                                            <td class="px-4 py-3 text-gray-700" x-text="o.key_in_at ?? '-'"></td>
                                            <td class="px-4 py-3 text-gray-700" x-text="o.install_date ?? '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>
</x-dashboard-layout>

<script>
    // ✅ GLOBAL FACTORY (dipakai langsung oleh x-data="memberSelect(...)")
    window.memberSelect = function(members, initialId, initialLabel) {
        return {
            open: false,
            members: members || [],
            selectedId: initialId || null,
            query: initialLabel || '',

            filtered() {
                const q = (this.query || '').toLowerCase().trim();
                if (!q) return this.members;
                return this.members.filter(m => (m.label || '').toLowerCase().includes(q));
            },

            choose(m) {
                this.selectedId = m.id;
                this.query = m.label;
                this.open = false;
            },
        };
    };
</script>
