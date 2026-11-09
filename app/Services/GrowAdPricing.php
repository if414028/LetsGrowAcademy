<?php

namespace App\Services;

class GrowAdPricing
{
    public function quote(int $dailyBudget, int $days): array
    {
        $budget = $dailyBudget * $days;
        $vatPercent = (int) config('grow-ads.vat_percent');
        $vat = (int) ceil($budget * $vatPercent / 100);
        $fee = (int) config('grow-ads.service_fee');

        return [
            'daily_budget' => $dailyBudget, 'duration_days' => $days,
            'ad_budget' => $budget, 'vat_percent' => $vatPercent, 'vat_amount' => $vat,
            'service_fee' => $fee, 'total_amount' => $budget + $vat + $fee,
        ];
    }
}
