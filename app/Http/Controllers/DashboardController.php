<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\UserHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // STAT CARDS (Overview)
        // =========================================================

        // ambil semua bawahan multi-level + diri sendiri
        $descendantIds = $this->getAllDescendantUserIds((int) $user->id);
        $scopeUserIds = array_values(array_unique(array_merge([(int) $user->id], $descendantIds)));

        // 1) Total unit terjual (SO selesai)
        $totalUnitsSold = (int) SalesOrder::query()
            ->whereIn('sales_orders.sales_user_id', $scopeUserIds)
            ->where('sales_orders.status', 'selesai')
            ->join('sales_order_items', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->sum('sales_order_items.qty');

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

        return view('dashboard', compact(
            'soDeactivationWarnings',
            'selfWarning',
            'totalUnitsSold',
            'totalRegularProducts',
            'totalBundlings',
            'totalActiveDownline',
            'trend',
            'salesTrendLabels',
            'salesTrendUnits'
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
