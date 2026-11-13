<?php

namespace App\Services;

use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RecruitmentEventCheckout
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly CrmWebhookOutbox $crm,
    ) {}

    public function start(RecruitmentLead $lead): ?RecruitmentEventPayment
    {
        return DB::transaction(function () use ($lead) {
            $lead = RecruitmentLead::lockForUpdate()->findOrFail($lead->id);
            if (in_array($lead->event_payment_status, ['paid', 'refunded'], true)) {
                return null;
            }

            $payment = $lead->eventPayments()->latest('id')->first();
            if ($payment?->status === 'pending') {
                return $payment;
            }

            if (! config('midtrans.server_key')) {
                throw new RuntimeException('Event payment is not configured.');
            }

            $price = $payment?->only(['class_amount', 'service_fee']) ?? config('recruitment.event_payment');
            $orderId = 'REVT-'.Str::uuid();
            $returnUrl = route('subscriber-recruitment.payment.return', [$lead->source_slug, $lead->event_checkout_id]);
            $url = $this->midtrans->createSnapRedirectUrl([
                'transaction_details' => ['order_id' => $orderId, 'gross_amount' => array_sum($price)],
                'item_details' => [
                    ['id' => 'event-class', 'name' => 'Kelas pengenalan', 'price' => $price['class_amount'], 'quantity' => 1],
                    ['id' => 'app-service', 'name' => 'Biaya layanan aplikasi', 'price' => $price['service_fee'], 'quantity' => 1],
                ],
                'customer_details' => ['first_name' => $lead->full_name, 'phone' => $lead->phone_number],
                'callbacks' => ['finish' => $returnUrl, 'error' => $returnUrl],
            ]);

            $payment = $lead->eventPayments()->create($price + ['midtrans_order_id' => $orderId, 'checkout_url' => $url]);
            $lead->update(['event_payment_status' => 'awaiting_payment', 'event_paid_at' => null]);
            $lead->touch();
            $this->crm->record($lead, 'payment.updated', $payment);

            return $payment;
        });
    }

    public function sync(RecruitmentEventPayment $payment): void
    {
        $this->update($payment, $this->midtrans->transactionStatus($payment->midtrans_order_id));
    }

    public function update(RecruitmentEventPayment $payment, object $status): void
    {
        abort_unless(($status->order_id ?? null) === $payment->midtrans_order_id, 422, 'Payment order does not match.');
        abort_unless(is_numeric($status->gross_amount ?? null)
            && (float) $status->gross_amount === (float) $payment->total(), 422, 'Payment amount does not match.');

        DB::transaction(function () use ($payment, $status) {
            $lead = RecruitmentLead::lockForUpdate()->findOrFail($payment->recruitment_lead_id);
            $payment = RecruitmentEventPayment::lockForUpdate()->findOrFail($payment->id);
            $transactionStatus = strtolower((string) ($status->transaction_status ?? ''));
            $fraud = $status->fraud_status ?? null;
            $paid = (string) ($status->status_code ?? '') === '200'
                && (($transactionStatus === 'settlement' && ($fraud === null || $fraud === 'accept'))
                    || ($transactionStatus === 'capture' && $fraud === 'accept'));

            $nextStatus = $payment->status;
            if ($payment->status === 'paid' && in_array($transactionStatus, ['refund', 'chargeback'], true)) {
                $nextStatus = 'refunded';
            } elseif (! in_array($payment->status, ['paid', 'refunded'], true)) {
                $nextStatus = match (true) {
                    $paid => 'paid',
                    $transactionStatus === 'expire' => 'expired',
                    in_array($transactionStatus, ['deny', 'cancel', 'failure'], true) => 'failed',
                    default => $payment->status,
                };
            }

            $payment->fill([
                'status' => $nextStatus,
                'midtrans_transaction_id' => $status->transaction_id ?? $payment->midtrans_transaction_id,
                'paid_at' => $nextStatus === 'paid'
                    ? ($payment->paid_at ?? ($payment->status === 'paid' ? $lead->event_paid_at : null) ?? $this->paidAt($status))
                    : $payment->paid_at,
            ]);
            if (! $payment->isDirty()) {
                return;
            }
            $payment->save();
            $payment->refresh();

            // A late failure on an older attempt must not undo a successful payment.
            $payments = $lead->eventPayments()->latest('id')->get();
            $effective = $payments->firstWhere('status', 'paid')
                ?? $payments->firstWhere('status', 'refunded') ?? $payments->first();
            $lead->update([
                'event_payment_status' => $effective->status === 'pending' ? 'awaiting_payment' : $effective->status,
                'event_paid_at' => $effective->status === 'paid' ? $effective->paid_at : null,
            ]);
            // Include transaction-ID-only changes in incremental backup synchronization.
            $lead->touch();
            $this->crm->record($lead, 'payment.updated', $payment);
        });
    }

    private function paidAt(object $status): Carbon
    {
        // Midtrans timestamps without an offset use GMT+7; never use transaction_time
        // (the time the transaction was created) as proof of the time it was paid.
        if (! empty($status->settlement_time)) {
            try {
                return Carbon::parse($status->settlement_time, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
                // Retain the first server verification time if no usable time is supplied.
            }
        }

        return now();
    }
}
