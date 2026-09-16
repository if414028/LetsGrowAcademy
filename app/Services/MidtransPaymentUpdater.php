<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class MidtransPaymentUpdater
{
    public function update(Subscription $subscription, object $status): Subscription
    {
        return DB::transaction(function () use ($subscription, $status): Subscription {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $transactionStatus = (string) ($status->transaction_status ?? '');
            $fraudStatus = (string) ($status->fraud_status ?? '');

            $updates = [
                'midtrans_transaction_id' => $status->transaction_id ?? $subscription->midtrans_transaction_id,
                'payment_type' => $status->payment_type ?? $subscription->payment_type,
                'payment_payload' => (array) $status,
            ];

            $isPaid = $transactionStatus === 'settlement'
                || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

            if ($isPaid && $subscription->payment_status !== 'paid') {
                $currentEnd = $subscription->user->activeSubscription()?->ends_at;
                $startsAt = $currentEnd?->isFuture() ? $currentEnd->copy() : now();

                $updates += [
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'paid_at' => now(),
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMonthsNoOverflow($subscription->duration_months),
                    'reviewed_at' => now(),
                ];
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
                $updates += [
                    'payment_status' => match ($transactionStatus) {
                        'cancel' => 'cancelled',
                        'expire' => 'expired',
                        default => 'failed',
                    },
                    'status' => 'rejected',
                ];
            } elseif ($subscription->payment_status !== 'paid') {
                $updates['payment_status'] = 'pending';
            }

            $subscription->update($updates);

            return $subscription->refresh();
        });
    }
}
