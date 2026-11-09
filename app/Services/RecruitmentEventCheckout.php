<?php

namespace App\Services;

use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RecruitmentEventCheckout
{
    public function __construct(private readonly MidtransService $midtrans) {}

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

            return $lead->eventPayments()->create($price + ['midtrans_order_id' => $orderId, 'checkout_url' => $url]);
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
            $transactionStatus = $status->transaction_status ?? null;

            if ($payment->status === 'paid' && in_array($transactionStatus, ['refund', 'chargeback'], true)) {
                $payment->update(['status' => 'refunded']);
                $lead->update(['event_payment_status' => 'refunded', 'event_paid_at' => null]);
            }

            if ($payment->status !== 'pending') {
                return;
            }

            $fraud = $status->fraud_status ?? null;
            $paid = (string) ($status->status_code ?? '') === '200'
                && (($transactionStatus === 'settlement' && ($fraud === null || $fraud === 'accept'))
                    || ($transactionStatus === 'capture' && $fraud === 'accept'));

            if ($paid) {
                $payment->update(['status' => 'paid']);
                $lead->update(['event_payment_status' => 'paid', 'event_paid_at' => now()]);
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
                $payment->update(['status' => 'failed']);
            }
        });
    }
}
