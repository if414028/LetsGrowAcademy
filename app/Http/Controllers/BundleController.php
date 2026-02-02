<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $bundles = Product::query()
            ->where('type', 'bundle')
            ->with(['primaryPrice'])
            ->when($q, fn($query) => $query->where(function ($qq) use ($q) {
                $qq->where('sku', 'like', "%{$q}%")
                   ->orWhere('product_name', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bundles.index', compact('bundles', 'q'));
    }

    public function create()
    {
        // hanya regular products yang boleh jadi isi bundling
        $products = Product::query()
            ->where('type', 'regular')
            ->orderBy('product_name')
            ->get(['id','sku','product_name','model']);

        return view('bundles.create', compact('products'));
    }

    public function store(Request $request)
    {
        // 1️⃣ validasi dasar
        $validated = $this->validateBundle($request);

        // 2️⃣ ambil product_id dari items
        $ids = collect($validated['items'])
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        // 3️⃣ cek: item bundle HARUS product regular
        $invalid = Product::whereIn('id', $ids)
            ->where('type', '!=', 'regular')
            ->exists();

        if ($invalid) {
            return back()
                ->withErrors(['items' => 'Isi bundling hanya boleh menggunakan product reguler.'])
                ->withInput();
        }

        // (opsional tapi bagus) cegah duplikat product
        if ($ids->count() !== collect($validated['items'])->count()) {
            return back()
                ->withErrors(['items' => 'Product di dalam bundling tidak boleh duplikat.'])
                ->withInput();
        }

        // 4️⃣ simpan ke DB
        DB::transaction(function () use ($request, $validated) {

            $data = collect($validated)->except(['prices','items'])->all();
            $data['type'] = 'bundle';
            $data['is_active'] = $request->boolean('is_active');

            if ($request->hasFile('product_image')) {
                $data['product_image'] = $request->file('product_image')
                    ->store('products', 'public');
            }

            $bundle = Product::create($data);

            // simpan harga
            $bundle->prices()->createMany(
                $this->mapPrices($validated['prices'])
            );

            // attach item bundling
            $bundle->bundleItems()->attach(
                $this->mapItemsToAttach($validated['items'])
            );
        });

        return redirect()
            ->route('products.index', ['type' => 'bundle'])
            ->with('success', 'Bundling berhasil ditambahkan.');
    }

    public function show(Product $bundle)
    {
        $this->ensureBundle($bundle);

        $bundle->load(['prices', 'bundleItems']);

        return view('bundles.show', compact('bundle'));
    }

    public function edit(Product $bundle)
    {
        $this->ensureBundle($bundle);

        $bundle->load(['prices', 'bundleItems']);

        $products = Product::query()
            ->where('type', 'regular')
            ->orderBy('product_name')
            ->get(['id','sku','product_name','model']);

        $existingPrices = $bundle->prices->map(fn($p) => [
            'id' => $p->id,
            'label' => $p->label,
            'billing_type' => $p->billing_type,
            'duration_months' => $p->duration_months,
            'amount' => (string) $p->amount,
            'is_active' => (bool) $p->is_active,
            'sort_order' => (int) $p->sort_order,
        ])->values();

        $existingItems = $bundle->bundleItems->map(function ($p) {
            return [
                'product_id' => $p->id,
                'qty' => (int) $p->pivot->qty,
                'sort_order' => (int) $p->pivot->sort_order,
            ];
        })->sortBy('sort_order')->values();

        return view('bundles.edit', compact('bundle', 'products', 'existingPrices', 'existingItems'));
    }

    public function update(Request $request, Product $bundle)
    {
        $this->ensureBundle($bundle);

        // 1) validate
        $validated = $this->validateBundle($request, $bundle->id);

        // 2) business rules (SEBELUM transaction)
        $itemIds = collect($validated['items'])
            ->pluck('product_id')
            ->filter()
            ->values();

        // 2a) cegah duplikat product di bundling
        if ($itemIds->count() !== $itemIds->unique()->count()) {
            return back()
                ->withErrors(['items' => 'Product di dalam bundling tidak boleh duplikat.'])
                ->withInput();
        }

        // 2b) isi bundling hanya boleh product regular
        $invalid = Product::whereIn('id', $itemIds->unique())
            ->where('type', '!=', 'regular')
            ->exists();

        if ($invalid) {
            return back()
                ->withErrors(['items' => 'Isi bundling hanya boleh menggunakan product reguler.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $bundle, $validated) {

            // 3) update bundle basic fields
            $data = collect($validated)->except(['prices','items'])->all();
            $data['type'] = 'bundle';
            $data['is_active'] = $request->boolean('is_active');

            if ($request->hasFile('product_image')) {
                if ($bundle->product_image && Storage::disk('public')->exists($bundle->product_image)) {
                    Storage::disk('public')->delete($bundle->product_image);
                }
                $data['product_image'] = $request->file('product_image')->store('products', 'public');
            } else {
                unset($data['product_image']);
            }

            $bundle->update($data);

            // 4) sync prices
            $bundle->load('prices');
            $existingIds = $bundle->prices->pluck('id')->all();

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

            // 4a) delete prices yang hilang dari payload
            $idsToDelete = array_values(array_diff($existingIds, $incomingIds));
            if (!empty($idsToDelete)) {
                $bundle->prices()->whereIn('id', $idsToDelete)->delete();
            }

            // 4b) update existing + create new (AMAN: pastikan id milik bundle)
            foreach ($incoming as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id) {
                    $bundle->prices()->where('id', $id)->update($row);
                } else {
                    $bundle->prices()->create($row);
                }
            }

            // 5) sync bundle items pivot
            // mapItemsToAttach() harus return format:
            // [ product_id => ['qty'=>..,'sort_order'=>..], ... ]
            $bundle->bundleItems()->sync(
                $this->mapItemsToAttach($validated['items'])
            );
        });

        return redirect()
            ->route('products.index', ['type' => 'bundle'])
            ->with('success', 'Bundling berhasil diupdate.');
    }

    // =========================
    // Helpers
    // =========================

    private function ensureBundle(Product $product): void
    {
        abort_unless($product->type === 'bundle', 404);
    }

    private function validateBundle(Request $request, ?int $bundleId = null): array
    {
        $rules = [
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku' . ($bundleId ? ',' . $bundleId : '')],
            'product_name' => ['required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],

            // items bundling
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','integer','exists:products,id'],
            'items.*.qty' => ['required','integer','min:1'],

            // prices (boleh monthly)
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.id' => ['nullable', 'integer'],
            'prices.*.label' => ['required', 'string', 'max:100'],
            'prices.*.billing_type' => ['required', 'in:one_time,monthly'],
            'prices.*.duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'prices.*.amount' => ['required', 'numeric', 'min:0'],
            'prices.*.is_active' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);

        // extra rule: monthly wajib duration_months
        foreach ($validated['prices'] as $i => $p) {
            if (($p['billing_type'] ?? null) === 'monthly' && empty($p['duration_months'])) {
                return back()
                    ->withErrors(["prices.$i.duration_months" => "Durasi wajib diisi untuk tipe Monthly."])
                    ->withInput()
                    ->throwResponse();
            }
        }

        // optional guard: pastikan item yang dipilih bukan bundle
        $ids = collect($validated['items'])->pluck('product_id')->unique()->values();
        $bad = Product::whereIn('id', $ids)->where('type', '!=', 'regular')->count();
        if ($bad > 0) {
            return back()
                ->withErrors(['items' => 'Isi bundling hanya boleh product regular (bukan bundling).'])
                ->withInput()
                ->throwResponse();
        }

        return $validated;
    }

    private function mapPrices(array $prices): array
    {
        return collect($prices)->values()->map(function ($p, $i) {
            return [
                'label' => $p['label'],
                'billing_type' => $p['billing_type'],
                'duration_months' => $p['billing_type'] === 'monthly' ? (int) $p['duration_months'] : null,
                'amount' => $p['amount'],
                'is_active' => (bool) ($p['is_active'] ?? true),
                'sort_order' => $i,
            ];
        })->all();
    }

    private function mapItemsToAttach(array $items): array
    {
        return collect($items)
            ->values()
            ->mapWithKeys(function ($row, $i) {
                return [
                    (int) $row['product_id'] => [
                        'qty' => (int) ($row['qty'] ?? 1),
                        'sort_order' => $i,
                    ],
                ];
            })
            ->all();
    }
                    
}
