<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCutoff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $isAdminOrHead = $authUser->hasAnyRole(['Admin', 'Head Admin']);

        // ======================================
        // Scope member filter
        // ======================================
        $authDownlineIds = $authUser->downlineUserIds();

        $allowedIds = $isAdminOrHead
            ? User::query()->pluck('id')
            : $authDownlineIds->push($authUser->id)->unique()->values();

        $memberId = (int) $request->get('member_id', 0);
        $hasMemberFilter = $memberId && $allowedIds->contains($memberId);

        $baseUser = $authUser;
        if ($hasMemberFilter) {
            $baseUser = User::query()->whereKey($memberId)->first() ?? $authUser;
        }

        // Reset:
        // - Admin/Head Admin => semua data
        // Filter:
        // - partner terpilih + downline
        if ($isAdminOrHead && !$hasMemberFilter) {
            $childIds = collect();
            $scopeUserIds = User::query()->pluck('id');
        } else {
            $childIds = $baseUser->downlineUserIds();
            $scopeUserIds = $childIds->push($baseUser->id)->unique()->values();
        }

        // ======================================
        // Date range
        // ======================================
        $q = trim((string) $request->get('q', ''));

        $cutoff = PerformanceCutoff::current();

        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date   ?? Carbon::now()->endOfMonth()->addMonth()->toDateString();

        [$from, $to, $isManual] = $this->normalizeDateRange(
            $request->get('from'),
            $request->get('to')
        );

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $manualDateRange = (
            $request->filled('from') || $request->filled('to')
        ) && (
            $from !== $defaultFrom || $to !== $defaultTo
        );

        // ======================================
        // Dropdown options
        // ======================================
        $memberOptions = User::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('id', $allowedIds))
            ->when($isAdminOrHead, fn($q) => $q->where('status', 'Active'))
            ->orderByRaw("COALESCE(NULLIF(full_name,''), name) asc")
            ->get(['id', 'name', 'full_name'])
            ->map(fn($u) => [
                'id' => $u->id,
                'label' => trim(($u->full_name ?: $u->name) . ($u->id === $authUser->id ? ' (Saya)' : '')),
            ])
            ->values();

        // ======================================
        // Helper units
        // ======================================
        $joinUnits = function ($q, string $soAlias = 'so') {
            return $q
                ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', "{$soAlias}.id")
                ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
                ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id');
        };

        $unitsExpr = "
            COALESCE(SUM(
                CASE
                    WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                    ELSE soi.qty
                END
            ), 0)
        ";

        $rowUnitExpr = "CASE WHEN p.type = 'bundle' THEN soi.qty * bi.qty ELSE soi.qty END";

        // ======================================
        // TEAM PERFORMANCE
        // ======================================
        $teamPerformanceQ = User::query()
            ->when(
                $isAdminOrHead && !$hasMemberFilter,
                fn($q) => $q->role('Health Planner')->where('users.status', 'Active'),
                fn($q) => $q->whereIn('users.id', $childIds)
            )
            ->leftJoin('sales_orders as so', function ($join) use ($from, $to) {
                $join->on('so.sales_user_id', '=', 'users.id')
                    ->whereNull('so.deleted_at')
                    ->where('so.status', 'selesai');

                if ($from) $join->whereDate('so.install_date', '>=', $from);
                if ($to)   $join->whereDate('so.install_date', '<=', $to);
            });

        $teamPerformanceQ = $joinUnits($teamPerformanceQ, 'so')
            ->select(
                'users.id',
                'users.name',
                DB::raw("COALESCE(NULLIF(users.full_name,''), users.name) as full_label"),
                DB::raw("{$unitsExpr} as units")
            )
            ->groupBy('users.id', 'users.name', 'users.full_name')
            ->orderByDesc('units');

        $teamPerformance = $teamPerformanceQ
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->full_label,
                    'units' => (int) $row->units,
                ];
            });

        // ======================================
        // MY TOTAL UNITS
        // ======================================
        $myTotalUnitsQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $baseUser->id)
            ->where('so.status', 'selesai');

        if ($from) $myTotalUnitsQ->whereDate('so.install_date', '>=', $from);
        if ($to)   $myTotalUnitsQ->whereDate('so.install_date', '<=', $to);

        $myTotalUnits = (int) $joinUnits($myTotalUnitsQ, 'so')
            ->selectRaw("{$unitsExpr} as units")
            ->value('units');

        // ======================================
        // SUMMARY CARDS
        // ======================================
        $summaryQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        $this->applyPerformanceScopeFilter($summaryQ, $from, $to, !$manualDateRange);

        $summaryQ = $joinUnits($summaryQ, 'so');

        $summary = $summaryQ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 0
                    AND so.ccp_status = 'menunggu pengecekan'
                    AND (so.status = 'menunggu verifikasi' OR so.status = 'dibatalkan')
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_key_in,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'menunggu pengecekan'
                    AND so.status = 'menunggu verifikasi'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_recurring,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status = 'menunggu jadwal'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as menunggu_jadwal,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status = 'dijadwalkan'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as dijadwalkan,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status IN ('ditunda', 'gagal penelponan', 'tinjau ulang')
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as pending,

            COALESCE(SUM(
                CASE
                    WHEN so.status = 'selesai'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_sudah_install
        ")->first();

        // ======================================
        // TEAM SHEET
        // ======================================
        $soiAgg = DB::table('sales_order_items as soi')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->selectRaw("
                soi.sales_order_id,
                COALESCE(SUM(
                    CASE
                        WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                        ELSE soi.qty
                    END
                ),0) as ns_units
            ")
            ->groupBy('soi.sales_order_id');

        $sheetQ = DB::table('sales_orders as so')
            ->join('users as u', 'u.id', '=', 'so.sales_user_id')
            ->leftJoin('customers as c', function ($j) {
                $j->on('c.id', '=', 'so.customer_id')->whereNull('c.deleted_at');
            })
            ->leftJoinSub($soiAgg, 'soi', function ($j) {
                $j->on('soi.sales_order_id', '=', 'so.id');
            })
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        $this->applyPerformanceScopeFilter($sheetQ, $from, $to, !$manualDateRange, true);

        $teamSheetRows = $sheetQ
            ->orderBy('u.name')
            ->orderBy('so.key_in_at')
            ->select([
                'so.id',
                'so.sales_user_id',
                DB::raw("COALESCE(NULLIF(u.full_name,''), u.name) as hp_name"),
                DB::raw("COALESCE(c.full_name, '-') as customer_name"),
                'so.key_in_at',
                'so.ccp_status',
                'so.status',
                'so.install_date',
                'so.status_reason',
                'so.ccp_remarks',
                'so.payment_method_remarks',
                DB::raw("
                    COALESCE(
                        NULLIF(so.payment_method_remarks,''),
                        NULLIF(so.ccp_remarks,''),
                        NULLIF(so.status_reason,''),
                        '-'
                    ) as remarks
                "),
                'so.ccp_approved_at',
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
            ->selectRaw("
                CASE
                    WHEN DATE(so.key_in_at) < DATE(?)
                     AND (
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'menunggu pengecekan' AND so.status = 'menunggu verifikasi')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status = 'menunggu jadwal')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status = 'dijadwalkan')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status IN ('ditunda', 'gagal penelponan', 'tinjau ulang'))
                     )
                    THEN 1
                    ELSE 0
                END as is_carry_over
            ", [$from])
            ->get()
            ->groupBy('hp_name');

        // ======================================
        // ROAD TO HM
        // ======================================
        $roadToHm = null;

        if ($authUser->hasRole('Health Planner')) {
            $roadToHm = $this->buildRoadToHmData($authUser);
        }

        return view('performances.index', [
            'teamPerformance' => $teamPerformance,
            'teamMemberCount' => ($isAdminOrHead && !$hasMemberFilter)
                ? User::query()->role('Health Planner')->where('status', 'Active')->count()
                : $childIds->count(),
            'myTotalUnits'    => $myTotalUnits,
            'q'               => $q,
            'from'            => $from,
            'to'              => $to,
            'summary'         => $summary,
            'teamSheetRows'   => $teamSheetRows,
            'memberOptions'   => $memberOptions,
            'memberId'        => $hasMemberFilter ? $baseUser->id : null,
            'memberLabel'     => $hasMemberFilter ? ($baseUser->full_name ?: $baseUser->name) : '',
            'roadToHm'        => $roadToHm,
        ]);
    }

    public function teamDetail(Request $request, User $user)
    {
        $auth = $request->user();

        $isAdminOrHead = $auth->hasAnyRole(['Admin', 'Head Admin']);
        $isChild = $auth->childrenUsers()->where('users.id', $user->id)->exists();

        abort_unless($isAdminOrHead || $isChild, 403);

        [$from, $to, $isManual] = $this->normalizeDateRange(
            $request->get('from'),
            $request->get('to')
        );

        $cutoff = PerformanceCutoff::current();
        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date   ?? Carbon::now()->endOfMonth()->addMonth()->toDateString();

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $totalUnitsQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->where('so.status', 'selesai');

        if ($from) $totalUnitsQ->whereDate('so.install_date', '>=', $from);
        if ($to)   $totalUnitsQ->whereDate('so.install_date', '<=', $to);

        $totalUnits = (int) $totalUnitsQ
            ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                        ELSE soi.qty
                    END
                ),0) as units
            ")
            ->value('units');

        $ordersQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->where('so.sales_user_id', $user->id)
            ->where('so.status', 'selesai');

        if ($from) $ordersQ->whereDate('so.install_date', '>=', $from);
        if ($to)   $ordersQ->whereDate('so.install_date', '<=', $to);

        $orders = $ordersQ
            ->orderByDesc('so.install_date')
            ->limit(10)
            ->get(['so.id', 'so.order_no', 'so.status', 'so.key_in_at', 'so.install_date']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->full_name ?: $user->name,
            'total_units' => (int) $totalUnits,
            'revenue' => 0,
            'orders' => $orders,
        ]);
    }

    private function buildPerformanceData(Request $request): array
    {
        $authUser = $request->user();
        $isAdminOrHead = $authUser->hasAnyRole(['Admin', 'Head Admin']);

        // ======================================
        // Scope member filter
        // ======================================
        $authDownlineIds = $authUser->downlineUserIds();

        $allowedIds = $isAdminOrHead
            ? User::query()->pluck('id')
            : $authDownlineIds->push($authUser->id)->unique()->values();

        $memberId = (int) $request->get('member_id', 0);
        $hasMemberFilter = $memberId && $allowedIds->contains($memberId);

        $baseUser = $authUser;
        if ($hasMemberFilter) {
            $baseUser = User::query()->whereKey($memberId)->first() ?? $authUser;
        }

        if ($isAdminOrHead && !$hasMemberFilter) {
            $childIds = collect();
            $scopeUserIds = User::query()->pluck('id');
        } else {
            $childIds = $baseUser->downlineUserIds();
            $scopeUserIds = $childIds->push($baseUser->id)->unique()->values();
        }

        [$from, $to, $isManual] = $this->normalizeDateRange(
            $request->get('from'),
            $request->get('to')
        );

        $cutoff = PerformanceCutoff::current();

        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date   ?? Carbon::now()->endOfMonth()->addMonth()->toDateString();

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $manualDateRange = (
            $request->filled('from') || $request->filled('to')
        ) && (
            $from !== $defaultFrom || $to !== $defaultTo
        );

        // ======================================
        // Helper units
        // ======================================
        $joinUnits = function ($q, string $soAlias = 'so') {
            return $q
                ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', "{$soAlias}.id")
                ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
                ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id');
        };

        $rowUnitExpr = "CASE WHEN p.type = 'bundle' THEN soi.qty * bi.qty ELSE soi.qty END";

        // ======================================
        // SUMMARY
        // ======================================
        $summaryQ = DB::table('sales_orders as so')
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        $this->applyPerformanceScopeFilter($summaryQ, $from, $to, !$manualDateRange);

        $summaryQ = $joinUnits($summaryQ, 'so');

        $summary = $summaryQ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 0
                    AND so.ccp_status = 'menunggu pengecekan'
                    AND (so.status = 'menunggu verifikasi' OR so.status = 'dibatalkan')
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_key_in,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'menunggu pengecekan'
                    AND so.status = 'menunggu verifikasi'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_recurring,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status = 'menunggu jadwal'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as menunggu_jadwal,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status = 'dijadwalkan'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as dijadwalkan,

            COALESCE(SUM(
                CASE
                    WHEN COALESCE(so.is_recurring, 0) = 1
                    AND so.ccp_status = 'disetujui'
                    AND so.status IN ('ditunda', 'gagal penelponan', 'tinjau ulang')
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as pending,

            COALESCE(SUM(
                CASE
                    WHEN so.status = 'selesai'
                    THEN {$rowUnitExpr} ELSE 0
                END
            ),0) as total_sudah_install
        ")->first();

        // ======================================
        // TEAM SHEET
        // ======================================
        $soiAgg = DB::table('sales_order_items as soi')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->selectRaw("
                soi.sales_order_id,
                COALESCE(SUM(
                    CASE
                        WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                        ELSE soi.qty
                    END
                ),0) as ns_units
            ")
            ->groupBy('soi.sales_order_id');

        $sheetQ = DB::table('sales_orders as so')
            ->join('users as u', 'u.id', '=', 'so.sales_user_id')
            ->leftJoin('customers as c', function ($j) {
                $j->on('c.id', '=', 'so.customer_id')->whereNull('c.deleted_at');
            })
            ->leftJoinSub($soiAgg, 'soi', function ($j) {
                $j->on('soi.sales_order_id', '=', 'so.id');
            })
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        $this->applyPerformanceScopeFilter($sheetQ, $from, $to, !$manualDateRange, true);

        $teamSheetRows = $sheetQ
            ->orderBy('u.name')
            ->orderBy('so.key_in_at')
            ->select([
                'so.id',
                'so.sales_user_id',
                DB::raw("COALESCE(NULLIF(u.full_name,''), u.name) as hp_name"),
                DB::raw("COALESCE(c.full_name, '-') as customer_name"),
                'so.key_in_at',
                'so.ccp_status',
                'so.status',
                'so.install_date',
                'so.status_reason',
                'so.ccp_remarks',
                'so.payment_method_remarks',
                DB::raw("
                    COALESCE(
                        NULLIF(so.payment_method_remarks,''),
                        NULLIF(so.ccp_remarks,''),
                        NULLIF(so.status_reason,''),
                        '-'
                    ) as remarks
                "),
                'so.ccp_approved_at',
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
            ->selectRaw("
                CASE
                    WHEN DATE(so.key_in_at) < DATE(?)
                     AND (
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'menunggu pengecekan' AND so.status = 'menunggu verifikasi')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status = 'menunggu jadwal')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status = 'dijadwalkan')
                        OR
                        (COALESCE(so.is_recurring,0) = 1 AND so.ccp_status = 'disetujui' AND so.status IN ('ditunda', 'gagal penelponan', 'tinjau ulang'))
                     )
                    THEN 1
                    ELSE 0
                END as is_carry_over
            ", [$from])
            ->get()
            ->groupBy('hp_name');

        return [
            'baseUser' => $baseUser,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'teamSheetRows' => $teamSheetRows,
            'hasMemberFilter' => $hasMemberFilter,
        ];
    }

    public function export(Request $request)
    {
        $data = $this->buildPerformanceData($request);

        /** @var \App\Models\User $baseUser */
        $baseUser = $data['baseUser'];
        $from = $data['from'];
        $to = $data['to'];
        $summary = $data['summary'];
        $teamSheetRows = $data['teamSheetRows'];
        $hasMemberFilter = $data['hasMemberFilter'];

        $userName = strtoupper($baseUser->full_name ?: $baseUser->name);
        $roleName = $baseUser->roles->pluck('name')->first();

        $roleMap = [
            'Health Manager' => 'HM',
            'Health Planner' => 'HP',
            'Sales Manager'  => 'SM',
            'Admin'          => 'Admin',
            'Head Admin'     => 'Head Admin',
        ];

        $rolePrefix = $roleMap[$roleName] ?? $roleName ?? '';
        $title = $hasMemberFilter
            ? trim("Team {$rolePrefix} {$userName}")
            : 'Team Performance All';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Performance');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(16);

        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:K2');
        $sheet->setCellValue('A2', "Range: {$from} s/d {$to}");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 4;
        $headers = ['No', 'Nama HP', 'Nama Customer', 'Tanggal Key in', 'Old Case', 'CCP disetujui', 'Key-in', 'Install/NS', 'Status', 'Tanggal Instalasi', 'Remarks'];
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFBFE3FF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF1F2937'],
                ],
            ],
        ]);

        $row = $headerRow + 1;
        $no = 1;

        foreach ($teamSheetRows as $hpName => $rows) {
            $startRowForHp = $row;
            $count = $rows->count();

            foreach ($rows as $r) {
                $keyIn = $r->key_in_at ? Carbon::parse($r->key_in_at)->format('d-M') : '-';
                $ccpAppr = $r->ccp_approved_at ? Carbon::parse($r->ccp_approved_at)->format('d-M') : '';
                $installDate = $r->install_date ? Carbon::parse($r->install_date)->format('d-M') : '';
                $ns = (int) $r->ns_units;

                $sheet->setCellValue("A{$row}", $no);
                $sheet->setCellValue("B{$row}", $hpName);
                $sheet->setCellValue("C{$row}", $r->customer_name);
                $sheet->setCellValue("D{$row}", $keyIn);
                $sheet->setCellValue("E{$row}", ((int) $r->is_carry_over === 1) ? 'Old Case' : '-');
                $sheet->setCellValue("F{$row}", $ccpAppr ?: '');
                $sheet->setCellValue("G{$row}", "{$ns}NS");
                $sheet->setCellValue("H{$row}", ($r->status ?? '') === 'selesai' ? 'OK' : '');
                $sheet->setCellValue("I{$row}", $r->status ? Str::of($r->status)->replace('_', ' ')->title() : '-');
                $sheet->setCellValue("J{$row}", $installDate ?: '');
                $sheet->setCellValue("K{$row}", $r->remarks ?? '-');

                $row++;
            }

            if ($count > 1) {
                $endRowForHp = $row - 1;
                $sheet->mergeCells("A{$startRowForHp}:A{$endRowForHp}");
                $sheet->mergeCells("B{$startRowForHp}:B{$endRowForHp}");
            }

            $sheet->getStyle("A{$startRowForHp}:B" . ($row - 1))
                ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            $no++;
        }

        $sheet->getStyle("A" . ($headerRow + 1) . ":K" . ($row - 1))->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF9CA3AF'],
                ],
            ],
        ]);

        $row += 2;

        $sheet->mergeCells("A{$row}:K{$row}");
        $sheet->setCellValue("A{$row}", "Summary");
        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF1E293B'],
                ],
            ],
        ]);
        $row++;

        $summaryRows = [
            ['Total Key-In', (int)($summary->total_key_in ?? 0), 'FFE5E7EB'],
            ['Total Recurring', (int)($summary->total_recurring ?? 0), 'FFBFDBFE'],
            ['Dijadwalkan', (int)($summary->dijadwalkan ?? 0), 'FFFDE68A'],
            ['Menunggu Jadwal', (int)($summary->menunggu_jadwal ?? 0), 'FFFCD34D'],
            ['Pending', (int)($summary->pending ?? 0), 'FFD8B4FE'],
            ['Total sudah install (OK)', (int)($summary->total_sudah_install ?? 0), 'FF86EFAC'],
        ];

        foreach ($summaryRows as [$label, $value, $bgColor]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("K{$row}", $value);
            $sheet->mergeCells("A{$row}:J{$row}");

            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF94A3B8'],
                    ],
                ],
            ]);

            $row++;
        }

        $fileName = $this->performanceExportFileName($hasMemberFilter, $baseUser, $from, $to, 'xlsx');
        $tmpPath = storage_path('app/' . Str::uuid()->toString() . '.xlsx');

        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    public function exportNewFormat(Request $request)
    {
        $data = $this->buildPerformanceNewFormatData($request);

        /** @var \App\Models\User $baseUser */
        $baseUser = $data['baseUser'];
        $authUser = $request->user();
        $from = $data['from'];
        $to = $data['to'];
        $rows = $data['rows'];
        $hasMemberFilter = $data['hasMemberFilter'];

        $memberName = $hasMemberFilter
            ? strtoupper($baseUser->full_name ?: $baseUser->name)
            : 'ALL';

        $periodLabel = $this->performancePeriodLabel($from, $to);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('New Format');

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(32);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(22);

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', "LAPORAN PERFORMANCE {$memberName}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', $periodLabel);
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 4;
        $headers = ['NO.', 'NAMA HP', 'SALES ORDER', 'NAMA CUSTOMER', 'UNIT', 'PARTIAL', 'TANGGAL KEY-IN', 'STATUS', 'TANGGAL INSTALASI/TU'];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE5E7EB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF111827'],
                ],
            ],
        ]);

        $row = $headerRow + 1;
        $no = 1;
        $totalPartial = 0;
        $totalSelesai = 0;
        $totalDitinjauUlang = 0;
        $totalCancelled = 0;
        $totalUnit = 0;

        foreach ($rows as $r) {
            $isPartial = ($r->payment_method ?? '') === 'partial';
            $status = (string) ($r->status ?? '');
            $remarks = mb_strtolower((string) ($r->remarks ?? ''));
            $unitCount = (int) ($r->unit_count ?? 0);

            $totalPartial += $isPartial ? 1 : 0;
            $totalSelesai += $status === 'selesai' ? $unitCount : 0;
            $totalDitinjauUlang += str_contains($remarks, 'ditinjau ulang') ? $unitCount : 0;
            $totalCancelled += $status === 'dibatalkan' ? $unitCount : 0;
            $totalUnit += $unitCount;

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $r->hp_name);
            $sheet->setCellValue("C{$row}", $r->item_order_no ?: '-');
            $sheet->setCellValue("D{$row}", $r->customer_name);
            $sheet->setCellValue("E{$row}", $r->unit_label);
            $sheet->setCellValue("F{$row}", $isPartial ? 'YES' : '');
            $sheet->setCellValue("G{$row}", $r->key_in_at ? Carbon::parse($r->key_in_at)->format('n/j/Y') : '-');
            $sheet->setCellValue("H{$row}", $this->newFormatStatusLabel($status, $remarks));
            $sheet->setCellValue("I{$row}", $r->install_date ? Carbon::parse($r->install_date)->format('n/j/Y') : '');

            $sheet->getStyle("A{$row}:I{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB($this->newFormatStatusColor($status));

            $row++;
            $no++;
        }

        if ($row > $headerRow + 1) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":I" . ($row - 1))->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF111827'],
                    ],
                ],
            ]);

            $sheet->getStyle("A" . ($headerRow + 1) . ":A" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F" . ($headerRow + 1) . ":I" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $summaryStart = $row + 1;
        $sheet->mergeCells("A{$summaryStart}:I{$summaryStart}");
        $sheet->setCellValue("A{$summaryStart}", 'SUMMARY');
        $sheet->getStyle("A{$summaryStart}:I{$summaryStart}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF111827'],
                ],
            ],
        ]);

        $summaryRows = [
            ['TOTAL PARTIAL', $totalPartial],
            ['TOTAL SELESAI', $totalSelesai],
            ['TOTAL DITINJAU ULANG', $totalDitinjauUlang],
            ['TOTAL CANCELLED', $totalCancelled],
            ['TOTAL UNIT', $totalUnit],
        ];

        $summaryRow = $summaryStart + 1;
        foreach ($summaryRows as [$label, $value]) {
            $sheet->mergeCells("A{$summaryRow}:E{$summaryRow}");
            $sheet->mergeCells("F{$summaryRow}:I{$summaryRow}");
            $sheet->setCellValue("A{$summaryRow}", $label);
            $sheet->setCellValue("F{$summaryRow}", $value);
            $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF111827'],
                    ],
                ],
            ]);
            $summaryRow++;
        }

        $fileName = $this->performanceNewFormatFileName($authUser, $from, $to);
        $tmpPath = storage_path('app/' . Str::uuid()->toString() . '.xlsx');

        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->buildPerformanceData($request);

        /** @var \App\Models\User $baseUser */
        $baseUser = $data['baseUser'];
        $roleName = $baseUser->roles->pluck('name')->first();
        $userName = strtoupper($baseUser->full_name ?: $baseUser->name);

        $roleMap = [
            'Health Manager' => 'HM',
            'Health Planner' => 'HP',
            'Sales Manager'  => 'SM',
            'Admin'          => 'Admin',
            'Head Admin'     => 'Head Admin',
        ];

        $rolePrefix = $roleMap[$roleName] ?? $roleName ?? '';
        $title = $data['hasMemberFilter']
            ? trim("Team {$rolePrefix} {$userName}")
            : 'Team Performance All';
        $fileName = $this->performanceExportFileName($data['hasMemberFilter'], $baseUser, $data['from'], $data['to'], 'pdf');

        return view('performances.export-pdf', [
            'title' => $title,
            'fileName' => $fileName,
            'from' => $data['from'],
            'to' => $data['to'],
            'summary' => $data['summary'],
            'teamSheetRows' => $data['teamSheetRows'],
        ]);
    }

    private function performanceExportFileName(bool $hasMemberFilter, User $baseUser, string $from, string $to, string $extension): string
    {
        $memberName = $hasMemberFilter
            ? trim((string) ($baseUser->full_name ?: $baseUser->name))
            : 'ALL';

        $memberName = preg_replace('/[\/\\\\:*?"<>|]+/', '-', $memberName) ?: 'ALL';
        $dateRange = "{$from} sd {$to}";

        return "Team Performance - {$memberName} - {$dateRange}.{$extension}";
    }

    private function buildPerformanceNewFormatData(Request $request): array
    {
        $authUser = $request->user();
        $isAdminOrHead = $authUser->hasAnyRole(['Admin', 'Head Admin']);

        $authDownlineIds = $authUser->downlineUserIds();
        $allowedIds = $isAdminOrHead
            ? User::query()->pluck('id')
            : $authDownlineIds->push($authUser->id)->unique()->values();

        $memberId = (int) $request->get('member_id', 0);
        $hasMemberFilter = $memberId && $allowedIds->contains($memberId);

        $baseUser = $authUser;
        if ($hasMemberFilter) {
            $baseUser = User::query()->whereKey($memberId)->first() ?? $authUser;
        }

        if ($isAdminOrHead && !$hasMemberFilter) {
            $scopeUserIds = User::query()->pluck('id');
        } else {
            $scopeUserIds = $baseUser->downlineUserIds()->push($baseUser->id)->unique()->values();
        }

        [$from, $to, $isManual] = $this->normalizeDateRange(
            $request->get('from'),
            $request->get('to')
        );

        $cutoff = PerformanceCutoff::current();
        $defaultFrom = $cutoff?->start_date ?? Carbon::now()->startOfMonth()->subMonth()->toDateString();
        $defaultTo   = $cutoff?->end_date   ?? Carbon::now()->endOfMonth()->addMonth()->toDateString();

        if (!$isManual) {
            $from = $defaultFrom;
            $to   = $defaultTo;
        }

        $manualDateRange = (
            $request->filled('from') || $request->filled('to')
        ) && (
            $from !== $defaultFrom || $to !== $defaultTo
        );

        $unitCountExpr = "
            COALESCE(SUM(
                CASE
                    WHEN p.type = 'bundle' THEN soi.qty * COALESCE(bi.qty, 1)
                    ELSE soi.qty
                END
            ), 0) as unit_count
        ";

        $q = DB::table('sales_orders as so')
            ->join('users as u', 'u.id', '=', 'so.sales_user_id')
            ->join('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->leftJoin('customers as c', function ($j) {
                $j->on('c.id', '=', 'so.customer_id')->whereNull('c.deleted_at');
            })
            ->whereNull('so.deleted_at')
            ->whereIn('so.sales_user_id', $scopeUserIds);

        $this->applyPerformanceScopeFilter($q, $from, $to, !$manualDateRange, true);

        $rows = $q
            ->select([
                'soi.id as item_id',
                'soi.order_no as item_order_no',
                DB::raw("COALESCE(NULLIF(u.full_name,''), u.name) as hp_name"),
                DB::raw("COALESCE(c.full_name, '-') as customer_name"),
                DB::raw("COALESCE(NULLIF(p.model,''), NULLIF(p.product_name,''), NULLIF(p.sku,''), '-') as unit_label"),
                'so.payment_method',
                'so.key_in_at',
                'so.status',
                'so.install_date',
                DB::raw("
                    COALESCE(
                        NULLIF(so.payment_method_remarks,''),
                        NULLIF(so.ccp_remarks,''),
                        NULLIF(so.status_reason,''),
                        '-'
                    ) as remarks
                "),
            ])
            ->selectRaw($unitCountExpr)
            ->groupBy(
                'soi.id',
                'soi.order_no',
                'u.full_name',
                'u.name',
                'c.full_name',
                'p.model',
                'p.product_name',
                'p.sku',
                'so.payment_method',
                'so.key_in_at',
                'so.status',
                'so.install_date',
                'so.payment_method_remarks',
                'so.ccp_remarks',
                'so.status_reason'
            )
            ->orderBy('u.name')
            ->orderBy('so.key_in_at')
            ->orderBy('soi.id')
            ->get();

        return [
            'baseUser' => $baseUser,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'hasMemberFilter' => $hasMemberFilter,
        ];
    }

    private function performancePeriodLabel(string $from, string $to): string
    {
        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        if ($fromDate->isSameMonth($toDate)) {
            return $fromDate->format('F Y');
        }

        return "{$from} s/d {$to}";
    }

    private function newFormatStatusLabel(string $status, string $remarks): string
    {
        if (str_contains($remarks, 'ditinjau ulang')) {
            return 'DITINJAU ULANG';
        }

        return match ($status) {
            'selesai' => 'SELESAI',
            'dibatalkan' => 'CANCELLED',
            'dijadwalkan' => 'DIJADWALKAN',
            'menunggu jadwal' => 'MENUNGGU JADWAL',
            'menunggu verifikasi' => 'MENUNGGU VERIFIKASI',
            'ditunda' => 'DITUNDA',
            'gagal penelponan' => 'GAGAL PENELPONAN',
            'tinjau ulang' => 'TINJAU ULANG',
            default => strtoupper($status ?: '-'),
        };
    }

    private function newFormatStatusColor(string $status): string
    {
        return match ($status) {
            'menunggu verifikasi', 'menunggu jadwal' => 'FFD9D9D9',
            'dijadwalkan' => 'FF9FC5E8',
            'dibatalkan' => 'FFE06666',
            'ditunda', 'gagal penelponan' => 'FFF6B26B',
            'tinjau ulang' => 'FFFFE599',
            'selesai' => 'FFA9D18E',
            default => 'FFFFFFFF',
        };
    }

    private function performanceNewFormatFileName(User $authUser, string $from, string $to): string
    {
        $userName = trim((string) ($authUser->full_name ?: $authUser->name));
        $userName = preg_replace('/[\/\\\\:*?"<>|]+/', '-', $userName) ?: 'User';

        $monthName = strtoupper($this->performancePeriodLabel($from, $to));

        return "Laporan Performance - {$userName} - {$monthName}.xlsx";
    }

    public function updateCutoff(Request $request)
    {
        $request->validate([
            'cutoff_start' => ['required', 'date'],
            'cutoff_end'   => ['required', 'date', 'after_or_equal:cutoff_start'],
        ]);

        PerformanceCutoff::create([
            'start_date' => $request->cutoff_start,
            'end_date'   => $request->cutoff_end,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('performance.index')->with('success', 'Cut off performance berhasil disimpan.');
    }

    /**
     * Scope data performance:
     * 1. key_in_at dalam periode
     * 2. atau status selesai + install_date dalam periode
     * 3. atau ccp_approved_at dalam periode (khusus Team Sheet)
     * 4. atau carry over recurring dari periode sebelumnya (khusus cutoff mode)
     */
    private function applyPerformanceScopeFilter(
        $q,
        string $from,
        string $to,
        bool $withCarryOver = true,
        bool $includeApprovalAndUpdateDates = false
    ): void
    {
        $carryFrom = Carbon::parse($from)->subMonthNoOverflow()->toDateString();

        $q->where(function ($w) use ($from, $to, $carryFrom, $withCarryOver, $includeApprovalAndUpdateDates) {
            // A. SO key-in dalam periode
            $w->where(function ($a) use ($from, $to) {
                $a->whereNotNull('so.key_in_at')
                    ->whereDate('so.key_in_at', '>=', $from)
                    ->whereDate('so.key_in_at', '<=', $to);
            });

            // B. SO selesai dan install dalam periode
            $w->orWhere(function ($a) use ($from, $to) {
                $a->where('so.status', 'selesai')
                    ->whereNotNull('so.install_date')
                    ->whereDate('so.install_date', '>=', $from)
                    ->whereDate('so.install_date', '<=', $to);
            });

            // C. SO yang approval CCP masuk periode Team Sheet
            if ($includeApprovalAndUpdateDates) {
                $w->orWhere(function ($a) use ($from, $to) {
                    $a->whereNotNull('so.ccp_approved_at')
                        ->whereDate('so.ccp_approved_at', '>=', $from)
                        ->whereDate('so.ccp_approved_at', '<=', $to);
                });
            }

            // D. Carry over dari periode sebelumnya
            if ($withCarryOver) {
                $w->orWhere(function ($x) use ($carryFrom, $from) {
                    $x->whereNotNull('so.key_in_at')
                        ->whereDate('so.key_in_at', '>=', $carryFrom)
                        ->whereDate('so.key_in_at', '<', $from)
                        ->where(function ($carry) {
                            $carry
                                ->where(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'menunggu pengecekan')
                                        ->where('so.status', 'menunggu verifikasi');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->where('so.status', 'menunggu jadwal');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->where('so.status', 'dijadwalkan');
                                })
                                ->orWhere(function ($a) {
                                    $a->whereRaw('COALESCE(so.is_recurring,0) = 1')
                                        ->where('so.ccp_status', 'disetujui')
                                        ->whereIn('so.status', ['ditunda', 'gagal penelponan', 'tinjau ulang']);
                                });
                        });
                });
            }
        });
    }

    private function normalizeDateRange(?string $from, ?string $to): array
    {
        $from = trim((string) $from);
        $to   = trim((string) $to);

        $from = $from !== '' ? $from : null;
        $to   = $to !== '' ? $to : null;

        $isManual = (bool) ($from || $to);

        if ($isManual) {
            if ($from && !$to) $to = Carbon::parse($from)->endOfMonth()->toDateString();
            if (!$from && $to) $from = Carbon::parse($to)->startOfMonth()->toDateString();
        }

        return [$from, $to, $isManual];
    }

    private function applyManualDateFilter($q, string $from, string $to): void
    {
        $this->applyPerformanceScopeFilter($q, $from, $to, false);
    }

    private function buildRoadToHmData(User $user): array
    {
        Carbon::setLocale('id');

        $targetPersonal = 3;
        $targetTeam = 30;
        $targetActiveHp = 5;

        $now = Carbon::now()->startOfMonth();

        $historyStart = $now->copy()->subMonthsNoOverflow(12)->startOfMonth();
        $historyEnd   = $now->copy()->addMonthsNoOverflow(4)->endOfMonth();

        $downlineIds = $user->downlineUserIds()->unique()->values();

        $downlines = User::query()
            ->whereIn('id', $downlineIds)
            ->where('status', 'Active')
            ->orderByRaw("COALESCE(NULLIF(full_name,''), name) asc")
            ->get(['id', 'name', 'full_name']);

        $trackedUserIds = $downlines->pluck('id')->push($user->id)->unique()->values();

        $unitsExpr = "
            COALESCE(SUM(
                CASE
                    WHEN p.type = 'bundle' THEN soi.qty * bi.qty
                    ELSE soi.qty
                END
            ), 0)
        ";

        $monthlyUnitsRaw = DB::table('sales_orders as so')
            ->leftJoin('sales_order_items as soi', 'soi.sales_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'soi.product_id')
            ->leftJoin('bundle_items as bi', 'bi.bundle_id', '=', 'p.id')
            ->whereNull('so.deleted_at')
            ->where('so.status', 'selesai')
            ->whereIn('so.sales_user_id', $trackedUserIds)
            ->whereNotNull('so.install_date')
            ->whereDate('so.install_date', '>=', $historyStart->toDateString())
            ->whereDate('so.install_date', '<=', $historyEnd->toDateString())
            ->selectRaw("
                so.sales_user_id,
                DATE_FORMAT(so.install_date, '%Y-%m-01') as month_key,
                {$unitsExpr} as units
            ")
            ->groupBy('so.sales_user_id', DB::raw("DATE_FORMAT(so.install_date, '%Y-%m-01')"))
            ->get();

        $unitsByUserMonth = [];
        foreach ($monthlyUnitsRaw as $row) {
            $unitsByUserMonth[$row->sales_user_id][$row->month_key] = (int) $row->units;
        }

        $activeHpIds = User::query()
            ->whereIn('id', $downlineIds)
            ->role('Health Planner')
            ->pluck('id')
            ->values();

        $getPersonalAch = function (string $monthKey) use ($unitsByUserMonth, $user) {
            return (int) ($unitsByUserMonth[$user->id][$monthKey] ?? 0);
        };

        $getTeamAch = function (string $monthKey) use ($trackedUserIds, $unitsByUserMonth) {
            $total = 0;
            foreach ($trackedUserIds as $uid) {
                $total += (int) ($unitsByUserMonth[$uid][$monthKey] ?? 0);
            }
            return $total;
        };

        $getActiveHpAch = function (string $monthKey) use ($activeHpIds, $unitsByUserMonth) {
            $count = 0;
            foreach ($activeHpIds as $uid) {
                if ((int) ($unitsByUserMonth[$uid][$monthKey] ?? 0) > 0) {
                    $count++;
                }
            }
            return $count;
        };

        $historyMonths = collect();
        $cursor = $historyStart->copy();

        while ($cursor->lte($now)) {
            $monthKey = $cursor->format('Y-m-01');

            $personalAch = $getPersonalAch($monthKey);
            $teamAch = $getTeamAch($monthKey);
            $activeHpAch = $getActiveHpAch($monthKey);

            $personalPassed = $personalAch >= $targetPersonal;
            $teamPassed = $teamAch >= $targetTeam;
            $activeHpPassed = $activeHpAch >= $targetActiveHp;

            $historyMonths->push([
                'key' => $monthKey,
                'date' => $cursor->copy(),
                'label' => ucfirst($cursor->translatedFormat('F')),
                'personal_ach' => $personalAch,
                'team_ach' => $teamAch,
                'active_hp_ach' => $activeHpAch,
                'personal_passed' => $personalPassed,
                'team_passed' => $teamPassed,
                'active_hp_passed' => $activeHpPassed,
                'all_passed' => $personalPassed && $teamPassed && $activeHpPassed,
            ]);

            $cursor->addMonthNoOverflow();
        }

        $lastFailedIndex = null;
        foreach ($historyMonths as $index => $month) {
            if (!$month['all_passed']) {
                $lastFailedIndex = $index;
            }
        }

        if ($lastFailedIndex === null) {
            $activeStartMonth = $historyMonths->last()['date']->copy();
            foreach ($historyMonths as $month) {
                if ($month['all_passed']) {
                    $activeStartMonth = $month['date']->copy();
                    break;
                }
            }
        } else {
            $failedMonthDate = $historyMonths[$lastFailedIndex]['date']->copy();
            $activeStartMonth = $failedMonthDate->copy()->addMonthNoOverflow();

            if ($activeStartMonth->gt($now)) {
                $activeStartMonth = $now->copy();
            }
        }

        $months = collect(range(0, 3))->map(function ($i) use ($activeStartMonth) {
            $m = $activeStartMonth->copy()->addMonthsNoOverflow($i);

            return [
                'key' => $m->format('Y-m-01'),
                'label' => ucfirst($m->translatedFormat('F')),
                'start' => $m->copy()->startOfMonth()->toDateString(),
                'end' => $m->copy()->endOfMonth()->toDateString(),
            ];
        })->values();

        $month5 = $activeStartMonth->copy()->addMonthsNoOverflow(4);
        $month5Label = ucfirst($month5->translatedFormat('F'));

        $personalMonths = [];
        $teamMonths = [];
        $activeHpMonths = [];

        $monthPassedMap = [];

        foreach ($months as $m) {
            $monthKey = $m['key'];

            $personalAch = $getPersonalAch($monthKey);
            $teamAch = $getTeamAch($monthKey);
            $activeHpAch = $getActiveHpAch($monthKey);

            $personalPassed = $personalAch >= $targetPersonal;
            $teamPassed = $teamAch >= $targetTeam;
            $activeHpPassed = $activeHpAch >= $targetActiveHp;

            $monthPassedMap[$monthKey] = $personalPassed && $teamPassed && $activeHpPassed;

            $personalMonths[$monthKey] = [
                'ach' => $personalAch,
                'shrt' => max($targetPersonal - $personalAch, 0),
                'passed' => $personalPassed,
            ];

            $teamMonths[$monthKey] = [
                'ach' => $teamAch,
                'shrt' => max($targetTeam - $teamAch, 0),
                'passed' => $teamPassed,
            ];

            $activeHpMonths[$monthKey] = [
                'ach' => $activeHpAch,
                'shrt' => max($targetActiveHp - $activeHpAch, 0),
                'passed' => $activeHpPassed,
            ];
        }

        foreach ($months as $m) {
            $monthKey = $m['key'];
            $isGreen = $monthPassedMap[$monthKey] ?? false;

            $personalMonths[$monthKey]['is_green'] = $isGreen;
            $teamMonths[$monthKey]['is_green'] = $isGreen;
            $activeHpMonths[$monthKey]['is_green'] = $isGreen;
        }

        $rows = [
            [
                'label' => 'Personal',
                'target' => $targetPersonal,
                'months' => $personalMonths,
            ],
            [
                'label' => 'Team',
                'target' => $targetTeam,
                'months' => $teamMonths,
            ],
            [
                'label' => 'Active HP',
                'target' => $targetActiveHp,
                'months' => $activeHpMonths,
            ],
        ];

        $allFourMonthsPassed = true;
        foreach ($months as $m) {
            $monthKey = $m['key'];

            $personalPassed = $personalMonths[$monthKey]['passed'] ?? false;
            $teamPassed = $teamMonths[$monthKey]['passed'] ?? false;
            $activeHpPassed = $activeHpMonths[$monthKey]['passed'] ?? false;

            if (!($personalPassed && $teamPassed && $activeHpPassed)) {
                $allFourMonthsPassed = false;
                break;
            }
        }

        return [
            'months' => $months,
            'month5' => $month5Label,
            'rows' => $rows,
            'congrats_green' => $allFourMonthsPassed,
            'targets' => [
                'personal' => $targetPersonal,
                'team' => $targetTeam,
                'active_hp' => $targetActiveHp,
            ],
        ];
    }
}
