<?php

namespace App\Services;

use App\Models\CrmRegistrationTombstone;
use App\Models\RecruitmentEventPayment;
use App\Models\RecruitmentLead;

class CrmRegistrationData
{
    public function registration(RecruitmentLead $lead, ?int $sequence = null): array
    {
        $lead->loadMissing('eventPayments');
        $payments = $lead->eventPayments->sortByDesc('id');
        $payment = in_array($lead->event_payment_status, ['paid', 'refunded'], true)
            ? $payments->firstWhere('status', $lead->event_payment_status)
            : $payments->first();

        return [
            'registration_id' => $lead->registrationId(),
            'environment' => config('crm.environment'),
            'full_name' => $lead->full_name,
            'whatsapp_number' => '+'.$lead->phone_number,
            'domicile' => $lead->domicile,
            'payment_status' => $this->status($lead->event_payment_status),
            'registered_at' => $lead->created_at->toISOString(),
            'updated_at' => $lead->updated_at->toISOString(),
            'paid_at' => $lead->event_paid_at?->toISOString(),
            'whatsapp_consent' => $lead->whatsapp_consent,
            'whatsapp_consented_at' => $lead->whatsapp_consented_at?->toISOString(),
            'whatsapp_consent_version' => $lead->whatsapp_consent_version,
            'whatsapp_consent_revoked_at' => $lead->whatsapp_consent_revoked_at?->toISOString(),
            'seminar_id' => $lead->seminar_id,
            'seminar_name' => $lead->seminar_name,
            'batch_id' => $lead->batch_id,
            'batch_starts_at' => $lead->batch_starts_at?->toISOString(),
            'continue_payment_url' => $lead->continuePaymentUrl(),
            'source' => [
                'type' => $lead->source,
                'slug' => $lead->source_slug,
                'url' => $lead->source_url,
                'owner_user_id' => $lead->owner_user_id,
            ],
            'payment' => $payment ? $this->payment($payment) : null,
            'last_event_sequence' => $sequence ?? (int) $lead->crm_webhook_deliveries_max_id,
        ];
    }

    public function payment(RecruitmentEventPayment $payment): array
    {
        return [
            'transaction_id' => $payment->midtrans_transaction_id,
            'order_id' => $payment->midtrans_order_id,
            'payment_status' => $this->status($payment->status),
            'paid_at' => $payment->paid_at?->toISOString(),
            'updated_at' => $payment->updated_at->toISOString(),
            'class_amount' => (int) $payment->class_amount,
            'service_fee' => (int) $payment->service_fee,
            'selected_payment_method' => $payment->selected_payment_method,
            'payment_method_label' => $payment->pricing_details['payment_method_label'] ?? null,
            'transaction_fee' => $payment->pricing_details['transaction_fee'] ?? null,
            'gateway_vat_percent' => $payment->pricing_details['gateway_vat_percent'] ?? null,
            'gateway_vat_amount' => $payment->pricing_details['gateway_vat_amount'] ?? null,
            'merchant_covers_fee' => $payment->pricing_details['merchant_covers_fee'] ?? null,
            'total_amount' => $payment->total(),
            'currency' => 'IDR',
        ];
    }

    public function deletion(CrmRegistrationTombstone $tombstone): array
    {
        return $tombstone->only(['registration_id', 'environment']) + [
            'deleted_at' => $tombstone->deleted_at->toISOString(),
            'last_event_sequence' => $tombstone->last_event_sequence,
        ];
    }

    private function status(string $status): string
    {
        return match ($status) {
            'paid', 'failed', 'expired', 'refunded' => $status,
            default => 'pending',
        };
    }
}
