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
            $childIds = collect(); // direct reports tidak dipakai spesifik
            $scopeUserIds = User::query()->pluck('id');
        } else {
            $childIds = $baseUser->downlineUserIds();
            $scopeUserIds = $childIds->push($baseUser->id)->unique()->values();
        }

        // ======================================
        // Date range
        // ======================================
        $q = trim((string) $request->get('q', ''));

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
        // - reset admin/head admin => semua HP
        // - filter partner => hanya downline partner terpilih
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

        if ($isManual) {
            $this->applyManualDateFilter($summaryQ, $from, $to);
        } else {
            $this->applyCutoffSoFilter($summaryQ, $from, $to);
        }

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
                    AND so.status IN ('ditunda', 'gagal penelponan')
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

        if ($isManual) {
            $this->applyManualDateFilter($sheetQ, $from, $to);
        } else {
            $this->applyCutoffSoFilter($sheetQ, $from, $to);
        }

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
                DB::raw("CASE WHEN so.ccp_status = 'disetujui' THEN so.updated_at ELSE NULL END as ccp_approved_at"),
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
            ->selectRaw("CASE WHEN DATE(so.key_in_at) < DATE(?) THEN 1 ELSE 0 END as is_carry_over", [$from])
            ->get()
            ->groupBy('hp_name');

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

        if ($isManual) {
            $this->applyManualDateFilter($summaryQ, $from, $to);
        } else {
            $this->applyCutoffSoFilter($summaryQ, $from, $to);
        }

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
                    AND so.status IN ('ditunda', 'gagal penelponan')
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

        if ($isManual) {
            $this->applyManualDateFilter($sheetQ, $from, $to);
        } else {
            $this->applyCutoffSoFilter($sheetQ, $from, $to);
        }

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
                DB::raw("
                    COALESCE(
                        NULLIF(so.payment_method_remarks,''),
                        NULLIF(so.ccp_remarks,''),
                        NULLIF(so.status_reason,''),
                        '-'
                    ) as remarks
                "),
                DB::raw("CASE WHEN so.ccp_status = 'disetujui' THEN so.updated_at ELSE NULL END as ccp_approved_at"),
                DB::raw("COALESCE(soi.ns_units, 0) as ns_units"),
            ])
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
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(42);

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', "Range: {$from} s/d {$to}");
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 4;
        $headers = ['No', 'Nama HP', 'Nama Customer', 'Tanggal Key in', 'CCP disetujui', 'Key-in', 'Install/NS', 'Tanggal Instalasi', 'Remarks'];
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
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
                $sheet->setCellValue("E{$row}", $ccpAppr ?: '');
                $sheet->setCellValue("F{$row}", "{$ns}NS");
                $sheet->setCellValue("G{$row}", ($r->status ?? '') === 'selesai' ? 'OK' : '');
                $sheet->setCellValue("H{$row}", $installDate ?: '');
                $sheet->setCellValue("I{$row}", $r->remarks ?? '-');

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

        $sheet->getStyle("A" . ($headerRow + 1) . ":I" . ($row - 1))->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF9CA3AF'],
                ],
            ],
        ]);

        $row += 2;

        $sheet->mergeCells("A{$row}:I{$row}");
        $sheet->setCellValue("A{$row}", "Summary");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        $summaryRows = [
            ['Total Key-In', (int)($summary->total_key_in ?? 0), 'FFF9FAFB'],
            ['Total Recurring', (int)($summary->total_recurring ?? 0), 'FFEFF6FF'],
            ['Dijadwalkan', (int)($summary->dijadwalkan ?? 0), 'FFFFFBEB'],
            ['Menunggu Jadwal', (int)($summary->menunggu_jadwal ?? 0), 'FFFFFBEB'],
            ['Pending', (int)($summary->pending ?? 0), 'FFFAF5FF'],
            ['Total sudah install (OK)', (int)($summary->total_sudah_install ?? 0), 'FFF0FDF4'],
        ];

        foreach ($summaryRows as [$label, $value, $bgColor]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("I{$row}", $value);
            $sheet->mergeCells("A{$row}:H{$row}");

            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE5E7EB'],
                    ],
                ],
            ]);

            $sheet->getStyle("I{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
        }

        $fileName = 'performance_' . Str::slug($title) . "_{$from}_{$to}.xlsx";
        $tmpPath = storage_path('app/' . Str::uuid()->toString() . '.xlsx');

        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
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

    private function applyCutoffSoFilter($q, string $from, string $to): void
    {
        $carryFrom = Carbon::parse($from)->subMonthNoOverflow()->toDateString();

        $q->where(function ($w) use ($from, $to, $carryFrom) {
            $w->whereDate('so.key_in_at', '>=', $from)
                ->whereDate('so.key_in_at', '<=', $to);

            $w->orWhere(function ($x) use ($carryFrom, $from) {
                $x->whereRaw('COALESCE(so.is_recurring,0) = 0')
                    ->whereDate('so.key_in_at', '>=', $carryFrom)
                    ->whereDate('so.key_in_at', '<', $from)
                    ->where('so.ccp_status', 'menunggu pengecekan');
            });
        })->whereNotNull('so.key_in_at');
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
        $q->whereDate('so.key_in_at', '>=', $from)
            ->whereDate('so.key_in_at', '<=', $to)
            ->whereNotNull('so.key_in_at');
    }
}
