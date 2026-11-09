<?php

namespace App\Services;

use App\Models\GrowAdCampaign;
use Illuminate\Support\Facades\DB;

class GrowAdPaymentUpdater
{
    public function update(GrowAdCampaign $campaign, object $status): GrowAdCampaign
    {
        abort_unless(($status->order_id ?? '') === $campaign->midtrans_order_id, 422, 'Payment order does not match.');
        abort_unless(is_numeric($status->gross_amount ?? null)
            && (float) $status->gross_amount === (float) $campaign->total_amount, 422, 'Payment amount does not match.');

        return DB::transaction(function () use ($campaign, $status) {
            $campaign = GrowAdCampaign::lockForUpdate()->findOrFail($campaign->id);
            $transactionStatus = (string) ($status->transaction_status ?? '');
            $paid = $transactionStatus === 'settlement'
                || ($transactionStatus === 'capture' && ($status->fraud_status ?? '') === 'accept');
            $updates = ['payment_type' => $status->payment_type ?? $campaign->payment_type, 'payment_payload' => (array) $status];

            // Repeated or late notifications must never restart a paid campaign or undo settlement.
            if ($campaign->payment_status === 'paid' && in_array($transactionStatus, ['refund', 'partial_refund', 'chargeback', 'partial_chargeback'], true)) {
                $updates += ['payment_status' => 'refunded', 'status' => 'cancelled'];
            } elseif ($campaign->payment_status === 'refunded') {
                // A stale settlement cannot re-open a refunded order.
            } elseif ($paid && $campaign->payment_status !== 'paid') {
                $updates += ['payment_status' => 'paid', 'paid_at' => now(), 'status' => 'preparing'];
            } elseif ($campaign->payment_status !== 'paid' && in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
                $updates += ['payment_status' => match ($transactionStatus) {
                    'cancel' => 'cancelled', 'expire' => 'expired', default => 'failed',
                }, 'status' => 'cancelled'];
            }

            $campaign->update($updates);

            return $campaign->refresh();
        });
    }
}
