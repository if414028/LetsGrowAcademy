<?php

namespace App\Console\Commands;

use App\Models\PerformanceCutoff;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\HealthManagerNsScope;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileDashboardNetSales extends Command
{
    protected $signature = 'sales:reconcile-dashboard
        {--user= : ID akun Admin, Head Admin, atau Sales Manager; default Head Admin/Admin pertama}';

    protected $description = 'Read-only: identify sales counted by Overview but missing or duplicated in the HM table';

    public function handle(): int
    {
        $viewer = $this->option('user') !== null
            ? User::find($this->option('user'))
            : User::role(['Head Admin', 'Admin'])->orderBy('id')->first();

        if (! $viewer || ! $viewer->hasAnyRole(['Admin', 'Head Admin', 'Sales Manager'])) {
            $this->error('Gunakan --user=ID akun Admin, Head Admin, atau Sales Manager.');
            return self::FAILURE;
        }

        $cutoff = PerformanceCutoff::current();
        $start = $cutoff ? Carbon::parse($cutoff->start_date)->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $cutoff ? Carbon::parse($cutoff->end_date)->endOfDay() : now()->endOfMonth()->endOfDay();
        $admin = $viewer->hasAnyRole(['Admin', 'Head Admin']);

        // Mirror DashboardController's status, date, unit and HM visibility rules.
        $base = SalesOrder::query()->where('sales_orders.status', 'selesai')
            ->where(function ($dateScope) use ($start, $end) {
                $dateScope->whereBetween('sales_orders.key_in_at', [$start, $end])
                    ->orWhere(function ($installScope) use ($start, $end) {
                        $installScope->whereNotNull('sales_orders.install_date')
                            ->whereDate('sales_orders.install_date', '>=', $start->toDateString())
                            ->whereDate('sales_orders.install_date', '<=', $end->toDateString());
                    });
            });
        $cardQuery = clone $base;
        if ($viewer->hasRole('Health Manager')) {
            HealthManagerNsScope::apply($cardQuery, $viewer, 'sales_orders', 'sales_orders.install_date');
        } elseif (! $admin) {
            $cardQuery->whereIn('sales_orders.sales_user_id', $viewer->teamUserIds()->concat([$viewer->id])->unique());
        }

        $columns = ['sales_orders.id', 'sales_orders.order_no', 'sales_orders.sales_user_id', 'sales_orders.key_in_at', 'sales_orders.install_date'];
        $orders = $cardQuery->join('sales_order_items as soi', function ($join) {
            $join->on('soi.sales_order_id', '=', 'sales_orders.id')->whereNull('soi.parent_item_id');
        })->join('products as p', 'p.id', '=', 'soi.product_id')
            ->select($columns)->selectRaw('COALESCE(SUM(soi.qty), 0) as units')
            ->groupBy(...$columns)->orderBy('sales_orders.id')->get();

        $managers = $viewer->hasRole('Sales Manager')
            ? $viewer->childrenUsers()->role('Health Manager')->where('users.status', 'Active')->get()
            : User::role('Health Manager')->where('status', 'Active')->get();
        $assignments = [];
        $scopes = [];
        $table = [];
        $outsideCardUnits = 0;
        foreach ($managers as $manager) {
            $query = clone $base;
            $scopes[$manager->id] = HealthManagerNsScope::apply($query, $manager, 'sales_orders', 'sales_orders.install_date');
            $ids = $query->pluck('sales_orders.id');
            foreach ($ids as $id) {
                $assignments[$id][] = $manager->name.' (#'.$manager->id.')';
            }
            $units = (int) (clone $query)->join('sales_order_items as soi', function ($join) {
                $join->on('soi.sales_order_id', '=', 'sales_orders.id')->whereNull('soi.parent_item_id');
            })->join('products as p', 'p.id', '=', 'soi.product_id')->sum('soi.qty');
            $outsideCardUnits += $units - (int) $orders->whereIn('id', $ids)->sum('units');
            $table[] = ['hm_id' => $manager->id, 'name' => $manager->name, 'units' => $units];
        }
        $missing = $orders->filter(fn ($order) => empty($assignments[$order->id]));
        $duplicates = $orders->filter(fn ($order) => count($assignments[$order->id] ?? []) > 1);
        $extraDuplicateUnits = (int) $duplicates->sum(fn ($order) => (count($assignments[$order->id]) - 1) * (int) $order->units);
        $cardUnits = (int) $orders->sum('units');
        $tableUnits = array_sum(array_column($table, 'units'));

        $this->line('READ-ONLY: tidak mengubah SO, user, hierarki, atau riwayat HM.');
        $this->line('Viewer: '.$viewer->name.' (#'.$viewer->id.')');
        $this->line('Closing date: '.$start->toDateString().' s/d '.$end->toDateString());
        $this->table(['Metrik', 'Units'], [
            ['Overview Total Net Sales', $cardUnits],
            ['Jumlah Total Units tabel HM', $tableUnits],
            ['Selisih card - tabel', $cardUnits - $tableUnits],
            ['SO card tanpa atribusi HM yang ditampilkan', (int) $missing->sum('units')],
            ['Unit tambahan akibat atribusi ganda', $extraDuplicateUnits],
            ['Unit tabel di luar cakupan card', $outsideCardUnits],
        ]);
        usort($table, fn ($a, $b) => $b['units'] <=> $a['units']);
        $this->table(['HM ID', 'Health Manager', 'Units'], $table);

        foreach ($missing as $order) {
            $seller = User::find($order->sales_user_id);
            $this->newLine();
            $this->line('SO tidak masuk tabel: '.($order->order_no ?: '#'.$order->id).' | ID '.$order->id.' | Units '.(int) $order->units);
            $this->line('Sales: '.($seller?->name ?? '(user tidak ditemukan)').' (#'.$order->sales_user_id.') | Status akun: '.($seller?->status ?? '-'));
            if ($seller?->health_manager_id) {
                $assigned = $seller->assignedHealthManager;
                $this->line('HM tersimpan: '.($assigned?->name ?? '-').' (#'.$seller->health_manager_id.') | Role sekarang: '.($assigned?->getRoleNames()->implode(', ') ?? '-'));
            }
            $this->line('Key-in: '.($order->key_in_at?->format('Y-m-d H:i:s') ?? 'NULL').' | Install: '.($order->install_date?->toDateString() ?? 'NULL'));
            $candidates = $managers->filter(fn ($manager) => $scopes[$manager->id]['scope_ids']->contains((int) $order->sales_user_id));
            if ($candidates->isEmpty()) {
                $this->line('Diagnosis: sales tidak berada dalam cakupan hierarki HM yang ditampilkan.');
            } else {
                $this->line('Diagnosis: sales ada dalam tim HM, tetapi SO dikecualikan oleh aturan periode HM/tanggal instalasi.');
                foreach ($candidates as $candidate) {
                    $this->line('HM kandidat: '.$candidate->name.' (#'.$candidate->id.') | Periode pengecualian: '.json_encode($scopes[$candidate->id]['exclusion_periods'][$order->sales_user_id] ?? []));
                }
            }
            $this->table(['User ID', 'Sales / Upline', 'Status akun', 'Role', 'Riwayat HM'], $this->ancestry((int) $order->sales_user_id));
        }
        foreach ($duplicates as $order) {
            $this->line('Atribusi ganda: '.($order->order_no ?: '#'.$order->id).' | Units '.(int) $order->units.' | '.implode(', ', $assignments[$order->id]));
        }
        if ($missing->isEmpty() && $duplicates->isEmpty() && $outsideCardUnits === 0) {
            $this->info('Semua SO pada card teratribusi tepat satu kali ke tabel HM.');
        }

        return self::SUCCESS;
    }

    private function ancestry(int $sellerId): array
    {
        $pending = [$sellerId];
        $visited = [];
        $rows = [];
        while ($pending) {
            $id = array_shift($pending);
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $user = User::find($id);
            $periods = DB::table('health_manager_periods')->where('user_id', $id)->get(['started_on', 'ended_on']);
            $rows[] = [$id, $user?->name ?? '-', $user?->status ?? '-', $user?->getRoleNames()->implode(', ') ?? '-', $periods->toJson()];
            foreach (DB::table('user_hierarchies')->where('child_user_id', $id)->pluck('parent_user_id') as $parentId) {
                $pending[] = (int) $parentId;
            }
        }

        return $rows;
    }
}
