<x-dashboard-layout>
    <div class="flex items-center justify-between gap-4">
        <div><h1 class="text-2xl font-semibold text-gray-900">{{ $customer->exists ? 'Edit Customer' : 'Tambah Customer' }}</h1>
            <p class="text-sm text-gray-500">Lengkapi data customer dan pemiliknya.</p></div>
        <a href="{{ route('customers.index') }}" class="rounded-xl border bg-white px-4 py-2 text-sm">Kembali</a>
    </div>
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" enctype="multipart/form-data" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="mt-6 max-w-4xl rounded-2xl border bg-white p-5 sm:p-6">
        @csrf
        @if ($customer->exists) @method('PUT') @endif
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            @foreach (['full_name' => ['Nama Customer', true], 'phone_number' => ['Nomor Telepon', false], 'address' => ['Alamat', true], 'religion' => ['Agama', false], 'unit_serial_number' => ['Nomor Seri Unit', false]] as $field => [$label, $required])
                <div>
                    <label for="{{ $field }}" class="text-sm font-medium text-gray-700">{{ $label }} {{ $required ? '*' : '(Opsional)' }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $customer->$field) }}" @required($required)
                        maxlength="{{ ['phone_number' => 30, 'religion' => 100, 'address' => 500][$field] ?? 255 }}"
                        class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            @endforeach
            <div>
                <label for="date_of_birth" class="text-sm font-medium text-gray-700">Tanggal Lahir (Opsional)</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                @hasanyrole('Admin|Head Admin')
                    <label for="health_planner_id" class="text-sm font-medium text-gray-700">HP / HM Pemilik *</label>
                    <select id="health_planner_id" name="health_planner_id" required class="mt-1 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih HP atau HM aktif</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string) old('health_planner_id', $customer->health_planner_id) === (string) $owner->id)>{{ $owner->full_name ?: $owner->name }} • ID: {{ $owner->dst_code ?: $owner->id }} • {{ $owner->hasRole('Health Manager') ? 'HM' : 'HP' }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="rounded-xl bg-blue-50 p-3 text-sm text-blue-900">Pemilik: <strong>{{ auth()->user()->full_name ?: auth()->user()->name }}</strong> • ID: {{ auth()->user()->dst_code ?: auth()->id() }}. Customer baru otomatis menjadi milik Anda.</div>
                @endhasanyrole
                <p class="mt-2 text-xs text-gray-500">Satu HP maksimal memiliki satu customer. HM dapat memiliki banyak customer.</p>
            </div>
            @foreach (['ktp' => 'KTP', 'unit_barcode' => 'Barcode Unit'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="text-sm font-medium text-gray-700">Upload {{ $label }} (Opsional)</label>
                    <input id="{{ $field }}" type="file" name="{{ $field }}" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 block w-full text-sm text-gray-600">
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG, WEBP, atau PDF. Maksimal 5 MB. Kosongkan untuk mempertahankan dokumen yang sudah ada.</p>
                    @if ($customer->exists && $customer->{$field . '_path'})
                        <a href="{{ route('customers.document', [$customer, $field]) }}" class="mt-2 inline-block text-sm text-blue-600 underline">Unduh {{ $label }} saat ini</a>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-6 flex justify-end"><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan Customer</button></div>
    </form>
</x-dashboard-layout>
