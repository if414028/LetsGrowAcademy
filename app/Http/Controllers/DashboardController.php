<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Contest;
use Carbon\Carbon;
use App\Models\PerformanceCutoff;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // window warning: 5-6 bulan
        $warnTo   = now()->subMonths(5);
        $warnFrom = now()->subMonths(6);

        $lastSoSub = SalesOrder::query()
            ->select('sales_user_id', DB::raw('MAX(key_in_at) as last_so_at'))
            ->groupBy('sales_user_id');

        $warningsQuery = User::query()
            ->role('Health Planner')
            ->where('users.status', 'Active')
            ->leftJoinSub($lastSoSub, 'so', function ($join) {
                $join->on('so.sales_user_id', '=', 'users.id');
            })

            // join hierarchy untuk ambil parent (Health Manager) dari HP
            ->leftJoin('user_hierarchies as uh', 'uh.child_user_id', '=', 'users.id')
            ->leftJoin('users as hm', 'hm.id', '=', 'uh.parent_user_id')

            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.dst_code',
                DB::raw('COALESCE(so.last_so_at, users.created_at) as last_activity_at'),
                DB::raw('hm.name as health_manager_name'),
            ])
            ->whereRaw(
                'COALESCE(so.last_so_at, users.created_at) <= ? AND COALESCE(so.last_so_at, users.created_at) > ?',
                [$warnTo, $warnFrom]
            )
            ->orderByRaw('COALESCE(so.last_so_at, users.created_at) asc');

        // =========================
        // Scope by role
        // =========================
        if ($user->hasRole('Sales Manager')) {
            // ambil Health Manager yang direct child dari Sales Manager
            $healthManagerIds = $user->childrenUsers()
                ->role('Health Manager')
                ->pluck('users.id');

            // ambil Health Planner yang parent nya adalah HM di atas
            $hpIds = UserHierarchy::query()
                ->whereIn('parent_user_id', $healthManagerIds)
                ->pluck('child_user_id');

            $warningsQuery->whereIn('users.id', $hpIds);
        } elseif ($user->hasRole('Health Manager')) {
            // bawahan langsung HM (HP)
            $childHpIds = $user->childrenUsers()
                ->role('Health Planner')
                ->pluck('users.id');

            $warningsQuery->whereIn('users.id', $childHpIds);

            // supaya nama HM di tabel selalu konsisten
            $warningsQuery->addSelect(DB::raw("'" . addslashes($user->name) . "' as health_manager_name"));
        } elseif ($user->hasRole('Health Planner')) {
            $warningsQuery->where('users.id', $user->id);
        } elseif (!$user->hasRole('Admin')) {
            $warningsQuery->whereRaw('1=0');
        }

        $soDeactivationWarnings = $warningsQuery->get()->map(function ($u) {
            $u->last_activity_at = \Carbon\Carbon::parse($u->last_activity_at);
            $u->deactivate_at = $u->last_activity_at->copy()->addMonths(6);
            return $u;
        });

        $selfWarning = $user->hasRole('Health Planner')
            ? $soDeactivationWarnings->first()
            : null;

        // =========================================================
        // CUTOFF WINDOW (untuk semua stat card berbasis Sales Order)
        // =========================================================
        $cutoff = PerformanceCutoff::query()
            ->orderByDesc('start_date')   // atau kolom lain yang kamu pakai
            ->first();

        $cutoffStart = $cutoff
            ? Carbon::parse($cutoff->start_date)->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $cutoffEnd = $cutoff
            ? Carbon::parse($cutoff->end_date)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // =========================================================
        // STAT CARDS (Overview)
        // =========================================================

        $isAdminOrHead = $user->hasAnyRole(['Admin', 'Head Admin']);
        $descendantIds = $this->getAllDescendantUserIds((int) $user->id);
        $scopeUserIds = array_values(array_unique(array_merge([(int) $user->id], $descendantIds)));

        // ambil semua bawahan multi-level + diri sendiri
        $descendantIds = $this->getAllDescendantUserIds((int) $user->id);
        $scopeUserIds = array_values(array_unique(array_merge([(int) $user->id], $descendantIds)));

        // 1) Total unit terjual (SO selesai)
        $totalUnitsSold = (int) SalesOrder::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('sales_orders.sales_user_id', $scopeUserIds))
            ->where('sales_orders.status', 'selesai')
            ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->sum('sales_order_items.qty');

        // 1a) Total Penjualan Individu (units) - SO selesai
        $totalSalesIndividu = (int) SalesOrder::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('sales_orders.sales_user_id', $scopeUserIds))
            ->where('sales_orders.status', 'selesai')
            ->where('sales_orders.customer_type', 'individu')
            ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->sum('sales_order_items.qty');

        // 1b) Total Penjualan Corporate (units) - SO selesai
        $totalSalesCorporate = (int) SalesOrder::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('sales_orders.sales_user_id', $scopeUserIds))
            ->where('sales_orders.status', 'selesai')
            ->where('sales_orders.customer_type', 'corporate')
            ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->sum('sales_order_items.qty');

        // 1c) Total Penjualan Produk Satuan (units) - SO selesai
        $totalSalesProductSatuan = (int) SalesOrder::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('sales_orders.sales_user_id', $scopeUserIds))
            ->where('sales_orders.status', 'selesai')
            ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->where('products.type', 'regular')
            ->sum('sales_order_items.qty');

        // 1d) Total Penjualan Produk Bundling - SO selesai
        // NOTE: sesuai request: "bundling itung 1 saja untuk 1 bundling" => hitung per item bundling, bukan qty
        $totalSalesProductBundling = (int) SalesOrder::query()
            ->when(!$isAdminOrHead, fn($q) => $q->whereIn('sales_orders.sales_user_id', $scopeUserIds))
            ->where('sales_orders.status', 'selesai')
            ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->join('products as bundle', 'bundle.id', '=', 'sales_order_items.product_id')
            ->where('bundle.type', 'bundle')
            ->join('bundle_items as bi', 'bi.bundle_id', '=', 'bundle.id')
            ->selectRaw('COALESCE(SUM(sales_order_items.qty * bi.qty), 0) as total_units')
            ->value('total_units');

        // 2) Total produk reguler aktif
        $totalRegularProducts = (int) Product::query()
            ->where('type', 'regular')
            ->where('is_active', 1)
            ->count();

        // 3) Total bundling aktif
        $totalBundlings = (int) Product::query()
            ->where('type', 'bundle')
            ->where('is_active', 1)
            ->count();

        // 4) Total downline aktif (semua bawahan aktif, multi-level)
        $totalActiveDownline = User::query()
            ->whereIn('id', $descendantIds)   // hanya bawahan, bukan diri sendiri
            ->where('status', 'Active')
            ->count();

        // 5) Total Health Manager aktif (hanya tampil untuk SM, Admin, Head Admin)
        $totalActiveHealthManagers = 0;

        if ($user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin'])) {

            if ($user->hasRole('Sales Manager')) {
                // HM direct child dari SM
                $hmIds = $user->childrenUsers()
                    ->role('Health Manager')
                    ->pluck('users.id');

                $totalActiveHealthManagers = User::query()
                    ->whereIn('id', $hmIds)
                    ->where('status', 'Active')
                    ->count();
            } else {
                // Admin & Head Admin lihat semua HM aktif
                $totalActiveHealthManagers = User::query()
                    ->role('Health Manager')
                    ->where('status', 'Active')
                    ->count();
            }
        }

        // 6) Total Health Planner (Aktif Bulan Ini)
        $monthStart = now()->startOfMonth();
        $monthEnd   = now()->endOfMonth();

        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            // ✅ Admin/Head Admin: tampilkan total semua HP aktif (tanpa filter downline & tanpa syarat SO)
            $totalActiveHealthPlannersThisMonth = (int) User::query()
                ->role('Health Planner')
                ->where('users.status', 'Active')
                ->count();
        } else {
            // ✅ selain Admin/Head: hanya HP di bawah user (multi-level) yang membuat minimal 1 SO bulan ini
            $totalActiveHealthPlannersThisMonth = (int) User::query()
                ->role('Health Planner')
                ->where('users.status', 'Active')
                ->whereIn('users.id', $scopeUserIds) // scope sudah include diri sendiri + downline
                ->whereExists(function ($sub) use ($monthStart, $monthEnd) {
                    $sub->select(DB::raw(1))
                        ->from('sales_orders')
                        ->whereColumn('sales_orders.sales_user_id', 'users.id')
                        ->whereBetween('sales_orders.key_in_at', [$monthStart, $monthEnd]);
                    // kalau mau hanya SO selesai, tambahkan:
                    // ->where('sales_orders.status', 'selesai');
                })
                ->distinct()
                ->count();
        }

        // =========================================================
        // HM PERFORMANCE TABLE (HM + Team Units)
        // tampil untuk: SM, Admin, Head Admin
        // =========================================================
        $healthManagerPerformance = collect();

        if ($user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin'])) {

            // ambil list HM yang ditampilkan
            if ($user->hasRole('Sales Manager')) {
                // HM direct child dari SM
                $healthManagers = $user->childrenUsers()
                    ->role('Health Manager')
                    ->where('users.status', 'Active')
                    ->select('users.id', 'users.name', 'users.email', 'users.dst_code')
                    ->get();
            } else {
                // Admin / Head Admin: semua HM aktif
                $healthManagers = User::query()
                    ->role('Health Manager')
                    ->where('status', 'Active')
                    ->select('id', 'name', 'email', 'dst_code')
                    ->get();
            }

            // hitung total units (SO selesai) untuk tiap HM + tim multi-level
            $healthManagerPerformance = $healthManagers->map(function ($hm) use ($cutoffStart, $cutoffEnd) {

                $descendantIds = $this->getAllDescendantUserIds((int) $hm->id);
                $scopeIds = array_values(array_unique(array_merge([(int) $hm->id], $descendantIds)));

                $units = (int) SalesOrder::query()
                    ->whereIn('sales_orders.sales_user_id', $scopeIds)
                    ->where('sales_orders.status', 'selesai')
                    ->whereBetween('sales_orders.key_in_at', [$cutoffStart, $cutoffEnd])
                    ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                    ->sum('sales_order_items.qty');

                return (object) [
                    'id' => (int) $hm->id,
                    'name' => $hm->name,
                    'email' => $hm->email,
                    'dst_code' => $hm->dst_code,
                    'team_size' => count($descendantIds),
                    'units' => $units,
                ];
            })->sortByDesc('units')->values();
        }

        // =========================================================
        // SALES TREND (Weekly / Monthly)
        // =========================================================

        $trend = $request->string('trend')->toString() ?: 'weekly';
        if (!in_array($trend, ['weekly', 'monthly'], true)) {
            $trend = 'weekly';
        }

        $salesTrendLabels = [];
        $salesTrendUnits  = [];

        if ($trend === 'weekly') {
            // last 8 weeks
            $weeks = 8;
            $end = now()->endOfWeek();
            $start = now()->startOfWeek()->subWeeks($weeks - 1);

            $rawWeekly = SalesOrder::query()
                ->whereIn('sales_orders.sales_user_id', $scopeUserIds)
                ->where('sales_orders.status', 'selesai')
                ->whereBetween('sales_orders.key_in_at', [$start, $end])
                ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                ->selectRaw('YEARWEEK(sales_orders.key_in_at, 3) as yw, SUM(sales_order_items.qty) as units')
                ->groupBy('yw')
                ->pluck('units', 'yw'); // [yw => units]

            $cursor = $start->copy();
            for ($i = 0; $i < $weeks; $i++) {
                // YEARWEEK(mode 3) bentuknya: 202605 (ISO year+week)
                $key = (int) $cursor->format('oW');
                $salesTrendLabels[] = $cursor->format('d M'); // label start of week
                $salesTrendUnits[]  = (int) ($rawWeekly[$key] ?? 0);
                $cursor->addWeek();
            }
        } else {
            // monthly: last 6 months
            $months = 6;
            $end = now()->endOfMonth();
            $start = now()->startOfMonth()->subMonths($months - 1);

            $rawMonthly = SalesOrder::query()
                ->whereIn('sales_orders.sales_user_id', $scopeUserIds)
                ->where('sales_orders.status', 'selesai')
                ->whereBetween('sales_orders.key_in_at', [$start, $end])
                ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
                ->selectRaw("DATE_FORMAT(sales_orders.key_in_at, '%Y-%m') as ym, SUM(sales_order_items.qty) as units")
                ->groupBy('ym')
                ->pluck('units', 'ym'); // [ym => units]

            $cursor = $start->copy();
            for ($i = 0; $i < $months; $i++) {
                $key = $cursor->format('Y-m');
                $salesTrendLabels[] = $cursor->format('M Y');
                $salesTrendUnits[]  = (int) ($rawMonthly[$key] ?? 0);
                $cursor->addMonth();
            }
        }

        // =========================================================
        // ACTIVE CONTEST LIST (Kontes berlangsung sesuai rules final)
        // =========================================================
        $now = now();

        $activeContestsQuery = Contest::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now);

        // Admin & Head Admin: lihat semua kontes berlangsung
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            // no extra filter
        }
        // Sales Manager: lihat kontes berlangsung yang dia buat atau yang target HM-nya bawahannya
        elseif ($user->hasRole('Sales Manager')) {

            $hmIds = $user->childrenUsers()
                ->role('Health Manager')
                ->pluck('users.id')
                ->map(fn($v) => (int) $v)
                ->all();

            $activeContestsQuery->where(function ($w) use ($user, $hmIds) {
                // kontes yang dia buat
                $w->where('created_by_user_id', $user->id);

                // kontes target HM bawahannya
                $w->orWhere(function ($w2) use ($hmIds) {
                    if (empty($hmIds)) {
                        $w2->whereRaw('1=0');
                        return;
                    }
                    $w2->where(function ($w3) use ($hmIds) {
                        foreach ($hmIds as $hmId) {
                            $w3->orWhereJsonContains('rules->target_hm_ids', $hmId);
                        }
                    });
                });
            });
        }
        // Health Manager: lihat kontes berlangsung yang dia buat atau yang menargetkan HM ini
        elseif ($user->hasRole('Health Manager')) {
            $activeContestsQuery->where(function ($w) use ($user) {
                $w->where('created_by_user_id', $user->id)
                    ->orWhereJsonContains('rules->target_hm_ids', (int) $user->id);
            });
        }
        // Health Planner / role lain: hanya kontes berlangsung yang dia participant
        else {
            $activeContestsQuery->whereHas('participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

            // (opsional) kalau suatu saat HP bisa create kontes:
            // $activeContestsQuery->orWhere('created_by_user_id', $user->id);
        }

        $activeContests = $activeContestsQuery
            ->orderBy('end_date') // paling dekat berakhir dulu
            ->get();

        // =========================================================
        // BIRTHDAY TODAY (Overview)
        // Rules:
        // 1) Admin & Head Admin: semua user yang ulang tahun hari ini
        // 2) SM / HM / HP: hanya downline (multi-level) yang ulang tahun hari ini
        // + jika user login ulang tahun, tampilkan greeting card khusus
        // =========================================================

        $todayMd = now()->format('m-d');

        $isBirthdayToday = false;
        if (!empty($user->date_of_birth)) {
            $isBirthdayToday = Carbon::parse($user->date_of_birth)->isBirthday();
        }

        $todayBirthdaysQuery = User::query()
            ->whereNotNull('date_of_birth')
            ->where('status', 'Active')
            ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$todayMd])
            ->with('roles') // Spatie roles
            ->select('id', 'name', 'email', 'dst_code', 'date_of_birth');

        // scope sesuai role
        if ($user->hasAnyRole(['Admin', 'Head Admin'])) {
            // no filter, lihat semua
        } else {
            // SM / HM / HP: hanya downline multi-level
            $downlineIds = $this->getAllDescendantUserIds((int) $user->id);

            if (empty($downlineIds)) {
                $todayBirthdaysQuery->whereRaw('1=0');
            } else {
                $todayBirthdaysQuery->whereIn('id', $downlineIds);
            }
        }

        // optional: urutkan nama
        $todayBirthdays = $todayBirthdaysQuery
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                $dob = Carbon::parse($u->date_of_birth);
                $u->dob_fmt = $dob->translatedFormat('d M'); // contoh: 27 Feb
                $u->age = $dob->age; // umur saat ini
                $u->role_name = $u->roles->pluck('name')->first() ?? '-';
                return $u;
            });

        return view('dashboard', compact(
            'soDeactivationWarnings',
            'selfWarning',
            'totalUnitsSold',
            'totalSalesIndividu',
            'totalSalesCorporate',
            'totalSalesProductSatuan',
            'totalSalesProductBundling',
            'totalRegularProducts',
            'totalBundlings',
            'totalActiveDownline',
            'totalActiveHealthManagers',
            'totalActiveHealthPlannersThisMonth',
            'healthManagerPerformance',
            'trend',
            'salesTrendLabels',
            'salesTrendUnits',
            'activeContests',
            'todayBirthdays',
            'isBirthdayToday',
        ));
    }

    /**
     * Ambil semua descendant (bawahan) multi-level dari tabel user_hierarchies.
     * Asumsi: user_hierarchies: parent_user_id -> child_user_id (direct).
     */
    private function getAllDescendantUserIds(int $userId): array
    {
        $visited = [];
        $queue = [$userId];

        while (!empty($queue)) {
            $parentId = array_shift($queue);

            $children = UserHierarchy::query()
                ->where('parent_user_id', $parentId)
                ->pluck('child_user_id')
                ->all();

            foreach ($children as $childId) {
                $childId = (int) $childId;

                if (isset($visited[$childId])) {
                    continue;
                }

                $visited[$childId] = true;
                $queue[] = $childId;
            }
        }

        return array_keys($visited);
    }
}
