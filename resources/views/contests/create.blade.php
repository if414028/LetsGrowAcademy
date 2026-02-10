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

                <div>
                    <label class="text-sm font-semibold text-gray-700">Target Unit</label>
                    <input type="number" name="target_unit" value="{{ old('target_unit') }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Contoh: 10" min="1" required>
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
</x-dashboard-layout>
