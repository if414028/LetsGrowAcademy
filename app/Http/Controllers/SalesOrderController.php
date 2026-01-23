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

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];

        $q = SalesOrder::query()
            ->with(['customer', 'salesUser']);

        if ($request->filled('search')) {
            $search = $request->search;

            $q->where(function ($qq) use ($search) {
                $qq->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('salesUser', fn ($u) => $u->where('full_name', 'like', "%{$search}%"));
            });
        }

        // ✅ status tab filter (hanya kalau status valid)
        if ($request->filled('status') && in_array($request->status, $statuses, true)) {
            $q->where('status', $request->status);
        }

        if ($request->filled('ccp_status')) {
            $q->where('ccp_status', $request->ccp_status);
        }

        $salesOrders = $q->latest('key_in_at')->paginate(10)->withQueryString();

        $activeStatus = $request->filled('status') ? $request->status : 'all';

        return view('sales-orders.index', compact('salesOrders', 'statuses', 'activeStatus'));
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load([
            'customer',
            'salesUser',
            'items.product',
        ]);

        return view('sales-orders.show', compact('salesOrder'));
    }

    public function create()
    {
        $paymentMethods = ['partial', 'outright'];
        $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];
        $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get(['id', 'sku', 'product_name']);

        // untuk maintain value saat validation error (Admin)
        $oldSalesUser = null;
        if (old('sales_user_id')) {
            $oldSalesUser = User::find(old('sales_user_id'), ['id','name','email']);
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
        $paymentMethods = ['partial', 'outright'];
        $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];
        $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', 'unique:sales_orders,order_no'],

            // Admin wajib pilih; non-admin boleh nullable (akan dipaksa jadi auth user)
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
            'payment_method' => ['nullable', Rule::in($paymentMethods)],
            'status' => ['required', Rule::in($statuses)],
            'ccp_status' => ['required', Rule::in($ccpStatuses)],

            // items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'status_reason' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['dibatalkan','ditunda','gagal penelponan'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if (!in_array($validated['status'], ['dibatalkan','ditunda','gagal penelponan'], true)) {
            $validated['status_reason'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser) {

        // sales_user_id: admin harus pakai pilihan; non-admin selalu auth user
        $salesUserId = $authUser->hasRole('Admin')
            ? (int) $validated['sales_user_id']
            : (int) $authUser->id;

            /**
             * CUSTOMER
             * - kalau user pilih dari dropdown => customer_id ada
             * - kalau tidak => insert baru
             */
            $customerId = $validated['customer_id'] ?? null;

            if (!$customerId) {
                $name = trim($validated['customer_name']);
                $phone = trim((string) ($validated['customer_phone'] ?? ''));

                // exact match (nama), dan kalau phone diisi ikut dipakai untuk memperkecil duplikat
                $existing = Customer::query()
                    ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($name)])
                    ->when($phone !== '', fn ($q) => $q->where('phone_number', $phone))
                    ->first();

                if ($existing) {
                    $customerId = $existing->id;
                } else {
                    $customer = Customer::create([
                        'full_name' => $name,
                        'phone_number' => $validated['customer_phone'] ?? null,
                        'address' => $validated['customer_address'] ?? null,
                    ]);
                    $customerId = $customer->id;
                }
            }

            /**
             * SALES ORDER
             */
            $installDate = null;
            if (($validated['status'] ?? null) === 'dijadwalkan') {
                $installDate = $validated['install_date']; // wajib sudah divalidasi
            } elseif (($validated['status'] ?? null) === 'menunggu verifikasi') {
                $installDate = null; // wajib kosong
            } else {
                // dibatalkan/ditunda/gagal penelponan/selesai => simpan kalau ada, tidak wajib
                $installDate = $validated['install_date'] ?? null;
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
            ]);

            /**
             * ITEMS
             */
            $itemsPayload = collect($validated['items'])
                ->filter(fn ($row) => !empty($row['product_id']) && !empty($row['qty']))
                ->map(fn ($row) => [
                    'product_id' => (int) $row['product_id'],
                    'qty' => (int) $row['qty'],
                ])
                ->values()
                ->all();

            // butuh relasi items() di SalesOrder model
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
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                ->orWhere('full_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id','name','full_name','email','dst_code'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'label' => $u->name . ($u->email ? " ({$u->email})" : ''),
                'dst_code' => $u->dst_code,
            ]);

        return response()->json($users);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $paymentMethods = ['partial', 'outright'];
        $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];
        $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];

        $salesOrder->load(['customer', 'salesUser', 'items.product']);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get(['id', 'sku', 'product_name']);

        // untuk maintain value saat validation error (Admin)
        $oldSalesUser = $salesOrder->salesUser;
        if (old('sales_user_id')) {
            $oldSalesUser = User::find(old('sales_user_id'), ['id','name','email','dst_code']);
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
        $paymentMethods = ['partial', 'outright'];
        $statuses = ['menunggu verifikasi', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'selesai'];
        $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', Rule::unique('sales_orders', 'order_no')->ignore($salesOrder->id)],

            // Admin boleh pilih; non-admin nullable (akan dipaksa jadi auth user)
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
            'payment_method' => ['nullable', Rule::in($paymentMethods)],
            'status' => ['required', Rule::in($statuses)],
            'ccp_status' => ['required', Rule::in($ccpStatuses)],

            // items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'status_reason' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['dibatalkan','ditunda','gagal penelponan'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if (!in_array($validated['status'], ['dibatalkan', 'ditunda', 'gagal penelponan'], true)) {
            $validated['status_reason'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser, $salesOrder) {

            // sales_user_id: admin harus pakai pilihan; non-admin selalu auth user
            $salesUserId = $authUser->hasRole('Admin')
                ? (int) $validated['sales_user_id']
                : (int) $authUser->id;

            /**
             * CUSTOMER
             */
            $customerId = $validated['customer_id'] ?? null;

            if ($customerId) {
                // update data customer yang dipilih (biar input edit kepakai)
                Customer::whereKey($customerId)->update([
                    'full_name' => trim($validated['customer_name']),
                    'phone_number' => $validated['customer_phone'] ?? null,
                    'address' => $validated['customer_address'] ?? null,
                ]);
            } else {
                $name = trim($validated['customer_name']);
                $phone = trim((string) ($validated['customer_phone'] ?? ''));

                $existing = Customer::query()
                    ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($name)])
                    ->when($phone !== '', fn ($q) => $q->where('phone_number', $phone))
                    ->first();

                if ($existing) {
                    $customerId = $existing->id;
                } else {
                    $customer = Customer::create([
                        'full_name' => $name,
                        'phone_number' => $validated['customer_phone'] ?? null,
                        'address' => $validated['customer_address'] ?? null,
                    ]);
                    $customerId = $customer->id;
                }
            }

            /**
             * SALES ORDER
             */
            $installDate = $salesOrder->install_date; // default: keep existing

            if (($validated['status'] ?? null) === 'dijadwalkan') {
                // wajib ada (validated)
                $installDate = $validated['install_date'];
            } elseif (($validated['status'] ?? null) === 'menunggu verifikasi') {
                // harus dihapus
                $installDate = null;
            } else {
                // dibatalkan/ditunda/gagal penelponan/selesai
                // kalau user ngirim install_date (misalnya mau edit), update; kalau tidak, keep existing
                if (array_key_exists('install_date', $validated)) {
                    $installDate = $validated['install_date'] ?? $installDate;
                }
            }


            $salesOrder->update([
                // tetap validate, tapi biasanya readonly
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

            /**
             * ITEMS (replace all)
             */
            $itemsPayload = collect($validated['items'])
                ->filter(fn ($row) => !empty($row['product_id']) && !empty($row['qty']))
                ->map(fn ($row) => [
                    'product_id' => (int) $row['product_id'],
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
}