@php
    $isHM = auth()->user()->hasRole('Health Manager');
    $isSM = auth()->user()->hasRole('Sales Manager');
@endphp

<x-dashboard-layout>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Buat Kontes</h1>
            <p class="text-sm text-gray-500">Buat kontes baru untuk tim yang kamu pilih.</p>
        </div>

        <a href="{{ route('contests.index') }}"
            class="inline-flex items-center rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            ← Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-semibold mb-1">Ada input yang belum valid:</div>
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-black/5">
        <form method="POST" action="{{ route('contests.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Basic Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Judul Kontes</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Kontes Instalasi Februari" required>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Deskripsi</label>
                    <textarea name="description" rows="3"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="(Opsional)">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                {{-- Contest Type --}}
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Tipe Kontes</label>
                    <select id="contest_type" name="type"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="leaderboard"
                            {{ old('type', 'leaderboard') === 'leaderboard' ? 'selected' : '' }}>
                            Kontes Pribadi
                        </option>
                        <option value="qualifier" {{ old('type') === 'qualifier' ? 'selected' : '' }}>
                            Kontes Team
                        </option>
                    </select>

                    <p class="mt-2 text-xs text-gray-500">
                        Semua kontes dihitung dari total qty (SUM qty) Sales Order “selesai”.
                        Kontes Team punya syarat bulanan (personal qty + direct active partner).
                    </p>
                </div>

                {{-- Date basis --}}
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Basis Tanggal</label>
                    <select id="date_basis" name="date_basis"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="install_date"
                            {{ old('date_basis', 'install_date') === 'install_date' ? 'selected' : '' }}>
                            Install Date
                        </option>
                        <option value="key_in_at" {{ old('date_basis') === 'key_in_at' ? 'selected' : '' }}>
                            Key-in Date
                        </option>
                    </select>
                    <p class="mt-2 text-xs text-gray-500">
                        Tanggal yang dipakai untuk filter periode kontes.
                    </p>
                </div>

                {{-- Target --}}
                <div>
                    <label id="target_label" class="text-sm font-semibold text-gray-700">Target Total Qty</label>
                    <input type="number" name="target_unit" value="{{ old('target_unit') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: 25" min="1" required>
                    <p id="target_hint" class="mt-2 text-xs text-gray-500 hidden">
                        Untuk Qualifier 133, target ini adalah total qty selama periode (contoh: 25 qty total).
                    </p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Reward</label>
                    <input type="text" name="reward" value="{{ old('reward') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: Voucher / Bonus / Hadiah">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Banner (Opsional)</label>
                    <input type="file" name="banner" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-600
                                  file:mr-4 file:rounded-xl file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                    <p class="mt-2 text-xs text-gray-500">Jika diupload, akan disimpan ke storage (public).</p>
                </div>

                {{-- Rules 133 --}}
                <div id="rules_133" class="md:col-span-2 hidden">
                    <div class="rounded-2xl border bg-purple-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Rules Qualifier 133</div>
                                <div class="text-xs text-gray-600">
                                    Pemenang adalah HP yang memenuhi syarat di setiap bulan selama periode kontes.
                                    Semua hitungan memakai total qty (SUM qty).
                                </div>
                            </div>
                            <span
                                class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                133
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700">Minimal Qty Pribadi / Bulan</label>
                                <input type="number" name="monthly_min_personal_ns" min="1"
                                    value="{{ old('monthly_min_personal_ns', 3) }}"
                                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-2 text-xs text-gray-500">
                                    Contoh: 3 (tiap bulan minimal total qty pribadi = 3).
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-gray-700">Minimal Direct Active Partner /
                                    Bulan</label>
                                <input type="number" name="monthly_min_direct_active_partner" min="0"
                                    value="{{ old('monthly_min_direct_active_partner', 3) }}"
                                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-2 text-xs text-gray-500">
                                    Partner dianggap “active” jika minimal qty pribadinya/bulan memenuhi angka di kiri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Target Team --}}
            <div class="rounded-2xl border bg-gray-50 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Target Tim</div>
                        <div class="text-xs text-gray-600">
                            Peserta kontes akan otomatis mencakup HM terpilih + semua HP di bawah HM tersebut.
                        </div>
                    </div>

                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        {{ auth()->user()->getRoleNames()->first() }}
                    </span>
                </div>

                <div class="mt-4">
                    {{-- Sales Manager: otomatis semua downline --}}
                    @if ($isSM)
                        <div class="rounded-xl border bg-white px-4 py-3 text-sm text-gray-700">
                            Kamu adalah <span class="font-semibold">Sales Manager</span>.
                            Kontes akan otomatis berlaku untuk
                            <span class="font-semibold">semua Health Manager</span> di bawah kamu
                            dan seluruh Health Planner di bawah HM tersebut.
                        </div>
                        <input type="hidden" name="target_mode" value="all_downline">

                        {{-- Health Manager / Admin / Head Admin: pilih HM --}}
                    @elseif(
                        $isHM ||
                            auth()->user()->hasAnyRole(['Admin', 'Head Admin']))
                        <label class="text-sm font-semibold text-gray-700">Pilih Health Manager</label>
                        <select name="hm_ids[]" multiple
                            class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            @forelse(($healthManagers ?? []) as $hm)
                                <option value="{{ $hm->id }}"
                                    {{ collect(old('hm_ids', []))->contains($hm->id) ? 'selected' : '' }}>
                                    {{ $hm->name }} ({{ $hm->dst_code ?? '-' }})
                                </option>
                            @empty
                                <option disabled>Tidak ada Health Manager</option>
                            @endforelse
                        </select>
                        <p class="mt-2 text-xs text-gray-500">
                            Peserta kontes akan mencakup HM terpilih dan seluruh HP di bawahnya.
                        </p>

                        {{-- Role lain --}}
                    @else
                        <div class="rounded-xl border bg-white px-4 py-3 text-sm text-gray-700">
                            Kamu tidak memiliki akses untuk membuat kontes.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('contests.index') }}"
                    class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Batal
                </a>

                <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Simpan Kontes
                </button>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const typeEl = document.getElementById('contest_type');
            const rulesEl = document.getElementById('rules_133');
            const targetLabel = document.getElementById('target_label');
            const targetHint = document.getElementById('target_hint');

            function sync() {
                const type = typeEl?.value || 'leaderboard';

                if (type === 'qualifier') {
                    rulesEl?.classList.remove('hidden');
                    if (targetLabel) targetLabel.textContent = 'Target Total Qty (Periode)';
                    targetHint?.classList.remove('hidden');
                } else {
                    rulesEl?.classList.add('hidden');
                    if (targetLabel) targetLabel.textContent = 'Target Total Qty';
                    targetHint?.classList.add('hidden');
                }
            }

            typeEl?.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-dashboard-layout>
