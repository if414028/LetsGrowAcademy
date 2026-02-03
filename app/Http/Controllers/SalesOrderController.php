<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\ProductPrice;

class SalesOrderController extends Controller
{
    private array $paymentMethods = ['partial', 'outright'];
    private array $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];
    private array $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];

    public function index(Request $request)
    {
        $user = $request->user();

        $q = SalesOrder::query()->with(['customer', 'salesUser']);

        // ✅ Role-based scope
        if ($user->hasAnyRole(['Sales Manager', 'Health Manager'])) {
            // ambil semua bawahan (children) dari user ini
            $childIds = $user->childrenUsers()->pluck('users.id');

            // kalau tidak punya bawahan, hasilnya kosong
            $q->whereIn('sales_user_id', $childIds);
        } elseif (!$user->hasRole('Admin')) {
            // opsional: kalau selain Admin / Manager tidak boleh lihat sama sekali
            abort(403);
            // atau kalau mau tampil kosong aja:
            // $q->whereRaw('1=0');
        }

        // 🔎 search
        if ($request->filled('search')) {
            $search = $request->search;

            $q->where(function ($qq) use ($search) {
                $qq->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('salesUser', fn ($u) => $u->where('full_name', 'like', "%{$search}%"));
            });
        }

        // status
        if ($request->filled('status') && in_array($request->status, $this->statuses, true)) {
            $q->where('status', $request->status);
        }

        // ccp_status
        if ($request->filled('ccp_status') && in_array($request->ccp_status, $this->ccpStatuses, true)) {
            $q->where('ccp_status', $request->ccp_status);
        }

        $salesOrders = $q->latest('key_in_at')->paginate(10)->withQueryString();
        $activeStatus = $request->filled('status') ? $request->status : 'all';

        $statuses = $this->statuses;

        return view('sales-orders.index', compact('salesOrders', 'statuses', 'activeStatus'));
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer','salesUser','items.product','items.productPrice']);
        return view('sales-orders.show', compact('salesOrder'));
    }

    public function create()
    {
        $paymentMethods = $this->paymentMethods;
        $statuses = $this->statuses;
        $ccpStatuses = $this->ccpStatuses;

        // ✅ include model karena label di UI pakai model
        $products = Product::query()
        ->where('is_active', true)
        ->with(['prices' => function ($q) {
            $q->where('is_active', true)
            ->orderBy('billing_type')
            ->orderBy('duration_months');
        }])
        ->orderBy('product_name')
        ->get(['id','sku','product_name','model']);

        // maintain value saat validation error (Admin)
        $oldSalesUser = null;
        if (old('sales_user_id')) {
            $oldSalesUser = User::role('Health Planner')
                ->whereKey(old('sales_user_id'))
                ->first(['id', 'name', 'email', 'dst_code']);
        }


        return view('sales-orders.create', compact(
            'paymentMethods',
            'statuses',
            'ccpStatuses',
            'products',
            'oldSalesUser'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', 'unique:sales_orders,order_no'],

            // Admin wajib pilih; non-admin nullable (dipaksa jadi auth user)
            'sales_user_id' => [$authUser->hasRole('Admin') ? 'required' : 'nullable', 'exists:users,id'],

            // customer input
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],

            // order fields
            'key_in_at' => ['nullable', 'date'],
            'install_date' => [
                Rule::requiredIf(fn () => ($request->input('status') === 'dijadwalkan')),
                Rule::prohibitedIf(fn () => ($request->input('status') === 'menunggu verifikasi')),
                'nullable',
                'date',
            ],
            'is_recurring' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', Rule::in($this->paymentMethods)],
            'status' => ['required', Rule::in($this->statuses)],
            'ccp_status' => ['required', Rule::in($this->ccpStatuses)],

            // items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.product_price_id' => ['required', 'exists:product_prices,id'],

            'status_reason' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['dibatalkan', 'ditunda', 'gagal penelponan'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if ($authUser->hasRole('Admin')) {
            $isHealthPlanner = User::role('Health Planner')
                ->whereKey($validated['sales_user_id'])
                ->exists();

            if (!$isHealthPlanner) {
                return back()
                    ->withErrors(['sales_user_id' => 'Sales User harus memiliki role Health Planner.'])
                    ->withInput();
            }
        }

        foreach ($request->input('items', []) as $i => $row) {
            $pid = $row['product_id'] ?? null;
            $priceId = $row['product_price_id'] ?? null;

            if ($pid && $priceId) {
                $ok = ProductPrice::query()
                    ->where('id', $priceId)
                    ->where('product_id', $pid)
                    ->exists();

                if (!$ok) {
                    return back()
                        ->withErrors(["items.$i.product_price_id" => "Price tidak sesuai dengan product yang dipilih."])
                        ->withInput();
                }
            }
        }


        // normalize status_reason
        if (!in_array($validated['status'], ['dibatalkan', 'ditunda', 'gagal penelponan'], true)) {
            $validated['status_reason'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser) {
            $salesUserId = $authUser->hasRole('Admin')
                ? (int) $validated['sales_user_id']
                : (int) $authUser->id;

            // CUSTOMER: pilih existing / create baru
            $customerId = $this->resolveCustomerId($validated);

            // install_date: simplify by status
            $installDate = null;
            if (($validated['status'] ?? null) === 'dijadwalkan') {
                $installDate = $validated['install_date']; // required
            }

            $so = SalesOrder::create([
                'order_no' => $validated['order_no'],
                'sales_user_id' => $salesUserId,
                'customer_id' => $customerId,
                'key_in_at' => $validated['key_in_at'] ?? now(),
                'install_date' => $installDate,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'payment_method' => $validated['payment_method'] ?? null,
                'status' => $validated['status'],
                'ccp_status' => $validated['ccp_status'],
                'status_reason' => $validated['status_reason'], // ✅ BUG FIX: dulu tidak disimpan
            ]);

            // ITEMS: validated sudah aman
            $itemsPayload = collect($validated['items'])
                ->map(fn ($row) => [
                    'product_id' => (int) $row['product_id'],
                    'product_price_id' => (int) $row['product_price_id'], // ✅ new
                    'qty' => (int) $row['qty'],
                ])
                ->values()
                ->all();

            $so->items()->createMany($itemsPayload);

            return redirect()
                ->route('sales-orders.index')
                ->with('success', 'Sales order berhasil dibuat.');
        });
    }

    public function searchSalesUsers(Request $request)
    {
        abort_unless(Auth::check() && Auth::user()->hasRole('Admin'), 403);

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $users = User::query()
            ->role('Health Planner')
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                ->orWhere('full_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'full_name', 'email', 'dst_code'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'label' => $u->name . ($u->email ? " ({$u->email})" : ''),
                'dst_code' => $u->dst_code,
            ]);

        return response()->json($users);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $paymentMethods = $this->paymentMethods;
        $statuses = $this->statuses;
        $ccpStatuses = $this->ccpStatuses;

        $salesOrder->load(['customer', 'salesUser', 'items.productPrice']);

        // Ambil semua price_id yang sudah kepilih di order ini
        $selectedPriceIds = $salesOrder->items->pluck('product_price_id')->filter()->unique()->values();

        $products = Product::query()
            ->where('is_active', true)
            ->with(['prices' => function ($q) use ($selectedPriceIds) {
                $q->where(function ($qq) use ($selectedPriceIds) {
                    $qq->where('is_active', true)
                    ->orWhereIn('id', $selectedPriceIds); // ✅ supaya selected price tetap muncul
                })
                ->orderBy('billing_type')
                ->orderBy('duration_months');
            }])
            ->orderBy('product_name')
            ->get(['id','sku','product_name','model']);

        $oldSalesUser = $salesOrder->salesUser;
        if (old('sales_user_id')) {
            $oldSalesUser = User::find(old('sales_user_id'), ['id', 'name', 'email', 'dst_code']);
        }

        return view('sales-orders.edit', compact(
            'salesOrder',
            'paymentMethods',
            'statuses',
            'ccpStatuses',
            'products',
            'oldSalesUser'
        ));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', Rule::unique('sales_orders', 'order_no')->ignore($salesOrder->id)],

            'sales_user_id' => [$authUser->hasRole('Admin') ? 'required' : 'nullable', 'exists:users,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],

            'key_in_at' => ['nullable', 'date'],
            'install_date' => [
                Rule::requiredIf(fn () => ($request->input('status') === 'dijadwalkan')),
                Rule::prohibitedIf(fn () => ($request->input('status') === 'menunggu verifikasi')),
                'nullable',
                'date',
            ],
            'is_recurring' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', Rule::in($this->paymentMethods)],
            'status' => ['required', Rule::in($this->statuses)],
            'ccp_status' => ['required', Rule::in($this->ccpStatuses)],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.product_price_id' => ['required', 'exists:product_prices,id'],

            'status_reason' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['dibatalkan', 'ditunda', 'gagal penelponan'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        foreach ($request->input('items', []) as $i => $row) {
            $pid = $row['product_id'] ?? null;
            $priceId = $row['product_price_id'] ?? null;

            if ($pid && $priceId) {
                $ok = ProductPrice::query()
                    ->where('id', $priceId)
                    ->where('product_id', $pid)
                    ->exists();

                if (!$ok) {
                    return back()
                        ->withErrors(["items.$i.product_price_id" => "Price tidak sesuai dengan product yang dipilih."])
                        ->withInput();
                }
            }
        }

        if (!in_array($validated['status'], ['dibatalkan', 'ditunda', 'gagal penelponan'], true)) {
            $validated['status_reason'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser, $salesOrder) {
            $salesUserId = $authUser->hasRole('Admin')
                ? (int) $validated['sales_user_id']
                : (int) $authUser->id;

            $customerId = $this->resolveCustomerId($validated, true);

            // install_date: simple by status
            $installDate = null;
            if (($validated['status'] ?? null) === 'dijadwalkan') {
                $installDate = $validated['install_date']; // required
            }

            $salesOrder->update([
                'order_no' => $validated['order_no'],
                'sales_user_id' => $salesUserId,
                'customer_id' => $customerId,
                'key_in_at' => $validated['key_in_at'] ?? $salesOrder->key_in_at ?? now(),
                'install_date' => $installDate,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'payment_method' => $validated['payment_method'] ?? null,
                'status' => $validated['status'],
                'ccp_status' => $validated['ccp_status'],
                'status_reason' => $validated['status_reason'],
            ]);

            $itemsPayload = collect($validated['items'])
                ->map(fn ($row) => [
                    'product_id' => (int) $row['product_id'],
                    'product_price_id' => (int) $row['product_price_id'], // ✅ new
                    'qty' => (int) $row['qty'],
                ])
                ->values()
                ->all();

            $salesOrder->items()->delete();
            $salesOrder->items()->createMany($itemsPayload);

            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('success', 'Sales order berhasil diupdate.');
        });
    }

    /**
     * Resolve customer_id.
     * - kalau customer_id ada => pakai itu, optional update fields kalau $allowUpdateExisting = true
     * - kalau tidak ada => cari existing by (lower(full_name), phone optional) lalu create jika belum ada
     */
    private function resolveCustomerId(array $validated, bool $allowUpdateExisting = false): int
    {
        $customerId = $validated['customer_id'] ?? null;

        $name = trim($validated['customer_name']);
        $phone = trim((string) ($validated['customer_phone'] ?? ''));

        if ($customerId) {
            if ($allowUpdateExisting) {
                Customer::whereKey($customerId)->update([
                    'full_name' => $name,
                    'phone_number' => $validated['customer_phone'] ?? null,
                    'address' => $validated['customer_address'] ?? null,
                ]);
            }
            return (int) $customerId;
        }

        $existing = Customer::query()
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($name)])
            ->when($phone !== '', fn ($q) => $q->where('phone_number', $phone))
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $customer = Customer::create([
            'full_name' => $name,
            'phone_number' => $validated['customer_phone'] ?? null,
            'address' => $validated['customer_address'] ?? null,
        ]);

        return (int) $customer->id;
    }
}