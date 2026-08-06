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
use App\Models\UserHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalesOrderController extends Controller
{
    private array $paymentMethods = [
        'partial'  => 'CC',
        'outright' => 'POA',
    ];

    private array $statuses = ['menunggu verifikasi', 'menunggu jadwal', 'dijadwalkan', 'dibatalkan', 'ditunda', 'gagal penelponan', 'tinjau ulang', 'selesai'];
    private array $ccpStatuses = ['menunggu pengecekan', 'dibatalkan', 'ditolak', 'disetujui'];
    private array $customerTypes = ['individu', 'corporate'];

    public function index(Request $request)
    {
        $user = $request->user();
        $canFilterHealthManager = $user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']);
        $healthManagerOptions = $this->healthManagerFilterOptionsFor($user);
        [$q, $selectedStatuses, $dateFilterBy] = $this->filteredSalesOrdersQuery(
            $request,
            $canFilterHealthManager,
            $healthManagerOptions
        );

        $salesOrders = $q->latest('key_in_at')->paginate(10)->withQueryString();
        $statuses = $this->statuses;
        $customerTypes = $this->customerTypes;
        $dateFilterOptions = [
            'key_in_at' => 'Berdasarkan Key In',
            'ccp_approved_at' => 'Berdasarkan CCP Approved At',
            'install_date' => 'Berdasarkan Tanggal Instalasi',
        ];

        return view('sales-orders.index', compact(
            'salesOrders',
            'statuses',
            'selectedStatuses',
            'customerTypes',
            'dateFilterBy',
            'dateFilterOptions',
            'healthManagerOptions',
            'canFilterHealthManager'
        ));
    }

    public function export(Request $request)
    {
        $salesOrders = $this->salesOrdersForExport($request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penjualan');

        $headers = ['No', 'Order Number', 'Sales', 'Customer', 'No. Telepon', 'Key In', 'Recurring', 'Guarantee Letter', 'CCP', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        foreach ($salesOrders as $index => $salesOrder) {
            $row = $index + 2;
            $sheet->fromArray([
                $index + 1,
                $salesOrder->order_no,
                $salesOrder->salesUser?->full_name ?: ($salesOrder->salesUser?->name ?? '-'),
                $salesOrder->customer?->full_name ?? '-',
                $salesOrder->customer?->phone_number ?? '-',
                $salesOrder->key_in_at?->format('d M Y') ?? '-',
                $salesOrder->is_recurring ? 'Yes' : 'No',
                $salesOrder->guarantee_letter ? 'Yes' : 'No',
                $salesOrder->ccp_status ?? '-',
                $salesOrder->status ?? '-',
            ], null, "A{$row}");
            $sheet->setCellValueExplicit("E{$row}", (string) ($salesOrder->customer?->phone_number ?? '-'), DataType::TYPE_STRING);
            $sheet->getStyle("A{$row}:J{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB($this->salesOrderStatusColor((string) $salesOrder->status));
        }

        $lastRow = max(2, $salesOrders->count() + 1);
        $sheet->getStyle("A2:J{$lastRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
        foreach (['A' => 7, 'B' => 22, 'C' => 26, 'D' => 26, 'E' => 18, 'F' => 16, 'G' => 12, 'H' => 18, 'I' => 22, 'J' => 22] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:J{$lastRow}");

        $fileName = $this->salesOrdersExportFileName('xlsx');
        $tmpPath = storage_path('app/' . Str::uuid() . '.xlsx');
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        return view('sales-orders.export-pdf', [
            'salesOrders' => $this->salesOrdersForExport($request),
            'fileName' => $this->salesOrdersExportFileName('pdf'),
        ]);
    }


    public function show(SalesOrder $salesOrder)
    {
        $user = request()->user();

        $visibleSalesUserIds = $this->visibleSalesUserIdsFor($user);

        if ($visibleSalesUserIds !== null) {
            abort_unless($visibleSalesUserIds->contains((int) $salesOrder->sales_user_id), 403);
        }

        $salesOrder->load([
            'customer',
            'salesUser',
            'parentItems.product',
            'parentItems.productPrice',
            'parentItems.childItems.product',
        ]);
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
            }, 'bundleItems'])
            ->orderBy('product_name')
            ->get(['id', 'sku', 'product_name', 'model', 'type']);

        // maintain value saat validation error (Admin)
        $oldSalesUser = null;
        if (old('sales_user_id')) {
            $oldSalesUser = User::role('Health Planner')
                ->whereKey(old('sales_user_id'))
                ->first(['id', 'name', 'email', 'dst_code']);
        }

        // maintain old value
        $oldHealthManager = null;
        if (old('health_manager_id')) {
            $oldHealthManager = User::role('Health Manager')
                ->whereKey(old('health_manager_id'))
                ->first(['id', 'name', 'email']);
        }


        return view('sales-orders.create', compact(
            'paymentMethods',
            'statuses',
            'ccpStatuses',
            'products',
            'oldSalesUser',
            'oldHealthManager'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $isPrivileged = $authUser->hasAnyRole(['Admin', 'Head Admin']);

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', 'unique:sales_orders,order_no'],

            'health_manager_id' => [$isPrivileged ? 'required' : 'nullable', 'exists:users,id'],
            'sales_user_id'     => [$isPrivileged ? 'required' : 'nullable', 'exists:users,id'],

            // customer input
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['required', 'string', 'max:500'],

            // order fields
            'key_in_at' => ['nullable', 'date'],
            'install_date' => [
                Rule::requiredIf(fn() => in_array($request->input('status'), ['dijadwalkan', 'dibatalkan', 'ditunda', 'tinjau ulang', 'selesai'], true)),
                Rule::prohibitedIf(fn() => in_array($request->input('status'), [
                    'menunggu verifikasi',
                    'menunggu jadwal',
                    'gagal penelponan'
                ], true)),
                'nullable',
                'date',
            ],
            'is_recurring' => ['nullable', 'boolean'],
            'guarantee_letter' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', Rule::in(array_keys($this->paymentMethods))],
            'payment_method_remarks' => [
                // opsional, tapi hanya boleh ada kalau Admin/Head Admin DAN payment_method = outright (POA)
                Rule::prohibitedIf(fn() => !$isPrivileged || $request->input('payment_method') !== 'outright'),
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => ['required', Rule::in($this->statuses)],
            'ccp_status' => ['required', Rule::in($this->ccpStatuses)],

            'ccp_remarks' => [
                Rule::requiredIf(fn() => $request->input('ccp_status') === 'ditolak'),
                Rule::prohibitedIf(fn() => $request->input('ccp_status') !== 'ditolak'),
                'nullable',
                'string',
                'max:1000',
            ],
            'ccp_approved_at' => [
                Rule::requiredIf(fn() => $request->input('ccp_status') === 'disetujui'),
                Rule::prohibitedIf(fn() => $request->input('ccp_status') !== 'disetujui'),
                'nullable',
                'date',
            ],

            'customer_type' => ['required', Rule::in($this->customerTypes)],

            // items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.order_no' => ['nullable', 'string', 'max:50'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.product_price_id' => ['required', 'exists:product_prices,id'],
            'items.*.bundle_items' => ['nullable', 'array'],
            'items.*.bundle_items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.bundle_items.*.order_no' => ['nullable', 'string', 'max:50'],
            'items.*.bundle_items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.bundle_items.*.is_cancelled' => ['nullable', 'boolean'],

            'status_reason' => [
                Rule::requiredIf(fn() => in_array($request->input('status'), ['dibatalkan', 'ditunda', 'gagal penelponan', 'tinjau ulang'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        if ($authUser->hasAnyRole(['Admin', 'Head Admin'])) {
            $isHealthPlanner = User::role('Health Planner')
                ->whereKey($validated['sales_user_id'])
                ->exists();

            if (!$isHealthPlanner) {
                return back()
                    ->withErrors(['sales_user_id' => 'Health Planner wajib dipilih.'])
                    ->withInput();
            }

            $hm = User::find((int) $validated['health_manager_id']);
            $downlineIds = $hm ? $this->descendantUserIds($hm->id) : collect();

            if (!$downlineIds->contains((int) $validated['sales_user_id'])) {
                return back()
                    ->withErrors(['sales_user_id' => 'Health Planner yang dipilih bukan bawahan dari Health Manager tersebut.'])
                    ->withInput();
            }
        }

        if ($error = $this->validateSalesOrderItemsPayload($request->input('items', []))) {
            return back()->withErrors($error)->withInput();
        }


        // normalize status_reason
        if (!in_array($validated['status'], ['dibatalkan', 'ditunda', 'gagal penelponan', 'tinjau ulang'], true)) {
            $validated['status_reason'] = null;
        }

        // normalize ccp fields
        if (($validated['ccp_status'] ?? null) !== 'ditolak') {
            $validated['ccp_remarks'] = null;
        }
        if (($validated['ccp_status'] ?? null) !== 'disetujui') {
            $validated['ccp_approved_at'] = null;
        }

        // normalize payment_method_remarks
        if (!$isPrivileged || (($validated['payment_method'] ?? null) !== 'outright')) {
            $validated['payment_method_remarks'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser) {
            $isPrivileged = $authUser->hasAnyRole(['Admin', 'Head Admin']);

            $salesUserId = $isPrivileged
                ? (int) $validated['sales_user_id']
                : (int) $authUser->id;

            // CUSTOMER: pilih existing / create baru
            $customerId = $this->resolveCustomerId($validated);

            // install_date: simplify by status
            $installDate = null;
            if (in_array($validated['status'] ?? null, ['dijadwalkan', 'dibatalkan', 'ditunda', 'tinjau ulang', 'selesai'], true)) {
                $installDate = $validated['install_date'] ?? null;
            }

            $so = SalesOrder::create([
                'order_no' => $validated['order_no'],
                'sales_user_id' => $salesUserId,
                'customer_id' => $customerId,
                'customer_type' => $validated['customer_type'],
                'key_in_at' => $validated['key_in_at'] ?? now(),
                'install_date' => $installDate,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'guarantee_letter' => (bool) ($validated['guarantee_letter'] ?? false),
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_method_remarks' => $validated['payment_method_remarks'] ?? null,
                'status' => $validated['status'],
                'ccp_status' => $validated['ccp_status'],
                'ccp_remarks' => $validated['ccp_remarks'] ?? null,
                'ccp_approved_at' => $validated['ccp_approved_at'] ?? null,
                'status_reason' => $validated['status_reason'],
            ]);

            $this->persistSalesOrderItems($so, $validated['items']);

            return redirect()
                ->route('sales-orders.index')
                ->with('success', 'Penjualan berhasil dibuat.');
        });
    }

    public function searchSalesUsers(Request $request)
    {
        abort_unless(
            $request->user() &&
                $request->user()->hasAnyRole(['Admin', 'Head Admin']),
            403
        );

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
            ->map(function ($u) {
                $display = trim((string) $u->name);
                $full = trim((string) ($u->full_name ?? ''));
                $labelName = $display;

                if ($full !== '' && mb_strtolower($full) !== mb_strtolower($display)) {
                    $labelName .= " • {$full}";
                }

                $label = $labelName . ($u->email ? " ({$u->email})" : '');

                return [
                    'id' => $u->id,
                    'label' => $label,
                    'dst_code' => $u->dst_code,
                ];
            });

        return response()->json($users);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $paymentMethods = $this->paymentMethods;
        $statuses = $this->statuses;
        $ccpStatuses = $this->ccpStatuses;

        $salesOrder->load([
            'customer',
            'salesUser',
            'parentItems.product.bundleItems',
            'parentItems.productPrice',
            'parentItems.childItems.product',
        ]);

        // Ambil semua price_id yang sudah kepilih di order ini
        $selectedPriceIds = $salesOrder->parentItems->pluck('product_price_id')->filter()->unique()->values();

        $products = Product::query()
            ->where('is_active', true)
            ->with(['prices' => function ($q) use ($selectedPriceIds) {
                $q->where(function ($qq) use ($selectedPriceIds) {
                    $qq->where('is_active', true)
                        ->orWhereIn('id', $selectedPriceIds); // ✅ supaya selected price tetap muncul
                })
                    ->orderBy('billing_type')
                    ->orderBy('duration_months');
            }, 'bundleItems'])
            ->orderBy('product_name')
            ->get(['id', 'sku', 'product_name', 'model', 'type']);

        $oldSalesUser = $salesOrder->salesUser;
        if (old('sales_user_id')) {
            $oldSalesUser = User::find(old('sales_user_id'), ['id', 'name', 'email', 'dst_code']);
        }

        $oldHealthManager = null;
        if (old('health_manager_id')) {
            $oldHealthManager = User::find(old('health_manager_id'), ['id', 'name', 'email']);
        } else {
            $hmId = $this->nearestHealthManagerId((int) $salesOrder->sales_user_id);
            if ($hmId) {
                $oldHealthManager = User::find($hmId, ['id', 'name', 'email']);
            }
        }

        return view('sales-orders.edit', compact(
            'salesOrder',
            'paymentMethods',
            'statuses',
            'ccpStatuses',
            'products',
            'oldSalesUser',
            'oldHealthManager'
        ));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $isPrivileged = $authUser->hasAnyRole(['Admin', 'Head Admin']);

        $validated = $request->validate([
            'order_no' => ['required', 'string', 'max:50', Rule::unique('sales_orders', 'order_no')->ignore($salesOrder->id)],

            'health_manager_id' => [$isPrivileged ? 'required' : 'nullable', 'exists:users,id'],
            'sales_user_id'     => [$isPrivileged ? 'required' : 'nullable', 'exists:users,id'],

            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_type' => ['required', Rule::in($this->customerTypes)],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['required', 'string', 'max:500'],

            'key_in_at' => ['nullable', 'date'],
            'install_date' => [
                Rule::requiredIf(fn() => in_array($request->input('status'), ['dijadwalkan', 'dibatalkan', 'ditunda', 'tinjau ulang', 'selesai'], true)),
                Rule::prohibitedIf(fn() => in_array($request->input('status'), ['menunggu verifikasi', 'menunggu jadwal', 'gagal penelponan'], true)),
                'nullable',
                'date',
            ],
            'is_recurring' => ['nullable', 'boolean'],
            'guarantee_letter' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', Rule::in(array_keys($this->paymentMethods))],
            'payment_method_remarks' => [
                Rule::prohibitedIf(fn() => !$isPrivileged || $request->input('payment_method') !== 'outright'),
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => ['required', Rule::in($this->statuses)],
            'ccp_status' => ['required', Rule::in($this->ccpStatuses)],

            'ccp_remarks' => [
                Rule::requiredIf(fn() => $request->input('ccp_status') === 'ditolak'),
                Rule::prohibitedIf(fn() => $request->input('ccp_status') !== 'ditolak'),
                'nullable',
                'string',
                'max:1000',
            ],
            'ccp_approved_at' => [
                Rule::requiredIf(fn() => $request->input('ccp_status') === 'disetujui'),
                Rule::prohibitedIf(fn() => $request->input('ccp_status') !== 'disetujui'),
                'nullable',
                'date',
            ],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.order_no' => ['nullable', 'string', 'max:50'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.product_price_id' => ['required', 'exists:product_prices,id'],
            'items.*.bundle_items' => ['nullable', 'array'],
            'items.*.bundle_items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.bundle_items.*.order_no' => ['nullable', 'string', 'max:50'],
            'items.*.bundle_items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.bundle_items.*.is_cancelled' => ['nullable', 'boolean'],

            'status_reason' => [
                Rule::requiredIf(fn() => in_array($request->input('status'), ['dibatalkan', 'ditunda', 'gagal penelponan', 'tinjau ulang'], true)),
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $isDowngradingApprovedCcp = $salesOrder->ccp_status === 'disetujui'
            && $validated['ccp_status'] !== 'disetujui';

        if ($isDowngradingApprovedCcp && in_array($validated['status'], ['dijadwalkan', 'selesai'], true)) {
            return back()
                ->withErrors([
                    'ccp_status' => 'CCP status tidak dapat diubah dari disetujui selama status instalasi masih dijadwalkan atau selesai. Ubah status instalasi menjadi dibatalkan atau menunggu jadwal terlebih dahulu.',
                ])
                ->withInput();
        }

        if ($isPrivileged) {
            $isHealthPlanner = User::role('Health Planner')
                ->whereKey($validated['sales_user_id'])
                ->exists();

            if (!$isHealthPlanner) {
                return back()
                    ->withErrors(['sales_user_id' => 'Health Planner wajib dipilih.'])
                    ->withInput();
            }

            $hm = User::find((int) $validated['health_manager_id']);
            $downlineIds = $hm ? $this->descendantUserIds($hm->id) : collect();

            if (!$downlineIds->contains((int) $validated['sales_user_id'])) {
                return back()
                    ->withErrors(['sales_user_id' => 'Health Planner yang dipilih bukan bawahan dari Health Manager tersebut.'])
                    ->withInput();
            }
        }

        if ($error = $this->validateSalesOrderItemsPayload($request->input('items', []))) {
            return back()->withErrors($error)->withInput();
        }

        if (!in_array($validated['status'], ['dibatalkan', 'ditunda', 'gagal penelponan', 'tinjau ulang'], true)) {
            $validated['status_reason'] = null;
        }

        if (($validated['ccp_status'] ?? null) !== 'ditolak') {
            $validated['ccp_remarks'] = null;
        }
        if (($validated['ccp_status'] ?? null) !== 'disetujui') {
            $validated['ccp_approved_at'] = null;
        }

        if (!$isPrivileged || (($validated['payment_method'] ?? null) !== 'outright')) {
            $validated['payment_method_remarks'] = null;
        }

        return DB::transaction(function () use ($validated, $authUser, $salesOrder) {
            $isPrivileged = $authUser->hasAnyRole(['Admin', 'Head Admin']);
            $salesUserId = $isPrivileged
                ? (int) $validated['sales_user_id']
                : (int) $authUser->id;

            $customerId = $this->resolveCustomerId($validated, true);

            // install_date: simple by status
            $installDate = null;
            if (in_array($validated['status'] ?? null, ['dijadwalkan', 'dibatalkan', 'ditunda', 'tinjau ulang', 'selesai'], true)) {
                $installDate = $validated['install_date'] ?? null;
            }

            $salesOrder->update([
                'order_no' => $validated['order_no'],
                'sales_user_id' => $salesUserId,
                'customer_id' => $customerId,
                'customer_type' => $validated['customer_type'],
                'key_in_at' => $validated['key_in_at'] ?? $salesOrder->key_in_at ?? now(),
                'install_date' => $installDate,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'guarantee_letter' => (bool) ($validated['guarantee_letter'] ?? false),
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_method_remarks' => $validated['payment_method_remarks'] ?? null,
                'status' => $validated['status'],
                'ccp_status' => $validated['ccp_status'],
                'ccp_remarks' => $validated['ccp_remarks'] ?? null,
                'ccp_approved_at' => $validated['ccp_approved_at'] ?? null,
                'status_reason' => $validated['status_reason'],
            ]);

            $salesOrder->items()->delete();
            $this->persistSalesOrderItems($salesOrder, $validated['items']);

            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('success', 'Penjualan berhasil diupdate.');
        });
    }

    /**
     * Resolve customer_id.
     * - kalau customer_id ada => pakai itu, optional update fields kalau $allowUpdateExisting = true
     * - kalau tidak ada => cari existing by (lower(full_name), phone optional) lalu create jika belum ada
     */
    private function validateSalesOrderItemsPayload(array $items): ?array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->with('bundleItems')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $i => $row) {
            $product = $products->get((int) ($row['product_id'] ?? 0));
            $priceId = $row['product_price_id'] ?? null;

            if (!$product) {
                continue;
            }

            if ($priceId) {
                $ok = ProductPrice::query()
                    ->where('id', $priceId)
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$ok) {
                    return ["items.$i.product_price_id" => "Price tidak sesuai dengan product yang dipilih."];
                }
            }

            if ($product->type !== 'bundle') {
                continue;
            }

            $bundleItems = collect($row['bundle_items'] ?? []);
            if ($bundleItems->isEmpty()) {
                return ["items.$i.bundle_items" => "Bundle wajib memiliki list product di dalamnya."];
            }

            $allowed = $product->bundleItems->mapWithKeys(fn($item) => [
                (int) $item->id => (int) $item->pivot->qty,
            ]);

            foreach ($bundleItems as $childIndex => $child) {
                $childProductId = (int) ($child['product_id'] ?? 0);
                if (!$allowed->has($childProductId)) {
                    return ["items.$i.bundle_items.$childIndex.product_id" => "Product child tidak termasuk dalam bundle yang dipilih."];
                }
            }
        }

        return null;
    }

    private function persistSalesOrderItems(SalesOrder $salesOrder, array $items): void
    {
        $products = Product::query()
            ->with('bundleItems')
            ->whereIn('id', collect($items)->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        foreach ($items as $row) {
            $product = $products->get((int) $row['product_id']);
            $children = collect($row['bundle_items'] ?? [])->values();
            $isBundle = $product && $product->type === 'bundle';
            $activeChildQty = $children
                ->reject(fn($child) => (bool) ($child['is_cancelled'] ?? false))
                ->sum(fn($child) => max(1, (int) ($child['qty'] ?? 1)));

            $parent = $salesOrder->items()->create([
                'parent_item_id' => null,
                'product_id' => (int) $row['product_id'],
                'product_price_id' => (int) $row['product_price_id'],
                'order_no' => filled($row['order_no'] ?? null) ? trim((string) $row['order_no']) : null,
                'qty' => $isBundle ? max(0, (int) $activeChildQty) : max(1, (int) $row['qty']),
                'is_cancelled' => false,
            ]);

            if (!$isBundle) {
                continue;
            }

            foreach ($children as $child) {
                $salesOrder->items()->create([
                    'parent_item_id' => $parent->id,
                    'product_id' => (int) $child['product_id'],
                    'product_price_id' => null,
                    'order_no' => filled($child['order_no'] ?? null) ? trim((string) $child['order_no']) : null,
                    'qty' => max(1, (int) ($child['qty'] ?? 1)),
                    'is_cancelled' => (bool) ($child['is_cancelled'] ?? false),
                ]);
            }
        }
    }

    private function resolveCustomerId(array $validated, bool $allowUpdateExisting = false): int
    {
        $customerId = $validated['customer_id'] ?? null;

        $name = trim($validated['customer_name']);
        $phone = trim((string) ($validated['customer_phone'] ?? ''));

        if ($customerId) {
            $customerData = [];

            if ($allowUpdateExisting || !empty($validated['customer_birth_date'])) {
                $customerData['date_of_birth'] = $validated['customer_birth_date'] ?? null;
            }

            if ($allowUpdateExisting) {
                $customerData = array_merge($customerData, [
                    'full_name' => $name,
                    'phone_number' => $validated['customer_phone'] ?? null,
                    'address' => $validated['customer_address'] ?? null,
                ]);
            }

            if (!empty($customerData)) {
                Customer::whereKey($customerId)->update($customerData);
            }
            return (int) $customerId;
        }

        $existing = Customer::query()
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($name)])
            ->when($phone !== '', fn($q) => $q->where('phone_number', $phone))
            ->first();

        if ($existing) {
            if (!empty($validated['customer_birth_date'])) {
                $existing->update([
                    'date_of_birth' => $validated['customer_birth_date'],
                ]);
            }

            return (int) $existing->id;
        }

        $customer = Customer::create([
            'full_name' => $name,
            'date_of_birth' => $validated['customer_birth_date'] ?? null,
            'phone_number' => $validated['customer_phone'] ?? null,
            'address' => $validated['customer_address'] ?? null,
        ]);

        return (int) $customer->id;
    }

    public function searchHealthManagers(Request $request)
    {
        abort_unless(
            $request->user() &&
                $request->user()->hasAnyRole(['Admin', 'Head Admin']),
            403
        );

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $users = User::query()
            ->role('Health Manager')
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'full_name', 'email'])
            ->map(function ($u) {
                $display = trim((string) $u->name);
                $full = trim((string) ($u->full_name ?? ''));

                $labelName = $display;
                if ($full !== '' && mb_strtolower($full) !== mb_strtolower($display)) {
                    $labelName .= " • {$full}";
                }

                $label = $labelName . ($u->email ? " ({$u->email})" : '');

                return [
                    'id' => $u->id,
                    'label' => $label,
                ];
            });

        return response()->json($users);
    }

    public function searchHealthPlanners(Request $request)
    {
        abort_unless(
            $request->user() &&
                $request->user()->hasAnyRole(['Admin', 'Head Admin']),
            403
        );

        $managerId = (int) $request->get('health_manager_id');
        if (!$managerId) return response()->json([]);

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $hm = User::find($managerId);
        if (!$hm) return response()->json([]);

        // ✅ gunakan relasi yang sudah terbukti dipakai di index()
        $downlineIds = $this->descendantUserIds($hm->id); // pastikan ini include semua downline
        if ($downlineIds->isEmpty()) return response()->json([]);

        $users = User::query()
            ->role('Health Planner')
            ->whereIn('id', $downlineIds)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'full_name', 'email', 'dst_code'])
            ->map(function ($u) {
                $display = trim((string) $u->name);
                $full = trim((string) ($u->full_name ?? ''));
                $labelName = $display;

                if ($full !== '' && mb_strtolower($full) !== mb_strtolower($display)) {
                    $labelName .= " • {$full}";
                }

                $label = $labelName . ($u->email ? " ({$u->email})" : '');

                return [
                    'id' => $u->id,
                    'label' => $label,
                    'dst_code' => $u->dst_code,
                ];
            });

        return response()->json($users);
    }


    /**
     * Ambil semua descendant user id dari 1 root user (BFS) via table user_hierarchies.
     * Mengembalikan collection of ids (include root).
     */
    private function descendantUserIds(int $rootId)
    {
        $visited = collect([$rootId]);
        $queue = collect([$rootId]);

        while ($queue->isNotEmpty()) {
            $batch = $queue->splice(0)->all();

            $children = UserHierarchy::query()
                ->whereIn('parent_user_id', $batch)
                ->pluck('child_user_id');

            $children = $children->diff($visited);

            if ($children->isEmpty()) break;

            $visited = $visited->merge($children);
            $queue = $queue->merge($children);
        }

        return $visited->values();
    }


    public function listHealthPlanners(Request $request)
    {
        abort_unless(
            $request->user() &&
                $request->user()->hasAnyRole(['Admin', 'Head Admin']),
            403
        );

        $managerId = (int) $request->get('health_manager_id');
        if (!$managerId) return response()->json([]);

        $hm = User::find($managerId);
        if (!$hm) return response()->json([]);

        // downline HM (yang kamu pakai di search)
        $downlineIds = $this->descendantUserIds($hm->id);
        if ($downlineIds->isEmpty()) return response()->json([]);

        $users = User::query()
            ->role('Health Planner')
            ->whereIn('id', $downlineIds)
            ->orderBy('name')
            ->get(['id', 'name', 'full_name', 'email', 'dst_code'])
            ->map(function ($u) {
                $display = trim((string) $u->name);
                $full = trim((string) ($u->full_name ?? ''));

                $labelName = $display;
                if ($full !== '' && mb_strtolower($full) !== mb_strtolower($display)) {
                    $labelName .= " • {$full}";
                }

                $label = $labelName . ($u->email ? " ({$u->email})" : '');

                return [
                    'id' => $u->id,
                    'label' => $label,
                    'dst_code' => $u->dst_code,
                ];
            })

            ->values();

        return response()->json($users);
    }

    private function nearestHealthManagerId(int $userId): ?int
    {
        $visited = [];
        $current = $userId;

        while ($current) {
            if (isset($visited[$current])) break;
            $visited[$current] = true;

            $parentId = UserHierarchy::query()
                ->where('child_user_id', $current)
                ->value('parent_user_id');

            if (!$parentId) return null;

            $isHm = User::role('Health Manager')->whereKey($parentId)->exists();
            if ($isHm) return (int) $parentId;

            $current = (int) $parentId;
        }

        return null;
    }

    /**
     * IDs user yang boleh terlihat di Sales Orders (yang relevan: Health Planner saja).
     * - Admin/Head Admin: handled di index/show (return null berarti no restriction)
     * - HP: hanya diri sendiri
     * - Selain itu: semua downline tapi difilter hanya role HP
     */
    private function visibleSalesUserIdsFor(User $user)
    {
        // Admin/Head Admin: no restriction
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            return null;
        }

        // ✅ Semua non-admin (termasuk Health Planner): diri sendiri + semua downline
        $treeIds = $this->descendantUserIds((int) $user->id); // include self + all descendants

        // sales_user_id di SO itu biasanya Health Planner, jadi filter hanya HP
        return User::query()
            ->role('Health Planner')
            ->whereIn('id', $treeIds)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();
    }

    private function healthManagerFilterOptionsFor(User $user)
    {
        if (!$user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin'])) {
            return collect();
        }

        $q = User::query()
            ->role('Health Manager')
            ->where('status', 'Active');

        if ($user->hasRole('Sales Manager')) {
            $hmIds = UserHierarchy::query()
                ->where('parent_user_id', $user->id)
                ->pluck('child_user_id');

            $q->whereIn('id', $hmIds);
        }

        return $q
            ->orderByRaw("COALESCE(NULLIF(full_name,''), name) asc")
            ->get(['id', 'name', 'full_name', 'email'])
            ->map(fn($hm) => [
                'id' => (int) $hm->id,
                'label' => trim((string) ($hm->full_name ?: $hm->name)),
                'email' => $hm->email,
            ])
            ->values();
    }

    private function filteredSalesOrdersQuery(Request $request, bool $canFilterHealthManager, $healthManagerOptions): array
    {
        $q = SalesOrder::query()->with(['customer', 'salesUser']);
        $visibleSalesUserIds = $this->visibleSalesUserIdsFor($request->user());

        if ($visibleSalesUserIds !== null) {
            $q->whereIn('sales_user_id', $visibleSalesUserIds);
        }

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $q->where(function (Builder $query) use ($search) {
                $query->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn(Builder $customer) => $customer->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('salesUser', function (Builder $salesUser) use ($search) {
                        $salesUser->where('full_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $selectedStatuses = collect((array) $request->input('status', []))
            ->filter(fn($status) => in_array($status, $this->statuses, true))
            ->values()
            ->all();

        if ($selectedStatuses !== []) {
            $q->whereIn('status', $selectedStatuses);
        }

        if ($request->filled('ccp_status') && in_array($request->ccp_status, $this->ccpStatuses, true)) {
            $q->where('ccp_status', $request->ccp_status);
        }

        $dateFilterColumns = [
            'key_in_at' => 'key_in_at',
            'ccp_approved_at' => 'ccp_approved_at',
            'install_date' => 'install_date',
        ];
        $dateFilterBy = array_key_exists($request->get('date_filter_by'), $dateFilterColumns)
            ? $request->get('date_filter_by')
            : 'key_in_at';
        $dateFilterColumn = $dateFilterColumns[$dateFilterBy];

        if ($request->filled('from')) {
            $q->whereDate($dateFilterColumn, '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate($dateFilterColumn, '<=', $request->to);
        }
        if ($request->filled('customer_type') && in_array($request->customer_type, $this->customerTypes, true)) {
            $q->where('customer_type', $request->customer_type);
        }
        if ($request->boolean('guarantee_letter')) {
            $q->where('guarantee_letter', true);
        }

        if ($canFilterHealthManager && $request->filled('health_manager_id')) {
            $healthManagerId = (int) $request->health_manager_id;
            $allowedIds = $healthManagerOptions->pluck('id')->map(fn($id) => (int) $id);

            if ($allowedIds->contains($healthManagerId)) {
                $healthPlannerIds = User::query()
                    ->role('Health Planner')
                    ->whereIn('id', $this->descendantUserIds($healthManagerId))
                    ->pluck('id');
                $q->whereIn('sales_user_id', $healthPlannerIds);
            }
        }

        return [$q, $selectedStatuses, $dateFilterBy];
    }

    private function salesOrdersForExport(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['Head Admin', 'Admin', 'Health Manager']), 403);

        $canFilterHealthManager = $request->user()->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']);
        $healthManagerOptions = $this->healthManagerFilterOptionsFor($request->user());
        [$query] = $this->filteredSalesOrdersQuery($request, $canFilterHealthManager, $healthManagerOptions);

        return $query->latest('key_in_at')->get();
    }

    private function salesOrdersExportFileName(string $extension): string
    {
        return 'Data Penjualan - ' . now()->format('Y-m-d') . ".{$extension}";
    }

    private function salesOrderStatusColor(string $status): string
    {
        return match ($status) {
            'dibatalkan', 'gagal penelponan' => 'FFFEE2E2',
            'ditunda', 'tinjau ulang' => 'FFFEF3C7',
            'selesai' => 'FFDCFCE7',
            default => 'FFF3F4F6',
        };
    }
}
