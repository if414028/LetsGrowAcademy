<?php

namespace App\Http\Controllers;

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

            // ✅ join hierarchy untuk ambil parent (Health Manager) dari HP
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
        // ✅ Scope by role
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

        return view('dashboard', compact('soDeactivationWarnings', 'selfWarning'));
    }
}
