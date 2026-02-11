<x-dashboard-layout>
    <div class="p-4 md:p-6 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-semibold text-gray-900">Bulk Upload Products</h1>
                <p class="text-sm text-gray-500">Khusus product <b>Regular</b> (bukan bundle). Upload via CSV.</p>
            </div>

            <a href="{{ route('products.index', ['type' => 'regular']) }}"
               class="rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                ← Back
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border bg-white p-5 shadow-sm ring-1 ring-black/5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Download template CSV, isi data (1 SKU bisa beberapa baris untuk banyak harga), lalu upload.
                </div>
                <a href="{{ route('products.bulk.template') }}"
                   class="inline-flex items-center rounded-xl border bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Download Template CSV
                </a>
            </div>

            <form action="{{ route('products.bulk.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">File CSV</label>
                        <input type="file" name="file" accept=".csv,text/csv"
                               class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                        <p class="mt-1 text-xs text-gray-500">
                            Notes: Untuk banyak harga di 1 produk, duplikat baris dengan SKU yang sama.
                            <br>billing_type: <b>one_time</b> / <b>monthly</b>. monthly wajib duration_months.
                        </p>
                    </div>

                    <div class="md:col-span-1 flex items-end">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                            Upload & Proses
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if (session('bulk_failed'))
            @php($failed = session('bulk_failed'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <div class="font-semibold text-red-900">Gagal ({{ count($failed) }})</div>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-red-900">
                            <th class="py-2 pr-4">Row</th>
                            <th class="py-2 pr-4">SKU</th>
                            <th class="py-2 pr-4">Errors</th>
                        </tr>
                        </thead>
                        <tbody class="text-red-800">
                        @foreach ($failed as $f)
                            <tr class="border-t border-red-200">
                                <td class="py-2 pr-4">{{ $f['row'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $f['key'] ?? '-' }}</td>
                                <td class="py-2 pr-4">
                                    <ul class="list-disc pl-5">
                                        @foreach (($f['errors'] ?? []) as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (session('bulk_success'))
            @php($success = session('bulk_success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5">
                <div class="font-semibold text-green-900">Berhasil ({{ count($success) }})</div>

                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-green-900">
                            <th class="py-2 pr-4">SKU</th>
                            <th class="py-2 pr-4">Product</th>
                            <th class="py-2 pr-4">Prices</th>
                            <th class="py-2 pr-4">Rows</th>
                        </tr>
                        </thead>
                        <tbody class="text-green-900">
                        @foreach ($success as $s)
                            <tr class="border-t border-green-200">
                                <td class="py-2 pr-4">{{ $s['sku'] }}</td>
                                <td class="py-2 pr-4">{{ $s['product_name'] }}</td>
                                <td class="py-2 pr-4">{{ $s['prices_count'] }}</td>
                                <td class="py-2 pr-4 text-xs font-mono">{{ implode(',', $s['rows'] ?? []) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>
