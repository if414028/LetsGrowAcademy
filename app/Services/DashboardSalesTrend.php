<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\User;
use Carbon\Carbon;

class DashboardSalesTrend
{
    public function build(User $user, string $trend): array
    {
        $trend = in_array($trend, ['daily', 'weekly', 'monthly'], true) ? $trend : 'weekly';
        [$start, $end, $count] = match ($trend) {
            'daily' => [today()->subDays(29), today()->endOfDay(), 30],
            'monthly' => [now()->startOfMonth()->subMonths(5), now()->endOfMonth(), 6],
            default => [now()->startOfWeek()->subWeeks(7), now()->endOfWeek(), 8],
        };

        $labels = [];
        $keys = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $count; $i++) {
            $keys[] = $this->bucket($cursor, $trend);
            $labels[] = $cursor->format($trend === 'monthly' ? 'M Y' : 'd M');
            match ($trend) {
                'daily' => $cursor->addDay(),
                'monthly' => $cursor->addMonth(),
                default => $cursor->addWeek(),
            };
        }

        $perHealthManager = $user->hasAnyRole(['Sales Manager', 'Admin', 'Head Admin']);
        $scopeIds = $user->teamUserIds()->push((int) $user->id)->unique()->all();
        if ($perHealthManager) {
            $managers = User::role('Health Manager')->where('status', 'Active')->orderBy('name');
            if (! $user->hasAnyRole(['Admin', 'Head Admin'])) {
                $managers->whereIn('id', $scopeIds);
            }
            $seriesUsers = $managers->get();
        } else {
            $seriesUsers = collect([$user]);
        }

        $datasets = [];
        foreach ($seriesUsers as $seriesUser) {
            $query = SalesOrder::query()
                ->where('sales_orders.status', 'selesai')
                ->whereBetween('sales_orders.key_in_at', [$start, $end]);

            if ($seriesUser->hasRole('Health Manager')) {
                HealthManagerNsScope::apply($query, $seriesUser, 'sales_orders', 'sales_orders.install_date');
            } else {
                $query->whereIn('sales_orders.sales_user_id', $scopeIds);
            }

            // Match Overview units: bundle parents already contain their active child qty.
            $rows = $query->join('sales_order_items as soi', function ($join) {
                $join->on('soi.sales_order_id', '=', 'sales_orders.id')->whereNull('soi.parent_item_id');
            })
                ->join('products as p', 'p.id', '=', 'soi.product_id')
                ->selectRaw('DATE(sales_orders.key_in_at) as sale_day, SUM(soi.qty) as units')
                ->groupByRaw('DATE(sales_orders.key_in_at)')
                ->get();

            $totals = [];
            foreach ($rows as $row) {
                $key = $this->bucket(Carbon::parse($row->sale_day), $trend);
                $totals[$key] = ($totals[$key] ?? 0) + (int) $row->units;
            }
            $datasets[] = [
                'label' => $perHealthManager ? $seriesUser->name.($seriesUser->dst_code ? ' ('.$seriesUser->dst_code.')' : '') : 'Units',
                'data' => array_map(fn ($key) => $totals[$key] ?? 0, $keys),
            ];
        }

        return compact('trend', 'labels', 'datasets', 'perHealthManager');
    }

    private function bucket(Carbon $date, string $trend): string
    {
        return $date->format(match ($trend) {
            'daily' => 'Y-m-d',
            'monthly' => 'Y-m',
            default => 'oW',
        });
    }
}
