<x-dashboard-layout>
    <div x-data="teamSheet">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">My Performance</h1>
                <p class="text-sm text-gray-500">Monitor performa tim.</p>
            </div>
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
                        <div class="text-sm text-white/80">Team Members</div>
                        <div class="text-3xl font-bold leading-tight">{{ $teamMemberCount }}</div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-sm text-white/80">Total Units Sold</div>
                    <div class="text-3xl font-bold leading-tight">{{ $myTotalUnits }}</div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="mt-6 rounded-2xl border bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 p-5 flex-wrap">
                <h2 class="text-lg font-semibold text-gray-900">Team Performance</h2>

                <form method="GET" class="flex items-center gap-2">
                    <input name="q" value="{{ $q }}" placeholder="Search member..."
                        class="w-64 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                    <button type="submit"
                        class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead
                        class="border-t border-b bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Salesperson</th>
                            <th class="px-6 py-3">Units</th>
                            <th class="px-6 py-3">Last Activity</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        @foreach ($teamPerformance as $row)
                            <tr class="hover:bg-gray-50 cursor-pointer" @click="openSheet({{ $row['id'] }})">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $row['units'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $row['last_activity'] }}</td>
                            </tr>
                        @endforeach
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

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('teamSheet', () => ({
                sheetOpen: false,
                loading: false,
                detail: {},

                async openSheet(userId) {
                    this.sheetOpen = true;
                    this.loading = true;
                    this.detail = {};

                    try {
                        const res = await fetch(`{{ url('/performance/team') }}/${userId}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) throw new Error('Failed to load');
                        this.detail = await res.json();
                    } catch (e) {
                        this.detail = {
                            name: 'Error',
                            total_units: 0,
                            orders: []
                        };
                    } finally {
                        this.loading = false;
                    }
                },

                closeSheet() {
                    this.sheetOpen = false;
                    this.loading = false;
                    this.detail = {};
                },
            }))
        })
    </script>
@endpush
