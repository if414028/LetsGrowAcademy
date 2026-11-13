<?php

namespace App\Services;

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
            'registration_id' => 'LGA-'.$lead->id,
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
            'total_amount' => $payment->total(),
            'currency' => 'IDR',
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
