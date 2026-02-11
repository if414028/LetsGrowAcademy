<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $type = $request->string('type')->toString() ?: 'regular'; // default tab

        if (!in_array($type, ['regular', 'bundle'], true)) {
            $type = 'regular';
        }

        $products = Product::query()
            ->with(['displayPrice'])
            ->where('type', $type)
            ->when($q, fn($query) => $query->where(function ($qq) use ($q) {
                $qq->where('sku', 'like', "%{$q}%")
                    ->orWhere('product_name', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // counts untuk label tab
        $countRegular = Product::where('type', 'regular')->count();
        $countBundle  = Product::where('type', 'bundle')->count();

        return view('products.index', compact('products', 'q', 'type', 'countRegular', 'countBundle'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku'],
            'product_name' => ['required', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:100'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],

            // harga
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.label' => ['required', 'string', 'max:100'],
            'prices.*.billing_type' => ['required', 'in:one_time,monthly'],
            'prices.*.duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {

            // 1️⃣ Simpan product (tanpa prices)
            $productData = collect($validated)->except('prices')->all();
            $productData['is_active'] = $request->boolean('is_active');

            if ($request->hasFile('product_image')) {
                $productData['product_image'] = $request->file('product_image')
                    ->store('products', 'public');
            }

            $product = \App\Models\Product::create($productData);

            // 2️⃣ Simpan harga-harga
            $rows = collect($validated['prices'])
                ->values()
                ->map(function ($p, $i) {
                    return [
                        'label' => $p['label'],
                        'billing_type' => $p['billing_type'],
                        'duration_months' =>
                        $p['billing_type'] === 'monthly'
                            ? (int) $p['duration_months']
                            : null,
                        'amount' => $p['amount'],
                        'is_active' => (bool) ($p['is_active'] ?? true),
                        'sort_order' => $i,
                    ];
                })
                ->all();

            $product->prices()->createMany($rows);
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('prices');
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load('prices');

        $existingPrices = $product->prices->map(function ($p) {
            return [
                'id' => $p->id,
                'label' => $p->label,
                'billing_type' => $p->billing_type,
                'duration_months' => $p->duration_months,
                'amount' => (string) $p->amount,
                'is_active' => (bool) $p->is_active,
                'sort_order' => $p->sort_order,
            ];
        })->values();

        return view('products.edit', compact('product', 'existingPrices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,' . $product->id],
            'product_name' => ['required', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:100'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],

            // prices array
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.id' => ['nullable', 'integer'],
            'prices.*.label' => ['required', 'string', 'max:100'],
            'prices.*.billing_type' => ['required', 'in:one_time,monthly'],
            'prices.*.duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.is_active' => ['nullable', 'boolean'],
        ]);

        // extra rule: monthly wajib duration_months
        foreach ($validated['prices'] as $i => $p) {
            if (($p['billing_type'] ?? null) === 'monthly' && empty($p['duration_months'])) {
                return back()
                    ->withErrors(["prices.$i.duration_months" => "Durasi wajib diisi untuk tipe Monthly."])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $product, $validated) {
            // 1) update data product
            $productData = collect($validated)->except('prices')->all();
            $productData['is_active'] = $request->boolean('is_active');

            // Replace image jika upload baru
            if ($request->hasFile('product_image')) {
                if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
                    Storage::disk('public')->delete($product->product_image);
                }
                $productData['product_image'] = $request->file('product_image')->store('products', 'public');
            } else {
                unset($productData['product_image']);
            }

            $product->update($productData);

            // 2) sync prices
            $product->load('prices');
            $existingIds = $product->prices->pluck('id')->all();

            $incoming = collect($validated['prices'])->values()->map(function ($p, $i) {
                return [
                    'id' => $p['id'] ?? null,
                    'label' => $p['label'],
                    'billing_type' => $p['billing_type'],
                    'duration_months' => $p['billing_type'] === 'monthly' ? (int) $p['duration_months'] : null,
                    'amount' => $p['amount'],
                    'is_active' => (bool) ($p['is_active'] ?? true),
                    'sort_order' => $i,
                ];
            });

            $incomingIds = $incoming->pluck('id')->filter()->map(fn($v) => (int) $v)->all();

            // 2a) delete yang tidak ada di payload
            $idsToDelete = array_values(array_diff($existingIds, $incomingIds));
            if (!empty($idsToDelete)) {
                $product->prices()->whereIn('id', $idsToDelete)->delete();
            }

            // 2b) update existing + create new
            foreach ($incoming as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id) {
                    // keamanan: pastikan milik product ini
                    $product->prices()->where('id', $id)->update($row);
                } else {
                    $product->prices()->create($row);
                }
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }

    public function bulkUploadForm()
    {
        return view('products.bulk-upload');
    }

    public function bulkUploadTemplate()
    {
        $lines = [
            // NOTE: prices untuk 1 produk diwakili 1 baris.
            // Kalau mau 2 harga untuk 1 SKU, duplikat baris sku yang sama (dengan label/billing_type berbeda).
            "sku,product_name,model,description,is_active,price_label,billing_type,duration_months,amount,price_is_active",
            "AP-1018F,BREEZE,AP-1018F,,1,One Time,one_time,,8400000,1",
            "AP-1018F,BREEZE,AP-1018F,,1,Monthly 12,monthly,12,350000,1",
        ];

        $csv = implode("\n", $lines) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products-regular-bulk-template.csv"',
        ]);
    }

    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'File tidak bisa dibaca.']);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['file' => 'CSV kosong / header tidak ditemukan.']);
        }

        $header = array_map(fn($h) => trim(mb_strtolower((string) $h)), $header);

        $requiredHeaders = [
            'sku',
            'product_name',
            'model',
            'is_active',
            'price_label',
            'billing_type',
            'duration_months',
            'amount',
            'price_is_active'
        ];

        foreach ($requiredHeaders as $rh) {
            if (!in_array($rh, $header, true)) {
                fclose($handle);
                return back()->withErrors(['file' => "Header wajib tidak ada: {$rh}"]);
            }
        }

        $rawRows = [];
        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($data, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            $row['_row'] = $rowNumber;

            $rawRows[] = $row;
        }
        fclose($handle);

        if (count($rawRows) === 0) {
            return back()->withErrors(['file' => 'Tidak ada data yang bisa diproses (selain header).']);
        }

        /**
         * Group per SKU:
         * - 1 SKU bisa punya banyak price rows
         */
        $groups = collect($rawRows)->groupBy(fn($r) => mb_strtoupper($r['sku'] ?? ''));

        $success = [];
        $failed = [];

        // cek sku existing sekali saja
        $allSkus = $groups->keys()->filter()->values()->all();
        $existingSkus = Product::query()
            ->whereIn(DB::raw('UPPER(sku)'), $allSkus)
            ->pluck('sku')
            ->map(fn($v) => mb_strtoupper($v))
            ->all();
        $existingSkus = array_flip($existingSkus);

        foreach ($groups as $skuKey => $rows) {
            $first = $rows->first();
            $rowIdx = $first['_row'] ?? null;

            if (!$skuKey) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => '(empty sku)',
                    'errors' => ['SKU wajib diisi.'],
                ];
                continue;
            }

            if (isset($existingSkus[$skuKey])) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => $skuKey,
                    'errors' => ['SKU sudah terdaftar.'],
                ];
                continue;
            }

            // Validasi basic product pakai baris pertama (asumsi sama untuk sku yang sama)
            $basic = [
                'sku' => $first['sku'] ?? null,
                'product_name' => $first['product_name'] ?? null,
                'model' => $first['model'] ?? null,
                'description' => $first['description'] ?? null,
                'is_active' => $first['is_active'] ?? null,
            ];

            $vBasic = Validator::make($basic, [
                'sku' => ['required', 'string', 'max:50'],
                'product_name' => ['required', 'string', 'max:150'],
                'model' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'is_active' => ['nullable'],
            ]);

            if ($vBasic->fails()) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => $skuKey,
                    'errors' => $vBasic->errors()->all(),
                ];
                continue;
            }

            // Validasi prices per row
            $priceRows = [];
            $priceErrors = [];

            foreach ($rows as $r) {
                $pv = Validator::make($r, [
                    'price_label' => ['required', 'string', 'max:100'],
                    'billing_type' => ['required', 'in:one_time,monthly'],
                    'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
                    'amount' => ['required', 'numeric', 'min:0'],
                    'price_is_active' => ['nullable'],
                ]);

                if ($pv->fails()) {
                    $priceErrors[] = "Row {$r['_row']}: " . implode(' | ', $pv->errors()->all());
                    continue;
                }

                if (($r['billing_type'] ?? null) === 'monthly' && empty($r['duration_months'])) {
                    $priceErrors[] = "Row {$r['_row']}: duration_months wajib untuk billing_type monthly.";
                    continue;
                }

                $priceRows[] = [
                    'label' => $r['price_label'],
                    'billing_type' => $r['billing_type'],
                    'duration_months' => ($r['billing_type'] === 'monthly') ? (int) $r['duration_months'] : null,
                    'amount' => $r['amount'],
                    'is_active' => filter_var($r['price_is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                ];
            }

            if (!empty($priceErrors)) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => $skuKey,
                    'errors' => $priceErrors,
                ];
                continue;
            }

            if (count($priceRows) < 1) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => $skuKey,
                    'errors' => ['Minimal 1 price wajib ada untuk tiap SKU.'],
                ];
                continue;
            }

            // Simpan
            try {
                DB::transaction(function () use ($basic, $priceRows, $skuKey, &$success, $rows) {
                    $product = Product::create([
                        'sku' => $basic['sku'],
                        'product_name' => $basic['product_name'],
                        'model' => $basic['model'],
                        'description' => $basic['description'] ?: null,
                        'type' => 'regular', // DIPAKSA REGULAR
                        'is_active' => filter_var($basic['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                        'product_image' => null,
                    ]);

                    $rowsToCreate = collect($priceRows)->values()->map(function ($p, $i) {
                        return [
                            'label' => $p['label'],
                            'billing_type' => $p['billing_type'],
                            'duration_months' => $p['duration_months'],
                            'amount' => $p['amount'],
                            'is_active' => (bool) $p['is_active'],
                            'sort_order' => $i,
                        ];
                    })->all();

                    $product->prices()->createMany($rowsToCreate);

                    $success[] = [
                        'sku' => $skuKey,
                        'product_name' => $product->product_name,
                        'prices_count' => count($rowsToCreate),
                        'rows' => $rows->pluck('_row')->values()->all(),
                    ];
                });
            } catch (\Throwable $e) {
                $failed[] = [
                    'row' => $rowIdx,
                    'key' => $skuKey,
                    'errors' => ["Gagal simpan: " . $e->getMessage()],
                ];
            }
        }

        return back()->with([
            'bulk_success' => $success,
            'bulk_failed' => $failed,
        ]);
    }
}
