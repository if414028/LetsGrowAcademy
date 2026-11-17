<?php

namespace App\Services;

use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class RecruitmentEventCheckout
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly CrmWebhookOutbox $crm,
        private readonly GatewayPricing $pricing,
    ) {}

    public function start(RecruitmentLead $lead, mixed $paymentMethod = null): ?RecruitmentEventPayment
    {
        return DB::transaction(function () use ($lead, $paymentMethod) {
            $lead = RecruitmentLead::lockForUpdate()->findOrFail($lead->id);
            if (in_array($lead->event_payment_status, ['paid', 'refunded'], true)) {
                return null;
            }

            $payment = $lead->eventPayments()->latest('id')->first();
            if ($payment?->status === 'pending') {
                return $payment;
            }

            $validated = Validator::make(['payment_method' => $paymentMethod], [
                'payment_method' => ['required', 'string', Rule::in(array_keys($this->pricing->paymentMethods()))],
            ], [], ['payment_method' => 'metode pembayaran'])->validate();

            if (! config('midtrans.server_key') || ! config('midtrans.client_key')) {
                throw new RuntimeException('Event payment is not configured.');
            }

            $quote = $this->pricing->quote((int) ($payment?->class_amount ?? config('recruitment.event_payment.class_amount')), $validated['payment_method']);
            $orderId = 'REVT-'.Str::uuid();
            $returnUrl = route('subscriber-recruitment.payment.return', [$lead->source_slug, $lead->event_checkout_id]);
            $items = [['id' => 'event-class', 'name' => 'Kelas pengenalan', 'price' => $quote['subtotal'], 'quantity' => 1]];
            if ($quote['transaction_fee'] > 0) {
                $items[] = ['id' => 'transaction-fee', 'name' => 'Biaya transaksi', 'price' => $quote['transaction_fee'], 'quantity' => 1];
            }
            if ($quote['gateway_vat_amount'] > 0) {
                $items[] = ['id' => 'gateway-vat', 'name' => 'PPN biaya transaksi '.$quote['gateway_vat_percent'].'%', 'price' => $quote['gateway_vat_amount'], 'quantity' => 1];
            }
            $snap = $this->midtrans->createSnapTransaction([
                'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $quote['total']],
                'item_details' => $items,
                'enabled_payments' => [$quote['snap_payment_method']],
                'customer_details' => ['first_name' => $lead->full_name, 'phone' => $lead->phone_number],
                'callbacks' => ['finish' => $returnUrl, 'error' => $returnUrl],
            ]);

            if (empty($snap->token) || empty($snap->redirect_url)) {
                throw new RuntimeException('Incomplete event checkout response.');
            }
            $payment = $lead->eventPayments()->create([
                'class_amount' => $quote['subtotal'],
                'service_fee' => $quote['transaction_fee'] + $quote['gateway_vat_amount'],
                'selected_payment_method' => $validated['payment_method'],
                'pricing_details' => $quote,
                'snap_token' => $snap->token,
                'midtrans_order_id' => $orderId,
                'checkout_url' => $snap->redirect_url,
            ]);
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
